<?php

function withImapEnvironment(array $values, Closure $callback): mixed
{
    $keys = [
        'IMAP_HOST',
        'IMAP_PORT',
        'IMAP_ENCRYPTION',
        'IMAP_FOLDER',
        'IMAP_SERVER',
        'IMAP_MAILBOX',
        'IMAP_USERNAME',
        'IMAP_USER',
        'IMAP_PASSWORD',
    ];

    $previous = [];

    foreach ($keys as $key) {
        $previous[$key] = [
            'env' => $_ENV[$key] ?? null,
            'server' => $_SERVER[$key] ?? null,
            'getenv' => getenv($key) === false ? null : getenv($key),
        ];

        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    }

    foreach ($values as $key => $value) {
        putenv($key.'='.$value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    try {
        return $callback(require config_path('imap.php'));
    } finally {
        foreach ($keys as $key) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);

            if ($previous[$key]['getenv'] !== null) {
                putenv($key.'='.$previous[$key]['getenv']);
            }

            if ($previous[$key]['env'] !== null) {
                $_ENV[$key] = $previous[$key]['env'];
            }

            if ($previous[$key]['server'] !== null) {
                $_SERVER[$key] = $previous[$key]['server'];
            }
        }
    }
}

test('imap config builds a direct ssl spacemail mailbox from split variables', function () {
    withImapEnvironment([
        'IMAP_HOST' => 'mail.spacemail.com',
        'IMAP_PORT' => '993',
        'IMAP_ENCRYPTION' => 'ssl',
        'IMAP_FOLDER' => 'INBOX',
        'IMAP_USERNAME' => 'buzon@example.com',
        'IMAP_PASSWORD' => 'secret-password',
    ], function (array $config) {
        expect($config['host'])->toBe('mail.spacemail.com')
            ->and($config['port'])->toBe(993)
            ->and($config['encryption'])->toBe('ssl')
            ->and($config['mailbox'])->toBe('{mail.spacemail.com:993/imap/ssl}INBOX')
            ->and($config['username'])->toBe('buzon@example.com')
            ->and($config['password'])->toBe('secret-password');
    });
});

test('imap host variables take precedence over legacy mailbox variables', function () {
    withImapEnvironment([
        'IMAP_HOST' => 'mail.spacemail.com',
        'IMAP_PORT' => '993',
        'IMAP_ENCRYPTION' => 'ssl',
        'IMAP_MAILBOX' => '{imap.gmail.com:993/imap/ssl}INBOX',
        'IMAP_USER' => 'legacy@example.com',
        'IMAP_USERNAME' => 'buzon@example.com',
    ], function (array $config) {
        expect($config['mailbox'])->toBe('{mail.spacemail.com:993/imap/ssl}INBOX')
            ->and($config['username'])->toBe('buzon@example.com');
    });
});
