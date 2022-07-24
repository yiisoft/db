<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use Stringable;
use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * TableName - abstraction for name of table in DataBase
 */
class TableName implements Stringable, TableNameInterface
{
    private const DELIMITER = '.';

    private ?string $serverName;
    private ?string $catalogName;
    private ?string $schemaName;
    private string|ExpressionInterface $tableName;

    private ?string $prefix;

    /**
     * @param string|ExpressionInterface $tableName
     * @param string|null $prefix
     * @param string|null $schemaName
     * @param string|null $catalogName
     * @param string|null $serverName
     *
     * @todo check tablePrefix
     */
    public function __construct(string|ExpressionInterface $tableName, ?string $prefix = null, ?string $schemaName = null, ?string $catalogName = null, ?string $serverName = null)
    {
        $this->tableName = $tableName;
        $this->prefix = $prefix;
        $this->schemaName = $schemaName;
        $this->catalogName = $catalogName;
        $this->serverName = $serverName;
    }

    public function getServerName(): ?string
    {
        return $this->serverName;
    }

    public function getCatalogName(): ?string
    {
        return $this->catalogName;
    }

    public function getSchemaName(): ?string
    {
        return $this->schemaName;
    }

    public function getTableName(): string|ExpressionInterface
    {
        return $this->tableName;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function __toString()
    {
        return implode(static::DELIMITER, array_filter([
            $this->serverName,
            $this->catalogName,
            $this->schemaName,
            $this->addPrefix($this->tableName),
        ]));
    }

    private function addPrefix(string $name): string
    {
        if (!str_contains($name, '{{')) {
            return $name;
        }

        $name = preg_replace('/{{(.*?)}}/', '\1', $name);

        return str_replace('%', $this->prefix, $name);
    }
}
