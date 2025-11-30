<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Schema\Column;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Schema\Column\IntegerColumn;

final class IntegerColumnTest extends TestCase
{
    public function testMaxValueIsEndedByDot(): void
    {
        if (PHP_INT_SIZE === 8) {
            $value = '9223372036854775807.';
            $expectedValue = 9223372036854775807;
        } else {
            $value = '2147483647.';
            $expectedValue = 2147483647;
        }

        $column = new IntegerColumn();

        $result = $column->phpTypecast($value);

        $this->assertSame($expectedValue, $result);
    }
}
