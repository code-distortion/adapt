<?php

namespace CodeDistortion\Adapt\Initialise;

use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\DTO\LaravelPropBagDTO;
use CodeDistortion\Adapt\DTO\RemoteShareDTO;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use CodeDistortion\Adapt\Exceptions\AdaptDeprecatedFeatureException;
use CodeDistortion\Adapt\LaravelAdapt;
use CodeDistortion\Adapt\PreBoot\PreBootTestLaravel;
use CodeDistortion\Adapt\Support\LaravelSupport;
use CodeDistortion\Adapt\Support\Settings;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Laravel\Dusk\Browser;
use Laravel\Dusk\TestCase as DuskTestCase;
use PDOException;

/**
 * Allow Laravel tests to use Adapt.
 *
 * @mixin LaravelTestCase
 */
trait InitialiseLaravelAdapt
{
    /** @var PreBootTestLaravel|null Used so Laravel pre-booting code doesn't exist in InitialiseLaravelAdapt. */
    private ?PreBootTestLaravel $adaptPreBootTestLaravel = null;



    /**
     * Helper method to make it easier to initialise Adapt.
     *
     * @param LaravelTestCase $test
     * @return void
     */
    public static function initialiseAdaptIfNeeded(LaravelTestCase $test): void
    {
        if (!in_array(LaravelAdapt::class, class_uses_recursive(get_class($test)))) {
            return;
        }

        /** @var InitialiseLaravelAdapt $test */
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
     */
    public function initialiseAdapt(): void
    {
        // only initialise once
        if ($this->adaptPreBootTestLaravel) {
            return;
        }



        // build a PropBagDTO containing the relevant properties this class has.
        $propNames = [
            'buildDatabases',
            'reuseTestDBs',
            'scenarioTestDBs',
            'useSnapshotsWhenReusingDB',
            'useSnapshotsWhenNotReusingDB',
            'preMigrationImports',
            'migrations',
            'seeders',
            'seed',
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



        // start a database transaction on the given connection
        // (ADAPTED FROM Laravel Framework's RefreshDatabase::beginDatabaseTransaction())
        $buildTransactionClosure = function (string $conn) {

            /** @var $this LaravelTestCase */
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
                            // act gracefully if the transaction was committed already? - no
                        }

                        $connection->setEventDispatcher($dispatcher);
                        $connection->disconnect();
                    } else {
                        $connection->rollback();
                    }
                }
            );
        };



        // allow for a custom build process via databaseInit(…)
        // build a closure to be called when initialising the DatabaseBuilder/s
        $buildInitCallback = null;
        if (method_exists(static::class, Settings::CUSTOM_BUILD_METHOD)) {
            $buildInitCallback = function (DatabaseBuilder $builder) {
                $initMethod = Settings::CUSTOM_BUILD_METHOD;
                $this->$initMethod($builder);
            };
        }



        // create a new pre-boot object to perform the boot work
        // allowing this trait to not contain so many things
        $this->adaptPreBootTestLaravel = new PreBootTestLaravel(
            get_class($this),
            $this->getName(),
            $propBag,
            $buildTransactionClosure,
            $buildInitCallback,
            $this instanceof DuskTestCase
        );



        // tell the test to run the set-up and tear-down methods at the right time
        /** @var $this LaravelTestCase */
        $this->afterApplicationCreated(function () {
            $this->adaptPreBootTestLaravel->adaptSetUp();

            $this->beforeApplicationDestroyed(function () {
                $this->adaptPreBootTestLaravel->adaptTearDown();
            });
        });
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
        return $this->adaptPreBootTestLaravel->newBuilder($connection);
    }





    /**
     * Have the Browsers pass the current (test) config to the server when they make requests.
     *
     * @deprecated
     * @see shareConfig
     * @param Browser               $browser     The browser to update with the current config.
     * @param Browser[]|Browser[][] ...$browsers Any additional browsers to update with the current config.
     * @return void
     * @throws AdaptDeprecatedFeatureException Thrown because this method has been deprecated.
     */
    public function useCurrentConfig(Browser $browser, Browser ...$browsers): void
    {
        throw AdaptDeprecatedFeatureException::deprecatedFeatureUsed('useCurrentConfig', 'shareConfig');
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
     * Fetch the http headers that lets Adapt share the connections it's prepared.
     *
     * @param boolean $includeKey Include the key in the value.
     * @return array<string, string>
     */
    public static function getShareConnectionsHeaders(bool $includeKey = false): array
    {
        // fetch the connection-databases list from Laravel
        $connectionDBs = LaravelSupport::readPreparedConnectionDBsFromFramework() ?? [];

        if (!count($connectionDBs)) {
            return [];
        }

        $remoteShareDTO = (new RemoteShareDTO())
            ->tempConfigFile(null)
            ->connectionDBs($connectionDBs);

        $value = $includeKey
            ? Settings::REMOTE_SHARE_KEY . ": {$remoteShareDTO->buildPayload()}"
            : $remoteShareDTO->buildPayload();

        return [Settings::REMOTE_SHARE_KEY => $value];
    }
}
