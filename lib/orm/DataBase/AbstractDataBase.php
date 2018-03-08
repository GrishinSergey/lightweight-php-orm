<?php

namespace orm\DataBase;


use orm\Exceptions\OrmRuntimeException;
use orm\Query\QueryMemento;


/**
 * Class AbstractDataBase
 * @package orm\DataBase
 */
abstract class AbstractDataBase
{

    /**
     * AbstractDataBase constructor.
     */
    public function __construct()
    {
        try {
            $settings = (object)get_object_vars($this);
            if (!isset($settings->dbname)) {
                $settings->dbname = (new \ReflectionClass($this))->getShortName();
            }
            QueryMemento::getInstance()
                ->addQueryData("dbname", $settings->dbname)
                ->addQueryData("dbtype", $settings->dbtype)
                ->addQueryData("username", $settings->user)
                ->addQueryData("password", $settings->password);
        } catch (\Exception $exception) {
            die(new OrmRuntimeException("Expected username and password for mysql server"));
        }
    }

}
