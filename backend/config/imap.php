<?php

return [
    'mailbox' => env('IMAP_SERVER') ?: env('IMAP_MAILBOX', '{mail.spacemail.com:993/imap/ssl}INBOX'),
    'username' => env('IMAP_USER') ?: env('IMAP_USERNAME'),
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
