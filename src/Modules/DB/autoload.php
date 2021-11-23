<?php

$autoload = function($class)
{
    $namespace = 'WN\\'.basename(__DIR__);
    $file = str_replace($namespace, __DIR__.'/classes', $class).'.php';
    $file = str_replace(DIRECTORY_SEPARATOR, '/', $file);
    if(is_file($file)) require_once $file;
    else return true;
};

spl_autoload_register($autoload);