<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use PDO;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Constant\GettypeResult;
use Yiisoft\Db\Schema\SchemaInterface;

use function gettype;

class BinaryColumnSchema extends AbstractColumnSchema
{
    public function __construct(
        string $type = SchemaInterface::TYPE_BINARY,
    ) {
        parent::__construct($type);
    }

    public function dbTypecast(mixed $value): mixed
    {
        return match (gettype($value)) {
            GettypeResult::STRING => new Param($value, PDO::PARAM_LOB),
            GettypeResult::RESOURCE => $value,
            GettypeResult::NULL => null,
            GettypeResult::BOOLEAN => $value ? '1' : '0',
            default => $value instanceof ExpressionInterface ? $value : (string) $value,
        };
    }

    public function phpTypecast(mixed $value): mixed
    {
        return $value;
    }
}
