<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions\Interface;

use Yiisoft\Db\Expression\ExpressionBuilderInterface;

interface SimpleConditionBuilderInterface extends ExpressionBuilderInterface
{
    /**
     * Method builds the raw SQL from the $expression that will not be additionally escaped or quoted.
     *
     * @param SimpleConditionInterface $expression Expression to be built.
     * @param array $params Binding parameters.
     *
     * @return string The raw SQL that will not be additionally escaped or quoted.
     */
    public function build(SimpleConditionInterface $expression, array &$params = []): string;
}
