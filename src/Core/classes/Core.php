<?php

namespace WN\Core;

// use WN\Core\{Config, Session};
use WN\Core\Helper\{HTTP, Accept};
use WN\Core\Model\Data\File;
use WN\Core\Exception\WnException;

class Core
{
    use Pattern\Singletone;

    const RELATIVE = 1;

    // public static $settings = false;
    public static $cache = true;
    public static $errors = false;
    public static $cache_lifetime = 10;
    public static $cahe_enable_query = true;

    public static $db_driver = 'mysql';
    public static $sqlite_path = APPPATH.'data';

    public static $pathmap = [];

    public static $index_file = 'index.php';
    public static $charset = 'UTF-8';

    protected static $env = 10;

    protected static $pdo = [];

    public static function paths($relative = 0)
    {
        if($relative === 0)
            return Autoload::$mod_paths;
        else
        {
            $paths = [];

            foreach(Autoload::$mod_paths as $abs_path)
                $paths[] = ltrim(str_replace($_SERVER['DOCUMENT_ROOT'], '', $abs_path), '/');

            return $paths;
        }
    }

    public static function bootstrap()
    {
        if(static::enviroment() > TESTING)
        {
            static::$errors = TRUE;
            static::$cache = FALSE;
        }
        elseif(static::enviroment() > STAGING)
        {
            static::$errors = TRUE;
            static::$cache = TRUE;
        }

        if(static::$errors === TRUE)
        {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        }
        else
        {
            error_reporting(0);
            ini_set('display_errors', 0);
            Exception\Logger::$is_log = true;
        }
    }

    public static function enviroment(int $enviroment = NULL)
    {
        if($enviroment === NULL) return static::$env;
        else static::$env = $enviroment;
    }

    public static function find_file($name, $array=FALSE)
    {
        if(is_file($name)) return $name;

        $ext = pathinfo($name, PATHINFO_EXTENSION);
        if(!$ext) $name .= '.php';
        
        $name = str_replace(DIRECTORY_SEPARATOR, '/', $name);
        $name = trim($name, '/');

        $found = [];

        foreach(static::paths() AS $path)
        {
            $file = $path.'/'.$name;

            if(is_file($file))
            {
                if($array) $found[] = $file;
                else return $file;
            }
        }

        return ($array) ? $found : FALSE;
    }

    public static function cache($file, $value = null, $lifetime = 0)
    {
        if((!static::$cahe_enable_query && !empty($_GET)) || !empty($_GET[Session::$cookie_name])) return;
        
        $model = new File($file, ['dir' => 'cache', 'lifetime' => static::$cache_lifetime, 'return_default' => false]);

        if($value === null) return $model->get();
        elseif($lifetime > 0) $model->set($value, $lifetime);
    }

    protected function __construct()
    {
        /** develop mode */
        // static::$errors = TRUE;
        // error_reporting(E_ALL);
        // ini_set('display_errors', 1);
        /*****************/
        
        define('PRODUCTION', 10);
        define('STAGING', 20);
        define('TESTING', 30);
        define('DEVELOPMENT', 40);
        define('BASEDIR', str_replace(static::$index_file, '', $_SERVER['SCRIPT_NAME']));
        
        // ini_set('url_rewriter.tags', "a=href,area=href,frame=src,form=,fieldset=");

        register_shutdown_function([$this, 'shutdown'], getcwd());
        register_shutdown_function(['WN\Core\Exception\Handler', 'shutdownHandler'], getcwd());       
        set_error_handler(['WN\Core\Exception\Handler', 'errorHandler']);
        set_exception_handler(['WN\Core\Exception\Handler', 'exceptionHandler']);

        $config = Config::instance();

        // Set eviroment var depends of config host/domain settings
        static::enviroment($config->get('host/'.DOMAIN, 'env', Config::BOOT));

        // define behavior depending on the environment variable
        static::bootstrap();
        
        // include_once static::find_file('init.php');

        // for($i = 0, $inits = static::find_file('init', TRUE);  $i < count($inits); $i++)
        foreach(static::find_file('init', TRUE) as $init)
        {
            include_once $init;
        }
    }

    public function execute()
    {      
        static::$cache = false; // temporary
        // static::$errors = false;

        $uri = HTTP::detect_uri();

        if(static::$cache && ($output = static::cache(md5($uri))) !== false) {}
        else
        {
            $request = Request::factory($uri);
            $output = $request->execute();
            if(static::$cache) static::cache(md5($uri), $output, $request->response->server_cache_lifetime);
        }

        // Session::instance()->save();
        // global $session;
        // $session->save();

        $ob_handlers = ob_list_handlers();
        if(end($ob_handlers) === 'URL-Rewriter') ob_flush();

        echo $output;


        // echo ob_get_clean();
    }

    public function shutdown($absdir)
    {
        // Session::instance()->save();
        // $ob_handlers = ob_list_handlers(); exit;
        // var_dump($ob_handlers);
        // if(end($ob_handlers) === 'URL-Rewriter') ob_flush();

        // echo ob_get_clean();

        chdir($absdir);
        $files = static::find_file('finally.php', true);
        foreach($files as $file)
            include_once $file;
    }
}