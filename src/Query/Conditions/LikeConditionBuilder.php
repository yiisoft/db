<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionBuilderTrait;
use Yiisoft\Db\Expression\ExpressionInterface;

use function implode;
use function is_array;
use function is_string;
use function preg_match;
use function strpos;
use function strtoupper;

/**
 * Class LikeConditionBuilder builds objects of {@see LikeCondition}.
 */
class LikeConditionBuilder implements ExpressionBuilderInterface
{
    use ExpressionBuilderTrait;

    /**
     * @var array map of chars to their replacements in LIKE conditions. By default it's configured to escape
     * `%`, `_` and `\` with `\`.
     */
    protected array $escapingReplacements = [
        '%' => '\%',
        '_' => '\_',
        '\\' => '\\\\',
    ];
    protected ?string $escapeCharacter = null;

    /**
     * Method builds the raw SQL from the $expression that will not be additionally escaped or quoted.
     *
     * @param ExpressionInterface|LikeCondition $expression the expression to be built.
     * @param array $params the binding parameters.
     *
     * @return string the raw SQL that will not be additionally escaped or quoted.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $operator = strtoupper($expression->getOperator());
        $column = $expression->getColumn();
        $values = $expression->getValue();
        $escape = $expression->getEscapingReplacements();

        if ($escape === null || $escape === []) {
            $escape = $this->escapingReplacements;
        }

        [$andor, $not, $operator] = $this->parseOperator($operator);

        if (!is_array($values)) {
            $values = [$values];
        }

        if (empty($values)) {
            return $not ? '' : '0=1';
        }

        if ($column instanceof ExpressionInterface) {
            $column = $this->queryBuilder->buildExpression($column, $params);
        } elseif (is_string($column) && strpos($column, '(') === false) {
            $column = $this->queryBuilder->getDb()->quoteColumnName($column);
        }

        $escapeSql = $this->getEscapeSql();

        $parts = [];
        foreach ($values as $value) {
            if ($value instanceof ExpressionInterface) {
                $phName = $this->queryBuilder->buildExpression($value, $params);
            } else {
                $phName = $this->queryBuilder->bindParam(
                    empty($escape) ? $value : ('%' . strtr($value, $escape) . '%'),
                    $params
                );
            }
            $parts[] = "{$column} {$operator} {$phName}{$escapeSql}";
        }

        return implode($andor, $parts);
    }

    /**
     * @return string|null character used to escape special characters in LIKE conditions.
     *
     * By default it's assumed to be `\`.
     */
    private function getEscapeSql(): ?string
    {
        if ($this->escapeCharacter !== null) {
            return " ESCAPE '{$this->escapeCharacter}'";
        }

        return '';
    }

    /**
     * @param string $operator
     *
     * @throws InvalidArgumentException
     *
     * @return array
     */
    protected function parseOperator(string $operator): array
    {
        if (!preg_match('/^(AND |OR |)(((NOT |))I?LIKE)/', $operator, $matches)) {
            throw new InvalidArgumentException("Invalid operator '$operator'.");
        }

        $andor = ' ' . (!empty($matches[1]) ? $matches[1] : 'AND ');
        $not = !empty($matches[3]);
        $operator = $matches[2];

        return [$andor, $not, $operator];
    }
}
