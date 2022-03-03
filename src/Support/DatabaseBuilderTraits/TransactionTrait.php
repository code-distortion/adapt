<?php

namespace CodeDistortion\Adapt\Support\DatabaseBuilderTraits;

use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\Exceptions\AdaptTransactionException;

/**
 * @mixin DatabaseBuilder
 */
trait TransactionTrait
{
    /**
     * Start the database transaction.
     *
     * @return void
     */
    public function applyTransaction(): void
    {
        if (!$this->config->usingTransactions()) {
            return;
        }

        $this->dbAdapter()->build->applyTransaction();
    }

    /**
     * Check to see if any of the transaction was committed (if relevant), and generate a warning.
     *
     * @return void
     * @throws AdaptTransactionException Thrown when the test committed the test-transaction.
     */
    public function checkForCommittedTransaction(): void
    {
        if (!$this->config->usingTransactions()) {
            return;
        }
        if (!$this->dbAdapter()->reuse->wasTransactionCommitted()) {
            return;
        }

        $this->di->log->warning(
            "The {$this->config->testName} test committed the transaction wrapper - "
            . "turn \$reuseTestDBs off to isolate it from other "
            . "tests that don't commit their transactions"
        );

        throw AdaptTransactionException::testCommittedTransaction($this->config->testName);
    }
}
