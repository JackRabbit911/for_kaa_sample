<?php

namespace WN\Page;

use WN\Core\Core;
use WN\Core\Exception\WnException;
use WN\DB\Pattern\Model;
use WN\Core\Helper\{Arr, Text};
use WN\User\User;

class ModelPage extends Model
{
    public static $tables_options = [
        [   'name'  => 'pages',
            'columns' => [
                'id int(11) pk_ai',
                'path varchar(256) charset latin1 collate latin1',
                'url varchar(256)',
                'dep_url varchar(256)',
                'title varchar(128)',
                'keywords varchar(256)',
                'description varchar(512)',
                'slug varchar(256)',
                'h1 varchar(256)',
                'cdate int(10)',
                'edate int(10)',
                'editor int(11)',
                'access varchar(17)',
                'status int(1)',
                'main mediumtext',
            ],
            'index' => [
                'unique (path)',
                'unique (url)',
                'unique (dep_url)',
                'index (title)',
            ],
            'collate' => ['utf8_general_ci', 'utf8', 'InnoDB']
        ],
        [   'name'  => 'pages_props',
            'columns' => [
                'path varchar(256) charset latin1 collate latin1',
                'key varchar(64)',
                'value varchar(256)',
            ],
            'index' => [
                'index (path)',
                'unique (path, key)',
                'fk(path) rf pages(path) cascade',
            ],
            'collate' => ['utf8_general_ci', 'utf8', 'InnoDB'],
        ],
        [
            'name' => 'texts',
            'columns' => [
                'id int(11) pk_ai',
                'text mediumtext'
            ],
            'collate' => ['utf8_general_ci', 'utf8', 'InnoDB']
        ],
    ];

    public $ent_columns = [];

    public $tree;
    protected $pp;

    public static function create_table($options = null)
    {
       if(!$options) $options = static::$tables_options;
       foreach($options AS $table)
       {
            static::$db->create($table)->exec();
       }
    }

    public function __construct($settings = null)
    {
        parent::__construct($settings);

        $this->table = static::$db->etp($this->table_name);
    }

    public function get()
    {
        $id = func_get_arg(0);

        if(is_numeric($id)) $args = ['id', $id];
        else $args = ['url', ltrim($id, '/')];

        $data = call_user_func_array('parent::get', $args);

        return $data;
    }

    public function get_uri($param)
    {
        $url = static::$db->select('url')->from($this->table_name)
            ->where('id', $param)->or_where('title', $param)
            ->execute()->fetchColumn();

        if($url === false && Core::$errors === true)
            throw new WnException('Page id=":param" or title=":param" not found', [':param'=>$param]);
    }

    // public function get_blob($id)
    // {
    //     $sth = static::$db->select('value')->from('texts')
    //                 ->where($id)->execute();

    //     return $sth->fetchColumn();
    // }

    public function get_children()
    {
        $args = func_get_args();
        
        $id = $args[0] ?? 1;

        if(is_numeric($id)) $args[0] = ['id', $id];
        else $args[0] = ['url', $id];

        $collection = call_user_func_array([$this->table, 'get_children'], $args);

        foreach($collection AS &$item)
        {
            $item = Page::factory($item);
        }

        return $collection;
    }

    public function get_parents()
    {
        $args = func_get_args();
    
        if(!is_array($args[0]))
        {
            $id = $args[0] ?? 1;

            if(is_numeric($id)) $args[0] = ['id', $id];
            else $args[0] = ['url', $id];
        }

        $collection = call_user_func_array([$this->table, 'get_parents'], $args);

        foreach($collection AS &$item)
        {
            $item = Page::factory($item);
        }

        return $collection;
    }

    public function tree()
    {
        return static::$db->tree($this->table_name);

    }

    public function getAll($where = [], $columns = [], $order_by = null)
    {
        // $eav = static::$db->eav($this->table_name);
        // var_dump($eav); exit;
        return $this->table->getAll($where, $columns, $order_by)
            ->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, 'WN\Page\Page');
    }

    // public function set_properties($id, $data)
    // {
    //     return $this->table->set_props($data, $id);
    // }

    public function set($data)
    {
        if(empty($data)) return null;
        $parent = Arr::flush($data, 'parent');

        if(empty($data['edate'])) $data['edate'] = time();
        if(empty($data['id'])) $data['cdate'] = $data['edate'];
        if(empty($data['editor'])) $data['editor'] = User::auth()->id;
        // if(empty($data['access'])) $data['access'] = '754';

        return $this->table->set($data, $parent);
    }

    public function remove()
    {
        return call_user_func_array([$this->table, 'delete'], func_get_args());
    }

    // public function get_properties($path)
    // {
    //     $paths = $this->tree->parent_paths($path);

    //     $select = static::$db->select('key', 'value')->from('pages_props')
    //                 ->where('page_path', 'in', $paths)
    //                 // ->order_by('page_path asc')
    //                 ->execute()->fetchAll(\PDO::FETCH_KEY_PAIR);

    //     return $select;
    // }

    // public function delete_properties($id, $props)
    // {
    //     return $this->table->delete_props($id, $props);
    // }
}