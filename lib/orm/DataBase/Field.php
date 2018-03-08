<?php

namespace orm\DataBase;

use orm\DataBase\fields\DateTime;
use orm\DataBase\fields\ForeignKey;
use orm\DataBase\fields\Number;
use orm\DataBase\fields\PrimaryKey;
use orm\DataBase\fields\StringField;

/**
 * Class Field
 * Contains methods for describing fields and types in database
 * for migrations and controlling types and references in database.
 */
class Field
{
    /**
     * @param string $type            type of number field (int, float, double)
     * @param int    $size            size of field
     * @param string $attribute       attribute of field (binary, unsigned)
     * @param bool   $auto_increment, auto_increment flag
     *
     * @return \orm\DataBase\fields\Number
     */
    public static function number($type = 'int', $size = 10, $attribute = '', $auto_increment = false)
    {
        $obj = new Number();
        $obj->type = $type;
        $obj->size = $size;
        $obj->attribute = $attribute;
        $obj->auto_increment = $auto_increment;

        return $obj;
    }

    /**
     * describe PRIMARY KEY with AUTO_INCREMENT.
     *
     * @return PrimaryKey
     */
    public static function primaryKey()
    {
        $obj = new PrimaryKey();
        $obj->type = 'int';
        $obj->size = 10;
        $obj->auto_increment = true;
        $obj->primary_key = true;

        return $obj;
    }

    /**
     * REFERENCES:
     *     ON DELETE (RESTRICT, CASCADE, NO ACTION, SET NULL)
     *     ON UPDATE (RESTRICT, CASCADE, NO ACTION, SET NULL).
     *
     * @param string $table      -- name of table for foreign key
     * @param string $field      -- field in table for foreign key
     * @param array  $references -- array with settings of references for foreign key.
     *                           If it not sent, will be set from default values (restrict)
     *
     * @return ForeignKey
     */
    public static function foreignKey($table, $field, $references = ['on_delete' => 'restrict', 'on_update' => 'restrict'])
    {
        $obj = new ForeignKey();
        $obj->table = $table;
        $obj->field = $field;
        $obj->on_delete = $references['on_delete'];
        $obj->on_update = $references['on_update'];

        return $obj;
    }

    /**
     * String field with type varchar.
     *
     * @param int $length
     *
     * @return StringField
     */
    public static function varchar($length = 255)
    {
        return self::stringField('varchar', $length);
    }

    /**
     * String field with type varchar.
     *
     * @param int $length
     *
     * @return StringField
     */
    public static function text($length = 65535)
    {
        return self::stringField('text', $length);
    }

    /**
     * datetime field.
     *
     * @param string $format
     *
     * @return DateTime
     */
    public static function dateTime($format = '%Y-%M-%d %h:%m:%s')
    {
        return self::dateTimeUniversal('datetime', $format);
    }

    /**
     * date field.
     *
     * @param string $format
     *
     * @return DateTime
     */
    public static function date($format = '%Y-%M-%d')
    {
        return self::dateTimeUniversal('date', $format);
    }

    /**
     * time field.
     *
     * @param string $format
     *
     * @return DateTime
     */
    public static function time($format = '%h:%m:%s')
    {
        return self::dateTimeUniversal('time', $format);
    }

    /**
     * describe universal field of datetime.
     *
     * @param $type
     * @param $format
     *
     * @return DateTime
     */
    private static function dateTimeUniversal($type, $format)
    {
        $obj = new DateTime();
        $obj->type = $type;
        $obj->format = $format;

        return $obj;
    }

    /**
     * describe universal string field.
     *
     * @param $type
     * @param $size
     *
     * @return StringField
     */
    private static function stringField($type, $size)
    {
        $obj = new StringField();
        $obj->type = $type;
        $obj->size = $size;
        $obj->encoding = 'utf8_general_ci';

        return $obj;
    }
}
