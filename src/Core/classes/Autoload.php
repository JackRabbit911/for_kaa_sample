<?php
/**
 * Class Autoload
 *
 * loads classes using PSR-4 standard
 */
namespace WN\Core;

use WN\Core\Exception\WnException;

class Autoload
{
    /**
     * array of the modules to create paths map
     * @var array
     */
    public static $modules = [];

    public static $classes_folder = 'classes';
    public static $modules_folder = 'modules';
    public static $root_folder = 'src';
    public static $namespace_prefix = 'WN\\';
    public static $is_remove_modules_folder = true;
    public static $is_include_submodules = false;
    // public static $is_include_subdomains = true;
    // public static $is_modules_merge = FALSE;

    public static $mod_paths = [];
    public static $class_paths = [];

    public static function add($path, $namespace = null, $include_submodules = null)
    {
        if($include_submodules !== null) static::$is_include_submodules = $include_submodules;

        if(is_array($path))
        {
            foreach($path as $module => $namespace)
            {
                static::add($module, $namespace, static::$is_include_submodules);
            }
        }
        else
        {
            $path = static::get_realpath($path);
           
            if(basename($path) === static::$root_folder)
            {
                static::add(array_fill_keys( static::modules($path), null));
            }
            else
            {
                $module = preg_replace('/^(.*?'.static::$root_folder.'[\\\\|\/])/i', '', $path);

                if($namespace === null) $namespace = static::get_namespace($module);
                elseif($namespace === false) $namespace = '';

                if($namespace === FALSE) $namespace = '';

                $class_folder = $path.'/'.static::$classes_folder;

                if(is_dir($class_folder))
                {
                    if($namespace === NULL) $namespace = static::get_namespace($path);

                    if(!array_key_exists($class_folder, static::$class_paths))
                        static::$class_paths[$class_folder] = $namespace; 
                }
                elseif($namespace !== null && strpos($namespace, static::$namespace_prefix) !== 0)
                {
                    if(!array_key_exists($path, static::$class_paths))
                        static::$class_paths[$path] = $namespace;
                }
                
                if(!in_array($path, static::$mod_paths) && strpos($namespace, static::$namespace_prefix) === 0)
                    static::$mod_paths[] = $path;
                
                $module_folder = $path.'/'.static::$modules_folder;

                if(is_dir($module_folder) && static::$is_include_submodules === true)
                {
                    static::add(array_fill_keys( static::modules($module_folder), null));
                }
            }
        }

        uasort(static::$class_paths, 'static::_compare');
        uasort(static::$mod_paths, 'static::_compare');
    }

    protected static function _compare($a, $b)
    {
        if(stripos($a, 'Core') !== false) return 1;
        else return 0;
    }

    public static function modules($path, $mask = '/*')
    {
        $modules = [];

        $realpath = static::get_realpath($path);        

        $dir = glob($realpath.$mask, GLOB_ONLYDIR);

        foreach($dir as $item)
        {
            $basename = basename($item);
            $module = strtolower($basename);
            
            if(preg_match('/^[A-Z]{1}/', $basename) && $module != static::$modules_folder)
            {
                $modules[] = $item;

                if(static::$is_include_submodules === true)
                    $modules = array_merge($modules, static::modules($item, '/'.static::$modules_folder.'/*'));
            }

            if($module == static::$modules_folder)
                $modules = array_merge($modules, static::modules($item));
        }

        return $modules;
    }

    public static function get_realpath($path)
    {
        $realpath = realpath($path);

        if(!$realpath)
            $realpath = realpath('/'.static::$root_folder.'/'.$path);

        if(!$realpath) $realpath = SRCPATH.$path;

        // echo realpath(static::$root_folder), '<br>';
        // var_dump($path);
        // exit;

        if(!$realpath) //die('Invalid path: '.$path);
        // {
        //     var_dump(static::$root_folder.'/'.$path, __DIR__, SRCPATH); exit;
        // }
            throw new WnException('Invalid path: :path', [':path'=>$path]);
        else $realpath = str_replace(DIRECTORY_SEPARATOR, '/', $realpath);

        // var_dump($realpath);
        // exit;

        return $realpath;
    }

    /**
     * looking for a class to load iterating over all the paths
     *
     * @param $class
     * @return bool
     */
    public static function loadClass($class)
    {
        foreach(static::$class_paths AS $path => $namespace)
        {
            $return = static::_find_class($class, $path.'/', $namespace);
            if($return === TRUE) return TRUE;
        }
    }

    /**
     * Looking the class to require in the path
     *
     * @param $class
     * @param $paths
     * @param null $prefix
     * @param null $suffix
     * @return bool
     */
    protected static function _find_class($class, $path, $namespace)
    {
        $namespace = preg_quote($namespace);
        $pattern = ["/^($namespace)/", '/^(\\'.DIRECTORY_SEPARATOR.')/', "/(\\\\)/"];
        $replace = ["", "", "/"];

        $filename = preg_replace($pattern, $replace, $class);

        $file = $path.$filename.'.php';

        if(is_file($file))
        {
            require_once $file;
            return TRUE;
        }
    }

    protected static function get_namespace($module)
    {
        $namespace = ucwords($module, '/\\');

        $namespace = str_replace('/', '\\', $namespace);
        

        if(static::$is_remove_modules_folder)
            $namespace = preg_replace('/('.static::$modules_folder.'[\\\\|\/])/i', '', $namespace);

        return static::$namespace_prefix.$namespace;
    }
}