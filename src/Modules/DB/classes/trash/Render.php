<?php
namespace WN\DB;

trait Render
{
    protected $eol = ' ';
    protected $sql;

    public function pre_render($prepare_mode = null, $strict = null)
    {
        DB::$level++;

        if($strict === null && DB::$level > 0) $strict = DB::$strict;
        elseif($strict === null && DB::$level === 0) $strict = DB::$strict_default;
        else DB::$strict = $strict;

        if($strict === true)
            $this->eol = PHP_EOL.str_repeat("\t", DB::$level);
        else $this->eol = ' ';

        if($prepare_mode === null && DB::$level == 1) DB::$prepare_mode = DB::$prepare_mode_default;
        elseif($prepare_mode) DB::$prepare_mode = $prepare_mode;
    }

    protected function post_render()
    {
        DB::$level--;

        if(DB::$level === 0)
        {
            // var_dump(DB::$level);
            DB::$strict = DB::$strict_default;
            DB::$prepare_mode = DB::$prepare_mode_default;
            if(!isset($this->union)) Lib::$_plhs = [];
        }
    }

    public function execute($params = null)
    {
        // if($this->sql) $sql = $this->sql;
        // else 
            $sql = $this->render();

        if(!$params) $params = $this->params();
        elseif(DB::$prepare_mode === DB::NAMED)
            $params = Lib::santize_params($params);

        $sth = $this->db->pdo->prepare($sql);
        $sth->execute($params);
        return $sth;
    }
}