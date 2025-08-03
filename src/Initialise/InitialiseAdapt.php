<?php

namespace CodeDistortion\Adapt\Initialise;

use CodeDistortion\Adapt\AdaptDatabase;
use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\DatabaseDefinition;
use CodeDistortion\Adapt\DTO\LaravelPropBagDTO;
use CodeDistortion\Adapt\DTO\RemoteShareDTO;
use CodeDistortion\Adapt\Exceptions\AdaptBootException;
use CodeDistortion\Adapt\Exceptions\AdaptDeprecatedFeatureException;
use CodeDistortion\Adapt\LaravelAdapt;
use CodeDistortion\Adapt\PreBoot\PreBootTestLaravel;
use CodeDistortion\Adapt\Support\LaravelSupport;
use CodeDistortion\Adapt\Support\PHPSupport;
use CodeDistortion\Adapt\Support\Settings;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Laravel\Dusk\Browser;
use Laravel\Dusk\TestCase as DuskTestCase;
use PHPUnit\Framework\Attributes\Before;
use ReflectionException;

/**
 * Allow Laravel tests to use Adapt.
 *
 * @mixin LaravelTestCase
 */
trait InitialiseAdapt
{
    /** @var PreBootTestLaravel|null Used so Laravel pre-booting code doesn't exist in InitialiseAdapt. */
    private $adaptPreBootTestLaravel;



    /**
     * Helper method to make it easier to initialise Adapt.
     *
     * @param LaravelTestCase $test The current test.
     * @return void
     */
    public static function initialiseAdaptIfNeeded($test)
    {
        $found = false;
        $traits = [AdaptDatabase::class, LaravelAdapt::class];
        foreach ($traits as $trait) {
            if (in_array($trait, class_uses_recursive(get_class($test)))) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            return;
        }

        /** @var InitialiseAdapt $test */
        $test->initialiseAdapt();
    }

