<?php

namespace CodeDistortion\Adapt\Boot;

use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\DTO\PropBagDTO;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use Laravel\Dusk\Browser;

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
    public function testName(string $testName): self;

    /**
     * Specify the properties that were present in the test-class.
     *
     * @param PropBagDTO $propBag A populated PropBagDTO.
     * @return static
     */
    public function props(PropBagDTO $propBag): self;

    /**
     * Specify the closure to call to start a db transaction.
     *
     * @param callable $transactionClosure The closure to use.
     * @return static
     */
    public function transactionClosure(callable $transactionClosure): self;

    /**
     * Specify the callback closure to call that will initialise the DatabaseBuilder/s.
     *
     * @param callable|null $initCallback The closure to use.
     * @return static
     */
    public function initCallback(?callable $initCallback): self;

//    /**
//     * Specify the DIContainer to use.
//     *
//     * @param DIContainer $di The DIContainer to use.
//     * @return static
//     */
//    public function setDI(DIContainer $di): self;

    /**
     * Ensure the storage-directory exists.
     *
     * @return static
     * @throws AdaptConfigException When the storage directory cannot be created.
     */
    public function ensureStorageDirExists(): self;



    /**
     * Run the process to build the databases.
     *
     * @return void
     */
    public function run(): void;

    /**
     * Let the databaseInit(…) method generate a new DatabaseBuilder.
     *
     * Create a new DatabaseBuilder object, and add it to the list to execute later.
     *
     * @param string $connection The database connection to prepare.
     * @return DatabaseBuilder
     * @throws AdaptConfigException Thrown when the connection doesn't exist.
     */
    public function newBuilder(string $connection): DatabaseBuilder;

    /**
     * Build the list of connections that Adapt has prepared, and their corresponding databases.
     *
     * @return array
     */
    public function buildConnectionDBsList(): array;

    /**
     * Store the current config in the filesystem temporarily, and get the browsers refer to it in a cookie.
     *
     * @param Browser[]             $browsers      The browsers to update with the current config.
     * @param array<string, string> $connectionDBs The list of connections that have been prepared,
     *                                             and their corresponding databases from the framework.
     * @return void
     */
    public function haveBrowsersShareConfig(array $browsers, array $connectionDBs): void;

    /**
     * Check to see if any of the transactions were committed, and generate an exception.
     *
     * To be run after the transaction was rolled back.
     *
     * @return void
     */
    public function checkForCommittedTransactions(): void;

    /**
     * Perform any clean-up needed after the test has finished.
     *
     * @return void
     */
    public function postTestCleanUp(): void;

    /**
     * Remove stale databases, snapshots and orphaned config files.
     *
     * @return void
     */
    public function purgeStaleThings(): void;
}
