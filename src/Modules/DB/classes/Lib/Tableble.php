<?php
namespace WN\DB\Lib;

interface Tableble
{
    public function insert(array $data);

    public function update(array $data);

    public function upsert(array $data);

    public function delete();

    public function get();

    public function getAll();

    public function select();

    public function group_by();

    public function where($column, $compare = NULL, $value = NULL);

    public function having($column, $compare = NULL, $value = NULL);
}