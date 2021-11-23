<?php
use WN\Core\I18n as I18n;

function __($string, array $values = NULL, $lang = null)
{
    return 'qq'; // I18n::gettext($string, $values, $lang);
}

function _f($key, $value)
{    
    $default = [
        'date'  => 'd.m.Y',
        'time'  => 'H:m',
        'float' => '2',
        'currency'  => 'USD',
    ];

    $format = I18n::l10n(I18n::lang(), $key);

    if($format === NULL && isset($default[$key])) $format = $default[$key];

    if(function_exists($key) && $format)
    {
        return call_user_func_array($key, array($format, $value));
    }
    elseif(method_exists('Core\I18n', $key))
    {
        return call_user_func_array(array('Core\I18n', $key), array($format, $value));
    }
    else
    {
        return $value; // float($format, $value);
    }
    
    
}
