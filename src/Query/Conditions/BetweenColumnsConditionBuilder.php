<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\Conditions\Interface\BetweenColumnsConditionBuilderInterface;
use Yiisoft\Db\Query\Conditions\Interface\BetweenColumnsConditionInterface;
use Yiisoft\Db\Query\QueryBuilderInterface;
use Yiisoft\Db\Query\QueryInterface;

/**
 * Class BetweenColumnsConditionBuilder builds objects of {@see BetweenColumnsCondition}.
 */
class BetweenColumnsConditionBuilder implements BetweenColumnsConditionBuilderInterface
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function build(BetweenColumnsConditionInterface $expression, array &$params = []): string
    {
        $operator = $expression->getOperator();
        $startColumn = $this->escapeColumnName($expression->getIntervalStartColumn(), $params);
        $endColumn = $this->escapeColumnName($expression->getIntervalEndColumn(), $params);
        $value = $this->createPlaceholder($expression->getValue(), $params);

        return "$value $operator $startColumn AND $endColumn";
    }

    /**
     * Attaches $value to $params array and returns placeholder.
     *
     * @param mixed $value
     * @param array $params Passed by reference
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return string
     */
    protected function createPlaceholder(mixed $value, array &$params): string
    {
        if ($value instanceof ExpressionInterface) {
            return $this->queryBuilder->buildExpression($value, $params);
        }

        return $this->queryBuilder->bindParam($value, $params);
    }

    /**
     * Prepares column name to be used in SQL statement.
     *
     * @param ExpressionInterface|QueryInterface|string $columnName
     * @param array $params The binding parameters.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return string
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
            return $this->queryBuilder->quoter()->quoteColumnName($columnName);
        }

        return $columnName;
    }
}
