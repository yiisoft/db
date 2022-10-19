<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Query\Helper;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\Quoter;
use Yiisoft\Db\Tests\Support\Mock;

final class QueryHelperTest extends TestCase
{
    /**
     * @dataProvider \Yiisoft\Db\Tests\Query\Helper\QueryHelperProviders::tablesNameDataProvider
     */
    public function testCleanUpTableNames(array $tables, string $prefixDatabase, array $expected): void
    {
        $this->assertEquals(
            $expected,
            Mock::queryHelper()->cleanUpTableNames($tables, new Quoter('"', '"'))
        );
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Query\Helper\QueryHelperProviders::filterConditionDataProvider
     */
    public function testFilterCondition(array|string $condition, array|string $expected): void
    {
        $this->assertEquals($expected, Mock::queryHelper()->filterCondition($condition));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Query\Helper\QueryHelperProviders::normalizeOrderByProvider
     */
    public function testNormalizeOrderBy(array|string|Expression $columns, array|string $expected): void
    {
        $this->assertEquals($expected, Mock::queryHelper()->normalizeOrderBy($columns));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Query\Helper\QueryHelperProviders::normalizeSelectProvider
     */
    public function testNormalizeSelect(array|string|Expression $columns, array|string $expected): void
    {
        $this->assertEquals($expected, Mock::queryHelper()->normalizeSelect($columns));
    }
}
