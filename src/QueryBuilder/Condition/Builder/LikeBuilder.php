<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Builder;

use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\Like;
use Yiisoft\Db\QueryBuilder\Condition\LikeMode;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function implode;
use function is_array;
use function preg_match;
use function str_contains;
use function strtoupper;
use function strtr;

/**
 * Build an object of {@see Like} into SQL expressions.
 *
 * @implements ExpressionBuilderInterface<Like>
 */
class LikeBuilder implements ExpressionBuilderInterface
{
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
        private readonly string|null $escapeSql = null
    ) {
    }

    /**
     * @var array Map of chars to their replacements in `LIKE` conditions. By default, it's configured to escape
     * `%`, `_` and `\` with `\`.
     */
    protected array $escapingReplacements = [
        '%' => '\%',
        '_' => '\_',
        '\\' => '\\\\',
    ];

    /**
     * Build SQL for {@see Like}.
     *
     * @param Like $expression
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $values = $expression->value;

        [$andor, $not, $operator] = $this->parseOperator($expression);

        if (!is_array($values)) {
            $values = [$values];
        }

        if (empty($values)) {
            return $not ? '' : '0=1';
        }

        $column = $this->prepareColumn($expression, $params);

        $parts = [];

        /** @psalm-var list<string|ExpressionInterface> $values */
        foreach ($values as $value) {
            $placeholderName = $this->preparePlaceholderName($value, $expression, $params);
            $parts[] = "$column $operator $placeholderName$this->escapeSql";
        }

        return implode($andor, $parts);
    }

    /**
     * Prepare column to use in SQL.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    protected function prepareColumn(Like $condition, array &$params): string
    {
        $column = $condition->column;

        if ($column instanceof ExpressionInterface) {
            return $this->queryBuilder->buildExpression($column, $params);
        }

        if (!str_contains($column, '(')) {
            return $this->queryBuilder->getQuoter()->quoteColumnName($column);
        }

        return $column;
    }

    /**
     * Prepare value to use in SQL.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @return string
     */
    protected function preparePlaceholderName(
        string|ExpressionInterface $value,
        Like $condition,
        array &$params,
    ): string {
        if ($value instanceof ExpressionInterface) {
            return $this->queryBuilder->buildExpression($value, $params);
        }

        if ($condition->escape) {
            $escapedValue = strtr($value, $this->escapingReplacements);
            $wrappedValue = match ($condition->mode) {
                LikeMode::Contains => '%' . $escapedValue . '%',
                LikeMode::StartsWith => $escapedValue . '%',
                LikeMode::EndsWith => '%' . $escapedValue,
            };
            return $this->queryBuilder->bindParam(new Param($wrappedValue, DataType::STRING), $params);
        }

        return $this->queryBuilder->bindParam(new Param($value, DataType::STRING), $params);
    }

    /**
     * Parses operator and returns its parts.
     *
     * @throws InvalidArgumentException
     *
     * @psalm-return array{0: string, 1: bool, 2: string}
     */
    protected function parseOperator(Like $condition): array
    {
        $operator = strtoupper($condition->operator);
        if (!preg_match('/^(AND |OR |)((NOT |)I?LIKE)/', $operator, $matches)) {
            throw new InvalidArgumentException("Invalid operator in like condition: \"$operator\"");
        }

        $andor = ' ' . (!empty($matches[1]) ? $matches[1] : 'AND ');
        $not = !empty($matches[3]);
        $operator = $matches[2];

        return [$andor, $not, $operator];
    }
}
