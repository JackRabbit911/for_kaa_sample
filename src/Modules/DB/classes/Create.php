<?php
namespace WN\DB;

use WN\Core\Exception\WnException;
use WN\DB\Lib\Parser;

class Create
{
    protected $driver;
    protected $columns;
    public $name;
    public $mode;
    protected $constraints;
    public $like;
    protected $suffix;
    protected $create_index;
    protected $index = true;
    // protected $engine;
    // protected $collate;
    protected $sql;
    public $eol;

    public function __construct($db, $name, $mode = 'TABLE')
    {
        $this->db = $db;
        $this->driver = __NAMESPACE__.'\Lib\\'.ucfirst($this->db->driver);
        $this->mode = $mode;

        if(is_string($name))
            $this->name = $name;
        elseif(is_array($name))
        {
            $this->name = $name['name'];
            unset($name['name']);
            $this->set($name);
        }
    }

    public function like($table_name)
    {
        $this->like = $table_name;
        return $this;
    }

    public function column($column)
    {
        if(is_array($column))
            foreach($column AS $item)
                $this->column($item);
        else $this->columns[] = $this->driver::column($column);

        return $this;
    }

    public function index($index)
    {
        if(is_array($index))
            foreach($index AS $item)
                $this->index($item);
        elseif($index === false) $this->index = false;
        else
        {
            $index = preg_replace_callback('#\(.+?\)#', function($matches){
                return '('.Parser::escape(trim($matches[0],'()')).')';
            }, $index);
            if(strpos($index, 'index') === 0 && $this->driver ==  __NAMESPACE__.'\Lib\Sqlite')
                $this->create_index[] = $this->driver::create_index($this->name, $index);
            else
                $this->constraints[] = $this->driver::constraint($index);
        }

        return $this;
    }

    public function collate($collate)
    {
        $suffix = $this->driver::collate($collate);
        if($suffix) $this->suffix[] = $suffix;
        return $this;
    }

    public function charset($charset)
    {
        $suffix = $this->driver::charset($charset);
        if($suffix) $this->suffix[] = $suffix;
        return $this;
    }

    public function engine($engine)
    {
        $suffix = $this->driver::engine($engine);
        if($suffix) $this->suffix[] = $suffix;
        return $this;
    }

    public function collate_charset_engine($array)
    {
        list($collate, $charset, $engine) = $array;
        $this->collate($collate);
        $this->charset($charset);
        $this->engine($engine);
        return $this;
    }

    public function set($options)
    {
        $this->column($options['columns']);
        if(isset($options['index']))
            $this->index($options['index']);
        if(isset($options['collate']))
            $this->collate_charset_engine($options['collate']);

        return $this;
    }

    public function render($strict = false)
    {
        if($this->sql) return $this->sql;

        if($strict) $this->eol = PHP_EOL."\t";
        else $this->eol = ' ';

        $this->name = Parser::escape($this->name);

        if($this->like)
        {
            $this->sql = $this->driver::like($this);
            return $this->sql;
        }

        if(is_array($this->columns))
            foreach($this->columns AS &$column)
            {
                $this->columns_unique[] = $column[0];
                $column = implode(' ', $column);
            }

        if($this->columns)
            $columns = implode(",$this->eol", $this->columns);
        else $columns = null;

        if($this->constraints)
        {
            $constraint = ','.$this->eol.implode(','.$this->eol, $this->constraints);
            // $constraint = str_replace('ON DELETE', $this->eol."\t".'ON DELETE', $constraint);
        }
        else $constraint = '';

        // var_dump($columns);

        if(stripos($constraint, 'UNIQUE') === false 
            && (stripos($columns, 'PRIMARY') === false && stripos($columns, 'UNIQUE') === false)
            && !empty($this->columns_unique) && $this->index === true)
        {
            $constraint .= ', UNIQUE ('.implode(', ', $this->columns_unique).')';
        }

        if($columns) $columns = '('.$columns.$constraint.')';

        if($this->suffix)
            $suffix = $this->eol.implode($this->eol, $this->suffix);
        else $suffix = null;

        if($this->create_index)
            $create_index = ';'.PHP_EOL.implode(';'.PHP_EOL, $this->create_index);
        else $create_index = null;

        $this->sql = "CREATE $this->mode IF NOT EXISTS $this->name$this->eol$columns$suffix$create_index";

        return $this->sql;
    }

    public function exec()
    {
        if($this->sql) $sql = $this->sql;
        else  $sql = $this->render();

        Schema::$tables[$this->name] = null;
  
        return $this->db->pdo->exec($sql);
    }

    public static function replace_outside_chars($str, $replace, $left, $right = null)
    {
        if(!$right) $right = $left;
        $res = '';
        $len = strlen($str);

        while($len > 0)
        {           
            $pos = strpos($str, $left);
            if($pos !== false)
            {
                $b = substr($str, 0, $pos);
                $str = substr($str, $pos+1);
                $res .= strtr($b, $replace);
                $pos = strpos($str, $right);
                $res .= $left.substr($str, 0, $pos+1);
                $str = substr($str, $pos+1);
                $len = strlen($str);
            }
            else 
            {
                $res .= strtr($str, $replace);
                $len = 0;
            }
        }

        return $res;
    }
}