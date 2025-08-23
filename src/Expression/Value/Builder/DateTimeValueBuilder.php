<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Value\Builder;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use Stringable;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\GettypeResult;
use Yiisoft\Db\Expression\Builder\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\Value\DateTimeValue;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\Column\ColumnFactoryInterface;

use function date_create_immutable;
use function gettype;
use function sprintf;

/**
 * Builder for {@see DateTimeValue} expressions.
 *
 * @implements ExpressionBuilderInterface<DateTimeValue>
 *
 * @psalm-import-type ColumnInfo from ColumnFactoryInterface
 */
final class DateTimeValueBuilder implements ExpressionBuilderInterface
{
    private ColumnFactoryInterface $columnFactory;

    /**
     * @param QueryBuilderInterface $queryBuilder The query builder instance.
     */
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {
        $this->columnFactory = $this->queryBuilder->getColumnFactory();
    }

    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $value = $this->columnFactory
            ->fromType($expression->type, $this->prepareInfo($expression))
            ->dbTypecast($this->prepareValue($expression->value));
        return $this->queryBuilder->buildValue($value, $params);
    }

    private function prepareValue(int|float|string|Stringable|DateTimeInterface $value): DateTimeInterface
    {
        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        /** @psalm-suppress PossiblyInvalidArgument */
        $result = match (gettype($value)) {
            GettypeResult::STRING => $this->prepareStringValue($value),
            GettypeResult::INTEGER => DateTimeImmutable::createFromFormat('U', (string) $value),
            GettypeResult::DOUBLE => DateTimeImmutable::createFromFormat('U.u', (string) $value),
            GettypeResult::OBJECT => $this->prepareStringValue((string) $value),
        };
        if ($result === false) {
            throw new InvalidArgumentException(
                sprintf(
                    'The value "%s" is not a valid datetime.',
                    $value,
                ),
            );
        }
        return $result;
    }

    private function prepareStringValue(string $value): DateTimeImmutable|false
    {
        return match ($value) {
            (string) (int) $value => DateTimeImmutable::createFromFormat('U', $value),
            (string) (float) $value => DateTimeImmutable::createFromFormat('U.u', $value),
            default => date_create_immutable($value),
        };
    }

    /**
     * @psalm-return ColumnInfo
     */
    private function prepareInfo(DateTimeValue $expression): array
    {
        return match ($expression->type) {
            ColumnType::TIMESTAMP,
            ColumnType::TIME,
            ColumnType::TIMETZ,
            ColumnType::DATETIME,
            ColumnType::DATETIMETZ => $expression->info + ['size' => 0],
            default => $expression->info,
        };
    }
}
