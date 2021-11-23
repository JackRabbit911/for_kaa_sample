<?php
namespace WN\UpFile;

use WN\Core\Pattern\Entity;
use WN\Core\Helper\{UTF8, File, HTML, Text};
use WN\Core\Route;

class UpFile extends Entity
{
    public static $model;

    public static function upload($file, $dir = null)
    {
        $obj = new static(null, static::$settings);
        $obj->data = static::$model->upload($file, $dir);
        return $obj;
    }

    public static function clean()
    {
        static::model_instance();

        return call_user_func_array([static::$model, 'orphan'], func_get_args());
        // return static::$model->clean_db();
    }

    public function download($title = null)
    {
        return HTML::download_anchor($this->src(), $title);
    }

    public function src()
    {
        $id = $this->id ?? 0;
        $file = $this->data['orig_name'] ?? 'file_not_found.ext';

        $route_name = strtolower(Text::class_basename(get_called_class()));
        
        $route = Route::get($route_name);
        $uri = $route->uri([
            'id'    => $id,
            'file'  => static::name2ascii($file),
            ]);

        return $uri;
    }

    public function type($type = null)
    {
        $ftype = File::type_by_mime($this->mime);
        return ($type) ? $ftype == $type : $ftype;
    }

    public function is_file()
    {
        return (is_file($this->filename)) ? true : false;
    }

    protected static function name2ascii($name)
    {
        $name = UTF8::transliterate_to_ascii($name);
        $name = strtolower(preg_replace('/\s+/', '-', $name));
        return $name;
    }
}