<?php

namespace CodeDistortion\Adapt\Initialise;

use CodeDistortion\Adapt\Boot\BootTestInterface;
use CodeDistortion\Adapt\Boot\BootTestLaravel;
use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\DTO\LaravelPropBagDTO;
use CodeDistortion\Adapt\DTO\PropBagDTO;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use CodeDistortion\Adapt\Support\Settings;
use Laravel\Dusk\Browser;
use Laravel\Dusk\TestCase as DuskTestCase;
use PDOException;

/**
 * Allow Laravel tests to use Adapt.
 */
trait InitialiseLaravelAdapt
{
    /** @var PropBagDTO The properties specified in the test-class. */
    protected PropBagDTO $propBag;

    /** @var boolean Whether this set-up object's initialisation has been run yet or not. */
    private bool $initialised = false;

    /** @var BootTestInterface The object used to boot Adapt. */
    private BootTestInterface $bootTestLaravel;



    /**
     * Initialise Adapt automatically.
     *
     * @before
     * @return void
     */
    protected function autoTriggerInitialisation(): void
    {
        $this->afterApplicationCreated(function () {
            $this->initialiseAdapt();
        });

        $this->afterApplicationCreated(function () {
            $this->beforeApplicationDestroyed(function () {
                // to be run after the transaction was rolled back
                $this->bootTestLaravel->checkForCommittedTransactions();
            });
        });
    }

    /**
     * Clean-up after a test has run.
     *
     * @after
     * @return void
     */
    protected function autoTriggerCleanUp(): void
    {
        $this->bootTestLaravel->postTestCleanUp();
    }



    /**
     * Prepare and boot Adapt.
     *
     * @return void
     */
    protected function initialiseAdapt(): void
    {
        if ($this->initialised) {
            return;
        }
        $this->initialised = true;

        $this->buildPropBag();
        $this->prepareLaravelConfig();

        $this->bootTestLaravel = $this->buildBootObject();
        $this->bootTestLaravel->run();
    }


    /**
     * Build an array containing the relevant properties this class has.
     *
     * @return void
     */
    private function buildPropBag(): void
    {
        $propNames = [
            'buildDatabases',
            'reuseTestDBs',
            'scenarioTestDBs',
            'snapshotsEnabled',
            'takeSnapshotAfterMigrations',
            'takeSnapshotAfterSeeders',
            'preMigrationImports',
            'migrations',
            'seeders',
            'remapConnections',
            'defaultConnection',
            'isBrowserTest',
        ];

        $this->propBag = new LaravelPropBagDTO();
        foreach ($propNames as $propName) {
            if (property_exists(static::class, $propName)) {
                $this->propBag->addProp($propName, $this->$propName);
            }
        }
    }

    /**
     * Update the Laravel config ready for the tests to run.
     *
     * @return void
     */
    private function prepareLaravelConfig(): void
    {
        $this->initLaravelDefaultConnection();
        $this->remapLaravelDBConnections();
    }

    /**
     * Choose the database connection to use for this test, and set it as Laravel's default database connection.
     *
     * @return void
     * @throws AdaptConfigException Thrown when the desired default connection doesn't exist.
     */
    private function initLaravelDefaultConnection(): void
    {
        if (!$this->propBag->hasProp('defaultConnection')) {
            return;
        }

        $connection = $this->propBag->prop('defaultConnection');
        if (!config("database.connections.$connection")) {
            throw AdaptConfigException::invalidDefaultConnection($connection);
        }

        config(['database.default' => $connection]);
    }

    /**
     * Remap the config database connections, overwriting ones with others.
     *
     * @return void
     */
    private function remapLaravelDBConnections(): void
    {
        foreach ($this->parseRemapDBStrings() as $dest => $src) {
            $replacement = config("database.connections.$src");
            config(["database.connections.$dest" => $replacement]);
        }
    }

    /**
     * Break down the remap-database strings and pick out the important ones.
     *
     * Gives priority the ones specified as props, but higher than that it gives priority to ones that start with "!".
     *
     * @return array
     */
    private function parseRemapDBStrings(): array
    {
        return array_merge(
            $this->parseRemapDBString($this->propBag->config('remap_connections'), null, true),
            $this->parseRemapDBString($this->propBag->prop('remapConnections', ''), null, false),
            $this->parseRemapDBString($this->propBag->config('remap_connections'), true, true),
            $this->parseRemapDBString($this->propBag->prop('remapConnections', ''), true, false)
        );
    }

