<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions\Builder;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\Conditions\Interface\LikeConditionInterface;
use Yiisoft\Db\Query\QueryBuilderInterface;

use function implode;
use function is_array;
use function is_string;
use function preg_match;
use function strtoupper;

/**
 * Class LikeConditionBuilder builds objects of {@see LikeCondition}.
 */
class LikeConditionBuilder implements ExpressionBuilderInterface
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * @var array map of chars to their replacements in LIKE conditions. By default, it's configured to escape
     * `%`, `_` and `\` with `\`.
     */
    protected array $escapingReplacements = [
        '%' => '\%',
        '_' => '\_',
        '\\' => '\\\\',
    ];
    protected ?string $escapeCharacter = null;

    /**
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function build(LikeConditionInterface $expression, array &$params = []): string
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
        } elseif (is_string($column) && !str_contains($column, '(')) {
            $column = $this->queryBuilder->quoter()->quoteColumnName($column);
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
     * @param string $operator
     *
     * @throws InvalidArgumentException
     *
     * @return array
     *
     * @psalm-return array{0: string, 1: bool, 2: string}
     */
    protected function parseOperator(string $operator): array
    {
        if (!preg_match('/^(AND |OR |)((NOT |)I?LIKE)/', $operator, $matches)) {
            throw new InvalidArgumentException("Invalid operator '$operator'.");
        }

        $andor = ' ' . (!empty($matches[1]) ? $matches[1] : 'AND ');
        $not = !empty($matches[3]);
        $operator = $matches[2];

        return [$andor, $not, $operator];
    }

    /**
     * @return string character used to escape special characters in LIKE conditions. By default, it's assumed to be `\`.
     */
    private function getEscapeSql(): string
    {
        if ($this->escapeCharacter !== null) {
            return " ESCAPE '{$this->escapeCharacter}'";
        }

        return '';
    }
}
