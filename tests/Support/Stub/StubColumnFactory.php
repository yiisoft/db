<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Schema\Column\AbstractColumnFactory;
use Yiisoft\Db\Syntax\ColumnDefinitionParserInterface;

class StubColumnFactory extends AbstractColumnFactory
{
    protected function getType(string $dbType, array $info = []): string
    {
        if (!empty($info['dimension'])) {
            return ColumnType::ARRAY;
        }

        return $this->isType($dbType) ? $dbType : ColumnType::STRING;
    }

    protected function isDbType(string $dbType): bool
    {
        return match ($dbType) {
            'string', 'array' => false,
            'varchar' => true,
            default => $this->isType($dbType),
        };
    }

    protected function columnDefinitionParser(): ColumnDefinitionParserInterface
    {
        return new StubColumnDefinitionParser();
    }
}
