<?php

declare(strict_types=1);

namespace Yiisoft\Db\Data;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;

/**
 * SqlDataProvider implements a data provider based on a plain SQL statement.
 *
 * SqlDataProvider provides data in terms of arrays, each representing a row of query result.
 *
 * Like other data providers, SqlDataProvider also supports sorting and pagination.
 *
 * It does so by modifying the given {@see sql} statement with "ORDER BY" and "LIMIT" clauses. You may configure the
 * {@see sort} and {@see pagination} properties to customize sorting and pagination behaviors.
 *
 * SqlDataProvider may be used in the following way:
 *
 * ```php
 * $count = Yii::$app->db->createCommand('
 *     SELECT COUNT(*) FROM user WHERE status=:status
 * ', [':status' => 1])->queryScalar();
 *
 * $dataProvider = new SqlDataProvider([
 *     'sql' => 'SELECT * FROM user WHERE status=:status',
 *     'params' => [':status' => 1],
 *     'totalCount' => $count,
 *     'sort' => [
 *         'attributes' => [
 *             'age',
 *             'name' => [
 *                 'asc' => ['first_name' => SORT_ASC, 'last_name' => SORT_ASC],
 *                 'desc' => ['first_name' => SORT_DESC, 'last_name' => SORT_DESC],
 *                 'default' => SORT_DESC,
 *                 'label' => 'Name',
 *             ],
 *         ],
 *     ],
 *     'pagination' => [
 *         'pageSize' => 20,
 *     ],
 * ]);
 *
 * // get the user records in the current page
 * $models = $dataProvider->getModels();
 * ```
 *
 * Note: if you want to use the pagination feature, you must configure the {@see totalCount} property to be the total
 * number of rows (without pagination). And if you want to use the sorting feature, you must configure the {@see sort}
 * property so that the provider knows which columns can be sorted.
 *
 * For more details and usage information on SqlDataProvider.
 * See the [guide article on data providers](guide:output-data-providers).
 */
final class SqlDataProvider extends DataProvider
{
    private ConnectionInterface $db;
    private string $sql;
    private array $params = [];

    /**
     * @var callable|string|null the column that is used as the key of the data models.
     * This can be either a column name, or a callable that returns the key value of a given data model.
     *
     * If this is not set, the keys of the [[models]] array will be used.
     */
    private $key = null;

    public function __construct(ConnectionInterface $db, string $sql, array $params = [])
    {
        $this->db = $db;
        $this->sql = $sql;
        $this->params = $params;
    }

    /**
     * Prepares the data models that will be made available in the current page.
     *
     * @return array the available data models.
     */
    protected function prepareModels(): array
    {
        $sort = $this->getSort();
        $pagination = $this->getPagination();

        $sql = $this->sql;
        $orders = [];
        $limit = $offset = null;

        if ($sort !== null) {
            $orders = $sort->getOrders();

            $pattern = '/\s+order\s+by\s+([\w\s,\.]+)$/i';

            if (preg_match($pattern, $sql, $matches)) {
                array_unshift($orders, new Expression($matches[1]));

                $sql = preg_replace($pattern, '', $sql);
            }
        }

        if ($pagination !== null) {
            $pagination->totalCount = $this->getTotalCount();

            $limit = $pagination->getLimit();
            $offset = $pagination->getOffset();
        }

        $sql = $this->db->getQueryBuilder()->buildOrderByAndLimit($sql, $orders, $limit, $offset);

        return $this->db->createCommand($sql, $this->params)->queryAll();
    }

    /**
     * Prepares the keys associated with the currently available data models.
     *
     * @param array $value the available data models.
     *
     * @return array the keys.
     */
    protected function prepareKeys(array $value = []): array
    {
        $keys = [];

        if ($this->key !== null) {
            foreach ($value as $model) {
                if (is_string($this->key)) {
                    $keys[] = $model[$this->key];
                } else {
                    $keys[] = ($this->key)($model);
                }
            }

            return $keys;
        }

        return array_keys($value);
    }

    /**
     * Returns a value indicating the total number of data models in this data provider.
     *
     * @return int total number of data models in this data provider.
     */
    protected function prepareTotalCount(): int
    {
        return (int) (new Query($this->db))->from(['sub' => "({$this->sql})"])->params($this->params)->count('*');
    }
}
