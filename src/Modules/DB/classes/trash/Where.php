<?php
namespace WN\DB;

trait Where
{
    use WhereHaving;

    protected $arr_where = [];
    protected $where;
    
    public function where($column, $compare = null, $value = null, $is_plh = true)
    {
        if(func_num_args() === 2)
        {
            $value = $compare;
            $compare = '=';
        }

        if(!empty($this->arr_where))
            $this->and_where($column, $compare, $value, $is_plh);
        else
        {
            $this->arr_where[] = ['WHERE', $column, $compare, $value, $is_plh];
        }

        return $this;
    }
    
    public function and_where($column, $compare = null, $value = null, $is_plh = true)
    {
        if(func_num_args() === 2)
        {
            $value = $compare;
            $compare = '=';
        }
        
        
        $this->arr_where[] = ['AND', $column, $compare, $value, $is_plh];
        return $this;
    }
    
    public function or_where($column, $compare = null, $value = null, $is_plh = true)
    {
        if(func_num_args() === 2)
        {
            $value = $compare;
            $compare = '=';
        }
        
        $this->arr_where[] = ['OR', $column, $compare, $value, $is_plh];
        return $this;
    }
    
    public function where_open($column, $compare = null, $value = null, $is_plh = true)
    {
        if(func_num_args() === 2)
        {
            $value = $compare;
            $compare = '=';
        }

        if(!empty($this->arr_where))
            $this->and_where_open($column, $compare, $value, $is_plh);
        else
        {
            $this->arr_where[] = ['WHERE (', $column, $compare, $value, $is_plh];
        }

        return $this;
    }

    public function and_where_open($column, $compare = null, $value = null, $is_plh = true)
    {
        if(func_num_args() === 2)
        {
            $value = $compare;
            $compare = '=';
        }

        $this->arr_where[] = ['AND (', $column, $compare, $value, $is_plh];
        return $this;
    }

    public function or_where_open($column, $compare = null, $value = null, $is_plh = true)
    {
        if(func_num_args() === 2)
        {
            $value = $compare;
            $compare = '=';
        }

        $this->arr_where[] = ['OR (', $column, $compare, $value, $is_plh];
        return $this;
    }
    
    public function where_close()
    {
        $this->arr_where[] = ')';
        return $this;
    }

    protected function _where()
    {
        return $this->_wh($this->arr_where);
    }
}