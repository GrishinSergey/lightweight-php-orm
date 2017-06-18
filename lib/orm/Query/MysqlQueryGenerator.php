<?php

namespace orm\Query;


class MysqlQueryGenerator implements QueryGeneratorInterface
{

    private $databaseData = [];
    private $pdo = null;

    public function __construct()
    {
        $this->databaseData = QueryMemento::createInstance()->getStorage();
        $this->pdo = PdoAdapter::getInstance()->getPdoObject();
    }

    public function insertOrUpdateIfDuplicate($table, $keys)
    {
        $preparedData = ""; $preparedFields = ""; $preparedQuery = "";
        foreach ($keys as $key) {
            $preparedData .= ":{$key}, ";
            $preparedFields .= $key . ", ";
            $preparedQuery .= "{$key} = :{$key}, ";
        }
        $update = substr($preparedQuery, 0, -2);
        $fields = substr($preparedFields, 0, -2);
        $values = substr($preparedData, 0, -2);
        $sql = "insert into {$table} ({$fields}) values ({$values}) on duplicate key update {$update}, id=LAST_INSERT_ID(id)";
        return $this->pdo->prepare($sql);
    }

    public function delete($table, $keys)
    {
        $where = implode(" and ", array_map(function ($key) {return "{$key} = :{$key}";}, $keys));
        return $this->pdo->prepare("delete from {$table} where {$where}");
    }

    public function selectByKeys($table, $keys)
    {
        $where = implode(" and ", array_map(function ($key) {return "{$key} = :{$key}";}, $keys));
        return $this->pdo->prepare("select * from {$table} where {$where}");
    }

    public function selectAll($table)
    {
        return $this->pdo->prepare("select * from {$table}");
    }
}