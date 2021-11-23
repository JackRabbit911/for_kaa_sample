<?php
namespace WN\DB;

trait OrderLimit
{
    protected $limit;
    protected $order_by;
    
    public function order_by()
    {
        $args = func_get_args();
        $this->order_by = 'ORDER BY '.Lib::_parse_order($args);
        return $this;
    }

    public function limit($p1, $p2 = null)
    {
        if($p2) $p2 = ', '.$p2;
        $this->limit = 'LIMIT '.$p1.$p2;
        return $this;
    }
}