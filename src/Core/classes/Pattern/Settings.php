<?php

namespace WN\Core\Pattern;

use WN\Core\{Core, Config};
use WN\Core\Helper\Text;

trait Settings
{
    // public static $settings = null;
    public static $is_once = true;

    public static function settings($settings = null)
    {
        // var_dump(get_called_class());

        static $is_set = false;

        // if(static::$settings === false || ($is_set && static::$is_once)) return;

        if($is_set && static::$is_once) return;

        if(!$settings)
        {
            // if(static::$settings) $settings = static::$settings;
            // else 
                $settings = strtolower(Text::class_basename(get_called_class()));
        }

        // var_dump($settings);

        if(is_string($settings))
            $settings = Config::instance()->get($settings, null, Config::SETTINGS);

            // var_dump($settings); echo '<br>';
           
        if(is_array($settings) && !empty($settings))
        {
            foreach($settings as $name => $value)
            {
                if(is_array($value) && is_array(static::$$name))
                    static::$$name = array_replace_recursive(static::$$name, $value);
                else static::$$name = $value;
            }
        }

        $is_set = true; 
    }

    // public static function wn_class_basename($class = null)
    // {
    //     if($class === null) $class = get_called_class();
    //     elseif(is_object($class)) $class = get_class($class);
    
    //     return basename(str_replace('\\', '/', $class));
    // }
}