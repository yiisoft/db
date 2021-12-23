<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

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
class JsonExpression implements ExpressionInterface, \JsonSerializable
{
    public const TYPE_JSON = 'json';
    public const TYPE_JSONB = 'jsonb';
    protected $value;
    protected ?string $type;

    public function __construct($value, ?string $type = null)
    {
        if ($value instanceof self) {
            $value = $value->getValue();
        }

        $this->value = $value;
        $this->type = $type;
    }

    /**
     * The value must be compatible with {@see \Yiisoft\Json\Json::encode()|Json::encode()} input requirements.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Type of JSON, expression should be casted to. Defaults to `null`, meaning no explicit casting will be performed.
     *
     * This property will be encountered only for DBMSs that support different types of JSON.
     *
     * For example, PostgreSQL has `json` and `jsonb` types.
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
     * @return mixed data which can be serialized by <b>json_encode</b>, which is a value of any type other than a
     * resource.
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $value = $this->getValue();
        if ($value instanceof QueryInterface) {
            throw new InvalidConfigException(
                'The JsonExpression class can not be serialized to JSON when the value is a QueryInterface object'
            );
        }

        return $value;
    }
}
