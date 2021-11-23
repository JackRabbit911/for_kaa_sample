<?php

use WN\Core\{Route};

Route::set('media', 'media(/<file>)')
    ->filter(array('file' => '.+'))
    ->defaults(array(
        'controller' => 'WN\Core\Controller\Media',
        'file'       => NULL,
    ))
    ->top();

Route::set('default', '(<controller>(/<action>(/<param1>(/<param2>))))(?<query>)')
    ->defaults([
        'controller' => 'WN\App\Controller\Index',
    ])
    ->filter(function($params){
        if(class_exists($params['controller'])) return true;
        else return false;
    });   

require_once 'lib.php';
