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
            "Started the wrapper-transaction in \"{$this->configDTO->connection}\" "
            . "database \"{$this->configDTO->database}\"",
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
     * Check to see if any of the transaction was rolled-back (if relevant).
     *
     * @return void
     * @throws AdaptTransactionException When the test rolled-back the wrapper-transaction.
     */
    private function checkForRolledBackTransaction()
    {
        if (!$this->configDTO->shouldUseTransaction()) {
            return;
        }

        if (!$this->dbAdapter()->reuseTransaction->wasTransactionRolledBack()) {
            return;
        }

        $this->dbAdapter()->reuseTransaction->recordThatTransactionWasRolledBack();

//        $this->di->log->vWarning(
//            "The wrapper-transaction was rolled-back in \"{$this->configDTO->connection}\" database "
//            . "\"{$this->configDTO->database}\"",
//            $addNewLineAfter
//        );

        throw AdaptTransactionException::aTestRolledBackTheWrapperTransaction();
    }

    /**
     * Check to see if any of the transaction was committed (if relevant).
     *
     * @param integer $logTimer        The timer that started before rolling the transaction back.
     * @param boolean $addNewLineAfter Whether a new line should be added after logging or not.
     * @return void
     * @throws AdaptTransactionException When the test committed the wrapper-transaction.
     */
    private function checkForCommittedTransaction(int $logTimer, bool $addNewLineAfter)
    {
        if (!$this->configDTO->shouldUseTransaction()) {
            return;
        }

        if (!$this->dbAdapter()->reuseTransaction->wasTransactionCommitted()) {
            $this->di->log->vDebug(
                "Rolled back the wrapper-transaction in \"{$this->configDTO->connection}\" "
                . "database \"{$this->configDTO->database}\"",
                $logTimer,
                $addNewLineAfter
            );
            return;
        }

//        $this->di->log->vWarning(
//            "The wrapper-transaction was committed in database \"{$this->configDTO->database}\"",
//            $addNewLineAfter
//        );

        throw AdaptTransactionException::aTestCommittedTheWrapperTransaction();
    }
}
