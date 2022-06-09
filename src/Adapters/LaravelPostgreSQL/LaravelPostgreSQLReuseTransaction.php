<?php

namespace CodeDistortion\Adapt\Adapters\LaravelPostgreSQL;

use CodeDistortion\Adapt\Adapters\Interfaces\ReuseTransactionInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\Support\LaravelSupport;
use CodeDistortion\Adapt\Support\Settings;
use stdClass;
use Throwable;

/**
 * Database-adapter methods related to managing Laravel/PostgreSQL reuse through transactions.
 */
class LaravelPostgreSQLReuseTransaction implements ReuseTransactionInterface
{
    use InjectTrait;



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
    public function startTransaction(): void
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
    public function rollBackTransaction(): void
    {
        LaravelSupport::rollBackTransaction($this->configDTO->connection);
    }

    /**
     * Check if the transaction was committed.
     *
     * @return boolean
     */
    public function wasTransactionCommitted(): bool
    {
        try {
            $rows = $this->di->db->select(
                "SELECT \"transaction_reusable\" FROM \"" . Settings::REUSE_TABLE . "\" LIMIT 1 OFFSET 0"
            );

            /** @var stdClass|null $reuseInfo */
            $reuseInfo = $rows[0] ?? null;

            return ($reuseInfo->transaction_reusable ?? null) === false;

        } catch (Throwable $e) {
            return false;
        }
    }
}
