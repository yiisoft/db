<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\PDO;

use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * Class PDOValueBuilder builds object of the {@see PDOValue} expression class.
 */
final class PDOValueBuilder implements ExpressionBuilderInterface
{
    public const PARAM_PREFIX = ':pv';

    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $placeholder = self::PARAM_PREFIX . count($params);
        $params[$placeholder] = $expression;
        return $placeholder;
    }
}
