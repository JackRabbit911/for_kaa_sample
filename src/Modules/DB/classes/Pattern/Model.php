<?php

namespace WN\DB\Pattern;

use WN\Core\Pattern\ModelEntity;
use WN\DB\DB;
use WN\Core\Helper\{Inflector, Text};

abstract class Model extends ModelEntity
{
    public static $driver;
    public static $db;   
    public static $is_eav;
    public static $table_options;

    public $table_name;
    public $table;

    public static function create_table($options = null)
    {
        if(!$options) $options = static::$table_options;
        
        static::$db->create(static::instance()->table_name)
            ->set($options)->exec();
    }

    public function __construct()
    {
        parent::__construct();

        if(!static::$db)
                static::$db = (static::$driver) ? DB::instance(static::$driver) : DB::instance();

        if(!$this->table_name)
            $this->table_name = strtolower(Inflector::plural(Text::class_basename($this->entity_class)));

        if(static::$is_eav)
            $this->table = static::$db->eav($this->table_name);
        else $this->table = static::$db->table($this->table_name);

        if(DB::$auto_create === true)
        {
            if(!static::$db->schema()->tables($this->table_name))
                static::create_table();

            if(static::$is_eav && !static::$db->schema()->tables($this->table_name.'_props'))
                $this->table->create_table_props();
        }
    }

    public function get()
    {
        return call_user_func_array([$this->table, 'get'], func_get_args());
    }

    public function getAll()
    {
        $this->table->setFetchMode(\PDO::FETCH_CLASS, $this->entity_class);
        return call_user_func_array([$this->table, 'getAll'], func_get_args());
    }

    public function set($data)
    {
        // var_dump($data);

        if(isset($data['id']))
        {
            $id = $data['id'];
            $old_data = $this->get($id);

            // var_dump($data, $old_data);

            $data = array_diff($data, $old_data);
            $data['id'] = $id;
        }

        // print_r($data);

        return $this->table->set($data);
    }
}