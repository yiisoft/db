<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use BackedEnum;
use Stringable;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Constant\GettypeResult;
use Yiisoft\Db\Constant\PhpType;

use function gettype;

/**
 * Represents the metadata for a string column.
 */
class StringColumn extends AbstractColumn
{
    protected const DEFAULT_TYPE = ColumnType::STRING;

    public function dbTypecast(mixed $value): mixed
    {
        return match (gettype($value)) {
            GettypeResult::STRING => $value,
            GettypeResult::RESOURCE => $value,
            GettypeResult::NULL => null,
            GettypeResult::INTEGER,
            GettypeResult::DOUBLE => (string) $value,
            GettypeResult::BOOLEAN => $value ? '1' : '0',
            GettypeResult::OBJECT => match (true) {
                $value instanceof ExpressionInterface => $value,
                $value instanceof BackedEnum => (string) $value->value,
                $value instanceof Stringable => (string) $value,
                default => $this->throwWrongTypeException($value::class),
            },
            default => $this->throwWrongTypeException(gettype($value)),
        };
    }

    /** @psalm-mutation-free */
    public function getPhpType(): string
    {
        return PhpType::STRING;
    }

    public function phpTypecast(mixed $value): string|null
    {
        /** @var string|null $value */
        return $value;
    }
}
