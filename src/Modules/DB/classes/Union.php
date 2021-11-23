<?php

namespace WN\DB;

use WN\Core\Helper\Arr;
use WN\DB\Lib\{OrderLimit, Render};

class Union //extends Render
{
    use OrderLimit;

    protected $db;
    protected $_sql = '';
    protected $_params = [];
    protected $_is_all;
    protected $_objects;

    public function __construct()
    {
        $args = func_get_args();
        $this->db = array_shift($args);
        $this->_is_all = array_shift($args);
        $this->_objects = $args[0];
    }

    public function _render()
    {
        $sep = ($this->_is_all) ? ' UNION ALL ' : ' UNION ';

        // if($this->order_by)
        {
            $bkt_open = '(';
            $bkt_close = ')';
            // $this->order_by = ' '.$this->order_by;
        }
        // else $bkt_open = $bkt_close = null;

        foreach($this->_objects AS $select)
        {
            $arr_sql[] = ($select->sql)
                ? $bkt_open.$select->sql.$bkt_close
                : $bkt_open.$select->render().$bkt_close;

            $arr_params[] = $select->params(DB::$prepare_mode);
        }

        if($this->order_by) $this->order_by = ' '.$this->order_by;
;
        $this->_sql = implode($sep, $arr_sql).$this->order_by;
        $this->_params = Arr::flatten($arr_params);

        // var_dump($this->_sql); exit;

        return $this->_sql;
    }

    public function execute($params = null)
    {
        if(!$this->_sql) $this->_render();

        $sth = $this->db->pdo->prepare($this->_sql);
        $sth->execute($this->_params);
        return $sth;
    }
}