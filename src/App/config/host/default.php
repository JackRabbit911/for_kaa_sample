<?php

return [
    'env'   => DEVELOPMENT,
    'connect'   => [
        'mysql'     => [
            'dsn'       => 'mysql:dbname=test;host=mysql',
            'username'  => 'test',
            'password'  => '123456',
        ],
        'sqlite'    => [
            'dsn'   => 'sqlite:src/App/data/data.sdb',
        ],
        'memcache'  => [
            'server'    => 'localhost',
            'port'      => 11211,
        ],
    ],
    'mail'  => [
        'is_smtp'   => false,
        'is_imap'   => false,
        'mailboxes' => ['info@buri.me' => ''],
    ]
];
