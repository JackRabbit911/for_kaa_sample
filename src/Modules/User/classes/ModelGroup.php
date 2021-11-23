<?php
namespace WN\User;

use WN\DB\Pattern\Model;
use WN\DB\DB;

class ModelGroup extends Model
{
    // public static $instance;
    // public static $db;
    // public static $table_name = 'groups';
    // public static $entity_class;

    public static $ug_table_name = 'users_groups';

    // public static $auto_create = true;

    public static $tables_options = [
        [   'name'  => 'groups',
            'columns' => [
                'id int(11) pk_ai',
                'path varchar(256) charset latin1 collate latin1',
                'title varchar(128)',
                'desc varchar(256)',
            ],
            'index' => [
                'unique (path)',
                'index (title)',
            ],
            'collate' => ['utf8_general_ci', 'utf8', 'InnoDB']],

        [   'name'  => 'users_groups',
            'columns' => [
                'uid int(11)',
                'gid int(11)',
                'role int(3)',
            ],
            'index' => [
                'index (uid)',
                'index (gid)',
                'unique (uid, gid)',
                'fk(uid) rf users(id) cascade',
                'fk(gid) rf groups(id) cascade',
            ],
            'collate' => ['latin1_bin', 'latin1', 'InnoDB'],
        ]];

    public $tree;
    protected $ug;

    // public $table_name = 'groups';

    public static function create_table($options = null)
    {
       if(!$options) $options = static::$tables_options;
       foreach($options AS $table)
       {
        //    echo 'lala ';
            static::$db->create($table)->exec();
       }
    //    exit;
    }

    public function __construct($settings = null)
    {
        parent::__construct($settings);

        $this->table = static::$db->table($this->table_name);
        $this->tree = static::$db->tree($this->table_name);
        $this->ug = static::$db->table(static::$ug_table_name);

        // if(!static::$db->schema()->tables(static::$table_name))
        //     static::create_table();

        // var_dump($this->table_name);
        // exit;
    }

    public function get($id = null)
    {
        if(is_numeric($id)) $where = [$id];
        else $where = ['title', $id];

        return call_user_func_array('parent::get', $where);
    }

    public function set($data, $parent = null)
    {
        if(empty($data)) return null;

        if(is_string($parent))
        {
            $pgroup = $this->table->where('title', $parent)->or_where('path', $parent)->get();
            $parent = $pgroup['path'] ?? null;
            $parent_id = $pgroup['id'];
        }
        else $parent_id = $parent;

        if(!isset($data['id'])) return $this->tree->insert($data, $parent);
        elseif(isset($data['id']) && $parent === null) 
            return $this->table->where($data['id'])->update($data);
        else
        {
            $this->table->where($data['id'])->update($data);
            return $this->tree->move($data['id'], $parent_id);
        }
    }

    public function move($id, $pid)
    {
        // echo 'qq';
        return $this->tree->move($id, $pid);
    }

    public function add_user($user_id, $group_id, $role = ROLE_USER)
    {
        $data = ['uid' => $user_id, 'gid' => $group_id, 'role' => $role];

        if($group_id === null)
        {
            $row = $this->ug->where('uid', $user_id)->where('gid', null)->get();
            if($row) return $this->ug->where('uid', $user_id)
                ->where('gid', null)->update($data);
            else return $this->ug->insert($data);
        }
        else return $this->ug->upsert($data);
    }

    public function remove_user($user_id, $group_id)
    {
        return $this->ug->where('uid', $user_id)->where('gid', $group_id)->delete();
    }

    public function users($const, $group_id, $order_by = null, $limit = null, $offset = null)
    {
        switch($const)
        {
            case DB::PAIR:
                $select = static::$db->select('users.id', 'role'); break;
            case DB::COUNT:
                $select = static::$db->select('count(*) co'); break;
            case DB::ID:
                $select = static::$db->select('users.id'); break;
            default: $select = static::$db->select('users.*', 'role');
        }

        $select->from('users_groups ug')
                    ->join('users', 'left')->on('users.id', 'uid')
                    ->where('gid', $group_id);

        if(!is_array($order_by)) $arr_sort_rules = [$order_by];
        foreach($arr_sort_rules AS $rule) $select->order_by($rule);

        $sth = $select->limit($limit)->offset($offset)->execute();

        switch($const)
        {
            case DB::OBJ:
                return $sth->fetchAll(\PDO::FETCH_CLASS, 'WN\User\User'); break;
            case DB::ARR:
                return $sth->fetchAll(); break;
            case DB::PAIR:
                return $sth->fetchAll(\PDO::FETCH_KEY_PAIR); break;
            case DB::COUNT:
                return (int) $sth->fetch()['co']; break;
            case DB::ID:
                return $sth->fetchAll(\PDO::FETCH_COLUMN); break;
        }
    }

    public function children($group_id, $trim = false)
    {
        $arr = $this->tree->children($group_id);
        if($trim && $group_id > 0) array_shift(($arr));
        return $arr;
    }

    public function parents($group_id, $trim = false)
    {
        $arr = $this->tree->parents($group_id);
        if($trim) array_pop(($arr));
        return $arr;
    }

    // public function is_children($group_id)
    // {
    //     $children = $this->children($group_id, true);
    //     return (empty($children)) ? false : true;
    // }

    public function delete($group, $force = null)
    {
        if(!$group instanceof Group) $group = new Group($group);
        if($force === true || $group->users(DB::COUNT) == 0)
        {
            $deleted = ($this->tree->delete($group->id, $force) === false) ? false : true;
            if($deleted) $group = null;
        }
        else return false;
    }

    public function get_role($uid, $gid = null, $is_max = true)
    {
        if($gid !== null && !is_numeric($gid))
            $gid = static::$db->select('id')->from($this->table_name)->where('title', $gid);

        if($is_max)
        {
            // var_dump($this->table_name);
            // exit;

            $tree = static::$db->tree($this->table_name);
            $pids = $tree->parent_ids($gid);
            
            $role = static::$db->select('max(role) role')->from(static::$ug_table_name)
                ->where('uid', $uid)
                ->and_where_open('gid', 'in', $pids)
                ->or_where('gid', null)->where_close()
                ->execute()->fetch();

                // var_dump($role->render(3), $role->params());
        }
        else
        {
            $role = static::$db->select('role')->from(static::$ug_table_name)
                ->where('gid', $gid)->where('uid', $uid)
                ->order_by('role desc')->limit(1)
                ->execute()->fetch();
            
        }
        return ($role) ? (int) $role['role'] : 0;
    }

    public function users_groups($const, $uid)
    {
        switch($const)
        {
            case DB::PAIR:
                $select = static::$db->select('id', 'role'); break;
            case DB::COUNT:
                $select = static::$db->select('count(id) co'); break;
            case DB::ID:
                $select = static::$db->select('id'); break;
            default:
                $select = static::$db->select($this->table_name.'.*', 'role');
        }

        $sth = $select->from(static::$ug_table_name)
                    ->join($this->table_name, 'left')->on('id', 'gid')
                    ->where('uid', $uid)
                    ->order_by('path')->execute();

        switch($const)
        {
            case DB::OBJ:
                return $sth->fetchAll(\PDO::FETCH_CLASS, 'WN\User\Group'); break;
            case DB::ARR:
                return $sth->fetchAll(); break;
            case DB::PAIR:
                return $sth->fetchAll(\PDO::FETCH_KEY_PAIR); break;
            case DB::COUNT:
                return (int) $sth->fetch()['co']; break;
            case DB::ID:
                return $sth->fetchAll(\PDO::FETCH_COLUMN); break;
        }
    }
}