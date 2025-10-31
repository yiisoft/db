<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function array_merge;
use function substr;

/**
 * It's used to build expressions for use in database queries.
 *
 * It provides a {@see build()} method for creating various types of expressions, such as conditions, joins, and
 * ordering clauses.
 *
 * These expressions can be used with the query builder to build complex and customizable database queries
 * {@see Expression} class.
 *
 * @implements ExpressionBuilderInterface<Expression>
 *
 * @psalm-import-type ParamsType from ConnectionInterface
 */
final class ExpressionBuilder implements ExpressionBuilderInterface
{
    public function __construct(private readonly QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * Builds an SQL expression from the given expression object.
     *
     * This method is called by the query builder to build SQL expressions from {@see ExpressionInterface} objects.
     *
     * @param Expression $expression The expression to build.
     * @param array $params The parameters to be bound to the query.
     *
     * @psalm-param ParamsType $params
     *
     * @return string SQL expression.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $sql = $expression->expression;
        $expressionParams = $expression->params;

        if (empty($expressionParams)) {
            return $sql;
        }

        if (isset($expressionParams[0])) {
            $params = array_merge($params, $expressionParams);
            return $sql;
        }

        $nonUniqueReplacements = $this->appendParams($expressionParams, $params);
        $expressionReplacements = $this->buildParamExpressions($expressionParams, $params);

        $replacements = $this->mergeReplacements($nonUniqueReplacements, $expressionReplacements);

        if (empty($replacements)) {
            return $sql;
        }

        return $this->queryBuilder->replacePlaceholders($sql, $replacements);
    }

    /**
     * Appends parameters to the list of query parameters replacing non-unique parameters with unique ones.
     *
     * @param array $expressionParams Parameters to be appended.
     * @param array $params Parameters to be bound to the query.
     *
     * @psalm-param ParamsType $expressionParams
     * @psalm-param ParamsType $params
     *
     * @return string[] Replacements for non-unique parameters.
     */
    private function appendParams(array &$expressionParams, array &$params): array
    {
        $nonUniqueParams = [];

        /** @psalm-var non-empty-string $name */
        foreach ($expressionParams as $name => $value) {
            $paramName = $name[0] === ':' ? substr($name, 1) : $name;

            if (!isset($params[$paramName]) && !isset($params[":$paramName"])) {
                $params[$name] = $value;
                continue;
            }

            $nonUniqueParams[$name] = $value;
        }

        $replacements = [];

        foreach ($nonUniqueParams as $name => $value) {
            $paramName = $name[0] === ':' ? substr($name, 1) : $name;
            $uniqueName = $this->getUniqueName($paramName, $params);

            $replacements[":$paramName"] = ":$uniqueName";

            if ($name[0] === ':') {
                $uniqueName = ":$uniqueName";
            }

            $params[$uniqueName] = $value;
            $expressionParams[$uniqueName] = $value;
            unset($expressionParams[$name]);
        }

        return $replacements;
    }

    /**
     * Build expression values of parameters.
     *
     * @param array $expressionParams Parameters from the expression.
     * @param array $params Parameters to be bound to the query.
     *
     * @psalm-param ParamsType $expressionParams
     * @psalm-param ParamsType $params
     *
     * @return string[] Replacements for parameters.
     */
    private function buildParamExpressions(array $expressionParams, array &$params): array
    {
        $replacements = [];

        /** @psalm-var non-empty-string $name */
        foreach ($expressionParams as $name => $value) {
            if (!$value instanceof ExpressionInterface || $value instanceof Param) {
                continue;
            }

            $placeholder = $name[0] !== ':' ? ":$name" : $name;
            $replacements[$placeholder] = $this->queryBuilder->buildExpression($value, $params);

            /** @psalm-var ParamsType $params */
            unset($params[$name]);
        }

        return $replacements;
    }

    /**
     * Merges replacements for non-unique parameters with replacements for expression parameters.
     *
     * @param string[] $replacements Replacements for non-unique parameters.
     * @param string[] $expressionReplacements Replacements for expression parameters.
     *
     * @return string[] Merged replacements.
     */
    private function mergeReplacements(array $replacements, array $expressionReplacements): array
    {
        if (empty($replacements)) {
            return $expressionReplacements;
        }

        if (empty($expressionReplacements)) {
            return $replacements;
        }

        /** @psalm-var non-empty-string $value */
        foreach ($replacements as $name => $value) {
            if (isset($expressionReplacements[$value])) {
                $replacements[$name] = $expressionReplacements[$value];
                unset($expressionReplacements[$value]);
            }
        }

        return $replacements + $expressionReplacements;
    }

    /**
     * Returns a unique name for the parameter without colon at the beginning.
     *
     * @param string $name Name of the parameter without colon at the beginning.
     * @param array $params Parameters to be bound to the query.
     *
     * @psalm-param ParamsType $params
     *
     * @return string Unique name of the parameter with colon at the beginning.
     *
     * @psalm-return non-empty-string
     */
    private function getUniqueName(string $name, array $params): string
    {
        $uniqueName = $name . '_0';

        for ($i = 1; isset($params[$uniqueName]) || isset($params[":$uniqueName"]); ++$i) {
            $uniqueName = $name . '_' . $i;
        }

        return $uniqueName;
    }
}
