<?php
namespace WN\User\Model;

use WN\Core\Pattern\Singletone;
use WN\Core\Model\DB\{PDO, Table, DB};

class GroupDB
{
    use Singletone;

    public static $connect = 'sqlite.data';
    public static $file_config = 'group_db';

    public static $group_table_options = [
        'columns'   => [
            'id'            => 'integer(11) primary_ai',
            'parent'        => 'integer(11)',
            'title'         => 'varchar(128) unique',
            'desc'          => 'varchar(255)',
        ],
        // 'keys' => ['(`parent`)' => 'index'],
    ];

    public static $roles_table_options = [
        'columns' => [
            'user_id'   => 'integer(11) not null',
            'group_id'  => 'integer(11) not null',
            'role'      => 'integer(3) not null',
        ],
        'keys' => ['(`user_id`, `group_id`)' => 'unique'],
    ];

    public function __construct()
    {
        static::settings(static::$file_config);
        static::$group_table_options['connect'] = static::$roles_table_options['connect'] 
        = $this->pdo = PDO::instance(static::$connect);
        $this->driver_name = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        // $this->driver = 'WN\Core\Model\DB\Drivers\\'.ucfirst($this->driver_name);
        // $this->db = DB::instance(static::$connect);
        $this->group_table = Table::instance('groups', static::$group_table_options);
        $this->roles_table = Table::instance('users_groups', static::$roles_table_options);
    }

    public function find($id)
    {
        return $this->group_table->get($id);
    }

    public function save($data)
    {
        $id = $this->group_table->set($data);
        if(empty($id) && $data['id']) return $data['id'];
        else return $id;
    }

    public function role($user_id, $group)
    {
        // $sql_is_table = $this->driver::is_table('users_groups');

        // if($this->pdo->query($sql_is_table)->fetch() === false)
        // {
        //     $this->db->create('users_groups')
        //         ->columns(static::$roles_table_options['columns'])
        //         ->keys(static::$roles_table_options['keys'])
        //         ->execute();
        // }

        
        $sth = $this->pdo->prepare(Sql::get('role_mysql'));
        $sth->execute([':group_id' => $group, ':user_id' => $user_id]);
        $res = $sth->fetch();

        return ($res['role'] === null) ? 0 : $res['role'];
    }

    public function add_user($user_id, $group_id, $role = 20)
    {
        $data = ['user_id' => $user_id, 'group_id' => $group_id, 'role' => $role];
        return $this->roles_table->set($data);
    }
}