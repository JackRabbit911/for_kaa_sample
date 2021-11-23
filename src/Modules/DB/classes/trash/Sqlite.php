<?php
namespace WN\DB;

class Sqlite
{
    protected static $types = [
        'int'       => 'INTEGER',
        'integer'   => 'INTEGER',
        'varchar'   => 'TEXT',
        'string'    => 'TEXT',
    ];

    protected static $constraints = [
        'pk_ai' => 'NOT NULL PRIMARY KEY AUTOINCREMENT',
        'primary key'   => 'PRIMARY KEY',
        'primary'       => 'PRIMARY KEY',
        'autoincrement' => 'AUTOINCREMENT',
        'set null'      => 'ON DELETE SET NULL ON UPDATE SET NULL',
        'not null'      => 'NOT NULL',
        'null'          => 'NULL',
        'unique'        => 'UNIQUE',
        'default'       => 'DEFAULT',
        'check'         => 'CHECK',
        'index'         => 'INDEX',
        // 'charset'       => 'CHARACTER SET',
        // 'collate'       => 'COLLATE',
        'foreign key'   => 'FOREIGN KEY',
        'references'    => 'REFERENCES',
        'cascade'       => 'ON DELETE CASCADE ON UPDATE CASCADE',
    ];

    public static function column($str)
    {
        $pattern = ['/[\(\d\)]/', '/^ +| +$|( ) +/m'];
        $str = preg_replace($pattern, ['', ' '], $str);

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

    public static function create_index($table, $str)
    {
        $pattern = '/[(](.+?)[)]/';
        if(preg_match($pattern, $str, $matches))
        {
            $column = $matches[1];
            $index_name = 'ix'.trim($column, '`');
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

    public static function upsert($eol, $table, $columns, $placeholders, $data, $conflict)
    {
        // if(empty($pk)) $pk = static::pk($pdo, $table);

        if($conflict === false) $update_sql = '';
        else
        {
            // $conflict = implode(', ', $pk);
            $update_sql = $eol."ON CONFLICT($conflict) DO UPDATE SET $data";
        }

        return "INSERT INTO $table ($columns)".$eol."VALUES ($placeholders)".$update_sql;
    }

    public static function conflict($pdo, $table)
    {
        $table_info = $pdo->query("PRAGMA TABLE_INFO ($table)")->fetchAll();
        foreach($table_info as $column) if($column['pk'] == 1) $res[] = Lib::escape($column['name']);

        if(!isset($res))
        {
            $indexes = $pdo->query("PRAGMA INDEX_LIST ($table)")->fetchAll();

            foreach($indexes as $index)
            {
                if($index['unique'] != 0)
                {
                    $concat_key = $pdo->query("PRAGMA INDEX_INFO (".$index['name'].")")->fetchAll();
                    foreach($concat_key as $in) $res[] = Lib::escape($in['name']);
                }
            }
        }
        
        return $res ?? false;
    }
}