<?php

namespace WN\Core\Model\Session;

use WN\Core\{Config, Session};

class DB
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

    public static $upsert_mysql = 
        "INSERT INTO `sessions` VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `id` = ?, `last_activity` = ?, `is_long` = ?, `user_id` = ?, `data` = ?";

    public static $upsert_sqlite = 
        "INSERT INTO `sessions` VALUES (?, ?, ?, ?, ?) ON CONFLICT(`id`) DO UPDATE SET `id` = ?, `last_activity` = ?, `is_long` = ?, `user_id` = ?, `data` = ?";

    protected static $pdo;

    public static function create_table($driver)
    {
        static::set_pdo($driver);

        $var = 'create_'.$driver;
        $sql = static::$$var;

        static::$pdo->exec($sql);
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

    public static function get($id)
    {
        $sth = static::$pdo->prepare(static::$sql_select);
        $sth->execute([$id]);
        $data = $sth->fetch();

        $lifetime = ($data['is_long']) ? Session::$long : Session::$short;
        if($data['last_activity'] < time() - $lifetime)
        {
            $sql = "DELETE FROM `sessions` WHERE `id` = ?";
            $sth = static::$pdo->prepare($sql);
            $sth->execute([$id]);
            $data = [];
        }

        if(isset($data['data']))
            $data['data'] = (array) json_decode($data['data']);

        return ($data) ? $data : [];
    }

    public static function set($data)
    {
        $last_activity = time();
        $data['data'] = json_encode($data['data']);

        $plhs[] = $data['old_id'];
        $plhs[] = $last_activity;
        $plhs[] = $data['is_long'];
        $plhs[] = $data['user_id'];
        $plhs[] = $data['data'];

        $plhs[] = $data['id'];
        $plhs[] = $last_activity;
        $plhs[] = $data['is_long'];
        $plhs[] = $data['user_id'];
        $plhs[] = $data['data'];

        static::upsert($plhs);
    }

    public static function delete($id, $old_id, $user_id, $mode = 0)
    {
        $sql = "DELETE FROM `sessions` WHERE ";

        if($mode === 0)
        {
            $sql .= "`id`=? OR `id`=?";

            $sth = static::$pdo->prepare($sql);
            $sth->execute([$id, $old_id]);
        }
        elseif($mode === 1)
        {
            $sql .= "`user_id`=?";
            $sth = static::$pdo->prepare($sql);
            $sth->execute([$user_id]);
        }
        elseif($mode === 2)
        {
            $sql .= "`user_id`=? AND (`id`<>? OR `id`<>?";
            $sth = static::$pdo->prepare($sql);
            $sth->execute([$user_id, $id, $old_id]);
        }

        return $sth->rowCount();
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

    protected static function upsert($plhs)
    {
        $driver = static::$pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $var = 'upsert_'.$driver;
        $sql = static::$$var;

        $sth = static::$pdo->prepare($sql);
        $sth->execute($plhs);
    }
}