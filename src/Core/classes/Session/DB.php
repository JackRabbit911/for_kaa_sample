<?php
namespace WN\Core\Session;

use WN\Core\{Config, Session};

class DB// implements Sessionable
{
    public static $options = [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_NAMED,
    ];

    public static $sqlite_path = APPPATH.'data/';

    public static $create_sqlite = 
        "CREATE TABLE IF NOT EXISTS `sessions` (
            `id` TEXT NOT NULL PRIMARY KEY,
            `last_activity` INTEGER,
            `is_long` BOOLEAN,
            `user_id` INTEGER,
            `data` TEXT);
        CREATE INDEX IF NOT EXISTS ixla ON `sessions` (`last_activity`)";

    public static $create_mysql = 
        "CREATE TABLE IF NOT EXISTS `sessions` (
            `id` VARCHAR(64) NOT NULL PRIMARY KEY,
            `last_activity` INT(10),
            `is_long` BOOLEAN,
            `user_id` INT(11),
            `data` TEXT,
            INDEX ixla (`last_activity`))";

    public static $sql_select = 
        "SELECT * FROM `sessions` WHERE `id`= ? LIMIT 1";

    public static $sql_update = 
        "UPDATE `sessions` SET `id` = ?, `last_activity` = ?, `is_long` = ?, `user_id` = ?, `data` = ? 
        WHERE `id` =  ?";
    
    public static $insert_mysql = 
        "INSERT IGNORE INTO `sessions` VALUES (?, ?, ?, ?, ?)";

    public static $insert_sqlite = 
        "INSERT OR IGNORE INTO `sessions` VALUES (?, ?, ?, ?, ?)";

    protected static $pdo;

    public static function create_table($driver)
    {
        static::set_pdo($driver);

        $var = 'create_'.$driver;
        $sql = static::$$var;

        static::$pdo->exec($sql);
    }

    public static function find($id)
    {
        // var_dump(static::$pdo->getAttribute(\PDO::ATTR_DRIVER_NAME));

        $sth = static::$pdo->prepare(static::$sql_select);
        $sth->execute([$id]);
        $data = $sth->fetch();

        if(isset($data))
        {
            $lifetime = ($data['is_long']) ? Session::$long : Session::$short;
            if($data['last_activity'] < time() - $lifetime)
            {
                $sql = "DELETE FROM `sessions` WHERE `id` = ?";
                $sth = static::$pdo->prepare($sql);
                $sth->execute([$id]);
                return false;
            }

            $data['data'] = (array) json_decode($data['data']);
            return $data;
        }
        else return false;
    }

    public static function save($data, $old_sid = null)
    {
        $plhs[] = $data['id'];
        $plhs[] = time();
        $plhs[] = $data['is_long'];
        $plhs[] = $data['user_id']; // ?? 0;
        $plhs[] = json_encode($data['data']);

        // var_dump($old_sid); exit;

        if($old_sid && ($count = static::update($plhs, $old_sid) > 0)) {}
        else $count = static::insert($plhs);

        return $count;
    }

    protected static function insert($plhs)
    {
        $driver = static::$pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $var = 'insert_'.$driver;
        $sql = static::$$var;

        $sth = static::$pdo->prepare($sql);
        $sth->execute($plhs);

        return static::$pdo->lastInsertId();
    }

    protected static Function update($plhs, $old_sid)
    {
        $plhs[] = $old_sid;
        $sth = static::$pdo->prepare(static::$sql_update);
        $sth->execute($plhs);

        return $sth->rowCount();
    }

    public static function delete($sid, $user_id, $mode = 0)
    {
        $sql = "DELETE FROM `sessions` WHERE ";

        if($mode === 0)
        {
            $sql .= "`id`=?";

            $sth = static::$pdo->prepare($sql);
            $sth->execute([$sid]);
            $count = $sth->rowCount();

            return $count;
        }
        elseif($mode === 1)
        {
            $sql .= "user_id=?";
            $sth = static::$pdo->prepare($sql);
            $sth->execute([$user_id]);
            return $sth->rowCount();
        }
        elseif($mode === 2)
        {
            $sql .= "user_id=? AND id<>?";
            $sth = static::$pdo->prepare($sql);
            $sth->execute([$user_id, $sid]);
            return $sth->rowCount();
        }
    }

    public static function online($lifetime)
    {
        $sql = "SELECT COUNT(`id`) AS 'all', 
                (SELECT COUNT(`id`) FROM `sessions` WHERE last_activity>? AND user_id > 0) AS users, 
                (SELECT COUNT(`id`) FROM `sessions` WHERE last_activity>? AND user_id IS NULL) AS guests
                FROM `sessions` WHERE last_activity > ?";

        $time = time() - $lifetime;
        $sth = static::$pdo->prepare($sql);
        $sth->execute([$time, $time, $time]);

        return $sth->fetchAll();
    }

    public static function gd($lifetime, $is_long = false)
    {
        $suffix = (!$is_long) ? ' AND `is_long` <> 1' : null;

        $sql = "DELETE FROM `sessions` WHERE `last_activity` < ?$suffix";

        $sth = static::$pdo->prepare($sql);
        $sth->execute([time()-$lifetime]);

        return $sth->rowCount();
    }

    public static function get_last($user_id)
    {
        $sql = "SELECT `data` FROM `sessions` WHERE `user_id`=? AND last_activity=
                (SELECT MAX(last_activity) FROM `sessions` WHERE `user_id`=?)";

        $sth = static::$pdo->prepare($sql);
        $sth->execute([$user_id, $user_id]);

        $res = $sth->fetch();

        return (array) json_decode($res['data']);
    }

    public static function set_pdo($driver)
    {
        if(isset(static::$pdo)) return;

        if($driver === 'sqlite')
        {
            if(!is_dir(static::$sqlite_path)) mkdir(static::$sqlite_path, 0777, true);
            $connect['dsn'] = 'sqlite:'.static::$sqlite_path.'session.sdb';
            $connect['username'] = null;
            $connect['password'] = null;
        }
        else $connect = Config::instance()->connect[$driver];
            
        static::$pdo = new \PDO($connect['dsn'], $connect['username'], $connect['password'], static::$options);  
    }
}