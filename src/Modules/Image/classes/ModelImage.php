<?php
namespace WN\Image;

use WN\UpFile\ModelUpFile;

class ModelImage extends ModelUpFile
{
    public function get()
    {
        $this->table->where('mime', 'like', 'image/%');
        return call_user_func_array('parent::get', func_get_args());
    }

    public function getAll()
    {
        $this->table->where('mime', 'like', 'image/%');
        return call_user_func_array('parent::getAll', func_get_args());
    }
}