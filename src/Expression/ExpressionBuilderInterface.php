<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

/**
 * Interface ExpressionBuilderInterface is designed to build raw SQL from specific expression objects that implement
 * {@see ExpressionInterface}.
 */
interface ExpressionBuilderInterface
{
    /**
     * Method builds the raw SQL from the $expression that will not be additionally escaped or quoted.
     *
     * @param ExpressionInterface $expression the expression to be built.
     * @param array $params the binding parameters.
     *
     * @return string the raw SQL that will not be additionally escaped or quoted.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string;
}
