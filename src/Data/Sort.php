<?php

declare(strict_types=1);

namespace Yiisoft\Db\Data;

use function array_merge;

use function explode;
use function implode;
use function is_array;
use function is_iterable;
use function is_scalar;
use function strncmp;
use function substr;
use Yiisoft\Db\Exception\InvalidConfigException;

/**
 * Sort represents information relevant to sorting.
 *
 * When data needs to be sorted according to one or several attributes,
 * we can use Sort to represent the sorting information and generate
 * appropriate hyperlinks that can lead to sort actions.
 *
 * A typical usage example is as follows,
 *
 * ```php
 * public function actionIndex()
 * {
 *     $sort = new Sort();
 *
 *     $sort->attributes(
 *         [
 *             'age',
 *             'name' => [
 *                  'asc' => ['first_name' => SORT_ASC, 'last_name' => SORT_ASC],
 *                  'desc' => ['first_name' => SORT_DESC, 'last_name' => SORT_DESC],
 *             ]
 *         ]
 *     )->params(['sort' => 'age,-name'])->enableMultiSort(true);
 * }
 * ```
 *
 * In the above, we declare two {@see attributes]] that support sorting: `name` and `age`.
 *
 * For more details and usage information on Sort, see the [guide article on sorting](guide:output-sorting).
 *
 * @property array $attributeOrders Sort directions indexed by attribute names. Sort direction can be either `SORT_ASC`
 * for ascending order or `SORT_DESC` for descending order. Note that the type of this property differs in getter and
 * setter. See {@see getAttributeOrders()} and {@see attributeOrders()} for details.
 * @property array $orders The columns (keys) and their corresponding sort directions (values). This can be passed to
 * {@see \Yiisoft\Db\Query\Query::orderBy()]] to construct a DB query. This property is read-only.
 */
final class Sort
{
    /**
     * @var array|null the currently requested sort order as computed by {@see getAttributeOrders}.
     */
    private ?array $attributeOrders = null;
    private bool $enableMultiSort = false;
    private array $attributes = [];
    private string $sortParam = 'sort';
    private array $defaultOrder = [];
    private string $separator = ',';
    private array $params = [];

