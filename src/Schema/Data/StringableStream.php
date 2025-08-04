<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Data;

use LogicException;
use Stringable;
use Yiisoft\Db\Constant\GettypeResult;

use function fclose;
use function gettype;
use function is_resource;
use function stream_get_contents;

/**
 * Represents a resource stream to be used as a {@see Stringable} value.
 *
 * ```php
 * use Yiisoft\Db\Schema\Data\ResourceStream;
 *
 * // @var resource $resource
 * $stream = new StringableStream($resource);
 *
 * echo $stream;
 * ```
 */
final class StringableStream implements Stringable
{
    /**
     * @var resource|string $value The resource stream or the result of reading the stream.
     */
    private mixed $value;

    /**
     * @param resource $value The open resource stream.
     */
    public function __construct(mixed $value)
    {
        $this->value = $value;
    }

    /**
     * Closes the resource.
     */
    public function __destruct()
    {
        if (is_resource($this->value)) {
            fclose($this->value);
        }
    }

    /**
     * @return string[] Prepared values for serialization.
     */
    public function __serialize(): array
    {
        return ['value' => $this->__toString()];
    }

    /**
     * @return string The result of reading the resource stream.
     */
    public function __toString(): string
    {
        /**
         * @psalm-suppress PossiblyFalsePropertyAssignmentValue, PossiblyInvalidArgument
         * @var string
         */
        return match (gettype($this->value)) {
            GettypeResult::RESOURCE => $this->value = stream_get_contents($this->value),
            GettypeResult::RESOURCE_CLOSED => throw new LogicException('Resource is closed.'),
            default => $this->value,
        };
    }

    /**
     * @return resource|string The resource stream or the result of reading the stream.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }
}
