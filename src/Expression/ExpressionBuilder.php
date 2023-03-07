<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use function array_merge;

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
    public function build(Expression $expression, array &$params = []): string
    {
        $params = array_merge($params, $expression->getParams());
        return $expression->__toString();
    }
}
