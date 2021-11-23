<?php
namespace WN\DB;

use WN\DB\Lib\{Parser};

class Upsert extends Insert
{
    protected function _render()
    {
        $sql = parent::_render();

        foreach($this->columns as &$column)
            $column = Parser::escape($column);

        $driver = __NAMESPACE__.'\Lib\\'.ucfirst($this->db->driver);

        $upsert = $driver::upsert($this->columns, $this->db->pdo, $this->table);

        return $sql.$this->eol.$upsert;
    }
}