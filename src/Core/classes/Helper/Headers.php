<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace WN\Core\Helper;

/**
 * Description of Header
 *
 * @author JackRabbit
 */
class Headers
{
    public static function get($header = NULL)
    {
//        return $key;
        
        $result = [];
        
        foreach(headers_list() AS $header_str)
        {
            $arr = explode(':', $header_str, 2);
            
            $key = Arr::get($arr, 0);
            
            $value = trim(Arr::get($arr, 1));
            
            $result[$key][] = $value;
            
        }
        
        if($header === NULL) return $result;
        else
        {
//            return $key;
            
            $header = rtrim(ucwords($header, '-'), '-');
            
//            return $key;
            
            $arr = Arr::get($result, $header);
            
//            return $key;

            if(is_array($arr) && count($arr) === 1) return $arr[0];
            else return $arr;
        }
        
//        return (count($result) === 1) ? $result[0] : $result;
    }
    
    public static function set($key, $value)
    {
        $header = rtrim(ucwords($key, '-'), '-').': '.$value;       
        header($header);
    }
    
    public static function is_sent($key, $value = NULL)
    {
        $header = Headers::get($key);
        
        $result = FALSE;
        
        if($value === NULL)
        {
            if($header) $result = TRUE;
        }
        else
        {
            
            if(is_array($header))
            {
                foreach($header AS $item)
                {
                    if(stripos($item, $value) !== FALSE) $result = TRUE;
                }
            }
            else if(stripos($header, $value) !== FALSE) $result = TRUE;
        }
        return $result;
    }
}
