<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Mysql;

use Yiisoft\Db\Schema;
use Yiisoft\Db\Contracts\ConnectionInterface;
use Yiisoft\Db\Mysql\ColumnSchemaBuilder;

class ColumnSchemaBuilderTest extends \Yiisoft\Db\Tests\ColumnSchemaBuilderTest
{
    protected ?string $driverName = 'mysql';

    /**
     * @param string $type
     * @param int $length
     * @return ColumnSchemaBuilder
     */
    public function getColumnSchemaBuilder($type, $length = null)
    {
        return new ColumnSchemaBuilder($type, $length, $this->getConnection());
    }

    /**
     * @return array
     */
    public function typesProvider()
    {
        return [
            ['integer UNSIGNED', Schema::TYPE_INTEGER, null, [
                ['unsigned'],
            ]],
            ['integer(10) UNSIGNED', Schema::TYPE_INTEGER, 10, [
                ['unsigned'],
            ]],
            ['integer(10) COMMENT \'test\'', Schema::TYPE_INTEGER, 10, [
                ['comment', 'test'],
            ]],
        ];
    }
}
