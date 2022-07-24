<?php

namespace Yiisoft\Db\Schema;


use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * TableName - abstraction for name of table in DataBase
 */
interface TableNameInterface
{
    public function getServerName(): ?string;

    public function getCatalogName(): ?string;

    public function getSchemaName(): ?string;

    public function getTableName(): string|ExpressionInterface;

    public function getPrefix(): ?string;
}
