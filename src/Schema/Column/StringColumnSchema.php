<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Constant\GettypeResult;
use Yiisoft\Db\Constant\PhpType;

use function gettype;

class StringColumnSchema extends AbstractColumnSchema
{
    /**
     * @psalm-param ColumnType::* $type
     */
    public function __construct(
        string $type = ColumnType::STRING,
    ) {
        parent::__construct($type);
    }

    public function dbTypecast(mixed $value): mixed
    {
        return match (gettype($value)) {
            GettypeResult::STRING => $value,
            GettypeResult::RESOURCE => $value,
            GettypeResult::NULL => null,
            GettypeResult::BOOLEAN => $value ? '1' : '0',
            default => $value instanceof ExpressionInterface ? $value : (string) $value,
        };
    }

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
