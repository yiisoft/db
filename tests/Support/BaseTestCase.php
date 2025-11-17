<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

use PHPUnit\Framework\TestCase;

abstract class BaseTestCase extends TestCase
{
    protected function replaceQuotes(string $sql): string
    {
        return str_replace(['[[', ']]'], ['[', ']'], $sql);
    }
}
