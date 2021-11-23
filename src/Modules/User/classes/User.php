<?php

namespace WN\User;

const ROLE_SUBUSER = 10;
const ROLE_USER = 20;

use WN\Core\Pattern\Entity;
use WN\Core\{Session, Route, View};
use WN\Core\Helper\{Date, HTTP, HTML, Arr};
use WN\DB\DB;

class User extends Entity
{
    const CONFIRM_EMAIL = 1;
    const CONFIRM_PHONE = 2;
    const CONFIRM_COMBINE = 3;

    public static $instance;
    public static $model;
    public static $get_default_role_callback = [__CLASS__, 'get_default_role'];

    public static $confirm = self::CONFIRM_COMBINE;

    public static $session;

    public static function auth()
    {
        if(!static::$instance instanceof static)
        {
            if(!static::$session)
                static::$session = Session::instance();

            static::$instance = new static(static::$session->user_id);
        }

        return static::$instance;
    }

    public static function force_login($userdata, $password = false, $is_long = false)
    {
        if(is_array($userdata))
        {
            $userdata = $userdata['userdata'] ?? null;
            $password = $userdata['password'] ?? null;

            if(array_key_exists('is_long', $userdata)) $is_long = $userdata['is_long'];
            elseif(array_key_exists('is_short', $userdata)) $is_long = false;
        }

        static::model_instance();

        $userdata = static::$model->get_userdata($userdata, $password);

        if($userdata)
        {
            static::$instance = new static($userdata);
            if(!static::$session) static::$session = Session::instance();
            static::$session->user_id = static::$instance->id;
            static::$session->is_long = $is_long;
        }

        return (static::$instance) ? static::$instance : false;
    }

    public static function login($userdata, $password, $is_long = false)
    {
        if(!$password) return false;
        else return static::force_login($userdata, $password, $is_long);
    }

    public function __construct($id = null, array $settings = null)
    {
        parent::__construct($id, $settings);

        if(isset($this->data['password']))
        {
            $this->is_user = true;
            unset($this->data['password']);
        }

        foreach($this->data as $key => $value)
            if(empty($value) && $value != 0) unset($this->data[$key]);
    }

    public function save()
    {
        $id = parent::save();
        if(!$this->id) $this->id = $id;
        return $id;
    }

    public function log_out(int $mode = Session::DEL_CURRENT)
    {
        static::$session->destroy($mode);
        $this->id = null;
        $this->data = [];
        static::$instance = $this;
    }

    public function role($group = null, $is_max = true)
    {
        $model_group = ModelGroup::instance();

        if(is_bool($is_max))
        {
            $role = $model_group->get_role($this->id, $group, $is_max);

            return (!$role && !$group && $this->id) 
                ? call_user_func(static::$get_default_role_callback) : $role;
        }
        elseif(is_numeric($is_max))
        {
            $model_group->add_user($this->id, $group, $is_max);
            return $is_max;
        }
    }

    public function groups($const = null)
    {
        if($const === null) $const = DB::PAIR;
        return ModelGroup::instance()->users_groups($const, $this->id);
    }

    public function dob(string $format = null)
    {
        if(!$this->dob) return null;
        else return Date::format($this->dob, $format);
    }

    public function age($format = '%y')
    {
        if(!$this->dob) return null;
        else return Date::interval($this->dob, null, $format);
    }

    public function name()
    {
        if($this->nickname) return $this->nickname;
        elseif($this->firstname) return $this->firstname;
        elseif($this->lastname) return $this->lastname;
        elseif($this->email) return $this->email;
        elseif($this->id) return __('User');
        else return __('Guest');
    }

    protected function get_default_role()
    {
        return (!empty($this->data['is_user'])) ? ROLE_USER : ROLE_SUBUSER;
    }
}