<?php
namespace WN\User;

use WN\DB\Pattern\Model;
use WN\DB\{DB, EAV};
use WN\Core\Session;
use WN\Core\Helper\{Validation AS Valid, Date};

class ModelUser extends Model
{
    public static $tables_options = [
            'name'  => 'users',
            'columns' => [
                'id int(11) pk_ai',
                'nickname varchar(64)',
                'firstname varchar(64)',
                'lastname varchar(64)',
                'email varchar(64)',
                'phone varchar(20)',
                'password varchar(64)',
                'dob int(10)',
                'sex int(1)',
                'register int(10)'
            ],
            'index' => [
                'unique (email)',
                'unique (phone)',
                'index (password)',
            ],
            'collate' => ['utf8_general_ci', 'utf8', 'InnoDB'],
        ];

    public static function create_table($options = null)
    {
        if(!$options) $options = static::$tables_options;

        static::$db->create($options)->exec();
    }

    public function set($data)
    {
        if(isset($data['is_user'])) unset($data['is_user']);
        if(!empty($data['password'])) $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        if(!empty($data['dob'])) $data['dob'] = Date::timestamp($data['dob']);
        if(!empty($data['phone'])) $data['phone'] = static::filter_santize_phone($data['phone']);

        foreach($data as $key => &$value)
        {
            $value = trim($value);
            if(empty($value) && $value !== '0') unset($data[$key]);
        }

        return parent::set($data);
    }

    public function get_userdata($value, $password = false)
    {
        $select = static::$db->select()->from($this->table_name);

        if(is_int($value) || ctype_digit($value))
            $select->where('id', $value)->or_where('phone', $value);
        elseif(Valid::email($value))
            $select->where('email', filter_var($value, FILTER_SANITIZE_EMAIL, FILTER_FLAG_EMAIL_UNICODE));
        else
            $select->where('phone', static::filter_santize_phone($value));

        $userdata = $select->limit(1)->execute()->fetch();

        if($password)
            if(password_verify($password, $userdata['password']))
                return $userdata;
            else return false;
        else return $userdata;
    }

    public static function unique($value, $field)
    {
        $table_name = self::instance()->table_name;
        if($value == '') return true;

        $user = User::auth();

        if($field == 'phone') $value = static::filter_santize_phone($value);

        $select = static::$db->select('id')->from($table_name)
            ->where($field, $value);

        if($user->id) $select->where('id', '!=', $user->id);
            
        $result = $select->limit(1)->execute()->fetch();

        return ($result) ? false : true;
    }

    public static function filter_santize_phone($value)
    {
        return preg_replace('/[^\d]/', '', $value);
    }
}