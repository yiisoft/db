<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\Schema\Column\AbstractColumnFactory;
use Yiisoft\Db\Schema\SchemaInterface;

class ColumnFactory extends AbstractColumnFactory
{
    protected function getType(string $dbType, array $info = []): string
    {
        return $this->isType($dbType) ? $dbType : SchemaInterface::TYPE_STRING;
    }

    protected function isType(string $dbType): bool
    {
        return match ($dbType) {
            SchemaInterface::TYPE_UUID,
            SchemaInterface::TYPE_CHAR,
            SchemaInterface::TYPE_STRING,
            SchemaInterface::TYPE_TEXT,
            SchemaInterface::TYPE_BINARY,
            SchemaInterface::TYPE_BOOLEAN,
            SchemaInterface::TYPE_TINYINT,
            SchemaInterface::TYPE_SMALLINT,
            SchemaInterface::TYPE_INTEGER,
            SchemaInterface::TYPE_BIGINT,
            SchemaInterface::TYPE_FLOAT,
            SchemaInterface::TYPE_DOUBLE,
            SchemaInterface::TYPE_DECIMAL,
            SchemaInterface::TYPE_MONEY,
            SchemaInterface::TYPE_DATETIME,
            SchemaInterface::TYPE_TIMESTAMP,
            SchemaInterface::TYPE_TIME,
            SchemaInterface::TYPE_DATE,
            SchemaInterface::TYPE_JSON => true,
            default => false,
        };
    }
}
