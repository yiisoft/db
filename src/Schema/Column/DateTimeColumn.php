<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Stringable;
use UnexpectedValueException;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\GettypeResult;
use Yiisoft\Db\Expression\ExpressionInterface;

use function date_create_immutable;
use function date_default_timezone_get;
use function gettype;
use function is_string;

/**
 * Represents the metadata for a datetime column.
 *
 * Supported abstract column types:
 * - `ColumnType::TIMESTAMP`
 * - `ColumnType::DATETIME`
 * - `ColumnType::DATETIMETZ`
 * - `ColumnType::TIME`
 * - `ColumnType::TIMETZ`
 * - `ColumnType::DATE`
 * - `ColumnType::INTEGER`
 * - `ColumnType::BIGINT`
 * - `ColumnType::FLOAT`
 *
 * Possible issues:
 * - MySQL DBMS converts `TIMESTAMP` column type values from database session time zone to UTC for storage,
 *   and back from UTC to the session time zone when retrieve the values;
 * - Oracle DBMS converts `TIMESTAMP WITH LOCAL TIME ZONE` column type values from database session time zone to
 *   the database time zone for storage, and back from the database time zone to the session time zone when retrieve
 *   the values.
 *
 * Both of them do not store time zone offset and require to convert datetime values to the database session time
 * zone before insert and back to the PHP time zone after retrieve the values. This will be done in the
 * {@see dbTypecast()} and {@see phpTypecast()} methods and guarantees that the values are stored in the
 * database in the correct time zone.
 *
 * To avoid possible time zone issues with the datetime values conversion, it is recommended to set the PHP and database
 * time zones to UTC.
 */
class DateTimeColumn extends AbstractColumn
{
    protected const DEFAULT_TYPE = ColumnType::DATETIME;

    /**
     * The database time zone to be used when converting datetime values before inserting them into the database
     * and when converting them back to PHP `DateTimeImmutable` objects. It is used when the column type does not have
     * time zone information. Use empty string to disable time zone conversion.
     */
    protected string $dbTimezone = 'UTC';

    /**
     * The PHP time zone for the `string` datetime values when converting them to `DateTimeImmutable` objects before
     * inserting them into the database or after retrieving them from the database. Use empty string to use current PHP
     * time zone.
     */
    protected string $phpTimezone = 'UTC';

    protected ?bool $shouldConvertTimezone = null;

    /**
     * @psalm-var non-empty-string|null
     */
    protected ?string $format = null;

    /**
     * @param mixed $value The value representing datetime or time to be typecasted to the database format.
     * Possible values:
     * - null;
     * - string - it can be:
     *     - empty string - treated as null;
     *     - string with integer value - treated as unix timestamp;
     *     - string with float value - treated as unix timestamp with microseconds;
     *     - string with datetime format;
     * - Stringable object - converted to string;
     * - integer - treated as unix timestamp;
     * - float - treated as unix timestamp with microseconds;
     * - DateTimeImmutable;
     * - DateTimeInterface;
     * - ExpressionInterface;
     *
     * If the value is `string` or a `Stringable` object, it will be converted to a `DateTimeImmutable` object with
     * the default time zone set specified in the {@see $phpTimezone} property. If the conversion fails, the original
     * value will be returned.
     */
    public function dbTypecast(mixed $value): float|int|string|ExpressionInterface|null
    {
        /** @psalm-suppress MixedArgument, PossiblyFalseArgument */
        return match (gettype($value)) {
            GettypeResult::NULL => null,
            GettypeResult::STRING => $this->dbTypecastString($value),
            GettypeResult::INTEGER => $this->dbTypecastDateTime(DateTimeImmutable::createFromFormat('U', (string) $value)),
            GettypeResult::DOUBLE => $this->dbTypecastDateTime(DateTimeImmutable::createFromFormat('U.u', (string) $value)),
            GettypeResult::OBJECT => match (true) {
                $value instanceof DateTimeImmutable => $this->dbTypecastDateTime($value),
                $value instanceof DateTimeInterface => $this->dbTypecastDateTime(DateTimeImmutable::createFromInterface($value)),
                $value instanceof ExpressionInterface => $value,
                $value instanceof Stringable => $this->dbTypecastString((string) $value),
                default => $this->throwWrongTypeException($value::class),
            },
            default => $this->throwWrongTypeException(gettype($value)),
        };
    }

