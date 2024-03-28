<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function array_intersect_key;
use function array_merge;
use function preg_quote;
use function preg_replace;

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

        if ($this->queryBuilder === null || isset($expressionParams[0])) {
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

        if (empty($nonUniqueParams)) {
            return $sql;
        }

        $patterns = [];
        $replacements = [];

        /** @var string $name */
        foreach ($nonUniqueParams as $name => $value) {
            $patterns[] = $this->getPattern($name);
            $uniqueName = $this->getUniqueName($name, $params);

            $replacements[] = $uniqueName[0] !== ':' ? ":$uniqueName" : $uniqueName;

            $params[$uniqueName] = $value;
            $expressionParams[$uniqueName] = $value;
            unset($expressionParams[$name]);
        }

        return preg_replace($patterns, $replacements, $sql, 1);
    }

    private function replaceParamExpressions(string $sql, array $expressionParams, array &$params): string
    {
        $patterns = [];
        $replacements = [];

        /** @var string $name */
        foreach ($expressionParams as $name => $value) {
            if (!$value instanceof ExpressionInterface) {
                continue;
            }

            $patterns[] = $this->getPattern($name);
            /** @psalm-suppress PossiblyNullReference */
            $replacements[] = $this->queryBuilder->buildExpression($value, $params);

            unset($params[$name]);
        }

        if (empty($patterns)) {
            return $sql;
        }

        return preg_replace($patterns, $replacements, $sql, 1);
    }

    /** @psalm-return non-empty-string */
    private function getPattern(string $name): string
    {
        if ($name[0] !== ':') {
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
