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
    /** @var string|null The name of the test being run. */
    protected $testName = null;

    /** @var PropBagDTO|null The properties that were present in the test-class. */
    protected $propBag = null;

    /** @var boolean Whether a browser test is being run. */
    protected $browserTestDetected = false;

    /** @var callable|null The closure to call to start a db transaction. */
    protected $transactionClosure = null;

    /** @var callable|null The callback closure to call that will initialise the DatabaseBuilder/s. */
    private $initCallback = null;

    /** @var DatabaseBuilder[] The database builders made by this object (so they can be executed afterwards). */
    private $builders = [];

    /** @var DIContainer|null The DIContainer to be used. */
//    protected ?DIContainer $di = null;


    /**
     * Set the name of the test being run.
     *
     * @param string $testName The name of the test being run.
     * @return static
     */
    public function testName(string $testName)
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
    public function props(PropBagDTO $propBag)
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
    public function browserTestDetected(bool $browserTestDetected)
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
    public function transactionClosure(callable $transactionClosure)
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
    public function initCallback($initCallback)
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
    public function addBuilder(DatabaseBuilder $builder)
    {
        $this->builders[] = $builder;
    }

    /**
     * Run the process to build the databases.
     *
     * @return void
     */
    public function run()
    {
        if (Settings::$isFirstTest) {
            Settings::$isFirstTest = false;
            $this->newLog()->info('==== Adapt initialisation ================');
            $this->purgeInvalidThings();
        }

//        $this->resolveDI();
        $this->initBuilders();
        $this->executeBuilders();
    }

    /**
     * Initialise the builders, calling the custom databaseInit method if it has been defined.
     *
     * @return void
     */
    private function initBuilders()
    {
        if (!$this->propBag) {
            return;
        }
        if (!$this->propBag->config('build_databases', 'buildDatabases')) {
            return;
        }

        $builder = $this->newDefaultBuilder();

        if ($this->initCallback) {
            $callback = $this->initCallback;
            $callback($builder);
        }
    }

    /**
     * Create a new DatabaseBuilder object based on the "default" database connection.
     *
     * @return DatabaseBuilder
     */
    abstract protected function newDefaultBuilder(): DatabaseBuilder;

    /**
     * Create a new DatabaseBuilder object, and add it to the list to execute later.
     *
     * @param string $connection The database connection to prepare.
     * @return DatabaseBuilder
     * @throws AdaptConfigException Thrown when the connection doesn't exist.
     */
    abstract protected function newBuilder(string $connection): DatabaseBuilder;

    /**
     * Execute the builders that this object created (ie. build their databases).
     *
     * Any that have already been executed will be skipped.
     *
     * @return void
     */
    private function executeBuilders()
    {
        foreach ($this->builders as $builder) {
            if (!$builder->hasExecuted()) {
                $builder->execute();
            }
        }
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
     * Build a new Log instance.
     *
     * @return LogInterface
     */
    abstract protected function newLog(): LogInterface;

    /**
     * Check to see if any of the transactions were committed, and generate a warning.
     *
     * @return void
     */
    public function checkForCommittedTransactions()
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
    abstract public function postTestCleanUp();

    /**
     * Remove invalid databases, snapshots and orphaned config files.
     *
     * @return void
     */
    abstract public function purgeInvalidThings();
}
