<?php

namespace WN\Core\Model;

use WN\Core\Pattern;

abstract class Model implements Pattern\Model
{
    use \WN\Core\Pattern\Singletone;

    abstract public function find($id, $param = null);

    abstract public function save($id, $content, $param = null);

    abstract public function delete($id);

}