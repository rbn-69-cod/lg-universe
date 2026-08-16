<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcesarEmailsPedidos extends Command
{
    protected $signature = 'emails:procesar-pedidos';

    protected $description = 'Importa correos IMAP configurados y extrae codigos o enlaces recientes';

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

        $this->limpiarCorreosAntiguos($dbTabla);

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

    private function limpiarCorreosAntiguos(string $dbTabla): void
    {
        try {
            $minutos = min(7, max(1, (int) config('imap.retention_minutes')));
            $cutoff = now()->subMinutes($minutos);

            DB::table($dbTabla)
                ->where(function ($query) use ($cutoff) {
                    $query->whereNotNull('fecha_procesado_db')
                        ->where('fecha_procesado_db', '<', $cutoff);
                })
                ->orWhere(function ($query) use ($cutoff) {
                    $query->whereNull('fecha_procesado_db')
                        ->where('fecha_recibido', '<', $cutoff);
                })
                ->delete();
        } catch (\Throwable $e) {
            $this->log('Error limpieza: '.$e->getMessage());
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
        $asuntoNorm = $this->norm($asunto);
        $remitente = $this->remitente($header);
        $fecha = date('Y-m-d H:i:s', $header->udate ?? time());

        $rawHeaders = @imap_fetchheader($inbox, $uid, FT_UID) ?: '';
        $structure = @imap_fetchstructure($inbox, $uid, FT_UID);

        $textoPlano = '';
        $html = '';

        if ($structure) {
            [$textoPlano] = $this->findBodyPart($structure, 'PLAIN', '', $inbox, $uid);
            [$html, $htmlSection] = $this->findBodyPart($structure, 'HTML', '', $inbox, $uid);

            if (! $htmlSection && $textoPlano !== '') {
                $html = '<pre>'.htmlspecialchars($textoPlano, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</pre>';
            }
        }

        $textoPlano = $this->toUtf8($textoPlano);
        $html = $this->toUtf8($html);
        $searchableBody = $this->bodyToText($textoPlano."\n".$html);
        $searchableNorm = $this->norm($searchableBody);

        $esTemporal = $this->esCorreoTemporal($asuntoNorm, $searchableNorm);
        $esHogar = $this->esCorreoHogar($asuntoNorm, $searchableNorm);

        if (! $esTemporal && ! $esHogar) {
            $this->log("Asunto ignorado: {$asunto}");
            $this->markSeen($inbox, $uid);

            return;
        }

        $destinatarioOriginal = $this->extraerDestinatarioOriginal($searchableBody, $rawHeaders, $header, $imapUsuario);
        $datosExtraidos = $this->extraerDatosNetflix($html, $textoPlano, $esTemporal, $esHogar);

        try {
            $exists = DB::table($dbTabla)
                ->where('destinatario_original', $destinatarioOriginal)
                ->where('asunto', $asunto)
                ->where('fecha_recibido', $fecha)
                ->exists();

            if ($exists) {
                $this->log('Correo ya procesado anteriormente, se omite duplicado.');
                $this->markSeen($inbox, $uid);

                return;
            }

            DB::table($dbTabla)->insert([
                'destinatario_original' => $destinatarioOriginal,
                'asunto' => $asunto,
                'remitente' => $remitente,
                'fecha_recibido' => $fecha,
                'cuerpo_html' => $html,
                'datos_extraidos' => json_encode($datosExtraidos, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ]);

            if ($datosExtraidos === []) {
                $this->log('Correo guardado, pero no se encontraron codigos ni enlaces.');
            } else {
                $this->log('Correo guardado con '.$this->summary($datosExtraidos).'.');
            }

            $this->markSeen($inbox, $uid);
        } catch (\Throwable $e) {
            $this->log('ERROR DB: '.$e->getMessage());
        }
    }

    private function esCorreoTemporal(string $asuntoNorm, string $bodyNorm): bool
    {
        $haystack = $asuntoNorm.' '.$bodyNorm;
        $keywords = $this->configuredKeywords('temporal');

        foreach ($keywords as $keyword) {
            if (str_contains($haystack, $this->norm($keyword))) {
                return true;
            }
        }

        return false;
    }

    private function esCorreoHogar(string $asuntoNorm, string $bodyNorm): bool
    {
        $haystack = $asuntoNorm.' '.$bodyNorm;
        $keywords = $this->configuredKeywords('hogar');

        foreach ($keywords as $keyword) {
            if (str_contains($haystack, $this->norm($keyword))) {
                return true;
            }
        }

        return false;
    }

    private function configuredKeywords(string $type): array
    {
        $raw = (string) config("imap.keywords.{$type}", '');

        return array_values(array_filter(array_map(
            fn (string $keyword) => trim($keyword),
            explode(',', $raw)
        )));
    }

    private function extraerDatosNetflix(string $html, string $textoPlano, bool $esTemporal, bool $esHogar): array
    {
        $body = $this->cleanEmailBody($html."\n".$textoPlano);
        $resultados = [];

        if ($esTemporal) {
            $codigo = $this->extraerCodigoNetflix($body);
            if ($codigo !== null) {
                $resultados[] = $codigo;
            }
        }

        if ($esHogar || $resultados === []) {
            $resultados = array_merge($resultados, $this->extraerLinksNetflix($body));
        }

        return array_values(array_unique(array_filter($resultados)));
    }

    private function summary(array $values): string
    {
        $links = 0;
        $codes = 0;

        foreach ($values as $value) {
            $value = trim((string) $value);

            if (str_starts_with($value, 'http')) {
                $links++;
            } elseif (preg_match('/^\d{4,8}$/', $value)) {
                $codes++;
            }
        }

        return "{$links} link(s), {$codes} codigo(s)";
    }

    private function extraerCodigoNetflix(string $body): ?string
    {
        $text = $this->bodyToText($body);

        $patterns = [
            '/Ingresa\s+este\s+c[oó]digo\s+para\s+iniciar\s+sesi[oó]n\s*[:\-]?\s*(\d{4,8})/iu',
            '/c[oó]digo\s+para\s+iniciar\s+sesi[oó]n\s*[:\-]?\s*(\d{4,8})/iu',
            '/c[oó]digo\s+de\s+inicio\s+de\s+sesi[oó]n\s*[:\-]?\s*(\d{4,8})/iu',
            '/\b(\d{4,8})\b\s+Ingresa\s+este\s+c[oó]digo/iu',
            '/\b(\d{4,8})\b/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    private function extraerLinksNetflix(string $body): array
    {
        $links = [];
        $patterns = [
            '/(https?:\/\/www\.netflix\.com\/[^\s"\'<>]*?(?:update-primary-location|travel\/verify)[^\s"\'<>]*)/i',
            '/(https?:\/\/www\.netflix\.com\/[^\s"\'<>]*?nftoken=[^\s"\'<>]*)/i',
            '/(https?:\/\/www\.netflix\.com\/[^\s"\'<>]{50,})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $body, $matches)) {
                $links = array_merge($links, $matches[0]);
            }

            if ($links !== []) {
                break;
            }
        }

        return array_values(array_unique(array_map(
            fn ($link) => html_entity_decode($link, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            $links
        )));
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
            '/Netflix te envi[oó] este mensaje a\s+\[([^\]]+)\]/iu',
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

    private function norm(string $txt): string
    {
        $txt = mb_strtolower($txt, 'UTF-8');
        $txt = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $txt) ?: $txt;
        $txt = preg_replace('/[^a-z0-9\s\?\!]/', ' ', $txt) ?? $txt;
        $txt = preg_replace('/\s+/', ' ', $txt) ?? $txt;

        return trim($txt);
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
