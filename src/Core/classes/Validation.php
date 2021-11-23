<?php

namespace WN\Core;

use WN\Core\Helper\{Validation as Valid, Upload, Text, Arr};

class Validation
{
    public $rules = [];
    public $response = [];

    public $files_to_upload = [];

    // protected $checked_fields = [];

    public function __construct($rules = [])
    {
        $datatypes = Config::instance()->get('validation', null, Config::SETTINGS);
        Valid::$datatypes = array_merge(Valid::$datatypes, $datatypes);

        if(!empty($rules)) $this->rules($rules);
            // foreach($rules AS $name => $rule)
            //     $this->rule($name, $rule);
    }

    public function rule($name, $rule)
    {
        $parse_str = function($name, $rule)
        {
            $arr_rules = array_map('trim', explode('|', $rule));

            foreach($arr_rules AS $str)
            {
                if(strpos($str, ')', -1) === strlen($str)-1)
                {
                    $pattern = '/\((.*)\)/';
                    if(preg_match($pattern, $str, $match))
                        if($match[1]) $args = explode(', ', $match[1]);
                    else $args = [];

                    foreach($args AS &$arg)
                        $arg = Text::json_decode($arg);
                    
                    $func = preg_replace($pattern, '', $str);
                    array_unshift($args, $func);                    
                }
                else $args = explode(', ', $str);

                $this->rules[$name][] = $args;
            }
        };

        if(func_num_args() === 2)
        {
            if(is_string($rule))
            {
                $parse_str($name, $rule);
            }
            elseif(is_array($rule))
            {
                if(is_callable($rule) 
                    // || method_exists('WN\Core\Helper\Validation', $rule[0])
                    // || method_exists('WN\Core\Helper\Upload', $rule[0])
                    // || (is_string($rule) && array_key_exists($rule, Valid::$datatypes))
                    )
                        $this->rules[$name][] = [$rule];
                else
                {
                    if(is_callable($rule[0])
                        // || method_exists('WN\Core\Helper\Validation', $rule[0] 
                        // || method_exists('WN\Core\Helper\Upload', $rule[0]) 
                        // || (is_string($rule) && array_key_exists($rule, Valid::$datatypes)))
                        )
                            $this->rules[$name][] = $rule;
                    else
                    {
                        foreach($rule AS $item)
                        {
                            if(is_callable($item)) $this->rules[$name][] = [$item];
                            elseif(is_string($item)) $parse_str($name, $item);
                            else $this->rules[$name][] = $item;
                        }
                    }
                }
            }
            elseif($rule === null) $this->rules[$name] = null;
        }
        else
        {
            $args = func_get_args();
            array_shift($args);
            $this->rules[$name][] = $args;
        }
    }

    public function rules(array $rules)
    {
        foreach($rules AS $name => $rule)
                $this->rule($name, $rule);
    }

    public function check()
    {
        foreach($this->rules AS $name => $rule)
                    $this->response[$name] = new Validation\Response();

        // var_dump($this->response[$name]->vars);

        $post = call_user_func_array('array_merge', func_get_args());

        // foreach(func_get_args() AS $post)
        // {
            $this->post = $post;

            if(!empty($post))
            {
                foreach($post AS $name => $value)
                {
                    if(is_array($value))
                    {
                        if(Upload::valid($value))
                        {
                            // $this->checked_fields[$name] = true;
                            $check[] = Upload::check($this, $name, $value);
                            
                        }
                        else $check[] = $this->check_array($name, $value);
                    }
                    else $check[] = $this->check_field($name, trim($value));

                    unset($this->rules[$name]);
                }

                // var_dump($this->response[$name]->vars);

            // else foreach($this->rules AS $name => $rule)
            //         $this->response[$name] = new Validation\Response();

            // print_r(array_diff_key($this->rules, $this->checked_fields)); echo '<br>';

            // print_r($this->rules); echo '<br>';

            // if(!Upload::valod($value))
                // foreach(array_diff_key($this->rules, $this->checked_fields) AS $name => $rule)
                // {
                //     $check[] = $this->check_field($name, null);
                // }
                foreach($this->rules AS $name => $rule)
                    $check[] = $this->check_field($name, null);
           
            }
        // }

        

        // print_r($this->rules); echo '<br>';

        return (empty($check) || in_array(false, $check)) ? false : true;
    }

    public function set_values($data)
    {
        $data = (object) $data;

        foreach($this->response AS $name => &$response)
        {
            if(!$response->value) $response->value = $data->$name ?? null;
        }
    }

    protected function check_field($name, $value)
    {
        // $this->checked_fields[$name] = true;

        if(!isset($this->rules[$name]))
        {
            $this->response[$name] = new Validation\Response();
            $this->response[$name]->check(true, ['value' => $value]);
            return true;
        }

        foreach($this->rules[$name] AS $args)
        {
            $func = array_shift($args);

            foreach($args AS $k=>$arg)
                if(is_string($arg) && defined($arg)) $args[$k] = constant($arg);
            
            if(($key = array_search(':value', $args)) !== false)
                $args[$key] = $value;
            else array_unshift($args, $value);

            if(($key = array_search(':validation', $args)) !== false)
                $args[$key] = &$this;

            if(($key = array_search(':post', $args)) !== false)
                $args[$key] = $this->post;

            if(($key = array_search(':name', $args)) !== false)
                $args[$key] = $name;

            if(!is_callable($func))
            {
                $func = [Valid::class, $func];
                Valid::$validation = &$this;
                Valid::$post[$name] = $value;
                Valid::$name = $name;
            }
            // elseif(is_string($func)) $func = (strpos($func, '::') !== false) ? explode('::', $func) : $func;

            // var_dump($this->response[$name]->vars);

            // var_dump($func, $args);
            // $args = $args + [':name' => $name];
            // if()
            $this->response[$name] = new Validation\Response($func, $args);
            $this->response[$name]->vars[':name'] = $name;

            // var_dump($this->response[$name]->vars);

            if(($key = array_search(':response', $args)) !== false)
                $args[$key] = &$this->response;

                // var_dump($func, $args); echo '<hr>';
                // var_dump($this->response[$name]->vars);

            $check = call_user_func_array($func, $args);

            // var_dump($this->response[$name]->vars);

            if($check !== true) break;
        }

        

        // var_dump($check);
        // $args = $args + [':name' => $name];

        // if(!empty($value) && $check === true)
            // $this->response[$name] = new Validation\Response($check, $value, $func, $args);

        $this->response[$name]->check($check, ['value' => $value]);

        

        return ($check === true) ? true : false;
    }

    protected function check_array($name, $value)
    {
        $check = true;

        foreach($value AS $i => $val)
        {
            $check = $this->check_field($name, $val);

            if($check !== true)
            {               
                $check = false;
                break;
            }
            else $qq[$i] = $val;
        }

        $this->response[$name]->value = $qq;
        return $check;
    }
}