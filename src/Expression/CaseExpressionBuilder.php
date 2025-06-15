<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use InvalidArgumentException;
use Yiisoft\Db\Constant\GettypeResult;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function gettype;

/**
 * Builds expressions for {@see CaseExpression}.
 */
class CaseExpressionBuilder implements ExpressionBuilderInterface
{
    public function __construct(protected readonly QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * Builds an SQL CASE expression from the given {@see CaseExpression} object.
     *
     * @param CaseExpression $expression The CASE expression to build.
     * @param array $params The parameters to be bound to the query.
     *
     * @return string SQL CASE expression.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $whenClauses = $expression->getWhen();

        if (empty($whenClauses)) {
            throw new InvalidArgumentException('The CASE expression must have at least one WHEN clause.');
        }

        $sql = 'CASE';
        $queryBuilder = $this->queryBuilder;

        $case = $expression->getCase();

        if ($case !== null) {
            $sql .= ' ' . $this->buildCondition($case, $params);
        }

        foreach ($whenClauses as $when) {
            $sql .= ' WHEN ' . $this->buildCondition($when->condition, $params);
            $sql .= ' THEN ' . $queryBuilder->buildValue($when->result, $params);
        }

        if ($expression->hasElse()) {
            $sql .= ' ELSE ' . $queryBuilder->buildValue($expression->getElse(), $params);
        }

        return $sql . ' END';
    }

    /**
     * Builds condition parts of the CASE expression based on their type.
     *
     * @return string The SQL condition string.
     */
    protected function buildCondition(
        array|bool|ExpressionInterface|float|int|string|null $condition,
        array &$params,
    ): string {
        /**
         * @var string
         * @psalm-suppress ArgumentTypeCoercion
         */
        return match (gettype($condition)) {
            GettypeResult::ARRAY => $this->queryBuilder->buildCondition($condition, $params),
            GettypeResult::STRING => $condition,
            default => $this->queryBuilder->buildValue($condition, $params),
        };
    }
}
