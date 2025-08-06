<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Builder;

use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\BetweenColumns;
use Yiisoft\Db\QueryBuilder\Condition\NotBetweenColumns;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Query\QueryInterface;

use function str_contains;

/**
 * Build an object of {@see BetweenColumns} or {@see NotBetweenColumns} into SQL expressions.
 *
 * @implements ExpressionBuilderInterface<BetweenColumns|NotBetweenColumns>
 */
class BetweenColumnsBuilder implements ExpressionBuilderInterface
{
    public function __construct(private readonly QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * Build SQL for {@see BetweenColumns} or {@see NotBetweenColumns}.
     *
     * @param BetweenColumns|NotBetweenColumns $expression
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $operator = match ($expression::class) {
            BetweenColumns::class => 'BETWEEN',
            NotBetweenColumns::class => 'NOT BETWEEN',
        };
        $startColumn = $this->escapeColumnName($expression->intervalStartColumn, $params);
        $endColumn = $this->escapeColumnName($expression->intervalEndColumn, $params);
        $value = $this->createPlaceholder($expression->value, $params);
        return "$value $operator $startColumn AND $endColumn";
    }

    /**
     * Attaches `$value` to `$params` array and return placeholder.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    protected function createPlaceholder(mixed $value, array &$params): string
    {
        if ($value instanceof ExpressionInterface) {
            return $this->queryBuilder->buildExpression($value, $params);
        }

        return $this->queryBuilder->bindParam($value, $params);
    }

    /**
     * Prepares column name to use in SQL statement.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    protected function escapeColumnName(
        ExpressionInterface|QueryInterface|string $columnName,
        array &$params = []
    ): string {
        if ($columnName instanceof QueryInterface) {
            [$sql, $params] = $this->queryBuilder->build($columnName, $params);
            return "($sql)";
        }

        if ($columnName instanceof ExpressionInterface) {
            return $this->queryBuilder->buildExpression($columnName, $params);
        }

        if (!str_contains($columnName, '(')) {
            return $this->queryBuilder->getQuoter()->quoteColumnName($columnName);
        }

        return $columnName;
    }
}
