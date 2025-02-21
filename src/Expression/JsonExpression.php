<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

/**
 * Represents data to encode to JSON.
 *
 * For example:
 *
 * ```php
 * new JsonExpression(['a' => 1, 'b' => 2]); // will be encoded to '{"a": 1, "b": 2}'
 * ```
 */
final class JsonExpression implements ExpressionInterface
{
    /**
     * @param mixed $value The json's content. In can be represented as
     * - an `array` of values;
     * - an instance which implements {@see Traversable} or {@see JsonSerializable} and represents an array of values;
     * - an instance of {@see QueryInterface} that represents an SQL sub-query;
     * - a valid JSON encoded array as a `string`, e.g. `'[1,2,3]'` or `'{"a":1,"b":2}'`;
     * - any other value compatible with {@see \json_encode()} input requirements.
     * @param string|null $type Type of JSON, expression should be cast to. Defaults to `null`, meaning no explicit
     * casting will be performed. This property will be encountered only for DBMSs that support different types of JSON.
     * For example, PostgresSQL has `json` and `jsonb` types.
     */
    public function __construct(private readonly mixed $value, private readonly string|null $type = null)
    {
    }

    /**
     * The json's content. In can be represented as
     * - an `array` of values;
     * - an instance which implements {@see Traversable} or {@see JsonSerializable} and represents an array of values;
     * - an instance of {@see QueryInterface} that represents an SQL sub-query;
     * - a valid JSON encoded array as a `string`, e.g. `[1,2,3]` or `'{"a":1,"b":2}'`;
     * - any other value compatible with {@see \json_encode()} input requirements.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Type of JSON, expression should be cast to. Defaults to `null`, meaning no explicit casting will be performed.
     * This property will be encountered only for DBMSs that support different types of JSON.
     * For example, PostgresSQL has `json` and `jsonb` types.
     */
    public function getType(): string|null
    {
        return $this->type;
    }
}
