<?php

namespace App\Core;

use BadMethodCallException;
use ErrorException;

abstract class Facade
{
    /**
     * @return mixed
     */
    protected static function target()
    {
        return null;
    }

    /**
     * @throws ErrorException
     */
    public static function __callStatic(string $name, array $arguments)
    {
        $target = static::target();

        if (!is_object($target)) {
            throw new ErrorException("Facade class does not implement target() static method correctly: returned non-object value");
        }

        if (!method_exists($target, $name)) {
            throw new BadMethodCallException("Static method $name() does not exist on class " . get_class($target));
        }

        return call_user_func_array([$target, $name], $arguments);
    }
}