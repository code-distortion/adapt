# Adapt - A Database Preparation Tool (for your tests)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/code-distortion/adapt.svg?style=flat-square)](https://packagist.org/packages/code-distortion/adapt)
![PHP from Packagist](https://img.shields.io/packagist/php-v/code-distortion/adapt?style=flat-square)
![Laravel](https://img.shields.io/badge/laravel-5.1+%2C%206%2C%20%207%20%26%208-blue?style=flat-square)
[![GitHub Workflow Status](https://img.shields.io/github/workflow/status/code-distortion/adapt/run-tests?label=tests&style=flat-square)](https://github.com/code-distortion/adapt/actions)
[![Buy The World a Tree](https://img.shields.io/badge/treeware-%F0%9F%8C%B3-lightgreen?style=flat-square)](https://plant.treeware.earth/code-distortion/adapt)
[![Contributor Covenant](https://img.shields.io/badge/contributor%20covenant-v2.0%20adopted-ff69b4.svg?style=flat-square)](CODE_OF_CONDUCT.md)

***code-distortion/adapt*** is a [Laravel](https://github.com/laravel/laravel) package that builds databases for your tests and can give large speed improvements.



## Table of Contents

* [Introduction](#introduction)
* [Who will benefit from using Adapt?](#who-will-benefit-from-using-adapt)
* [Installation](#installation)
    * [Config](#config)
* [Usage](#usage)
    * [PHPUnit Usage](#phpunit-usage)
    * [PEST Usage](#pest-usage)
    * [Usage Notes](#usage-notes)
    * [Artisan Console Commands](#artisan-console-commands)
* [Caching Mechanisms](#caching-mechanisms)
    * [Reuse Of Test-Databases](#reuse-of-test-databases)
    * [Dynamic Database Creation](#dynamic-database-creation)
    * [Database Snapshots](#database-snapshots)
* [Cache Invalidation](#cache-invalidation)
* [Customisation](#customisation)
    * [PHPUnit Customisation](#phpunit-customisation)
    * [PEST Customisation](#pest-customisation)
* [Scenarios and Techniques](#scenarios-and-techniques)
    * [My project only uses the "default" connection…](#my-project-only-uses-the-default-connection)
    * [My seeders take a while to run / different seeders are needed for different tests…](#my-seeders-take-a-while-to-run--different-seeders-are-needed-for-different-tests)
    * [My project uses database connections by name…](#my-project-uses-database-connections-by-name)
    * [When performing browser testing (such as using Dusk)…](#when-performing-browser-testing-such-as-using-dusk)
    * [I would like to run my tests in parallel using ParaTest…](#i-would-like-to-run-my-tests-in-parallel-using-paratest)
    * [Some of my own code uses transactions…](#some-of-my-own-code-uses-transactions)
    * [I have my own database dump that I'd like to import…](#i-have-my-own-database-dump-that-id-like-to-import)
    * [My project uses more than one database…](#my-project-uses-more-than-one-database)
* [Testing](#testing)
* [Changelog](#changelog)
    * [SemVer](#semver)
* [Treeware](#treeware)
* [Contributing](#contributing)
    * [Code of Conduct](#code-of-conduct)
    * [Security](#security)
* [Credits](#credits)
* [License](#license)



## Introduction

Adapt is a replacement for Laravel's test database traits which builds your test-databases and makes re-use almost instant by avoiding the need to re-build them each time.

It allows for a high level of [customisation](#customisation), but **most likely all you'll need to do is [apply it to your tests](#usage) and it will work out of the box**.

> Normally when creating [PHPUnit](https://github.com/sebastianbergmann/phpunit) or [Pest](https://github.com/pestphp/pest) tests in Laravel you would use the *RefreshDatabase* trait (or possibly *DatabaseTransactions* or *DatabaseMigrations*) to manage how your database is built.
>
> If your project has a lot of migrations, this can end up taking a long time because the database is built from scratch before each test-run. Even if you can, importing a pre-built sql file can be slow.



## Who will benefit from using Adapt?

Laravel projects with tests that use a database will see an improvement in test speed, particularly when their migrations and seeders take a while to run.

To benefit to as many people as possible, Adapt has been developed to be compatible with **Laravel 5.1+, 6, 7 & 8** and **PHP 7.0 - 8.0** (**Linux** and **MacOS** are currently supported).

The currently supported databases are: **MySQL**, **SQLite** and **SQLite :memory:**.



## Installation

Install the package via composer:

``` bash
composer require code-distortion/adapt --dev
```

Adapt integrates with Laravel 5.5+ automatically thanks to Laravel's package auto-detection.

<details><summary>(Click here for Laravel 5.0 - 5.4)</summary>
<p>

The service provider is only used to enable the [artisan commands](#artisan-commands) so you can safely skip this step if you like.

For Laravel 5.0 - 5.4, add the following to `app/Providers/AppServiceProvider.php` to enable it (only in your local / testing environment):

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

> ***Note***: The custom environment values you'd like to add should be put in your `.env.testing` file if you use one (rather than `.env`).



## Usage

### PHPUnit Usage

For most projects, all you'll need to do is add the `CodeDistortion\Adapt\LaravelAdapt` trait to the test-classes you'd like a database for, and away you go.

Your migrations and seeders will be run ready for your tests.

``` php
<?php
// tests/Integration/MyTest.php

namespace Tests\Integration;

use CodeDistortion\Adapt\LaravelAdapt; // **** add this ****
//use Illuminate\Foundation\Testing\DatabaseMigrations;   // not needed
//use Illuminate\Foundation\Testing\DatabaseTransactions; // not needed
//use Illuminate\Foundation\Testing\RefreshDatabase;      // not needed

use Tests\TestCase;

class MyTest extends TestCase
{
    use LaravelAdapt; // **** add this ****
//  use DatabaseMigrations;   // not needed
//  use DatabaseTransactions; // not needed
//  use RefreshDatabase;      // not needed
    …
}
```

Just run your tests like normal. If you like you can also [customise Adapt's settings](#customisation) on a per-test basis.

<details><summary>(Click here if you're using an old version of PHPUnit (< ~v6) and are having problems)</summary>
<p>

If you're using an old version of PHPUnit and want to populate database data in your setUp() method, you'll run in to problems because PHPUnit used to initialise things like Adapt [after the setUp() method was called](https://github.com/sebastianbergmann/phpunit/issues/1616).

- To solve this either put the code to populate the database into a seeder and have Adapt run that.

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;*or*

- Add this to your base TestCase `setUp()` method to boot Adapt:

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

        // **** add this to boot LaravelAdapt ****
        if (in_array(LaravelAdapt::class, class_uses_recursive(static::class))) {
            $this->initialiseAdapt();
        }
    }
}
```
</p>
</details>



### PEST Usage

PEST lets you [assign classes and traits to your tests](https://pestphp.com/docs/guides/laravel/) with the `uses(…)` helper function.

All you need to do to add Adapt to your PEST tests is add `uses(LaravelAdapt::class);` to the tests you'd like a database for.

``` php
<?php
// tests\Feature\MyTest.php

use App\User;

uses(\CodeDistortion\Adapt\LaravelAdapt::class); // **** add this ****

beforeEach(fn () => factory(User::class)->create());

it('has users')->assertDatabaseHas('users', ['id' => 1]);
```

Adapt's settings can also be [customised](#pest-customisation) on a per-test basis when using PEST.



### Usage Notes

To carry out the different types of caching that this package uses, you may need to address the following:

- The user/s your code connects to the database with need to have **permission to create and drop databases**.
- The user your tests run as needs to have **write-access to the filesystem** to store snapshots and sqlite files.
- When using MySQL, Adapt uses the `mysqldump` and `mysql` executables to create and import snapshots. If these aren't in your system-path, you can specify their location in the `database.mysql` config section.
- If you have several projects using Adapt that use the same database server, you should set the `project-name` config setting to a unique value in each to stop them from interfering with each other's test-databases.
- If you see databases with names like *test_your_database_name_17bd3c_d266ab43ac75*, don't worry! These are the [dynamically created databases](#dynamic-database-creation). Leave them to get the speed benefit of using them (but you can safely delete them).
- Adapt creates a table in your test databases called `____adapt____` which holds meta-data used to identify when the database should be used.

See the [scenarios and techniques](#scenarios-and-techniques) section below for more tips.



### Artisan Console Commands

`php artisan adapt:list-db-caches` - Lists the databases and [snapshot files](#database-snapshots) that Adapt has created.

You won't need to clear old databases and snapshot files as Adapt does this automatically, however you can if you like:

`php artisan adapt:remove-db-caches`



## Caching Mechanisms

Adapt uses these caching mechanisms to improve testing speed.

> ***Note***: You can safely delete test-databases left by Adapt but **don't change data** in them as they will be reused and are assumed to be in a clean state.



### Reuse Of Test-Databases

Once a test-database has been built, it's possible to reuse it in the next test-run without building it again.

Adapt will reuse test-databases provided they were left in a clean state. To maintain this clean state, **transactions** are used and then rolled back afterward each test.

This setting is best used in conjunction with the [dynamic database creation](#dynamic-database-creation) caching below.

Test-database reuse is turned **ON** by default.



### Dynamic Database Creation

This setting lets Adapt create a separate test-database for each scenario your tests need (eg. when different seeders are run). These databases will have names similar to *test_your_database_name_17bd3c_d266ab43ac75* (so don't worry if you see them).

These scenarios then co-exist allowing each of them to be re-used straight away (without rebuilding for the current scenario).

And so, this setting is best used in conjunction with the [reuse of test-databases](#reuse-of-test-databases) caching above.

Dynamic database creation is turned **ON** by default.



### Database Snapshots

As a database is migrated and/or seeded, a snapshot (eg. a .sql dump file) is taken ready for importing next time it's needed.

A snapshot can be taken right after the migrations have run (but before seeding), and another can be taken after seeding has completed (and is ready to use).

Snapshot files are stored in the `database/adapt-test-storage` directory (configurable via the `storage-dir` config setting). They're safe to delete, however you don't need to as they're cleaned up automatically when necessary.

This method is particularly useful when [running browser-tests](#performing-browser-testing-such-as-using-dusk) as the other caching methods are turned off.

Database snapshots are turned **OFF** by default, unless [browser-testing](#performing-browser-testing-such-as-using-dusk) is detected.

> ***Note***: SQLite database files aren't exported and imported, they are simply copied.



## Cache Invalidation

So that you don't run in to problems when you update the structure of your database or the way it's populated, changes to files inside */database/factories*, */database/migrations*, and */database/seeds* will invalidate existing test-databases and snapshots (the `pre-migration-imports` and `migrations` files are also taken in to account).

These invalid test-databases and snapshots are cleaned up **automatically**, and fresh versions will be built the next time your tests run.

This list of directories can be configured via the `look-for-changes-in` config setting.



## Customisation

As well as the `config/code_distortion.adapt.php` [config settings](#config), you can customise many of them inside your tests as shown below. You may wish to share these between similar tests by putting them in a trait or parent test-class:



### PHPUnit Customisation

Add any of the following to your test class when needed.

``` php
<?php
// tests/Integration/MyTest.php

namespace Tests\Integration;

use CodeDistortion\Adapt\LaravelAdapt;
use Tests\TestCase;

class MyTest extends TestCase
{
    use LaravelAdapt;

    /**
     * Specify database dump files to import before migrations run.
     *
     * NOTE: It's important that these dumps don't contain output from seeders
     * if those seeders are to be run by Adapt as needed afterwards.
     *
     * NOTE: pre-migration-imports aren't available for sqlite :memory:
     * databases.
     *
     * @var string[]|string[][]
     */
    protected array $preMigrationImports = [
        'mysql' => ['database/dumps/mysql/my-database.sql'],
        'sqlite' => ['database/dumps/sqlite/my-database.sqlite'], // SQLite files are simply copied
    ];

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
     * Let Adapt re-use databases.
     *
     * NOTE: this requires the transactions setting to be on.
     *
     * @var boolean
     */
    protected bool $reuseTestDBs = true;

    /**
     * Let Adapt create databases dynamically based on the scenario.
     *
     * @var boolean
     */
    protected bool $dynamicTestDBs = true;

    /**
     * Encapsulate each test inside a transaction - it's rolled back afterwards
     * to leave the database in it's initial state.
     *
     * @var boolean
     */
    protected bool $transactions = true;

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
     * When performing browser tests "reuse-test-dbs", "dynamic-test-dbs"
     * and "transactions" need to be turned off.
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
     * Set up the database/s programmatically.
     *
     * You may set up more test-databases by calling:
     * $this->newBuilder(string $connection), and then altering its settings.
     *
     * Each $builder object starts with the combined settings from the config
     * and properties from this test-class.
     *
     * @param DatabaseBuilder $builder Used to create the first database.
     * @return void
     */
    protected function databaseInit(DatabaseBuilder $builder): void
    {
        $preMigrationImports =  [
            'mysql' => ['database/dumps/mysql/my-database.sql'],
            'sqlite' => ['database/dumps/sqlite/my-database.sqlite'], // SQLite files are simply copied
        ];

        // the DatabaseBuilder $builder will contain settings based on the
        // config and properties above. You can override them like so:
        $builder
            ->preMigrationImports($preMigrationImports) // or ->noPreMigrationImports()
            ->migrations() // or ->migrations('database/migrations') or ->noMigrations()
            ->seeders(['DatabaseSeeder']) // or ->noSeeders()
            ->reuseTestDBs() // or ->noReuseTestDBs()
            ->dynamicTestDBs() // or ->noDynamicTestDBs()
            ->transactions() // or ->noTransactions()
            ->snapshots() // or ->noSnapshots()
            ->isBrowserTest() // or isNotBrowserTest()
            ->makeDefault(); // make the "default" Laravel connection point to this database

        // define a second database
        $connection = 'mysql2';
        $builder2 = $this->newBuilder($connection); /** @var DatabaseBuilder $builder2 **/
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
// tests\Feature\MyTest.php

use App\User;

uses(\Tests\MyLaravelAdapt::class); // **** add this ****

beforeEach(fn () => factory(User::class)->create());

it('has users')->assertDatabaseHas('users', [
    'id' => 1,
]);
```

You can add any of the customisation values [from above](#phpunit-customisation).



## Scenarios and Techniques

Here are various scenarios and comments about each:



### My project only uses the "default" connection&hellip;

This is probably the most common scenario. You could choose to continue using the same connection to test with (eg. "mysql"), or swap it out for a SQLite database which may improve speed by setting the **default-connection** setting.

> ***Note***: **SQLite :memory:** databases automatically disappear when the client disconnects from it, and PHPUnit disconnects from databases between tests. Because of this, a memory database will need to be re-built for each test **so it's less likely to be the quickest type of database to use**.



### My seeders take a while to run / different seeders are needed for different tests&hellip;

You can take advantage of Adapt's caching by getting *it* to run your seeders. This way they'll be included in Adapt's caching. Just specify the **seeders** you'd like to run either in the `seeders` config setting or in the `$seeders` property in your test-classes.

By specifying them in your test-classes, you can run different seeders for different tests.

By default, the regular **DatabaseSeeder** is run after the migrations.



### My project uses database connections by name&hellip;

If your codebase picks database connections by name (instead of letting the "default" connection be used), you'll want to look at the `remap-connections` setting that lets you update the settings for your other connections.



### When performing browser testing (such as using Dusk)&hellip;

When browser testing some cache settings need to be turned off.

The browser (which runs in a different process and causes external requests to your website) needs to access the same database that your tests build so you'll need **reuse-database**, **dynamic-test-dbs** and **transactions** to be turned off.

Adapt detects when a Dusk test is running and turns them off **automatically** (and also takes a snapshot after seeding by turning [database snapshots](#database-snapshots) on). You can override this setting by setting the `$isBrowserTest` true/false property in your test-classes.



### I would like to run my tests in parallel using ParaTest&hellip;

[paratestphp/paratest](https://github.com/paratestphp/paratest) is a package that splits your test-suite in to parts and runs them in parallel using multiple processes. Adapt detects when ParaTest is being used and creates a distinct database for each process by adding a unique suffix.



### Some of my own code uses transactions&hellip;

This is fine! To maintain a known state, Adapt normally wraps your tests inside a transaction and rolls it back afterwards. If your own code uses transactions as well, Adapt will automatically detect that its own transaction was implicitly committed, and will re-build the database for the next test. You may wish to turn on the `snapshots.enabled` config setting so sql dumps are created and imported - to save time migrating + running seeders.



### I have my own database dump that I'd like to import&hellip;

You might have your own database dump file that you'd like to import instead of migrating from scratch. Pop it in your filesystem and add it to the `pre-migration-imports` config setting or `$preMigrationImports` test-class property. There's a spot there to add files for each type of database.

This might save time if you have lots of migrations to run, or be useful if you have some other funky data set-up going on.

> ***Note***: Any remaining migrations and the seeding will run after these have been imported.

> ***Note***: SQLite database files aren't imported, they are simply copied.



### My project uses more than one database&hellip;

You can build extra databases by adding the `databaseInit()` method to your test-class and setting up more connections there (see the [Customisation](#customisation) section above).



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
