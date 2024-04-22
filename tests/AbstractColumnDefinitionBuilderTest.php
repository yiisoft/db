<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\QueryBuilder\ColumnDefinitionBuilder;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Tests\Support\TestTrait;

abstract class AbstractColumnDefinitionBuilderTest extends TestCase
{
    use TestTrait;

    /** @dataProvider \Yiisoft\Db\Tests\Provider\ColumnDefinitionBuilderProvider::build */
    public function testBuild(string $expected, ColumnInterface $column): void
    {
        $db = $this->getConnection();

        $builder = new ColumnDefinitionBuilder($db->getQueryBuilder());

        $this->assertSame($expected, $builder->build($column));
    }
}
