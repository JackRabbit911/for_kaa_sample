<?php

namespace WN\Core\Model\Message;

use WN\Core\Model\Data\FileData;
use WN\Core\I18n;

class File
{
    public static $dir = 'messages';
    protected $folder;
    protected $file;
    protected $default_file;
    protected $default_message;

    public function __construct($lang, $folder = '', $default_message)
    {
        $this->folder = (!empty($folder)) ? '/'.$folder.'/' : '/';

        $this->file = static::$dir.$this->folder.$lang;
        $this->default_file = static::$dir.$this->folder.I18n::$default_lang;
        $this->default_message = $default_message;
    }

    public function get($path)
    {
        if(!($value = FileData::get($this->file, $path)))
        {
            if(!($value = FileData::get($this->default_file, $path)))
                if(!($value = FileData::get($this->file, 'default')))
                    if(!($value = FileData::get($this->default_file, 'default')))
                        $value = $this->default_message;
        }
        return $value;
    }

    public function key_exists($path)
    {
        if(!($value = FileData::get($this->file, null)))
            if(!($value = FileData::get($this->default_file, null)))
                return false;
            else return key_exists($path, $value);
        else return key_exists($path, $value);
    }
}