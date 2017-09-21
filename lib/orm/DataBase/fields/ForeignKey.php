<?php

namespace orm\DataBase\fields;

/**
 * Class ForeignKey.
 */
class ForeignKey
{
    /**
     * @var
     */
    public $table;
    /**
     * @var
     */
    public $field;
    /**
     * @var
     */
    public $on_delete;
    /**
     * @var
     */
    public $on_update;
}
