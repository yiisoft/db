<?php

namespace Yiisoft\Db\Expressions;

use Yiisoft\Db\Exceptions\InvalidConfigException;
use Yiisoft\Db\Querys\QueryInterface;

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

    /**
     * @var mixed the value to be encoded to JSON.
     *
     * The value must be compatible with {@see \Yiisoft\Json\Json::encode()|Json::encode()]} input requirements.
     */
    protected $value;

    /**
     * @var string|null Type of JSON, expression should be casted to. Defaults to `null`, meaning
     * no explicit casting will be performed.
     *
     * This property will be encountered only for DBMSs that support different types of JSON.
     *
     * For example, PostgreSQL has `json` and `jsonb` types.
     */
    protected ?string $type;

    /**
     * JsonExpression constructor.
     *
     * @param mixed $value the value to be encoded to JSON. The value must be compatible with
     * {@see \Yiisoft\Json\Json::encode()|Json::encode()} requirements.
     * @param string|null $type  the type of the JSON. See {@see JsonExpression::type}
     *
     * @see type
     */
    public function __construct($value, $type = null)
    {
        if ($value instanceof self) {
            $value = $value->getValue();
        }

        $this->value = $value;
        $this->type = $type;
    }

    /**
     * @return mixed
     *
     * @see value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return null|string the type of JSON
     *
     * @see type
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
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource.
     */
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
