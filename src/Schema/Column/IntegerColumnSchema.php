<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function is_int;

class IntegerColumnSchema extends AbstractColumnSchema
{
    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->type(SchemaInterface::TYPE_INTEGER);
        $this->phpType(SchemaInterface::PHP_TYPE_INTEGER);
    }

    public function dbTypecast(mixed $value): int|ExpressionInterface|null
    {
        return match (true) {
            is_int($value), $value === null, $value instanceof ExpressionInterface => $value,
            $value === '' => null,
            default => (int) $value,
        };
    }

    public function phpTypecast(mixed $value): int|null
    {
        if ($value === null) {
            return null;
        }

        return (int) $value;
    }
}
