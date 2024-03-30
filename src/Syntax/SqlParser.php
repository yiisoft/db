<?php

declare(strict_types=1);

namespace Yiisoft\Db\Syntax;

use function strlen;
use function substr;

/**
 * SQL parser.
 *
 * This class provides methods to parse SQL statements and extract placeholders from them.
 */
class SqlParser
{
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
    public function getNextPlaceholder(int|null &$position = null): string|null
    {
        $result = null;
        $length = $this->length - 1;

        while ($this->position < $length) {
            $pos = $this->position++;

            match ($this->sql[$pos]) {
                ':' => ($word = $this->parseWord()) === ''
                    ? $this->skipChars(':')
                    : $result = ':' . $word,
                '"', "'" => $this->skipQuotedWithoutEscape($this->sql[$pos]),
                '-' => $this->sql[$this->position] === '-'
                    ? ++$this->position && $this->skipToAfterChar("\n")
                    : null,
                '/' => $this->sql[$this->position] === '*'
                    ? ++$this->position && $this->skipToAfterString('*/')
                    : null,
                default => null,
            };

            if ($result !== null) {
                $position = $pos;

                return $result;
            }
        }

        return null;
    }

    /**
     * Parses and returns word symbols. Equals to `\w+` in regular expressions.
     *
     * @return string Parsed word symbols.
     */
    final protected function parseWord(): string
    {
        $word = '';
        $continue = true;

        while ($continue && $this->position < $this->length) {
            match ($this->sql[$this->position]) {
                '_', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
                'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u',
                'v', 'w', 'x', 'y', 'z',
                'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U',
                'V', 'W', 'X', 'Y', 'Z' => $word .= $this->sql[$this->position++],
                default => $continue = false,
            };
        }

        return $word;
    }

    /**
     * Parses and returns identifier. Equals to `[_a-zA-Z]\w+` in regular expressions.
     *
     * @return string Parsed identifier.
     */
    protected function parseIdentifier(): string
    {
        return match ($this->sql[$this->position]) {
            '_',
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u',
            'v', 'w', 'x', 'y', 'z',
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U',
            'V', 'W', 'X', 'Y', 'Z' => $this->sql[$this->position++] . $this->parseWord(),
            default => '',
        };
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
        for (; $this->position < $this->length; ++$this->position) {
            if ($this->sql[$this->position] === $endChar) {
                ++$this->position;
                return;
            }

            if ($this->sql[$this->position] === '\\') {
                ++$this->position;
            }
        }
    }

    /**
     * Skips all specified characters.
     */
    final protected function skipChars(string $char): void
    {
        while ($this->position < $this->length && $this->sql[$this->position] === $char) {
            ++$this->position;
        }
    }

    /**
     * Skips to the character after the specified character.
     */
    final protected function skipToAfterChar(string $char): void
    {
        for (; $this->position < $this->length; ++$this->position) {
            if ($this->sql[$this->position] === $char) {
                ++$this->position;
                return;
            }
        }
    }

    /**
     * Skips to the character after the specified string.
     */
    final protected function skipToAfterString(string $string): void
    {
        $firstChar = $string[0];
        $subString = substr($string, 1);
        $length = strlen($subString);

        do {
            $this->skipToAfterChar($firstChar);

            if (substr($this->sql, $this->position, $length) === $subString) {
                $this->position += $length;
                return;
            }
        } while ($this->position + $length < $this->length);
    }
}
