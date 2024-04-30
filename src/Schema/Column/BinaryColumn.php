<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use PDO;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function gettype;

class BinaryColumn extends Column
{
    public function __construct(
        string|null $type = SchemaInterface::TYPE_BINARY,
        string|null $phpType = SchemaInterface::PHP_TYPE_RESOURCE,
    ) {
        parent::__construct($type, $phpType);
    }

    public function dbTypecast(mixed $value): mixed
    {
        if ($value instanceof ExpressionInterface) {
            return $value;
        }

        return match (gettype($value)) {
            'string' => new Param($value, PDO::PARAM_LOB),
            'resource' => $value,
            'NULL' => null,
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };
    }
}
