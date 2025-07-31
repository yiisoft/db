<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;

use function addcslashes;
use function array_map;
use function array_slice;
use function count;
use function explode;
use function implode;
use function is_string;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strrpos;
use function substr;

/**
 * The Quoter is a class that's used to quote table and column names for use in SQL statements.
 *
 * It provides a set of methods for quoting different types of names, such as table names, column names, and schema
 * names.
 *
 * The Quoter class is used by {@see \Yiisoft\Db\QueryBuilder\AbstractQueryBuilder} to quote names.
 *
 * It's also used by {@see \Yiisoft\Db\Command\AbstractCommand} to quote names in SQL statements before passing them to
 * database servers.
 */
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

    public function cleanUpTableNames(array $tableNames): array
    {
        $cleanedUpTableNames = [];
        $pattern = <<<PATTERN
        ~^\s*((?:['"`\[]|{{).*?(?:['"`\]]|}})|\(.*?\)|.*?)(?:\s+(?:as\s+)?((?:['"`\[]|{{).*?(?:['"`\]]|}})|.*?))?\s*$~iux
        PATTERN;

        /** @psalm-var array<array-key, ExpressionInterface|string> $tableNames */
        foreach ($tableNames as $alias => $tableName) {
            if (is_string($tableName) && !is_string($alias)) {
                if (preg_match($pattern, $tableName, $matches)) {
                    if (isset($matches[2])) {
                        [, $tableName, $alias] = $matches;
                    } else {
                        $tableName = $alias = $matches[1];
                    }
                }
            }

            if (!is_string($alias)) {
                throw new InvalidArgumentException(
                    'To use Expression in from() method, pass it in array format with alias.'
                );
            }

            if (is_string($tableName)) {
                $cleanedUpTableNames[$this->ensureNameQuoted($alias)] = $this->ensureNameQuoted($tableName);
            } elseif ($tableName instanceof ExpressionInterface) {
                $cleanedUpTableNames[$this->ensureNameQuoted($alias)] = $tableName;
            } else {
                throw new InvalidArgumentException(
                    'Use ExpressionInterface without cast to string as object of tableName'
                );
            }
        }

        return $cleanedUpTableNames;
    }

    public function getRawTableName(string $name): string
    {
        if (str_contains($name, '{{')) {
            /** @var string $name */
            $name = preg_replace('/{{(.*?)}}/', '\1', $name);

            return str_replace('%', $this->tablePrefix, $name);
        }

        return $name;
    }

    /** @psalm-return array{schemaName?: string, name: string} */
    public function getTableNameParts(string $name): array
    {
        $parts = array_reverse(array_slice(explode('.', $name), -2, 2));
        /** @var string[] */
        $parts = array_map($this->unquoteSimpleTableName(...), $parts);

        if (!isset($parts[1])) {
            return ['name' => $parts[0]];
        }

        return [
            'schemaName' => $parts[1],
            'name' => $parts[0],
        ];
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
            $name = $parts[count($parts) - 1];
        }

        /** @var string */
        return preg_replace('|^\[\[([\w\-. ]+)]]$|', '\1', $name);
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

        return $name === '*' || str_starts_with($name, $startingCharacter)
            ? $name
            : $startingCharacter . $name . $endingCharacter;
    }

    public function quoteSimpleTableName(string $name): string
    {
        if (is_string($this->tableQuoteCharacter)) {
            $startingCharacter = $endingCharacter = $this->tableQuoteCharacter;
        } else {
            [$startingCharacter, $endingCharacter] = $this->tableQuoteCharacter;
        }

        return str_starts_with($name, $startingCharacter)
            ? $name
            : $startingCharacter . $name . $endingCharacter;
    }

    public function quoteSql(string $sql): string
    {
        /** @var string */
        return preg_replace_callback(
            '/({{(%?[\w\-. ]+)%?}}|\\[\\[([\w\-. ]+)]])/',
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
        if (str_starts_with($name, '(')) {
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

    public function quoteValue(string $value): string
    {
        return "'" . str_replace("'", "''", addcslashes($value, "\000\032")) . "'";
    }

    public function setTablePrefix(string $value): void
    {
        $this->tablePrefix = $value;
    }

    public function unquoteSimpleColumnName(string $name): string
    {
        if (is_string($this->columnQuoteCharacter)) {
            $startingCharacter = $this->columnQuoteCharacter;
        } else {
            $startingCharacter = $this->columnQuoteCharacter[0];
        }

        return !str_starts_with($name, $startingCharacter)
            ? $name
            : substr($name, 1, -1);
    }

    public function unquoteSimpleTableName(string $name): string
    {
        if (is_string($this->tableQuoteCharacter)) {
            $startingCharacter = $this->tableQuoteCharacter;
        } else {
            $startingCharacter = $this->tableQuoteCharacter[0];
        }

        return !str_starts_with($name, $startingCharacter)
            ? $name
            : substr($name, 1, -1);
    }
}
