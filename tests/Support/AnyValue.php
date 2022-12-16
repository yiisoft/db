<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

final class AnyValue extends CompareValue
{
    private static self|null $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
