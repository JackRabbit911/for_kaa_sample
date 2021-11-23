<?php
namespace WN\DB\Lib;

use WN\DB\Expression;

trait Where
{
    use WhereHaving;

    public $arr_where = [];
   
    public function where($column, $compare = null, $value = null)
    {
        // if(func_num_args() == 1)
        // {
        //     $value = $column;
        //     $column = 'id';
        //     $compare = '=';
        // }
        // elseif(func_num_args() == 2)
        // {
        //     $value = $compare;
        //     $compare = '=';
        // }

        list($column, $compare, $value) = call_user_func_array([$this, '_args'], func_get_args());

        if(!empty($this->arr_where))
            $this->and_where($column, $compare, $value);
        else
            $this->arr_where[] = ['WHERE', $column, $compare, $value,];

        return $this;
    }
    
    public function and_where($column, $compare = null, $value = null)
    {
        // if(func_num_args() === 2)
        // {
        //     $value = $compare;
        //     $compare = '=';
        // }

        list($column, $compare, $value) = call_user_func_array([$this, '_args'], func_get_args());
        
        if(empty($this->arr_where))
            $this->where($column, $compare, $value);
        else
            $this->arr_where[] = ['AND', $column, $compare, $value];

        return $this;
    }
    
    public function or_where($column, $compare = null, $value = null)
    {
        // if(func_num_args() === 2)
        // {
        //     $value = $compare;
        //     $compare = '=';
        // }

        list($column, $compare, $value) = call_user_func_array([$this, '_args'], func_get_args());

        if(empty($this->arr_where))
            $this->where($column, $compare, $value);
        else
            $this->arr_where[] = ['OR', $column, $compare, $value];

        return $this;
    }
    
    public function where_open($column, $compare = null, $value = null)
    {
        // if(func_num_args() === 2)
        // {
        //     $value = $compare;
        //     $compare = '=';
        // }

        list($column, $compare, $value) = call_user_func_array([$this, '_args'], func_get_args());

        if(!empty($this->arr_where))
            $this->and_where_open($column, $compare, $value);
        else
        {
            $this->arr_where[] = ['WHERE (', $column, $compare, $value];
        }
        return $this;
    }

    public function and_where_open($column, $compare = null, $value = null)
    {
        //  if(func_num_args() === 2)
        // {
        //     $value = $compare;
        //     $compare = '=';
        // }

        list($column, $compare, $value) = call_user_func_array([$this, '_args'], func_get_args());

        if(empty($this->arr_where))
            $this->where_open($column, $compare, $value);
        else
            $this->arr_where[] = ['AND (', $column, $compare, $value];

        return $this;
    }

    public function or_where_open($column, $compare = null, $value = null)
    {
        // if(func_num_args() === 2)
        // {
        //     $value = $compare;
        //     $compare = '=';
        // }

        list($column, $compare, $value) = call_user_func_array([$this, '_args'], func_get_args());

        if(empty($this->arr_where))
            $this->where_open($column, $compare, $value);
        else
            $this->arr_where[] = ['OR (', $column, $compare, $value];

        return $this;
    }
    
    public function where_close()
    {
        $this->arr_where[] = ')';
        return $this;
    }

    protected function where2string()
    {
        return $this->_wh($this->arr_where);
    }

    protected function _args($column, $compare = null, $value = null)
    {
        if(func_num_args() == 1)
        {
            $value = $column;
            $column = 'id';
            $compare = (is_array($value)) ? 'IN' : '=';
        }
        elseif(func_num_args() == 2)
        {
            $value = $compare;
            $compare = (is_array($value)) ? 'IN' : '=';
        }

        return [$column, $compare, $value];
    }
}