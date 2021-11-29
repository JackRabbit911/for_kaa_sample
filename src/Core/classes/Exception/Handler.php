<?php

namespace WN\Core\Exception;

use WN\Core\{Core, View, I18n};
use WN\Core\Helper\{HTTP, Arr};

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

    // public static $is_log = false;
    public static $strict = false;
    public static $view_wrap = 'errors/wrapper';
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

        $e = new \ErrorException($errstr, $errno, $errno, $errfile, $errline);
        return static::exceptionHandler($e);
    }

    public static function exceptionHandler($e)
    {
        if (!(error_reporting())) {
            static::$shutdown = false;
            return false; // Ошибка проигнорирована
        }

        static::$shutdown = false;
        static::response($e);   
        return true;        
    }

    public static function shutdownHandler($dir)
    {
        $error = error_get_last();
        if($error && static::$shutdown)
        {
            chdir($dir);
            $e = new \ErrorException($error['message'], $error['type'], $error['type'], $error['file'], $error['line']);
            return static::exceptionHandler($e);
        }
        else return false;
    }

    // public static function text($e)
    // {
    //     $v = static::getExceptionVars($e);
    //     $file = Debug::path($v['file']);
    //     return "{$v['class']} [{$v['code']}]; {$v['message']}; in: {$v['file']} [{$v['line']}]";
    // }

    // public static function showError($e)
    // {
    //     $vars = static::getExceptionVars($e);
    //     return View::factory(static::$view_error, $vars)->render();
    // }

    public static function response($e)
    {
        // $e->gmt = date(I18n::l10n('date_time'), time());
        // $e->uri = HTTP::detect_uri();

        // $error_string = static::showError($e);

        $vars = static::getExceptionVars($e);
        // $error_page = View::factory(static::$view_error, $vars)->render();

        if(Logger::$is_log) Logger::add($vars);        

        if(Core::$errors)
        {
            while (ob_get_level()) ob_end_clean();

            ob_start();
            HTTP::status(500);
            error_reporting(0);
            $content = View::factory(static::$view_error, $vars)->render();
            echo View::factory(static::$view_wrap, ['content' => $content])->render();
            ob_get_flush();

            exit(1);
        }
        elseif(in_array($e->getCode(), static::$fatal_errors) || static::$strict === true)
        {
            $http_status = (method_exists($e, 'getHTTPStatus')) ? $e->getHTTPStatus() : 503;
            HTTP::status($http_status);
            static::http_response($http_status, null); //($http_status, $e->getMessage());
        }
        else return false;
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

    public static function getExceptionVars(\Throwable $e)
    {
        $reflect = new \ReflectionClass($e);

        $array['class'] = $reflect->getShortName();
        $array['code'] = static::$php_errors[$e->getCode()] ?? '';
        $array['message'] = $e->getMessage();
        $array['gmt'] = date(I18n::l10n('date_time'), time());
        $array['uri'] = HTTP::detect_uri();
        $array['file'] = $e->getFile();
        $array['line'] = $e->getLine();
        $array['trace'] = $e->getTrace();

        return $array;
    }
}
