<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Helper\DbStringHelper;
use Yiisoft\Db\Schema\SchemaInterface;

use function is_float;
use function is_resource;
use function is_string;

class StringColumn extends Column
{
    public function __construct(
        string|null $type = SchemaInterface::TYPE_STRING,
        string|null $phpType = SchemaInterface::PHP_TYPE_STRING,
    ) {
        parent::__construct($type, $phpType);
    }

    public function dbTypecast(mixed $value): string|ExpressionInterface|null
    {
        return match (true) {
            is_string($value), $value === null, is_resource($value), $value instanceof ExpressionInterface => $value,
            /** ensure type cast always has . as decimal separator in all locales */
            is_float($value) => DbStringHelper::normalizeFloat($value),
            $value === false => '0',
            default => (string) $value,
        };
    }
}
