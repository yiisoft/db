<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function gettype;

class StringColumnSchema extends AbstractColumnSchema
{
    public function __construct(
        string $type = SchemaInterface::TYPE_STRING,
        string|null $phpType = SchemaInterface::PHP_TYPE_STRING,
    ) {
        parent::__construct($type, $phpType);
    }

    public function dbTypecast(mixed $value): mixed
    {
        return match (gettype($value)) {
            'string', 'resource' => $value,
            'NULL' => null,
            'boolean' => $value ? '1' : '0',
            default => $value instanceof ExpressionInterface ? $value : (string) $value,
        };
    }

    public function phpTypecast(mixed $value): mixed
    {
        return $value;
    }
}
