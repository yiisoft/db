<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Schema\Column;

use LogicException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Schema\Column\EnumColumn;

final class EnumColumnTest extends TestCase
{
    public function testValues(): void
    {
        $column = new EnumColumn();

        $this->assertSame($column, $column->values(['positive', 'negative']));
        $this->assertSame(['positive', 'negative'], $column->getValues());
    }

    public function testWithoutValues(): void
    {
        $column = new EnumColumn();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Enum values have not been set.');
        $column->getValues();
    }
}
