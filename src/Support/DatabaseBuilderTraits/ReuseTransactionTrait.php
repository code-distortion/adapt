<?php

namespace CodeDistortion\Adapt\Support\DatabaseBuilderTraits;

use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\Exceptions\AdaptTransactionException;

/**
 * @mixin DatabaseBuilder
 */
trait ReuseTransactionTrait
{
    /**
     * Start the database transaction.
     *
     * @return void
     */
    public function applyTransaction()
    {
        if (!$this->configDTO->shouldUseTransaction()) {
            return;
        }

        $this->dbAdapter()->reuseTransaction->applyTransaction();
    }

    /**
     * Check to see if any of the transaction was committed (if relevant), and generate a warning.
     *
     * @return void
     * @throws AdaptTransactionException When the test committed the test-transaction.
     */
    private function checkForCommittedTransaction()
    {
        if (!$this->configDTO->shouldUseTransaction()) {
            return;
        }
        if (!$this->dbAdapter()->reuseTransaction->wasTransactionCommitted()) {
            return;
        }

        $this->di->log->warning(
            "The {$this->configDTO->testName} test committed the transaction wrapper - "
            . "turn \$reuseTransaction off to isolate it from other "
            . "tests that don't commit their transactions"
        );

        throw AdaptTransactionException::testCommittedTransaction($this->configDTO->testName);
    }
}
