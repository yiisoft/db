<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition\Builder;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\QueryBuilder\Condition\BetweenColumnsCondition;
use Yiisoft\Db\QueryBuilder\Condition\Builder\BetweenColumnsConditionBuilder;
use Yiisoft\Db\QueryBuilder\Condition\Interface\ConditionInterface;
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

    public function testWrongConditionType(): void
    {
        $db = $this->getConnection();

        $wrongCondition = new class implements ConditionInterface {
            public static function fromArrayDefinition(string $operator, array $operands): ConditionInterface
            {
                return new self();
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('BetweenColumnsConditionBuilder can only be used with BetweenColumnsConditionInterface instance.');

        (new BetweenColumnsConditionBuilder($db->getQueryBuilder()))->build($wrongCondition);
    }
}
