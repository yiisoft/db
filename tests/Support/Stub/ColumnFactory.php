<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Schema\Column\AbstractColumnFactory;

class ColumnFactory extends AbstractColumnFactory
{
    protected function getType(string $dbType, array $info = []): string
    {
        return $this->isType($dbType) ? $dbType : ColumnType::STRING;
    }

    protected function isDbType(string $dbType): bool
    {
        return $dbType === 'varchar'
            || $dbType !== 'string' && $this->isType($dbType);
    }
}
