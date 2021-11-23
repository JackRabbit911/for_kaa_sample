<?php
namespace WN\DB;

trait WhereHaving
{
    protected static $prepare_mode;

    public $params = [];

    protected function _wh($array)
    {
        $wh = '';
        foreach($array AS $item)
        {
            if(is_array($item))
            {
                if($item[1] instanceof Select)
                {
                    // var_dump($item); exit;
                    $wh .= $item[1]->_render();
                    // die('qq');
                }
                else
                {
                    $wh .= 
                        $this->eol.$item[0].' '
                        .Lib::santize_string($item[1]).' '
                        .strtoupper($item[2]).' '
                        .$this->_placeholder($item[1], $item[2], $item[3], $item[4]);
                }
            }
            else $wh .= $item;
        }
        return $wh;
    }

    protected function _placeholder($column, $compare, $value, $is_plh = true)
    {
        if($is_plh === false) return Lib::escape($value);
        
        if(is_array($value))
        {
            if(stripos($compare, 'BETWEEN') !== false)
            {
                $placeholder = $this->between($column, $value);
            }
            else
            {
                if(DB::$prepare_mode === DB::NAMED)
                {
                    foreach($value AS $k=>$v)
                    {
                        $plh = Lib::_get_plh_unique($column);
                        $this->params[$plh] = (is_string($v)) ? "'$v'" : $v;
                        $arr_plh[$k] = $plh;
                    }
                    $placeholder = '('.implode(', ', $arr_plh).')';
                }
                elseif(DB::$prepare_mode === DB::POSITION)
                {
                    foreach($value AS $k=>$v)
                    {
                        $plh = '?';
                        $this->params[] = $v;
                        $arr_plh[$k] = $plh;
                    }
                    $placeholder = '('.implode(', ', $arr_plh).')';
                }
                else $placeholder = '('.implode(', ', $value).')';
            }
        }
        elseif($value instanceof Select)
        {
            $placeholder = '('.$value->_render().')';
            $this->params($value->params());
        }
        else
        {
            if(!Lib::santize_set($value))
            {
                if(DB::$prepare_mode === DB::NAMED)
                {
                    $placeholder = Lib::_get_plh_unique($column);
                    $this->params[$placeholder] = $value;
                }
                elseif(DB::$prepare_mode === DB::POSITION)
                {
                    $placeholder = '?';
                    $this->params[] = $value;
                }
                else
                    $placeholder = (is_string($value)) ? "'".$value."'" : $value;
            }
            else $placeholder = $value;
        }

        return $placeholder;
    }

    protected function between($column, $value)
    {
        // var_dump($value); exit;
        if($value[0] instanceof Select)
        {
            $plh1 = '('.$value[0]->_render().')';
            $this->params($value[0]->params());
        }
        else
        {
            if(DB::$prepare_mode === DB::NAMED)
            {
                $plh1 = Lib::_get_plh_unique($column);
                $this->params[$plh1] = $value[0];
            }
            elseif(DB::$prepare_mode === DB::POSITION)
            {
                $plh1 = '?';
                $this->params[] = $value[0];
            }
            else $plh1 = (is_string($value[0])) ? "'$value[0]'" : $value[0];
        }

        if($value[1] instanceof Select)
        {
            $plh2 = '('.$value[1]->_render().')';
            $this->params($value[1]->params());
        }
        else
        {

            if(DB::$prepare_mode === DB::NAMED)
            {
                $plh2 = Lib::_get_plh_unique($column);
                $this->params[$plh2] = $value[1];
            }
            elseif(DB::$prepare_mode === DB::POSITION)
            {
                $plh2 = '?';
                $this->params[] = $value[1];
            }
            else $plh2 = (is_string($value[1])) ? "'$value[1]'" : $value[1];
        }

        return ''.$plh1.' AND '.$plh2;
    }
}