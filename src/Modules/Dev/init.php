<?php

use WN\Core\{Route, I18n};

Route::set('~test', '~test(/<file>)(?<query>)')
    ->filter([
        'file' => '[-_0-9a-z/.]+',
        
    ])
    ->defaults([
        'controller' => 'WN\Dev\Controller\Test',
    ])
    ;

// WN\Core\Helper\Upload::$default_directory = 'public/test_upload';