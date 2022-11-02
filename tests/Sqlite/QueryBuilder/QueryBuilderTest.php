<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Sqlite\QueryBuilder;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Tests\AbstractQueryBuilderTest;
use Yiisoft\Db\Tests\Support\Mock;

/**
 * @group db
 * @group sqlite
 */
final class QueryBuilderTest extends AbstractQueryBuilderTest
{
    protected array|string $columnQuoteCharacter = '`';
    protected Mock $mock;
    protected array|string $tableQuoteCharacter = '`';

    public function setUp(): void
    {
        parent::setUp();

        $this->mock = new Mock('sqlite');
    }

    public function tearDown(): void
    {
        parent::tearDown();

        unset($this->mock);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Sqlite\Provider\QueryBuilderProvider::buildConditions()
     *
     * @param array $condition
     * @param string $expected
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
