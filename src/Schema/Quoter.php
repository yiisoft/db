<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use function addcslashes;
use function explode;
use function implode;
use function is_string;
use function preg_replace_callback;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strpos;
use function strrpos;
use function substr;

class Quoter implements QuoterInterface
{
    public function __construct(
        /** @psalm-var string[]|string */
        private array|string $columnQuoteCharacter,
        /** @psalm-var string[]|string */
        private array|string $tableQuoteCharacter,
        private string $tablePrefix = ''
    ) {
    }

    public function getTableNameParts(string $name): array
    {
        $parts = array_slice(explode('.', $name), -2, 2);
        return array_map(function ($part) {
            return $this->unquoteSimpleTableName($part);
        }, $parts);
    }

    public function ensureNameQuoted(string $name): string
    {
        $name = str_replace(["'", '"', '`', '[', ']'], '', $name);

        if ($name && !preg_match('/^{{.*}}$/', $name)) {
            return '{{' . $name . '}}';
        }

        return $name;
    }

    public function ensureColumnName(string $name): string
    {
        if (strrpos($name, '.') !== false) {
            $parts = explode('.', $name);
            $name = $parts[count($parts)-1];
        }
        return preg_replace('|^\[\[([_\w\-. ]+)\]\]$|', '\1', $name);
    }

    public function quoteColumnName(string $name): string
    {
        if (str_contains($name, '(') || str_contains($name, '[[')) {
            return $name;
        }

        if (($pos = strrpos($name, '.')) !== false) {
            $prefix = $this->quoteTableName(substr($name, 0, $pos)) . '.';
            $name = substr($name, $pos + 1);
        } else {
            $prefix = '';
        }

        if (str_contains($name, '{{')) {
            return $name;
        }

        return $prefix . $this->quoteSimpleColumnName($name);
    }

    public function quoteSimpleColumnName(string $name): string
    {
        if (is_string($this->columnQuoteCharacter)) {
            $startingCharacter = $endingCharacter = $this->columnQuoteCharacter;
        } else {
            [$startingCharacter, $endingCharacter] = $this->columnQuoteCharacter;
        }

        return $name === '*' || str_contains($name, $startingCharacter) ? $name : $startingCharacter . $name
            . $endingCharacter;
    }

    public function quoteSimpleTableName(string $name): string
    {
        if (is_string($this->tableQuoteCharacter)) {
            $startingCharacter = $endingCharacter = $this->tableQuoteCharacter;
        } else {
            [$startingCharacter, $endingCharacter] = $this->tableQuoteCharacter;
        }

        return str_contains($name, $startingCharacter) ? $name : $startingCharacter . $name . $endingCharacter;
    }

    public function quoteSql(string $sql): string
    {
        return preg_replace_callback(
            '/({{(%?[\w\-. ]+%?)}}|\\[\\[([\w\-. ]+)]])/',
            function ($matches) {
                if (isset($matches[3])) {
                    return $this->quoteColumnName($matches[3]);
                }

                return str_replace('%', $this->tablePrefix, $this->quoteTableName($matches[2]));
            },
            $sql
        );
    }

    public function quoteTableName(string $name): string
    {
        if (str_starts_with($name, '(') && strpos($name, ')') === strlen($name) - 1) {
            return $name;
        }

        if (str_contains($name, '{{')) {
            return $name;
        }

        if (!str_contains($name, '.')) {
            return $this->quoteSimpleTableName($name);
        }

        $parts = $this->getTableNameParts($name);

        foreach ($parts as $i => $part) {
            $parts[$i] = $this->quoteSimpleTableName($part);
        }

        return implode('.', $parts);
    }

    public function quoteValue(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return '\'' . str_replace('\'', '\'\'', addcslashes($value, "\000\032")) . '\'';
    }

    public function unquoteSimpleColumnName(string $name): string
    {
        if (is_string($this->columnQuoteCharacter)) {
            $startingCharacter = $this->columnQuoteCharacter;
        } else {
            $startingCharacter = $this->columnQuoteCharacter[0];
        }

        return !str_contains($name, $startingCharacter) ? $name : substr($name, 1, -1);
    }

    public function unquoteSimpleTableName(string $name): string
    {
        if (is_string($this->tableQuoteCharacter)) {
            $startingCharacter = $this->tableQuoteCharacter;
        } else {
            $startingCharacter = $this->tableQuoteCharacter[0];
        }

        return !str_contains($name, $startingCharacter) ? $name : substr($name, 1, -1);
    }
}
