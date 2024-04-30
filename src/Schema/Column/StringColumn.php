<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function gettype;

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
        if ($value instanceof ExpressionInterface) {
            return $value;
        }

        return match (gettype($value)) {
            'string', 'resource' => $value,
            'NULL' => null,
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };
    }
}
