<?php

namespace WN\DB;

use WN\Core\Core;
use WN\Core\Helper\Arr;
use WN\Core\Exception;
use WN\Page\Page;

class ETP extends \WN\DB\Pattern\Scheme  //Entity - Tree - Proprties
{
    protected static $instance = [];

    public $t_entities;
    public $t_props;
    // public $t_blob = 'texts';
    public $field_path = 'path';
    public $len = 4;

    public function __construct($table, $settings = null)
    {
        parent::__construct($table, $settings);

        if(!$this->t_entities && $table) $this->t_entities = $table;
        if(!$this->t_props) $this->t_props = $this->t_entities.'_props';

        $this->tree = $this->db->tree($this->t_entities);

        if(empty($this->ent_columns))
            $this->ent_columns = array_flip(array_keys($this->db->schema()->columns($this->t_entities)));
    }

    public function get()
    {
        $row = call_user_func_array([$this, '_get_row'], func_get_args());

        if(!$row) return false;
        else
        {
            $path = $row[$this->field_path];

            $this->path_length = strlen($path);

            $paths = $this->_parent_paths($path);

            $props = $this->_get_props($paths);

            return $row + $props;
        }
    }

    public function getAll($where = null, $columns = [], $order_by = null)
    {
        $select = $this->db->select($columns)->from($this->t_entities);

        call_user_func_array([$select, 'where'], $where);

        if($order_by)
        {
            $select->order_by($order_by);
        }

        // $select->order_by('id');

        // echo $select->render();

        return $select->execute();
    }

    public function get_children()
    {
        $args = func_get_args();

        $where = $args[0] ?? ['id', 1];
        $columns = $args[1] ?? null;
        $level = $args[2] ?? null;
        $full = $args[3] ?? true;
        
        if(isset($columns[0]) && $columns[0] === '*')
        {
            $ent_columns = false;
            $props_columns = null;
        }
        elseif(!empty($columns))
        {
            $ent_columns = array_intersect($columns, array_flip($this->ent_columns));
            $props_columns = array_diff($columns, array_flip($this->ent_columns));
            $remove_path = true;            
        }
        else
        {
            $ent_columns = false;
            $props_columns = false;
        }

        if(!$full)
        {
            $value = end($where);
            $key = reset($where);
            $this->tree->where($key, '<>', $value);
        }

        if(is_array($ent_columns))
        {
            if(!in_array($this->field_path, $ent_columns))
                array_unshift($ent_columns, $this->field_path);

            if(!in_array('id', $ent_columns))
                array_unshift($ent_columns, 'id');
        }

        if($ent_columns) call_user_func_array([$this->tree, 'select'], $ent_columns);

        if(!empty($level))
        {
            // $length = $this->_get_length($where);
            $this->tree->where(DB::expr('LENGTH(`pages`.`path`)'), '<=', $this->path_length + ($this->len * $level));
        }

        $collection = call_user_func_array([$this->tree, 'children'], $where);
   
        foreach($collection AS &$item)
        {
            if($props_columns || $props_columns === null)
            {
                $paths = $this->_parent_paths($item['path']);
                $props = $this->_get_props($paths, $props_columns);
                $item = Arr::insert($item, $props, count($item)-2);
            }

            if(isset($remove_path) && !in_array($this->field_path, $columns))
                unset($item[$this->field_path]);
        }

        return $collection;
    }

    public function get_parents()
    {
        $args = func_get_args();

        $where = $args[0] ?? ['id', 1];
        $columns = $args[1] ?? null;
        $full = $args[2] ?? true;

        if(isset($columns[0]) && $columns[0] === '*')
        {
            $ent_columns = false;
            $props_columns = null;
        }
        elseif(!empty($columns))
        {
            $ent_columns = array_intersect($columns, array_flip($this->ent_columns));
            $props_columns = array_diff($columns, array_flip($this->ent_columns));
            $remove_path = true;            
        }
        else
        {
            $ent_columns = false;
            $props_columns = false;
        }

        if(!$full)
        {
            $value = end($where);
            $key = reset($where);
            $this->tree->where($key, '<>', $value);
        }

        if(is_array($ent_columns))
        {
            if(!in_array($this->field_path, $ent_columns))
                array_unshift($ent_columns, $this->field_path);

            if(!in_array('id', $ent_columns))
                array_unshift($ent_columns, 'id');
        }

        if($ent_columns) call_user_func_array([$this->tree, 'select'], $ent_columns);

        $collection = call_user_func_array([$this->tree, 'parents'], $where);
   
        foreach($collection AS &$item)
        {
            if($props_columns || $props_columns === null)
            {
                $paths = $this->_parent_paths($item['path']);
                $props = $this->_get_props($paths, $props_columns);
                $item += $props;
            }

            if(isset($remove_path) && !in_array($this->field_path, $columns))
                unset($item[$this->field_path]);
        }

        return $collection;
    }

