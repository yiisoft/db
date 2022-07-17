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
     * @param string|null $schemaName
     * @param string|null $catalogName
     * @param string|null $serverName
     *
     * @todo check tablePrefix
     */
    public function __construct(string|ExpressionInterface $tableName, ?string $schemaName = null, ?string $catalogName = null, ?string $serverName = null)
    {
        $this->tableName = $tableName;
        $this->schemaName = $schemaName;
        $this->catalogName = $catalogName;
        $this->serverName = $serverName;
    }

    public function withPrefix(string $prefix): self
    {
        $new = clone $this;
        $new->prefix = $prefix;
        return $new;
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
            ($this->prefix ?? '') . $this->tableName,
        ]));
    }
}
