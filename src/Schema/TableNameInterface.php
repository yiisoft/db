<?php

namespace Yiisoft\Db\Schema;

use Stringable;
use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * TableName - abstraction for name of table in DataBase
 */
interface TableNameInterface extends Stringable
{
    /**
     * Replacing % symbol to table prefix before returning value
     * @return string
     */
    public function getTableName(): string;

    public function getRawTableName(): string|ExpressionInterface;

    public function getPrefix(): ?string;

    public function setPrefix(?string $prefix = null): static;

    public function getSchemaName(): ?string;

    public function getCatalogName(): ?string;

    public function getServerName(): ?string;
}
