<?php

namespace CodeDistortion\Adapt\Initialise;

use CodeDistortion\Adapt\Boot\BootTestInterface;
use CodeDistortion\Adapt\Boot\BootTestLaravel;
use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\DTO\LaravelPropBagDTO;
use CodeDistortion\Adapt\DTO\PropBagDTO;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use CodeDistortion\Adapt\Support\Settings;
use Illuminate\Contracts\Container\BindingResolutionException;
use Laravel\Dusk\Browser;
use Laravel\Dusk\TestCase as DuskTestCase;
use PDOException;

/**
 * Allow Laravel tests to use Adapt.
 */
trait InitialiseLaravelAdapt
{
    /** @var PropBagDTO The properties specified in the test-class. */
    protected PropBagDTO $adaptPropBag;

    /** @var boolean Whether this set-up object's initialisation has been run yet or not. */
    private bool $adaptInitialised = false;

    /** @var BootTestInterface The object used to boot Adapt. */
    private BootTestInterface $adaptBootTestLaravel;



    /**
     * Initialise Adapt automatically.
     *
     * @before
     * @return void
     */
    protected function initialiseAdapt(): void
    {
        if ($this->adaptInitialised) {
            return;
        }
        $this->adaptInitialised = true;

        $this->afterApplicationCreated(function () {
            $this->adaptSetUp();

            $this->beforeApplicationDestroyed(function () {
                $this->adaptTearDown();
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

    }



    /**
     * Prepare and boot Adapt.
     *
     * @return void
     */
    private function adaptSetUp(): void
    {
        $this->adaptBuildPropBag();
        $this->adaptPrepareLaravelConfig();

        $this->adaptBootTestLaravel = $this->adaptBuildBootObject();
        $this->adaptBootTestLaravel->run();
    }

    /**
     * Build an array containing the relevant properties this class has.
     *
     * @return void
     */
    private function adaptBuildPropBag(): void
    {
        $propNames = [
            'buildDatabases',
            'reuseTestDBs',
            'scenarioTestDBs',
            'useSnapshotsWhenReusingDB',
            'useSnapshotsWhenNotReusingDB',
            'preMigrationImports',
            'migrations',
            'seeders',
            'remapConnections',
            'defaultConnection',
            'isBrowserTest',
        ];

        $this->adaptPropBag = new LaravelPropBagDTO();
        foreach ($propNames as $propName) {
            if (property_exists(static::class, $propName)) {
                $this->adaptPropBag->addProp($propName, $this->$propName);
            }
        }
    }

    /**
     * Update the Laravel config ready for the tests to run.
     *
     * @return void
     */
    private function adaptPrepareLaravelConfig(): void
    {
        $this->adaptInitLaravelDefaultConnection();
        $this->adaptRemapLaravelDBConnections();
    }

    /**
     * Choose the database connection to use for this test, and set it as Laravel's default database connection.
     *
     * @return void
     * @throws AdaptConfigException Thrown when the desired default connection doesn't exist.
     */
    private function adaptInitLaravelDefaultConnection(): void
    {
        if (!$this->adaptPropBag->hasProp('defaultConnection')) {
            return;
        }

        $connection = $this->adaptPropBag->prop('defaultConnection');
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
    private function adaptRemapLaravelDBConnections(): void
    {
        foreach ($this->adaptParseRemapDBStrings() as $dest => $src) {
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
    private function adaptParseRemapDBStrings(): array
    {
        return array_merge(
            $this->adaptParseRemapDBString($this->adaptPropBag->config('remap_connections'), null, true),
            $this->adaptParseRemapDBString($this->adaptPropBag->prop('remapConnections', ''), null, false),
            $this->adaptParseRemapDBString($this->adaptPropBag->config('remap_connections'), true, true),
            $this->adaptParseRemapDBString($this->adaptPropBag->prop('remapConnections', ''), true, false)
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
    private function adaptParseRemapDBString(?string $remapString, ?bool $getImportant, bool $isConfig): array
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
     * Build the boot-test object.
     *
     * @return BootTestInterface
     */
    private function adaptBuildBootObject(): BootTestInterface
    {
        return (new BootTestLaravel())
            ->testName(get_class($this) . '::' . $this->getName())
            ->props($this->adaptPropBag)
            ->browserTestDetected($this->adaptDetectBrowserTest())
            ->transactionClosure($this->adaptBuildTransactionClosure())
            ->initCallback($this->adaptBuildInitCallback())
            ->ensureStorageDirExists();
    }


    /**
     * Detect if this is running as a browser test.
     *
     * (When a browser test is detected, some caching functionality is turned off).
     *
     * @return boolean
     */
    private function adaptDetectBrowserTest(): bool
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
                            // a committed transaction is checked for before this code runs
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
     * Perform any clean-up / checking once the test has finished.
     *
     * @return void
     */
    private function adaptTearDown(): void
    {
        try {
            $this->adaptBootTestLaravel->checkForCommittedTransactions();
        } finally {
            $this->adaptBootTestLaravel->postTestCleanUp();
        }
    }



    /**
     * Let the databaseInit(…) method generate a new DatabaseBuilder.
     *
     * Create a new DatabaseBuilder object, and add it to the list to execute later.
     *
     * @param string $connection The database connection to prepare.
     * @return DatabaseBuilder
     * @throws AdaptConfigException Thrown when the connection doesn't exist.
     */
    protected function newBuilder(string $connection): DatabaseBuilder
    {
        return $this->adaptBootTestLaravel->newBuilder($connection);
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

        $this->adaptBootTestLaravel->getBrowsersToPassThroughCurrentConfig($allBrowsers);
    }



    /**
     * Fetch the http headers that lets Adapt share the connections it's built.
     *
     * @param boolean $includeKey Include the key in the value.
     * @return array<string, string>
     */
    public static function getShareConnectionsHeaders(bool $includeKey = false): array
    {
        $value = static::adaptConnectionsHeaderValue($includeKey);

        return $value
            ? [Settings::SHARE_CONNECTIONS_HTTP_HEADER_NAME => $value]
            : [];
    }

    /**
     * Get the http-header value used to pass connection-database details to a remote installation of Adapt.
     *
     * @param boolean $includeKey Include the key in the value.
     * @return string|null
     */
    private static function adaptConnectionsHeaderValue(bool $includeKey = false): ?string
    {
        $connectionDatabases = static::adaptGetFrameworkConnectionDatabases();

        if (is_null($connectionDatabases)) {
            return null;
        }

        $value = serialize($connectionDatabases);

        return $includeKey
            ? Settings::SHARE_CONNECTIONS_HTTP_HEADER_NAME . ": $value"
            : $value;
    }

    /**
     * Fetch the connection-databases list from Laravel.
     *
     * @return array|null
     */
    private static function adaptGetFrameworkConnectionDatabases(): ?array
    {
        try {
            return app(Settings::SHARE_CONNECTIONS_SINGLETON_NAME);
        } catch (BindingResolutionException $e) {
            return null;
        }
    }
}
