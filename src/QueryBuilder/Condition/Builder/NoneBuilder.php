<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Builder;

use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\None;

/**
 * Builds SQL expressions for {@see None} condition.
 *
 * @implements ExpressionBuilderInterface<None>
 */
final class NoneBuilder implements ExpressionBuilderInterface
{
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        return '0=1';
    }
}
