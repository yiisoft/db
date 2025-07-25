<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition\Builder;

use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use Yiisoft\Db\QueryBuilder\Condition\Builder\LikeBuilder;
use Yiisoft\Db\QueryBuilder\Condition\Like;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 */
final class LikeBuilderTest extends TestCase
{
    use TestTrait;

    public function testOperatorException(): void
    {
        $db = $this->getConnection();

        $likeCondition = new Like('column', 'invalid', 'value');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid operator in like condition: "INVALID"');

        (new LikeBuilder($db->getQueryBuilder()))->build($likeCondition);
    }
}
