<?php
namespace WN\DB;

use WN\Core\Exception\WnException;
use WN\DB\Lib\{Where, Render, Parser};

class Upsert1 extends Render
{
    use Where;

    protected $table;
    protected $keys = [];
    protected $data = [];

    public function __construct($db, $table)
    {
        $this->db = $db;
        $this->table = $table;
    }

    public function set($column, $value = null)
    {
        if(is_array($column)) $this->data = array_merge($this->data, $column);
        elseif(is_string($column)) $this->data[$column] = $value;

        return $this;
    }

    public function keys()
    {
        $args = func_get_args();

        if(count($args) === 1 && is_array($args[0]))
            $keys = $args[0];
        else $keys = $args;

        $this->keys = array_merge($this->keys, $keys);
        return $this;
    }

    protected function _render()
    {
        $columns = $plhs = $set = [];
        
        foreach($this->data AS $key => $value)
        {
            if(!$value instanceof Expression)
            {
                $plh = $this->placeholder($key, '=', $value);
                $plhs[] = $plh;
            }
        }       

        foreach($this->data AS $key => $value)
        {
            $escaped_column = Parser::escape($key);            
            $set[] = $escaped_column.' = '.$this->placeholder($key, '=', $value);
            
            if(!$value instanceof Expression)
            {
                $columns[] = $escaped_column; 
            }
        }

        $columns = implode(', ', $columns);
        $plhs = implode(', ', $plhs);
       
        $set = implode(',', $set);

        $driver = __NAMESPACE__.'\Lib\\'.ucfirst($this->db->driver);

        if($this->db->driver === 'sqlite')
        {
            if(empty($this->keys)) $this->keys = $driver::conflict($this->db->pdo, $this->table);

            $conflict = ($this->keys) ? implode(', ', $this->keys) : false;

            return $driver::upsert($this->eol, $this->table, $columns, $plhs, $set, $conflict);
        }
        elseif($this->db->driver === 'mysql')
        {
            return $driver::upsert($this->eol, $this->table, $columns, $plhs, $set);
        }
    }

    public function execute($params = null)
    {
        // try
        // {
            $sth = parent::execute($params);
            $id = $this->db->pdo->lastInsertId();

            if($id == 0) $id = $this->data['id'] ?? null;
            return $id;
        // }
        // catch(\PDOException $e)
        // {
        //     return false;
        // }
    }
}