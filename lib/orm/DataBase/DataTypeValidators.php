<?php

namespace orm\DataBase;

use orm\Exceptions\ExceptionsMessages;
use orm\Exceptions\OrmRuntimeException;

/**
 * Class DataTypeValidators.
 */
class DataTypeValidators
{
    /**
     * @param $data
     * @param $size
     */
    public static function varchar($data, $size)
    {
        self::throwExceptionIfNotStringOrMoreThanTransmittedLength($data, $size);
    }

    /**
     * @param $instance
     * @param $className
     * @param $field
     *
     * @throws OrmRuntimeException
     */
    public static function foreignKey($instance, $className, $field)
    {
        if (!$instance instanceof $className || !property_exists($instance, $field)) {
            throw new OrmRuntimeException(ExceptionsMessages::unexpectedTypeOfValue($className, gettype($instance)));
        }
    }

    /**
     * @param $data
     * @param $size
     */
    public static function text($data, $size)
    {
        self::throwExceptionIfNotStringOrMoreThanTransmittedLength($data, $size);
    }

    /**
     * @TODO: realize
     */
    public static function time()
    {
    }

    /**
     * @TODO: realize
     */
    public static function date()
    {
    }

    /**
     * @TODO: realize
     */
    public static function dateTime()
    {
    }

    /**
     * @param $data
     * @param $size
     *
     * @throws OrmRuntimeException
     */
    private static function throwExceptionIfNotStringOrMoreThanTransmittedLength($data, $size)
    {
        if (!is_string($data)) {
            throw new OrmRuntimeException(ExceptionsMessages::unexpectedTypeOfValue('string', gettype($data)));
        }
        if (strlen($data) > $size) {
            throw new OrmRuntimeException(ExceptionsMessages::sizeOfValueBiggerThanSizeOfField(strlen($data), $size));
        }
    }
}
