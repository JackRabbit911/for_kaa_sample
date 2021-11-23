<?php

use WN\Core\{Route};

Route::set('upfile', 'file/<id>(/<file>)(?<query>)')
    ->filter(['file' => '[-\w%.]+', 'id' => '[\w]+'])
    ->defaults([
        // 'namespace' => 'WN\UpFile',
        'controller' => 'WN\UpFile\Controller\Index',
    ])
    ->top();