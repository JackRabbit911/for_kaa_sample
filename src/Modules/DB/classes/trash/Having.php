<?php
namespace WN\DB;

trait Having
{
    use WhereHaving;

    protected $arr_having = [];
    
    public function having($column, $compare = null, $value = null, $is_plh = true)
    {
        if(func_num_args() === 2)
        {
            $value = $compare;
            $compare = '=';
        }

        if(!empty($this->arr_having))
            $this->and_having($column, $compare, $value, $is_plh);
        else
        {
            $this->arr_having[] = ['HAVING', $column, $compare, $value, $is_plh];
        }

        return $this;
    }
    
    public function and_having($column, $compare = null, $value = null, $is_plh = true)
    {
        if(func_num_args() === 2)
        {
            $value = $compare;
            $compare = '=';
        }
        
        
        $this->arr_having[] = ['AND', $column, $compare, $value, $is_plh];
        return $this;
    }
    
    public function or_having($column, $compare = null, $value = null, $is_plh = true)
    {
        if(func_num_args() === 2)
        {
            $value = $compare;
            $compare = '=';
        }
        
        $this->arr_having[] = ['OR', $column, $compare, $value, $is_plh];
        return $this;
    }
    
    public function having_open($column, $compare = null, $value = null, $is_plh = true)
    {
        if(func_num_args() === 2)
        {
            $value = $compare;
            $compare = '=';
        }

        if(!empty($this->arr_having))
            $this->and_having_open($column, $compare, $value, $is_plh);
        else
        {
            $this->arr_having[] = ['HAVING (', $column, $compare, $value, $is_plh];
        }

        return $this;
    }

    public function and_having_open($column, $compare = null, $value = null, $is_plh = true)
    {
        if(func_num_args() === 2)
        {
            $value = $compare;
            $compare = '=';
        }

        $this->arr_having[] = ['AND (', $column, $compare, $value, $is_plh];
        return $this;
    }

    public function or_having_open($column, $compare = null, $value = null, $is_plh = true)
    {
        if(func_num_args() === 2)
        {
            $value = $compare;
            $compare = '=';
        }

        $this->arr_having[] = ['OR (', $column, $compare, $value, $is_plh];
        return $this;
    }
    
    public function having_close()
    {
        $this->arr_having[] = ')';
        return $this;
    }

    protected function _having()
    {
        return $this->_wh($this->arr_having);
    }
}