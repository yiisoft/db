<?php

declare(strict_types=1);

namespace Yiisoft\Db\Syntax;

/**
 * Parses column definition string. For example, `string(255)` or `int unsigned`.
 */
interface ColumnDefinitionParserInterface
{
    /**
     * Parses column definition string.
     *
     * @param string $definition The column definition string. For example, `string(255)` or `int unsigned`.
     *
     * @return array The column information.
     *
     * @psalm-return array{
     *     check?: string,
     *     collation?: string,
     *     comment?: string,
     *     defaultValueRaw?: string,
     *     dimension?: positive-int,
     *     enumValues?: list<string>,
     *     extra?: string,
     *     notNull?: bool,
     *     scale?: int,
     *     size?: int,
     *     type: lowercase-string,
     *     unique?: bool,
     *     unsigned?: bool,
     * }
     */
    public function parse(string $definition): array;
}
