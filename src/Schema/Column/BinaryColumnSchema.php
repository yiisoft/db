<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use PDO;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Helper\DbStringHelper;
use Yiisoft\Db\Schema\SchemaInterface;

use function is_float;
use function is_resource;
use function is_string;

class BinaryColumnSchema extends AbstractColumnSchema
{
    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->type(SchemaInterface::TYPE_BINARY);
        $this->phpType(SchemaInterface::PHP_TYPE_RESOURCE);
    }

    public function dbTypecast(mixed $value): mixed
    {
        return match (true) {
            is_string($value) => new Param($value, PDO::PARAM_LOB),
            $value === null, is_resource($value), $value instanceof ExpressionInterface => $value,
            /** ensure type cast always has . as decimal separator in all locales */
            is_float($value) => DbStringHelper::normalizeFloat($value),
            $value === false => '0',
            default => (string) $value,
        };
    }
}
