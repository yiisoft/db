<?php

declare(strict_types=1);

namespace Yiisoft\Db\Syntax;

/**
 * Should be implemented by classes that can parse a string value into an array.
 */
interface ParserToArrayInterface
{
    /**
     * Parses a string value into an array.
     *
     * @param string $value The value to be parsed.
     *
     * @return array|null The parsed value.
     */
    public function parse(string $value): array|null;
}
