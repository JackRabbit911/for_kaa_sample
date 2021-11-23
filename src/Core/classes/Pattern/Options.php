<?php
namespace WN\Core\Pattern;

trait Options
{
    public function options($options)
    {
        if(!empty($options) && is_iterable($options))
            foreach($options as $name => $value)
            {
                if(property_exists(get_class(), $name)) $this->$name = $value;
                else $this->data[$name] = $value;
            }
    }
}