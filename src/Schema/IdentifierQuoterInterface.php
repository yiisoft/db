<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use Yiisoft\Db\Expression\Value\Identifier;

/**
 * This interface provides a method to quote identifiers strictly, without any heuristics.
 *
 * Unlike {@see QuoterInterface::quoteColumnName()}, the method always treats each name part as an identifier and never
 * as an SQL fragment. It's used to build {@see Identifier} expressions.
 *
 * The interface is separated from {@see QuoterInterface} to keep backward compatibility with third-party quoter
 * implementations. It will be merged into {@see QuoterInterface} in the next major version.
 */
interface IdentifierQuoterInterface
{
    /**
     * Quotes an identifier consisting of one or more parts, for example, `schema`, `table` and `column`.
     *
     * Each part is enclosed in quote characters of the DBMS, the closing quote character inside the part is escaped
     * by doubling it, and the parts are joined with a dot. Special characters and sequences, such as `(`, `*`,
     * `[[...]]`, `{{...}}` or `%`, aren't processed and are quoted literally.
     *
     * For example, `quoteIdentifier('t', 'a"b')` returns `"t"."a""b"` for DBMS that use double quotes.
     *
     * @param string ...$parts The identifier parts to quote.
     *
     * @return string The quoted identifier.
     */
    public function quoteIdentifier(string ...$parts): string;
}
