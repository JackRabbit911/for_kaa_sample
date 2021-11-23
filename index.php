<?php

// echo $domain; exit;

ob_start();

$src = 'src';
$sys = $src.'/Core';
$app = $src.'/App';
$public = 'public';

// if(is_file('cachepaths.php')) require_once 'cachepaths.php';
// elseif(is_file('subdomain.php')) require_once 'subdomain.php';
// else
// {
    // $docroot = $_SERVER['DOCUMENT_ROOT'];
    $srcpath = str_replace(DIRECTORY_SEPARATOR, '/', __DIR__).'/'.$src;
    $syspath = str_replace(DIRECTORY_SEPARATOR, '/', __DIR__).'/'.$sys;
    $apppath = str_replace(DIRECTORY_SEPARATOR, '/', __DIR__).'/'.$app;
    // $subdomain = null;
    if(!isset($domain)) $domain = $_SERVER['SERVER_NAME'];
    
    $subdomain = rtrim(substr($_SERVER['SERVER_NAME'], 0, strpos($_SERVER['SERVER_NAME'], $domain)), '.');
    if(!$subdomain) $subdomain = null;
    // var_dump($subdomain, $domain);
    // exit;

    $public = 'public';
// }

$autoload_filename = "$syspath/classes/Autoload.php";

if(!is_file($autoload_filename))
    die("File: $autoload_filename not found!");

require_once $autoload_filename;

WN\Core\Autoload::add($apppath);
WN\Core\Autoload::add($syspath);

spl_autoload_register('WN\Core\Autoload::loadClass');

define('SRCPATH', $srcpath.'/');
define('SYSPATH', $syspath.'/');
define('APPPATH', $apppath.'/');
define('SUBDOMAIN', $subdomain);
define('DOMAIN', $domain);
define('PUBDIR', $public);
define('DOCROOT', str_replace(DIRECTORY_SEPARATOR, '/',__DIR__).'/');

// $constants = [
//     'SYSPATH'   => $syspath.'/',
//     'APPPATH'   => $apppath.'/',
//     'SUBDOMAIN' => $subdomain,
//     'DOMAIN'    => $domain,
//     'PUBDIR'    => $public,
// ];

// WN\Core\Core::define_constants($constants);

// var_dump($_SERVER); 

// echo ord('_'), ' ', chr(95);

// exit;



foreach(get_defined_vars() AS $k => $v)
{
    if(strpos((string) $k, '_') === 0 || strpos($k, 'GLOBALS') === 0) continue;
    else unset($$k);
}
unset($k, $v);

// var_dump($_SERVER); exit;

// die(SYSPATH);



WN\Core\Core::instance()->execute();

// var_dump(ob_get_level());

// 1/0;

// echo ob_get_clean();