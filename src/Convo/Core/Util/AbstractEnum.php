<?php

namespace Convo\Core\Util;

abstract class AbstractEnum
{
    public static function getValues()
    {
        $reflection = new \ReflectionClass(get_called_class());

        return $reflection->getConstants();
    }
}