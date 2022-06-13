<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Conditions\Builder;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\QueryBuilder\Conditions\Interface\BetweenConditionInterface;

use function str_contains;

/**
 * Class BetweenConditionBuilder builds objects of {@see BetweenCondition}.
 */
class BetweenConditionBuilder implements ExpressionBuilderInterface
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function build(BetweenConditionInterface $expression, array &$params = []): string
    {
        $operator = $expression->getOperator();
        $column = (string) $expression->getColumn();

        if (!str_contains($column, '(')) {
            $column = $this->queryBuilder->quoter()->quoteColumnName($column);
        }

        $phName1 = $this->createPlaceholder($expression->getIntervalStart(), $params);
        $phName2 = $this->createPlaceholder($expression->getIntervalEnd(), $params);
        return "$column $operator $phName1 AND $phName2";
    }

    /**
     * Attaches $value to $params array and returns placeholder.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    protected function createPlaceholder(mixed $value, array &$params): string
    {
        if ($value instanceof ExpressionInterface) {
            return $this->queryBuilder->buildExpression($value, $params);
        }

        return $this->queryBuilder->bindParam($value, $params);
    }
}
