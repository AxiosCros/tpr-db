<?php

namespace tpr\db\manager\mysql;

use tpr\db\manager\driver\Mysql;

class Column extends Mysql
{
    private $table_name;

    private $column_name;

    public function setTableName($table_name)
    {
        $this->table_name = $table_name;

        return $this;
    }

    public function setColumnName($column_name)
    {
        $this->column_name = $column_name;

        return $this;
    }

    public function add()
    {
        $this->operation = Operation::COLUMN_ADD;
        $this->sql_data  = [
            'table_name'  => $this->formatTableName($this->table_name),
            'column_name' => $this->formatTableName($this->column_name),
            'datatype'    => $this->getDataType(),
        ];

        return $this;
    }
}
