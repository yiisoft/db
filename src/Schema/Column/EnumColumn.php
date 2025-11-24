<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Constant\ColumnType;

final class EnumColumn extends StringColumn
{
    protected const DEFAULT_TYPE = ColumnType::ENUM;

    /**
     * @var string[]|null $values The list of possible values for an ENUM column.
     */
    protected ?array $values = null;

    /**
     * @param string[]|null $values The list of possible values for the `ENUM` column.
     */
    public function values(?array $values): static
    {
        $this->values = $values;
        return $this;
    }

    /**
     * @return string[]|null The enum values of the column.
     *
     * @see values()
     */
    public function getValues(): ?array
    {
        return $this->values;
    }
}
