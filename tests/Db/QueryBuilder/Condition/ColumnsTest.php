<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\QueryBuilder\Condition\Columns;

/**
 * @group db
 */
final class ColumnsTest extends TestCase
{
    public function testConstructor(): void
    {
        $condition = new Columns(['expired' => false, 'active' => true]);

        $this->assertSame(['expired' => false, 'active' => true], $condition->values);
    }

    public function testFromArrayDefinition(): void
    {
        $condition = Columns::fromArrayDefinition('AND', ['expired' => false, 'active' => true]);

        $this->assertSame(['expired' => false, 'active' => true], $condition->values);
    }
}
