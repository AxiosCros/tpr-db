<?php
/**
 * @author  : axios
 * @email   : axiosleo@foxmail.com
 * @blog    : http://hanxv.cn
 * @datetime: 2018-12-18 16:38
 */

namespace tpr\db\manager\mysql;

use tpr\db\manager\driver\Mysql;

class Database extends Mysql
{
    public function setCharSet($charset = null)
    {
        $this->charset = $charset;
        return $this;
    }

    public function setCollate($collate)
    {
        $this->collate = $collate;
        return $this;
    }

    public function table($table_name)
    {
        $Table = new Table();
        $Table->setTableName($table_name);
        $Table->dbName($this->dbName());
        return $Table;
    }

    public function getTableList()
    {
        $sql    = Sql::getSql(Operation::TABLE_SHOW, [
            "name" => $this->dbName()
        ]);
        $tables = $this->query->query($sql);
        $list   = [];
        foreach ($tables as $t) {
            foreach ($t as $k => $v) {
                array_push($list, $v);
            }
        }
        return $list;
    }

    public function create($name = null)
    {
        $this->sql_data  = [
            'name'    => is_null($name) ? $this->dbName() : $name,
            'charset' => $this->charset,
            'collate' => $this->charset . $this->collate
        ];
        $this->operation = Operation::DB_CREATE;
        return $this;
    }

    public function delete()
    {
        $this->sql_data  = [
            'name' => $this->formatDbName($this->dbName())
        ];
        $this->operation = Operation::DB_DELETE;
        return $this;
    }

    public function saveAllData($path = '', $mode = 0, $limit = 100, $name = '')
    {
        $path = $this->filePath($path);
        if (empty($name)) {
            $name = $this->dbName();
        }
        $filename = $path . DIRECTORY_SEPARATOR . $name . '.sql';
        if (file_exists($filename)) {
            @unlink($filename);
        }
        $tables = $this->getTableList();

        // create database sql
        $this->saveFile($filename, $this->create()->buildSql() . ';');

        $n = 0;
        foreach ($tables as $table) {
            $table_name = '`' . $table . '`';

            $filename_table = $mode ? $path . $table . '.sql' : $filename;
            // create table sql
            $this->saveFile($filename_table, $this->getSql($this->query->query("SHOW CREATE TABLE " . $table_name)) . ';', 3);
            // insert data sql
            $tmp = $this->query->table($table)->count();
            $m   = 0;
            while ($tmp > 0) {
                $m++;
                $filename_data = $mode == 2 ? $this->filePath($path . $table) . 'data_' . $m . '.sql' : $filename;
                $data          = $this->query->table($table)->limit($limit)->select();
                foreach ($data as $d) {
                    $this->saveFile($filename_data, $this->buildDataSql($table_name, $d));
                }
                $tmp = $tmp - $limit;
            }
            $n++;
        }
        return true;
    }

    private function filePath($path)
    {
        $path = substr($path, -1) != '/' ? $path . '/' : $path;
        if (!file_exists($path)) {
            if (!mkdir($path, 0700, true)) {
                return null;
            }
        }
        return $path;
    }

    private function saveFile($filename, $sql, $blank = 0)
    {
        $fp = fopen($filename, 'a+');
        if (flock($fp, LOCK_EX)) {
            while ($blank > 0) {
                fwrite($fp, "\r\n");
                $blank = $blank - 1;
            }
            fwrite($fp, $sql . "\r\n");
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }

    private function getSql($result)
    {
        foreach ($result as $r) {
            $n = 0;
            foreach ($r as $t) {
                $n++;
                if ($n == 2) {
                    return $t;
                }
            }
        }
        return "";
    }

    private function buildDataSql($table, $data)
    {
        $values = "";
        $n      = 0;
        foreach ($data as $d) {
            if ($n > 0) {
                $values .= ",";
            }
            if (!is_null($d)) {
                $values .= "'" . $d . "'";
            } else {
                $values .= 'null';
            }
            $n++;
        }

        return Sql::getSql('insert.data', [
            'table_name' => $table,
            'values'     => $values
        ]);
    }
}