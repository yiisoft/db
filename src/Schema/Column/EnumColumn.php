<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use LogicException;
use Yiisoft\Db\Constant\ColumnType;

final class EnumColumn extends StringColumn
{
    protected const DEFAULT_TYPE = ColumnType::ENUM;

    /**
     * @var string[]|null $values The list of possible values for an ENUM column.
     * @psalm-var non-empty-list<string>|null
     */
    protected ?array $values = null;

    /**
     * @param string[] $values The list of possible values for the `ENUM` column.
     * @psalm-param non-empty-list<string> $values
     */
    public function values(array $values): static
    {
        $this->values = $values;
        return $this;
    }

    /**
     * @return string[] The enum values of the column.
     * @psalm-return non-empty-list<string>
     *
     * @see values()
     */
    public function getValues(): array
    {
        if ($this->values === null) {
            throw new LogicException('Enum values have not been set.');
        }
        return $this->values;
    }
}
