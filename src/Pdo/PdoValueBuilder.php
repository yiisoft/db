<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pdo;

use Yiisoft\Db\Expressions\ExpressionInterface;
use Yiisoft\Db\Expressions\ExpressionBuilderInterface;

/**
 * Class PdoValueBuilder builds object of the {@see PdoValue} expression class.
 */
class PdoValueBuilder implements ExpressionBuilderInterface
{
    public const PARAM_PREFIX = ':pv';

    /**
     * {@inheritdoc}
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $placeholder = static::PARAM_PREFIX . count($params);
        $params[$placeholder] = $expression;

        return $placeholder;
    }
}
