<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\QueryBuilder\Conditions\Builder;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\QueryBuilder\Conditions\Builder\LikeConditionBuilder;
use Yiisoft\Db\QueryBuilder\Conditions\LikeCondition;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
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
