<?php

namespace orm\Query;

use orm\Exceptions\OrmRuntimeException;
use PDO;

class PdoAdapter
{
    private $pdo = null;

    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct()
    {
        try {
            $database = QueryMemento::getInstance()->getStorage();
            $this->pdo = new PDO(
                "{$database['dbtype']}:host=localhost;dbname={$database['dbname']}",
                $database['username'],
                $database['password'],
                [
                    PDO::MYSQL_ATTR_INIT_COMMAND => "set names 'utf8'",
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                ]
            );
        } catch (\PDOException $PDOException) {
            throw new OrmRuntimeException($PDOException->getMessage());
        }
    }

    public function getPdoObject()
    {
        return $this->pdo;
    }
}
