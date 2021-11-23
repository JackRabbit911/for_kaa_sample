<?php

namespace WN\Core\Model\Data;

use WN\Core\Helper\{Arr, Dir};
use WN\Core\Model\Data\File;

class Files
{
    public $dir;
    public $driver;
    public $rename = false;

    public function __construct($options = [])
    {
        $this->dir = Dir::prepare($options['dir']);
        $this->options = $options;

        if(!is_dir($this->dir)) mkdir($this->dir, 0777, true);
    }

    public function set_lifetime($lifetime)
    {
        $this->options['lifetime'] = $lifetime;
    }

    public function get($file = null)
    {
        if($file && preg_match('/[*?!\[\]]/', $file) == 0)
        {
            $ext = pathinfo($file, PATHINFO_EXTENSION);

            if($ext && strpos(Arr::get($this->options, 'ext'), $ext) !== false) $this->options['ext'] = null;
            else
                $this->options['ext'] = (!empty($this->options['ext'])) ? '.'.ltrim($this->options['ext'], '.') : null;

            if(!is_file($file))
                $file = Dir::prepare($this->dir).'/'.$file.Arr::get($this->options, 'ext');

            $this->driver = File::factory($file, $this->options);
            return $this->driver->get();
        }
        else
        {
            $result = [];
            $files = Dir::get($this->dir, $file);

            foreach($files AS $file)
            {
                $content = File::factory($file, $this->options)->get();
                if($content) $result[pathinfo($file, PATHINFO_FILENAME)] = $content;
            }

            return $result;
        }
    }

    public function set($file, $data, $lifetime = 0)
    {
        if(!$this->driver || !$this->rename) $this->driver = File::factory($file, $this->options);

        if(pathinfo($this->driver->file, PATHINFO_FILENAME) !== $file)
            $this->driver->rename($file);
       
        $this->driver->set($data, $lifetime, true);
    }

    public function delete($file = null, $lifetime = null)
    {
        if($file && preg_match('/[*?!\[\]]/', $file) == 0)
        {
            if(!$this->driver) $this->driver = File::factory($file, $this->options);
            $this->driver->delete();
        }
        else Dir::clean($this->dir, $file, $lifetime);
    }
}