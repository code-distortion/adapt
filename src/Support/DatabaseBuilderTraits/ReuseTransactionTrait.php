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
     * Start the wrapper-transaction.
     *
     * @return void
     */
    public function startTransaction()
    {
        if (!$this->configDTO->shouldUseTransaction()) {
            return;
        }

        $logTimer = $this->di->log->newTimer();

        $this->dbAdapter()->reuseTransaction->startTransaction();

        $this->di->log->vDebug(
            "Started the wrapper-transaction in database \"{$this->configDTO->database}\"",
            $logTimer
        );
    }

    /**
     * Roll-back the wrapper-transaction.
     *
     * @return void
     */
    public function rollBackTransaction()
    {
        if (!$this->configDTO->shouldUseTransaction()) {
            return;
        }

        $this->dbAdapter()->reuseTransaction->rollBackTransaction();
    }

    /**
     * Check to see if any of the transaction was committed (if relevant), and generate a warning.
     *
     * @param integer $logTimer        The timer that started before rolling the transaction back.
     * @param boolean $addNewLineAfter Whether a new line should be added after logging or not.
     * @return void
     * @throws AdaptTransactionException When the test committed the test-transaction.
     */
    private function checkForCommittedTransaction(int $logTimer, bool $addNewLineAfter)
    {
        if (!$this->configDTO->shouldUseTransaction()) {
            return;
        }

        if (!$this->dbAdapter()->reuseTransaction->wasTransactionCommitted()) {
            $this->di->log->vDebug(
                "Rolled back the wrapper-transaction in database \"{$this->configDTO->database}\"",
                $logTimer,
                $addNewLineAfter
            );
            return;
        }

//        $this->di->log->vWarning(
//            "The wrapper-transaction was committed in database \"{$this->configDTO->database}\"",
//            $addNewLineAfter
//        );

        throw AdaptTransactionException::aTestCommittedTheWrapperTransaction($this->configDTO->testName);
    }
}
