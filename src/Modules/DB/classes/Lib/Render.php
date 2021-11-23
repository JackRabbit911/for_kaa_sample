<?php
namespace WN\DB\Lib;

use WN\DB\DB;

abstract class Render
{
    public $params = [];

    protected $eol = ' ';
    public $sql;

    protected $render_func = '_render';
    protected $_fetchMode;

    protected $prepare_mode;

    public function render($prepare_mode = null, $strict = null)
    {
        if($this->sql) return $this->sql;

        $this->pre_render($prepare_mode, $strict);

        // var_dump(DB::$prepare_mode);

        $this->sql = call_user_func([$this, $this->render_func]);

        $this->post_render();

        return $this->sql;
    }

    public function params($params = false)
    {
        // var_dump(DB::$prepare_mode);

        if(is_array($params))
        {
            $this->params = array_merge($this->params, $params);
            return $this;
        }
        // elseif($params === true) return  $this->params;
        // else return array_values($this->params);
        elseif($params === DB::POSITION)
        {
            // var_dump(DB::$prepare_mode);
            return array_values($this->params);
        }
        // else return $this->params;
        // if($params !== true && DB::$level === 0)
        //     DB::$prepare_mode = DB::$prepare_mode_default;

        // else 
        return $this->params;
    }

    public function prepare($params = null)
    {
        if($this->sql) $sql = $this->sql;
        else $sql = $this->render();

        $sth = $this->db->pdo->prepare($sql);

        return $sth;
    }

    public function execute($params = null)
    {
        $sth = $this->prepare($params);

        // if(DB::$prepare_mode === DB::NAMED)
        //     $assoc = true;
        // else $assoc = false;

        if(!$params) $params = $this->params(DB::$prepare_mode);

        // if(!$params) $params = $this->params;

        foreach($params AS $key => $value)
        {
            if(is_int($key)) $key++;

            if(is_int($value)) $type = \PDO::PARAM_INT;
            elseif(is_string($value)) $type = \PDO::PARAM_STR;
            elseif(is_bool($value)) $type = \PDO::PARAM_BOOL;
            else $type = null;

            // echo $key, $value, $type, '<br>';

            $sth->bindValue($key, $value, $type);
        }

        // exit;

        // DB::$prepare_mode = DB::$prepare_mode_default;

        // echo $this->sql; exit;

        $sth->execute();
        return $sth;
    }

    protected function pre_render($prepare_mode = null, $strict = null)
    {
        DB::$level++;

        if($strict === null && DB::$level > 0) $strict = DB::$strict;
        elseif($strict === null && DB::$level === 0) $strict = DB::$strict_default;
        else DB::$strict = $strict;

        if($strict === true)
            $this->eol = PHP_EOL.str_repeat("\t", DB::$level);
        else $this->eol = ' ';

        if($prepare_mode === null && DB::$level == 1) DB::$prepare_mode = DB::$prepare_mode_default;
        elseif($prepare_mode) 
            DB::$prepare_mode = $prepare_mode;

        
    }

    protected function post_render()
    {
        DB::$level--;

        if(DB::$level === 0)
        {
            DB::$strict = DB::$strict_default;
            // DB::$prepare_mode = DB::$prepare_mode_default;
            if(!isset($this->union)) Parser::$_plhs = [];
            // DB::reset();
        }
    }
}