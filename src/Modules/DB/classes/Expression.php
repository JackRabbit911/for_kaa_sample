<?php
namespace WN\DB;

use WN\DB\Lib\Render;

class Expression
{
    public $expr = [];

    public function __construct($args = [])
    {
        $this->expr = $args;
    }

    public function compile($query = null)
    {
        // var_dump($this->expr, $query); exit;
        foreach($this->expr AS $item)
        {
            if($item instanceof Select && $query instanceof Render)
            {
                $expression[] = '('.$item->_render(DB::NAMED).')';
                $query->params($item->params(DB::NAMED));
            }
            else $expression[] = $item;
        }

        return implode('', $expression);
    }
}