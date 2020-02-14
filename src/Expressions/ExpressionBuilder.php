<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expressions;

/**
 * Class ExpressionBuilder builds objects of {@see \Yiisoft\Db\Expressions\Expression} class.
 */
class ExpressionBuilder implements ExpressionBuilderInterface
{
    use ExpressionBuilderTrait;

    /**
     * {@inheritdoc}
     *
     * @param Expression|ExpressionInterface $expression the expression to be built
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $params = array_merge($params, $expression->params);

        return $expression->__toString();
    }
}
