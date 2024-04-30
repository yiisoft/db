<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function bindec;
use function preg_match;

class BitColumn extends Column
{
    public function __construct(
        string|null $type = SchemaInterface::TYPE_BIT,
        string|null $phpType = SchemaInterface::PHP_TYPE_INTEGER,
    ) {
        parent::__construct($type, $phpType);
    }

    public function dbTypecast(mixed $value): int|string|ExpressionInterface|null
    {
        if ($value instanceof ExpressionInterface) {
            return $value;
        }

        return match ($value) {
            null, '' => null,
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

    public function normalizeDefaultValue(string|null $value): int|ExpressionInterface|null
    {
        if ($value === null || $this->isComputed() || preg_match("/^\(?NULL\b/i", $value) === 1) {
            return null;
        }

        if (preg_match("/^[Bb]?'([01]+)'/", $value, $matches) === 1) {
            /** @var int */
            return bindec($matches[1]);
        }

        return new Expression($value);
    }
}
