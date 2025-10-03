<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Statement\Builder;

use Yiisoft\Db\Constant\GettypeResult;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\Statement\CaseX;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function gettype;
use function is_array;

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
            $sql .= ' ' . $this->buildCaseValue($expression->value, $params);
        }

        foreach ($expression->whenThen as $whenThen) {
            $sql .= ' WHEN ' . $this->buildCondition($whenThen->when, $params);
            $sql .= ' THEN ' . $this->queryBuilder->buildValue($whenThen->then, $params);
        }

        if ($expression->hasElse()) {
            $sql .= ' ELSE ' . $this->queryBuilder->buildValue($expression->else, $params);
        }

        return $sql . ' END';
    }

    /**
     * Builds the case value part of the CASE expression based on their type.
     *
     * @return string The SQL condition string.
     */
    protected function buildCaseValue(mixed $value, array &$params): string
    {
        /**
         * @var string
         * @psalm-suppress MixedArgument
         */
        return match (gettype($value)) {
            GettypeResult::ARRAY => $this->queryBuilder->buildCondition($value, $params),
            GettypeResult::STRING => $this->queryBuilder->getQuoter()->quoteColumnName($value),
            default => $this->queryBuilder->buildValue($value, $params),
        };
    }

    /**
     * Builds the condition part of the CASE expression based on their type.
     *
     * @return string The SQL condition string.
     */
    protected function buildCondition(mixed $condition, array &$params): string
    {
        if (is_array($condition)) {
            return $this->queryBuilder->buildCondition($condition, $params);
        }

        return $this->queryBuilder->buildValue($condition, $params);
    }
}
