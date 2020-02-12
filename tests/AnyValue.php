<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

class AnyValue extends CompareValue
{
    /**
     * @var self
     */
    private static ?AnyValue $instance = null;

    public static function getInstance(): AnyValue
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
