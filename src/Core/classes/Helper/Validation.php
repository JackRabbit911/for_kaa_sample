<?php

namespace WN\Core\Helper;

use WN\Core\{Core, I18n, Config};
use WN\Core\Exception\WnException;

class Validation
{
    public static $datatypes = [
        'username'     => ['regexp', '/^[\w\s\-@.]*$/u'],
        'password'     => ['regexp', '/^[\w\s\-@.]*$/u'],
        'email'        => ['filter', FILTER_VALIDATE_EMAIL],
        'integer'      => ['filter', FILTER_VALIDATE_INT],
        'alpha'        => ['regexp', '/^[a-zA-Z]*$/'],
        'alpha_num'    => ['regexp', '/^[a-zA-Z0-9]*$/'],
        'alpha_utf8'   => ['regexp', '/^[\pL]*$/u'],
        'alpha_num_utf8'=>['regexp', '/^[\w]*$/u'],
        'alpha_space'  => ['regexp', '/^[a-zA-Z\s]*$/u'],
        'alpha_space_utf8'=>['regexp', '/^[\pL\s]*$/u'],
        'phone'        => ['regexp', '/^[\+\s\d\-()]{3,20}$/'],
        'phone_strict' => ['regexp', '/^[\d]{11,11}$/'],
        ];

    public static $validation;
    public static $post;
    public static $name;

    public static function __callStatic($name, $arguments)
    {        
        if(isset(static::$datatypes[$name]))
        {
            $func = static::$datatypes[$name][0];
            array_push($arguments, static::$datatypes[$name][1]);          
            return call_user_func_array([__CLASS__, $func], $arguments);
        }
        elseif(Core::$errors)
            throw new WnException('function: :func not found!', [':func'=>$name]);
        else return true;
    }

    public static function confirm($value, $field = 'password')
    {
        // var_dump(static::$validation->response[$field]->value);
        // exit;

        if(isset(static::$post[$field]))
            $confirm = static::$post[$field];
        elseif(Core::$errors) throw new WnException('field: ":field" not found!', [':field'=>$field]);
        else return false;

        // return false;
        static::$validation->response[static::$name]->vars[':field'] = __(ucfirst($field));

        return ((string) $value === (string) $confirm) ? true : false;
    }

    public static function regexp($value, $regex)
    {
        // var_dump($value, $regex);
        if(empty($value)) return true;
        return (preg_match($regex, $value) === 0) ? FALSE : TRUE;
    }

    public static function filter($value, $filter, $options = [])
    {
        if(empty($value)) return true;
        return (filter_var($value, $filter, $options)) ? true : false;
    }

    public static function required($value)
    {
        if(empty($value)) return false;
        // elseif(is_array($value)) return Upload::not_empty($value);
        else return true;
    }

    public static function required_one_of($value)
    {
        $args = func_get_args();

        array_shift($args);

        $fill = 0;
        // static::$validation->response[static::$name]->value = $value;


        // $args[] = static::$name;

        // var_dump($args);

        foreach($args as $field)
        {
            // var_dump($field, static::$validation->response[$field]->value);
            if(!empty(static::$post[$field])) //static::$validation->response[$field]->value))
            {
                // var_dump('qq');
                $fill = 1;
                break;
            }
        }

        if($fill === 0)
        {
            foreach($args as $field)
            {
                static::$validation->response[$field]->status = false;
                $fields[] = __(ucfirst($field));
            }

            // $fields[] = __(ucfirst(static::$name));
            static::$validation->response[static::$name]->vars[':fields'] = implode(', ', $fields);
            return false;
        }
        else return true;
    }

    public static function valid_date($value, $format = null)
    {
        if(empty($value)) return true;

        if(!$format) $format = I18n::l10n('date');

        // var_dump($format); exit;
        $_format = I18n::l10n('user_date_format');
        if(!$_format) $_format = $format;

        static::$validation->response[static::$name]->vars[':format'] = $_format;

        // var_dump(static::$validation->response[static::$name]->vars[':format']);

        $d = \DateTime::createFromFormat($format, $value);
        if($d && $d->format($format) == $value)
        {
            // $value = $d->getTimestamp();
            return true;
        }
        else
        {
            // var_dump($format);
            return false;
        }
    }

    public static function length($value, $min, $max)
    {
        if(empty($value)) return true;
        return (mb_strlen($value) < $min || mb_strlen($value) > $max) ? false : true;
    }

    public static function min_length($value, $min)
    {
        if(empty($value)) return true;
        return (mb_strlen($value) < $min) ? false : true;
    }

    public static function max_length($value, $max)
    {
        if(empty($value)) return true;
        return (mb_strlen($value) > $max) ? false : true;
    }

    public static function boolean($value)
    {
        // return is_bool(null);     
        return is_bool(filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
    }

    public static function in_range($value, $min, $max)
    {
        if(empty($value)) return true;
        return ($value >= $min && $value <= $max);
    }

    public static function email_or_phone($value)
    {
        if(empty($value)) return true;
        if(!static::email($value))
            return static::phone($value);
        else return true;
    }

    // public static function valid($value)
    // {
    //     return Upload::valid($value);
    // }

    // public static function type($value, array $allowed)
    // {
    //     return Upload::type($value, $allowed);
    // }

    // public static function image($value, $max_width = NULL, $max_height = NULL, $exact = FALSE)
    // {
    //     return Upload::image($value, $max_width, $max_height, $exact);
    // }
}