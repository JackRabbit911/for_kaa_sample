<?php
namespace WN\DB\Lib;

use WN\DB\{DB, Expression, Select};

trait WhereHaving
{
    protected $driver;

    protected function _wh($array)
    {
        $wh = '';
        foreach($array AS $item)
        {
            if(is_array($item))
            {
                if($item[1] instanceof Select)
                {
                    $wh .= $item[1]->_render();
                    $this->params($item[1]->params(DB::NAMED));
                }
                else
                {
                    // for case case insensitive search
                    if($this->driver === 'sqlite' && is_string($item[3]))
                    {
                        $item[1] = "mb_lower($item[1])";
                        $item[3] = mb_strtolower($item[3]);
                    }

                    $wh .= 
                        $this->eol.$item[0].' '
                        .Parser::santize_string($item[1]).' '
                        .$this->compare($item[2], $item[3]).' '
                        .$this->placeholder($item[1], $item[2], $item[3]);
                }
            }
            else $wh .= $item;
        }
        return $wh;
    }

    protected function compare($compare, $value)
    {
        if($value === null)
        {
            if($compare === '=') return 'IS';
            else
            {
                $driver = __NAMESPACE__.'\\'.ucfirst($this->db->driver);
                return $driver::not_null();
            }
        }
        else return strtoupper($compare);
    }

    protected function placeholder($column, $compare, $value)
    {   
        if(is_array($value))
        {
            if(stripos($compare, 'BETWEEN') !== false)
            {
                $placeholder = $this->_placeholder($column, $value[0]).' AND '.$this->_placeholder($column, $value[1]);
            }
            else
            {
                foreach($value AS $k => $v)
                    $arr_plh[$k] = $this->_placeholder($column, $v);

                $placeholder = '('.implode(', ', $arr_plh).')';
            }
        }
        elseif($value === null) $placeholder = 'NULL';
        else $placeholder = $this->_placeholder($column, $value);
        
        return $placeholder;
    }

    protected function _placeholder($column, $value)
    {
        // var_dump(DB::$prepare_mode);
        if($value instanceof Select)
        {
            $placeholder = '('.$value->_render().')';
            $this->params($value->params(DB::NAMED));
        }
        elseif($value instanceof Expression)
        {
            $placeholder = $value->compile($this);
        }
        elseif(is_string($value) && strpos($value, '`') === 0 && strpos($value, '`', -1) === strlen($value)-1)
        {
            $placeholder = $value;
        }
        else
        {
            if(DB::$prepare_mode === DB::NAMED)
            {
                $placeholder = Parser::get_plh();
                if(isset($value) || $value === null) $this->params[$placeholder] = $value;
            }
            elseif(DB::$prepare_mode === DB::POSITION)
            {
                $placeholder = '?';
                if(isset($value) || $value === null) $this->params[Parser::get_plh()] = $value;
            }
            else
            {
                if(is_string($value)) $placeholder = "'".$value."'";
                elseif($value === null) $placeholder = "NULL";
                else $placeholder = $value;
            }
                // $placeholder = (is_string($value)) ? "'".$value."'" : $value;
        }

        return $placeholder;
    }
}