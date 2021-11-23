<?php

namespace WN\User\Model;

use WN\Core\Pattern\Singletone;
use WN\Core\Model\DB\Table;

class UserDB
{
    use Singletone;

    // public static $class_model = 'DB';
    public static $connect = 'sqlite.data';
    public static $file_config = 'user_db';

    public static $users_table_options = [
        'columns'   => [
            'id'            => 'integer(11) primary_ai',
            'email'         => 'varchar(128) null unique',
            'phone'         => 'varchar(16) null unique',
            'username'      => 'varchar(128)',
            'password'      => 'varchar(128)',
            'name'          => 'varchar(128)',
            'surname'       => 'varchar(128)',
            'sex'           => 'integer(1)',
            'dob'           => 'integer(11)',
        ],
    ];

    // public static $roles_table_options = [
    //     'columns' => [
    //         'user_id'   => 'integer(11) not null',
    //         'group_id'  => 'varchar(128) not null',
    //         'role'      => 'integer(3) not null',
    //     ],
    //     'keys' => ['(`user_id`, `group_id`)' => 'unique'],
    // ];

    public $user_table;
    public $role_table;

    // public static function instance()
    // {

    // }

    public function __construct()
    {
        static::settings(static::$file_config);

        static::$users_table_options['pdo'] = static::$connect;        
        $this->user_table = Table::instance('users', static::$users_table_options);
    }

    public function find($id)
    {
        return $this->user_table->get($id);
    }

    public function save($data)
    {
        $id = $this->user_table->set($data);
        if(empty($id) && $data['id']) return $data['id'];
        else return $id;
    }

    public function role($id, $group_id = null, $strict = false)
    {
        // return GroupDB::instance();
        $model_group =  GroupDB::instance();
        return $model_group->role($id, $group_id);
    }
}