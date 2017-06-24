<?php


namespace orm\DataBase;


use orm\DataBase\fields\ForeignKey;
use orm\DataBase\fields\PrimaryKey;
use orm\Exceptions\MigrationException;
use orm\Exceptions\QueryGenerationException;
use orm\Migrations\Migration;
use orm\Query\PdoAdapter;
use orm\Query\QueryExecutor;
use orm\Query\QueryGeneratorInterface;
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
    private $reflection_class = null;
    /**
     * @var null
     */
    private $table_fields = null;
    /**
     * @var string
     */
    protected $table_name = "";

    public function initTable()
    {
        $this->reflection_class = new \ReflectionClass($this);
        $this->table_fields = $this->getTableFields(true);
        $this->initTableName();
    }

    public function migrate()
    {
        try {
            (new QueryExecutor(PdoAdapter::getInstance()
                ->getPdoObject()->prepare(
                    (new Migration($this->table_fields, $this->table_name))
                        ->buildMigrationSqlCode()), []))
                ->executeSql();
        } catch (\PDOException $e) {
            throw new MigrationException($e->getMessage());
        }
    }

    /**
     * @throws QueryGenerationException
     */
    public function save()
    {
        try {
            $data = $this->getTableFields();
            $pdo_statement = $this
                    ->getQueryGeneratorInstance()
                    ->insertOrUpdateIfDuplicate($this->table_name, array_keys($data));
            array_combine(
                    array_map(function ($key) {return ":{$key}";}, array_keys($data)),
                    array_values($data));
            $query_executor = new QueryExecutor($pdo_statement, array_combine(
                    array_map(function ($key) {return ":{$key}";}, array_keys($data)),
                    array_values($data)));
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
            $pdo_statement = $table
                    ->getQueryGeneratorInstance()
                    ->selectAll($table->table_name);
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
            $pdo_statement = $table
                    ->getQueryGeneratorInstance()
                    ->selectByKeys($table->table_name, array_keys($data));
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
        return (count($collection) === 0) ? null : $collection[0];
    }

    /**
     * @return int
     * @throws QueryGenerationException
     */
    public function remove()
    {
        try {
            $keys = array_filter($this->table_fields, function ($item) {
                return $item instanceof PrimaryKey;
            });
            $pdo_statement = $this
                    ->getQueryGeneratorInstance()
                    ->delete($this->table_name, array_keys($keys));
            $query_executor = new QueryExecutor(
                    $pdo_statement,
                    [":" . array_keys($keys)[0] => $this->{array_keys($keys)[0]}]);
            return $query_executor->delete();
        } catch (\PDOException $e) {
            throw new QueryGenerationException($e->getMessage());
        }
    }

    /**
     * @todo: not hardcoding the name primarykey. Search it in the table of fields, and then use
     *
     *
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
                $obj->$key = ($obj->$key instanceof ForeignKey) ? $obj->$key->table::find(["id" => $value])[0] : $value;
            }
            $result[] = $obj;
        }
        return $result;
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

    /**
     * @return QueryGeneratorInterface
     */
    private function getQueryGeneratorInstance()
    {
        $generator_name = "orm\\Query\\" . ucfirst(QueryMemento::createInstance()
                ->getStorage()["dbtype"]) . "QueryGenerator";
        return new $generator_name();
    }

    /**
     * @param bool $addPrimaryKeyFlag
     * @return array
     */
    private function getTableFields($addPrimaryKeyFlag = false)
    {
        $storage = [];
        foreach ($this->reflection_class->getProperties(\ReflectionProperty::IS_PUBLIC) as $field) {
            if (!$addPrimaryKeyFlag && $this->{$field->name} instanceof PrimaryKey) {
                continue;
            }
            else if (!$addPrimaryKeyFlag && $this->table_fields[$field->name] instanceof ForeignKey) {
                $primary_key_name = array_keys(array_filter($this->{$field->name}->table_fields, function ($item) {
                    return $item instanceof PrimaryKey;
                }));
                $storage[$field->name] = $this->{$field->name}->{$primary_key_name[0]};
            }
            else {
                $storage[$field->name] = $this->{$field->name};
            }
        }
        return $storage;
    }

    private function initTableName()
    {
        if ($this->table_name == "") {
            $this->table_name = (new \ReflectionClass($this))->getShortName();
        }
    }

}