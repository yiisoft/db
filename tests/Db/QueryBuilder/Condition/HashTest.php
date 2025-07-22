<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\QueryBuilder\Condition\Hash;

/**
 * @group db
 */
final class HashTest extends TestCase
{
    public function testConstructor(): void
    {
        $hashCondition = new Hash(['expired' => false, 'active' => true]);

        $this->assertSame(['expired' => false, 'active' => true], $hashCondition->hash);
    }

    public function testFromArrayDefinition(): void
    {
        $hashCondition = Hash::fromArrayDefinition('AND', ['expired' => false, 'active' => true]);

        $this->assertSame(['expired' => false, 'active' => true], $hashCondition->hash);
    }
}