    /**
     * Converts the value from the database format to PHP `DateTimeImmutable` object.
     * If the database type does not have time zone information, the time zone will be set to the current PHP time zone.
     *
     * @param string|null $value
     */
    public function phpTypecast(mixed $value): DateTimeImmutable|null
    {
        if (is_string($value)) {
            $phpTimezone = $this->getPhpTimezone();

            if ($this->shouldConvertTimezone()) {
                /** @psalm-suppress ArgumentTypeCoercion */
                $datetime = new DateTimeImmutable($value, new DateTimeZone($this->dbTimezone));

                if ($phpTimezone !== $this->dbTimezone) {
                    return $datetime->setTimezone(new DateTimeZone($phpTimezone));
                }

                return $datetime;
            }

            return new DateTimeImmutable($value, new DateTimeZone($phpTimezone));
        }

        return $value;
    }

    protected function shouldConvertTimezone(): bool
    {
        return $this->shouldConvertTimezone ??= $this->dbTimezone !== '' && match ($this->getType()) {
            ColumnType::DATETIMETZ,
            ColumnType::TIMETZ,
            ColumnType::DATE => false,
            default => true,
        };
    }

    /** @psalm-return non-empty-string */
    protected function getFormat(): string
    {
        return $this->format ??= match ($this->getType()) {
            ColumnType::TIMESTAMP,
            ColumnType::DATETIME => 'Y-m-d H:i:s' . $this->getMillisecondsFormat(),
            ColumnType::DATETIMETZ => 'Y-m-d H:i:s' . $this->getMillisecondsFormat() . 'P',
            ColumnType::TIME => 'H:i:s' . $this->getMillisecondsFormat(),
            ColumnType::TIMETZ => 'H:i:s' . $this->getMillisecondsFormat() . 'P',
            ColumnType::DATE => 'Y-m-d',
            ColumnType::INTEGER,
            ColumnType::BIGINT => 'U',
            ColumnType::FLOAT ,
            ColumnType::DOUBLE,
            ColumnType::DECIMAL => 'U.u',
            default => throw new UnexpectedValueException(
                'Unsupported abstract column type ' . $this->getType() . ' for ' . static::class . ' class.',
            ),
        };
    }

    protected function getMillisecondsFormat(): string
    {
        return match ($this->getSize()) {
            0 => '',
            1, 2, 3 => '.v',
            default => '.u',
        };
    }

    /**
     * Returns the PHP time zone for the `string` datetime values when converting them to `DateTimeImmutable` objects
     * before inserting them into the database or after retrieving them from the database.
     *
     * @psalm-return non-empty-string
     */
    protected function getPhpTimezone(): string
    {
        return empty($this->phpTimezone)
            ? date_default_timezone_get()
            : $this->phpTimezone;
    }

    private function dbTypecastDateTime(DateTimeImmutable $value): float|int|string
    {
        if ($this->shouldConvertTimezone()) {
            /** @psalm-suppress ArgumentTypeCoercion */
            $value = $value->setTimezone(new DateTimeZone($this->dbTimezone));
        }

        $format = $this->getFormat();
        $result = $value->format($format);

        return match ($format) {
            'U' => (int) $result,
            'U.u' => (float) $result,
            default => $result,
        };
    }

    private function dbTypecastString(string $value): float|int|string|null
    {
        /** @psalm-suppress PossiblyFalseArgument */
        return match ($value) {
            '' => null,
            (string)(int) $value => $this->dbTypecastDateTime(DateTimeImmutable::createFromFormat('U', $value)),
            (string)(float) $value => $this->dbTypecastDateTime(DateTimeImmutable::createFromFormat('U.u', $value)),
            default => ($datetime = date_create_immutable($value, new DateTimeZone($this->getPhpTimezone()))) !== false
                ? $this->dbTypecastDateTime($datetime)
                : $value,
        };
    }
}
