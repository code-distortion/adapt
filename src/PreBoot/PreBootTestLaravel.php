<?php

namespace CodeDistortion\Adapt\PreBoot;

use CodeDistortion\Adapt\Boot\BootTestInterface;
use CodeDistortion\Adapt\Boot\BootTestLaravel;
use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\DatabaseDefinition;
use CodeDistortion\Adapt\DI\Injectable\Interfaces\LogInterface;
use CodeDistortion\Adapt\DTO\PropBagDTO;
use CodeDistortion\Adapt\Exceptions\AdaptBootException;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use CodeDistortion\Adapt\Support\Exceptions;
use CodeDistortion\Adapt\Support\LaravelConfig;
use CodeDistortion\Adapt\Support\LaravelEnv;
use CodeDistortion\Adapt\Support\LaravelSupport;
use CodeDistortion\Adapt\Support\Settings;
use Laravel\Dusk\Browser;
use Throwable;

/**
 * Pre-Bootstrap for Laravel tests.
 *
 * Used so Laravel specific pre-booting code doesn't need to exist in the InitialiseAdapt trait.
 */
class PreBootTestLaravel
{
    /** @var BootTestInterface The object used to boot Adapt. */
    private BootTestInterface $adaptBootTestLaravel;

    /** @var string The class the current test is in. */
    private string $testClass;

    /** @var string The name of the current test. */
    private string $testName;

    /** @var PropBagDTO The properties specified in the test-class. */
    private PropBagDTO $propBag;

    /** @var LogInterface The logger to use. */
    private LogInterface $log;

    /** @var callable|null Callback that uses databaseInit(), to let the Test customise the database build process. */
    private $buildInitCallback;

    /** @var boolean Whether the current test is a browser test or not. */
    private bool $isBrowserTest;

    /** @var boolean Whether Pest is being used for this test or not. */
    private bool $usingPest;



    /**
     * Constructor.
     *
     * @param string        $testClass         The class the current test is in.
     * @param string        $testName          The name of the current test.
     * @param PropBagDTO    $propBag           The properties specified in the test-class.
     * @param callable|null $buildInitCallback The callback that calls the custom databaseInit() build process.
     * @param boolean       $isBrowserTest     Whether the current test is a browser test or not.
     * @param boolean       $usingPest         Whether Pest is being used for this test or not.
     */
    public function __construct(
        string $testClass,
        string $testName,
        PropBagDTO $propBag,
        ?callable $buildInitCallback,
        bool $isBrowserTest,
        bool $usingPest
    ) {
        $this->testClass = $testClass;
        $this->testName = $testName;
        $this->propBag = $propBag;
        $this->buildInitCallback = $buildInitCallback;
        $this->isBrowserTest = $isBrowserTest;
        $this->usingPest = $usingPest;
    }



    /**
     * Prepare and boot Adapt.
     *
     * @param callable $beforeRefreshingDatabase Callback to call the test's beforeRefreshingDatabase() method.
     * @param callable $afterRefreshingDatabase  Callback to call the test's afterRefreshingDatabase() method.
     * @param callable $unsetArtisan             Callback to clear Artisan from Laravel's App.
     * @return void
     * @throws Throwable When something goes wrong.
     */
    public function adaptSetUp(
        callable $beforeRefreshingDatabase,
        callable $afterRefreshingDatabase,
        callable $unsetArtisan
    ): void {

        // the logger needs Laravel's config settings to be built,
        // so it needs to be built here instead of earlier in the constructor
        // (as Laravel hadn't booted by that point)
        $this->log = LaravelSupport::newLaravelLogger();

        try {

            $this->prepareLaravelConfig();

            $beforeRefreshingDatabase(); // for compatability with Laravel's `RefreshDatabase`

            // Laravel connects to the database in some situations before reaching here (e.g. when using debug-bar).
            // when using scenarios, this is the wrong database to use
            // disconnect now to start a fresh
            LaravelSupport::disconnectFromConnectedDatabases($this->log);

            $this->adaptBootTestLaravel = $this->buildBootObject($this->log);
            $this->adaptBootTestLaravel->runBuildSteps();
            $this->adaptBootTestLaravel->runPostBuildSteps();

            $afterRefreshingDatabase(); // for compatability with Laravel's `RefreshDatabase`
            $unsetArtisan(); // unset Artisan, so as to not interfere with mocks inside tests

        } catch (Throwable $e) {
            Exceptions::logException($this->log, $e, true);
            throw $e;
        }
    }

    /**
     * Perform any clean-up / checking once the test has finished.
     *
     * @return void
     * @throws Throwable When something goes wrong.
     */
    public function adaptTearDown(): void
    {
        try {
            $this->adaptBootTestLaravel->runPostTestSteps();
        } catch (Throwable $e) {
            Exceptions::logException($this->log, $e, true);
            throw $e;
        } finally {
            $this->adaptBootTestLaravel->runPostTestCleanUp();
        }
    }



