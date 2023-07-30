<?php

namespace CodeDistortion\Adapt\Boot;

use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\DatabaseDefinition;
use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DI\Injectable\Interfaces\LogInterface;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\DTO\PropBagDTO;
use CodeDistortion\Adapt\Exceptions\AdaptBootException;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use CodeDistortion\Adapt\Support\PHPSupport;
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

    /** @var boolean Whether Pest is being used for this test or not. */
    protected bool $usingPest = false;

    /** @var callable|null The callback closure to call that will initialise the DatabaseBuilder/s. */
    private $initCallback;

    /** @var DatabaseDefinition[] The DatabaseDefinitions made by this object (so they can be used afterwards). */
    protected array $databaseDefinitions = [];

    /** @var DatabaseBuilder[] The database builders made by this object (so they can be executed afterwards). */
    protected array $databaseBuilders = [];

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
     * Check if databases are to be built.
     *
     * @return boolean
     */
    private function buildingDatabases(): bool
    {
        return $this->propBag->adaptConfig('build_databases', 'buildDatabases');
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
     * Specify whether Pest is being used or not.
     *
     * @param boolean $usingPest Whether Pest is being used for this test or not.
     * @return static
     */
    public function usingPest(bool $usingPest): self
    {
        $this->usingPest = $usingPest;
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
     * Store the given DatabaseBuilder.
     *
     * @param DatabaseBuilder $builder The database builder to store.
     * @return void
     */
    public function addDatabaseBuilder(DatabaseBuilder $builder): void
    {
        $this->databaseBuilders[] = $builder;
    }

    /**
     * Store the given DatabaseDefinition.
     *
     * @param DatabaseDefinition $databaseDefinition The DatabaseDefinition to store.
     * @return void
     */
    public function addDatabaseDefinition(DatabaseDefinition $databaseDefinition): void
    {
        $this->databaseDefinitions[] = $databaseDefinition;
    }


    /**
     * Run the process to build the databases.
     *
     * @return void
     * @throws AdaptConfigException
     */
    public function runBuildSteps(): void
    {
        $this->isAllowedToRun();

//        $this->resolveDI();
        $this->prepareDatabaseDefinitions();
        $this->prepareDatabaseBuilders();
        $this->applyTheDefaultConnection();

        $this->purgeStaleThings();

        $builders = $this->pickBuildersToExecute();
        $this->checkForDuplicateConnections($builders);
        foreach ($builders as $builder) {
            $builder->execute();
        }

        $this->registerConnectionDBs();
    }

    /**
     * Perform things AFTER BUILDING, but BEFORE the TEST has run.
     *
     * e.g. Start the re-use transaction.
     *
     * @return void
     */
    public function runPostBuildSteps(): void
    {
        foreach ($this->pickExecutedBuilders() as $builder) {
            $builder->runPostBuildSteps();
        }

        $this->log->vDebug("The test will run now");
    }

    /**
     * Perform things AFTER the TEST has run.
     *
     * @return void
     */
    public function runPostTestSteps(): void
    {
        $count = 0;
        foreach ($this->databaseBuilders as $builder) {
            $isLast = (++$count == count($this->databaseBuilders));
            $builder->runPostTestSteps($isLast);
        }
    }

    /**
     * Perform any clean-up needed after the test has finished.
     *
     * @return void
     */
    abstract public function runPostTestCleanUp(): void;



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
     * @throws AdaptBootException When the database name isn't valid.
     */
    private function prepareDatabaseDefinitions(): void
    {
        if (!$this->buildingDatabases()) {
            return;
        }

        if (!$this->initCallback) {
            $this->newDefaultDatabaseDefinition();
            return;
        }

        $callback = $this->initCallback;

        $parameterClass = PHPSupport::getCallableFirstParameterType($callback);

        $parameterClass == DatabaseBuilder::class
            ? $callback($this->newDefaultDatabaseBuilder()) // @deprecated
            : $callback($this->newDefaultDatabaseDefinition());
    }

    /**
     * Initialise the builders, calling the custom databaseInit(…) method if it has been defined.
     *
     * @return void
     */
    private function prepareDatabaseBuilders(): void
    {
        if (!$this->buildingDatabases()) {
            return;
        }

        foreach ($this->databaseDefinitions as $databaseDefinition) {
            $configDTO = PHPSupport::readPrivateProperty($databaseDefinition, 'configDTO');
            $this->newDatabaseBuilderFromConfigDTO($configDTO);
        }

        $this->reCheckIfConnectionsExist();
    }

    /**
     * Pick and apply a default DatabaseBuilder.
     *
     * @return void
     * @throws AdaptConfigException
     */
    private function applyTheDefaultConnection(): void
    {
        if (!$this->buildingDatabases()) {
            return;
        }

        $defaultBuilders = [];
        foreach ($this->databaseBuilders as $databaseBuilder) {
            if ($databaseBuilder->getIsDefaultConnection()) {
                $defaultBuilders[] = $databaseBuilder;
            }
        }

        if (!count($defaultBuilders)) {
            // pick the first that's not false
            foreach ($this->databaseBuilders as $databaseBuilder) {
                if ($databaseBuilder->getIsDefaultConnection() === true) {
                    $defaultBuilders[] = head($this->databaseBuilders);
                }
            }
        }

        if (count($defaultBuilders) > 1) {
            throw AdaptConfigException::tooManyDefaultConnections();
        }

        // perform the action of making this default builder's connection the "default" one
        if (count($defaultBuilders) == 1) {
            $defaultBuilders[0]->makeDefault();
        }
    }



    /**
     * Create a new DatabaseDefinition object based on the "default" database connection,
     * and add it to the list to use later.
     *
     * @return DatabaseDefinition
     * @throws AdaptBootException When the database name isn't valid.
     */
    abstract protected function newDefaultDatabaseDefinition(): DatabaseDefinition;



    /**
     * Create a new DatabaseDefinition object based on the "default" database connection.
     *
     * @param boolean $addToList Add this DatabaseBuilder to the list to use later or not.
     * @return DatabaseBuilder
     * @throws AdaptBootException When the database name isn't valid.
     */
    abstract protected function newDefaultDatabaseBuilder(bool $addToList = true): DatabaseBuilder;

    /**
     * Create a new DatabaseBuilder object based on a ConfigDTO, and add it to the list to execute later.
     *
     * @param ConfigDTO $configDTO The ConfigDTO to use, already defined.
     * @param boolean   $addToList Add this DatabaseBuilder to the list to use later or not.
     * @return DatabaseBuilder
     */
    abstract protected function newDatabaseBuilderFromConfigDTO(
        ConfigDTO $configDTO,
        bool $addToList = true
    ): DatabaseBuilder;



    /**
     * Now that the databaseInit(..) method has been run, re-confirm whether the databases exist (because databaseInit
     * might have changed them).
     *
     * @return void
     */
    abstract protected function reCheckIfConnectionsExist(): void;



    /**
     * Pick the list of Builders that haven't been executed yet.
     *
     * @return DatabaseBuilder[]
     */
    private function pickBuildersToExecute(): array
    {
        $builders = [];
        foreach ($this->databaseBuilders as $builder) {
            if (!$builder->hasExecuted()) {
                $builders[] = $builder;
            }
        }
        return $builders;
    }

    /**
     * Pick the list of Builders that have been executed.
     *
     * @return DatabaseBuilder[]
     */
    private function pickExecutedBuilders(): array
    {
        $builders = [];
        foreach ($this->databaseBuilders as $builder) {
            if ($builder->hasExecuted()) {
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
     * Check to make sure that no two builders try to prepare for the same connection.
     *
     * @param DatabaseBuilder[] $builders The builders to check.
     * @return void
     * @throws AdaptConfigException When a connection would be prepared by more than one DatabaseBuilder.
     */
    private function checkForDuplicateConnections(array $builders): void
    {
        $connections = [];
        foreach ($builders as $builder) {

            $connection = $builder->getConnection();
            if (in_array($connection, $connections)) {
                throw AdaptConfigException::sameConnectionBeingBuiltTwice($connection);
            }
            $connections[] = $connection;
        }
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
        foreach ($this->databaseBuilders as $builder) {
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
     * Handle the process to remove stale databases, snapshots and orphaned config files.
     *
     * @return void
     */
    private function purgeStaleThings(): void
    {
        if (!$this->hasBuildersThatWillBuildLocally()) {
            return;
        }

        // find out what to purge
        $purgeConnections = $this->pickConnectionsToPurge();
        $purgeSnapshots = !Settings::getHasPurgedSnapshots();
        $removeOrphanedConfigs = !Settings::getHasRemovedOrphanedConfigs();

        if ((!count($purgeConnections)) && (!$purgeSnapshots) && (!$removeOrphanedConfigs)) {
            return;
        }

        // purge them
        if (!$this->performPurgeOfStaleThings(
            $purgeConnections,
            $purgeSnapshots,
            $removeOrphanedConfigs
        )) {
            return;
        }

        // record what was purged
        foreach ($purgeConnections as $connection) {
            Settings::setHasPurgedConnection($connection);
        }
        if ($purgeSnapshots) {
            Settings::setHasPurgedSnapshots();
        }
        if ($removeOrphanedConfigs) {
            Settings::setHasRemovedOrphanedConfigs();
        }
    }

    /**
     * Generate the list of connections that can be purged.
     *
     * @return string[]
     */
    private function pickConnectionsToPurge(): array
    {
        $connections = [];
        foreach ($this->databaseBuilders as $databaseBuilder) {

            $connection = $databaseBuilder->getConnection();

            if (!Settings::getHasPurgedConnection($connection)) {
                $connections[] = $connection;
            }
        }

        return $connections;
    }

    /**
     * Remove stale databases, snapshots and orphaned config files.
     *
     * @param string[] $purgeConnections      The connections to purge stale databases from.
     * @param boolean  $purgeSnapshots        Whether to purge stale snapshot files or not.
     * @param boolean  $removeOrphanedConfigs Whether to remove orphaned sharable config files or not.
     * @return boolean
     */
    abstract protected function performPurgeOfStaleThings(
        array $purgeConnections,
        bool $purgeSnapshots,
        bool $removeOrphanedConfigs
    ): bool;

    /**
     * Work out if stale things are allowed to be purged.
     *
     * @return boolean
     */
    abstract protected function canPurgeStaleThings(): bool;
}
