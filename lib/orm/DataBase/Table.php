<?php

namespace orm\DataBase;


use orm\DataBase\fields\ForeignKey;
use orm\DataBase\fields\PrimaryKey;
use orm\Exceptions\QueryGenerationException;
use orm\Query\QueryExecutor;
use orm\Query\QueryMemento;


/**
 * Class Table
 * @package orm\DataBase
 */
abstract class Table
{

    /**
     * @var null
     */
    private $table_fields = null;
    /**
     * @var null
     */
    private $query_generator = null;
    /**
     * @var null
     */
    private $table_context = null;
    /**
     * @var string
     */
    protected $table_name = "";

    /**
     * Table constructor.
     * @param $table_context
     */
    public function __construct($table_context)
    {
        $this->table_context = $table_context;
        $this->setTableFields();
        $database = QueryMemento::createInstance()->getStorage();
        $generator_name = "orm\\Query\\" . ucfirst($database["dbtype"]) . "QueryGenerator";
        $this->query_generator = new $generator_name();
        if ($this->table_name == "") {
            $this->table_name = (new \ReflectionClass($this))->getShortName();
        }
    }

    /**
     * @throws QueryGenerationException
     */
    public function save()
    {
        try {
            $data = $this->getKeysForQuery();
            $pdo_statement = $this->query_generator->insertOrUpdateIfDuplicate(
                    $this->table_name,
                    array_keys($data));
            $execute_data = array_combine(
                array_map(function ($key) {return ":{$key}";}, array_keys($data)),
                array_values($data));
            $query_executor = new QueryExecutor($pdo_statement, $execute_data);
            $this->updateValueOfPrimaryKey($query_executor->insertOrUpdate());
        } catch (\PDOException $e) {
            throw new QueryGenerationException($e->getMessage());
        }
    }

    /**
     * @return mixed
     * @throws QueryGenerationException
     */
    public static function listAll()
    {
        try {
            $called_class = get_called_class();
            $table = new $called_class();
            $pdo_statement = $table->query_generator->selectAll($table->table_name);
            $query_executor = new QueryExecutor($pdo_statement, []);
            return $table->fillObjectWithDataFromDataBase(get_called_class(), $query_executor->select());
        } catch (\PDOException $e) {
            throw new QueryGenerationException($e->getMessage());
        }
    }

    /**
     * @param $data
     * @return array
     * @throws QueryGenerationException
     */
    public static function find($data)
    {
        try {
            $called_class = get_called_class();
            $table = new $called_class();
            $pdo_statement = $table->query_generator->selectByKeys($table->table_name, array_keys($data));
            $query_executor = new QueryExecutor($pdo_statement, $data);
            return $table->fillObjectWithDataFromDataBase(get_called_class(), $query_executor->select());
        } catch (\PDOException $e) {
            throw new QueryGenerationException($e->getMessage());
        }
    }

    /**
     * @param $data
     * @return mixed
     */
    public static function findFirst($data)
    {
        $collection = self::find($data);
        if (count($collection) === 0) {
            return null;
        }
        return $collection[0];
    }

    /**
     * @return int
     * @throws QueryGenerationException
     */
    public function remove()
    {
        try {
            $keys = array_filter((array)get_object_vars($this->table_context)["table_fields"], function ($item) {
                return $item instanceof PrimaryKey;
            });
            $pdo_statement = $this->query_generator->delete($this->table_name, array_keys($keys));
            $query_executor = new QueryExecutor($pdo_statement, [":" . array_keys($keys)[0] => $this->{array_keys($keys)[0]}]);
            $cunt_rows = $query_executor->delete();
            return $cunt_rows;
        }catch (\PDOException $e) {
            throw new QueryGenerationException($e->getMessage());
        }
    }

    /**
     * @param $class_name
     * @param $data
     * @return array
     */
    private function fillObjectWithDataFromDataBase($class_name, $data)
    {
        $result = [];
        foreach ($data as $item) {
            $obj = new $class_name();
            foreach ($item as $key => $value) {
                if ($obj->$key instanceof ForeignKey) {
                    $obj->$key = $obj->$key->table::find(["id" => $value])[0];
                }
                else {
                    $obj->$key = $value;
                }
            }
            $result[] = $obj;
        }
        return $result;
    }

    /**
     * @param $fields
     * @return array
     */
    private function getData($fields)
    {
        $tmp = [];
        foreach ($fields as $key => $field) {
            if (is_object($field)) {
                unset($field->table_fields);
                unset($field->query_generator);
                unset($field->table_context);
                unset($field->table_name);
                $tmp[$key] = $this->getData($field);
            }
            $tmp[$key] = $field;
        }
        return $tmp;
    }

    /**
     *
     */
    private function setTableFields()
    {
        $this->table_fields = (object) get_object_vars($this->table_context);
        unset($this->table_fields->query_generator);
        unset($this->table_fields->table_fields);
        unset($this->table_fields->table_context);
        unset($this->table_fields->table_name);
    }

    /**
     * @return array
     */
    private function getKeysForQuery()
    {
        $fieldsOfClassTable = get_object_vars($this->table_context);
        $keys = $this->getData($fieldsOfClassTable);

        $this->table_context = $fieldsOfClassTable["table_context"];
        $this->query_generator = $fieldsOfClassTable["query_generator"];
        $this->table_fields = $fieldsOfClassTable["table_fields"];
        $this->table_name = $fieldsOfClassTable["table_name"];

        $fields = $keys["table_fields"];
        unset($keys["table_fields"]);
        unset($keys["query_generator"]);
        unset($keys["table_context"]);
        unset($keys["table_name"]);

        $data = [];
        foreach ($fields as $key => $value) {
            if ($keys[$key] instanceof PrimaryKey) {
                continue;
            }
            elseif ($value instanceof ForeignKey) {
                $data[$key] = $keys[$key]->{$value->field};
            }
            else {
                $data[$key] = $keys[$key];
            }
        }
        return $data;
    }

    /**
     * @param $primary_key_new_value
     */
    private function updateValueOfPrimaryKey($primary_key_new_value)
    {
        foreach ($this->table_fields as $key => $value) {
            if ($value instanceof PrimaryKey) {
                $this->$key = $primary_key_new_value;
                break;
            }
        }
    }

}