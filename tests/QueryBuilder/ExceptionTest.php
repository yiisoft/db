<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\QueryBuilder;

use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Tests\Support\Mock;
use Yiisoft\Db\Tests\Support\Stubs\ExpressionStub;

/**
 * @group db
 */
final class ExceptionTest extends TestCase
{
    private QueryBuilderInterface $queryBuilder;
    private Mock $mock;

    public function setUp(): void
    {
        parent::setUp();

        $this->mock = new Mock();
        $this->queryBuilder = $this->mock->queryBuilder();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        unset($this->queryBuilder, $this->mock);
    }

    public function testBuilColumnStringException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Column name must be a string or an instance of ExpressionInterface.'
        );

        $this->queryBuilder->buildColumns([1]);
    }

    public function testBuildJoinException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'A join clause must be specified as an array of join type, join table, and optionally join condition.'
        );

        $params = [];
        $this->queryBuilder->buildJoin([1], $params);
    }

    public function testGetExpressionBuilderException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expression of class Yiisoft\Db\Tests\Support\Stubs\ExpressionStub can not be built in Yiisoft\Db\QueryBuilder\DQLQueryBuilder'
        );

        $this->queryBuilder->getExpressionBuilder(new ExpressionStub());
    }
}
