<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Oracle\QueryBuilder;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Tests\AbstractQueryBuilderTest;
use Yiisoft\Db\Tests\Support\Mock;

/**
 * @group db
 * @group oracle
 */
final class QueryBuilderTest extends AbstractQueryBuilderTest
{
    protected array|string $columnQuoteCharacter = '"';
    protected Mock $mock;
    protected array|string $tableQuoteCharacter = '"';

    public function setUp(): void
    {
        parent::setUp();

        $this->mock = new Mock('oci');
    }

    public function tearDown(): void
    {
        parent::tearDown();

        unset($this->mock);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Oracle\Provider\QueryBuilderProvider::buildConditions()
     */
    public function testBuild(
        array|ExpressionInterface|string $conditions,
        string $expected,
        array $expectedParams = []
    ): void {
        parent::testBuild($conditions, $expected, $expectedParams);
    }
}
