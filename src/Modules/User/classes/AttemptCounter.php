<?php

namespace Wn\User;

use WN\Core\Helper\{HTTP, Validation as Valid};
use WN\User\{ModelUser, User};
use WN\Core\Session;

class AttemptCounter
{
    public static $db;
    public static $max = 3;
    public static $wait_time = 30;
    public static $table_name = 'attempts';

    public static $wait;
    public static $count;
    public static $regenerate = false;

    public static $tables_options = [
        'columns' => [
            'sid varchar(64) primary key',
            'email varchar(128)',
            'phone varchar(11)',
            'nickname varchar(256)',
            'last_activity int(10)',
            'time int(10)',
            'count int(2)',
        ],
        'index' => [
            // 'unique (sid)',
            'unique (email)',
            'unique (phone)',
            'unique (email, phone, nickname)',
        ],
        'collate' => ['latin1_bin', 'latin1', 'InnoDB'],
    ];

    public static $row;

    

    public static function create_table($options = null)
    {
        if(!$options) $options = static::$tables_options;
        $options['name'] = static::$table_name;
        static::$db->create($options)->exec();
    }

    public static function count($value, $max, $wait)
    {
        if(!$max) return [null, null];

        $session = Session::instance();
        $sid = $session->id;

        if(Valid::phone($value)) $value = ModelUser::filter_santize_phone($value);

        // $res = true;
        $w = null;
        static::delete_by_time($wait);
        list($time, $count) = static::get($sid, $value);

        if($count === null && $time === null)
        {
            $count = $max;
            static::insert($sid, $value, --$count);
        }
        elseif($time === null)
        {
            static::update($sid, $value, --$count, $time);
            // $w = $wait;
        }
        else
        {
            $w = $wait - (time() - $time);
            // if($w > 0) $res = $w;
            // else $res = true;
        }

        return [(int) $count, $w];
        // return $res;
    }

    public static function delete_by_time($wait)
    {
        $time = time() - $wait;
        $lifetime = $time + $wait - Session::$short;

        static::$db->delete(static::$table_name)
            ->where('time', '<', $time)
            ->or_where('last_activity', '<', $lifetime)
            ->execute();
    }

    public static function delete($sid, $userdata)
    {
        $delete = static::$db->delete(static::$table_name)
            ->where('sid', $sid);

        if(isset($userdata['email']))
            $delete->or_where('email', $userdata['email']);

        if(isset($userdata['phone']))
            $delete->or_where('phone', $userdata['phone']);
            
        $delete->execute();
    }

    public static function delete_by_phone($phone)
    {
        return static::$db->delete(static::$table_name)
            ->where('phone', $phone)
            ->execute();
    }

    protected static function insert($sid, $value, $count)
    {
        // User::model_instance();
        $user = ModelUser::instance()->get_userdata($value);

        if($user === false)
        {
            $column = static::detect_column($value);
            $data[$column] = $value;
        }
        
        $data['nickname'] = $user->nickname ?? null;
        $data['email'] = $user->email ?? null;
        $data['phone'] = $user->phone ?? null;
        $data['sid'] = $sid;
        $data['count'] = $count;
        $data['last_activity'] = time();

        return static::$db->insert(static::$table_name)->set($data)->execute();
    }

    protected static function update($sid, $value, $count, $time)
    {
        $column = static::detect_column($value);

        if($count === 0 && $time === null) $data['time'] = time();
        
        $data['count'] = $count;
        $userdata['last_activity'] = time();

        // var_dump($column);
        if(!$time)
            static::$db->update(static::$table_name)->set($data)
                    ->where('sid', $sid)
                    ->or_where($column, $value)->execute();
    }

    protected static function get($sid, $value)
    {
        $email = filter_var($value, FILTER_SANITIZE_EMAIL, FILTER_FLAG_EMAIL_UNICODE);
        $phone = ModelUser::filter_santize_phone($value);
        $name = filter_var($value, FILTER_SANITIZE_STRING);

        $select = static::$db->select('time', 'count')->from(static::$table_name)
                    ->where('sid', $sid)
                    ->or_where('email', $email)
                    ->or_where('nickname', $name);

        if(!empty($phone)) $select->or_where('phone', $phone);

        $sth = $select->limit(1)->execute();

        $sth->setFetchMode(\PDO::FETCH_NUM);

        return $sth->fetch();
    }

    protected static function detect_column($value)
    {
        if(filter_var($value, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE))
            return 'email';
        elseif(preg_match('/^[\+\s\d\-()]{3,20}$/', $value))
        {
            return 'phone';
        }
        else return 'nickname';
    }

    // public static function gd()
    // {
    //     $time = time() - static::$wait_time;
    //     static::$db->delete(static::$table_name)->where('time', '<', $time)->execute();
    // }
}