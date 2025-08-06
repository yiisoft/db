<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition\Builder;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\QueryBuilder\Condition\BetweenColumns;
use Yiisoft\Db\QueryBuilder\Condition\Builder\BetweenColumnsBuilder;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 */
final class BetweenColumnsBuilderTest extends TestCase
{
    use TestTrait;

    public function testEscapeColumnName(): void
    {
        $db = $this->getConnection();

        $betweenColumnsCondition = new BetweenColumns(42, '1', '100');
        $params = [];

        $this->assertSame(
            ':qp0 BETWEEN [1] AND [100]',
            (new BetweenColumnsBuilder($db->getQueryBuilder()))->build($betweenColumnsCondition, $params)
        );

        $this->assertEquals([':qp0' => 42], $params);
    }
}