    /**
     * Update the Laravel's own config ready for the tests to run.
     *
     * @return void
     */
    private function prepareLaravelConfig(): void
    {
        $this->initLaravelDefaultConnection();
        $this->undoSessionDriverOverride();
        $this->remapLaravelDBConnections();
    }

    /**
     * Choose the database connection to use for this test, and set it as Laravel's default database connection.
     *
     * @return void
     * @throws AdaptConfigException When the desired default connection doesn't exist.
     */
    private function initLaravelDefaultConnection(): void
    {
        $connection = $this->propBag->adaptConfig('default_connection', 'defaultConnection');
        if (!$connection) {
            return;
        }

        $connection = is_string($connection) ? $connection : ''; // phpstan
        if (!config("database.connections.$connection")) {
            throw AdaptConfigException::invalidDefaultConnection($connection);
        }

        config(['database.default' => $connection]);
    }

    /**
     * Laravel sets the session driver to "array" during tests, but it doesn't when using "php artisan dusk". This way,
     * "loginAs" works because the session data can persist in the database.
     *
     * This method "un-does" Laravel's override of the session driver when browser testing, so that loginAs works when
     * running "php artisan test" or "./vendor/bin/phpunit", instead of having to run "php artisan dusk".
     *
     * @return void
     */
    private function undoSessionDriverOverride(): void
    {
        if (!$this->isBrowserTest) {
            return;
        }

        LaravelEnv::reloadEnv(LaravelSupport::basePath(Settings::LARAVEL_ENV_TESTING_FILE), ['APP_ENV' => 'testing']);

        $sessionConfig = LaravelConfig::readConfigFile('session');
        if (!$sessionConfig['driver']) {
            return;
        }

        config(['session.driver' => $sessionConfig['driver']]);
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
     * @return array<string, string>
     */
    private function parseRemapDBStrings(): array
    {
        return array_merge(
            $this->parseRemapDBString($this->propBag->adaptConfig('remap_connections'), null, true),
            $this->parseRemapDBString($this->propBag->prop('remapConnections', ''), null, false),
            $this->parseRemapDBString($this->propBag->adaptConfig('remap_connections'), true, true),
            $this->parseRemapDBString($this->propBag->prop('remapConnections', ''), true, false)
        );
    }

    /**
     * Break down the given remap-database string into its parts.
     *
     * @param string|null  $remapString  The string to use.
     * @param boolean|null $getImportant Return "important" or "unimportant" ones? null for any.
     * @param boolean      $isConfig     Is this string from a config setting? (otherwise it's a test-class prop).
     * @return array<string, string>
     * @throws AdaptConfigException When the string can't be interpreted.
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

                    $dest = (string) $matches[2];
                    $src = (string) $matches[3];

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
     * @param LogInterface $log The logger to use.
     * @return BootTestInterface
     */
    private function buildBootObject(LogInterface $log): BootTestInterface
    {
        return (new BootTestLaravel())
            ->log($log)
            ->testName($this->testClass . '::' . $this->testName)
            ->props($this->propBag)
            ->browserTestDetected($this->isBrowserTest)
            ->usingPest($this->usingPest)
            ->initCallback($this->buildInitCallback)
            ->ensureStorageDirsExist();
    }



    /**
     * Let the databaseInit(…) method generate a new DatabaseDefinition.
     *
     * @param string $connection The database connection to prepare.
     * @return DatabaseDefinition
     * @throws AdaptBootException When the database name isn't valid.
     */
    public function newDatabaseDefinitionFromConnection(string $connection): DatabaseDefinition
    {
        return $this->adaptBootTestLaravel->newDatabaseDefinitionFromConnection($connection);
    }

    /**
     * Let the databaseInit(…) method generate a new DatabaseBuilder.
     *
     * @deprecated
     * @param string $connection The database connection to prepare.
     * @return DatabaseBuilder
     * @throws AdaptBootException When the database name isn't valid.
     */
    public function newDatabaseBuilderFromConnection(string $connection): DatabaseBuilder
    {
        return $this->adaptBootTestLaravel->newDatabaseBuilderFromConnection($connection);
    }



    /**
     * Build the list of connections that Adapt has prepared, and their corresponding databases.
     *
     * @return array<string, string>
     */
    public function buildConnectionDBsList(): array
    {
        return $this->adaptBootTestLaravel->buildConnectionDBsList();
    }

    /**
     * Store the current config in the filesystem temporarily, and get the browsers refer to it in a cookie.
     *
     * @param Browser[]             $browsers      The browsers to update with the current config.
     * @param array<string, string> $connectionDBs The list of connections that have been prepared,
     *                                             and their corresponding databases from the framework.
     * @return void
     */
    public function haveBrowsersShareConfig(array $browsers, array $connectionDBs): void
    {
        $this->adaptBootTestLaravel->haveBrowsersShareConfig($browsers, $connectionDBs);
    }
}
