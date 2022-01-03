<?php

namespace CodeDistortion\Adapt\Boot;

use CodeDistortion\Adapt\DTO\PropBagDTO;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;

/**
 * Bootstrap Adapt for tests.
 */
interface BootTestInterface
{
    /**
     * Set the name of the test being run.
     *
     * @param string $testName The name of the test being run.
     * @return static
     */
    public function testName($testName);

    /**
     * Specify the properties that were present in the test-class.
     *
     * @param PropBagDTO $propBag A populated PropBagDTO.
     * @return static
     */
    public function props($propBag);

    /**
     * Specify the closure to call to start a db transaction.
     *
     * @param callable $transactionClosure The closure to use.
     * @return static
     */
    public function transactionClosure($transactionClosure);

    /**
     * Specify the callback closure to call that will initialise the DatabaseBuilder/s.
     *
     * @param callable|null $initCallback The closure to use.
     * @return static
     */
    public function initCallback($initCallback);

//    /**
//     * Specify the DIContainer to use.
//     *
//     * @param DIContainer $di The DIContainer to use.
//     * @return static
//     */
//    public function setDI(DIContainer $di);

    /**
     * Ensure the storage-directory exists.
     *
     * @return static
     * @throws AdaptConfigException When the storage directory cannot be created.
     */
    public function ensureStorageDirExists();



    /**
     * Run the process to build the databases.
     *
     * @return void
     */
    public function run();

    /**
     * Check to see if any of the transactions were committed, and generate a warning.
     *
     * @return void
     */
    public function checkForCommittedTransactions();

    /**
     * Perform any clean-up needed after the test has finished.
     *
     * @return void
     */
    public function postTestCleanUp();

    /**
     * Remove invalid databases, snapshots and orphaned config files.
     *
     * @return void
     */
    public function purgeInvalidThings();
}
