<?php

namespace App\Console\Commands;

use App\Services\Email\NetflixEmailParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcesarEmailsPedidos extends Command
{
    protected $signature = 'emails:procesar-pedidos';

    protected $description = 'Importa correos IMAP configurados y extrae codigos o enlaces recientes';

    public function __construct(
        private readonly NetflixEmailParser $netflixEmailParser,
    ) {
        parent::__construct();
    }

    public function handle()
    {
        if (! function_exists('imap_open')) {
            $this->log('La extension PHP IMAP no esta habilitada.');

            return Command::FAILURE;
        }

        $lock = Cache::lock('procesar_emails_pedidos_lock', 120);

        if (! $lock->get()) {
            $this->log('Ya hay una ejecucion en curso, saliendo.');

            return Command::SUCCESS;
        }

        try {
            $this->procesar();
        } finally {
            optional($lock)->release();
        }

        return Command::SUCCESS;
    }

    protected function log(string $message): void
    {
        $line = '['.now()->format('Y-m-d H:i:s').'] '.$message;
        $this->info($line);
        Log::channel('single')->info($line);
    }

    protected function procesar(): void
    {
        $this->log('Iniciando importador IMAP.');

        $imapHost = (string) config('imap.host', '');
        $imapPort = (int) config('imap.port', 993);
        $imapEncryption = (string) config('imap.encryption', 'ssl');
        $imapServidor = (string) config('imap.mailbox');
        $imapUsuario = (string) config('imap.username');
        $imapPassword = (string) config('imap.password');
        $searchCriteria = trim((string) config('imap.search_criteria', 'UNSEEN')) ?: 'UNSEEN';
        $dbTabla = (string) config('imap.processed_table');

        $deletedRows = $this->cleanupExpiredEmails($dbTabla);
        $this->log("Cleanup emails_pedidos: {$deletedRows} fila(s) expiradas eliminadas.");

        $this->log('IMAP host: '.($imapHost !== '' ? $imapHost : 'NO CONFIGURADO'));
        $this->log('IMAP port: '.$imapPort);
        $this->log('IMAP encryption: '.$imapEncryption);
        $this->log('IMAP username: '.$this->maskEmail($imapUsuario));
        $this->log('IMAP mailbox flags: '.$this->maskMailbox($imapServidor));

        if ($imapUsuario === '' || $imapPassword === '') {
            $this->log('IMAP no configurado: faltan credenciales en variables de entorno.');

            return;
        }

        $connectionOk = $this->checkTcpConnection($imapHost, $imapPort);
        $this->log('IMAP connection: '.($connectionOk ? 'OK' : 'ERROR'));

        if (! $connectionOk) {
            return;
        }

        $inbox = @imap_open($imapServidor, $imapUsuario, $imapPassword);
        if (! $inbox) {
            $error = (string) imap_last_error();
            $this->log('IMAP authentication: '.$this->imapAuthStatus($error));
            $this->log('Error conexion IMAP: '.$this->sanitizeImapError($error, $imapUsuario));

            return;
        }

        $this->log('IMAP authentication: OK');

        $emailsUids = imap_search($inbox, $searchCriteria, SE_UID);

        if (! $emailsUids || ! is_array($emailsUids)) {
            imap_close($inbox);
            $this->log("No hay correos para criterio IMAP: {$searchCriteria}.");

            return;
        }

        $this->log('Encontrados '.count($emailsUids).' correos.');

        foreach ($emailsUids as $uid) {
            $this->procesarCorreoUid($inbox, (int) $uid, $dbTabla, $imapUsuario);
        }

        imap_close($inbox);
        $this->log('Fin del proceso.');
    }

    public function cleanupExpiredEmails(string $dbTabla): int
    {
        try {
            $cutoff = now()->subDay();

            return DB::table($dbTabla)
                ->where(function ($query) use ($cutoff) {
                    $query->whereNotNull('fecha_procesado_db')
                        ->where('fecha_procesado_db', '<=', $cutoff);
                })
                ->orWhere(function ($query) use ($cutoff) {
                    $query->whereNull('fecha_procesado_db')
                        ->where('fecha_recibido', '<=', $cutoff);
                })
                ->delete();
        } catch (\Throwable $e) {
            $this->log('Error limpieza: '.$e->getMessage());

            return 0;
        }
    }

    protected function procesarCorreoUid($inbox, int $uid, string $dbTabla, string $imapUsuario): void
    {
        $this->log("--- Procesando UID: {$uid} ---");

        $messageNo = imap_msgno($inbox, $uid);
        $header = @imap_headerinfo($inbox, $messageNo);

        if (! $header) {
            $this->log('No se pudo leer cabecera.');

            return;
        }

        $asunto = $this->decodeMime($header->subject ?? 'Sin Asunto');
        $remitente = $this->remitente($header);
        $fecha = date('Y-m-d H:i:s', $header->udate ?? time());

        $rawHeaders = @imap_fetchheader($inbox, $uid, FT_UID) ?: '';
        $rawBody = @imap_body($inbox, $uid, FT_UID) ?: '';
        $structure = @imap_fetchstructure($inbox, $uid, FT_UID);
        $messageId = $this->extractHeaderValue($rawHeaders, 'Message-ID');
        $threadId = $this->extractThreadId($rawHeaders);
        $imapUid = (string) $uid;

        $textoPlano = '';
        $html = '';

        if ($structure) {
            [$textoPlano] = $this->findBodyPart($structure, 'PLAIN', '', $inbox, $uid);
            [$html, $htmlSection] = $this->findBodyPart($structure, 'HTML', '', $inbox, $uid);

            if (! $htmlSection && $textoPlano !== '') {
                $html = '<pre>'.htmlspecialchars($textoPlano, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</pre>';
            }
        }

        $textoPlanoOriginal = $this->toUtf8($textoPlano);
        $htmlOriginal = $this->toUtf8($html);
        $searchableBody = $this->bodyToText($textoPlanoOriginal."\n".$htmlOriginal);
        $parsedData = $this->netflixEmailParser->parse($asunto, $htmlOriginal, $textoPlanoOriginal);
        $datosExtraidos = $this->buildStoredPayload(
            $parsedData,
            $asunto,
            $this->netflixEmailParser->extractLinks($htmlOriginal, $textoPlanoOriginal)
        );

        $destinatarioOriginal = $this->extraerDestinatarioOriginal($searchableBody, $rawHeaders, $header, $imapUsuario);

        try {
            $existsQuery = DB::table($dbTabla);

            if ($messageId !== null) {
                $existsQuery->where('message_id', $messageId);
            } else {
                $existsQuery->where(function ($query) use ($imapUid, $destinatarioOriginal, $asunto, $fecha) {
                    $query->where('imap_uid', $imapUid)
                        ->orWhere(function ($fallback) use ($destinatarioOriginal, $asunto, $fecha) {
                            $fallback->where('destinatario_original', $destinatarioOriginal)
                                ->where('asunto', $asunto)
                                ->where('fecha_recibido', $fecha);
                        });
                });
            }

            $exists = $existsQuery->exists();

            if ($exists) {
                $this->log('Correo ya procesado anteriormente, se omite duplicado.');
                $this->markSeen($inbox, $uid);

                return;
            }

            DB::table($dbTabla)->insert([
                'message_id' => $messageId,
                'thread_id' => $threadId,
                'imap_uid' => $imapUid,
                'destinatario_original' => $destinatarioOriginal,
                'asunto' => $asunto,
                'remitente' => $remitente,
                'fecha_recibido' => $fecha,
                'raw_email' => trim($rawHeaders."\r\n".$rawBody),
                'html_body_original' => $htmlOriginal,
                'text_body_original' => $textoPlanoOriginal,
                'cuerpo_html' => $htmlOriginal,
                'datos_extraidos' => json_encode($datosExtraidos, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'extraction_status' => $datosExtraidos['extraction_status'] ?? 'failed',
            ]);

            $this->log('Correo guardado con '.$this->summary($datosExtraidos).'.');
            $this->markSeen($inbox, $uid);
        } catch (\Throwable $e) {
            $this->log('ERROR DB: '.$e->getMessage());
        }
    }

    private function buildStoredPayload(array $parsedData, string $subject, array $foundLinks): array
    {
        $type = $parsedData['type'] ?? 'unknown';
        $code = $parsedData['code'] ?? null;
        $actionUrl = $parsedData['action_url'] ?? null;

        return [
            'platform' => $parsedData['platform'] ?? 'Netflix',
            'type' => $type,
            'code' => $code,
            'action_url' => $actionUrl,
            'duration_minutes' => $parsedData['duration_minutes'] ?? null,
            'subject' => $subject,
            'value' => $code ?? $actionUrl ?? null,
            'extraction_status' => match (true) {
                $type === 'login_code' && $code !== null => 'success',
                in_array($type, ['household_update', 'temporary_access'], true) && $actionUrl !== null => 'success',
                default => 'failed',
            },
            'found_links' => array_values(array_unique(array_filter($foundLinks, fn ($value) => trim((string) $value) !== ''))),
        ];
    }

    private function summary(array $payload): string
    {
        return match ($payload['type'] ?? 'unknown') {
            'login_code' => 'login_code: '.($payload['code'] ?? 'sin_codigo'),
            'household_update' => 'household_update: '.($payload['action_url'] ?? 'sin_link'),
            'temporary_access' => 'temporary_access: '.($payload['action_url'] ?? 'sin_link'),
            default => 'unknown / revisable',
        };
    }

    private function extraerDestinatarioOriginal(string $textoPlano, string $rawHeaders, object $header, string $imapUsuario): string
    {
        if (preg_match('/(?:mensaje|message)\s+(?:a|to)\s+\[([^\]]+@[^\]]+)\]/iu', $textoPlano, $matches)) {
            $candidate = filter_var(trim($matches[1]), FILTER_SANITIZE_EMAIL);
            if ($candidate !== '' && strcasecmp($candidate, $imapUsuario) !== 0) {
                return $candidate;
            }
        }

        $patterns = [
            '/Netflix te envi[oÃ³] este mensaje a\s+\[([^\]]+)\]/iu',
            '/sent this message to\s+\[([^\]]+)\]/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $textoPlano, $matches)) {
                return filter_var(trim($matches[1]), FILTER_SANITIZE_EMAIL);
            }
        }

        foreach (['X-Original-To', 'Original-Recipient', 'Envelope-To', 'Apparently-To', 'X-Forwarded-To'] as $headerName) {
            if (preg_match('/^'.preg_quote($headerName, '/').':\s*(?:rfc822;)?\s*<?([^\s<>;,]+@[^\s<>;,]+)>?/im', $rawHeaders, $matches)) {
                $candidate = filter_var(trim($matches[1]), FILTER_SANITIZE_EMAIL);
                if ($candidate !== '' && strcasecmp($candidate, $imapUsuario) !== 0) {
                    return $candidate;
                }
            }
        }

        if (preg_match('/^Delivered-To:\s*([^\s<>]+)/im', $rawHeaders, $matches)) {
            $deliveredTo = trim($matches[1]);
            if (strcasecmp($deliveredTo, $imapUsuario) !== 0) {
                return $deliveredTo;
            }
        }

        if (isset($header->to[0]->mailbox, $header->to[0]->host)) {
            return $header->to[0]->mailbox.'@'.$header->to[0]->host;
        }

        return 'destino_error@igruben.lat';
    }

    private function cleanEmailBody(string $body): string
    {
        $body = quoted_printable_decode($body);
        $body = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $body = preg_replace("/=\r\n|=\n|=\r/", '', $body) ?? $body;
        $body = str_replace('=3D', '=', $body);

        return str_replace(["\r", "\n"], ' ', $body);
    }

    private function bodyToText(string $body): string
    {
        $body = $this->cleanEmailBody($body);
        $body = preg_replace('/<\s*br\s*\/?>/i', "\n", $body) ?? $body;
        $body = preg_replace('/<\/(p|div|td|tr|h\d)>/i', "\n", $body) ?? $body;
        $body = strip_tags($body);
        $body = preg_replace('/\s+/', ' ', $body) ?? $body;

        return trim($body);
    }

    private function toUtf8(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (! mb_check_encoding($value, 'UTF-8')) {
            return @mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1,Windows-1252,UTF-8') ?: $value;
        }

        return $value;
    }

    private function decodeMime(string $value): string
    {
        return str_replace('_', ' ', mb_decode_mimeheader($value));
    }

    private function remitente(object $header): string
    {
        if (isset($header->from[0]->mailbox, $header->from[0]->host)) {
            return $header->from[0]->mailbox.'@'.$header->from[0]->host;
        }

        return 'Desconocido';
    }

    private function markSeen($inbox, int $uid): void
    {
        if (filter_var(config('imap.mark_seen', true), FILTER_VALIDATE_BOOLEAN)) {
            @imap_setflag_full($inbox, $uid, '\\Seen', ST_UID);
        }
    }

    private function checkTcpConnection(string $host, int $port): bool
    {
        if ($host === '' || $port <= 0) {
            return false;
        }

        $errno = 0;
        $errstr = '';
        $connection = @fsockopen($host, $port, $errno, $errstr, 10);

        if (! is_resource($connection)) {
            return false;
        }

        fclose($connection);

        return true;
    }

    private function maskEmail(string $email): string
    {
        if ($email === '') {
            return 'NO CONFIGURADO';
        }

        if (! str_contains($email, '@')) {
            return substr($email, 0, 2).'***';
        }

        [$local, $domain] = explode('@', $email, 2);
        $prefix = mb_substr($local, 0, min(3, mb_strlen($local)));

        return $prefix.'***@'.$domain;
    }

    private function maskMailbox(string $mailbox): string
    {
        return preg_replace('/\{([^}:]+):(\d+)([^}]*)\}.*/', '{$1:$2$3}***', $mailbox) ?: 'NO CONFIGURADO';
    }

    private function imapAuthStatus(string $error): string
    {
        $normalized = mb_strtolower($error);

        if (str_contains($normalized, 'auth') || str_contains($normalized, 'login') || str_contains($normalized, 'password')) {
            return 'ERROR';
        }

        return 'ERROR';
    }

    private function sanitizeImapError(string $error, string $username): string
    {
        $password = (string) config('imap.password');

        if ($password !== '') {
            $error = str_replace($password, '[hidden]', $error);
        }

        if ($username !== '') {
            $error = str_replace($username, $this->maskEmail($username), $error);
        }

        return $error !== '' ? $error : 'Sin detalle del servidor IMAP.';
    }

    private function extractHeaderValue(string $headers, string $name): ?string
    {
        if (! preg_match('/^'.preg_quote($name, '/').':\s*(.+)$/im', $headers, $matches)) {
            return null;
        }

        $value = trim((string) ($matches[1] ?? ''));

        return $value !== '' ? $value : null;
    }

    private function extractThreadId(string $headers): ?string
    {
        foreach (['X-GM-THRID', 'Thread-Index', 'References', 'In-Reply-To'] as $headerName) {
            $value = $this->extractHeaderValue($headers, $headerName);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    protected function findBodyPart($structure, string $mime, string $sec = '', $inbox = null, int $uid = 0): array
    {
        $body = '';
        $fsec = '';
        $enc = -1;

        if (! is_object($structure)) {
            return ['', '', -1];
        }

        if (isset($structure->parts)) {
            foreach ($structure->parts as $i => $part) {
                $curr = ($sec ? $sec.'.' : '').($i + 1);

                if (isset($part->subtype) && strcasecmp($part->subtype, $mime) === 0) {
                    $fsec = $curr;
                    $enc = $part->encoding;
                    break;
                }

                if (isset($part->parts)) {
                    [$bodyNested, $sectionNested, $encodingNested] = $this->findBodyPart($part, $mime, $curr, $inbox, $uid);
                    if ($sectionNested) {
                        $body = $bodyNested;
                        $fsec = $sectionNested;
                        $enc = $encodingNested;
                        break;
                    }
                }
            }
        } elseif (isset($structure->subtype) && strcasecmp($structure->subtype, $mime) === 0) {
            $fsec = '1';
            $enc = $structure->encoding;
        }

        if ($fsec && $inbox && $body === '') {
            $body = @imap_fetchbody($inbox, $uid, $fsec, FT_UID) ?: '';
            if ($enc === 3) {
                $body = base64_decode($body) ?: '';
            } elseif ($enc === 4) {
                $body = quoted_printable_decode($body);
            }
        }

        return [$body, $fsec, $enc];
    }
}
