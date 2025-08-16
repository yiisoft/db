<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\QueryBuilder\Condition\AndX;

/**
 * @group db
 */
final class AndXTest extends TestCase
{
    public function testConstructor(): void
    {
        $andCondition = new AndX(['a' => 1], ['b' => 2]);

        $this->assertSame([['a' => 1], ['b' => 2]], $andCondition->expressions);
    }
}
