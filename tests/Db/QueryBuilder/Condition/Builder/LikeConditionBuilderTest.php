<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition\Builder;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\QueryBuilder\Condition\Builder\LikeConditionBuilder;
use Yiisoft\Db\QueryBuilder\Condition\LikeCondition;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class LikeConditionBuilderTest extends TestCase
{
    use TestTrait;

    public function testOperatorException(): void
    {
        $db = $this->getConnection();

        $likeCondition = new LikeCondition('column', 'invalid', 'value');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid operator in like condition: "INVALID"');

        (new LikeConditionBuilder($db->getQueryBuilder()))->build($likeCondition);
    }
}
