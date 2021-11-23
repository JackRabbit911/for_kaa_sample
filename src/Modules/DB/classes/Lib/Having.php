<?php
namespace WN\DB\Lib;

trait Having
{
    use WhereHaving;

    public $arr_having = [];
   
    public function having($column, $compare = null, $value = null)
    {
        if(func_num_args() == 1)
        {
            $value = $column;
            $column = 'id';
            $compare = '=';
        }
        elseif(func_num_args() == 2)
        {
            $value = $compare;
            $compare = '=';
        }

        if(!empty($this->arr_having))
            $this->and_having($column, $compare, $value);
        else
            $this->arr_having[] = ['HAVING', $column, $compare, $value,];

        return $this;
    }
    
    public function and_having($column, $compare = null, $value = null)
    {
        if(func_num_args() === 2)
        {
            $value = $compare;
            $compare = '=';
        }
        
        if(empty($this->arr_having))
            $this->having($column, $compare, $value);
        else
            $this->arr_having[] = ['AND', $column, $compare, $value];

        return $this;
    }
    
    public function or_having($column, $compare = null, $value = null)
    {
        if(func_num_args() === 2)
        {
            $value = $compare;
            $compare = '=';
        }

        if(empty($this->arr_having))
            $this->having($column, $compare, $value);
        else
            $this->arr_having[] = ['OR', $column, $compare, $value];

        return $this;
    }
    
    public function having_open($column, $compare = null, $value = null)
    {
        if(func_num_args() === 2)
        {
            $value = $compare;
            $compare = '=';
        }

        if(!empty($this->arr_having))
            $this->arr_having = $this->_and_having_open($column, $compare, $value);
        else
        {
            $this->arr_having[] = ['HAVING (', $column, $compare, $value];
        }
        return $this;
    }

    public function and_having_open($column, $compare = null, $value = null)
    {
         if(func_num_args() === 2)
        {
            $value = $compare;
            $compare = '=';
        }

        if(empty($this->arr_having))
            $this->having_open($column, $compare, $value);
        else
            $this->arr_having[] = ['AND (', $column, $compare, $value];

        return $this;
    }

    public function or_having_open($column, $compare = null, $value = null)
    {
        if(func_num_args() === 2)
        {
            $value = $compare;
            $compare = '=';
        }

        if(empty($this->arr_having))
            $this->having_open($column, $compare, $value);
        else
            $this->arr_having[] = ['OR (', $column, $compare, $value];

        return $this;
    }
    
    public function having_close()
    {
        $this->arr_having[] = ')';
        return $this;
    }

    protected function having2string()
    {
        return $this->_wh($this->arr_having);
    }
}