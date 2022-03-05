<?php

namespace CodeDistortion\Adapt\Boot;

use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DI\Injectable\Interfaces\LogInterface;
use CodeDistortion\Adapt\DTO\PropBagDTO;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use CodeDistortion\Adapt\Support\Settings;

/**
 * Bootstrap Adapt for tests.
 */
abstract class BootTestAbstract implements BootTestInterface
{
    /** @var LogInterface The LogInterface to use. */
    protected LogInterface $log;

    /** @var string The name of the test being run. */
    protected string $testName;

    /** @var PropBagDTO The properties that were present in the test-class. */
    protected PropBagDTO $propBag;

    /** @var boolean Whether a browser test is being run. */
    protected bool $browserTestDetected = false;

    /** @var callable The closure to call to start a db transaction. */
    protected $transactionClosure;

    /** @var callable|null The callback closure to call that will initialise the DatabaseBuilder/s. */
    private $initCallback;

    /** @var DatabaseBuilder[] The database builders made by this object (so they can be executed afterwards). */
    private array $builders = [];

//    /** @var DIContainer|null The DIContainer to be used. */
//    protected ?DIContainer $di = null;



    /**
     * Set the LogInterface to use.
     *
     * @param LogInterface $log The logger to use.
     * @return static
     */
    public function log(LogInterface $log): self
    {
        $this->log = $log;
        return $this;
    }

    /**
     * Set the name of the test being run.
     *
     * @param string $testName The name of the test being run.
     * @return static
     */
    public function testName(string $testName): self
    {
        $this->testName = $testName;
        return $this;
    }

    /**
     * Specify the properties that were present in the test-class.
     *
     * @param PropBagDTO $propBag A populated PropBagDTO.
     * @return static
     */
    public function props(PropBagDTO $propBag): self
    {
        $this->propBag = $propBag;
        return $this;
    }

    /**
     * Specify if a browser test is being run.
     *
     * @param boolean $browserTestDetected Whether or not a browser test is being run.
     * @return static
     */
    public function browserTestDetected(bool $browserTestDetected): self
    {
        $this->browserTestDetected = $browserTestDetected;
        return $this;
    }

    /**
     * Specify the closure to call to start a db transaction.
     *
     * @param callable $transactionClosure The closure to use.
     * @return static
     */
    public function transactionClosure(callable $transactionClosure): self
    {
        $this->transactionClosure = $transactionClosure;
        return $this;
    }

    /**
     * Specify the callback closure to call that will initialise the DatabaseBuilder/s.
     *
     * @param callable|null $initCallback The closure to use.
     * @return static
     */
    public function initCallback(?callable $initCallback): self
    {
        $this->initCallback = $initCallback;
        return $this;
    }

    /**
     * Specify the DIContainer to use.
     *
     * @param DIContainer $di The DIContainer to use.
     * @return static
     */
//    public function setDI(DIContainer $di): self
//    {
//        $this->di = $di;
//        return $this;
//    }

    /**
     * Store the give DatabaseBuilder.
     *
     * @param DatabaseBuilder $builder The database builder to store.
     * @return void
     */
    public function addBuilder(DatabaseBuilder $builder): void
    {
        $this->builders[] = $builder;
    }

    /**
     * Run the process to build the databases.
     *
     * @return void
     */
    public function run(): void
    {
        $this->isAllowedToRun();

//        $this->resolveDI();
        $this->initBuilders();
        $this->purgeStaleThings();
        $this->executeBuilders();

        $this->registerConnectionDBs();
    }

    /**
     * Check that it's safe to run.
     *
     * @return void
     * @throws AdaptConfigException When the .env.testing file wasn't used to build the environment.
     */
    abstract protected function isAllowedToRun(): void;

    /**
     * Initialise the builders, calling the custom databaseInit(…) method if it has been defined.
     *
     * @return void
     */
    private function initBuilders(): void
    {
        if (!$this->propBag->adaptConfig('build_databases', 'buildDatabases')) {
            return;
        }

        $builder = $this->newDefaultBuilder();

        if (!$this->initCallback) {
            return;
        }

        $callback = $this->initCallback;
        $callback($builder);
    }

