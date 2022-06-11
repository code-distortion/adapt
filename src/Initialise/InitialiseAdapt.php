<?php

namespace CodeDistortion\Adapt\Initialise;

use CodeDistortion\Adapt\AdaptDatabase;
use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\DTO\LaravelPropBagDTO;
use CodeDistortion\Adapt\DTO\RemoteShareDTO;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use CodeDistortion\Adapt\Exceptions\AdaptDeprecatedFeatureException;
use CodeDistortion\Adapt\LaravelAdapt;
use CodeDistortion\Adapt\PreBoot\PreBootTestLaravel;
use CodeDistortion\Adapt\Support\LaravelSupport;
use CodeDistortion\Adapt\Support\PHPSupport;
use CodeDistortion\Adapt\Support\Settings;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Laravel\Dusk\Browser;
use Laravel\Dusk\TestCase as DuskTestCase;

/**
 * Allow Laravel tests to use Adapt.
 *
 * @mixin LaravelTestCase
 */
trait InitialiseAdapt
{
    /** @var PreBootTestLaravel|null Used so Laravel pre-booting code doesn't exist in InitialiseLaravelAdapt. */
    private ?PreBootTestLaravel $adaptPreBootTestLaravel = null;



    /**
     * Helper method to make it easier to initialise Adapt.
     *
     * @param LaravelTestCase $test The current test.
     * @return void
     */
    public static function initialiseAdaptIfNeeded(LaravelTestCase $test): void
    {
        $found = false;
        $traits = [LaravelAdapt::class, AdaptDatabase::class];
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
            'reuseTestDBs', // @deprecated
            'reuseTransaction',
            'reuseJournal',
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



        // allow for a custom build process via this test class's databaseInit(…) method
        // build a closure to be called when initialising the DatabaseBuilder/s
        $buildInitCallback = null;
        if (method_exists(static::class, Settings::LARAVEL_CUSTOM_BUILD_METHOD)) {
            $buildInitCallback = function (DatabaseBuilder $builder) {
                $initMethod = Settings::LARAVEL_CUSTOM_BUILD_METHOD;
                $this->$initMethod($builder);
            };
        }



        // create a new pre-boot object to perform the boot work
        // allowing this trait to not contain so many things
        $this->adaptPreBootTestLaravel = new PreBootTestLaravel(
            get_class($this),
            $this->getName(),
            $propBag,
            $buildInitCallback,
            $this instanceof DuskTestCase
        );



        // unset Artisan, to not interfere with mocks inside tests
        $unsetArtisan = function () {
            /** @var \Illuminate\Contracts\Console\Kernel $kernel */
            $kernel = $this->app[Kernel::class];
            method_exists($kernel, 'setArtisan')
                ? $kernel->setArtisan(null)
                : PHPSupport::updatePrivateProperty($kernel, 'artisan', null);
        };



        // tell the test to run the set-up and tear-down methods at the right time
        /** @var $this LaravelTestCase */
        $this->afterApplicationCreated(function () use ($unsetArtisan) {

            $this->adaptPreBootTestLaravel->adaptSetUp();
            $unsetArtisan();

            $this->beforeApplicationDestroyed(fn() => $this->adaptPreBootTestLaravel->adaptTearDown());
        });
    }





    /**
     * Let the databaseInit(…) method generate a new DatabaseBuilder.
     *
     * Create a new DatabaseBuilder object, and add it to the list to execute later.
     *
     * @param string $connection The database connection to prepare.
     * @return DatabaseBuilder
     * @throws AdaptConfigException When the connection doesn't exist.
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
     * @throws AdaptDeprecatedFeatureException Because this method has been deprecated.
     */
    public function useCurrentConfig(Browser $browser, Browser ...$browsers): void
    {
        throw AdaptDeprecatedFeatureException::deprecatedFeatureUsed('useCurrentConfig', 'shareConfig');
    }

    /**
     * Have the Browsers pass the current (test) config to the server when they make requests.
     *
     * @deprecated
     * @param Browser               $browser     The browser to update with the current config.
     * @param Browser[]|Browser[][] ...$browsers Any additional browsers to update with the current config.
     * @return void
     */
    public function shareConfig(Browser $browser, Browser ...$browsers): void
    {
        call_user_func_array([$this, 'useAdapt'], func_get_args());
    }

    /**
     * Have the Browsers pass the current (test) config to the server when they make requests.
     *
     * @param Browser               $browser     The browser to update with the current config.
     * @param Browser[]|Browser[][] ...$browsers Any additional browsers to update with the current config.
     * @return void
     */
    public function useAdapt(Browser $browser, Browser ...$browsers): void
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
