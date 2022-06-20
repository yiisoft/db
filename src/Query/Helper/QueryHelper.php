<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Helper;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\QuoterInterface;

use function array_key_exists;
use function array_shift;
use function array_unshift;
use function is_string;
use function preg_match;
use function preg_split;
use function str_contains;
use function strcasecmp;
use function strtoupper;
use function trim;

final class QueryHelper
{
    /**
     * Clean up table names and aliases.
     *
     * Both aliases and names are enclosed into {{ and }}.
     *
     * @param array $tableNames non-empty array
     * @param QuoterInterface $quoter The quoter used to quote table names and column names.
     *
     * @throws InvalidArgumentException
     *
     * @psalm-return array<array-key, ExpressionInterface|string> table names indexed by aliases
     */
    public function cleanUpTableNames(array $tableNames, QuoterInterface $quoter): array
    {
        $cleanedUpTableNames = [];
        $pattern = <<<PATTERN
        ~^\s*((?:['"`\[]|{{).*?(?:['"`\]]|}})|\(.*?\)|.*?)(?:(?:\s+(?:as)?\s*)((?:['"`\[]|{{).*?(?:['"`\]]|}})|.*?))?\s*$~iux
        PATTERN;

        /** @psalm-var array<array-key, Expression|string> $tableNames */
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

            if ($tableName instanceof Expression) {
                $cleanedUpTableNames[$quoter->ensureNameQuoted($alias)] = $tableName;
            } elseif ($tableName instanceof QueryInterface) {
                $cleanedUpTableNames[$quoter->ensureNameQuoted($alias)] = $tableName;
            } else {
                $cleanedUpTableNames[$quoter->ensureNameQuoted($alias)] = $quoter->ensureNameQuoted(
                    (string) $tableName
                );
            }
        }

        return $cleanedUpTableNames;
    }

    /**
     * Removes {@see isEmpty()|empty operands} from the given query condition.
     *
     * @param array|string $condition the original condition
     *
     * @return array|string the condition with {@see isEmpty()|empty operands} removed.
     */
    public function filterCondition(array|string $condition): array|string
    {
        if (!is_array($condition)) {
            return $condition;
        }

        if (!isset($condition[0])) {
            /** hash format: 'column1' => 'value1', 'column2' => 'value2', ... */
            /** @var mixed $value */
            foreach ($condition as $name => $value) {
                if ($this->isEmpty($value)) {
                    unset($condition[$name]);
                }
            }

            return $condition;
        }

        /** operator format: operator, operand 1, operand 2, ... */
        /** @var string */
        $operator = array_shift($condition);

        switch (strtoupper($operator)) {
            case 'NOT':
            case 'AND':
            case 'OR':
                /** @psalm-var array<array-key, array|string> $condition */
                foreach ($condition as $i => $operand) {
                    $subCondition = $this->filterCondition($operand);
                    if ($this->isEmpty($subCondition)) {
                        unset($condition[$i]);
                    } else {
                        $condition[$i] = $subCondition;
                    }
                }

                if (empty($condition)) {
                    return [];
                }

                break;
            case 'BETWEEN':
            case 'NOT BETWEEN':
                if (array_key_exists(1, $condition) && array_key_exists(2, $condition)) {
                    if ($this->isEmpty($condition[1]) || $this->isEmpty($condition[2])) {
                        return [];
                    }
                }

                break;
            default:
                if (array_key_exists(1, $condition) && $this->isEmpty($condition[1])) {
                    return [];
                }
        }

        array_unshift($condition, $operator);

        return $condition;
    }

    /**
     * Returns a value indicating whether the give value is "empty".
     *
     * The value is considered "empty", if one of the following conditions is satisfied:
     *
     * - it is `null`,
     * - an empty string (`''`),
     * - a string containing only whitespace characters,
     * - or an empty array.
     *
     * @param mixed $value
     *
     * @return bool if the value is empty
     */
    public function isEmpty(mixed $value): bool
    {
        return $value === '' || $value === [] || $value === null || (is_string($value) && trim($value) === '');
    }

    /**
     * Normalizes format of ORDER BY data.
     *
     * @param array|ExpressionInterface|string $columns the columns value to normalize.
     *
     * See {@see orderBy} and {@see addOrderBy}.
     *
     * @return array
     */
    public function normalizeOrderBy(array|string|ExpressionInterface $columns): array
    {
        if ($columns instanceof ExpressionInterface) {
            return [$columns];
        }

        if (is_array($columns)) {
            return $columns;
        }

        $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        $result = [];

        foreach ($columns as $column) {
            if (preg_match('/^(.*?)\s+(asc|desc)$/i', $column, $matches)) {
                $result[$matches[1]] = strcasecmp($matches[2], 'desc') ? SORT_ASC : SORT_DESC;
            } else {
                $result[$column] = SORT_ASC;
            }
        }

        return $result;
    }

    /**
     * Normalizes the SELECT columns passed to {@see select()} or {@see addSelect()}.
     *
     * @param array|ExpressionInterface|string $columns
     *
     * @return array
     */
    public function normalizeSelect(array|ExpressionInterface|string $columns): array
    {
        if ($columns instanceof ExpressionInterface) {
            $columns = [$columns];
        } elseif (!is_array($columns)) {
            $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        }

        $select = [];

        /** @psalm-var array<array-key, ExpressionInterface|string> $columns */
        foreach ($columns as $columnAlias => $columnDefinition) {
            if (is_string($columnAlias)) {
                // Already in the normalized format, good for them.
                $select[$columnAlias] = $columnDefinition;
                continue;
            }

            if (is_string($columnDefinition)) {
                if (
                    preg_match('/^(.*?)(?i:\s+as\s+|\s+)([\w\-_.]+)$/', $columnDefinition, $matches) &&
                    !preg_match('/^\d+$/', $matches[2]) &&
                    !str_contains($matches[2], '.')
                ) {
                    /** Using "columnName as alias" or "columnName alias" syntax */
                    $select[$matches[2]] = $matches[1];
                    continue;
                }
                if (!str_contains($columnDefinition, '(')) {
                    /** Normal column name, just alias it to itself to ensure it's not selected twice */
                    $select[$columnDefinition] = $columnDefinition;
                    continue;
                }
            }

            // Either a string calling a function, DB expression, or sub-query
            /** @var string */
            $select[] = $columnDefinition;
        }

        return $select;
    }
}
