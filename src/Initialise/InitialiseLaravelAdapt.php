<?php

namespace CodeDistortion\Adapt\Initialise;

use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\DTO\LaravelPropBagDTO;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use CodeDistortion\Adapt\Exceptions\AdaptDeprecatedFeatureException;
use CodeDistortion\Adapt\PreBoot\PreBootTestLaravel;
use CodeDistortion\Adapt\Support\Settings;
use Illuminate\Contracts\Container\BindingResolutionException;
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
     * Initialise Adapt automatically.
     *
     * NOTE: This method contains code that would normally be refactored into other methods.
     *       This is so the namespace of the Test class isn't muddied up with more methods than necessary.
     *
     * @before
     * @return void
     */
    protected function initialiseAdapt(): void
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
            'remapConnections',
            'defaultConnection',
            'isBrowserTest',
        ];
        $propBag = new LaravelPropBagDTO();
        foreach ($propNames as $propName) {
            if (property_exists(static::class, $propName)) {
                $propBag->addProp($propName, $this->$propName);
            }
        }



        // start a database transaction on the given connection.
        //
        // (ADAPTED FROM Laravel Framework's RefreshDatabase::beginDatabaseTransaction()).
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



        // allow for a custom build process. Build a closure to be called when initialising the DatabaseBuilder/s
        $buildInitCallback = null;
        if (method_exists(static::class, 'databaseInit')) {
            $buildInitCallback = function (DatabaseBuilder $builder) {
                $this->databaseInit($builder);
            };
        }



        // create a new pre-boot object to perform the boot work
        // allowing this trait to not contain so many things
        $this->adaptPreBootTestLaravel = new PreBootTestLaravel(
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
        return $this->adaptPreBootTestLaravel->adaptBootTestLaravel->newBuilder($connection);
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
        $allBrowsers = [];
        $browsers = array_merge([$browser], $browsers);
        foreach ($browsers as $browser) {
            $allBrowsers = array_merge(
                $allBrowsers,
                is_array($browser) ? $browser : [$browser]
            );
        }

        $this->adaptPreBootTestLaravel->adaptBootTestLaravel->getBrowsersToPassThroughCurrentConfig($allBrowsers);
    }





    /**
     * Fetch the http headers that lets Adapt share the connections it's built.
     *
     * NOTE: This method contains code that would normally be refactored into other methods.
     *       This is so the namespace of the Test class isn't muddied up with more methods than necessary.
     *
     * @param boolean $includeKey Include the key in the value.
     * @return array<string, string>
     */
    public static function getShareConnectionsHeaders(bool $includeKey = false): array
    {
        // fetch the connection-databases list from Laravel
        /** @var array|null $connectionDatabases */
        try {
            $connectionDatabases = app(Settings::SHARE_CONNECTIONS_SINGLETON_NAME);
        } catch (BindingResolutionException $e) {
            $connectionDatabases = null;
        }

        // get the http-header value used to pass connection-database details to a remote installation of Adapt.
        /** @var string|null $value */
        $value = null;
        if (!is_null($connectionDatabases)) {
            $value = serialize($connectionDatabases);
            $value = $includeKey
                ? Settings::SHARE_CONNECTIONS_HTTP_HEADER_NAME . ": $value"
                : $value;
        }

        return $value
            ? [Settings::SHARE_CONNECTIONS_HTTP_HEADER_NAME => $value]
            : [];
    }
}
