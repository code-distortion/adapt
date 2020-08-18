# Adapt - A Database Preparation Tool (For Your Tests)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/code-distortion/adapt.svg?style=flat-square)](https://packagist.org/packages/code-distortion/adapt)
![PHP from Packagist](https://img.shields.io/packagist/php-v/code-distortion/adapt?style=flat-square)
![Laravel](https://img.shields.io/badge/laravel-5.1+%2C%206%20%26%207-blue?style=flat-square)
[![GitHub Workflow Status](https://img.shields.io/github/workflow/status/code-distortion/adapt/run-tests?label=tests&style=flat-square)](https://github.com/code-distortion/adapt/actions)
[![Buy us a tree](https://img.shields.io/badge/treeware-%F0%9F%8C%B3-lightgreen?style=flat-square)](https://offset.earth/treeware?gift-trees)
[![Contributor Covenant](https://img.shields.io/badge/contributor%20covenant-v2.0%20adopted-ff69b4.svg?style=flat-square)](CODE_OF_CONDUCT.md)

***code-distortion/adapt*** is a [Laravel](https://github.com/laravel/laravel) package that builds databases for your tests and can drastically improve the preparation time by applying caching techniques.

## Introduction

Normally when creating [PHPUnit](https://github.com/sebastianbergmann/phpunit) tests in Laravel you would use the *RefreshDatabase*, *DatabaseMigrations* or *DatabaseTransactions* traits to manage how your database is built.

A big factor in how long this takes is the fact that the database is built from scratch every time your tests run. If your project has a lot of migrations, this can end up taking a long time.

Adapt is a replacement for these traits which builds your test-databases and improves the speed of subsequent test-runs by avoiding the need to re-build them each time.

It allows for a high level of customisation, but **most likely all you'll need to do is apply it to your tests and it will work out of the box** (see the [usage](#usage) section below).

## Who will benefit from using Adapt?

Laravel projects with tests that use a database will see an improvement in test speed, particularly when their migrations and seeders take a while to run.

The currently supported databases are: **MySQL**, **SQLite** and **SQLite :memory:**.



## Installation

Install the package via composer:

``` bash
composer require code-distortion/adapt --dev
```

Adapt integrates with Laravel 5.5+ automatically thanks to Laravel's package auto-detection. For Laravel 5.0 - 5.4, add the following line to `config/app.php`:

``` php
'providers' => [
    …
    CodeDistortion\Adapt\LaravelServiceProvider::class,
    …
],
```

#### Config

You can alter the default settings by publishing the `config/code-distortion.adapt.php` config file and updating it:

``` bash
php artisan vendor:publish --provider="CodeDistortion\Adapt\LaravelServiceProvider" --tag="config"
```

## Usage

For most projects, all you'll need to do is add the `CodeDistortion\Adapt\LaravelAdapt` trait to the test-classes you'd like it to apply to, and update the `setUp()` method in your abstract TestCase class to boot it.

``` php
<?php
// tests/TestCase.php

namespace Tests;

use CodeDistortion\Adapt\LaravelAdapt;
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

``` php
<?php
// tests/Integration/MyTest.php

namespace Tests\Integration;

use CodeDistortion\Adapt\LaravelAdapt;
//use Illuminate\Foundation\Testing\DatabaseMigrations;
//use Illuminate\Foundation\Testing\DatabaseTransactions;
//use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyTest extends TestCase
{
    use LaravelAdapt; // **** add this ****

//  use RefreshDatabase;      // not needed
//  use DatabaseMigrations;   // not needed
//  use DatabaseTransactions; // not needed

    // …
}
```

Then just run your tests like normal.

### Usage Notes

To carry out the different types of caching that this package uses, you may need to address the following:

- The user/s your code connects to the database with need to have **permission to create and drop databases**.
- The user your tests run as needs to have **write-access to the filesystem** to store snapshots and sqlite files.
- When using MySQL, Adapt uses the `mysqldump` and `mysql` executables to create and import snapshots. If these aren't in your system-path, you can specify their location in the `database.mysql` config section.
- If you have several projects using Adapt that use the same database server, you should set the `project-name` config setting to a unique value in each to stop them from interfering with each other's test-databases.
- If you see databases with names like *your_database_name_341494d96f668950_ed40917d3e7f9b50*, don't worry! These are the [dynamically created databases](#dynamic-database-creation). Leave them to get the speed benefit of using them (but you can safely delete them).

See the [scenarios and techniques](#scenarios-and-techniques) section below for more tips.

### Artisan Commands

`php artisan adapt:list-db-caches` - See the databases and [snapshot files](#database-snapshots) that Adapt has created.

You won't need to clear old databases and snapshot files as Adapt does this automatically, however you can if you like:

`php artisan adapt:remove-db-caches` - Remove Adapt's databases and snapshot files.



## Caching Mechanisms

Adapt uses these caching mechanisms to improve testing speed.

***Note***: You can safely delete test-databases left by Adapt but **don't change data** in them as they will be reused and are assumed to be in a clean state.

### Reuse Of Test-Databases

Once a test-database has been built, it's possible to reuse it in the next test-run without building it again.

Adapt will reuse test-databases provided they were left in a clean state. To maintain this clean state, **transactions** are used and then rolled back afterward each test.

This setting is best used in conjunction with the [dynamic database creation](#dynamic-database-creation) caching below.

This is turned **ON** by default.

### Dynamic Database Creation

This setting lets Adapt create a separate test-database for each scenario your tests need (eg. when different seeders are run). These databases will have names similar to *your_database_name_341494d96f668950_ed40917d3e7f9b50* (so don't worry if you see them).

These scenarios then co-exist allowing each of them to be re-used straight away (without rebuilding for the current scenario).

And so, this setting is best used in conjunction with the [reuse of test-databases](#reuse-of-test-databases) caching above.

This is turned **ON** by default.

### Database Snapshots

As a database is migrated and/or seeded, a snapshot (eg. a .sql dump file) is taken ready for importing next time it's needed.

A snapshot can be taken right after the migrations have run (but before seeding), and another can be taken after seeding has completed (and is ready to use).

Snapshot files are stored in the `database/adapt-test-storage` directory (configurable via the `storage-dir` config setting), and are safe to delete however you don't need to.

This method is particularly useful when [running browser-tests](#performing-browser-testing-such-as-using-dusk) as the other caching methods are turned off.

This is turned **OFF** by default.

***Note***: SQLite database files aren't exported and imported, they are simply copied.



## Cache Invalidation

So that you don't run in to problems when you update the structure of your database or the way it's populated, changes to files inside */database/factories*, */database/migrations*, and */database/seeds* will invalidate existing test-databases and snapshots (the `pre-migration-imports` files are also taken in to account).

These invalid test-databases and snapshots are cleaned up **automatically**, and fresh versions will be built the next time your tests run.

This list of directories can be configured via the `look-for-changes-in` config setting.



## Customisation

As well as the `config/code-distortion.adapt.php` config settings, you can customise many of them inside your tests. You may wish to share these between similar tests by putting them in a trait or a parent test-class:

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
            ->makeDefault(); // make the "default" connection point to this database

        // define a second database
        $connection = 'mysql2';
        $builder2 = $this->newBuilder($connection); /** @var DatabaseBuilder $builder2 **/
        $builder2
            ->preMigrationImports($preMigrationImports) // or ->noPreMigrationImports()
            // ...
            ->makeDefault(); // make the "default" connection point to this database
    }

    // …

}
```



## Scenarios and Techniques

Here are various scenarios and comments about each:

### My website only uses the "default" connection&hellip;

This is probably the most common scenario. You could choose to continue using the same connection to test with (eg. "mysql"), or swap it out for a SQLite database which may improve speed by setting the **default-connection** setting.

***Note***: **SQLite :memory:** databases are destroyed when the client disconnects from it, and PHPUnit disconnects from the database between tests. Because of these actions, a memory database will need to be re-built for each test **so it's less likely to be the quickest type of database to use**.

### My seeders take a while to run / different seeders are needed for different tests&hellip;

You can take advantage of Adapt's caching by getting *it* to run your seeders. This way they'll be included in Adapt's caching. Just specify the **seeders** you'd like to run either in the `seeders` config setting or in the `$seeders` property in your test-classes.

By specifying them in your test-classes, you can run different seeders for different tests.

By default, the regular **DatabaseSeeder** is run after the migrations.

### My website uses database connections by name&hellip;

If your codebase picks database connections by name (instead of just following the "default" connection), you won't be able to change the database it uses by updating where the "default" connection points to. Instead you'll want to look at the `remap-connections` setting to overwrite connections' details.

### Performing browser testing (such as using Dusk)&hellip;

When browser testing some cache settings need to be turned off.

The browser (which runs in a different process and causes external requests to your website) needs to access the same database that your tests build so you'll need **reuse-database**, **dynamic-test-dbs** and **transactions** to be turned off.

Adapt detects when a Dusk test is running and turns them off **automatically** (and turns [database snapshots](#database-snapshots) on). You can override this setting by setting the `$isBrowserTest` true/false property in your test-classes.  

### I have my own database dump that I'd like to import&hellip;

You might have your own database dump file that you'd like to import instead of migrating from scratch. Pop it in your filesystem and add it to the `pre-migration-imports` config setting or `$preMigrationImports` test-class property. There's a spot there to add files for each type of database.

This might save time if you have lots of migrations to run, or be useful if you have some other funky data set-up going on.

***Note***: Any remaining migrations and the seeding will run after these have been imported.

***Note***: SQLite database files aren't imported, they are simply copied.

### My website uses more than one database&hellip;

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

You're free to use this package, but if it makes it to your production environment please plant or buy a tree for the world.

It's now common knowledge that one of the best tools to tackle the climate crisis and keep our temperatures from rising above 1.5C is to <a href="https://www.bbc.co.uk/news/science-environment-48870920">plant trees</a>. If you support this package and contribute to the Treeware forest you'll be creating employment for local families and restoring wildlife habitats.

You can buy trees here [offset.earth/treeware](https://offset.earth/treeware?gift-trees)

Read more about Treeware at [treeware.earth](http://treeware.earth)

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Code of conduct

Please see [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

### Security

If you discover any security related issues, please email tim@code-distortion.net instead of using the issue tracker.

## Credits

- [Tim Chandler](https://github.com/code-distortion)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
