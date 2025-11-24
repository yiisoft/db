<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Schema\Column;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Schema\Column\EnumColumn;

final class EnumColumnTest extends TestCase
{
    public function testValues(): void
    {
        $column = new EnumColumn();

        $this->assertNull($column->getValues());
        $this->assertSame($column, $column->values(['positive', 'negative']));
        $this->assertSame(['positive', 'negative'], $column->getValues());

        $column->values([]);

        $this->assertSame([], $column->getValues());
    }
}
