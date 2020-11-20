<?php

declare(strict_types=1);

namespace Yiisoft\Db\Data;

use Yiisoft\Data\Paginator\PaginatorInterface;

/**
 * DataProvider provides a base class that implements the {@see DataProviderInterface}.
 *
 * For more details and usage information on DataProvider, see the
 * [guide article on data providers](guide:output-data-providers).
 */
abstract class DataProvider implements DataProviderInterface
{
    /**
     * @var string an ID that uniquely identifies the data provider among all data providers.
     * Generated automatically the following way in case it is not set:
     *
     * - First data provider ID is empty.
     * - Second and all subsequent data provider IDs are: "dp-1", "dp-2", etc.
     */
    private string $id;
    private ?Sort $sort = null;
    private ?PaginatorInterface $pagination = null;
    private array $keys = [];
    private array $models = [];
    private int $totalCount = 0;
    private bool $autoGenerate = true;
    private string $autoIdPrefix = 'dp';
    private static int $counter = 0;

    public function __construct()
    {
        if (isset($this->id)) {
            $this->id = $this->getId();
        }
    }

    /**
     * Prepares the data models that will be made available in the current page.
     *
     * @return array the available data models.
     */
    abstract protected function prepareModels(): array;

    /**
     * Prepares the keys associated with the currently available data models.
     *
     * @param array $value the available data models.
     *
     * @return array the keys.
     */
    abstract protected function prepareKeys(array $value = []): array;

    /**
     * Returns a value indicating the total number of data models in this data provider.
     *
     * @return int total number of data models in this data provider.
     */
    abstract protected function prepareTotalCount(): int;

    /**
     * Prepares the data models and keys.
     *
     * This method will prepare the data models and keys that can be retrieved via {@see getModels()} and
     * {@see getKeys()}.
     *
     * This method will be implicitly called by {@see getModels} and {@see getKeys()} if it has not been called
     * before.
     *
     * @param bool $forcePrepare whether to force data preparation even if it has been done before.
     */
    public function prepare(bool $forcePrepare = false): void
    {
        if ($forcePrepare || $this->models === []) {
            $this->models = $this->prepareModels();
        }
        if ($forcePrepare || $this->keys === []) {
            $this->keys = $this->prepareKeys($this->models);
        }
    }

    /**
     * Returns the data models in the current page.
     *
     * @return array the list of data models in the current page.
     */
    public function getModels(): array
    {
        $this->prepare();

        return $this->models;
    }

    /**
     * Sets the data models in the current page.
     *
     * @param array $value the models in the current page.
     */
    public function models(array $value): void
    {
        $this->models = $value;
    }

    /**
     * Returns the key values associated with the data models.
     *
     * @return array the list of key values corresponding to {@see models}. Each data model in
     * {@see models} is uniquely identified by the corresponding key value in this array.
     */
    public function getKeys(): array
    {
        $this->prepare();

        return $this->keys;
    }

    /**
     * Sets the key values associated with the data models.
     *
     * @param array $value the list of key values corresponding to {@see models}.
     */
    public function keys(array $value): void
    {
        $this->keys = $value;
    }

    /**
     * Returns the number of data models in the current page.
     *
     * @return int the number of data models in the current page.
     */
    public function getCount(): int
    {
        return count($this->getModels());
    }

    /**
     * Returns the total number of data models.
     *
     * When {@see pagination} is false, this returns the same value as {@see count}. Otherwise, it will call
     * {@see prepareTotalCount()} to get the count.
     *
     * @return int total number of possible data models.
     */
    public function getTotalCount(): int
    {
        if ($this->getPagination() === false) {
            return $this->getCount();
        }

        if ($this->totalCount === 0) {
            $this->totalCount = $this->prepareTotalCount();
        }

        return $this->totalCount;
    }

    /**
     * Sets the total number of data models.
     *
     * @param int $value the total number of data models.
     */
    public function totalCount(int $value): void
    {
        $this->totalCount = $value;
    }

    /**
     * Returns the pagination object used by this data provider.
     *
     * @return PaginatorInterface the pagination object. If this is false, it means the pagination is disabled.
     */
    public function getPagination(): ?PaginatorInterface
    {
        return $this->pagination;
    }

    /**
     * Sets the pagination for this data provider.
     *
     * @param PaginatorInterface $value the pagination to be used by this data provider.
     */
    public function pagination(PaginatorInterface $value): void
    {
        $this->pagination = $value;
    }

    /**
     * Returns the sorting object used by this data provider.
     *
     * @return Sort|null the sorting object. If this is false, it means the sorting is disabled.
     */
    public function getSort(): ?Sort
    {
        return $this->sort;
    }

    /**
     * Sets the sort definition for this data provider.
     *
     * @param Sort $value the sort definition to be used by this data provider.
     */
    public function withSort(Sort $value): void
    {
        $this->sort = $value;
    }

    /**
     * Refreshes the data provider.
     *
     * After calling this method, if {@see getModels()}, {@see getKeys()} or {@see getTotalCount()} is called again,
     * they will re-execute the query and return the latest data available.
     */
    public function refresh(): void
    {
        $this->totalCount = 0;
        $this->models = [];
        $this->keys = [];
    }

    /**
     * Set the Id of the widget.
     *
     * @param string $value
     *
     * @return $this
     */
    public function id(string $value): self
    {
        $this->id = $value;
        return $this;
    }

    /**
     * Counter used to generate {@see id} for widgets.
     *
     * @param int $value
     */
    public static function counterId(int $value): void
    {
        self::$counter = $value;
    }

    /**
     * The prefix to the automatically generated widget IDs.
     *
     * @param string $value
     *
     * @return $this
     *
     * {@see getId()}
     */
    public function autoIdPrefix(string $value): self
    {
        $this->autoIdPrefix = $value;
        return $this;
    }

    /**
     * @return string|null Id of the widget.
     */
    protected function getId(): ?string
    {
        if ($this->autoGenerate && $this->id === null) {
            $this->id = $this->autoIdPrefix . ++self::$counter;
        }

        return $this->id;
    }
}
