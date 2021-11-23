<?php
namespace WN\DB;

class Mysql
{
    protected static $types = [
        'int'       => 'INT',
        'integer'   => 'INT',
        'varchar'   => 'VARCHAR',
        'string'    => 'VARCHAR',
    ];

    protected static $constraints = [
        'pk_ai' => 'NOT NULL PRIMARY KEY AUTO_INCREMENT',
        'primary key'   => 'PRIMARY KEY',
        'primary'       => 'PRIMARY KEY',
        'autoincrement' => 'AUTO_INCREMENT',
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
        'references'    => 'REFERENCES',
        'cascade'       => 'ON DELETE CASCADE ON UPDATE CASCADE',
    ];

    public static function column($str)
    {
        $arr = explode(' ', $str, 3);

        if(isset($arr[1]))
        {
            $search = array_keys(static::$types);
            $replace = array_values(static::$types);
            $arr[1] = str_replace($search, $replace, $arr[1]);

            if(isset($arr[2]))
            {
                $search = array_keys(static::$constraints);
                $replace = array_values(static::$constraints);
                $arr[2] = str_replace($search, $replace, $arr[2]);
            }
        }

        return implode(' ', $arr);
    }

    public static function constraint($str)
    {
        $str = Create::replace_outside_chars($str, static::$constraints, '(', ')');
        // $str = strtr($str, static::$constraints);
        return $str;
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

    public static function upsert($eol, $table, $columns, $placeholders, $col_plh)
    {
        return "INSERT INTO `$table`($columns)".$eol."VALUES ($placeholders)".$eol."ON DUPLICATE KEY UPDATE $col_plh";
    }
}