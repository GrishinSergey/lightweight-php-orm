<?php


namespace orm\Migrations;


use orm\DataBase\fields\DateTime;
use orm\DataBase\fields\ForeignKey;
use orm\DataBase\fields\PrimaryKey;
use orm\DataBase\fields\StringField;
use orm\Exceptions\MigrationException;


class Migration
{

    private $table_fields = [];
    private $table_name = "";

    public function __construct($table_fields, $table_name)
    {
        $this->table_name = $table_name;
        $this->table_fields = $table_fields;
    }

    public function buildMigrationSqlCode()
    {
        $foreign_keys = "";
        $query = "drop table if exists `{$this->table_name}`; create table `{$this->table_name}` (\n";
        foreach ($this->table_fields as $key => $field) {
            if ($field instanceof PrimaryKey) {
                $query .= "`{$key}` {$field->type}({$field->size}) not null primary key auto_increment,\n";
            } elseif ($field instanceof ForeignKey) {
                $query .= "`{$key}` int not null,\n";
                $table = new $field->table();
                $reflection = new \ReflectionClass($table);
                $property = $reflection->getProperty("table_name");
                $property->setAccessible(true);
                $foreign_keys .= "alter table `{$this->table_name}` add constraint `{$key}_fk` foreign key (`{$key}`)" .
                                " references `{$property->getValue($table)}` (`{$field->field}`) on delete " .
                                "{$field->on_delete} on update {$field->on_update};\n";
                $property->setAccessible(false);
            } elseif ($field instanceof StringField) {
                $query .= "`{$key}` {$field->type}({$field->size}) not null,\n";
            } elseif ($field instanceof DateTime) {
                $query .= "`{$key}` {$field->type} not null,\n";
            } else {
                throw new MigrationException("Migration Exception");
            }
        }
        return substr($query, 0, -2) . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8;\n" . $foreign_keys;
    }

}