<?php
namespace WN\DB;

use WN\DB\Lib\{Where, OrderLimit, Render, Parser};

class Update extends Render
{
    use Where, OrderLimit;

    protected $data = [];
    protected $tables = [];

    public function __construct($db)
    {
        $this->db = func_get_arg(0);
        $args = func_get_arg(1);

        $this->tables = $args;
    }

    public function set($data, $value = null)
    {
        if(is_array($data)) $this->data = array_merge($this->data, $data);
        elseif(is_string($data)) $this->data[$data] = $value;

        return $this;
    }

    public function _render()
    {
        array_walk($this->tables, function(&$v, $k){$v = Parser::escape($v);});
        $tables = implode(', ', $this->tables);

        foreach($this->data AS $key => $value)
        {
            $plh = $this->placeholder($key, '=', $value);
            $set[] = Parser::escape($key).' = '.$plh;
        }

        $set = implode(', ', $set);

        $where = (!empty($this->arr_where)) ? $this->where2string() : null;

        return "UPDATE ".$tables.$this->eol."SET $set $where";
    }
    
    public function execute($params = null)
    {
        return parent::execute($params)->rowCount();
    }
}