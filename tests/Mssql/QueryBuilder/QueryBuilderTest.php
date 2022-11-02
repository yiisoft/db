<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Mssql\QueryBuilder;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Tests\AbstractQueryBuilderTest;
use Yiisoft\Db\Tests\Support\Mock;

/**
 * @group db
 * @group mssql
 */
final class QueryBuilderTest extends AbstractQueryBuilderTest
{
    protected array|string $columnQuoteCharacter = ['[', ']'];
    protected Mock $mock;
    protected array|string $tableQuoteCharacter = ['[', ']'];

    public function setUp(): void
    {
        parent::setUp();

        $this->mock = new Mock('sqlsrv');
    }

    public function tearDown(): void
    {
        parent::tearDown();

        unset($this->mock);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Mssql\Provider\QueryBuilderProvider::buildConditions()
     *
     * @param array $condition
     * @param array $params
     */
    public function testBuild(
        array|ExpressionInterface|string $conditions,
        string $expected,
        array $expectedParams = []
    ): void {
        parent::testBuild($conditions, $expected, $expectedParams);
    }
}
