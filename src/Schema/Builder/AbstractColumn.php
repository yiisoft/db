<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Builder;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Helper\DbStringHelper;
use Yiisoft\Db\Schema\SchemaInterface;

use function gettype;
use function implode;
use function strtr;

/**
 * Is a utility class that provides a convenient way to create column schemas for {@see AbstractSchema}.
 *
 * It provides methods for specifying the properties of a column, such as its type, size, default value, and whether it
 * is nullable or not. It also provides a method for creating a column schema based on the specified properties.
 *
 * For example, the following code creates a column schema for an integer column:
 *
 * ```php
 * $column = (new Column(SchemaInterface::TYPE_INTEGER))->notNull()->defaultValue(0);
 * ```
 *
 * Provides a fluent interface, which means that the methods can be chained together to create a column schema with
 * many properties in a single line of code.
 */
abstract class AbstractColumn implements ColumnInterface
{
    /**
     * Allows you to group and define the abstract column type as primary key.
     */
    public const TYPE_CATEGORY_PK = 'pk';
    /**
     * Allows you to group and define the abstract column type as `string`.
     */
    public const TYPE_CATEGORY_STRING = 'string';
    /**
     * Allows you to group and define the abstract column type as `numeric`.
     */
    public const TYPE_CATEGORY_NUMERIC = 'numeric';
    /**
     * Allows you to group and define the abstract column type as `time`.
     */
    public const TYPE_CATEGORY_TIME = 'time';
    /**
     * Allows you to group and define the abstract column type as `other`.
     */
    public const TYPE_CATEGORY_OTHER = 'other';
    /**
     * Allows you to group and define the abstract column type as `uuid`.
     */
    public const TYPE_CATEGORY_UUID = 'uuid';
    /**
     * Allows you to group and define the abstract column type as `uuid` primary key.
     */
    public const TYPE_CATEGORY_UUID_PK = 'uuid_pk';

    protected bool|null $isNotNull = null;
    protected bool $isUnique = false;
    protected string|null $check = null;
    protected mixed $default = null;
    protected string|null $append = null;
    protected bool $isUnsigned = false;
    protected string|null $comment = null;
    protected string $format = '{type}{length}{notnull}{unique}{default}{check}{comment}{append}';

    /** @psalm-var string[] */
    private array $categoryMap = [
        SchemaInterface::TYPE_PK => self::TYPE_CATEGORY_PK,
        SchemaInterface::TYPE_UPK => self::TYPE_CATEGORY_PK,
        SchemaInterface::TYPE_BIGPK => self::TYPE_CATEGORY_PK,
        SchemaInterface::TYPE_UBIGPK => self::TYPE_CATEGORY_PK,
        SchemaInterface::TYPE_CHAR => self::TYPE_CATEGORY_STRING,
        SchemaInterface::TYPE_STRING => self::TYPE_CATEGORY_STRING,
        SchemaInterface::TYPE_TEXT => self::TYPE_CATEGORY_STRING,
        SchemaInterface::TYPE_TINYINT => self::TYPE_CATEGORY_NUMERIC,
        SchemaInterface::TYPE_SMALLINT => self::TYPE_CATEGORY_NUMERIC,
        SchemaInterface::TYPE_INTEGER => self::TYPE_CATEGORY_NUMERIC,
        SchemaInterface::TYPE_BIGINT => self::TYPE_CATEGORY_NUMERIC,
        SchemaInterface::TYPE_FLOAT => self::TYPE_CATEGORY_NUMERIC,
        SchemaInterface::TYPE_DOUBLE => self::TYPE_CATEGORY_NUMERIC,
        SchemaInterface::TYPE_DECIMAL => self::TYPE_CATEGORY_NUMERIC,
        SchemaInterface::TYPE_DATETIME => self::TYPE_CATEGORY_TIME,
        SchemaInterface::TYPE_TIMESTAMP => self::TYPE_CATEGORY_TIME,
        SchemaInterface::TYPE_TIME => self::TYPE_CATEGORY_TIME,
        SchemaInterface::TYPE_DATE => self::TYPE_CATEGORY_TIME,
        SchemaInterface::TYPE_BINARY => self::TYPE_CATEGORY_OTHER,
        SchemaInterface::TYPE_BOOLEAN => self::TYPE_CATEGORY_NUMERIC,
        SchemaInterface::TYPE_MONEY => self::TYPE_CATEGORY_NUMERIC,
        SchemaInterface::TYPE_UUID => self::TYPE_CATEGORY_UUID,
        SchemaInterface::TYPE_UUID_PK => self::TYPE_CATEGORY_UUID_PK,
    ];

    /**
     * @psalm-param string[]|int[]|int|string|null $length
     */
    public function __construct(
        protected string $type,
        protected int|string|array|null $length = null
    ) {
    }

    public function notNull(): static
    {
        $this->isNotNull = true;
        return $this;
    }

    public function null(): static
    {
        $this->isNotNull = false;
        return $this;
    }

    public function unique(): static
    {
        $this->isUnique = true;
        return $this;
    }

    public function check(string|null $sql): static
    {
        $this->check = $sql;
        return $this;
    }

    public function defaultValue(mixed $default): static
    {
        if ($default === null) {
            $this->null();
        }

        $this->default = $default;

        return $this;
    }