    /**
     * Break down the given remap-database string into its parts.
     *
     * @param string|null  $remapString  The string to use.
     * @param boolean|null $getImportant Return "important" or "unimportant" ones? null for any.
     * @param boolean      $isConfig     Is this string from a config setting? (otherwise it's a test-class prop).
     * @return array
     * @throws AdaptConfigException Thrown when the string can't be interpreted.
     */
    private function parseRemapDBString(?string $remapString, ?bool $getImportant, bool $isConfig): array
    {
        if (is_null($remapString)) {
            return [];
        }

        $remap = [];
        foreach (explode(',', $remapString) as $mapping) {

            $orig = $mapping;
            $mapping = str_replace(' ', '', $mapping);
            if (!mb_strlen($mapping)) {
                continue;
            }

            if (preg_match('/(!?)([^<]+)<(.+)/', $mapping, $matches)) {

                $isImportant = (bool) $matches[1];
                if ((is_null($getImportant)) || ($getImportant === $isImportant)) {

                    $dest = $matches[2];
                    $src = $matches[3];

                    if (!config("database.connections.$dest")) {
                        throw AdaptConfigException::missingDestRemapConnection($dest, $isConfig);
                    }
                    if (!config("database.connections.$src")) {
                        throw AdaptConfigException::missingSrcRemapConnection($src, $isConfig);
                    }

                    $remap[$dest] = $src;
                }
            } else {
                throw AdaptConfigException::invalidConnectionRemapString($orig, $isConfig);
            }
        }
        return $remap;
    }

    /**
     * Build the test object and run it.
     *
     * @return BootTestInterface
     */
    private function buildBootObject(): BootTestInterface
    {
        return (new BootTestLaravel())
            ->testName(get_class($this) . ' - "' . $this->getName() . '"')
            ->props($this->propBag)
            ->browserTestDetected($this->detectBrowserTest())
            ->transactionClosure($this->adaptBuildTransactionClosure())
            ->initCallback($this->adaptBuildInitCallback());
    }


    /**
     * Detect if this is running as a browser test.
     *
     * (When a browser test is detected, some caching functionality is turned off).
     *
     * @return boolean
     */
    private function detectBrowserTest(): bool
    {
        return $this instanceof DuskTestCase;
    }


    /**
     * Start a database transaction on the given connection.
     *
     * (ADAPTED FROM Laravel Framework's RefreshDatabase::beginDatabaseTransaction()).
     *
     * @return callable
     */
    private function adaptBuildTransactionClosure(): callable
    {
        return function (string $conn) {

            $database = $this->app->make('db');
            $connection = $database->connection($conn);

            // this allows this code to run with older versions of Laravel versions
            $useEventDispatcher = (method_exists($connection, 'unsetEventDispatcher'));
            if ($useEventDispatcher) {
                $dispatcher = $connection->getEventDispatcher();
                $connection->unsetEventDispatcher();
                $connection->beginTransaction();
                $connection->setEventDispatcher($dispatcher);
            } else {
                $connection->beginTransaction();
            }

            $this->beforeApplicationDestroyed(
                function () use ($database, $conn, $useEventDispatcher) {
                    $connection = $database->connection($conn);
                    if ($useEventDispatcher) {
                        $dispatcher = $connection->getEventDispatcher();
                        $connection->unsetEventDispatcher();

                        try {
                            $connection->rollback();
                        } catch (PDOException $e) {
                            // act gracefully if the transaction was committed already
                        }

                        $connection->setEventDispatcher($dispatcher);
                        $connection->disconnect();
                    } else {
                        $connection->rollback();
                    }
                }
            );
        };
    }

    /**
     * Allow for a custom build process. Build a closure to be called when initialising the DatabaseBuilder/s.
     *
     * @return callable|null
     */
    private function adaptBuildInitCallback(): ?callable
    {
        if (!method_exists(static::class, 'databaseInit')) {
            return null;
        }
        return function (DatabaseBuilder $builder) {
            $this->databaseInit($builder);
        };
    }

    /**
     * Have the Browsers pass the current (test) config to the server when they make requests.
     *
     * @deprecated
     * @see shareConfig
     * @param Browser               $browser     The browser to update with the current config.
     * @param Browser[]|Browser[][] ...$browsers Any additional browsers to update with the current config.
     * @return void
     */
    public function useCurrentConfig(Browser $browser, Browser ...$browsers): void
    {
        call_user_func_array([$this, 'shareConfig'], func_get_args());
    }

    /**
     * Have the Browsers pass the current (test) config to the server when they make requests.
     *
     * @param Browser               $browser     The browser to update with the current config.
     * @param Browser[]|Browser[][] ...$browsers Any additional browsers to update with the current config.
     * @return void
     */
    public function shareConfig(Browser $browser, Browser ...$browsers): void
    {
        $allBrowsers = [];
        $browsers = array_merge([$browser], $browsers);
        foreach ($browsers as $browser) {
            $allBrowsers = array_merge(
                $allBrowsers,
                is_array($browser) ? $browser : [$browser]
            );
        }

        $this->bootTestLaravel->getBrowsersToPassThroughCurrentConfig($allBrowsers);
    }
}
