<?php
namespace WN\DB;

use WN\DB\Lib\{Where, Having, OrderLimit, Render, Parser};

class Select extends Render
{
    use Where, Having, OrderLimit;

    // public static $union_obj = [];

    // public $union;

    // protected $union_order_by;
    // protected $union_limit;

    protected $_sql;
    protected $from = [];
    public $columns = [];
    protected $values = [];
    protected $placeholders = [];
    protected $join = [];
    protected $distinct;

    protected $render_func = '_render_select';

    public function __construct()
    {
        $this->db = func_get_arg(0);
        $args = func_get_arg(1);
        if(count($args) === 0) $this->columns[] = '*';
        else $this->columns = $args;

        $this->driver = $this->db->driver;
    }

    public function select()
    {
        $args = func_get_args();
        if(count($args) === 0) $this->columns[] = '*';
        else $this->columns = array_merge($this->columns, $args);
        return $this;
    }

    public function distinct()
    {
        $this->distinct = 'DISTINCT ';
        return $this;
    }

    public function from()
    {
        $args = func_get_args();
        $this->from = array_merge($this->from, $args);
        return $this;
    }

    public function join($table, $type = null)
    {
        if(is_string($table)) $table_name = Parser::santize_string($table);
        elseif(is_array($table))
        {
            if(Lib::is_assoc($table))
            {
                $table_name = Parser::santize_string(key($table)).' AS '.Lib::santize_string($table(key($table)));
            }
            else
            {
                if(isset($table[1]))
                    $table_name = Parser::santize_string($table[0]).' AS '.Lib::santize_string($table[1]);
                else $table_name = Parser::santize_string($table[0]);
            }
        }

        if($type) $type = strtoupper($type).' ';
        $this->join[] = $type.'JOIN '.$table_name;

        return $this;
    }

    public function on($col1, $compare, $col2 = null)
    {
        if(!$col2)
        {
            $col2 = $compare;
            $compare = '=';
        }

        $k = array_key_last($this->join);

        if(stripos($this->join[$k], 'ON') !== false)
            $prefix = 'AND ';
        else $prefix = 'ON ';

        if(is_array($col2))
        {
            // $str = '(';

            // foreach($col2 AS $item)
            // {
            //     $str .= Parser::escape($item).', ';
            // }

            // $str = rtrim($str, ', ').')';

            $col2 = '('.implode(', ', $col2).')';
        }
        else $col2 = Parser::escape($col2);

        $this->join[$k] .= $this->eol.$prefix.Parser::escape($col1).' '.strtoupper($compare).' '.$col2; //Parser::escape($col2);

        return $this;
    }

    public function using($column)
    {
        $k = array_key_last($this->join);

        $this->join[$k] .= $this->eol.'USING('.Parser::escape($column).')';
        return $this;
    }

    public function group_by()
    {
        $args = func_get_args();
        $this->group_by = $args;
        return $this;
    }

    // public function union($all = null)
    // {
    //     if($all) $all = ' '.strtoupper($all);
    //     static::$union_obj[] = $this;
    //     $this->union = PHP_EOL.'UNION'.$all.PHP_EOL;
    //     return $this->db;
    // }

    // public function union_order_by()
    // {
    //     $args = func_get_args();
    //     $this->union_order_by = PHP_EOL.'ORDER BY '.OrderLimit::_parse_order($args);
    //     return $this;
    // }

    // public function union_limit($p1, $p2 = null)
    // {
    //     if($p2) $p2 = ', '.$p2;
    //     $this->union_limit = PHP_EOL.'LIMIT '.$p1.$p2;
    //     return $this;
    // }

    public function _render($prepare_mode = null, $strict = null)
    {
        return parent::render($prepare_mode, $strict);
    }

    // public function render($prepare_mode = null, $strict = null)
    // {
    //     if($this->sql) return $this->sql;

    //     if(empty(static::$union_obj)) 
    //         return $this->_render($prepare_mode, $strict);
    //     else static::$union_obj[] = $this;
        
    //     $sql = ''; 

    //     foreach(static::$union_obj AS $key => $select)
    //     {
    //         if($this->db->driver === 'mysql' && ($select->order_by || $select->limit || $this->union_order_by || $this->union_limit))
    //         {
    //             $bkt_open = '(';
    //             $bkt_close = ')';
    //         }
    //         else
    //         {
    //             $bkt_open = null;
    //             $bkt_close = null;
    //         }

    //         $sql .= $bkt_open.$select->_render($prepare_mode, $strict).$bkt_close.$select->union.$select->union_order_by.$select->union_limit;

    //         $this->params($select->params(true));
    //     }

    //     $this->sql = $sql;

    //     // if(DB::$prepare_mode === DB::POSITION) $this->params = array_values($this->params);
    //     // var_dump(DB::$prepare_mode); exit;

    //     $this->reset();

    //     return $this->sql;
    // }

    public function reset()
    {
        $this->from = [];
        $this->columns = [];
        $this->values = [];
        $this->placeholders = [];
        $this->join = [];
        $this->distinct = null;
        // $this->union = null;
        // static::$union_obj = [];
        // $this->union_limit = null;
        // $this->union_order_by = null;
        DB::reset();
    }

    protected function _render_select()
    {
        $select = 'SELECT '.$this->distinct.Parser::parser($this, $this->columns);
        
        // if($this->db->driver === 'sqlite')
            $select = strtr($select, ['`*`'=>'*']);

        $from = (!empty($this->from)) ? $this->eol.'FROM '.Parser::parser($this, $this->from) : null;
 
        $join = (!empty($this->join)) ? $this->eol.implode($this->eol, $this->join) : null;

        $where = (!empty($this->arr_where)) ? $this->where2string() : null;

        $group_by = (!empty($this->group_by)) ? $this->eol.'GROUP BY '.Parser::parser($this, $this->group_by) : null;

        $having = (!empty($this->arr_having)) ? $this->having2string() : null;

        $order_by = (!empty($this->order_by)) ? $this->eol.$this->order_by : null;

        $limit = ($this->limit) ? $this->eol.$this->limit : null;

        $offset = ($this->offset) ? $this->eol.$this->offset : null;

        return $select.$from.$join.$where.$group_by.$having.$order_by.$limit.$offset;
    }
}