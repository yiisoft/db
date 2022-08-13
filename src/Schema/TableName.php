<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

/**
 * TableName - abstraction for name of table in DataBase
 */
class TableName implements TableNameInterface
{
    private const DELIMITER = '.';

    private ?string $prefix = null;

    private string $tableName;
    private ?string $schemaName;
    private ?string $catalogName;
    private ?string $serverName;

    public function __construct(
        string $tableName,
        ?string $schemaName = null,
        ?string $catalogName = null,
        ?string $serverName = null
    ) {
        $this->tableName = $tableName;
        $this->schemaName = $schemaName;
        $this->catalogName = $catalogName;
        $this->serverName = $serverName;
    }

    public function getTableName(): string
    {
        return $this->addPrefix($this->tableName);
    }

    public function getRawTableName(): string
    {
        return $this->tableName;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function setPrefix(?string $prefix = null): static
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function withPrefix(?string $prefix = null): static
    {
        $new = clone $this;
        $new->prefix = $prefix;
        return $new;
    }

    public function getSchemaName(): ?string
    {
        return $this->schemaName;
    }

    public function getCatalogName(): ?string
    {
        return $this->catalogName;
    }

    public function getServerName(): ?string
    {
        return $this->serverName;
    }

    public function __toString()
    {
        return implode((string) static::DELIMITER, array_filter([
            $this->serverName,
            $this->catalogName,
            $this->schemaName,
            $this->getTableName(),
        ]));
    }

    private function addPrefix(string $name): string
    {
        if (!str_contains($name, '{{')) {
            return $name;
        }

        $name = preg_replace('/{{(.*?)}}/', '\1', $name);

        return str_replace('%', $this->prefix ?? '', $name);
    }
}
