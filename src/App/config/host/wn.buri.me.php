<?php

return [
    'env'   => DEVELOPMENT,
    'connect'   => [
        'memcache'  => [
            'server'    => '185.26.122.38',
            'port'      => 11211,
        ],
        'mysql'     => [
            'dsn'       => 'mysql:dbname=host1467240_zay;host=localhost',
            // 'host'      => 'host1467240',
            // 'dbname'    => 'host1467240_zay',
            'username'  => 'host1467240_zay',
            'password'  => 'berezay',
        ],
        'sqlite'    => [
            'dsn'   => 'sqlite:src/App/data/data.sdb',
        ],
    ],
    'mail'  => [
        'is_smtp'   => true,
        'is_imap'   => true,
        'smtp'      => 'mail.hostland.ru',
        'pop3'      => 'mail.hostland.ru',
        'imap'      => 'mail.hostland.ru',
        'smtp_port' => 587,
        'imap_box'  => "{mail.hostland.ru:993/imap/ssl}INBOX",
        'pop3_box'  => "{mail.hostland.ru:995/pop3/ssl}INBOX",
        'mailboxes' => ['info@buri.me' => 'buri2128506'],
    ]
];
