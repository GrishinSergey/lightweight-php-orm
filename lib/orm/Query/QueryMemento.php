<?php

/**
 * @TODO: think about renaming class, or/and create another class with same tasks, and remove this one.
 * @TODO: think about magic setter for fields of storage and make storage an object
 */

namespace orm\Query;

/**
 * Class QueryMemento (Realized as patterns Singleton and FluentInterface). Class For storing settings for PDO instance.
 */
class QueryMemento
{
    /**
     * @var QueryMemento, static field with instance of class
     */
    private static $instance = null;

    /**
     * @var array, storage of settings for PDO instance
     */
    private $storage = [];

    /**
     * Create only one instance of QueryMemento.
     *
     * @return QueryMemento, an instance of class QueryMemento
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Add setting to storage.
     *
     * @param $key string, key in storage for placing setting
     * @param $value string, setting of PDO connection
     *
     * @return $this
     */
    public function addQueryData($key, $value)
    {
        $this->storage[$key] = $value;

        return $this;
    }

    /**
     * Getter for storage.
     *
     * @return array, storage with all settings
     */
    public function getStorage()
    {
        return $this->storage;
    }
}
