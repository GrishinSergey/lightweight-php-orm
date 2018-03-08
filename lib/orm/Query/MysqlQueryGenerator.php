<?php

namespace orm\Query;


use orm\DataBase\fields\DateTime;
use orm\DataBase\fields\ForeignKey;
use orm\DataBase\fields\Number;
use orm\DataBase\fields\PrimaryKey;
use orm\DataBase\fields\StringField;
use orm\Exceptions\ExceptionsMessages;
use orm\Exceptions\MigrationException;

class MysqlQueryGenerator implements QueryGeneratorInterface
{

    private $pdo = null;

    public function __construct()
    {
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

    public function slice($table, $keys, $from, $to)
    {
        $where = implode(" and ", array_map(function ($key) {return "{$key} = :{$key}";}, $keys));
        $limit = "{$from}" . ($to !== 0) ? ", {$to} " : " ";
        return $this->pdo->prepare("select * from {$table} where {$where} limit {$limit}");
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

    public function sql($string, $params)
    {
        return $this->pdo->prepare($string, $params);
    }

    public function createDataBase($dbname)
    {
        return $this->pdo->prepare("create database if not exists `{$dbname}` default character set utf8 collate " .
                "utf8_general_ci;\nuse `{$dbname}`;\n");
    }

    public function createTable($table, $fields)
    {
        $query = "set foreign_key_checks = 0;\n";
        $foreign_keys = "";
        $query .= "drop table if exists `{$table}`; create table `{$table}` (\n";
        foreach ($fields as $key => $field) {
            if ($field instanceof PrimaryKey) {
                $query .= "`{$key}` {$field->type}({$field->size}) not null primary key auto_increment,\n";
            } elseif ($field instanceof ForeignKey) {
                $query .= "`{$key}` int not null,\n";
                $current_table = new $field->table();
                $reflection = new \ReflectionClass($current_table);
                $property = $reflection->getProperty("table_name");
                $property->setAccessible(true);
                $foreign_keys .= "alter table `{$table}` add constraint `{$key}_fk` foreign key (`{$key}`)" .
                        " references `{$property->getValue($current_table)}` (`{$field->field}`) on delete " .
                        "{$field->on_delete} on update {$field->on_update};\n";
                $property->setAccessible(false);
            } elseif ($field instanceof StringField) {
                $query .= "`{$key}` {$field->type}({$field->size}) not null,\n";
            } elseif ($field instanceof DateTime) {
                $query .= "`{$key}` {$field->type} not null,\n";
            } elseif ($field instanceof Number) {
                $auto_increment = ($field->auto_increment) ? "auto_increment" : "";
                $query .= "`{$key}` {$field->type}({$field->size}) {$field->attribute} not null {$auto_increment},\n";
            } else {
                throw new MigrationException(ExceptionsMessages::unsupportedTypeOfField(gettype($field)));
            }
        }
        return $this->pdo->prepare(substr($query, 0, -2) . "\n) engine=InnoDB default charset=utf8;\n" .
                $foreign_keys . "set foreign_key_checks = 1;\n");
    }
}