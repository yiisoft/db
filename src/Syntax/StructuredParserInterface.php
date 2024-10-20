<?php

declare(strict_types=1);

namespace Yiisoft\Db\Syntax;

/**
 * Should be implemented by classes that can parse a database structured representation into a PHP array.
 */
interface StructuredParserInterface
{
    /**
     * Parses a string value into an array.
     *
     * @param string $value The value to be parsed.
     *
     * @return array|null The parsed array, `null` if the value cannot be parsed.
     */
    public function parse(string $value): array|null;
}
