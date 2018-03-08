<?php

namespace orm\Exceptions;


abstract class ExceptionsMessages
{

    public static function unexpectedTypeOfValue($expectedType, $receivedType)
    {
        return "Unexpected type of value. Expected <b>{$expectedType}</b> but received <b>{$receivedType}</b>";
    }

    public static function sizeOfValueBiggerThanSizeOfField($valueSize, $fieldSize)
    {
        return "Size of value bigger than size of field. Expected <b>{$valueSize}</b> symbols but received <b>{$fieldSize}</b>";
    }

    public static function unsupportedTypeOfField($type)
    {
        return "{$type} is unsupported type of field";
    }

    public static function unsupportedTypeOfQuery($type)
    {
        return "{$type} is unsupported type of query";
    }

}