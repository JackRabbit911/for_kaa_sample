<?php

use WN\Core\{Route, Core};

Route::set('plh', '~plh(/<size>(/<bgcolor>(/<color>)))(?<query>)')
    ->filter(['size' => '[\d]{1,4}x{1}[\d]{1,4}|[\d]{1,4}x{1}|x{1}[\d]{1,4}',])
    ->defaults([
        // 'namespace' => 'WN\Image',
        'controller'=> 'WN\Image\Controller\Index',
        'action'    => 'plh',
    ])
    ->top();

// Route::set('original', 'img/original/<id>(/<file>)')
//     ->filter(['file' => '[-\w%.]+', 'id' => '[\w]+'])
//     ->defaults([
//         'namespace' => 'WN\Image',
//         'action'    => 'original',
//     ])
//     ->top();

// Route::set('square', 'img/square/<id>(/<file>(/<size>))')
//     ->filter(['file' => '[-\w%.]+', 'id' => '[\w]+',
//             'size' => '[\d]{1,4}',])
//     ->defaults([
//         'namespace' => 'WN\Image',
//         'action'    => 'square',
//     ])
//     ->top();

// Route::set('image', 'img/<action>/<id>(/<file>(/<size>))')
//     ->filter([
//             'action' => 'image|thumb|avatar|crop',
//             'file' => '[-\w%.]+', 'id' => '[\w]+',
//             'size' => '[\d]{1,4}x{1}[\d]{1,4}|[\d]{1,4}x{1}|x{1}[\d]{1,4}',])
//     ->defaults([
//         'namespace' => 'WN\Image',
//     ])
//     ->top();

Route::set('image', 'img/<id>(/<action>(/<size>)(/<file>))')
    ->filter([
        'action' => 'original|image|square|thumb|avatar|crop',
        'file' => '[-\w%.]+', 'id' => '[\d]+',
        'size' => '[\d]{1,4}x{1}[\d]{1,4}|[\d]{1,4}x{1}|x{1}[\d]{1,4}|[\d]{1,4}',
    ])
    ->defaults([
        // 'namespace' => 'WN\Image',
        'controller'=> 'WN\Image\Controller\Index',
        'action'    => 'original',
    ])
    ->top();

