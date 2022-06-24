<?php

namespace CodeDistortion\Adapt\Adapters\LaravelSQLite;

use CodeDistortion\Adapt\Adapters\AbstractClasses\AbstractReuseTransaction;
use CodeDistortion\Adapt\Adapters\Interfaces\ReuseTransactionInterface;
use CodeDistortion\Adapt\Adapters\Traits\SQLite\SQLiteHelperTrait;
use CodeDistortion\Adapt\Support\LaravelSupport;
use CodeDistortion\Adapt\Support\Settings;
use Throwable;

/**
 * Database-adapter methods related to managing Laravel/SQLite reuse through transactions.
 */
class LaravelSQLiteReuseTransaction extends AbstractReuseTransaction implements ReuseTransactionInterface
{
    use SQLiteHelperTrait;



    /**
     * Determine if a transaction can be used on this database (for database re-use).
     *
     * @return boolean
     */
    public function supportsTransactions(): bool
    {
        // the database connection is closed between tests,
        // which causes :memory: databases to disappear,
        // so transactions can't be used on them between tests
        return !$this->isMemoryDatabase();
    }



    /**
     * Start the wrapper-transaction.
     *
     * @return void
     */
    public function startTransaction(): void
    {
        $this->di->db->update("UPDATE `" . Settings::REUSE_TABLE . "` SET `transaction_reusable` = 1");
        LaravelSupport::startTransaction($this->configDTO->connection);
        $this->di->db->update("UPDATE `" . Settings::REUSE_TABLE . "` SET `transaction_reusable` = 0");
    }

    /**
     * Roll-back the wrapper-transaction.
     *
     * @return void
     */
    public function rollBackTransaction(): void
    {
        LaravelSupport::rollBackTransaction($this->configDTO->connection);
    }

    /**
     * Load the transaction-reusable value from Adapt's reuse-info table.
     *
     * @return integer|null
     */
    protected function loadTransactionReusable(): ?int
    {
        try {
            $rows = $this->di->db->select(
                "SELECT `transaction_reusable` FROM `" . Settings::REUSE_TABLE . "` LIMIT 0, 1"
            );

            return $rows[0]?->transaction_reusable ?? null;

        } catch (Throwable) {
            return null;
        }
    }

    /**
     * It was detected that the transaction was rolled-back, record this so the database is rebuilt next time.
     *
     * @return void
     */
    public function recordThatTransactionWasRolledBack(): void
    {
        $this->di->db->update("UPDATE `" . Settings::REUSE_TABLE . "` SET `transaction_reusable` = 0");
    }
}
