<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\QueryBuilder\Conditions\Builder;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\QueryBuilder\Conditions\Builder\LikeConditionBuilder;
use Yiisoft\Db\QueryBuilder\Conditions\LikeCondition;
use Yiisoft\Db\Tests\Support\Mock;

/**
 * @group db
 */
final class LikeConditionBuilderTest extends TestCase
{
    public function testOperatorException(): void
    {
        $mock = new Mock();
        $likeCondition = new LikeCondition('column', 'invalid', 'value');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid operator in like condition: "INVALID"');
        (new LikeConditionBuilder($mock->queryBuilder()))->build($likeCondition);
    }
}
