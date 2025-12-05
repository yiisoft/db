<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * Represents a common table expression (CTE) for SQL WITH queries.
 *
 * A data structure holds the information for a single "WITH" query clause.
 *
 * @see QueryPartsInterface::addWithQuery()
 * @see QueryPartsInterface::withQueries()
 */
final class WithQuery
{
    /**
     * @param QueryInterface|string $query The query to be used as a CTE. It can be a Query object or a raw SQL string.
     * @param ExpressionInterface|string $alias The name/alias for the CTE that can be referenced in the main query.
     * @param bool $recursive Whether this is a recursive CTE. Default is `false`.
     */
    public function __construct(
        public readonly QueryInterface|string $query,
        public readonly ExpressionInterface|string $alias,
        public readonly bool $recursive = false,
    ) {}
}
