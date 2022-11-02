<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Tests\Support\DbHelper;

abstract class AbstractQueryBuilderTest extends TestCase
{
    public function testBuild(
        array|ExpressionInterface|string $conditions,
        string $expected,
        array $expectedParams = []
    ): void {
        $query = $this->mock->query()->where($conditions);
        [$sql, $params] = $this->mock
            ->queryBuilder($this->columnQuoteCharacter, $this->tableQuoteCharacter)
            ->build($query);

        $this->assertSame(
            'SELECT *' . (
                empty($expected) ? '' : ' WHERE ' . DbHelper::replaceQuotes(
                    $expected,
                    $this->mock->getDriverName(),
                )
            ),
            $sql,
        );
        $this->assertSame($expectedParams, $params);
    }
}
