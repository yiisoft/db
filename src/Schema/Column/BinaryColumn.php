<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use BackedEnum;
use PDO;
use Stringable;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Constant\GettypeResult;
use Yiisoft\Db\Schema\Data\StringableStream;

use function gettype;
use function is_resource;

/**
 * Represents the metadata for a binary column.
 */
class BinaryColumn extends AbstractColumn
{
    protected const DEFAULT_TYPE = ColumnType::BINARY;

    public function dbTypecast(mixed $value): mixed
    {
        return match (gettype($value)) {
            GettypeResult::STRING => new Param($value, PDO::PARAM_LOB),
            GettypeResult::RESOURCE => $value,
            GettypeResult::NULL => null,
            GettypeResult::INTEGER => (string) $value,
            GettypeResult::DOUBLE => (string) $value,
            GettypeResult::BOOLEAN => $value ? '1' : '0',
            GettypeResult::OBJECT => match (true) {
                $value instanceof StringableStream => $value->getValue(),
                $value instanceof ExpressionInterface => $value,
                $value instanceof Stringable => (string) $value,
                $value instanceof BackedEnum => (string) $value->value,
                default => $this->throwWrongTypeException($value::class),
            },
            default => $this->throwWrongTypeException(gettype($value)),
        };
    }

    public function phpTypecast(mixed $value): StringableStream|string|null
    {
        /** @var StringableStream|string|null */
        return is_resource($value) ? new StringableStream($value) : $value;
    }
}
