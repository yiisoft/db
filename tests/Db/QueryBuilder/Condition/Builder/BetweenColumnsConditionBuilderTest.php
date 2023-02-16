<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition\Builder;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\QueryBuilder\Condition\BetweenColumnsCondition;
use Yiisoft\Db\QueryBuilder\Condition\Builder\BetweenColumnsConditionBuilder;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class BetweenColumnsConditionBuilderTest extends TestCase
{
    use TestTrait;

    public function testEscapeColumnName(): void
    {
        $db = $this->getConnection();

        $betweenColumnsCondition = new BetweenColumnsCondition(42, 'BETWEEN', '1', '100');
        $params = [];

        $this->assertSame(
            ':qp0 BETWEEN [1] AND [100]',
            (new BetweenColumnsConditionBuilder($db->getQueryBuilder()))->build($betweenColumnsCondition, $params)
        );

        $this->assertEquals([':qp0' => 42], $params);
    }
}
