<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\QueryBuilder\AbstractColumnDefinitionBuilder;
use Yiisoft\Db\Schema\Column\ColumnBuilder;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Tests\Support\Stub\ColumnDefinitionBuilder;
use Yiisoft\Db\Tests\Support\TestHelper;

/**
 * @group db
 */
final class ColumnDefinitionBuilderTest extends TestCase
{
    public function testBuildAlter(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();
        $qb = $db->getQueryBuilder();

        $cdb = new ColumnDefinitionBuilder($qb);

        $column = ColumnBuilder::integer();

        $this->assertEquals($cdb->build($column), $cdb->buildAlter($column));
    }

    public function testBuildEmptyDefaultForUuid(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();
        $qb = $db->getQueryBuilder();

        $cdb = new class ($qb) extends AbstractColumnDefinitionBuilder {
            protected function getDbType(ColumnInterface $column): string
            {
                return 'uuid';
            }
        };

        $column = ColumnBuilder::uuidPrimaryKey();

        $this->assertSame('uuid PRIMARY KEY', $cdb->build($column));
    }
}
