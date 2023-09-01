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
    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->dbType('json');
        $this->type(SchemaInterface::TYPE_JSON);
        $this->phpType(SchemaInterface::PHP_TYPE_ARRAY);
    }

    public function dbTypecast(mixed $value): mixed
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