    public function set($data, $parent = null)
    {        
        $props = array_diff_key($data, $this->ent_columns);
        $ents = array_diff_key($data, $props);

        try
        {
            if($this->db->pdo->inTransaction() === false)
                $this->db->pdo->beginTransaction();

            if($ents) $id = $this->_set_ents($ents, $parent);

            $path = $data['path'] ?? null;

            if($props && $id)
                foreach($this->_set_props($props, $id, $path) AS $action)
                    $action->execute();

            if($this->db->pdo->inTransaction() === true)  $this->db->pdo->commit();
        }
        catch(\PDOException $e)
        {
            if($this->db->pdo->inTransaction() === true)
                $this->db->pdo->rollBack();

            Exception\Handler::exceptionHandler($e);

            return false;
        }

        return $id ?? true;
    }

    public function delete($id)
    {       
        return $this->tree->delete($id);
    }

    // public function delete_props($id, $props)
    // {
    //     $path = $this->_get_path($id);

    //     if(!is_array($props)) $props = [$props];

    //     $delete = $this->db->delete($this->t_props)
    //             ->where($this->field_path, $path)
    //             ->where('key', 'in', $props);

    //     return $this->db->transaction($delete);
    // }

    protected function _set_ents($data, $parent = null)
    {
        if(!isset($data['id'])) $id = $this->tree->insert($data, $parent);
        else
        {
            $id = $data['id'];
            $row = $this->_get_row($id);
            $data = array_diff($data, $row);

            if($data)
            {
                $this->db->update($this->t_entities)->set($data)->where($data['id'])->execute();
                if($parent !== null) $this->tree->move($data['id'], $parent);
            }
        }

        return $id ?? $data['id'] ?? false;
    }

    protected function _set_props($data, $id, $path = null)
    {
        if(!$path) $path = $this->_get_path($id);
        $paths = $this->_parent_paths($path);
        $props = $this->_get_props($paths);

        $null = array_filter($data, function($v){
            return $v === null;
        });

        $data = array_diff($data, $props);
        $data = array_replace($data, $null);

        $i = 0;

        foreach($data AS $k => $v)
        {
            if($v === null)
            {
                $delete[$i] = $k;
            }
            else
            {
                $upsert_props[$i][$this->field_path] = $path;
                $upsert_props[$i]['key'] = $k;
                $upsert_props[$i]['value'] = $v;
            }

            $i++;
        }

        if(!empty($delete))
        {
            $delete_props_obj = $this->db->delete($this->t_props);

            foreach($delete AS $key)
            {
                $delete_props_obj->or_where_open($this->field_path, $path)
                        ->where('key', $key)
                        ->where_close();
            }

            $result[] = $delete_props_obj;
        }

        if(!empty($upsert_props))
            $result[] = $this->db->upsert($this->t_props)->set($upsert_props);

        return $result ?? []; 
    }

    protected function _get_path($id)
    {
        return $this->db->select($this->field_path)->from($this->t_entities)
                    ->where('id', $id)
                    ->or_where($this->field_path, $id)
                    ->execute()->fetchColumn();
    }

    protected function _parent_paths($path)
    {
        $res = [];
        $arr = str_split($path, $this->len);

        foreach($arr as $k => $item)
        {
            $prefix = $res[$k-1] ?? null;
            $res[$k] = $prefix.$item;
        }

        return $res;
    }

    protected function _get_row()
    {
        $args = func_get_args();
       
        $select = $this->db->select()->from($this->t_entities);

        if(!empty($args)) call_user_func_array([$select, 'where'], $args);

        return $select->execute()->fetch();
    }

    protected function _get_props($paths, $keys = null)
    {
        $select = $this->db->select('key', 'value')->from($this->t_props)
                ->where($this->field_path, 'in', $paths);

        // var_dump($keys);

        if(!empty($keys))
            $select->where('key', 'in', $keys);

        $sth = $select->order_by($this->field_path);

        return $sth->execute()->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    // protected function _get_blobs($paths)
    // {
    //     return $this->db->select('key', 'value')->from('texts')
    //             ->where('table', $this->t_entities)
    //             ->where($this->field_path, 'in', $paths)
    //             ->order_by($this->field_path);
    // }
}