<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Value;

use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * Represents data to encode to JSON.
 *
 * For example:
 *
 * ```php
 * new JsonValue(['a' => 1, 'b' => 2]); // will be encoded to '{"a": 1, "b": 2}'
 * ```
 */
final class JsonValue implements ExpressionInterface
{
    /**
     * @param mixed $value The JSON content. It can be represented as
     * - an `array` of values;
     * - an instance which implements {@see Traversable} or {@see JsonSerializable} and represents an array of values;
     * - an instance of {@see QueryInterface} that represents an SQL sub-query;
     * - a valid JSON encoded array as a `string`, e.g. `'[1,2,3]'` or `'{"a":1,"b":2}'`;
     * - any other value compatible with {@see \json_encode()} input requirements.
     * @param string|null $type Type of database column, value should be cast to. Defaults to `null`, meaning no explicit
     * casting will be performed. This property is used only for DBMSs that support different types of JSON.
     * For example, PostgresSQL has `json` and `jsonb` types.
     */
    public function __construct(public readonly mixed $value, public readonly string|null $type = null)
    {
    }
}