    /**
     * Create a new DatabaseBuilder object based on the "default" database connection.
     *
     * @return DatabaseBuilder
     */
    abstract protected function newDefaultBuilder(): DatabaseBuilder;

    /**
     * Execute the builders that this object created (i.e. build their databases).
     *
     * Any that have already been executed will be skipped.
     *
     * @return void
     */
    private function executeBuilders(): void
    {
        $builders = $this->pickBuildersToExecute();

        foreach ($builders as $builder) {
            $builder->execute();
        }

        // apply the transactions, AFTER all the databases have been built
        foreach ($builders as $builder) {
            $builder->applyTransaction();
        }
    }

    /**
     * Pick the list of Builders that haven't been executed yet.
     *
     * @return DatabaseBuilder[]
     */
    private function pickBuildersToExecute(): array
    {
        $builders = [];
        foreach ($this->builders as $builder) {
            if (!$builder->hasExecuted()) {
                $builders[] = $builder;
            }
        }
        return $builders;
    }

    /**
     * Check to see if any builders will build locally.
     *
     * @return boolean
     */
    private function hasBuildersThatWillBuildLocally(): bool
    {
        $builders = $this->pickBuildersToExecute();
        foreach ($builders as $builder) {
            if (!$builder->shouldBuildRemotely()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Pick the connections' databases, and register them with the framework.
     *
     * This is done so that user-land code can get the list. e.g. to include in headers to the
     *
     * @return void
     */
    private function registerConnectionDBs(): void
    {
        $this->registerPreparedConnectionDBsWithFramework($this->buildConnectionDBsList());
    }

    /**
     * Record the list of connections and their databases with the framework.
     *
     * @param array<string,string> $connectionDatabases The connections and the databases created for them.
     * @return void
     */
    abstract protected function registerPreparedConnectionDBsWithFramework(array $connectionDatabases): void;

    /**
     * Build the list of connections that Adapt has prepared, and their corresponding databases.
     *
     * @return array<string, string>
     */
    public function buildConnectionDBsList(): array
    {
        $connectionDatabases = [];
        foreach ($this->builders as $builder) {
            $connectionDatabases[$builder->getConnection()] = $builder->getResolvedDatabase();
        }
        return $connectionDatabases;
    }

    /**
     * Use the existing DIContainer, but build a default one if it hasn't been set.
     *
     * @return void
     */
//    private function resolveDI(): void
//    {
//        if (!$this->di) {
//            $this->setDI($this->defaultDI());
//        }
//    }

    /**
     * Build a default DIContainer object.
     *
     * @param string $connection The connection to start using.
     * @return DIContainer
     */
    abstract protected function defaultDI(string $connection): DIContainer;

    /**
     * Check to see if any of the transactions were committed, and generate an exception.
     *
     * To be run after the transaction was rolled back.
     *
     * @return void
     */
    public function checkForCommittedTransactions(): void
    {
        foreach ($this->builders as $builder) {
            $builder->checkForCommittedTransaction();
        }
    }

    /**
     * Perform any clean-up needed after the test has finished.
     *
     * @return void
     */
    abstract public function postTestCleanUp(): void;

    /**
     * Handle the process to remove stale databases, snapshots and orphaned config files.
     *
     * @return void
     */
    private function purgeStaleThings(): void
    {
        if (!Settings::$isFirstTest) {
            return;
        }
        Settings::$isFirstTest = false;

        if (!$this->hasBuildersThatWillBuildLocally()) {
            return;
        }

        $this->performPurgeStaleThings();
    }

    /**
     * Remove stale databases, snapshots and orphaned config files.
     *
     * @return void
     */
    abstract protected function performPurgeStaleThings(): void;

    /**
     * Work out if stale things are allowed to be purged.
     *
     * @return boolean
     */
    abstract protected function canPurgeStaleThings(): bool;
}
