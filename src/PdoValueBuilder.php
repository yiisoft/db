<?php

declare(strict_types=1);

namespace Yiisoft\Db;

use Yiisoft\Db\Contracts\ExpressionInterface;
use Yiisoft\Db\Contracts\ExpressionBuilderInterface;

/**
 * Class PdoValueBuilder builds object of the {@see PdoValue} expression class.
 */
class PdoValueBuilder implements ExpressionBuilderInterface
{
    const PARAM_PREFIX = ':pv';

    /**
     * {@inheritdoc}
     */
    public function build(ExpressionInterface $expression, array &$params = [])
    {
        $placeholder = static::PARAM_PREFIX.count($params);
        $params[$placeholder] = $expression;

        return $placeholder;
    }
}
