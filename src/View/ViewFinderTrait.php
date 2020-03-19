<?php

declare(strict_types=1);

namespace Yiisoft\Db\View;

trait ViewFinderTrait
{
    private array $viewNames = [];

    /**
     * Returns all views names in the database.
     *
     * @param string $schema the schema of the views. Defaults to empty string, meaning the current or default schema.
     *
     * @return array all views names in the database. The names have NO schema name prefix.
     */
    abstract protected function findViewNames(string $schema = ''): array;

    /**
     * Returns all view names in the database.
     *
     * @param string $schema the schema of the views. Defaults to empty string, meaning the current or default schema
     * name. If not empty, the returned view names will be prefixed with the schema name.
     * @param bool $refresh whether to fetch the latest available view names. If this is false, view names fetched
     * previously (if available) will be returned.
     *
     * @return array all view names in the database.
     */
    public function getViewNames(string $schema = '', bool $refresh = false): array
    {
        if (!isset($this->viewNames[$schema]) || $refresh) {
            $this->viewNames[$schema] = $this->findViewNames($schema);
        }

        return $this->viewNames[$schema];
    }
}
