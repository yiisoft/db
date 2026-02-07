<?php

declare(strict_types=1);

namespace Yiisoft\Db\Syntax;

use function preg_match;
use function preg_quote;
use function strcspn;
use function strlen;
use function strspn;
use function substr;

/**
 * SQL parser.
 *
 * This class provides methods to parse SQL statements and extract placeholders from them.
 */
abstract class AbstractSqlParser
{
    /** @var string Letter symbols, equals to `[_a-zA-Z]` in regular expressions */
    protected const LETTER_CHARS = '_abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    /** @var string Word symbols, equals to `\w` in regular expressions */
    protected const WORD_CHARS = '0123456789' . self::LETTER_CHARS;

    /**
     * @var int Length of SQL statement.
     */
    protected int $length;

    /**
     * @var int Current position in SQL statement.
     */
    protected int $position = 0;

    /**
     * @param string $sql SQL statement to parse.
     */
    public function __construct(protected string $sql)
    {
        $this->length = strlen($sql);
    }

    /**
     * Returns the next placeholder from the current position in SQL statement.
     *
     * @param int|null $position Position of the placeholder in SQL statement.
     *
     * @return string|null The next placeholder or null if it is not found.
     */
    abstract public function getNextPlaceholder(?int &$position = null): ?string;

    /**
     * Parses and returns word symbols. Equals to `\w+` in regular expressions.
     *
     * @return string Parsed word symbols.
     */
    final protected function parseWord(): string
    {
        $length = strspn($this->sql, self::WORD_CHARS, $this->position);
        $word = substr($this->sql, $this->position, $length);
        $this->position += $length;

        return $word;
    }

    /**
     * Parses and returns identifier. Equals to `[_a-zA-Z]\w+` in regular expressions.
     *
     * @return string Parsed identifier.
     */
    protected function parseIdentifier(): string
    {
        $length = strspn($this->sql, self::LETTER_CHARS, $this->position);

        if ($length === 0) {
            return '';
        }

        $length += strspn($this->sql, self::WORD_CHARS, $this->position + $length);
        $word = substr($this->sql, $this->position, $length);
        $this->position += $length;

        return $word;
    }

    /**
     * Skips quoted string without escape characters.
     */
    final protected function skipQuotedWithoutEscape(string $endChar): void
    {
        do {
            $this->skipToAfterChar($endChar);
        } while (($this->sql[$this->position] ?? null) === $endChar && ++$this->position);
    }

    /**
     * Skips quoted string with escape characters.
     */
    final protected function skipQuotedWithEscape(string $endChar): void
    {
        preg_match("/(?>[^$endChar\\\\]+|\\\\.)*/", $this->sql, $matches, 0, $this->position);
        $this->position += strlen($matches[0]) + 1;
    }

    /**
     * Skips all specified characters.
     */
    final protected function skipChars(string $char): void
    {
        $length = strspn($this->sql, $char, $this->position);
        $this->position += $length;
    }

    /**
     * Skips to the character after the specified character.
     */
    final protected function skipToAfterChar(string $char): void
    {
        $length = strcspn($this->sql, $char, $this->position);
        $this->position += $length + 1;
    }

    /**
     * Skips to the character after the specified string.
     */
    final protected function skipToAfterString(string $string): void
    {
        $quotedString = preg_quote($string, '/');
        preg_match("/.*?$quotedString/", $this->sql, $matches, 0, $this->position);
        $this->position += strlen($matches[0]) + 1;
    }
}
