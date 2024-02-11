<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function array_intersect_key;
use function array_merge;
use function preg_quote;
use function preg_replace;
use function str_starts_with;

/**
 * It's used to build expressions for use in database queries.
 *
 * It provides a methods {@see build()} for creating various types of expressions, such as conditions, joins, and
 * ordering clauses.
 *
 * These expressions can be used with the query builder to build complex and customizable database queries
 * {@see Expression} class.
 */
class ExpressionBuilder implements ExpressionBuilderInterface
{
    public function __construct(private QueryBuilderInterface|null $queryBuilder = null)
    {
    }

    public function build(Expression $expression, array &$params = []): string
    {
        $sql = $expression->__toString();
        $expressionParams = $expression->getParams();

        if (empty($expressionParams)) {
            return $sql;
        }

        if ($this->queryBuilder === null || isset($params[0]) || isset($expressionParams[0])) {
            $params = array_merge($params, $expressionParams);
            return $sql;
        }

        $sql = $this->appendParams($sql, $expressionParams, $params);

        return $this->replaceParamExpressions($sql, $expressionParams, $params);
    }

    private function appendParams(string $sql, array &$expressionParams, array &$params): string
    {
        $nonUniqueParams = array_intersect_key($expressionParams, $params);
        $params += $expressionParams;

        /** @var string $name */
        foreach ($nonUniqueParams as $name => $value) {
            $pattern = $this->getPattern($name);
            $uniqueName = $this->getUniqueName($name, $params);

            $replacement = !str_starts_with($uniqueName, ':') ? ":$uniqueName" : $uniqueName;

            $sql = preg_replace($pattern, $replacement, $sql, 1);

            $params[$uniqueName] = $value;
            $expressionParams[$uniqueName] = $value;
            unset($expressionParams[$name]);
        }

        return $sql;
    }

    private function replaceParamExpressions(string $sql, array $expressionParams, array &$params): string
    {
        /** @var string $name */
        foreach ($expressionParams as $name => $value) {
            if (!$value instanceof ExpressionInterface) {
                continue;
            }

            $pattern = $this->getPattern($name);
            /** @psalm-suppress PossiblyNullReference */
            $replacement = $this->queryBuilder->buildExpression($value, $params);

            $sql = preg_replace($pattern, $replacement, $sql, 1);

            unset($params[$name]);
        }

        return $sql;
    }

    /** @psalm-return non-empty-string */
    private function getPattern(string $name): string
    {
        if (!str_starts_with($name, ':')) {
            $name = ":$name";
        }

        return '/' . preg_quote($name, '/') . '\b/';
    }

    private function getUniqueName(string $name, array $params): string
    {
        $uniqueName = $name . '_0';

        for ($i = 1; isset($params[$uniqueName]); ++$i) {
            $uniqueName = $name . '_' . $i;
        }

        return $uniqueName;
    }
}
