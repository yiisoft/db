<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Helper\StringHelper;

use function gettype;
use function strtr;

/**
 * The AbstractColumnSchemaBuilder class is a utility class that provides a convenient way to create column schemas for
 * use with Schema class @see Schema.
 *
 * It provides methods for specifying the properties of a column, such as its type, size, default value, and whether it
 * is nullable or not. It also provides a method for creating a column schema based on the specified properties.
 *
 * For example, the following code creates a column schema for an integer column:
 *
 * ```php
 * $column = (new ColumnSchemaBuilder(Schema::TYPE_INTEGER))->notNull()->defaultValue(0);
 * ```
 *
 * The AbstractColumnSchemaBuilder class provides a fluent interface, which means that the methods can be chained
 * together to create a column schema with multiple properties in a single line of code.
 */
abstract class AbstractColumnSchemaBuilder implements ColumnSchemaBuilderInterface
{
    /**
     * Internally used constants representing categories that abstract column types fall under.
     *
     * {@see $categoryMap} for mappings of abstract column types to category.
     */
    public const CATEGORY_PK = 'pk';
    public const CATEGORY_STRING = 'string';
    public const CATEGORY_NUMERIC = 'numeric';
    public const CATEGORY_TIME = 'time';
    public const CATEGORY_OTHER = 'other';

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
        SchemaInterface::TYPE_PK => self::CATEGORY_PK,
        SchemaInterface::TYPE_UPK => self::CATEGORY_PK,
        SchemaInterface::TYPE_BIGPK => self::CATEGORY_PK,
        SchemaInterface::TYPE_UBIGPK => self::CATEGORY_PK,
        SchemaInterface::TYPE_CHAR => self::CATEGORY_STRING,
        SchemaInterface::TYPE_STRING => self::CATEGORY_STRING,
        SchemaInterface::TYPE_TEXT => self::CATEGORY_STRING,
        SchemaInterface::TYPE_TINYINT => self::CATEGORY_NUMERIC,
        SchemaInterface::TYPE_SMALLINT => self::CATEGORY_NUMERIC,
        SchemaInterface::TYPE_INTEGER => self::CATEGORY_NUMERIC,
        SchemaInterface::TYPE_BIGINT => self::CATEGORY_NUMERIC,
        SchemaInterface::TYPE_FLOAT => self::CATEGORY_NUMERIC,
        SchemaInterface::TYPE_DOUBLE => self::CATEGORY_NUMERIC,
        SchemaInterface::TYPE_DECIMAL => self::CATEGORY_NUMERIC,
        SchemaInterface::TYPE_DATETIME => self::CATEGORY_TIME,
        SchemaInterface::TYPE_TIMESTAMP => self::CATEGORY_TIME,
        SchemaInterface::TYPE_TIME => self::CATEGORY_TIME,
        SchemaInterface::TYPE_DATE => self::CATEGORY_TIME,
        SchemaInterface::TYPE_BINARY => self::CATEGORY_OTHER,
        SchemaInterface::TYPE_BOOLEAN => self::CATEGORY_NUMERIC,
        SchemaInterface::TYPE_MONEY => self::CATEGORY_NUMERIC,
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

    public function check(string|null $check): static
    {
        $this->check = $check;

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
     *
     * @return static The column schema builder instance itself.
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

    public function defaultExpression(string $default): static
    {
        $this->default = new Expression($default);

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
        if ($this->getTypeCategory() === self::CATEGORY_PK) {
            $format = '{type}{check}{comment}{append}';
        } else {
            $format = $this->format;
        }

        return $this->buildCompleteString($format);
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
     * Builds the default value specification for the column.
     *
     * @return string A string containing the DEFAULT keyword and the default value.
     */
    protected function buildDefaultString(): string
    {
        if ($this->default === null) {
            return $this->isNotNull === false ? ' DEFAULT NULL' : '';
        }

        $string = ' DEFAULT ';

        $string .= match (gettype($this->default)) {
            'object', 'integer' => (string)$this->default,
            'double' => StringHelper::normalizeFloat((string)$this->default),
            'boolean' => $this->default ? 'TRUE' : 'FALSE',
            default => "'$this->default'",
        };

        return $string;
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
}
