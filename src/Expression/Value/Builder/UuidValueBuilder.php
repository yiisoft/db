<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Value\Builder;

use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Expression\Value\UuidValue;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Builder for {@see UuidValue} expressions.
 *
 * Binds the UUID as a string parameter in the canonical form, which is what PostgreSQL `uuid` and MSSQL
 * `uniqueidentifier` columns expect. DBMS that store a UUID as raw bytes, such as MySQL, MariaDB, SQLite and Oracle,
 * provide their own builder in the DBMS-specific package.
 *
 * @implements ExpressionBuilderInterface<UuidValue>
 */
final class UuidValueBuilder implements ExpressionBuilderInterface
{
    /**
     * @param QueryBuilderInterface $queryBuilder The query builder instance.
     */
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {}

    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        /** @var UuidValue $expression */
        return $this->queryBuilder->bindParam(new Param($expression->value, DataType::STRING), $params);
    }
}
