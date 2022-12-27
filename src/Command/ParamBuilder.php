<?php

declare(strict_types=1);

namespace Yiisoft\Db\Command;

use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * The ParamBuilder class, which implements the {@see ExpressionBuilderInterface} interface, is used to build
 * {@see ParamInterface} objects.
 */
final class ParamBuilder implements ExpressionBuilderInterface
{
    public const PARAM_PREFIX = ':pv';

    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $placeholder = self::PARAM_PREFIX . count($params);
        $params[$placeholder] = $expression;
        return $placeholder;
    }
}
