<?php

declare(strict_types=1);

namespace Yiisoft\Db\TestUtility;

final class AnyValue extends CompareValue
{
    private static self $instance;

    public static function getInstance(): self
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
