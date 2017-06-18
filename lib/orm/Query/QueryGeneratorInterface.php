<?php

namespace orm\Query;


/**
 * Interface QueryGeneratorInterface. Base API for all *QueryGenerator classes
 * @package orm\Query
 */
interface QueryGeneratorInterface
{

    /**
     * Signature of insert|update query generator
     * @param $table string, name of table -- where data will saved or updated
     * @param $keys array, fields of table
     * @return \PDOStatement, prepared query
     */
    public function insertOrUpdateIfDuplicate($table, $keys);

    /**
     * Signature of delete query generator
     * @param $table string, name of table -- from data will be removed
     * @param $keys array, fields of table
     * @return \PDOStatement, prepared query
     */
    public function delete($table, $keys);

    /**
     * Signature of selectByKeys query generator
     * @param $table string, name of table -- where data will be found
     * @param $keys array, fields of table
     * @return \PDOStatement, prepared query
     */
    public function selectByKeys($table, $keys);

    /**
     * Signature of selectAll query generator
     * @param $table string, name of table -- from all data will be selected
     * @return \PDOStatement, prepared query
     */
    public function selectAll($table);

}
