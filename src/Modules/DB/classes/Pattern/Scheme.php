<?php

namespace WN\DB\Pattern;

use WN\DB\DB;

class Scheme
{
    protected static $instance = [];

    public $db;

    public static function instance($table, $settings = null)
    {
        if(!isset(static::$instance[$table]) || !(static::$instance[$table] instanceof static))
        {
            static::$instance[$table] = new static($table, $settings);
        }
        return static::$instance[$table];
    }

    protected function __construct($table, $settings = null)
    {
        if($settings instanceof DB) $this->db = $settings;
        elseif(is_string($settings) || $settings === null)
        {
            $connect = DB::connect($settings);
            $this->db = DB::instance($connect);
        }
    }
}