<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\QueryBuilder\Condition\HashCondition;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class HashConditionTest extends TestCase
{
    public function testConstructor(): void
    {
        $hashCondition = new HashCondition(['expired' => false, 'active' => true]);

        $this->assertSame(['expired' => false, 'active' => true], $hashCondition->getHash());
    }

    public function testFromArrayDefinition(): void
    {
        $hashCondition = HashCondition::fromArrayDefinition('AND', ['expired' => false, 'active' => true]);

        $this->assertSame(['expired' => false, 'active' => true], $hashCondition->getHash());
    }
}
