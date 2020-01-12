<?php

namespace Yiisoft\Db;

/**
 * Class ExpressionBuilder builds objects of [[Yiisoft\Db\Expression]] class.
 */
class ExpressionBuilder implements ExpressionBuilderInterface
{
    use ExpressionBuilderTrait;

    /**
     * {@inheritdoc}
     *
     * @param Expression|ExpressionInterface $expression the expression to be built
     */
    public function build(ExpressionInterface $expression, array &$params = [])
    {
        $params = array_merge($params, $expression->params);

        return $expression->__toString();
    }
}