    /**
     * @param array $value list of attributes that are allowed to be sorted. Its syntax can be described using the
     * following example:
     *
     * ```php
     * [
     *     'age',
     *     'name' => [
     *         'asc' => ['first_name' => SORT_ASC, 'last_name' => SORT_ASC],
     *         'desc' => ['first_name' => SORT_DESC, 'last_name' => SORT_DESC],
     *         'default' => SORT_DESC,
     *         'label' => 'Name',
     *     ],
     * ]
     * ```
     *
     * In the above, two attributes are declared: `age` and `name`. The `age` attribute is a simple attribute which is
     * equivalent to the following:
     *
     * ```php
     * [
     *     'age' => [
     *         'asc' => ['age' => SORT_ASC],
     *         'desc' => ['age' => SORT_DESC],
     *     ],
     *     'default' => SORT_ASC,
     *     'label' => Inflector::camel2words('age'),
     * ]
     * ```
     *
     * ```php
     * 'name' => [
     *     'asc' => '[[last_name]] ASC NULLS FIRST', // PostgreSQL specific feature
     *     'desc' => '[[last_name]] DESC NULLS LAST',
     * ]
     * ```
     *
     * The `name` attribute is a composite attribute:
     *
     * - The `name` key represents the attribute name which will appear in the URLs leading to sort actions.
     * - The `asc` and `desc` elements specify how to sort by the attribute in ascending and descending orders,
     *   respectively. Their values represent the actual columns and the directions by which the data should be sorted
     *   by.
     * - The `default` element specifies by which direction the attribute should be sorted if it is not currently sorted
     *   (the default value is ascending order).
     * - The `label` element specifies what label should be used when calling {@see link()} to create a sort link.
     *   If not set, {@see Inflector::toWords()} will be called to get a label. Note that it will not be HTML-encoded.
     *
     * Note that if the Sort object is already created, you can only use the full format to configure every attribute.
     * Each attribute must include these elements: `asc` and `desc`.
     *
     * @return self
     */
    public function attributes(array $value = []): self
    {
        $attributes = [];

        foreach ($value as $name => $attribute) {
            if (!is_array($attribute)) {
                $attributes[$attribute] = [
                    'asc' => [$attribute => SORT_ASC],
                    'desc' => [$attribute => SORT_DESC],
                ];
            } elseif (!isset($attribute['asc'], $attribute['desc'])) {
                $attributes[$name] = array_merge([
                    'asc' => [$name => SORT_ASC],
                    'desc' => [$name => SORT_DESC],
                ], $attribute);
            } else {
                $attributes[$name] = $attribute;
            }
        }

        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Sets up the currently sort information.
     *
     * @param array $attributeOrders sort directions indexed by attribute names. Sort direction can be either `SORT_ASC`
     * for ascending order or `SORT_DESC` for descending order.
     * @param bool $validate whether to validate given attribute orders against {@see attributes} and {enableMultiSort}.
     * If validation is enabled incorrect entries will be removed.
     */
    public function attributeOrders(array $attributeOrders = [], bool $validate = true): void
    {
        if ($attributeOrders === [] || !$validate) {
            $this->attributeOrders = $attributeOrders;
        } else {
            $this->attributeOrders = [];
            foreach ($attributeOrders as $attribute => $order) {
                if (isset($this->attributes[$attribute])) {
                    $this->attributeOrders[$attribute] = $order;
                    if (!$this->enableMultiSort) {
                        break;
                    }
                }
            }
        }
    }

    /**
     * Creates the sort variable for the specified attribute.
     *
     * The newly created sort variable can be used to create a URL that will lead to sorting by the specified attribute.
     *
     * @param string $attribute the attribute name.
     *
     * @throws InvalidConfigException if the specified attribute is not defined in {@see attributes}
     *
     * @return string the value of the sort variable.
     */
    public function createSortParam(string $attribute): string
    {
        if (!isset($this->attributes[$attribute])) {
            throw new InvalidConfigException("Unknown attribute: $attribute");
        }

        $definition = $this->attributes[$attribute];

        $directions = $this->getAttributeOrders();

        if (isset($directions[$attribute])) {
            $direction = $directions[$attribute] === SORT_DESC ? SORT_ASC : SORT_DESC;
            unset($directions[$attribute]);
        } else {
            $direction = $definition['default'] ?? SORT_ASC;
        }

        if ($this->enableMultiSort) {
            $directions = array_merge([$attribute => $direction], $directions);
        } else {
            $directions = [$attribute => $direction];
        }

        $sorts = [];

        foreach ($directions as $attribute => $direction) {
            $sorts[] = $direction === SORT_DESC ? '-' . $attribute : $attribute;
        }

        return implode($this->separator, $sorts);
    }

    /**
     * @param array $value the order that should be used when the current request does not specify any order.
     * The array keys are attribute names and the array values are the corresponding sort directions. For example,
     *
     * ```php
     * [
     *     'name' => SORT_ASC,
     *     'created_at' => SORT_DESC,
     * ]
     * ```
     *
     * {@see attributeOrders}
     *
     * @return $this
     */
    public function defaultOrder(array $value): self
    {
        $this->defaultOrder = $value;

        return $this;
    }

    /**
     * @param bool $value whether the sorting can be applied to multiple attributes simultaneously.
     *
     * Defaults to `false`, which means each time the data can only be sorted by one attribute.
     *
     * @return self
     */
    public function enableMultiSort(bool $value): self
    {
        $this->enableMultiSort = $value;

        return $this;
    }

    /**
     * Returns the sort direction of the specified attribute in the current request.
     *
     * @param string $attribute the attribute name.
     *
     * @return int|null Sort direction of the attribute. Can be either `SORT_ASC` for ascending order or `SORT_DESC` for
     * descending order. Null is returned if the attribute is invalid or does not need to be sorted.
     */
    public function getAttributeOrder(string $attribute): ?int
    {
        $orders = $this->getAttributeOrders();

        return $orders[$attribute] ?? null;
    }

    /**
     * Returns the currently requested sort information.
     *
     * @param bool $recalculate whether to recalculate the sort directions.
     *
     * @return array sort directions indexed by attribute names. Sort direction can be either `SORT_ASC` for ascending
     * order or `SORT_DESC` for descending order.
     */
    public function getAttributeOrders(bool $recalculate = false): array
    {
        if ($this->attributeOrders === null || $recalculate) {
            $this->attributeOrders = [];

            if (isset($this->params[$this->sortParam])) {
                foreach ($this->parseSortParam($this->params[$this->sortParam]) as $attribute) {
                    $descending = false;
                    if (strncmp($attribute, '-', 1) === 0) {
                        $descending = true;
                        $attribute = substr($attribute, 1);
                    }

                    if (isset($this->attributes[$attribute])) {
                        $this->attributeOrders[$attribute] = $descending ? SORT_DESC : SORT_ASC;
                        if (!$this->enableMultiSort) {
                            return $this->attributeOrders;
                        }
                    }
                }
            }

            if (empty($this->attributeOrders) && !empty($this->defaultOrder)) {
                $this->attributeOrders = $this->defaultOrder;
            }
        }

        return $this->attributeOrders;
    }

    /**
     * Returns the columns and their corresponding sort directions.
     *
     * @param bool $recalculate whether to recalculate the sort directions.
     *
     * @return array the columns (keys) and their corresponding sort directions (values). This can be passed to
     * {@see \Yiisoft\Db\Query\Query::orderBy()} to construct a DB query.
     */
    public function getOrders(bool $recalculate = false): array
    {
        $attributeOrders = $this->getAttributeOrders($recalculate);

        $orders = [];

        foreach ($attributeOrders as $attribute => $direction) {
            $definition = $this->attributes[$attribute];
            $columns = $definition[$direction === SORT_ASC ? 'asc' : 'desc'];
            if (is_iterable($columns)) {
                foreach ($columns as $name => $dir) {
                    $orders[$name] = $dir;
                }
            } else {
                $orders[] = $columns;
            }
        }

        return $orders;
    }

    /**
     * Returns a value indicating whether the sort definition supports sorting by the named attribute.
     *
     * @param string $name the attribute name.
     *
     * @return bool whether the sort definition supports sorting by the named attribute.
     */
    public function hasAttribute(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    /**
     * @param string $value the character used to separate different attributes that need to be sorted by.
     *
     * @return self
     */
    public function separator(string $value): self
    {
        $this->separator = $value;

        return $this;
    }

    /**
     * @param string $value the name of the parameter that specifies which attributes to be sorted in which direction.
     * Defaults to `sort`.
     *
     * {@see params}
     *
     * @return self
     */
    public function sortParam(string $value): self
    {
        $this->sortParam = $value;

        return $this;
    }

    /**
     * @param array $value parameters (name => value) that should be used to obtain the current sort directions and to
     * create new sort URLs. If not set, `$_GET` will be used instead.
     *
     * In order to add hash to all links use `array_merge($_GET, ['#' => 'my-hash'])`.
     *
     * The array element indexed by {@see sortParam} is considered to be the current sort directions. If the element
     * does not exist, the {@see defaultOrder|default order} will be used.
     *
     * @return self
     *
     * {@see sortParam}
     * {@see defaultOrder}
     */
    public function params(array $value): self
    {
        $this->params = $value;

        return $this;
    }

    /**
     * Parses the value of {@see sortParam} into an array of sort attributes.
     *
     * The format must be the attribute name only for ascending or the attribute name prefixed with `-` for descending.
     *
     * For example the following return value will result in ascending sort by `category` and descending sort by
     * `created_at`:
     *
     * ```php
     * [
     *     'category',
     *     '-created_at'
     * ]
     * ```
     *
     * @param string $param the value of the {@see sortParam}.
     *
     * @return array the valid sort attributes.
     */
    protected function parseSortParam(string $param): array
    {
        return is_scalar($param) ? explode($this->separator, $param) : [];
    }
}
