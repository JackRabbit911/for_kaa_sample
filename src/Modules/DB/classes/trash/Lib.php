<?php
namespace WN\DB;

class Lib
{
    public static $_plhs = [];

    public static function _get_plh_unique($column)
    {
        $column = strtr($column, ['.'=>'$']);

        if(array_key_exists($column, static::$_plhs))
        {
            $key = static::$_plhs[$column] + 1;
            static::$_plhs[$column] = $key;
            return ':'.$column.(string)$key;
        }
        else
        {
            static::$_plhs[$column] = 0;
            return ':'.$column;
        }
    }

    public static function santize_params($params)
    {
        if(static::is_assoc($params))
        {
            foreach($params AS $k => $v)
            {
                $key = strtr($k, ['.' => '$']);
                $key = ':'.ltrim($key, ':');
                $res[$key] = $v;
            }
            return $res;
        }
        else return $params;
    }

    public static function santize_set(&$value)
    {
        if(!is_string($value)) return false;

        if(strpos($value, '{') === 0 && strpos($value, '}', -1) === strlen($value)-1)
        {
                $value = trim($value, '{}');
                return true;
        }     
    }

    public static function santize_string($str)
    {
        $bkts = function($str)
        {
            if(strpos($str, '{') === 0 && strpos($str, '}', -1) === strlen($str)-1)
                return trim($str, '{}');
            elseif(($pos1 = strpos($str, '(')) !== false && ($pos2 = strpos($str, ')')) !== false)
            {
                $sub = substr($str, ++$pos1, $pos2-$pos1);
                $arr = explode($sub, $str);
                $sub = static::escape($sub);
                return implode($sub, $arr);           
            }
            else return static::escape($str);
        };

        $arr1 = explode(',', $str);
        
        foreach($arr1 AS $substr1)
        {
            $r = [];
            $arr2 = preg_split('/(\sas\s)|(\s)/i', trim($substr1));
            $r[] = $bkts($arr2[0]);
            if(isset($arr2[1])) $r[] = $bkts($arr2[1]);
            $res[] = implode(' AS ', $r);
        }
        return implode(', ', $res);
    }

    public static function escape($str)
    {
        $arr = explode('.', $str);
        
        foreach($arr AS &$item)
            $item = '`'.trim($item, '`').'`';
        return implode('.', $arr);
    }

    public static function _parser($select, $columns)
    {
        $res = [];
        
        foreach($columns AS $column)
        {
            if(is_string($column))
            {
                $res[] = Lib::santize_string($column);
            }
            elseif(is_array($column))
            {
                $key = key($column);
                
                if(is_string($key))
                    $res[] = Lib::santize_string($key).' AS `'.trim(current($column), '`').'`';
                else
                {
                    if($column[0] instanceof Select)
                    {
                        $str = '('.$column[0]->_render().')';
                        if(isset($column[1])) $str .= ' AS `'.trim($column[1], '`').'`';
                        $res[] = $str;
                        $select->params($column[0]->params());
                    }
                    else
                    {
                        $str = Lib::santize_string($column[0]);
                        if(isset($column[1])) $str .= ' AS `'.trim($column[1], '`').'`';
                        $res[] = $str;
                    }
                }
            }
            elseif($column instanceof Select)
            {
                $res[] = '('.$column->_render().')';
                $select->params($column->params());
            }
        }
        return implode(', ', $res); // ltrim($res, ',');
    }

    public static function _parse_order($args)
    {
        foreach($args AS $arg)
            $res[] = static::santize_order($arg);
        
        return  implode(', ', $res);
    }

    public static function santize_order($str)
    {
        $arr = explode(', ', $str);

        foreach($arr AS $substr)
        {
            $arr_substr = explode(' ', $substr);
            $arr_substr[0] = static::santize_string($arr_substr[0]);

            if(isset($arr_substr[1])) $arr_substr[1] = strtoupper($arr_substr[1]);

            $arr1[] = implode(' ', $arr_substr);
        }
        
        return implode(', ', $arr1);
    }

    public static function is_assoc(array $array)
	{
		$keys = array_keys($array);
		return array_keys($keys) !== $keys;
	}
}