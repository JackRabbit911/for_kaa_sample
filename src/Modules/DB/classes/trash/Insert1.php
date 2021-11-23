<?php
namespace WN\DB;

use WN\DB\Lib\{Render, Parser};

class Insert extends Render
{

    protected $columns = [];
    protected $values = [];
    protected $placeholders = [];
    public $params = [];
    protected $expr;

    protected $prepare_mode;

    public function __construct($db, $name, $data = null)
    {
        $this->db = $db;
        $this->name = $name;
        if($data) $this->set($data);
    }

    public function set()
    {
        $data = func_get_args();

        if(func_num_args() === 1 && is_array($data[0]))
            $data = $data[0];
        
        if(Parser::is_assoc($data))
        {
            $this->columns = array_keys($data);
            $this->values = array_values($data);
        }
        else
        {
            $this->columns = array_keys($data[0]);
            $this->values = array_map(function($v){return array_values($v);}, $data);
        }

        if(!$this->prepare_mode) $this->prepare_mode = DB::NAMED;
        return $this;
    }

    public function columns()
    {
        $args = func_get_args();
        if(is_array($args[0])) $columns = $args[0];
        else $columns = $args;

        if(empty($this->columns))
        {
            $this->columns = $columns;
            if(!$this->prepare_mode) $this->prepare_mode = DB::POSITION;
        }
        elseif(ini_get('display_errors') == 1) 
            throw new \Exception('Сolumn names already passed');

        return $this;
    }

    public function values()
    {
        if(empty($this->values))
        {
            $values = func_get_args();
            if(func_num_args() === 1 && is_array($values[0]))
                $this->values = $values[0];
            else $this->values = $values;
        }
        elseif(ini_get('display_errors') == 1) 
            throw new \Exception('Values already passed');

        return $this;
    }

    public function prepare_mode($mode = null)
    {
        if($mode) $this->prepare_mode = $mode;
        return $this;
    }

    public function expr($expr = null)
    {
        if($expr)
        {
            $this->expr = $expr;
            return $this;
        }
        elseif(!$this->expr)
        {
            $this->create_placeholder();

            if($this->placeholders)
            {
                if(is_array($this->placeholders)) $plhs = implode(',', $this->placeholders);
                else $plhs = $this->placeholders;
                $plhs = str_replace(['((', '))'], ['(', ')'], '('.$plhs.')');
                $this->expr = 'VALUES '.$plhs;
            }
        }
    }

    public function render($prepare_mode = null, $strict = false)
    {
        if($prepare_mode) $this->prepare_mode = $prepare_mode;

        if($strict) $this->eol = PHP_EOL."\t";
        else $this->eol = ' ';
        
        if(!empty($this->columns))
        {
            $columns = array_map(function($v){return "`$v`";}, $this->columns);
            $columns = ' ('.implode(',', $columns).')';
        }
        else
        {
            $columns = '';
            if($this->prepare_mode !== DB::NOT_PREPARE) $this->prepare_mode = DB::POSITION;
        }

        $this->expr();

        return "INSERT INTO $this->name$columns$this->eol$this->expr";
    }

    public function params($params = null)
    {
        if($params)
        {
            $this->params = $params;
            return $this;
        }
        elseif(!$this->params)
        {
            if($this->prepare_mode === DB::POSITION)
                $this->params = $this->values;
            elseif($this->prepare_mode === DB::NAMED)
            {   
                if(count($this->placeholders) == count($this->values) && !is_array($this->values[0]))
                    $this->params = array_combine($this->placeholders, $this->values);
                else
                {
                    foreach($this->values as $item)
                    {
                        if(count($this->placeholders) == count($item));
                            $this->params[] = array_combine($this->placeholders, $item);
                    }
                }
            }           
        }
        return $this->params;
    }

    protected function create_placeholder()
    {
        if($this->placeholders) return;

        if($this->params)
        {
            if(Parser::is_assoc($this->params))
                $this->placeholders = array_keys($this->params);
            else $this->placeholders = array_fill(0, count($this->params), '?');
        }

        if($this->placeholders) return;

        if($this->prepare_mode === DB::POSITION)
        {
            if(empty($this->columns))
            {
                if(!empty($this->values))
                {
                    if(is_array($this->values[0]))
                        $arr = $this->values[0];
                    else $arr = $this->values;
                }
                else $arr = [];
            }
            else $arr = $this->columns;

            $this->placeholders = array_fill(0, count($arr), '?');
        }
        elseif($this->prepare_mode === DB::NAMED)
            $this->placeholders = array_map(function($v){return ":$v";}, $this->columns);
        elseif($this->prepare_mode === DB::NOT_PREPARE)
        {
            foreach($this->values as $item)
                if(is_array($item))
                {
                    array_walk($item, function(&$v, $k){if(is_string($v)) $v="'$v'";elseif($v===null) $v = 'NULL';});
                    $this->placeholders[] = '('.implode(',', $item).')';
                } 
            elseif(is_string($item)) $this->placeholders[] = "'$item'";
            elseif($item === null) $this->placeholders[] = 'NULL';
            else $this->placeholders[] = $item;
        }
    }
}