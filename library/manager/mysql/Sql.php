<?php

namespace tpr\db\manager\mysql;

class Sql
{
    public static $sql = [
        'db.create'      => 'CREATE DATABASE IF NOT EXISTS {name} DEFAULT CHARACTER SET {charset} COLLATE {collate};',
        'db.delete'      => 'DROP DATABASE IF EXISTS {name};',
        'db.exist'       => 'SELECT SCHEMA_NAME,DEFAULT_CHARACTER_SET_NAME,DEFAULT_COLLATION_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME={name}',
        'db.show'        => 'SHOW DATABASES;',
        'table.show'     => 'SHOW TABLES FROM {name};',
        'table.create'   => 'CREATE TABLE {table_name} () ENGINE={engine} AUTO_INCREMENT={auto_increment} DEFAULT CHARSET={charset};',
        'table.sys'      => 'CREATE TABLE {table_name} {like};',
        'table.sys_data' => 'INSERT INTO {table_name} {sql}',
        'table.delete'   => 'DROP TABLE IF EXISTS {table_name};',
        'table.exist'    => 'SELECT `TABLE_SCHEMA`,`TABLE_NAME` FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA={name} AND TABLE_NAME={table_name}',
        'index.delete'   => 'ALTER TABLE {table_name} DROP INDEX {index_name};',
        'column.add'     => 'ALTER TABLE {table_name} ADD {column_name} {datatype};',
        'column.delete'  => 'ALTER TABLE {table_name} DROP COLUMN {column_name};',
        'column.update'  => 'ALTER TABLE {table_name} MODIFY COLUMN {column_name} {datatype};',
        'datatype'       => 'CHARACTER SET {charset} COLLATE {collate}',
        'insert.data'    => 'INSERT INTO {table_name} VALUES ({values});',
    ];

    public static function getSql($opt, $data = [])
    {
        $check = self::checkElement($opt, $data);
        if (true !== $check) {
            $e = new \InvalidArgumentException('`' . $check . '` not exist! : ');

            throw $e;
        }
        $sql = self::getSqlTemplate($opt);

        foreach ($data as $k => $v) {
            $key = '{' . $k . '}';
            if (false != strpos($sql, $key)) {
                $sql = str_replace($key, $v, $sql);
            }
        }

        return $sql;
    }

    public static function getSqlTemplate($opt)
    {
        return isset(self::$sql[$opt]) ? self::$sql[$opt] : '';
    }

    public static function checkElement($opt, $data = [])
    {
        $sql = self::getSqlTemplate($opt);

        $need_key = [];
        while (!empty($sql) && false !== strpos($sql, '{')) {
            $left  = strpos($sql, '{');
            $right = strpos($sql, '}');
            $str   = mb_substr($sql, $left + 1, $right - $left - 1);
            array_push($need_key, $str);
            $sql = mb_substr($sql, $right + 1);
        }

        foreach ($need_key as $key) {
            if (!isset($data[$key])) {
                return $key;
            }
        }

        return true;
    }
}
