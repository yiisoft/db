<?php

declare(strict_types=1);

namespace Yiisoft\Db\Syntax;

use JsonException;

use function json_decode;

final class JsonParser implements ArrayParserInterface, StructuredParserInterface
{
    /**
     * @throws JsonException
     */
    public function parse(string $value): array|null
    {
        /** @var array|null */
        return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
    }
}
