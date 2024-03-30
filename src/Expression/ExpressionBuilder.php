<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Syntax\SqlParser;

use function array_merge;
use function strlen;
use function substr;
use function substr_replace;

/**
 * It's used to build expressions for use in database queries.
 *
 * It provides a methods {@see build()} for creating various types of expressions, such as conditions, joins, and
 * ordering clauses.
 *
 * These expressions can be used with the query builder to build complex and customizable database queries
 * {@see Expression} class.
 *
 * @psalm-import-type ParamsType from ConnectionInterface
 */
class ExpressionBuilder implements ExpressionBuilderInterface
{
    public function __construct(private QueryBuilderInterface|null $queryBuilder = null)
    {
    }

    /**
     * Builds SQL statement from the given expression.
     *
     * @param Expression $expression The expression to be built.
     * @param array $params The parameters to be bound to the query.
     *
     * @psalm-param ParamsType $params
     *
     * @return string SQL statement.
     */
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

    /**
     * Appends parameters to the list of query parameters replacing non-unique parameters with unique ones.
     *
     * @param string $sql SQL statement of the expression.
     * @param array $expressionParams Parameters to be appended.
     * @param array $params Parameters to be bound to the query.
     *
     * @psalm-param ParamsType $params
     *
     * @return string SQL statement with unique parameters.
     */
    private function appendParams(string $sql, array &$expressionParams, array &$params): string
    {
        $nonUniqueParams = [];

        /** @var non-empty-string $name */
        foreach ($expressionParams as $name => $value) {
            $paramName = $name[0] === ':' ? substr($name, 1) : $name;

            if (!isset($params[$paramName]) && !isset($params[":$paramName"])) {
                $params[$name] = $value;
                continue;
            }

            $nonUniqueParams[$name] = $value;
        }

        /** @var non-empty-string $name */
        foreach ($nonUniqueParams as $name => $value) {

            $paramName = $name[0] === ':' ? substr($name, 1) : $name;
            $uniqueName = $this->getUniqueName($paramName, $params);

            $sql = $this->replacePlaceholder($sql, ":$paramName", ":$uniqueName");

            if ($name[0] === ':') {
                $uniqueName = ":$uniqueName";
            }

            $params[$uniqueName] = $value;
            $expressionParams[$uniqueName] = $value;
            unset($expressionParams[$name]);
        }

        return $sql;
    }

    /**
     * Replaces parameters with expression values in SQL statement.
     *
     * @param string $sql SQL statement where parameters should be replaced.
     * @param array $expressionParams Parameters to be replaced.
     * @param array $params Parameters to be bound to the query.
     *
     * @psalm-param ParamsType $expressionParams
     * @psalm-param ParamsType $params
     *
     * @return string SQL statement with replaced parameters.
     */
    private function replaceParamExpressions(string $sql, array $expressionParams, array &$params): string
    {
        /** @var non-empty-string $name */
        foreach ($expressionParams as $name => $value) {
            if (!$value instanceof ExpressionInterface) {
                continue;
            }

            $placeholder = $name[0] !== ':' ? ":$name" : $name;
            /** @psalm-suppress PossiblyNullReference */
            $replacement = $this->queryBuilder->buildExpression($value, $params);

            $sql = $this->replacePlaceholder($sql, $placeholder, $replacement);

            /** @psalm-var ParamsType $params */
            unset($params[$name]);
        }

        return $sql;
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

    /**
     * Replaces the placeholder with the replacement in SQL statement.
     *
     * @param string $sql SQL statement where the placeholder should be replaced.
     * @param string $placeholder Placeholder to be replaced.
     * @param string $replacement Replacement for the placeholder.
     *
     * @return string SQL with the replaced placeholder.
     */
    private function replacePlaceholder(string $sql, string $placeholder, string $replacement): string
    {
        $parser = $this->createSqlParser($sql);

        while (null !== $parsedPlaceholder = $parser->getNextPlaceholder($position)) {
            if ($parsedPlaceholder === $placeholder) {
                return substr_replace($sql, $replacement, $position, strlen($placeholder));
            }
        }

        return $sql;
    }

    /**
     * Creates an instance of {@see SqlParser} for the given SQL statement.
     *
     * @param string $sql SQL statement to be parsed.
     *
     * @return SqlParser SQL parser instance.
     */
    protected function createSqlParser(string $sql): SqlParser
    {
        return new SqlParser($sql);
    }
}
