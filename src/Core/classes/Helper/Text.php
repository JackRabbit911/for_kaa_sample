<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace WN\Core\Helper;

/**
 * Description of Text
 *
 * @author JackRabbit
 */

use WN\Core\Core;

class Text
{
    public static $markdown = NULL;
       
    public static function markdown($string, $tag = FALSE)
    {
        if(!class_exists('\Parsedown'))
        {
        //     include_once str_replace('/', DIRECTORY_SEPARATOR, SYSPATH.'vendor/Parsedown/Parsedown.php');
            include_once SYSPATH.'vendor/Parsedown/Parsedown.php';
        }
        
        if(static::$markdown === NULL)
        {
            static::$markdown = new \Parsedown();
        }
        
        // if($dir === FALSE)
        //     $result = self::$markdown->text($string);
        // else
        // {
        //     if($file = Core::find_file($string, $dir, 'md'))
        //     {
        //         $result = self::$markdown->text(file_get_contents($file));
        //     }
        //     else return NULL;
        // }

        $result = self::$markdown->text($string);

        unset($string);
        
        if($tag !== FALSE)
        {
            $result = static::htmlspecialchars($result, $tag);
        }
        
        return $result;
    }
    
    /**
     * Generates a random string of a given type and length.
     *
     *
     *     $str = Text::random(); // 8 character random string
     *
     * The following types are supported:
     *
     * alnum
     * :  Upper and lower case a-z, 0-9 (default)
     *
     * alpha
     * :  Upper and lower case a-z
     *
     * hexdec
     * :  Hexadecimal characters a-f, 0-9
     *
     * distinct
     * :  Uppercase characters and numbers that cannot be confused
     *
     * You can also create a custom type by providing the "pool" of characters
     * as the type.
     *
     * @param   string  $type   a type of pool, or a string of characters to use as the pool
     * @param   integer $length length of string to return
     * @return  string
     * @uses    UTF8::split
     */
    public static function random($type = NULL, $length = 8)
    {
            if ($type === NULL)
            {
                    // Default is to generate an alphanumeric string
                    $type = 'alnum';
            }

            $utf8 = FALSE;

            switch ($type)
            {
                    case 'alnum':
                            $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    break;
                    case 'alpha':
                            $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    break;
                    case 'hexdec':
                            $pool = '0123456789abcdef';
                    break;
                    case 'numeric':
                            $pool = '0123456789';
                    break;
                    case 'nozero':
                            $pool = '123456789';
                    break;
                    case 'distinct':
                            $pool = '2345679ACDEFHJKLMNPRSTUVWXYZ';
                    break;
                    default:
                            $pool = (string) $type;
                            $utf8 = ! UTF8::is_ascii($pool);
                    break;
            }

            // Split the pool into an array of characters
            $pool = ($utf8 === TRUE) ? UTF8::str_split($pool, 1) : str_split($pool, 1);

            // Largest pool key
            $max = count($pool) - 1;

            $str = '';
            for ($i = 0; $i < $length; $i++)
            {
                    // Select a random character from the pool and add it to the string
                    $str .= $pool[random_int(0, $max)];
            }

            // Make sure alnum strings contain at least one letter and one digit
            if ($type === 'alnum' AND $length > 1)
            {
                    if (ctype_alpha($str))
                    {
                            // Add a random digit
                            $str[random_int(0, $length - 1)] = chr(random_int(48, 57));
                    }
                    elseif (ctype_digit($str))
                    {
                            // Add a random letter
                            $str[random_int(0, $length - 1)] = chr(random_int(65, 90));
                    }
            }

            return $str;
    }
   
    
    public static function htmlspecialchars($str, $tag)
    {
        $regex = '|(<'.$tag.'>)(.*)(</'.$tag.'>)|isU';
        
        return preg_replace_callback($regex, function($m){
            $m[2] = htmlspecialchars($m[2]);
            return $m[1].$m[2].$m[3];
        }, $str);
    }

    public static function is_json($string)
    {
        if(!is_string($string)) return false;

        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public static function json_decode($string)
    {
        if(!is_string($string)) return $string;

        $object = json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE) ? $object : $string;
    }

    public static function is_serialized($str)
    {
        if(!is_string($str)) return false;

        $data = @unserialize($str);
        if ($str === 'b:0;' || $data !== false)
            return true;
        else return false;
    }

    public static function unserialize($str)
    {
        if(!is_string($str)) return $str;

        $data = @unserialize($str);
        if ($str === 'b:0;' || $data !== false)
            return $data;
        else return $str;
    }

    public static function class_basename($class)
    {
        // if($class === null) $class = get_called_class();
        if(is_object($class)) $class = get_class($class);

        // $class = str_replace('\\', '/', $class);
    
        return str_replace('/', '\\', basename(str_replace('\\', '/', $class)));
    }

    public static function class_namespace($class)
    {
        // if($class === null) $class = get_called_class();
        if(is_object($class)) $class = get_class($class);

        // $class = str_replace('\\', '/', $class);
    
        return str_replace('/', '\\', dirname(str_replace('\\', '/', $class)));
    }
}
