<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use Stringable;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Strings\NumericHelper;

use function gettype;
use function strtr;

/**
 * The ColumnSchemaBuilder class is a utility class that provides a convenient way to create column schemas for use
 * with Schema class @see Schema.
 *
 * It provides methods for specifying the properties of a column, such as its type, size, default value, and whether it
 * is nullable or not. It also provides a method for creating a column schema based on the specified properties.
 *
 * For example, the following code creates a column schema for an integer column:
 *
 * ```php
 * $column = (new ColumnSchemaBuilder(SchemaInterface::TYPE_INTEGER))->notNull()->defaultValue(0);
 * ```
 *
 * The ColumnSchemaBuilder class provides a fluent interface, which means that the methods can be chained together to
 * create a column schema with multiple properties in a single line of code.
 */
class ColumnSchemaBuilder implements Stringable
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

    /**
     * Adds a `NOT NULL` constraint to the column.
     *
     * @return static The column schema builder instance itself.
     *
     * @see isNotNull
     */
    public function notNull(): static
    {
        $this->isNotNull = true;

        return $this;
    }

    /**
     * Adds a `NULL` constraint to the column.
     *
     * @return static The column schema builder instance itself.
     *
     * @see isNotNull
     */
    public function null(): static
    {
        $this->isNotNull = false;

        return $this;
    }

    /**
     * Adds a `UNIQUE` constraint to the column.
     *
     * @return static The column schema builder instance itself.
     *
     * @see isUnique
     */
    public function unique(): static
    {
        $this->isUnique = true;

        return $this;
    }

    /**
     * Specify a `CHECK` constraint for the column.
     *
     * @param string|null $check The SQL of the `CHECK` constraint to be added.
     *
     * @return static The column schema builder instance itself.
     */
    public function check(string|null $check): static
    {
        $this->check = $check;

        return $this;
    }

    /**
     * Specify the default value for the column.
     *
     * @param mixed $default The default value to be used.
     *
     * @return static The column schema builder instance itself.
     */
    public function defaultValue(mixed $default): static
    {
        if ($default === null) {
            $this->null();
        }

        $this->default = $default;
        return $this;
    }

    /**
     * Specifies the comment for column.
     *
     * @param string|null $comment The comment to be added.
     *
     * @return static The column schema builder instance itself.
     */
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
        switch ($this->type) {
            case SchemaInterface::TYPE_PK:
                $this->type = SchemaInterface::TYPE_UPK;
                break;
            case SchemaInterface::TYPE_BIGPK:
                $this->type = SchemaInterface::TYPE_UBIGPK;
                break;
        }
        $this->isUnsigned = true;

        return $this;
    }

    /**
     * Specify the default SQL expression for the column.
     *
     * @param string $default The SQL expression to be used as default value.
     *
     * @return static The column schema builder instance itself.
     */
    public function defaultExpression(string $default): static
    {
        $this->default = new Expression($default);

        return $this;
    }

    /**
     * Specify additional SQL to be appended to column definition.
     *
     * Position modifiers will be appended after column definition in databases that support them.
     *
     * @param string $sql The SQL string to be appended.
     *
     * @return static The column schema builder instance itself.
     */
    public function append(string $sql): static
    {
        $this->append = $sql;

        return $this;
    }

    /**
     * Builds the full string for the column's schema including type, length, default value, not null and other SQL
     * fragment.
     *
     * @return string The SQL fragment that will be used for creating the column.
     */
    public function __toString(): string
    {
        if ($this->getTypeCategory() === self::CATEGORY_PK) {
            $format = '{type}{check}{comment}{append}';
        } else {
            $format = '{type}{length}{notnull}{unique}{default}{check}{comment}{append}';
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

        return "({$this->length})";
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
            'double' => NumericHelper::normalize((string)$this->default),
            'boolean' => $this->default ? 'TRUE' : 'FALSE',
            default => "'{$this->default}'",
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
        return !empty($this->check) ? " CHECK ({$this->check})" : '';
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

    /**
     * @return string|null The column type definition such as INTEGER, VARCHAR, DATETIME, etc.
     */
    public function getType(): string|null
    {
        return $this->type;
    }

    /**
     * @return array|int|string|null The column size or precision definition. This is what goes into the parenthesis
     * after the column type. This can be either a string, an integer or an array. If it is an array, the array values
     * will be joined into a string separated by comma.
     */
    public function getLength(): array|int|string|null
    {
        return $this->length;
    }

    /**
     * @return bool|null Whether the column is or not nullable. If this is `true`, a `NOT NULL` constraint will be
     * added. If this is `false`, a `NULL` constraint will be added.
     */
    public function isNotNull(): bool|null
    {
        return $this->isNotNull;
    }

    /**
     * @return bool Whether the column values should be unique. If this is `true`, a `UNIQUE` constraint will be added.
     */
    public function isUnique(): bool
    {
        return $this->isUnique;
    }

    /**
     * @return string|null The `CHECK` constraint for the column.
     */
    public function getCheck(): string|null
    {
        return $this->check;
    }

    /**
     * @return mixed The default value of the column.
     */
    public function getDefault(): mixed
    {
        return $this->default;
    }

    /**
     * @return string|null The SQL string to be appended to column schema definition.
     */
    public function getAppend(): string|null
    {
        return $this->append;
    }

    /**
     * @return bool Whether the column values should be unsigned. If this is `true`, an `UNSIGNED` keyword will be
     * added.
     */
    public function isUnsigned(): bool
    {
        return $this->isUnsigned;
    }

    /**
     * @return array The mapping of abstract column types (keys) to type categories (values).
     */
    public function getCategoryMap(): array
    {
        return $this->categoryMap;
    }

    /**
     * @return string|null The comment value of the column.
     */
    public function getComment(): string|null
    {
        return $this->comment;
    }
}
