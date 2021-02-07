# Adapt - A Database Preparation Tool

[![Latest Version on Packagist](https://img.shields.io/packagist/v/code-distortion/adapt.svg?style=flat-square)](https://packagist.org/packages/code-distortion/adapt)
![PHP from Packagist](https://img.shields.io/packagist/php-v/code-distortion/adapt?style=flat-square)
![Laravel](https://img.shields.io/badge/laravel-5.1+%2C%206%2C%20%207%20%26%208-blue?style=flat-square)
[![GitHub Workflow Status](https://img.shields.io/github/workflow/status/code-distortion/adapt/run-tests?label=tests&style=flat-square)](https://github.com/code-distortion/adapt/actions)
[![Buy The World a Tree](https://img.shields.io/badge/treeware-%F0%9F%8C%B3-lightgreen?style=flat-square)](https://plant.treeware.earth/code-distortion/adapt)
[![Contributor Covenant](https://img.shields.io/badge/contributor%20covenant-v2.0%20adopted-ff69b4.svg?style=flat-square)](CODE_OF_CONDUCT.md)



## Introduction

***code-distortion/adapt*** is a [Laravel](https://github.com/laravel/laravel) package that builds databases for your tests, improving their speed.

> The article [Adapt - A Database Preparation Tool](https://www.code-distortion.net/articles/adapt-a-database-preparation-tool/) provides an introduction to this package.

> The online-book [Test Database Speed Improvements - A Detailed Look](https://www.code-distortion.net/articles/test-database-speed-improvements-a-detailed-look/) explains the concepts this package uses in detail.



## Table of Contents

* [Introduction](#introduction)
* [Compatibility](#compatibility)
* [Installation](#installation)
    * [Config](#config)
* [Usage](#usage)
    * [Quick-Start](#quick-start)
    * [PHPUnit Usage](#phpunit-usage)
    * [PEST Usage](#pest-usage)
    * [Dusk Browser Test Usage](#dusk-browser-test-usage)
    * [Running your tests](#running-your-tests)
    * [Usage Notes](#usage-notes)
    * [Artisan Console Commands](#artisan-console-commands)
* [Customisation](#customisation)
    * [PHPUnit Customisation](#phpunit-customisation)
    * [PEST Customisation](#pest-customisation)
* [Caching Mechanisms](#caching-mechanisms)
    * [Database Snapshots](#database-snapshots)
    * [Re-using Databases *Within* A Test-Run](#re-using-databases-within-a-test-run)
    * [Re-using Databases *Between* Test-Runs](#re-using-databases-between-test-runs)
    * [Creation of "Scenario" Databases](#creation-of-scenario-databases)
* [Cache Invalidation](#cache-invalidation)
* [Testing Scenarios and Techniques](#testing-scenarios-and-techniques)
    * [Using the "default" database connection…](#using-the-default-database-connection)
    * [Building databases for non "default" connections, and using more than one database connection…](#building-databases-for-non-default-connections-and-using-more-than-one-database-connection)
    * [Using a different type of database…](#using-a-different-type-of-database)
    * [Seeding during testing…](#seeding-during-testing)
    * [Dusk browser tests…](#dusk-browser-tests)
    * [Running tests in parallel with ParaTest…](#running-tests-in-parallel-with-paratest)
    * [Testing code that itself uses transactions…](#testing-code-that-itself-uses-transactions)
    * [Importing custom database dump files…](#importing-custom-database-dump-files)
* [Testing](#testing)
* [Changelog](#changelog)
    * [SemVer](#semver)
* [Treeware](#treeware)
* [Contributing](#contributing)
    * [Code of Conduct](#code-of-conduct)
    * [Security](#security)
* [Credits](#credits)
* [License](#license)



## Compatibility

Adapt is compatible with [PHPUnit](https://github.com/sebastianbergmann/phpunit) and [PEST](https://pestphp.com/) tests in **Laravel 5.1 - 8** and **PHP 7.0 - 8.0** on **Linux** and **MacOS**.

It works in conjunction with [ParaTest](https://github.com/paratestphp/paratest) and [Dusk](https://laravel.com/docs/8.x/dusk).

The currently supported databases are: **MySQL**, **SQLite** and **SQLite :memory:**.



## Installation

Install the package via composer:

``` bash
composer require code-distortion/adapt --dev
```

Adapt integrates with Laravel 5.5+ automatically.

<details><summary>(Click here for Laravel <= 5.4)</summary>
<p>

If you're using an old version of Laravel, you'll need to register the service provider yourself.

Add the following to `app/Providers/AppServiceProvider.php` to enable it:

``` php
<?php
// app/Providers/AppServiceProvider.php

namespace App\Providers;
…
use CodeDistortion\Adapt\AdaptLaravelServiceProvider; // **** add this ****

class AppServiceProvider extends ServiceProvider
{
    …
    public function register()
    {
        // **** add this to the register() method ****
        if ($this->app->environment(['local', 'testing'])) {
            $this->app->register(AdaptLaravelServiceProvider::class);
        }
        …
    }
}
```
</p>
</details>



### Config

You can alter the default settings by publishing the `config/code_distortion.adapt.php` config file and updating it:

``` bash
php artisan vendor:publish --provider="CodeDistortion\Adapt\AdaptLaravelServiceProvider" --tag="config"
```

> ***Note***: If you'd like to add custom environment values, put them in your `.env.testing` file if you use one (rather than `.env`).



## Usage

### Quick-Start

- Step 1 - Replace the `RefreshDatabase`, `DatabaseTransactions` and `DatabaseMigrations` traits in your test classes with `LaravelAdapt`.
- Step 2 - Run your tests like normal.
  ``` bash
  # you can include browser tests by running
  # all tests in the "tests" directory
  php artisan test --parallel tests
  ```



### PHPUnit Usage

Use the `LaravelAdapt` trait in your test-classes instead of `RefreshDatabase`, `DatabaseTransactions` or `DatabaseMigrations`.

Then just run your tests like normal. If you like you can [customise Adapt's settings](#customisation) on a per-test basis.

``` php
<?php
// tests/Feature/MyFeatureTest.php

namespace Tests\Feature;

use CodeDistortion\Adapt\LaravelAdapt; // **** add this ****
//use Illuminate\Foundation\Testing\DatabaseMigrations;   // not needed
//use Illuminate\Foundation\Testing\DatabaseTransactions; // not needed
//use Illuminate\Foundation\Testing\RefreshDatabase;      // not needed
use Tests\TestCase;

class MyFeatureTest extends TestCase
{
    use LaravelAdapt; // **** add this ****
//  use DatabaseMigrations;   // not needed
//  use DatabaseTransactions; // not needed
//  use RefreshDatabase;      // not needed
    …
}
```

<details><summary>(Click here if you're using an old version of PHPUnit (< ~v6) and are having problems)</summary>
<p>

If you're using an old version of PHPUnit and want to populate database data in your setUp() method, you'll run in to problems because PHPUnit used to initialise things like Adapt [*after* the setUp() method was called](https://github.com/sebastianbergmann/phpunit/issues/1616).

- To solve this, either put the code to populate the database into a seeder and have Adapt run that.

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;*or*

- Add this to your base TestCase `setUp()` method so Adapt is booted:

``` php
<?php
// tests/TestCase.php

namespace Tests;

use CodeDistortion\Adapt\LaravelAdapt; // **** add this ****
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    // **** add the setUp() method if needed ****
    protected function setUp(): void
    {
        parent::setUp();

        // **** add this so LaravelAdapt is booted ****
        if (in_array(LaravelAdapt::class, class_uses_recursive(static::class))) {
            $this->initialiseAdapt();
        }
    }
}
```
</p>
</details>



### PEST Usage

PEST lets you [assign classes and traits to your Pest tests](https://pestphp.com/docs/guides/laravel/) with the `uses(…)` helper function.

Add `uses(LaravelAdapt::class);` to the tests you'd like a database for.

Adapt's settings can be [customised](#pest-customisation) on a per-test basis when using PEST.

``` php
<?php
// tests\Feature\MyFeatureTest.php

use App\User;

uses(\CodeDistortion\Adapt\LaravelAdapt::class); // **** add this ****

beforeEach(fn () => factory(User::class)->create());

it('has users')->assertDatabaseHas('users', ['id' => 1]);
```



### Dusk Browser Test Usage

Adapt can prepare databases for your [Dusk](https://laravel.com/docs/8.x/dusk) browser tests. You can even run them alongside your non-browser tests, including when running them in parallel.

> ***Note***: This implements a new technique to share your test's config settings with the process handling the browser requests. This allows page-loads to ***share the same config settings as your tests - including the database details***. This functionality is new and **experimental**.
> 
> This config-sharing can also be used in Dusk tests when you *don't have a database*. You can tell Adapt to skip the database-building process by turning off the `build_databases` config setting (or `$buildDatabases` test-class property).

Simply build your Dusk tests like normal, and make these two minor changes:

- Replace the usual `DatabaseMigrations` with the `LaravelAdapt` trait (if you need the database), and
- When you've created your browser instance, tell it to use your test-databases by adding `$this->useCurrentConfig($browser);` (see below).

``` php
<?php
// tests\Browser\MyDuskTest.php

namespace Tests\Browser;

use App\Models\User;
use CodeDistortion\Adapt\LaravelAdapt; // **** add this ****
//use Illuminate\Foundation\Testing\DatabaseMigrations; // not needed
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class MyDuskTest extends DuskTestCase
{
    use LaravelAdapt; // **** add this ****
//  use DatabaseMigrations; // not needed

    public function testBasicExample()
    {
        $this->browse(function (Browser $browser) {

            $this->useCurrentConfig($browser); // **** add this ****

            $user = User::factory()->create();
            $browser->visit('/')->assertSee("$user->id $user->name");
        });
    }
}
```

The config settings your tests use (built from `.env.testing`) are passed to the server through the browser, and your `.env.dusk.local` file will be skipped.

You **don't need** to run `php artisan dusk` to run your dusk browser tests. Just include the browser tests in your normal test-run.

> When Dusk tests are run, transactions are turned off and snapshot dumps are turned on instead.



### Running your tests

Just run your tests like normal.

``` bash
php artisan test --parallel tests
```

> You can **include your browser tests in the same run** as the rest (no need to run `php artisan dusk` especially). Just add them to the list of things to test. In the example above, "tests" is the base directory containing *all* your tests.

All the normal methods of running your tests work:

``` bash
# using Laravel's test command
php artisan test
# using Laravel's command to start parallel tests
php artisan test --parallel
# run PHPUnit directly
./vendor/bin/phpunit
# run ParaTest directly
./vendor/bin/paratest
```

> Running your Dusk tests with `php artisan dusk` works, however you should probably use one of the other methods. If you do, it will go through the process of copying `.env.dusk.local` over `.env`, which is then discarded anyway.



### Usage Notes

To carry out the different types of caching that this package uses, you may need to address the following:

- When connecting to your database server, the user your code connects with needs to have **permission to create and drop databases**.
- The user your tests run as needs to have **write-access to the filesystem** to store snapshots and sqlite files.
- When using MySQL, Adapt uses the `mysqldump` and `mysql` executables to create and import snapshots. If these aren't in your system-path, you can specify their location in the `database.mysql` config section.
- If you have several projects using Adapt that use the same database server, you should give each one a unique `project_name` config value to stop them from interfering with each other's test-databases.
- If you see databases with names like *test_your_database_name_17bd3c_d266ab43ac75*, don't worry! These are the [scenario databases](#scenario-database-creation). Leave them to get the speed benefit of reusing them (but you can safely delete them).
- Adapt creates a table in your test-databases called `____adapt____` which holds meta-data used to identify when the database can be used.

See the [scenarios and techniques](#scenarios-and-techniques) section below for more tips.



### Artisan Console Commands

`php artisan adapt:list-db-caches` - Lists the databases and [snapshot files](#database-snapshots) that Adapt has created.

You won't need to clear old databases and snapshot files as Adapt does this automatically, however you can if you like:

`php artisan adapt:remove-db-caches`



## Customisation

As well as the `config/code_distortion.adapt.php` [config settings](#config), you can customise most of them inside your tests as shown below. You may wish to share these between similar tests by putting them in a trait or parent test-class:



### PHPUnit Customisation

Add any of the following properties to your test-class when needed.

``` php
<?php
// tests/Feature/MyFeatureTest.php

namespace Tests\Feature;

use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\LaravelAdapt;
use Tests\TestCase;

class MyFeatureTest extends TestCase
{
    use LaravelAdapt;

    /**
     * Enable / disable database building. This is useful when you want to use
     * Adapt to handle your Browser (Dusk) tests but don't have a database.
     *
     * @var boolean
     */
    protected $buildDatabases = true;

    /**
     * Let Adapt re-use databases.
     *
     * @var boolean
     */
    protected bool $reuseTestDBs = true;

    /**
     * Let Adapt create databases dynamically (with distinct names) based on
     * the scenario.
     *
     * @var boolean
     */
    protected bool $scenarioTestDBs = true;

    /**
     * Enable / disable the use of snapshot files.
     *
     * @var boolean
     */
    protected bool $snapshotsEnabled = true;

    /**
     * Adapt can take a snapshot after migrations have run (but before
     * seeders).
     *
     * @var boolean
     */
    protected bool $takeSnapshotAfterMigrations = true;

    /**
     * Adapt can take a snapshot after migrations and seeders have run.
     *
     * @var boolean
     */
    protected bool $takeSnapshotAfterSeeders = true;

    /**
     * Specify database dump files to import before migrations run.
     *
     * NOTE: It's important that these dumps don't contain output from seeders
     * if those seeders are to be run by Adapt as needed afterwards.
     *
     * NOTE: pre_migration_imports aren't available for sqlite :memory:
     * databases.
     *
     * @var string[]|string[][]
     */
    protected array $preMigrationImports = [
//        'mysql' => ['database/dumps/mysql/my-database.sql'],
//        'sqlite' => ['database/dumps/sqlite/my-database.sqlite'], // SQLite files are simply copied
//    ];

    /**
     * Specify whether to run migrations or not. You can also specify the
     * location of the migrations to run.
     *
     * @var boolean|string
     */
    protected $migrations = true;
//    or
//    protected $migrations = 'database/migrations';

    /**
     * Specify the seeders to run (they will only be run if migrations are
     * run).
     *
     * @var string[]
     */
    protected array $seeders = ['DatabaseSeeder'];

    /**
     * Overwrite the details of certain database connections with values from
     * others.
     *
     * eg. overwrite the "mysql" connection with the "sqlite" connection's
     * details so sqlite is used instead.
     *
     * @var string
     */
    protected string $remapConnections = 'mysql < sqlite';

    /**
     * Specify which connection "default" should point to.
     *
     * @var string
     */
    protected string $defaultConnection = 'mysql';

    /**
     * When performing browser tests, "reuse_test_dbs" needs to be turned off.
     *
     * This is because the browser (which runs in a different process and
     * causes outside requests to your website) needs to access the same
     * database that your tests build.
     *
     * When this value isn't present Adapt will attempt to detect if a browser
     * test is running.
     *
     * @var boolean
     */
    protected bool $isBrowserTest = true;

    /**
     * Set up the database/s programmatically.
     *
     * You may set up more test-databases by calling:
     * $this->newBuilder(string $connection), and then altering its settings.
     *
     * Each $builder object starts with the combined settings from the config
     * and properties from this test-class.
     *
     * @param DatabaseBuilder $builder Used to create the "default"
     *                                 connection's database.
     * @return void
     */
    protected function databaseInit(DatabaseBuilder $builder): void
    {
        $preMigrationImports =  [
            'mysql' => ['database/dumps/mysql/my-database.sql'],
            'sqlite' => ['database/dumps/sqlite/my-database.sqlite'], // SQLite files are simply copied
        ];

        // the DatabaseBuilder $builder is pre-built to match your config settings
        // you can override them with any of the following…
        $builder
            ->connection('primary-mysql') // specify another connection to build a db for
            ->preMigrationImports($preMigrationImports) // or ->noPreMigrationImports()
            ->migrations() // or ->migrations('database/migrations') or ->noMigrations()
            ->seeders(['DatabaseSeeder']) // or ->noSeeders()
            ->reuseTestDBs() // or ->noReuseTestDBs()
            ->scenarioTestDBs() // or ->noScenarioTestDBs()
            ->snapshots() // or ->noSnapshots()
            ->isBrowserTest() // or isNotBrowserTest()
            ->makeDefault(); // make the "default" Laravel connection point to this database

        // create a database for another connection
        $connection = 'secondary-mysql';
        $builder2 = $this->newBuilder($connection);
        /** @var DatabaseBuilder $builder2 **/
        $builder2
            ->preMigrationImports($preMigrationImports) // or ->noPreMigrationImports()
            // …
            ->makeDefault(); // make the "default" Laravel connection point to this database
    }

    // …

}
```



### PEST Customisation

You can add custom Adapt settings to your PEST tests by creating a trait with the desired settings&hellip;

``` php
<?php
// tests\MyLaravelAdapt;

namespace Tests;

use CodeDistortion\Adapt\LaravelAdapt;

trait MyLaravelAdapt
{
    use LaravelAdapt;

    protected string $seeders = ['DatabaseSeeder', 'ShoppingCartSeeder'];
    // etc as above in the PHPUnit Customisation section…
}
```

and include it in your test:

``` php
<?php
// tests\Feature\MyFeatureTest.php

use App\User;

uses(\Tests\MyLaravelAdapt::class); // **** add this ****

beforeEach(fn () => factory(User::class)->create());

it('has users')->assertDatabaseHas('users', [
    'id' => 1,
]);
```

You can add any of the customisation values [from above](#phpunit-customisation).



## Caching Mechanisms

Adapt uses these caching mechanisms to improve testing speed.



### Database Snapshots

When a database is migrated and seeded by Adapt, it can take a snapshot (.sql dump file) ready for importing next time.

A snapshot can be taken right after the migrations have run (but before seeding), and another can be taken after seeding has completed.

Snapshot files are stored in the `database/adapt-test-storage` directory, and are automatically removed when they're not valid anymore.

> ***Snapshots*** are turned **OFF** by default, and turned **ON** when [browser-testing](#dusk-browser-test-usage) is detected.



### Re-using Databases *Within* A Test-Run

Adapt wraps your tests inside a transaction, and rolls it back afterwards. When the next test runs, it checks to make sure the transaction wasn't committed, and will re-use it if so.

> ***Transaction-rollback*** is turned **ON** by default, but turned off automatically during browser tests.



### Re-using Databases *Between* Test-Runs

Adapt will re-use your test-databases between test-runs. It checks to make sure the database was left in a clean state (as above), but also checks to make sure the scenario hasn't changed since last time (eg. if you're edited your migrations or seeders etc).

> ***Reuse test dbs*** is turned **ON** by default, but turned off automatically during browser tests.

> ***Note***: You can safely delete test-databases left by Adapt but **don't change data** in them as they are assumed to be in a clean state.



### Creation of "Scenario" Databases

Adapt will create a separate test-database for each "scenario" your tests use (eg. when different seeders are run). These databases have names similar to *test_your_database_name_17bd3c_d266ab43ac75* (so don't worry if you see them).

These scenario databases then co-exist, allowing each of them to be re-used straight away without needing to be rebuilt.

Old scenario databases are removed automatically when they aren't valid anymore.

> ***Scenario test dbs*** is turned **ON** by default.



## Cache Invalidation

So that you don't run in to problems when you update the structure of your database or the way it's populated, changes to files inside */database/factories*, */database/migrations*, and */database/seeds* will invalidate existing test-databases and snapshots (the `pre_migration_imports` and `migrations` files are also taken in to account).

These invalid test-databases and snapshots are cleaned up **automatically**, and fresh versions will be built the next time your tests run.

This list of directories can be configured via the `look_for_changes_in` config setting.



## Testing Scenarios and Techniques

Here are various testing scenarios and comments about each:



### Using the "default" database connection&hellip;

Projects using the "default" database connection is the most common scenario. After adding the `LaravelAdapt` trait to your test-classes, you probably won't need to change any settings.

When your tests run, a test-database will be created for the default connection.



### Building databases for non "default" connections, and using more than one database connection&hellip;

Adapt can build extra databases for you. So it knows what to build, [add the databaseInit() method](#phpunit-customisation) to your test-classes.

``` php
// tests/Feature/MyFeatureTest.php
…
class MyFeatureTest extends TestCase
{
    use LaravelAdapt;
    
    protected function databaseInit(DatabaseBuilder $builder): void
    {
        // the DatabaseBuilder $builder is pre-built to match your config
        // settings. It uses the "default" database connection to begin with.
        // you can tell it to build a test-database for another connection
        // instead
        $builder->connection('primary-mysql');
        
        // create a database for another connection
        $connection = 'secondary-mysql';
        $builder2 = $this->newBuilder($connection);
        /** @var DatabaseBuilder $builder2 **/
        $builder2
            ->migrations('path/to/other/migrations')
            // … etc
    }

    …
}

// somewhere else
DB::connection('primary-mysql')->select(…);
DB::connection('secondary-mysql')->select(…);
```

> If you want more flexibility in altering the database Laravel uses for each connection, you may want to look at the `remap_connections` setting.



### Using a different type of database&hellip;

As a part of Laravel's database functionality, you could try using a SQLite database by changing the connection the "default" setting refers to.

``` php
// .env.testing
DB_CONNECTION=sqlite

// config/database.php
'default' => env('DB_CONNECTION', 'mysql'),
```

> ***Note***: SQLite isn't fully compatible with other databases. To be safe, you ***should*** consider running your tests with the **same type of database that you use in production**. Your confidence in the tests is very important.

> ***Note***: **SQLite :memory:** databases automatically disappear between tests, and need to be re-built each time. Because of this, you might not get the speed-boost you're hoping for, particularly if you have lots of small tests.



### Seeding during testing&hellip;

To help Adapt be a drop-in replacement for Laravel's existing options, it doesn't run your seeders default. But you can specify seeders for it to run. The result will be incorporated into Adapt's caching system. This way, you won't need to run the seeders within each test!

If you'd like to specify seeders, or run different ones for different tests, see the `seeders` config option (or the `$seeders` test-class property).

``` php
// tests/Feature/MyFeatureTest.php
…
class MyFeatureTest extends TestCase
{
    use LaravelAdapt;

    protected array $seeders = ['SomeOtherDatabaseSeeder'];

    …
}
```



### Dusk browser tests&hellip;

Once you've added `$this->useCurrentConfig($browser);` to your [Dusk](https://laravel.com/docs/8.x/dusk) browser tests (see [above](#dusk-browser-test-usage)), you'll be able to run your browser tests alongside your other tests. Including running them in parallel.



### Running tests in parallel&hellip;

Laravel 8 integrates [ParaTest](https://github.com/paratestphp/paratest), allowing your tests to run in parallel. Tests are run in different processes, and the collated results are shown afterwards.

Adapt detects when ParaTest used and creates a distinct database for each process by adding a unique suffix to the database name.



### Testing code that itself uses transactions&hellip;

To maintain a known state, Adapt normally wraps your tests inside a transaction and rolls it back afterwards.

If your own code uses transactions as well, Adapt will detect that its own transaction was implicitly committed, and will re-build the database for the next test.

> In this situation, you may wish to turn the `reuse_test_dbs` option off, so it gets a different "scenario" to other tests where it's on. This will stop "thrashing" from occurring between these two situations. You may also want to turn `snapshots.enabled` on (or the `$snapshotsEnabled` test-class property) so sql dumps are created and imported.



### Importing custom database dump files&hellip;

To import your own database dump sql file, put it in your filesystem and add it to the `pre_migration_imports` config setting (or `$preMigrationImports` test-class property). There's a spot there to add files for each type of database.

This might save time if you have lots of migrations to run, or be useful if you have some other funky data set-up going on.

> Any remaining migrations and seeding will run after these have been imported.

> SQLite database files aren't imported, they are simply copied.

> You might want to look at [Laravel's migration squashing](https://laravel.com/docs/8.x/migrations#squashing-migrations) feature to do this *within* Laravel's migration process.



## Testing

``` bash
composer test
```



## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.



### SemVer

This library uses [SemVer 2.0.0](https://semver.org/) versioning. This means that changes to `X` indicate a breaking change: `0.0.X`, `0.X.y`, `X.y.z`. When this library changes to version 1.0.0, 2.0.0 and so forth it doesn't indicate that it's necessarily a notable release, it simply indicates that the changes were breaking.



## Treeware

This package is [Treeware](https://treeware.earth). If you use it in tests of a production project, then we ask that you [**buy the world a tree**](https://plant.treeware.earth/code-distortion/adapt) to thank us for our work. By contributing to the Treeware forest you’ll be creating employment for local families and restoring wildlife habitats.



## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.



### Code of Conduct

Please see [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.



### Security

If you discover any security related issues, please email tim@code-distortion.net instead of using the issue tracker.



## Credits

- [Tim Chandler](https://github.com/code-distortion)



## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
