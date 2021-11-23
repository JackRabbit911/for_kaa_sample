<?php
namespace WN\Core\Pattern;

trait Singletone
{
    use Settings;

    public static $is_settings = true;
    /** 
     * Singletone Object of real class 
     * @var  object
     */
    protected static $instance;
    
    /**
     * Singletone pattern
     * @return object this class
     */
    public static function instance($settings = null)
    {        
        if(!(static::$instance instanceof static))
        {
            if(static::$is_settings !== false)
            {
                // if($settings)
                //     static::$settings = array_replace_recursive(static::$settings, $settings);
                    
                static::settings($settings);
            }
            static::$instance = new static();
        }
        return static::$instance;
    }
}