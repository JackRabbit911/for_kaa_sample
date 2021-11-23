<?php

namespace WN\Core\Validation;

use WN\Core\Message;

class Response
{
    public static $valid;
    public static $invalid;

    public $status;
    public $code;
    public $msg;
    // public $name;
    public $value;
    public $vars = [];

    public static function set_classes($valid, $invalid)
    {
        static::$valid = $valid;
        static::$invalid = $invalid;
    }

    public function reset()
    {
        static::$valid = static::$invalid = null;
        $this->status = $this->code = $this->msg = $this->value = null;
        // $this->vars = [];

        return $this;
    }

    public function __construct($func = null, $args = null)
    {
        $this->message = new Message('validation', 'Invalid data');

        list($this->code, $this->vars) = $this->get_params($func, $args);
    }

    public function class()
    {
        if($this->status === null) return null;
        else return ($this->status) ? ' '.static::$valid : ' '.static::$invalid;
    }

    public function msg($replace = [])
    {
        if($replace) $this->vars = array_replace($this->vars, $replace);

        if(isset($this->msg)) $this->msg = strtr($this->msg, $this->vars);
        elseif($this->code) $this->msg = $this->message->get($this->code, $this->vars);
        else $this->msg = null;

        return $this->msg;
    }

    public function checked($value = null)
    {
        if($value === null)
        {
            if(isset($this->vars[':value'])) 
                return ' checked';
            else return '';
        }
        else
        {
            if($this->value == $value)
                return ' checked';
            else return '';
        }
    }

    public function check($check, $options = null)
    {
        if(isset($options['code'])) $this->code = $options['code'];

        if($check === null)
        {
            $this->msg = null;
            return;
        }
        elseif($check === true)
        {
            if($options['value'] != '')
            {
                $this->status = true;
                $this->value = $options['value'] ?? $this->vars[':value'] ?? null;
            }
            else
            {
                $this->status = null;
                $this->value = null;
            }
            $this->msg = '';
            return;
        }
        elseif(is_array($check))
        {
            foreach($check AS $k => $v)
                $this->$k = $v;  
        }
        elseif(is_string($check))
        {
            $this->status = false;

            if($this->message->key_exists($check)) $this->code = $check;
            else $this->msg = $check;
        }
        else
        {
            $this->status = false;
            $this->value = null;
            $this->msg = null;
        }
    }

    protected function get_params($func, $args)
    {
        $vars = [];

        if(is_array($func) && method_exists($func[0], $func[1]))
            $reflector = new \ReflectionMethod($func[0], $func[1]);
        elseif(is_string($func) && function_exists($func))
            $reflector = new \ReflectionFunction($func);
        elseif(is_string($func) && is_callable($func))
            $reflector = new \ReflectionMethod($func);
        elseif(is_object($func) && $func instanceof \Closure)
            $reflector = new \ReflectionFunction($func);
              
        if(isset($reflector))
        {
            $code = $reflector->getShortName();

            foreach($reflector->getParameters() as $k=>$rp)
            {
                if(isset($args[$k]))
                    $vars[':'.$rp->name] = $args[$k];
            }
        }
        else
        {
            $code = (is_array($func)) ? $func[1] : $func;
        }

        if($code === 'filter' && isset($vars[':filter']))
        {
            $code = $code.'.'.$vars[':filter'];
        }

        return [$code, $vars];
    }
}