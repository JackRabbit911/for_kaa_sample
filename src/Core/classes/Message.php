<?php

namespace WN\Core;

use WN\Core\I18n;
use WN\Core\Pattern\Options;

class Message
{
    use Options;

    public static $driver = 'file';
    public $model;

    public function __construct($folder = null, $default_message = null, $options = null)
    {
        if($options) $this->options($options);

        $driver = 'WN\Core\Model\Message\\'.ucfirst(static::$driver);
        $this->model = new $driver(I18n::lang(), $folder, $default_message);
    }

    public function get($path = null, $variables = null)
    {
        $str = $this->model->get($path);

        return ($variables && is_string($str)) ? strtr($str, $variables) : $str;
    }

    public function key_exists($path)
    {
        return $this->model->key_exists($path);
    }
}