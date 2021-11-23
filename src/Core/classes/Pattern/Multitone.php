<?php
namespace WN\Core\Pattern;

trait Multitone
{
    // use Settings, Options;
    /** 
     * array of Singletones Object of real class 
     * @var  object
     */
    protected static $instance = [];
    
    /**
     * Multitone pattern
     * @return object this class
     */
    public static function instance($key, $options = [])
    {     
        if(!isset(static::$instance[$key]) || !(static::$instance[$key] instanceof static))
        {
            static::$instance[$key] = new static($key, $options);
        }
        return static::$instance[$key];
    }
}
