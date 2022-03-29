<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use JsonSerializable;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Query\QueryInterface;

/**
 * Class JsonExpression represents data that should be encoded to JSON.
 *
 * For example:
 *
 * ```php
 * new JsonExpression(['a' => 1, 'b' => 2]); // will be encoded to '{"a": 1, "b": 2}'
 * ```
 */
class JsonExpression implements ExpressionInterface, JsonSerializable
{
    public const TYPE_JSON = 'json';
    public const TYPE_JSONB = 'jsonb';

    public function __construct(protected mixed $value, private ?string $type = null)
    {
        if ($value instanceof self) {
            $this->value = $value->getValue();
        }
    }

    /**
     * The value must be compatible with {@see \Yiisoft\Json\Json::encode()|Json::encode()} input requirements.
     *
     * @return mixed
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
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @throws InvalidConfigException when JsonExpression contains QueryInterface object
     *
     * @return mixed Data which can be serialized by <b>json_encode</b>, which is a value of any type other than a
     * resource.
     */
    public function jsonSerialize(): mixed
    {
        /** @var mixed */
        $value = $this->getValue();

        if ($value instanceof QueryInterface) {
            throw new InvalidConfigException(
                'The JsonExpression class can not be serialized to JSON when the value is a QueryInterface object'
            );
        }

        return $value;
    }
}
