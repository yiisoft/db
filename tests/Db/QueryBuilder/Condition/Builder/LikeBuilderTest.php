<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition\Builder;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\QueryBuilder\Condition\Builder\LikeBuilder;
use Yiisoft\Db\QueryBuilder\Condition\Like;
use Yiisoft\Db\QueryBuilder\Condition\LikeMode;
use Yiisoft\Db\Tests\Support\Stringable;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 */
final class LikeBuilderTest extends TestCase
{
    use TestTrait;

    #[TestWith(['%test%', LikeMode::Contains])]
    #[TestWith(['test%', LikeMode::StartsWith])]
    #[TestWith(['%test', LikeMode::EndsWith])]
    #[TestWith(['test', LikeMode::Custom])]
    public function testBuildWithContainsMode(string $expected, LikeMode $mode): void
    {
        $likeCondition = new Like('column', 'test', mode: $mode);
        $likeBuilder = new LikeBuilder($this->getConnection()->getQueryBuilder());

        $params = [];
        $likeBuilder->build($likeCondition, $params);

        $this->assertCount(1, $params);

        /** @var Param $param */
        $param = reset($params);
        $this->assertSame($expected, $param->value);
        $this->assertSame(DataType::STRING, $param->type);
    }

    #[TestWith(['%test%', 'test', true])]
    #[TestWith(['%te\_st%', 'te_st', true])]
    #[TestWith(['%te_st%', 'te_st', false])]
    public function testStringableValue(string $expected, string $value, bool $escape): void
    {
        $condition = new Like('column', new Stringable($value), escape: $escape);
        $builder = new LikeBuilder($this->getConnection()->getQueryBuilder());

        $params = [];
        $builder->build($condition, $params);

        $this->assertCount(1, $params);

        /** @var Param $param */
        $param = reset($params);
        $this->assertSame($expected, $param->value);
        $this->assertSame(DataType::STRING, $param->type);
    }
}
