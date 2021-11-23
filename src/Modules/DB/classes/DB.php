<?php
namespace WN\DB;

use Exception as GlobalException;
use WN\Core\Core;
use WN\Core\Exception;
use WN\DB\Lib\Render;

class DB
{
    const NAMED = 1;
    const POSITION = 2;
    const NOT_PREPARE = 3;

    const OBJ = 0;
    const ARR = 1;
    const PAIR = 2;
    const COUNT = 3;
    const ID = 4;

    public static $db_driver = 'mysql';
    public static $auto_create = true;
    public static $sqlite_path = '/src/App/data';
    public static $config_path = '/src/App/config/host/';

    public static $options = [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_NAMED,
    ];

    protected static $instance = [];

    public static $strict_default = false;
    public static $prepare_mode_default = 2;
    public static $level = 0;
    public static $prepare_mode;
    public static $strict;


    public $driver;
    public $pdo;
    public $class_driver;

    public static function instance($connect = null, $options = [])
    {
        if(!$connect || is_string($connect)) $connect = static::connect($connect);

        if(is_array($connect))
        {
            $options = array_replace(static::$options, $options);
            $pdo = new \PDO($connect['dsn'], $connect['username'], $connect['password'], $options);
        }
        elseif($connect instanceof \PDO) $pdo = $connect;

        $key = spl_object_hash($pdo);

        if(!isset(static::$instance[$key]))
            static::$instance[$key] = new static($pdo);

        return static::$instance[$key];
    }

    public static function connect($driver = null)
    {
        if(!$driver) $driver = static::$db_driver;

        if(stripos($driver, 'sqlite') === 0)
        {
            $path = $_SERVER['DOCUMENT_ROOT'].static::$sqlite_path;
            $dbname = ($dbname = substr($driver, 7)) ? $dbname : 'data';
            if(!is_dir($path)) mkdir($path, 0777, true);
            $connect['dsn'] = 'sqlite:'.$path.'/'.$dbname.'.sdb';
            $connect['username'] = null;
            $connect['password'] = null;
        }
        else
        {
            global $config_cache;
            if(isset($config_cache['connect'])) $connect = $config_cache['connect'][$driver];
            else
            {
                $config_path = $_SERVER['DOCUMENT_ROOT'].static::$config_path;
                if(!is_file(($config_file = $config_path.$_SERVER['SERVER_NAME'].'.php')))
                    $config_file = $config_path.'default.php';

                $config_cache = include $config_file;
                $connect = $config_cache['connect'][$driver];
            }
        }

        return $connect;
    }

    protected function __construct($pdo)
    {
        $this->pdo = $pdo;

        $this->driver = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $this->class_driver =  __NAMESPACE__.'\Lib\\'.ucfirst($this->driver);

        if($this->driver === 'sqlite')
        {
            $func = function($f){return mb_strtolower($f);};
            $this->pdo->sqliteCreateFunction('MB_LOWER', $func, 1);

            $this->pdo->exec("PRAGMA foreign_keys = ON");
        }
    }

    public static function expr($expr)
    {
        return new Expression(func_get_args());
    }

    public function create($name, $mode = 'TABLE')
    {
        return new Create($this, $name, $mode);
    }

    public function insert($name, $data = null)
    {
        return new Insert($this, $name, $data);
    }

    public function select()
    {
        $args = func_get_args();
        if(count($args) === 1 && is_array($args[0]))
            $args = $args[0];
            
        return new Select($this, $args);
    }

    public function union()
    {
        return new Union($this, false, func_get_args());
    }

    public function union_all()
    {
        return new Union($this, true, func_get_args());
    }

    public function update()
    {
        return new Update($this, func_get_args());
    }

    public function upsert($table)
    {
        return new Upsert($this, $table);
    }

    public function delete($table)
    {
        return new Delete($this, $table);
    }

    public function schema()
    {
        return new Schema($this);
    }

    public function table($table)
    {
        return Table::instance($table, $this);
    }

    public function eav($options = null)
    {
        return EAV::instance($options, $this);
    }

    public function tree($table)
    {
        return Tree::instance($table, $this);
    }

    public function etp($table)
    {
        return ETP::instance($table, $this);
    }

    public function truncate($table)
    {
        $sql = $this->class_driver::truncate($table);
        if(!$this->pdo->inTransaction()) $this->pdo->beginTransaction();
        $res = $this->pdo->exec($sql);
        if($this->pdo->inTransaction()) $this->pdo->commit();
        if($this->driver === 'sqlite') $this->pdo->exec("VACUUM");
        return $res;
    }

    public function drop($table)
    {
        $this->pdo->exec("DROP TABLE IF EXISTS $table");
    }

    public function exec()
    {
        $sql = '';
        foreach(func_get_args() AS $query)
        {
            $sql .= $query->render().';';
        }
        return $this->pdo->exec($sql);
    }

    public static function reset()
    {
        DB::$level = 0;
        DB::$strict = DB::$strict_default;
        DB::$prepare_mode = DB::$prepare_mode_default;
        Lib\Parser::$chr = 'a';
    }

    public function transaction()
    {
        foreach(func_get_args() AS $key => $arg)
        {
            if($arg instanceof Render)
            {
                $array[$key]['sth'] = $arg->prepare();
                $array[$key]['params'] = array_values($arg->params);
            }
        }

        try
        {
            if($this->pdo->inTransaction() === false)
                $this->pdo->beginTransaction();

            foreach($array AS $item)
            {
                if($item['sth'] instanceof \PDOStatement)
                    $result[] = $item['sth']->execute($item['params']);
            }

            if($this->pdo->inTransaction() === true)  $this->pdo->commit();
        }
        catch(\PDOException $e)
        {
            if($this->pdo->inTransaction() === true)
                $this->pdo->rollBack();
            
            if(Core::$errors) Exception\Handler::exceptionHandler($e);

            return false;
        }

        return $result;
    }
}