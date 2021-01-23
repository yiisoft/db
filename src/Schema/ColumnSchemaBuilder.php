<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Strings\NumericHelper;

/**
 * ColumnSchemaBuilder helps to define database schema types using a PHP interface.
 *
 * See {@see SchemaBuilderTrait} for more detailed description and usage examples.
 */
class ColumnSchemaBuilder
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

    private ?string $type;
    private $length;
    private ?bool $isNotNull = null;
    private bool $isUnique = false;
    private ?string $check = null;
    private $default;
    private ?string $append = null;
    private bool $isUnsigned = false;
    private ?string $after = null;
    private bool $isFirst = false;
    private array $categoryMap = [
        Schema::TYPE_PK => self::CATEGORY_PK,
        Schema::TYPE_UPK => self::CATEGORY_PK,
        Schema::TYPE_BIGPK => self::CATEGORY_PK,
        Schema::TYPE_UBIGPK => self::CATEGORY_PK,
        Schema::TYPE_CHAR => self::CATEGORY_STRING,
        Schema::TYPE_STRING => self::CATEGORY_STRING,
        Schema::TYPE_TEXT => self::CATEGORY_STRING,
        Schema::TYPE_TINYINT => self::CATEGORY_NUMERIC,
        Schema::TYPE_SMALLINT => self::CATEGORY_NUMERIC,
        Schema::TYPE_INTEGER => self::CATEGORY_NUMERIC,
        Schema::TYPE_BIGINT => self::CATEGORY_NUMERIC,
        Schema::TYPE_FLOAT => self::CATEGORY_NUMERIC,
        Schema::TYPE_DOUBLE => self::CATEGORY_NUMERIC,
        Schema::TYPE_DECIMAL => self::CATEGORY_NUMERIC,
        Schema::TYPE_DATETIME => self::CATEGORY_TIME,
        Schema::TYPE_TIMESTAMP => self::CATEGORY_TIME,
        Schema::TYPE_TIME => self::CATEGORY_TIME,
        Schema::TYPE_DATE => self::CATEGORY_TIME,
        Schema::TYPE_BINARY => self::CATEGORY_OTHER,
        Schema::TYPE_BOOLEAN => self::CATEGORY_NUMERIC,
        Schema::TYPE_MONEY => self::CATEGORY_NUMERIC,
    ];
    private ?string $comment = null;

    public function __construct(string $type, $length = null)
    {
        $this->type = $type;
        $this->length = $length;
    }

    /**
     * Adds a `NOT NULL` constraint to the column.
     *
     * {@see isNotNull}
     *
     * @return $this
     */
    public function notNull(): self
    {
        $this->isNotNull = true;

        return $this;
    }

    /**
     * Adds a `NULL` constraint to the column.
     *
     * {@see isNotNull}
     *
     * @return $this
     */
    public function null(): self
    {
        $this->isNotNull = false;

        return $this;
    }

    /**
     * Adds a `UNIQUE` constraint to the column.
     *
     * {@see isUnique}
     *
     * @return $this
     */
    public function unique(): self
    {
        $this->isUnique = true;

        return $this;
    }

    /**
     * Sets a `CHECK` constraint for the column.
     *
     * @param string|null $check the SQL of the `CHECK` constraint to be added.
     *
     * @return $this
     */
    public function check(?string $check): self
    {
        $this->check = $check;

        return $this;
    }

    /**
     * Specify the default value for the column.
     *
     * @param mixed $default the default value.
     *
     * @return $this
     */
    public function defaultValue($default): self
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
     * @param string|null $comment the comment
     *
     * @return $this
     */
    public function comment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Marks column as unsigned.
     *
     * @return $this
     */
    public function unsigned(): self
    {
        switch ($this->type) {
            case Schema::TYPE_PK:
                $this->type = Schema::TYPE_UPK;
                break;
            case Schema::TYPE_BIGPK:
                $this->type = Schema::TYPE_UBIGPK;
                break;
        }
        $this->isUnsigned = true;

        return $this;
    }

    /**
     * Adds an `AFTER` constraint to the column.
     *
     * Note: MySQL, Oracle support only.
     *
     * @param string $after the column after which $this column will be added.
     *
     * @return $this
     */
    public function after(string $after): self
    {
        $this->after = $after;

        return $this;
    }

    /**
     * Adds an `FIRST` constraint to the column.
     *
     * Note: MySQL, Oracle support only.
     *
     * @return $this
     */
    public function first(): self
    {
        $this->isFirst = true;

        return $this;
    }

    /**
     * Specify the default SQL expression for the column.
     *
     * @param string $default the default value expression.
     *
     * @return $this
     */
    public function defaultExpression(string $default): self
    {
        $this->default = new Expression($default);

        return $this;
    }

    /**
     * Specify additional SQL to be appended to column definition.
     *
     * Position modifiers will be appended after column definition in databases that support them.
     *
     * @param string $sql the SQL string to be appended.
     *
     * @return $this
     */
    public function append(string $sql): self
    {
        $this->append = $sql;

        return $this;
    }

    /**
     * Builds the full string for the column's schema.
     *
     * @return string
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
     * Builds the length/precision part of the column.
     *
     * @return string
     */
    protected function buildLengthString(): string
    {
        if ($this->length === null || $this->length === []) {
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
     * @return string returns 'NOT NULL' if {@see isNotNull} is true, 'NULL' if {@see isNotNull} is false or an empty
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
     * @return string returns string 'UNIQUE' if {@see isUnique} is true, otherwise it returns an empty string.
     */
    protected function buildUniqueString(): string
    {
        return $this->isUnique ? ' UNIQUE' : '';
    }

    /**
     * Builds the default value specification for the column.
     *
     * @return string string with default value of column.
     */
    protected function buildDefaultString(): string
    {
        if ($this->default === null) {
            return $this->isNotNull === false ? ' DEFAULT NULL' : '';
        }

        $string = ' DEFAULT ';
        switch (gettype($this->default)) {
            case 'object':
            case 'integer':
                $string .= $this->default;
                break;
            case 'double':
                /* ensure type cast always has . as decimal separator in all locales */
                $string .= NumericHelper::normalize((string) $this->default);
                break;
            case 'boolean':
                $string .= $this->default ? 'TRUE' : 'FALSE';
                break;
            default:
                $string .= "'{$this->default}'";
        }

        return $string;
    }

    /**
     * Builds the check constraint for the column.
     *
     * @return string a string containing the CHECK constraint.
     */
    protected function buildCheckString(): string
    {
        return $this->check !== null ? " CHECK ({$this->check})" : '';
    }

    /**
     * Builds the unsigned string for column. Defaults to unsupported.
     *
     * @return string a string containing UNSIGNED keyword.
     */
    protected function buildUnsignedString(): string
    {
        return '';
    }

    /**
     * Builds the after constraint for the column. Defaults to unsupported.
     *
     * @return string a string containing the AFTER constraint.
     */
    protected function buildAfterString(): string
    {
        return '';
    }

    /**
     * Builds the first constraint for the column. Defaults to unsupported.
     *
     * @return string a string containing the FIRST constraint.
     */
    protected function buildFirstString(): string
    {
        return '';
    }

    /**
     * Builds the custom string that's appended to column definition.
     *
     * @return string custom string to append.
     */
    protected function buildAppendString(): string
    {
        return $this->append !== null ? ' ' . $this->append : '';
    }

    /**
     * Returns the category of the column type.
     *
     * @return string|null a string containing the column type category name.
     */
    protected function getTypeCategory(): ?string
    {
        return $this->categoryMap[$this->type] ?? null;
    }

    /**
     * Builds the comment specification for the column.
     *
     * @return string a string containing the COMMENT keyword and the comment itself
     */
    protected function buildCommentString(): string
    {
        return '';
    }

    /**
     * Returns the complete column definition from input format.
     *
     * @param string $format the format of the definition.
     *
     * @return string a string containing the complete column definition.
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
            '{pos}' => $this->isFirst ? $this->buildFirstString() : $this->buildAfterString(),
            '{append}' => $this->buildAppendString(),
        ];

        return strtr($format, $placeholderValues);
    }

    /**
     * @return string|null the column type definition such as INTEGER, VARCHAR, DATETIME, etc.
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @return array|int|string column size or precision definition. This is what goes into the parenthesis after the
     * column type. This can be either a string, an integer or an array. If it is an array, the array values will be
     * joined into a string separated by comma.
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @return bool|null whether the column is or not nullable. If this is `true`, a `NOT NULL` constraint will be
     * added. If this is `false`, a `NULL` constraint will be added.
     */
    public function getIsNotNull(): ?bool
    {
        return $this->isNotNull;
    }

    /**
     * @return bool whether the column values should be unique. If this is `true`, a `UNIQUE` constraint will be added.
     */
    public function isUnique(): bool
    {
        return $this->isUnique;
    }

    /**
     * @return string|null the `CHECK` constraint for the column.
     */
    public function getCheck(): ?string
    {
        return $this->check;
    }

    /**
     * @return mixed default value of the column.
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @return string SQL string to be appended to column schema definition.
     */
    public function getAppend(): string
    {
        return $this->append;
    }

    /**
     * @return bool whether the column values should be unsigned. If this is `true`, an `UNSIGNED` keyword will be
     * added.
     */
    public function isUnsigned(): bool
    {
        return $this->isUnsigned;
    }

    /**
     * @return string|null the column after which this column will be added.
     */
    public function getAfter(): ?string
    {
        return $this->after;
    }

    /**
     * @return bool whether this column is to be inserted at the beginning of the table.
     */
    public function isFirst(): bool
    {
        return $this->isFirst;
    }

    /**
     * @return array mapping of abstract column types (keys) to type categories (values).
     */
    public function getCategoryMap(): array
    {
        return $this->categoryMap;
    }

    /**
     * @return string comment value of the column.
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }
}
