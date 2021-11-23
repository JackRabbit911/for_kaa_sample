<?php
namespace WN\DB;

use WN\DB\Lib\{Render, Where, Parser};

class Delete extends Render
{
    use Where;

    protected $table;

    public function __construct($db, $table)
    {
        $this->db = $db;
        $this->table = Parser::escape($table);
    }

    protected function _render()
    {
        $sql = "DELETE FROM $this->table";

        $where = (!empty($this->arr_where)) ? $this->where2string() : null;

        return $sql.$where;
    }

    public function execute($params = null)
    {
        return parent::execute($params)->rowCount();
    }
}