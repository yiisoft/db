<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Schema\Column\AbstractColumnFactory;

class ColumnFactory extends AbstractColumnFactory
{
    protected function getType(string $dbType, array &$info = []): string
    {
        return $this->mapType($this->typeMap, $dbType, $info)
            ?? (!empty($info['dimension'])
                ? ColumnType::ARRAY
                : ($this->isType($dbType) ? $dbType : ColumnType::STRING)
            );
    }

    protected function isDbType(string $dbType): bool
    {
        return match ($dbType) {
            'string', 'array' => false,
            'varchar' => true,
            default => $this->isType($dbType),
        };
    }
}
