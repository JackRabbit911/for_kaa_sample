<?php

namespace WN\Core\Model\Data;

use WN\Core\Helper\{Arr, Text};

class File // implements Model
{
    protected $path = APPPATH;
    protected $dir = 'data';

    public $ext;
    public $file;
    public $lifetime = 0;
    protected $touch = false;
    public $return_default = null;

    public static function factory(string $file, array $options = [])
    {
        return new static($file, $options);
    }

    public function __construct(string $file, array $options = [])
    {
        foreach($options as $name => $value)
            $this->$name = $value;

        $this->file = $this->_file($file);

        $this->info = pathinfo($this->file);

        if(!array_key_exists('extension', $this->info)) $this->info['extension'] = null;

        if(is_file($this->file) && $this->lifetime > 0)
        {
            // echo ' '.$this->lifetime.' ';
            $a = time() - filemtime($this->file);
            if($a > $this->lifetime) unlink($this->file);
        }
    }

    public function __toString()
    {
        return $this->get();
    }

    public function get($key = null)
    {
        if(!is_file($this->file)) return null;
        else return $this->_get($key);
    }

    public function set($content, $lifetime = 0, $replace = false)
    {
        if($replace === false && (is_array($content) || is_object($content)))
        {
            $array = (is_file($this->file)) ? $this->_get() : [];
            if(is_array($array))
                $content = array_replace_recursive($array, (array) $content);
        }

        if($this->info['extension'] === 'json') $content = json_encode($content);
        elseif($this->info['extension'] === 'wns') $content = serialize($content);

        if(!is_dir($this->info['dirname'])) mkdir($this->info['dirname'], 0777, true);

        file_put_contents($this->file, $content);

        if($lifetime > 0) touch($this->file);
    }

    public function delete()
    {
        if(!is_file($this->file)) return;

        if(func_num_args() == 0)
        {
            unlink($this->file);
        }
        else
        {
            $content = $this->_get();
            if(!is_array($content))
            {
                unlink($this->file);
            }
            else
            {
                foreach(func_get_args() as $key)
                    Arr::unset_path($content, $key);

                file_put_contents($this->file, $content);
            }
        }       
    }

    public function rename($file)
    {
        $file = $this->_file($file);

        if($file !== $this->file && is_file($this->file))
        {
            rename($this->file, $file);
            $this->file = $file;
        }
    }

    protected function _get($key = null)
    {
        
        if($this->touch) touch($this->file);

        $ext = $this->info['extension'];

        if($ext === 'json')
        {
            $array = json_decode(file_get_contents($this->file));
            return ($key) ? Arr::path($array, $key) : $array;
        }
        elseif($ext === 'php') return ($key) ? Arr::path(include $this->file, $key) : include $this->file;
        elseif($ext === 'md') return Text::markdown(file_get_contents($this->file));
        elseif($ext === 'ini')
        {
            $array = parse_ini_file($this->file);
            return ($key) ? Arr::path($array, $key) : $array;
        }
        elseif($ext === 'wns')
        {
            $array = unserialize(file_get_contents($this->file));
            return ($key) ? Arr::path($array, $key) : $array;
        }
        else return nl2br(file_get_contents($this->file));
    }

    protected function _file($filename)
    {
        if(is_file($filename)) return $filename;
        else
        {
            $this->ext = ($this->ext) ? '.'.ltrim($this->ext, '.') : null;
            $filename = pathinfo($filename, PATHINFO_FILENAME);
            return $this->path.$this->dir.'/'.$filename.$this->ext;
        }
    }
}