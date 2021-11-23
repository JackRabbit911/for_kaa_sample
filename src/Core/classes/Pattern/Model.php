<?php

namespace WN\Core\Pattern;

interface Model
{
    public function find($id, $param = null);

    public function save($id, $content, $param = null);

    public function delete($id);
}