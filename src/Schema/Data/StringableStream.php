<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Data;

use Stringable;

use function fclose;
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
     * @param resource|string $value The resource stream or the result of reading the stream.
     */
    public function __construct(private mixed $value)
    {
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
        if (is_resource($this->value)) {
            /** @var string */
            $this->value = stream_get_contents($this->value);
        }

        return (string) $this->value;
    }

    /**
     * @return resource|string The resource stream or the result of reading the stream.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }
}
