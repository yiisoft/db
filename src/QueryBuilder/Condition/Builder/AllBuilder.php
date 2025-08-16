<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Builder;

use Yiisoft\Db\Expression\Builder\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\All;

/**
 * Builds SQL expressions for {@see All} condition.
 *
 * @implements ExpressionBuilderInterface<All>
 */
final class AllBuilder implements ExpressionBuilderInterface
{
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        return '';
    }
}
