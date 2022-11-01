<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\QueryBuilder\Conditions;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\QueryBuilder\Conditions\HashCondition;

/**
 * @group db
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