    /**
     * Initialise Adapt automatically.
     *
     * NOTE: This method contains code that would normally be refactored into other methods.
     *       This is so the namespace of the user-land Test class isn't muddied up with more methods than necessary.
     *
     * @before
     * @return void
     * @throws AdaptBootException When Laravel's database-building traits are also present.
     */
    #[Before]
    public function initialiseAdapt()
    {
        // check to make sure Adapt only initialises the database/s once
        // a class property can't be used, because when running the tests with --repeat, the test object continues to
        // exist between iterations, but Laravel's app has been reset (and the database needs to be initialised again).

        // use Laravel's service container to record when this has been run
        $alreadyInitialised = false;
        try {
            $alreadyInitialised = app(Settings::LARAVEL_ALREADY_INITIALISED_SERVICE_CONTAINER_NAME);
        } catch (BindingResolutionException $exception) {
            app()->bind(Settings::LARAVEL_ALREADY_INITIALISED_SERVICE_CONTAINER_NAME, function () {
                return true;
            });
        } catch (ReflectionException $exception) { // < Laravel 5.8
            app()->bind(Settings::LARAVEL_ALREADY_INITIALISED_SERVICE_CONTAINER_NAME, function () {
                return true;
            });
        }

        // only initialise once
        if ($alreadyInitialised) {
            return;
        }



        // check to make sure Laravel's RefreshDatabase, DatabaseTransactions and DatabaseMigrations
        // traits aren't also being used
        // note: class_uses_recursive is a part of Laravel
        foreach ([RefreshDatabase::class, DatabaseTransactions::class, DatabaseMigrations::class] as $trait) {
            if (in_array($trait, class_uses_recursive(get_class($this)), true)) {
                throw AdaptBootException::laravelDatabaseTraitDetected($trait);
            }
        }



        // build a PropBagDTO containing the relevant properties this class has.
        $propNames = [
            'buildDatabases',
            'reuseTestDBs', // @deprecated
            'reuseTransaction', // @deprecated
            'transactions',
            'reuseJournal', // @deprecated
            'journals',
            'scenarioTestDBs', // @deprecated
            'scenarios',
            'useSnapshotsWhenReusingDB', // @deprecated
            'useSnapshotsWhenNotReusingDB', // @deprecated
            'snapshots',
            'preMigrationImports', // @deprecated
            'initialImports',
            'migrations',
            'seeders',
            'seeder', // for compatability with Laravel
            'seed', // for compatability with Laravel
            'remapConnections',
            'defaultConnection',
            'isBrowserTest',
            'remoteBuildUrl',
        ];
        $propBag = new LaravelPropBagDTO();
        foreach ($propNames as $propName) {
            if (property_exists(static::class, $propName)) {
                $propBag->addProp($propName, $this->$propName);
            }
        }

        // allow for a custom build process via this test class's databaseInit(…) method
        // build a closure to be called when initialising the DatabaseBuilder/s
        $buildInitCallback = null;
        $initMethod = Settings::LARAVEL_CUSTOM_BUILD_METHOD;
        if (method_exists(static::class, $initMethod)) {

            $parameterClass = PHPSupport::getClassMethodFirstParameterType(__CLASS__, $initMethod);

            $buildInitCallback = $parameterClass == DatabaseBuilder::class
                // @deprecated
                ? function (DatabaseBuilder $database) use ($initMethod) {
                    $this->$initMethod($database);
                }
                : function (DatabaseDefinition $database) use ($initMethod) {
                    $this->$initMethod($database);
                };
        }

        // detect if Pest is being used
        $usingPest = false;
        foreach (class_uses($this) as $trait) {
            if (mb_substr($trait, 0, mb_strlen('Pest\\')) == 'Pest\\') {
                $usingPest = true;
                break;
            }
        }



        // create a new pre-boot object to perform the boot work
        // allowing this trait to not contain so many things
        $name = method_exists($this, 'name')
            ? $this->name() // new in Laravel 10
            : $this->getName();

        $this->adaptPreBootTestLaravel = new PreBootTestLaravel(
            get_class($this),
            $name,
            $propBag,
            $buildInitCallback,
            $this instanceof DuskTestCase,
            $usingPest
        );

        // callback - for compatability with Laravel's `RefreshDatabase`
        $beforeRefreshingDatabase = function () {
            if (method_exists($this, 'beforeRefreshingDatabase')) {
                $this->beforeRefreshingDatabase();
            }
        };

        // callback - for compatability with Laravel's `RefreshDatabase`
        $afterRefreshingDatabase = function () {
            if (method_exists($this, 'afterRefreshingDatabase')) {
                $this->afterRefreshingDatabase();
            }
        };

        // unset Artisan, so as to not interfere with mocks inside tests
        $unsetArtisan = function () {
            /** @var \Illuminate\Contracts\Console\Kernel $kernel */
            $kernel = $this->app[Kernel::class];
            method_exists($kernel, 'setArtisan')
                ? $kernel->setArtisan(null)
                : PHPSupport::updatePrivateProperty($kernel, 'artisan', null); // Laravel <= 5.2
        };



        // tell the test to run the set-up and tear-down methods at the right time
        /** @var $this LaravelTestCase */
        $this->afterApplicationCreated(
            function () use ($beforeRefreshingDatabase, $afterRefreshingDatabase, $unsetArtisan) {

                $this->adaptPreBootTestLaravel->adaptSetUp(
                    $beforeRefreshingDatabase,
                    $afterRefreshingDatabase,
                    $unsetArtisan
                );

                $this->beforeApplicationDestroyed(
                    function () {
                        return $this->adaptPreBootTestLaravel->adaptTearDown();
                    }
                );
            }
        );
    }



    /**
     * Let the databaseInit(…) method generate a new DatabaseDefinition.
     *
     * @param string $connection The database connection to prepare.
     * @return DatabaseDefinition
     * @throws AdaptBootException When the database name isn't valid.
     */
    protected function prepareConnection($connection): DatabaseDefinition
    {
        return $this->adaptPreBootTestLaravel->newDatabaseDefinitionFromConnection($connection);
    }

