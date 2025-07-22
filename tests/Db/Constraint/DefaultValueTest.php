<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Constraint;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constraint\DefaultValue;

/**
 * @group db
 */
final class DefaultValueTest extends TestCase
{
    public function testDefaults(): void
    {
        $defaultValue = new DefaultValue();

        $this->assertSame('', $defaultValue->name);
        $this->assertSame([], $defaultValue->columnNames);
        $this->assertNull($defaultValue->value);
    }

    public function testValues(): void
    {
        $defaultValue = new DefaultValue(
            'column_default',
            ['column_name'],
            '10'
        );

        $this->assertSame('column_default', $defaultValue->name);
        $this->assertSame(['column_name'], $defaultValue->columnNames);
        $this->assertSame('10', $defaultValue->value);
    }
}
