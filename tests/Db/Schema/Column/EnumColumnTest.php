<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Schema\Column;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Schema\Column\EnumColumn;

final class EnumColumnTest extends TestCase
{
    public function testEnumValues(): void
    {
        $column = new EnumColumn();

        $this->assertNull($column->getEnumValues());
        $this->assertSame($column, $column->enumValues(['positive', 'negative']));
        $this->assertSame(['positive', 'negative'], $column->getEnumValues());

        $column->enumValues([]);

        $this->assertSame([], $column->getEnumValues());
    }
}
