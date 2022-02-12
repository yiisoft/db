<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryBuilderInterface;

use function strpos;

/**
 * Class BetweenColumnsConditionBuilder builds objects of {@see BetweenColumnsCondition}.
 */
class BetweenColumnsConditionBuilder implements ExpressionBuilderInterface
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $operator = $expression->getOperator();

        $startColumn = $this->escapeColumnName($expression->getIntervalStartColumn(), $params);
        $endColumn = $this->escapeColumnName($expression->getIntervalEndColumn(), $params);
        $value = $this->createPlaceholder($expression->getValue(), $params);

        return "$value $operator $startColumn AND $endColumn";
    }

    /**
     * Prepares column name to be used in SQL statement.
     *
     * @param ExpressionInterface|Query|string $columnName
     * @param array $params the binding parameters.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return string
     */
    protected function escapeColumnName($columnName, array &$params = []): string
    {
        if ($columnName instanceof Query) {
            [$sql, $params] = $this->queryBuilder->build($columnName, $params);

            return "($sql)";
        }

        if ($columnName instanceof ExpressionInterface) {
            return $this->queryBuilder->buildExpression($columnName, $params);
        }

        if (strpos($columnName, '(') === false) {
            return $this->queryBuilder->quoter()->quoteColumnName($columnName);
        }

        return $columnName;
    }

    /**
     * Attaches $value to $params array and returns placeholder.
     *
     * @param mixed $value
     * @param array $params passed by reference
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return string
     */
    protected function createPlaceholder($value, array &$params): string
    {
        if ($value instanceof ExpressionInterface) {
            return $this->queryBuilder->buildExpression($value, $params);
        }

        return $this->queryBuilder->bindParam($value, $params);
    }
}
