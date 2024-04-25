<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Schema\SchemaInterface;

use function is_string;
use function json_decode;

class JsonColumnSchema extends AbstractColumnSchema
{
    public function __construct(
        string $type = SchemaInterface::TYPE_JSON,
        string|null $phpType = SchemaInterface::PHP_TYPE_ARRAY,
    ) {
        parent::__construct($type, $phpType);
    }

    public function dbTypecast(mixed $value): ExpressionInterface|null
    {
        if ($value === null || $value instanceof ExpressionInterface) {
            return $value;
        }

        return new JsonExpression($value, $this->getDbType());
    }

    /**
     * @throws \JsonException
     */
    public function phpTypecast(mixed $value): mixed
    {
        if (is_string($value)) {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        }

        return $value;
    }
}