    /**
     * Let the databaseInit(…) method generate a new DatabaseBuilder.
     *
     * @deprecated
     * @param string $connection The database connection to prepare.
     * @return DatabaseBuilder
     * @throws AdaptBootException When the database name isn't valid.
     */
    protected function newBuilder($connection): DatabaseBuilder
    {
        return $this->adaptPreBootTestLaravel->newDatabaseBuilderFromConnection($connection);
    }



    /**
     * Have the Browsers pass the current (test) config to the server when they make requests.
     *
     * @deprecated
     * @see shareConfig
     * @param \Laravel\Dusk\Browser $browser     The browser to update with the current config.
     * @param \Laravel\Dusk\Browser ...$browsers Any additional browsers to update with the current config.
     * @return void
     * @throws AdaptDeprecatedFeatureException Because this method has been deprecated.
     */
    public function useCurrentConfig($browser, ...$browsers)
    {
        throw AdaptDeprecatedFeatureException::deprecatedFeatureUsed('useCurrentConfig', 'shareConfig');
    }

    /**
     * Have the Browsers pass the current (test) config to the server when they make requests.
     *
     * @deprecated
     * @param \Laravel\Dusk\Browser $browser     The browser to update with the current config.
     * @param \Laravel\Dusk\Browser ...$browsers Any additional browsers to update with the current config.
     * @return void
     */
    public function shareConfig($browser, ...$browsers)
    {
        call_user_func_array([$this, 'useAdapt'], func_get_args());
    }

    /**
     * Have the Browsers pass the current (test) config to the server when they make requests.
     *
     * @param \Laravel\Dusk\Browser $browser     The browser to update with the current config.
     * @param \Laravel\Dusk\Browser ...$browsers Any additional browsers to update with the current config.
     * @return void
     */
    public function useAdapt($browser, ...$browsers)
    {
        // normalise the list of browsers
        $allBrowsers = [];
        $browsers = array_merge([$browser], $browsers);
        foreach ($browsers as $browser) {
            $allBrowsers = array_merge(
                $allBrowsers,
                is_array($browser) ? $browser : [$browser]
            );
        }

        $connectionDBs = $this->adaptPreBootTestLaravel->buildConnectionDBsList();

        $this->adaptPreBootTestLaravel->haveBrowsersShareConfig($allBrowsers, $connectionDBs);
    }



    /**
     * Fetch the http headers that lets Adapt share the config and connections it's prepared.
     *
     * @deprecated
     * @param boolean $includeKey Include the key in the value.
     * @return array<string, string>
     */
    public static function getShareConnectionsHeaders($includeKey = false): array
    {
        // fetch the connection-databases list from Laravel
        $connectionDBs = LaravelSupport::readPreparedConnectionDBsFromFramework() ?? [];

        if (!count($connectionDBs)) {
            return [];
        }

        $remoteShareDTO = (new RemoteShareDTO())
            ->sharableConfigFile(null)
            ->connectionDBs($connectionDBs);

        $value = $includeKey
            ? Settings::REMOTE_SHARE_KEY . ": {$remoteShareDTO->buildPayload()}"
            : $remoteShareDTO->buildPayload();

        return [Settings::REMOTE_SHARE_KEY => $value];
    }

    /**
     * Fetch the http headers that lets Adapt share the config and connections it's prepared.
     *
     * @param boolean $includeKey Include the key in the value.
     * @return array<string, string>
     */
    public static function getShareHeaders($includeKey = false): array
    {
        // fetch the connection-databases list from Laravel
        $connectionDBs = LaravelSupport::readPreparedConnectionDBsFromFramework() ?? [];

        if (!count($connectionDBs)) {
            return [];
        }

        $remoteShareDTO = (new RemoteShareDTO())
            ->sharableConfigFile(null)
            ->connectionDBs($connectionDBs);

        $value = $includeKey
            ? Settings::REMOTE_SHARE_KEY . ": {$remoteShareDTO->buildPayload()}"
            : $remoteShareDTO->buildPayload();

        return [Settings::REMOTE_SHARE_KEY => $value];
    }
}
