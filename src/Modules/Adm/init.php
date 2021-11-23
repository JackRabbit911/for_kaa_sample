<?php

use WN\Core\{Route, I18n, Exception\Handler};
use WN\User\User;

if(SUBDOMAIN === 'adm')
Route::set('adm', '(<lang>)(/)(<controller>(/<action>(/<foo>)))(?<query>)', ['lang' => I18n::langs()])
->defaults([
    'controller' => 'WN\Adm\Controller\Index',
])
->filter(function(){
    if(User::auth()->role('admin') < ROLE_MODERATOR)
    {
        Handler::http_response(404);
        return false;
    }
    else 
        return true;
});

I18n::$redirect = 0;
I18n::$use_subdomain = false;

define('ADMPATH', str_replace(DIRECTORY_SEPARATOR, '/', __DIR__).'/');

// Route::set('adm', 'adm(/<controller>(/<action>(/<param>)))')
// ->defaults([
//     'namespace' => 'WN\Adm',
// ]);