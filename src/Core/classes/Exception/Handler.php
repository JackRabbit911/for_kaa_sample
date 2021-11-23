<?php

namespace WN\Core\Exception;

use WN\Core\Core;
use WN\Core\View;
use WN\Core\Helper\HTTP;
use WN\Core\Helper\Arr;

class Handler
{
    public static $php_errors = array(
		E_ERROR              => 'Fatal Error!',
		E_USER_ERROR         => 'User Error',
		E_PARSE              => 'Parse Error',
		E_WARNING            => 'Warning',
		E_USER_WARNING       => 'User Warning',
		E_STRICT             => 'Strict',
		E_NOTICE             => 'Notice',
		E_RECOVERABLE_ERROR  => 'Recoverable Error',
		E_DEPRECATED         => 'Deprecated',
    );

    public static $strict = false;
    public static $view_error = 'errors/error';
    public static $view_http_error = 'errors/http';

    protected static $fatal_errors = [0, E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    protected static $shutdown = true;

    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if (!(error_reporting() & $errno)) {
            static::$shutdown = false;
            return false; // Ошибка проигнорирована
        }
        // var_dump(static::$strict); echo error_reporting(); exit;
        $e = new \ErrorException($errstr, $errno, $errno, $errfile, $errline);
        return static::exceptionHandler($e, __FUNCTION__);
    }

    public static function exceptionHandler($e, $f = __FUNCTION__)
    {
        // if (!(error_reporting())) {
        //     static::$shutdown = false;
        //     return false; // Ошибка проигнорирована
        // }

        static::$shutdown = false;
        static::response($e, $f);   
        return true;        
    }

    public static function shutdownHandler($dir)
    {
        // while (ob_get_level()) ob_end_clean();
        // die('qq');
        // if (!(error_reporting())) {
            // return false; // Ошибка проигнорирована
        // }

        // if(static::$shutdown === false)
        //     return true;
        // echo 'fatal';
        // exit;
        // return true;
        // die('qq');
        // chdir($dir);
        // die('qq');
        // echo 'qq';

        $error = error_get_last();
        if($error && static::$shutdown)
        {
            chdir($dir);
            // $error = error_get_last();
            // var_dump($error); exit;
            $e = new \ErrorException($error['message'], $error['type'], $error['type'], $error['file'], $error['line']);
            return static::exceptionHandler($e, 'shutdownHandler');
        }
        else return false;
    }

    public static function text($e)
    {
        list($class, $code, $message, $file, $line) = static::getExceptionVars($e);
        $file = Debug::path($file);
        return "$class [$code]; $message; in: $file [$line]";
    }

    public static function showError($e, $func = null)
    {
        View::$path = 'views/';
        // ob_start();
        list($class, $code, $message, $file, $line, $trace) = static::getExceptionVars($e);
        return View::factory(static::$view_error, get_defined_vars())->render();
        // ob_end_clean();
    }

    public static function response($e, $f = null)
    {
        // View::$path = 'views/';
        // if(error_reporting() === 0) return;

        // Logger::add($e);        

        // $http_status = (method_exists($e, 'getHTTPStatus')) ? $e->getHTTPStatus() : 500;
        // HTTP::status($http_status);

        // var_dump($e, $http_status); exit;

        if(Core::$errors)
        {
            // var_dump(ob_get_level());
            while (ob_get_level()) ob_end_clean();

            // ob_clean();

            // echo 'qq';

            ob_start();
            HTTP::status(500);
            error_reporting(0);
            echo static::showError($e, $f);
            ob_get_flush();

            exit(1);
        }
        elseif(in_array($e->getCode(), static::$fatal_errors) || static::$strict === true)
        {
            $http_status = (method_exists($e, 'getHTTPStatus')) ? $e->getHTTPStatus() : 503;
            HTTP::status($http_status);
            static::http_response($http_status, null); //($http_status, $e->getMessage());
        }
        else
        {
            return false;
            // echo $e->getCode();
        }
    }

    public static function http_response($status, $msg = null)
    {
        if($status === 0) return;

        HTTP::status($status);

        $message = [
            '401' => 'Unauthorized',
            '403' => 'Forbidden',
            '404' => 'Page not found',
            '500' => 'Internal Server Error',
            '503' => 'Service Unavailable',
        ];

        if(!$msg) $msg = Arr::get($message, (string) $status, $message['500']);
        
        while (ob_get_level()) ob_end_clean();
        echo View::factory(static::$view_http_error, ['code'=>$status, 'message'=>$msg]);
        exit(1); 
    }

    protected static function getExceptionVars(\Throwable $e)
    {
        $reflect = new \ReflectionClass($e);
        $array[] = $reflect->getShortName();
        $array[] = static::$php_errors[$e->getCode()] ?? '';
        $array[] = $e->getMessage();
        $array[]   = $e->getFile();
        $array[]    = $e->getLine();
        $array[]   = $e->getTrace();

        // var_dump($e->getTrace()); exit;

        return $array;
    }
}