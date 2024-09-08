<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Constant\ColumnType;

class ColumnFactory extends AbstractColumnFactory
{
    protected function getType(string $dbType, array $info = []): string
    {
        return $this->isType($dbType) ? $dbType : ColumnType::STRING;
    }

    protected function isDbType(string $dbType): bool
    {
        return $this->isType($dbType);
    }
}
