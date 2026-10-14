<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Value;

use InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;

use function explode;
use function is_string;

/**
 * Represents an identifier, such as a column name, optionally prefixed with table and schema names, that is always
 * quoted strictly.
 *
 * Unlike {@see ColumnName}, which uses heuristic quoting and keeps names containing `(`, `[[`, `{{` or starting
 * with a quote character as is, this expression always renders a properly quoted identifier and never an SQL fragment.
 * Each name part is enclosed in quote characters of the DBMS, quote characters inside the part are escaped, and special
 * sequences (`(`, `*`, `[[...]]`, `{{...}}`, `%`) are quoted literally. So it's safe to use with names taken from
 * untrusted input:
 *
 * ```php
 * new Identifier('name'); // "name"
 * new Identifier('t.name'); // "t"."name"
 * new Identifier('s.t.name'); // "s"."t"."name"
 * new Identifier('LOWER(name)'); // "LOWER(name)"
 * new Identifier('a"b'); // "a""b"
 * ```
 *
 * A string name is split into parts by dots. To use a name part that contains a dot, pass the parts as an array:
 *
 * ```php
 * new Identifier(['t', 'na.me']); // "t"."na.me"
 * ```
 */
final class Identifier implements ExpressionInterface
{
    /**
     * @var string[] The identifier parts, for example, schema, table and column names.
     * @psalm-var non-empty-list<string>
     */
    public readonly array $parts;

    /**
     * @param string|string[] $name The identifier. It can be:
     * - a string, which is split into parts by dots, for example, `schema.table.column`, `table.column` or `column`;
     * - an array of parts, which are used as is, for example, `['table', 'column.with.dots']`.
     *
     * Empty name parts are allowed, since some DBMS (for example, SQLite) support empty identifiers.
     *
     * @psalm-param string|list<string> $name
     *
     * @throws InvalidArgumentException If an empty array is passed.
     */
    public function __construct(string|array $name)
    {
        if ($name === []) {
            throw new InvalidArgumentException('Identifier must have at least one part.');
        }

        $this->parts = is_string($name) ? explode('.', $name) : $name;
    }
}
