<?php

use WN\Core\Route;

Route::set('media', 'media(/<file>)')
    ->filter(array('file' => '.+'))
    ->defaults(array(
        'controller' => 'WN\Core\Controller\Media',
        'file'       => NULL,
    ))
    ->top();

Route::set('default_aka_codeigniter', '(<controller>(/<action>(/<any>)))(?<query>)')
    ->skip_controller();

Route::set('default', '(<any>)(?<query>)');

require_once 'lib.php';
