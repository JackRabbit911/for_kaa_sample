<?php

namespace WN\Core\Pattern;

// use ArrayObject;
use WN\Core\Helper\{Arr, Text};
use WN\Core\Exception\WnException;

abstract class Entity
{
    use Settings, Options;

    public static $model;

    public $id;
    public $data = [];

    public static function model_instance()
    {
        if(static::$model instanceof ModelEntity) return;

        $class = get_called_class();

        if(is_array(static::$model))
        {
            $settings = static::$model;
            
            if(isset(static::$model['class_model']))
            {
                static::$model = $settings['class_model'];
                unset($settings['class_model']);               
            }
            else static::$model = null;
        }
        else $settings = null;

        if(is_string(static::$model) && !class_exists(static::$model))
            static::$model = null;

        if(static::$model === null)
            static::$model = static::_get_model_name($class);

        if(is_string(static::$model) && class_exists(static::$model))
        {
            if(method_exists(static::$model, 'instance'))
                static::$model = static::$model::instance($settings);
            elseif(method_exists(static::$model, 'factory'))
                static::$model = static::$model::factory($settings);
            else static::$model = new static::$model($settings);            
        }

        if(property_exists(static::$model, 'entity_class'))
            static::$model->entity_class = $class;
    }

    public static function factory($id = null, $settings = null)
    {
        $entity = new static($id, $settings);

        return ($entity->id !== 0) ? $entity : false;
    }

    public function __construct($id = null, $settings = null)
    {
        static::settings($settings);

        static::model_instance();

        if(!$id && $this->id) $id = $this->id;

        if($id)
        {
            if(!is_array($id))
            {
                if(method_exists(static::$model, 'find')) $method = 'find';
                elseif(method_exists(static::$model, 'get')) $method = 'get';

                $data = static::$model->$method($id);
            }
            else $data = $id;

            $this->options($data);

            $this->id = (int) $this->id;
        }
    }

    public function &__get($name)
    {
        if(isset($this->data[$name])) return $this->data[$name];
        else
        {
            $x = null;
            return $x;
        }
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __unset($name)
    {
        $this->data[$name] = null;
    }

    public function __isset($name)
    {
        if(array_key_exists($name, $this->data)) return true;
        else return false;
    }

    public function __call($method, $arguments)
    {        
        return (isset($this->$method) && $this->$method instanceof \Closure)
            ? call_user_func_array(\Closure::bind($this->$method, $this, get_called_class()), $arguments)
            : null;
    }

    public function save()
    {
        foreach($this->data AS &$item)
            if(is_array($item) || is_object($item))
                $item = serialize($item);

        if($this->id) $this->data['id'] = $this->id;
        $id = static::$model->set($this->data);
        if(!$this->id) $this->id = $id;
        return $id;
    }

    protected static function _get_model_name($class)
    {
        $classname = Text::class_basename($class);
        $namespace = Text::class_namespace($class);

        $model_name = $namespace.'\Model'.$classname;

        if(class_exists(($model_name = $namespace.'\Model'.$classname))){}
        elseif(class_exists(($model_name = $namespace.'\Model\\'.ucfirst($classname))));
        else
        {
            $parent_class = get_parent_class($class);
            
            if($parent_class)
            {
                $reflection = new \ReflectionClass($parent_class);
                if($reflection->isAbstract()) return null;
                $model_name = static::_get_model_name($parent_class);
            }
            else return null;
        }

        return $model_name;
    }
}