<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\SchemaInterface;

final class BooleanColumnSchema extends AbstractColumnSchema
{
    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->type(SchemaInterface::TYPE_BOOLEAN);
        $this->phpType(SchemaInterface::PHP_TYPE_BOOLEAN);
    }

    public function dbTypecast(mixed $value): bool|ExpressionInterface|null
    {
        /** Optimized for performance. Do not merge cases or change order. */
        return match (true) {
            $value, $value === false, $value === null, $value instanceof ExpressionInterface => $value,
            $value === '' => null,
            default => (bool) $value,
        };
    }

    public function phpTypecast(mixed $value): bool|null
    {
        if ($value === null) {
            return null;
        }

        return $value && $value !== "\0";
    }
}
