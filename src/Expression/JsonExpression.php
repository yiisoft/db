<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use JsonSerializable;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Query\QueryInterface;

/**
 * Represents data to encode to JSON.
 *
 * For example:
 *
 * ```php
 * new JsonExpression(['a' => 1, 'b' => 2]); // will be encoded to '{"a": 1, "b": 2}'
 * ```
 */
class JsonExpression implements ExpressionInterface, JsonSerializable
{
    public function __construct(protected mixed $value, private string|null $type = null)
    {
        if ($value instanceof self) {
            $this->value = $value->getValue();
        }
    }

    /**
     * The value must be compatible with {@see \json_encode()} input requirements.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Type of JSON, expression should be cast to. Defaults to `null`, meaning no explicit casting will be performed.
     *
     * This property will be encountered only for DBMSs that support different types of JSON.
     *
     * For example, PostgresSQL has `json` and `jsonb` types.
     */
    public function getType(): string|null
    {
        return $this->type;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @throws InvalidConfigException When JsonExpression has a {@see QueryInterface} object
     *
     * @return mixed Data which can be serialized by `json_encode`, which is a value of any type other than a resource.
     */
    public function jsonSerialize(): mixed
    {
        /** @psalm-var mixed $value */
        $value = $this->getValue();

        if ($value instanceof QueryInterface) {
            throw new InvalidConfigException(
                'The JsonExpression class can not be serialized to JSON when the value is a QueryInterface object.'
            );
        }

        return $value;
    }
}
