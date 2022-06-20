<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use function array_merge;

/**
 * Class ExpressionBuilder builds objects of {@see Expression} class.
 */
class ExpressionBuilder implements ExpressionBuilderInterface
{
    public function build(Expression $expression, array &$params = []): string
    {
        $params = array_merge($params, $expression->getParams());
        return $expression->__toString();
    }
}
