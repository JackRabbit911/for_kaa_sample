<?php
namespace WN\DB\Lib;

use WN\DB\{Expression, Select, DB, Union};

class Parser
{
    public static $_plhs = [];
    public static $point_delimeter = '_POINT_';

    public static $chr = 'a';

    public static function parser($select, array $columns)
    {
        $res = [];
        
        foreach($columns AS $column)
        {
            if($column instanceof Expression)
            {
                $res[] = $column->compile($select);
            }
            elseif($column instanceof Select || $column instanceof Union)
            {
                $res[] = '('.$column->_render().')';
                $select->params($column->params(DB::NAMED));
            }
            elseif(is_array($column))
            {
                $key = key($column);
                
                if(is_string($key))
                    $res[] = static::santize_string($key).' AS '.static::escape($key);
                else
                {
                    if($column[0] instanceof Select || $column[0] instanceof Union)
                    {
                        $str = '('.$column[0]->_render().')';
                        if(isset($column[1])) $str .= ' AS '.static::escape($column[1]);
                        $res[] = $str;
                        $select->params($column[0]->params(DB::NAMED));
                    }
                    else
                    {
                        $str = static::santize_string($column[0]);
                        if(isset($column[1])) $str .= ' AS '.static::escape($column[1]);
                        $res[] = $str;
                    }
                }
            }
            else
            {
                $res[] = static::santize_string($column);
            }
        }
        return implode(', ', $res);
    }

    public static function escape($str)
    {
        if($str instanceof Expression)
            return $str->compile();

        $escape = function($str)
        {
            $arr = explode('.', $str);
        
            foreach($arr AS &$item)
                $item = '`'.trim($item, '` ').'`';
            return implode('.', $arr);
        };

        $array = explode(',', $str);

        return implode(', ', array_map($escape, $array));
    }

    public static function brackets($str)
    {
        if(($pos1 = strpos($str, '(')) !== false && ($pos2 = strpos($str, ')')) !== false)
        {
            $sub = substr($str, ++$pos1, $pos2-$pos1);
            if(!empty($sub))
            {
                $arr = explode($sub, $str);
                $sub = static::escape($sub);
                return implode($sub, array_map('strtoupper', $arr));
            }
            else return strtoupper($str);
        }
        else return static::escape($str);
    }

    public static function santize_string($str)
    {
        if($str instanceof Expression) return $str->compile();

        $array = preg_split('/(\ssa\s)|(\s)/i', strrev($str), 2);
        $array = array_reverse(array_map('strrev', $array));
        return implode(' AS ', array_map([__CLASS__, 'brackets'], $array));
        // return $array;
    }

    public static function get_plh()
    {
        return ':'.static::$chr++;
    }

    // public static function _get_plh_unique($column)
    // {
    //     $column = strtr($column, ['.'=> static::$point_delimeter]);

    //     if(array_key_exists($column, static::$_plhs))
    //     {
    //         $key = static::$_plhs[$column] + 1;
    //         static::$_plhs[$column] = $key;
    //         return ':'.$column.(string)$key;
    //     }
    //     else
    //     {
    //         static::$_plhs[$column] = 0;
    //         return ':'.$column;
    //     }
    // }

    // public static function santize_params($params)
    // {
    //     if(static::is_assoc($params))
    //     {
    //         foreach($params AS $k => $v)
    //         {
    //             $key = strtr($k, ['.' => static::$point_delimeter]);
    //             $key = ':'.ltrim($key, ':');
    //             $res[$key] = $v;
    //         }
    //         return $res;
    //     }
    //     else return $params;
    // }

    public static function is_assoc(array $array)
	{
		$keys = array_keys($array);
		return array_keys($keys) !== $keys;
	}
}