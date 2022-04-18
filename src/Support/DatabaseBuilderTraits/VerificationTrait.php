<?php

namespace CodeDistortion\Adapt\Support\DatabaseBuilderTraits;

use CodeDistortion\Adapt\DatabaseBuilder;

/**
 * @mixin DatabaseBuilder
 */
trait VerificationTrait
{
    /**
     * Create and populate the verification table.
     *
     * @return void
     */
    private function setUpVerification()
    {
        if (!$this->configDTO->shouldVerifyDatabase()) {
            return;
        }

        $this->dbAdapter()->verifier->setUpVerification(
            $this->configDTO->shouldVerifyStructure(),
            $this->configDTO->shouldVerifyData()
        );
    }

    /**
     * Record that a test with verification has begun.
     *
     * @return void
     */
    public function recordVerificationStart()
    {
        if (!$this->configDTO->shouldVerifyDatabase()) {
            return;
        }

        $this->dbAdapter()->verifier->recordVerificationStart();
    }

    /**
     * Record that a test with verification has finished, and the database is clean.
     *
     * @return void
     */
    private function recordVerificationStop()
    {
        if (!$this->configDTO->shouldVerifyDatabase()) {
            return;
        }

        $this->dbAdapter()->verifier->recordVerificationStop();
    }

    /**
     * Verify that the database's structure hasn't changed.
     *
     * @param boolean $addNewLineAfter Whether a new line should be added after logging or not.
     * @return void
     */
    private function verifyDatabaseStructure(bool $addNewLineAfter)
    {
        if (!$this->configDTO->shouldVerifyStructure()) {
            return;
        }

        $this->dbAdapter()->verifier->verifyStructure($addNewLineAfter);
    }

    /**
     * Verify that the database's content hasn't changed.
     *
     * @param boolean $addNewLineAfter Whether a new line should be added after logging or not.
     * @return void
     */
    private function verifyDatabaseData(bool $addNewLineAfter)
    {
        if (!$this->configDTO->shouldVerifyData()) {
            return;
        }

        $this->dbAdapter()->verifier->verifyData($addNewLineAfter);
    }
}
