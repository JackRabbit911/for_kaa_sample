<?php
namespace WN\DB;

use WN\Core\{Core, Exception};
use WN\DB\Lib\{Where, Having, OrderLimit};

class Tree extends \WN\DB\Pattern\Scheme
{
    use Where;

    protected static $instance = [];

    // public $db;
    public $table;
    public $field_path = 'path';
    public $len = 4;

    protected $columns = [];

    // public static function instance($table, $settings = null)
    // {
    //     if(!isset(static::$instance[$table]) || !(static::$instance[$table] instanceof static))
    //     {
    //         static::$instance[$table] = new static($table, $settings);
    //     }
    //     return static::$instance[$table];
    // }

    protected function __construct($table, $settings = null)
    {
        parent::__construct($table, $settings);

        // if($settings instanceof DB) $this->db = $settings;
        // elseif(is_string($settings) || $settings === null)
        // {
        //     $connect = DB::connect($settings);
        //     $this->db = DB::instance($connect);
        // }


        $this->table = $table;
    }

    public function select()
    {
        $this->columns = array_merge($this->columns, func_get_args());
        return $this;
    }

    public function children()
    {
        if(func_num_args() == 0 || empty(func_get_arg(0))) $where = null;
        else
        {
            $where = $this->db->select('t1.'.$this->field_path)->from($this->table.' t1');
            call_user_func_array([$where, 'where'], func_get_args());
        }

        if($this->db->driver === 'sqlite')
        {
            $expr_join = DB::expr("`$this->table`.`$this->field_path` || '%'");
            $expr_like = ($where) ? DB::expr($where, " || '%'") : '%';
        }
        elseif($this->db->driver === 'mysql')
        {
            $expr_join = DB::expr("CONCAT(`$this->table`.`$this->field_path`, '%')");
            $expr_like = ($where) ? DB::expr("CONCAT(", $where, ", '%')") : '%';
        }

        $this->columns_where_prepare();

        $tree = $this->db->select($this->columns)
            ->select('count(t.id)-1 children')
            ->select(DB::expr('CEIL((LENGTH(`'.$this->table.'`.`'.$this->field_path.'`)/'.$this->len.')-1) AS lvl'))
            ->from($this->table)
            ->join($this->table.' t', 'left')->on('t.'.$this->field_path, 'like', $expr_join)
            ->where($this->table.'.'.$this->field_path, 'like', $expr_like)
            // ->where(DB::expr('LENGTH(`'.$this->table.'`.`'.$this->field_path.'`) = 8'))
            ->group_by($this->table.'.id')
            ->order_by($this->table.'.'.$this->field_path);

            if($this->arr_where) $tree->arr_where = array_merge($tree->arr_where, $this->arr_where);

        $this->arr_where = $this->columns = [];

        // echo $tree->render(true); exit;

        return $tree->execute()->fetchAll();
    }

    public function parents()
    {
        $pids = call_user_func_array([$this, 'parent_ids'], func_get_args());

        $this->columns_where_prepare();

        $select = $this->db->select($this->columns)->from($this->table)->where('id', 'in', $pids)
            ->order_by($this->field_path);        

        if($this->arr_where) $select->arr_where = array_merge($select->arr_where, $this->arr_where);

        $this->arr_where = $this->columns = [];
           
        return $select->execute()->fetchAll();
    }

    public function parent_ids()
    {
        $select = $this->db->select($this->field_path)->from($this->table);
        call_user_func_array([$select, 'where'], func_get_args());
        $path = $select->execute()->fetch()[$this->field_path];

        $pids = str_split($path, $this->len);

        return array_map(function($v){return base_convert($v, 36, 10);}, $pids);
    }

    public function parent_id()
    {
        $select = $this->db->select($this->field_path)->from($this->table);
        call_user_func_array([$select, 'where'], func_get_args());
        $path = $select->execute()->fetch()[$this->field_path];

        return base_convert(substr($path, 0, -$this->len), 36, 10);
    }

