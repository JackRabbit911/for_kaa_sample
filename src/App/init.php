<?php

use WN\Core\{Autoload, Route, Config, Core, Exception, Helper};

$config = Config::instance();

// Set eviroment var depends of config host/domain settings
Core::enviroment($config->get('host/'.DOMAIN, 'env', Config::BOOT));

// define behavior depending on the environment variable
Core::bootstrap();


Autoload::add('Modules/DB');
Autoload::add('Modules/User');
// Autoload::add('Modules/UpFile');
// Autoload::add('Modules/Image');
// Autoload::add('Modules/Page');


Route::set('user', 'user/<action>')
    ->defaults(['controller' => 'UserForm']);

Route::set('home', '(<action>(/<param1>(/<param2>)))(?<query>)')
    ->defaults(['controller' => 'home']);


//include files init.php from all loaded modules
for($i = 1, $inits = Core::find_file('init', TRUE);  $i < count($inits); $i++)
{
    include_once $inits[$i];
}
