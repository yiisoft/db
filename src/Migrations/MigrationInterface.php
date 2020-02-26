<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migrations;

/**
 * The MigrationInterface defines the minimum set of methods to be implemented by a database migration.
 *
 * Each migration class should provide the {@see up()} method containing the logic for "upgrading" the database
 * and the {@see down()} method for the "downgrading" logic.
 */
interface MigrationInterface
{
    /**
     * This method contains the logic to be executed when applying this migration.
     *
     * @return bool return a false value to indicate the migration fails and should not proceed further. All other
     * return values mean the migration succeeds.
     */
    public function up(): bool;

    /**
     * This method contains the logic to be executed when removing this migration.
     *
     * The default implementation throws an exception indicating the migration cannot be removed.
     *
     * @return bool return a false value to indicate the migration fails and should not proceed further. All other
     * return values mean the migration succeeds.
     */
    public function down(): bool;
}
