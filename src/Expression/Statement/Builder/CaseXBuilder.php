<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Statement\Builder;

use Yiisoft\Db\Constant\GettypeResult;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\Statement\CaseX;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function gettype;
use function is_string;

/**
 * Builds expressions for {@see CaseX}.
 *
 * @implements ExpressionBuilderInterface<CaseX>
 */
class CaseXBuilder implements ExpressionBuilderInterface
{
    public function __construct(protected readonly QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * Builds an SQL `CASE` expression from the given {@see CaseX} object.
     *
     * @param CaseX $expression The `CASE` expression to build.
     * @param array $params The parameters to be bound to the query.
     *
     * @return string SQL `CASE` expression.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $sql = 'CASE';

        if ($expression->value !== null) {
            $sql .= ' ' . $this->buildCondition($expression->value, $params);
        }

        foreach ($expression->when as $when) {
            $sql .= ' WHEN ' . $this->buildCondition($when->condition, $params);
            $sql .= ' THEN ' . $this->buildResult($when->result, $params);
        }

        if ($expression->hasElse()) {
            $sql .= ' ELSE ' . $this->buildResult($expression->else, $params);
        }

        return $sql . ' END';
    }

    /**
     * Builds the condition part of the CASE expression based on their type.
     *
     * @return string The SQL condition string.
     */
    protected function buildCondition(mixed $condition, array &$params): string
    {
        /**
         * @var string
         * @psalm-suppress MixedArgument
         */
        return match (gettype($condition)) {
            GettypeResult::ARRAY => $this->queryBuilder->buildCondition($condition, $params),
            GettypeResult::STRING => $condition,
            default => $this->queryBuilder->buildValue($condition, $params),
        };
    }

    /**
     * Builds the result part of the `CASE` expression based on its type.
     *
     * @return string The SQL result string.
     */
    protected function buildResult(mixed $result, array &$params): string
    {
        if (is_string($result)) {
            return $result;
        }

        return $this->queryBuilder->buildValue($result, $params);
    }
}
