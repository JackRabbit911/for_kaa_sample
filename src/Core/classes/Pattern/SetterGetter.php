<?php

namespace WN\Core\Pattern;

use WN\Core\{Core, Config};
use WN\Core\Exception\WnException;

trait SetterGetter
{
    public static function setStaticVars($config_file = null, $name = null)
    {
        if($config_file === NULL)
        {
            $arr_class_name = explode('\\', get_called_class());
            $config_file = strtolower(array_pop($arr_class_name));
        }

        $config = Config::instance()->get($config_file);

        if(is_array($config))
        {
            if($name) $config = [$name => $config];
            
            foreach($config AS $key => $value)
            {
                try
                {
                    if(is_array($value))
                    {
                        static::$$key = array_replace_recursive((array) static::$$key, $value);
                    }
                    else static::$$key = $value;
                }
                catch(\Error $e)
                {
                    if(Core::$errors)
                    {
                        if(!property_exists(get_called_class(), $key))
                            $msg = 'Access to undeclared static property: ":var" from config ":config"';
                        else $msg = 'Access to non-static property: ":var" from config ":config"';

                        throw new WnException($msg, [':var'=>$key, ':config'=>$config_file]);
                    }
                    else continue;
                }                   
            }
        }
        else
        {
            try
            {
               static::$$name = $config;
            }
            catch(\Error $e)
            {
                if(Core::$errors)
                {
                    if(!property_exists(get_called_class(), $key))
                        $msg = 'Access to undeclared static property: ":var"';
                    else $msg = 'Access to non-static property: ":var"';

                    throw new WnException($msg, [':var'=>$name]);
                }
            }            
        }
    }

    public static function setStatic($name, $value, $type = false)
    {
        if(static::_checkType($value, $type))
        {
            if(!property_exists(get_called_class(), $name))
                if(Core::$errors) throw new WnException(
                        'Access to undeclared static property: ":var"',
                        [':var' => get_called_class().'::$'.$name]);
                else return;

            static::$$name = $value;
        }
    }

    public static function getStatic($name)
    {
        return static::$$name;
    }

    public function set($name, $value, $type = false, $if_exists = false)
    {
        if(static::_checkType($value, $type))
        {
            if($if_exists && !property_exists($this, $name))
                if(Core::$errors) throw new WnException(
                        'Access to undeclared property: ":var"',
                        [':var' =>\get_class($this).'->'.$name]);
                else return;

            $this->$name = $value;
        }
    }

    public function get($name)
    {
        return $this->$name;
    }

    protected static function _checkType($value, $type = false)
    {
        if($type === false) return true;

        function check_instanceof($object, array $allowed_types)
        {
            foreach($allowed_types AS $type)
            {
                if($object instanceof $type) return true;
            }
            return false;
        }

        $type_f = gettype($value);
        if(($type_f == 'object' && ! check_instanceof($value, (array) $type) || !in_array($type_f, (array) $type)))
        {
            if(Core::$errors) throw new WnException(
                'Type not match. Expected: ":type", received ":tf"',
                [':type' => $type, ':tf' => $type_f]);
            else return false;
        }
        else return true;
    }
}