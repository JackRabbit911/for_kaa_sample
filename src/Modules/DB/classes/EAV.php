<?php
namespace WN\DB;

use WN\Core\Core;
use WN\Core\Exception\WnException;
use WN\Core\Pattern\{Settings, Options};
use WN\Core\Helper\{Arr};
use WN\DB\Lib\{Where, Having, OrderLimit, Parser, Tableble};

class EAV extends \WN\DB\Pattern\Scheme  // implements Tableble
{
    use Options, Where, Having, OrderLimit;
    
    // public $db;

    // public $ent_columns = ['id'];

    public $t_entities;
    public $t_props;

    protected static $instance = [];

    protected $group_by = [];
    protected $select_columns = [];
    protected $select_props = [];
    protected $keys_props = [];

    // public static function instance($table, $settings = null)
    // {
    //     if(!isset(static::$instance[$table]) || !(static::$instance[$table] instanceof static))
    //     {
    //         static::$instance[$table] = new static($table, $settings);
    //     }
    //     return static::$instance[$table];
    // }

    public function __construct($table, $settings = null)
    {
        parent::__construct($table, $settings);

        // if(!$this->db)
        // {
        //     if($settings instanceof DB) $this->db = $settings;
        //     elseif(is_string($settings) || $settings === null)
        //     {
        //         $connect = DB::connect($settings);
        //         $this->db = DB::instance($connect);
        //     }
        // }

        if(!$this->t_entities && $table) $this->t_entities = $table;
        if(!$this->t_props) $this->t_props = $this->t_entities.'_props';

        if(empty($this->ent_columns))
            $this->ent_columns = array_keys($this->db->schema()->columns($this->t_entities));

        // $this->keys_props = $this->db->select('key')->from($this->t_props)->distinct()->execute()->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function create_table_props()
    {
        $opt = [
                    'columns' => [
                        'id int(11) not null',
                        'key varchar(64) not null',
                        'value varchar(255) not null',
                    ],
                    'index' => [
                        'unique (id, key)',
                        'foreign key (id) references '.$this->t_entities.'(id) cascade',
                    ]
                ];

        $schema = $this->db->schema();
        
        if($schema->tables($this->t_entities))
        {
            if(!$schema->tables($this->t_props))
                $this->db->create($this->t_props)->set($opt)->exec();
        }
        else throw new WnException("Table '$this->t_entities' doesn't exists");

        return $this;
    }

    public function insert(array $data)
    {
        $_insert_row = function(array $data, Insert &$e, Insert &$p)
        {
            $props = array_diff_key($data, array_flip($this->ent_columns));
            $ents = array_diff_key($data, $props);

            $id = $e->set($ents)->execute();

            foreach($props AS $k => $v)
                if($v !== null) $params2insert[] = [$id, $k, $v];

            if(isset($params2insert))
                $p->values($params2insert)->execute();

            return $id;
        };

        $e = $this->db->insert($this->t_entities);
        $p = $this->db->insert($this->t_props);

        if(Parser::is_assoc($data))
            return $_insert_row($data, $e, $p);
        else 
            foreach($data AS $item)
                $id = $_insert_row($item, $e, $p);

        return $id;
    }

    public function update(array $data)
    {
        $props = array_diff_key($data, array_flip($this->ent_columns));
        $ents = array_diff_key($data, $props);

        $e_update = $this->db->update($this->t_entities);
        $e_update->arr_where = $this->arr_where;

        $id = $ents['id'] ?? null;
        if(!$id)
            $id = $this->_get_id($this->arr_where);

        if(!$id) return;

        $e_update->where($id);

        if(!empty($ents))
            $update = $e_update->set($ents)->prepare();
        else $update = null;

        return $this->_upsert($id, $props, $update, $e_update->params(false));
    }

    public function upsert(array $data)
    {
        $props = array_diff_key($data, array_flip($this->ent_columns));
        $ents = array_diff_key($data, $props);

        // var_dump($data, $ents, $props);
        // exit;

        $id = $this->db->upsert($this->t_entities)->set($ents)->execute();

        $this->_upsert($id, $props);

        return $id;
    }

    public function set(array $data)
    {
        return $this->upsert($data);
    }

    public function delete()
    {
        $args = func_get_args();
        $delete = $this->db->delete($this->t_entities);
        if(!empty($args)) call_user_func_array([$delete, 'where'], $args);

        // if($this->db->driver === 'sqlite')
        //     $this->db->pdo->exec("PRAGMA foreign_keys = ON");

        return $delete->execute();
    }

    protected function _upsert($id, $props, $stmt = null, $params = null)
    {
        $params2delete = $params2upsert = [];

        foreach($props AS $k => $v)
        {
            if($v === null)
                $params2delete[] = [$id, $k];
            else $params2upsert[] = [$id, $k, $v, $v];
        }

        if(!empty($params2delete))
        {
            $del_sql = "DELETE FROM $this->t_props WHERE `id` = ? AND `key` = ?";
            $delete = $this->db->pdo->prepare($del_sql);           
        }

        $mysql = "INSERT INTO `$this->t_props`(`id`, `key`, `value`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = ?";
        $sqlite = "INSERT INTO `$this->t_props` (`id`, `key`, `value`) VALUES (?, ?, ?) ON CONFLICT(`id`, `key`) DO UPDATE SET `value` = ?";
        
        $driver = $this->db->driver;
        $sql = $$driver;

        $upsert = $this->db->pdo->prepare($sql);

        try
        {
            if($this->db->pdo->inTransaction() === false)
                $this->db->pdo->beginTransaction();

            if($stmt instanceof \PDOStatement && !empty($params))
                $stmt->execute($params);

            foreach($params2delete AS $item)
                $delete->execute($item);

            foreach($params2upsert AS $item)
                $upsert->execute($item);

            if($this->db->pdo->inTransaction() === true)  $this->db->pdo->commit();
            
            return true;
        }
        catch(\PDOException $e)
        {
            if($this->db->pdo->inTransaction() === true)
                $this->db->pdo->rollBack();
                
            return false;
        }
    }

    public function remove_broken_links()
    {
        $select = $this->db->select('p.id')->from('users_props p')
                    ->join('users u', 'left')
                    ->on('u.id', 'p.id')
                    ->where('u.id', null);

        $delete = $this->db->delete('users_props')->where('id', 'in', $select);

        return $delete->execute();
    }

    public function get()
    {
        $this->limit(1);

        $sth = call_user_func_array([$this, '_get'], func_get_args());

        $entity = $sth->fetch();

        if(!$entity) return false;
        else 
            foreach($entity AS $k => $v)
                if($v === null && !in_array($k, $this->ent_columns))
                    unset($entity[$k]);

        $props = (isset($entity['id'])) ? $this->get_props_by_id($entity['id']) : [];

        return $entity + $props;
    }

    public function getAll()
    {
        $sth = call_user_func_array([$this, '_get'], func_get_args());

        $entities = $sth->fetchAll();

        if(empty($entities)) return [];

        $ids = array_column($entities, 'id');

        $props = ($ids) ? $this->get_props_by_ids($ids) : [];

        foreach($props AS $item)
            $arr_props[$item['id']][$item['key']] = $item['value'];

        foreach($entities AS &$ent)
        {
            foreach($ent AS $k => $v)
                if(($v === null && !in_array($k, $this->ent_columns)))
                    unset($ent[$k]);

            if(!empty($arr_props) && array_key_exists($ent['id'], $arr_props))
                $ent = $ent + $arr_props[$ent['id']];
        }

        return $entities;
    }

    public function select()
    {
        $args = func_get_args();
        $args = str_replace('(id', '('.$this->t_entities.'.id', $args);
        $this->select_columns = $this->select_columns + $args;

        return $this;
    }

    public function group_by()
    {
        $args = func_get_args();
        $this->group_by = $args;
        return $this;
    }

    // public function order()
    // {
    //     $args = func_get_args();
    //     $this->order = $args;
    //     return call_user_func_array([$this, 'order_by'], func_get_args());
    //     // return $this;
    // }

    protected function _get_id($arr_where)
    {
        $select = $this->db->select('id')->from($this->t_entities);
        $select->arr_where = $arr_where;
        $result = $select->limit(1)->execute()->fetch();
        return (int)$result['id'] ?? null;
    }

    protected function _get()
    {
        $args = func_get_args();

        $chr = 'a';

        $select = $this->db->select($this->t_entities.'.*')->from($this->t_entities);

        if($this->select_columns)
            $select->columns = $this->select_columns;

        if(!empty($this->arr_where))
        {
            $this->arr_where[0][0] = 'WHERE (';
            if($this->arr_where[array_key_last($this->arr_where)] !== ')') $this->arr_where[] = ')';
        }

        $select->arr_where = $this->arr_where;

        if(!empty($args)) call_user_func_array([$select, 'where'], $args);

        $props = [];

        foreach($select->arr_where AS &$item)
        {
            if(!is_array($item)) continue;

            if(!in_array($item[1], $this->ent_columns) && !in_array($item[1], $props))
            {
                $select->select($chr.'.value '.$item[1])
                        ->join($this->t_props.' '.$chr, 'left')
                        ->on($this->t_entities.'.id', $chr.'.id')
                        ->on($chr++.'.key', DB::expr("'".$item[1]."'"));

                $props[] = $item[1];
            }
            else $item[1] = $this->t_entities.'.'.$item[1];
        }

        if($this->order_by)
        {
            if(preg_match_all('/`(\w+)`/', $this->order_by, $arr_order_columns))
                $columns = Arr::merge($this->group_by, $arr_order_columns[1]);
            else $columns = $this->group_by;
        }
        else $columns = $this->group_by;

        foreach($columns AS &$column)
        {
            if(in_array($column, $this->ent_columns))
                $column = $this->t_entities.'.'.$column;
            else
            {
                if(!in_array($column, $props))
                {
                    $select->select($chr.'.value '.$column)
                        ->join($this->t_props.' '.$chr, 'left')
                        ->on($this->t_entities.'.id', $chr.'.id')
                        ->on($chr++.'.key', DB::expr("'".$column."'"));
                }
            }
        }

        $select->group_by = $this->group_by;
        $select->arr_having = $this->arr_having;
        $select->order_by = $this->order_by;
        $select->limit = $this->limit;
        $select->offset = $this->offset;

        // var_dump($select->render(3, true));

        return $select->execute();
    }

    protected function get_props_by_id($id)
    {
        $get = $this->db->select('key', 'value')
                        ->from($this->t_props)
                        ->where($id);
                        
        return $get->execute()->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    protected function get_props_by_ids($ids)
    {
        $get = $this->db->select()
                        ->from($this->t_props)
                        ->where('id', 'in', $ids);

        return $get->execute()->fetchAll();
    }
}