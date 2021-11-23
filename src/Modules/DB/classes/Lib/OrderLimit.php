<?php
namespace WN\DB\Lib;

trait OrderLimit
{
    public $limit;
    public $offset;
    public $order_by;
    
    public function order_by()
    {
        $args = func_get_args();

        if($args && $args !== [null])
            $this->order_by = 'ORDER BY '.static::_parse_order($args);

        return $this;
    }

    public function limit($limit = null)
    {
        if($limit) $this->limit = 'LIMIT '.$limit;
        return $this;
    }

    public function offset($offset)
    {
        if($offset) $this->offset = 'OFFSET '.$offset;
        return $this;
    }

    public static function _parse_order($args)
    {
        foreach($args AS $arg)
            $res[] = static::santize_order($arg);
        
        return  implode(', ', $res);
    }

    public static function santize_order($str)
    {
        $arr = explode(', ', $str);

        foreach($arr AS $substr)
        {
            $arr_substr = explode(' ', $substr);
            $arr_substr[0] = Parser::santize_string($arr_substr[0]);

            if(isset($arr_substr[1])) $arr_substr[1] = strtoupper($arr_substr[1]);

            $arr1[] = implode(' ', $arr_substr);
        }
        
        return implode(', ', $arr1);
    }
}