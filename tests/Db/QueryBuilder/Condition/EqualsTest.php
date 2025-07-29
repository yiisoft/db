<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\QueryBuilder\Condition\Equals;

use function PHPUnit\Framework\assertSame;

/**
 * @group db
 */
final class EqualsTest extends TestCase
{
    public function testFromArrayDefinition(): void
    {
        $condition = Equals::fromArrayDefinition('EQUALS', ['id', 25]);

        assertSame('id', $condition->column);
        assertSame(25, $condition->value);
    }
}
