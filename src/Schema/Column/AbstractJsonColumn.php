<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\JsonExpression;

/**
 * Represents an abstract JSON column.
 *
 * @see JsonColumn for a JSON column with eager parsing values retrieved from the database.
 * @see JsonLazyColumn for a JSON column with lazy parsing values retrieved from the database.
 */
abstract class AbstractJsonColumn extends AbstractColumn
{
    protected const DEFAULT_TYPE = ColumnType::JSON;

    public function dbTypecast(mixed $value): ExpressionInterface|null
    {
        if ($value === null || $value instanceof ExpressionInterface) {
            return $value;
        }

        return new JsonExpression($value, $this->getDbType());
    }
}
