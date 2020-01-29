<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

class AnyValue extends CompareValue
{
    /**
     * @var self
     */
    private static $instance;

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
