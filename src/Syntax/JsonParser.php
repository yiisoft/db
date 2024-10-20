<?php

declare(strict_types=1);

namespace Yiisoft\Db\Syntax;


final class JsonParser implements ArrayParserInterface, StructuredParserInterface
{
    public function parse(string $value): array|null
    {
        /** @var array|null */
        return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
    }
}
