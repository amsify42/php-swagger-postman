<?php
namespace Amsify42\PhpSwaggerPostman\Swagger;

class Data
{
    public static function isInt($value)
    {
        return (is_int($value) || ctype_digit($value))? true: false;
    }
}
