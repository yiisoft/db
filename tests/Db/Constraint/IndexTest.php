<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Constraint;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constraint\Index;

/**
 * @group db
 */
final class IndexTest extends TestCase
{
    public function testDefaults(): void
    {
        $index = new Index();

        $this->assertSame('', $index->name);
        $this->assertSame([], $index->columnNames);
        $this->assertFalse($index->isUnique);
        $this->assertFalse($index->isPrimaryKey);
    }

    public function testValues(): void
    {
        $index = new Index(
            'pk',
            ['id'],
            true,
            true,
        );

        $this->assertSame('pk', $index->name);
        $this->assertSame(['id'], $index->columnNames);
        $this->assertTrue($index->isUnique);
        $this->assertTrue($index->isPrimaryKey);
    }
}
