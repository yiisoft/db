<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

use JsonSerializable;

/**
 * Object that implements the `JsonSerializable` interface for testing purposes.
 */
final class JsonSerializableObject implements JsonSerializable
{
    public function __construct(private readonly array $data)
    {
    }

    public function jsonSerialize(): mixed
    {
        return $this->data;
    }
}
