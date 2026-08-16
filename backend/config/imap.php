<?php

$host = trim((string) env('IMAP_HOST', ''));
$port = (int) env('IMAP_PORT', 993);
$encryption = strtolower(trim((string) env('IMAP_ENCRYPTION', 'ssl')));
$folder = trim((string) env('IMAP_FOLDER', 'INBOX')) ?: 'INBOX';

$mailbox = env('IMAP_SERVER') ?: env('IMAP_MAILBOX');

if ($host !== '') {
    $flags = '/imap';

    if ($encryption === 'ssl') {
        $flags .= '/ssl';
    } elseif ($encryption === 'tls') {
        $flags .= '/tls';
    } elseif (in_array($encryption, ['none', 'notls'], true)) {
        $flags .= '/notls';
    }

    $mailbox = sprintf('{%s:%d%s}%s', $host, $port > 0 ? $port : 993, $flags, $folder);
}

return [
    'host' => $host !== '' ? $host : preg_replace('/^\{([^}:]+).*$/', '$1', (string) ($mailbox ?: 'mail.spacemail.com')),
    'port' => $port > 0 ? $port : 993,
    'encryption' => $encryption ?: 'ssl',
    'folder' => $folder,
    'mailbox' => $mailbox ?: '{mail.spacemail.com:993/imap/ssl}INBOX',
    'username' => env('IMAP_USERNAME') ?: env('IMAP_USER'),
    'password' => env('IMAP_PASSWORD'),
    'search_criteria' => env('IMAP_SEARCH_CRITERIA', 'UNSEEN'),
    'mark_seen' => env('IMAP_MARK_SEEN', true),
    'processed_table' => env('EMAILS_PEDIDOS_TABLE', 'emails_pedidos'),
    'retention_minutes' => (int) env('EMAILS_MAX_MINUTES', 7),
    'keywords' => [
        'temporal' => env('IMAP_KEYWORDS_TEMPORAL', 'tu codigo de acceso temporal,codigo de acceso temporal,acceso temporal,codigo temporal,tu codigo de inicio de sesion,codigo de inicio de sesion,ingresa este codigo para iniciar sesion,iniciar sesion en netflix,login code,sign in code,code'),
        'hogar' => env('IMAP_KEYWORDS_HOGAR', 'solicitaste actualizar tu hogar con netflix,actualizar tu hogar con netflix,hogar con netflix,update primary location,update-primary-location,household'),
    ],
];
