<?php

namespace orm\DataBase\fields;


/**
 * Class ForeignKey
 * @package orm\DataBase\fields
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
