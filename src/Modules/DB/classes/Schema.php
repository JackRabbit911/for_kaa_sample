<?php
namespace WN\DB;

class Schema
{
    public static $tables;

    protected $driver;
    protected $query;
    
    public function __construct($db)
    {
        $this->db = &$db;
    }
    
    public function tables($table = null)
    {
        if(!static::$tables)
        {
            $sql = $this->db->class_driver::tables();
            static::$tables = array_fill_keys($this->db->pdo->query($sql)->fetchAll(\PDO::FETCH_COLUMN), null);
        }

        if(!$table) return array_keys(static::$tables);
        else return (array_key_exists($table, static::$tables)) ? true : false;
    }
    
    public function columns($table, $column = null)
    {
        if(!isset(static::$tables[$table]))
        {
            static::$tables[$table] = $this->db->class_driver::columns($this->db->pdo, $table);
        }
        return ($column) ? array_key_exists($column, static::$tables[$table]) : static::$tables[$table];
    }
    
    public function indexes($table)
    {
        return $this->db->class_driver::indexes($this->db->pdo, $table);
    }
    
    public function primary_key($table)
    {
        
    }
}