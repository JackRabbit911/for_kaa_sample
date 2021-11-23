<?php
namespace WN\DB;

use WN\DB\Lib\{WhereHaving, Render, Parser};

class Insert extends Render
{
    use WhereHaving;

    protected $data = [];
    protected $columns = [];
    protected $values = [];

    public function __construct($db, $table, $data = null)
    {
        $this->db = $db;
        $this->table = Parser::escape($table);
        if($data) $this->set($data);
    }

    public function reset()
    {
        $this->data = [];
        $this->columns = [];
        $this->values = [];
        Parser::$chr = 'a';
        $this->sql = null;
        $this->params = [];
        return $this;
    }

    public function set($data, $value = null)
    {
        if(is_array($data))
        {
            if(Parser::is_assoc($data))
            {
                $this->columns = array_replace($this->columns, array_keys($data));
                $this->values[] = array_replace($this->values, array_values($data));
            }
            elseif(isset($data[0]) && is_array($data[0]))
            {
                foreach($data AS $k => $row)
                {
                    $this->columns = array_replace($this->columns, array_keys($data[$k]));
                    $this->values[$k] = array_replace($this->values, array_values($data[$k]));
                }
            }
            elseif(empty($data)) $this->columns = 'DEFAULT VALUES';
        }
        else
        {
            $i = 0;
            if(!in_array($data, $this->columns))
                $this->columns[] = $data;
            else $i++;

            $this->values[$i][] = $value;
        }

        return $this;
    }

    public function columns()
    {
        $columns = func_get_args();

        if(count($columns) === 1 && is_array($columns[0]))
            $this->columns = array_replace($this->columns, $columns[0]);
        else
            foreach($columns AS $k => $column)
                $this->columns[$k] = $column;

        return $this;
    }

    public function values()
    {
        $values = func_get_args();

        if(count($values) === 1 && is_array($values[0])) $values = $values[0];

        $key_last = array_key_last($this->values);
        if($key_last === null) $key_last = 0;
        else $key_last++;

        foreach($values AS $k => $row)
        {
            if(is_array($row))
                $this->values[$key_last + $k] = $row;
            else
                $this->values[$key_last][] = $row;
        }

        return $this;
    }

    public function expr($select)
    {
        $this->values = $select->render();
        $this->params($select->params(DB::NAMED));
        return $this;
    }

    protected function _render()
    {
        if(!empty($this->columns) && is_array($this->columns))
            $columns = $this->eol.'('.implode(', ', array_map('WN\DB\Lib\Parser::escape', $this->columns)).')';
        elseif(!empty($this->columns) && is_string($this->columns)) $columns = $this->eol.$this->columns;
        else $columns = null;

        static $chr = 'a';

       

        if(!empty($this->values) && is_array($this->values))
        {
            $values = $this->eol.'VALUES ';
            foreach($this->values AS $i => $row)
            {
                $values .= ($i > 0) ? ', (' :'(';
                foreach($row AS $k => $value)
                {
                    $column = $this->columns[$k] ?? $chr++;
                    $plhs[$k]= $this->_placeholder($column, $value);
                }
                $values .= implode(', ', $plhs).')';
            }

            // var_dump($this->columns);
            // var_dump($plhs);
        }
        elseif(is_string($this->values)) $values = $this->values;
        else $values = null;

        return "INSERT INTO $this->table$columns$values";
    }

    public function execute($params = null)
    {
        parent::execute($params);
        $this->reset();
        return $this->db->pdo->lastInsertId();
    }
}