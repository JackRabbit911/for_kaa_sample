<?php
namespace WN\DB\Lib;

use WN\DB\Create;

class Sqlite
{
    protected static $types = [
        'int'       => 'INTEGER',
        'integer'   => 'INTEGER',
        'varchar'   => 'TEXT',
        'string'    => 'TEXT',
        'text'      => 'TEXT',
    ];

    protected static $constraints = [
        'pk_ai' => 'NOT NULL PRIMARY KEY AUTOINCREMENT',
        'primary key'   => 'PRIMARY KEY',
        'primary'       => 'PRIMARY KEY',
        'autoincrement' => 'AUTOINCREMENT',
        'ai'            => 'AUTOINCREMENT',
        'set null'      => 'ON DELETE SET NULL ON UPDATE SET NULL',
        'not null'      => 'NOT NULL',
        'null'          => 'NULL',
        'unique'        => 'UNIQUE',
        'default'       => 'DEFAULT',
        'check'         => 'CHECK',
        'index'         => 'INDEX',
        // 'charset'       => 'CHARACTER SET',
        'collate'       => 'COLLATE',
        'foreign key'   => 'FOREIGN KEY',
        'fk'            => 'FOREIGN KEY',
        'references'    => 'REFERENCES',
        'rf'            => 'REFERENCES',
        'cascade'       => 'ON DELETE CASCADE ON UPDATE CASCADE',
        'latin1'       => 'binary',
    ];

    public static function column($str)
    {
        // remove "(255)", double spaces and charset clause
        $pattern = ['/\(\d+\)/', '/^\s+|\s+$|\s{2,}/m', '/charset(\s\w+\s)/'];
        $str = preg_replace($pattern, ['', ' '], $str);

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

    public static function create_index($table, $str)
    {
        $pattern = '/[(](.+?)[)]/';
        if(preg_match($pattern, $str, $matches))
        {
            $column = Parser::escape($matches[1]);
            $index_name = 'ix'.trim($column, '`').'_'.trim($table, '`').'_'.uniqid();
            $table = Parser::escape($table);
            return "CREATE INDEX IF NOT EXISTS $index_name ON $table ($column)";
        }
        
    }

    public static function collate($collate)
    {
        return null;
    }

    public static function charset($charset)
    {
        return null;
    }

    public static function engine($engine)
    {
        return null;
    }

    public static function upsert($columns, $pdo, $table)
    {
        $key = static::conflict($pdo, $table);

        if($key)
        {
            $str = "ON CONFLICT($key) DO UPDATE SET ";

            foreach($columns as $column)
                $str .= ' '.$column.' = VALUES('.$column.')';

            return $str;
        }
    }

    // public static function upsert($eol, $table, $columns, $placeholders, $data, $conflict)
    // {
    //     if($conflict === false) $update_sql = '';
    //     else
    //     {
    //         $update_sql = $eol."ON CONFLICT($conflict) DO UPDATE SET $data";
    //     }

    //     return "INSERT INTO $table ($columns)".$eol."VALUES ($placeholders)".$update_sql;
    // }

    public static function conflict($pdo, $table)
    {
        $table_info = $pdo->query("PRAGMA TABLE_INFO ($table)")->fetchAll();
        foreach($table_info as $column) if($column['pk'] == 1) $res[] = Parser::escape($column['name']);

        if(!isset($res))
        {
            $indexes = $pdo->query("PRAGMA INDEX_LIST ($table)")->fetchAll();

            foreach($indexes as $index)
            {
                if($index['unique'] != 0)
                {
                    $concat_key = $pdo->query("PRAGMA INDEX_INFO (".$index['name'].")")->fetchAll();
                    foreach($concat_key as $in) $res[] = Parser::escape($in['name']);
                }
            }
        }
        
        return $res ?? false;
    }

    public static function truncate($table)
    {
        return "DELETE FROM $table; DELETE FROM sqlite_sequence WHERE name = '$table';";
    }

    public static function not_null()
    {
        return 'NOT';
    }

    public static function like($create)
    {
        $sql = $create->db->pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name = '$create->like'")->fetch();
        $create_table = strtr($sql['sql'], [$create->like => trim($create->name, '`')]);

        $res = [];
        $indexes = $create->db->pdo->query("PRAGMA INDEX_LIST ($create->like)")->fetchAll();
        foreach($indexes AS $index)
        {
            if($index['origin'] === 'c')
            {
                $index_info = $create->db->pdo->query("PRAGMA INDEX_INFO (".$index['name'].")")->fetchAll();
                foreach($index_info as $in) $res[] = static::create_index($create->name, 'index ('.$in['name'].')');
            }
        }

        $create_index = implode(';'.$create->eol, $res);

        return "$create_table;$create->eol$create_index";
    }

    public static function columns($pdo, $table, $column = null)
    {
        $sql = "PRAGMA TABLE_INFO ($table)";
        $array = $pdo->query($sql)->fetchAll();

        $res = [];
        foreach($array AS $item)
            $res[$item['name']] = $item['type'];

        if($column) return (array_key_exists($column, $res)) ? true : false;
        else return $res;
    }

    public static function tables()
    {
        return 'SELECT name FROM sqlite_master WHERE type="table" AND tbl_name != "sqlite_sequence" ORDER BY name';
    }

    public static function indexes($pdo, $table)
    {
        // $indexes = $pdo->query("PRAGMA INDEX_LIST ($table)")->fetchAll();
        // foreach($indexes AS $index)
        // {
        //     $res[] = $pdo->query("PRAGMA INDEX_INFO (".$index['name'].")")->fetchAll();
        // }

        // return $res;
    }
}