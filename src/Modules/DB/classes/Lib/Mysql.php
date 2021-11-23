<?php
namespace WN\DB\Lib;

use WN\DB\Create;

class Mysql
{
    protected static $types = [
        'int'       => 'INT',
        'integer'   => 'INT',
        'varchar'   => 'VARCHAR',
        'string'    => 'VARCHAR',
        'text'      => 'TEXT',
    ];

    protected static $constraints = [
        'pk_ai' => 'NOT NULL PRIMARY KEY AUTO_INCREMENT',
        'primary key'   => 'PRIMARY KEY',
        'primary'       => 'PRIMARY KEY',
        'autoincrement' => 'AUTO_INCREMENT',
        'ai'            => 'AUTO_INCREMENT',
        'set null'      => 'ON DELETE SET NULL ON UPDATE SET NULL',
        'not null'      => 'NOT NULL',
        'null'          => 'NULL',
        'unique'        => 'UNIQUE',
        'default'       => 'DEFAULT',
        'check'         => 'CHECK',
        'index'         => 'INDEX',
        'charset'       => 'CHARACTER SET',
        'collate'       => 'COLLATE',
        'foreign key'   => 'FOREIGN KEY',
        'fk'            => 'FOREIGN KEY',
        'references'    => 'REFERENCES',
        'rf'            => 'REFERENCES',
        'cascade'       => 'ON DELETE CASCADE ON UPDATE CASCADE',
        'collate latin1'=> 'COLLATE latin1_bin',
        // 'binary'        => 'latin1',
    ];

    public static function column($str)
    {
        $arr = explode(' ', $str, 3);

        $arr[0] = Parser::escape($arr[0]);

        if(isset($arr[1]))
        {
            $arr[1] = strtr($arr[1], static::$types);

            if(isset($arr[2]))
            {
                $arr[2] = strtr($arr[2], static::$constraints);
            }
        }
        return $arr;
    }

    public static function constraint($str)
    {
        return Create::replace_outside_chars($str, static::$constraints, '(', ')');
    }

    public static function collate($collate)
    {
        return 'COLLATE '.$collate;
    }

    public static function charset($charset)
    {
        return 'DEFAULT CHARSET='.$charset;
    }

    public static function engine($engine)
    {
        return 'ENGINE='.$engine;
    }

    public static function upsert($columns)
    {
        $str = 'ON DUPLICATE KEY UPDATE ';

        foreach($columns as $column)
        {
            $arr[] = $column.' = VALUES('.$column.')';
        }

        return $str.implode(', ', $arr);
    }

    // public static function upsert($eol, $table, $columns, $placeholders, $col_plh)
    // {
    //     return "INSERT INTO `$table`($columns)".$eol."VALUES ($placeholders)".$eol."ON DUPLICATE KEY UPDATE $col_plh";

    //     return $eol."ON DUPLICATE KEY UPDATE $col_plh";
    // }

    public static function truncate($pdo, $table)
    {
        return "TRUNCATE TABLE $table";
    }

    public static function not_null()
    {
        return 'IS NOT';
    }

    public static function like($create)
    {
        return "CREATE $create->mode IF NOT EXISTS $create->name$create->eol LIKE `$create->like`";
    }

    public static function columns($pdo, $table, $column = null)
    {
        $sql = "SHOW COLUMNS FROM $table";
        $array = $pdo->query($sql)->fetchAll();
        $res = [];

        foreach($array AS $item)
            $res[$item['Field']] = $item['Type'];

        if($column) return (array_key_exists($column, $res)) ? true : false;
        else return $res;
    }

    public static function tables()
    {
        return "SHOW TABLES";
    }

    public static function indexes($pdo, $table)
    {
        // return $pdo->query("SHOW INDEX FROM $table")->fetchAll();

        // return $pdo->query("SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE  table_name ='$table'")->fetchAll();
    }
}