<?php

namespace WN\User;

use WN\Core\Pattern\Entity;
use WN\DB\DB;

class Group extends Entity
{
    const OBJ = 0;
    const ARR = 1;
    const PAIR = 2;
    const COUNT = 3;
    const ID = 4;

    public static $model;// = 'WN\User\ModelGroup';

    public $parent;

    public function __construct($id = null, array $options = null)
    {
        static::$model = ModelGroup::instance();
        parent::__construct($id, $options);

        // if(!$this->id)
    }
   
    public function save()
    {
        if($this->id) $this->data['id'] = $this->id;
        $id = static::$model->set($this->data, $this->parent);
        if(!$this->id) $this->id = $id;
        return $id;
    }

    public function move($parent)
    {
        return static::$model->move($this->id, $parent);
    }

    public function add($user_id, $role = ROLE_USER)
    {
        return static::$model->add_user($user_id, $this->id, $role);
    }

    public function remove($user_id)
    {
        return static::$model->remove_user($user_id, $this->id);
    }

    public function users($const = null, $sort = null, $limit = null, $offset = null)
    {
        if($const === null) $const = DB::PAIR;
        return static::$model->users($const, $this->id, $sort, $limit, $offset);
    }

    public function children($trim = false)
    {
        return static::$model->children($this->id, $trim);
    }

    public function parents($trim = false)
    {
        return static::$model->parents($this->id, $trim);
    }

    // public function is_children()
    // {
    //     return static::$model->is_children($this->id);
    // }

    // public function delete()
    // {
    //     static::$model->delete($this->id);
    //     $this = null;
    // }

    // protected function top_level_data()
    // {
    //     $this->title = 'site';
    //     $this->desc = 'top level automatic group';
    // }
}