    public function parent_paths($path)
    {
        $res = [];
        $arr = str_split($path, $this->len);

        foreach($arr as $k => $item)
        {
            $prefix = $res[$k-1] ?? null;
            $res[$k] = $prefix.$item;
        }

        return $res;
    }

    public function insert($data, $parent = null)
    {
        try
        {
            if($this->db->pdo->inTransaction() === false)
                $this->db->pdo->beginTransaction();

            $id = $this->db->insert($this->table)->set($data)->execute();
            $this->set_path($id, $parent);

            if($this->db->pdo->inTransaction() === true)  $this->db->pdo->commit();

            // return $id;           
        }
        catch(\PDOException $e)
        {
            if($this->db->pdo->inTransaction() === true)
                $this->db->pdo->rollBack();

            if(Core::$errors) Exception\Handler::exceptionHandler($e);

            return false;
        }

        return $id ?? false;
    }

    public function delete($id, $cascade = null)
    {
        $children = $this->children($id);

        if(empty($children)) return null;
        elseif($children[0]['children'] > 0)
        {
            if($cascade === false) return false;
            elseif($cascade === null)
            {
                $suffix = $this->_get_path_str($id);

                $delete = $this->db->delete($this->table)->where($id);
                $update = $this->db->update($this->table)
                    ->set($this->field_path, DB::expr("REPLACE(`$this->field_path`, '", $suffix, "', '')"))
                    ->where($this->field_path, 'like', '%'.$suffix.'%');

                return $this->db->transaction($delete, $update);
            }
            elseif($cascade === true)
            {
                $ids = array_map(function($v){return $v['id'];}, $children);
                $delete = $this->db->delete($this->table)->where('id', 'in', $ids);
            }
        }
        else $delete = $this->db->delete($this->table)->where($id);

        return $this->db->transaction($delete);
    }

    public function move($id, $parent_id)
    {
        if($id == $parent_id) return 0;

        $paths = $this->db->select('id', $this->field_path)->from($this->table)
            ->where($id)->or_where($parent_id)->execute()->fetchAll(\PDO::FETCH_KEY_PAIR);

        $suffix = $this->_get_path_str($id);
        $path = $paths[$parent_id] ?? '';
        $replace = $path.$suffix;

        $update = $this->db->update($this->table)
            ->set($this->field_path, DB::expr("REPLACE(`$this->field_path`, '", $paths[$id], "', '", $replace, "')"))
            ->where($this->field_path, 'like', $paths[$id].'%');

        return $this->db->transaction($update);
    }

    public function set_path($id, $parent)
    {
        $path = $this->get_path($id, $parent);
        return $this->db->update($this->table)
                    ->set($this->field_path, $path)
                    ->where($id)
                    ->execute();
    }

    public function get_path($id, $parent)
    {
        if(empty($parent)) $path = [''];
        elseif(!is_string($parent))
        {
            $path = $this->db->select($this->field_path)->from($this->table)
                ->where($parent)->limit(1)->execute()->fetch(\PDO::FETCH_NUM);
        }
        else $path[] = $parent;

        return $path[0].$this->_get_path_str($id);
    }

    public function _get_path_str($id)
    {
        $base36 = base_convert($id, 10, 36);
        return str_pad($base36, $this->len, 0, STR_PAD_LEFT);
    }

    protected function columns_where_prepare()
    {
        if($this->arr_where)
        {
            foreach($this->arr_where AS &$clause)
            {
                // if($clause[1] instanceof Expression)
                //     $clause[1] = $clause[1];
                if(!$clause[1] instanceof Expression && strpos($clause[1], $this->table) !== 0)
                    $clause[1] = $this->table.'.'.$clause[1];
            }

            $this->arr_where[0][0] = 'AND';
        }

        if(empty($this->columns)) $this->columns = $this->table.'.*';
        else
            foreach($this->columns AS &$column)
                if(strpos($column, $this->table) !== 0)
                    $column = $this->table.'.'.$column;
    }
}