    public function comment(string|null $comment): static
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * Marks column as unsigned.
     */
    public function unsigned(): static
    {
        $this->type = match ($this->type) {
            SchemaInterface::TYPE_PK => SchemaInterface::TYPE_UPK,
            SchemaInterface::TYPE_BIGPK => SchemaInterface::TYPE_UBIGPK,
            default => $this->type,
        };

        $this->isUnsigned = true;

        return $this;
    }

    public function defaultExpression(string $sql): static
    {
        $this->default = new Expression($sql);
        return $this;
    }

    public function setFormat(string $format): void
    {
        $this->format = $format;
    }

    public function append(string $sql): static
    {
        $this->append = $sql;
        return $this;
    }

    public function asString(): string
    {
        $format = match ($this->getTypeCategory()) {
            self::TYPE_CATEGORY_PK => '{type}{check}{comment}{append}',
            self::TYPE_CATEGORY_UUID => '{type}{notnull}{unique}{default}{check}{comment}{append}',
            self::TYPE_CATEGORY_UUID_PK => '{type}{notnull}{default}{check}{comment}{append}',
            default => $this->format,
        };

        return $this->buildCompleteString($format);
    }

    public function getType(): string|null
    {
        return $this->type;
    }

    public function getLength(): array|int|string|null
    {
        return $this->length;
    }

    public function isNotNull(): bool|null
    {
        return $this->isNotNull;
    }

    public function isUnique(): bool
    {
        return $this->isUnique;
    }

    public function getCheck(): string|null
    {
        return $this->check;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function getAppend(): string|null
    {
        return $this->append;
    }

    public function isUnsigned(): bool
    {
        return $this->isUnsigned;
    }

    public function getCategoryMap(): array
    {
        return $this->categoryMap;
    }

    public function getComment(): string|null
    {
        return $this->comment;
    }

    /**
     * Builds the length, precision part of the column.
     *
     * @return string A string containing the length/precision of the column.
     */
    protected function buildLengthString(): string
    {
        if (empty($this->length)) {
            return '';
        }

        if (is_array($this->length)) {
            $this->length = implode(',', $this->length);
        }

        return '(' . $this->length . ')';
    }

    /**
     * Builds the not null constraint for the column.
     *
     * @return string A string 'NOT NULL' if {@see isNotNull} is true, 'NULL' if {@see isNotNull} is false or an empty
     * string otherwise.
     */
    protected function buildNotNullString(): string
    {
        if ($this->isNotNull === true) {
            return ' NOT NULL';
        }

        if ($this->isNotNull === false) {
            return ' NULL';
        }

        return '';
    }

    /**
     * Builds the unique constraint for the column.
     *
     * @return string A string 'UNIQUE' if {@see isUnique} is true, otherwise it returns an empty string.
     */
    protected function buildUniqueString(): string
    {
        return $this->isUnique ? ' UNIQUE' : '';
    }

    /**
     * Return the default value for the column.
     *
     * @return string|null string with default value of column.
     */
    protected function buildDefaultValue(): string|null
    {
        if ($this->default === null) {
            return $this->isNotNull === false ? 'NULL' : null;
        }

        return match (gettype($this->default)) {
            'object', 'integer' => (string) $this->default,
            'double' => DbStringHelper::normalizeFloat((string) $this->default),
            'boolean' => $this->default ? 'TRUE' : 'FALSE',
            default => "'$this->default'",
        };
    }

    /**
     * Builds the default value specification for the column.
     *
     * @return string A string containing the DEFAULT keyword and the default value.
     */
    protected function buildDefaultString(): string
    {
        $defaultValue = $this->buildDefaultValue();
        if ($defaultValue === null) {
            return '';
        }

        return ' DEFAULT ' . $defaultValue;
    }

    /**
     * Builds the check constraint for the column.
     *
     * @return string A string containing the CHECK constraint.
     */
    protected function buildCheckString(): string
    {
        return !empty($this->check) ? " CHECK ($this->check)" : '';
    }

    /**
     * Builds the unsigned string for column. Defaults to unsupported.
     *
     * @return string A string containing the UNSIGNED keyword.
     */
    protected function buildUnsignedString(): string
    {
        return '';
    }

    /**
     * Builds the custom string that's appended to column definition.
     *
     * @return string A string containing the custom SQL fragment appended to column definition.
     */
    protected function buildAppendString(): string
    {
        return !empty($this->append) ? ' ' . $this->append : '';
    }

    /**
     * @return string|null A string containing the column type category name.
     */
    protected function getTypeCategory(): string|null
    {
        return $this->categoryMap[$this->type] ?? null;
    }

    /**
     * Builds the comment specification for the column.
     *
     * @return string A string containing the COMMENT keyword and the comment itself.
     */
    protected function buildCommentString(): string
    {
        return '';
    }

    /**
     * Returns the complete column definition from input format.
     *
     * @param string $format The format of the definition.
     *
     * @return string A string containing the complete column definition.
     */
    protected function buildCompleteString(string $format): string
    {
        $placeholderValues = [
            '{type}' => $this->type,
            '{length}' => $this->buildLengthString(),
            '{unsigned}' => $this->buildUnsignedString(),
            '{notnull}' => $this->buildNotNullString(),
            '{unique}' => $this->buildUniqueString(),
            '{default}' => $this->buildDefaultString(),
            '{check}' => $this->buildCheckString(),
            '{comment}' => $this->buildCommentString(),
            '{append}' => $this->buildAppendString(),
        ];

        return strtr($format, $placeholderValues);
    }
}
