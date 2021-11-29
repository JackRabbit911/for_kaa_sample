<?php

use WN\Core\Autoload;

// WN\Core\Core::$errors = false;
WN\Core\Exception\Logger::$is_log = true;

Autoload::add('Modules/DB');
Autoload::add('Modules/User');
// Autoload::add('Modules/UpFile');
// Autoload::add('Modules/Image');
// Autoload::add('Modules/Page');
// Autoload::add('Modules/Dev');
