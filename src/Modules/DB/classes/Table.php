<?php
namespace WN\DB;

use WN\DB\Lib\{Where, Having, OrderLimit, Tableble};

class Table implements Tableble
{
    use Where, Having, OrderLimit;

    protected static $instance = [];

    public $db;
    public $table;
    
    protected $columns = [];
    protected $distinct;
    protected $run = true;
    protected $_fetchStyle;
    protected $_ctor;
    protected $_ctorargs;
    protected $_group_by;

    public static function instance($table, $settings = null)
    {
        if(!isset(static::$instance[$table]) || !(static::$instance[$table] instanceof static))
        {
            static::$instance[$table] = new static($table, $settings);
        }
        return static::$instance[$table];
    }

    // protected static function settings($settings = null)
    // {
    //     if(!$settings)
    //     {
    //         $file_config = '../config/table.php';
    //     }
    // }

    protected function __construct($table, $settings = null)
    {
        if($settings instanceof DB) $this->db = $settings;
        elseif(is_string($settings) || $settings === null)
        {
            $connect = DB::connect($settings);
            $this->db = DB::instance($connect);
        }

        $this->table = $table;
    }

    public function get()
    {
        $sth = call_user_func_array([$this, '_prepare'], func_get_args());

        return $sth->fetch();
    }

    public function getAll()
    {
        $sth = call_user_func_array([$this, '_prepare'], func_get_args());

        // return $sth;

        return $sth->fetchAll();
    }

    public function getColumn()
    {
        $args = func_get_args();
        $colno = array_pop($args);

        $sth = call_user_func_array([$this, '_prepare'], $args);

        return $sth->fetchColumn($colno);
    }

    public function select()
    {
        $this->columns = array_merge($this->columns, func_get_args());
        return $this;
    }

    public function distinct()
    {
        $this->distinct = true;
        return $this;
    }

    public function group_by()
    {
        $args = func_get_args();
        $this->_group_by = $args;
        return $this;
    }

    public function set($data)
    {
        if(empty($data)) return; // $this->insert($data);  // ?????

        if(!empty($this->arr_where))
        {
            $update = $this->db->update($this->table)->set($data);
            $update->arr_where = $this->arr_where;
            return $update->execute();
        }

        $upsert = $this->db->upsert($this->table)->set($data);
        // if($key) $upsert->keys($key);  // function keys() ????

        return ($this->run) ? $upsert->execute() : $upsert;
    }

    public function insert(array $data)
    {
        $insert = $this->db->insert($this->table)->set($data);

        if($this->run) return $insert->execute();
        else return $insert;
    }

    public function update(array $data)
    {
        $update = $this->db->update($this->table)->set($data);
        // $update->arr_where = $this->arr_where;
        if($this->arr_where) $update->arr_where = array_merge($update->arr_where, $this->arr_where);

        $this->arr_where = [];

        return ($this->run) ? $update->execute() : $update;
    }

    public function upsert(array $data)
    {
        $upsert = $this->db->upsert($this->table)->set($data);

        return ($this->run) ? $upsert->execute() : $upsert;
    }

    public function delete()
    {   $args = func_get_args();
        $delete = $this->db->delete($this->table);

        if($this->arr_where) $delete->arr_where = array_merge($delete->arr_where, $this->arr_where);
        if(!empty($args)) call_user_func_array([$delete, 'where'], $args);
        $this->arr_where = [];

        if($this->db->driver === 'sqlite')
            $this->db->pdo->exec("PRAGMA foreign_keys = ON");

        return ($this->run) ? $delete->execute() : $delete;
    }

    public function create($options)
    {
        $create = $this->db->create($this->table)->set($options);
        return ($this->run) ? $create->exec() : $create;
    }

    public function drop()
    {
        return $this->db->drop($this->table);
    }

    public function columns($column = null)
    {
        return $this->db->columns($this->table, $column);
    }

    public function prepare()
    {
        $this->run = false;
        return $this;
    }

    public function setFetchMode($fetchStyle, $ctor = null, $ctorargs = null)
    {
        $this->_fetchStyle = $fetchStyle;
        $this->_ctor = $ctor;
        $this->ctorargs = $ctorargs;
        return $this;
    }

    protected function _prepare()
    {
        $args = func_get_args();

        // if(count($args) == 1 && empty($this->columns) && !$this->arr_where && !$this->_ctor)
        // {
        //     if(is_numeric($args[0]))
        //         $sth = $this->db->pdo->query("SELECT * FROM `$this->table` WHERE `id` = $args[0]");
        //     elseif($args[0] instanceof Expression)
        //         $sth = $this->db->pdo->query("SELECT ".$args[0]->compile()." FROM `$this->table` LIMIT(1)");
        // }
        // elseif(count($args) == 0 && empty($this->columns) && !$this->arr_where && !$this->_ctor)
        // {
        //     $sth = $this->db->pdo->query("SELECT * FROM `$this->table`");
        // }
        // else
        {
            $select = $this->db->select($this->columns)->from($this->table);

            if($this->distinct) $select->distinct();
           
            if($this->arr_where) $select->arr_where = array_merge($select->arr_where, $this->arr_where);

            if(!empty($args)) call_user_func_array([$select, 'where'], $args);
            
            // var_dump($select->arr_where); exit;

            if($this->_group_by) $select->group_by = $this->_group_by;

            if($this->order_by) $select->order_by = $this->order_by;

            if($this->limit) $select->limit = $this->limit;
            if($this->offset) $select->offset = $this->offset;
            // $select->limit(2);

            // var_dump($this->limit);

            // var_dump($select->render());
            // var_dump($select->params(false));
            // exit;

            $sth = $select->execute();
        }

        $this->arr_where = $this->arr_having = [];

        if($this->_fetchStyle)
        {
            if(!$this->_ctor && $this->_ctor != 0 && !$this->_ctorargs)
                $sth->setFetchMode($this->_fetchStyle);
            elseif(($this->_ctor || $this->_ctor == 0) && !$this->_ctorargs)
                $sth->setFetchMode($this->_fetchStyle, $this->_ctor);
            else $sth->setFetchMode($this->_fetchStyle, $this->_ctor, $this->_ctorargs);
        }
       
        return $sth;
    }
}