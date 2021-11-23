<?php

namespace WN\Page;

use WN\Core\Request;
use WN\Core\Exception\WnException;

class Lib
{
    public static function view_hidden_input_action($file)
    {
        include_once 'src/Core/vendor/simplehtmldom/simple_html_dom.php';
        $html_content = file_get_html($file);
        
        foreach($html_content->find('input[type="hidden"]') AS $element)
        {
            $name = $element->getAttribute("name");
            if($name === 'url' || $name == 'handler')
            {
                $value = $element->getAttribute("value");
                break;
            }
        }
        unset($html_content);

        return static::hidden_input_action($name, $value);
    }

    public static function hidden_input_action($name, $value)
    {
        if($name === 'url')
        {
            $pattern = '/^[a-zA-Z0-9\~\/_?=&+]+$/';
            if(preg_match($pattern, $value) === 1)
                return Request::factory($value)->execute();
            else throw new WnException('Hidden input value is incorrect');
        }
        elseif($name === 'handler')
        {
            $pattern = '/^[a-zA-Z0-9\\\\(::)\[\]\(\),\s]+$/';
            if(preg_match($pattern, $value) === 1)
            {
                $args = preg_split('/[:\(\),\s]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
                $class = array_shift($args);
                $func = array_shift($args);
                $object = new $class;
                return call_user_func_array([$object, $func], $args);
            }
            else throw new WnException('Hidden input value is incorrect');           
        }
        else throw new WnException('Hidden input "url" or "handler" not found');
    }
}