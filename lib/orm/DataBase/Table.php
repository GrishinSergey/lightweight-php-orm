<?php


namespace orm\DataBase;


use orm\DataBase\fields\ForeignKey;
use orm\DataBase\fields\PrimaryKey;
use orm\Exceptions\ExceptionsMessages;
use orm\Exceptions\MigrationException;
use orm\Exceptions\QueryGenerationException;
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
     * @var \ReflectionClass
     */
    private $reflection_class = null;
    /**
     * @var array
     */
    private $table_fields = [];
    /**
     * @var string
     */
    protected $table_name = "";

    /**
     * Init table, set fields' types and set name of table
     */
    public function initTable()
    {
        $this->reflection_class = new \ReflectionClass($this);
        $this->table_fields = $this->getTableFields(true);
        if ($this->table_name == "") {
            $this->table_name = (new \ReflectionClass($this))->getShortName();
        }
    }

    /**
     * Migrate database from classes to sql and execute migration script.
     * @return bool true if migration successfully finished
     * @throws MigrationException
     */
    public function migrate()
    {
        try {
            $generator = $this->getQueryGeneratorInstance();
            (new QueryExecutor($generator->createDataBase(QueryMemento::getInstance()->getStorage()["dbname"]), []))
                    ->executeSql();
            (new QueryExecutor($generator->createTable($this->table_name, $this->table_fields), []))
                    ->executeSql();
            return true;
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
            $pdo_statement = $table->getQueryGeneratorInstance()->selectAll($table->table_name);
            $query_executor = new QueryExecutor($pdo_statement, []);
            return $table->fillObjectWithDataFromDataBase($called_class, $query_executor->select());
        } catch (\PDOException $e) {
            throw new QueryGenerationException($e->getMessage());
        }
    }

    /**
     * @param $from
     * @param int $to
     * @param array $data
     * @return array
     * @throws QueryGenerationException
     */
    public static function sliceByParams($from, $to = 0, $data = []): array
    {
        try {
            $called_class = get_called_class();
            $table = new $called_class();
            $pdo_statement = $table
                ->getQueryGeneratorInstance()
                ->slice($table->table_name, array_keys($data), $from, $to);
            $query_executor = new QueryExecutor($pdo_statement, $data);
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
            $pdo_statement = $table->getQueryGeneratorInstance()->selectByKeys($table->table_name, array_keys($data));
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
        return (0 === count($collection)) ? null : $collection[0];
    }

    /**
     * @return int
     * @throws QueryGenerationException
     */
    public function remove()
    {
        try {
            $keys = array_keys(array_filter($this->table_fields, function ($item) {
                return $item instanceof PrimaryKey;
            }));
            $queryExecutor = new QueryExecutor(
                $this->getQueryGeneratorInstance()->delete($this->table_name, $keys),
                [":" . $keys[0] => $this->{$keys[0]}]);
            return $queryExecutor->delete();
        } catch (\PDOException $e) {
            throw new QueryGenerationException($e->getMessage());
        }
    }

    /**
     * WARN: this method you will use this method at your own risk
     * @param $type
     * @param $query
     * @param $params
     * @return mixed
     * @throws QueryGenerationException
     */
    public function executeSql($type, $query, $params)
    {
        try {
            $queryStatement = $this->getQueryGeneratorInstance()->sql($query, array_keys($params));
            $executor = new QueryExecutor($queryStatement, $params);
            switch (strtolower($type)) {
                case "insert":
                case "update":
                    return $executor->insertOrUpdate();
                case "select":
                    return $executor->select();
                case "delete":
                    return $executor->delete();
                default:
                    throw new QueryGenerationException(ExceptionsMessages::unsupportedTypeOfQuery($type));
            }
        } catch (\PDOException $e) {
            throw new QueryGenerationException($e->getMessage());
        }
    }

    /**
     * @todo: not hardcode the name of primarykey. Search it in the table of fields, and then use
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
                if ($obj->{$key} instanceof ForeignKey) {
                    $obj->{$key} = $obj->{$key}->table::find(["id" => $value])[0];
                }
                else {
                    $obj->{$key} = $value;
                }
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
                $this->{$key} = $primary_key_new_value;
                break;
            }
        }
    }

    /**
     * @return QueryGeneratorInterface
     */
    private function getQueryGeneratorInstance()
    {
        $generator_name =
            "orm\\Query\\" .
            ucfirst(QueryMemento::getInstance()->getStorage()["dbtype"]) .
            "QueryGenerator";
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

}