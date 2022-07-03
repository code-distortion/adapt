<?php

namespace CodeDistortion\Adapt\Adapters\LaravelPostgreSQL;

use CodeDistortion\Adapt\Adapters\AbstractClasses\AbstractReuseTransaction;
use CodeDistortion\Adapt\Adapters\Interfaces\ReuseTransactionInterface;
use CodeDistortion\Adapt\Support\LaravelSupport;
use CodeDistortion\Adapt\Support\Settings;
use Throwable;

/**
 * Database-adapter methods related to managing Laravel/PostgreSQL reuse through transactions.
 */
class LaravelPostgreSQLReuseTransaction extends AbstractReuseTransaction implements ReuseTransactionInterface
{
    /**
     * Determine if a transaction can be used on this database (for database re-use).
     *
     * @return boolean
     */
    public function supportsTransactions(): bool
    {
        return true;
    }



    /**
     * Start the wrapper-transaction.
     *
     * @return void
     */
    public function startTransaction()
    {
        $this->di->db->update("UPDATE \"" . Settings::REUSE_TABLE . "\" SET \"transaction_reusable\" = TRUE");
        LaravelSupport::startTransaction($this->configDTO->connection);
        $this->di->db->update("UPDATE \"" . Settings::REUSE_TABLE . "\" SET \"transaction_reusable\" = FALSE");
    }

    /**
     * Roll-back the wrapper-transaction.
     *
     * @return void
     */
    public function rollBackTransaction()
    {
        LaravelSupport::rollBackTransaction($this->configDTO->connection);
    }

    /**
     * Load the transaction-reusable value from Adapt's reuse-info table.
     *
     * @return integer|null
     */
    protected function loadTransactionReusable()
    {
        try {
            $rows = $this->di->db->select(
                "SELECT \"transaction_reusable\" FROM \"" . Settings::REUSE_TABLE . "\" LIMIT 1 OFFSET 0"
            );

            return (($_ = $rows[0]) ? $_->transaction_reusable : null) ?? null;

        } catch (Throwable $exception) {
            return null;
        }
    }

    /**
     * It was detected that the transaction was rolled-back, record this so the database is rebuilt next time.
     *
     * @return void
     */
    public function recordThatTransactionWasRolledBack()
    {
        $this->di->db->update("UPDATE \"" . Settings::REUSE_TABLE . "\" SET \"transaction_reusable\" = FALSE");
    }
}
