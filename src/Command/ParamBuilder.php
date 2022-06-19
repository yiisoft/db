<?php

declare(strict_types=1);

namespace Yiisoft\Db\Command;

use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * Class ParamBuilder builds object of the {@see Param} expression class.
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
