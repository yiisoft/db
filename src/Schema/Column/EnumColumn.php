<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use BackedEnum;
use Stringable;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\GettypeResult;
use Yiisoft\Db\Expression\ExpressionInterface;

use function gettype;

final class EnumColumn extends AbstractColumn
{
    protected const DEFAULT_TYPE = ColumnType::ENUM;

    /**
     * @var string[]|null $enumValues The list of possible values for an ENUM column.
     */
    protected ?array $enumValues = null;

    /**
     * @param string[]|null $values The list of possible values for the `ENUM` column.
     */
    public function enumValues(?array $values): static
    {
        $this->enumValues = $values;
        return $this;
    }

    /**
     * @return string[]|null The enum values of the column.
     *
     * @see enumValues()
     */
    public function getEnumValues(): ?array
    {
        return $this->enumValues;
    }

    public function dbTypecast(mixed $value): mixed
    {
        return match (gettype($value)) {
            GettypeResult::STRING => $value,
            GettypeResult::RESOURCE => $value,
            GettypeResult::NULL => null,
            GettypeResult::INTEGER => (string) $value,
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

    public function phpTypecast(mixed $value): ?string
    {
        /** @var string|null $value */
        return $value;
    }
}
