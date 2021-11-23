<?php
namespace WN\Core\Pattern;

abstract class ModelEntity
{
    use Singletone;

    public $entity_class;

    public function __construct()
    {
        if(!$this->entity_class)
            $this->entity_class = ltrim(str_replace('Model', '', get_called_class()), '\\');
    }
}