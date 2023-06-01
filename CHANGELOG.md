# Changelog

All notable changes to `code-distortion/adapt` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).



## [0.12.7] - 2023-06-02

### Fixed
- Fixed a bug when handling a remote-build request



## [0.12.6] - 2023-06-02

### Fixed
- Fixed a path resolution bug that broke some tests



## [0.12.5] - 2023-06-02

### Fixed
- Fixed a bug in the `--repeat=x` fix from 0.12.4, in older versions of Laravel



## [0.12.4] - 2023-06-01

### Added
- Added support for Laravel 10

### Fixed
- Fixed a bug that stopped database/s from being prepared after the first run, when using the `--repeat=x` option
- Fixed a bug that caused execution to stop, when running tests from outside the project root-dir



## [0.12.3] - 2022-12-20

### Added
- Added support for PHP 8.2

### Fixed
- Fixed bug when detecting which seeders to run



## [0.12.2] - 2022-07-XX

### Added
- Added a more useful exception when a chosen connection doesn't exist

### Changed
- Added the `getShareHeaders()` method to replace the `getShareConnectionsHeaders()` method added to test classes (which is now deprecated, but will still work)



## [0.12.1] - 2022-07-08

### Changed
- Minor tweaks to documentation

### Fixed
- Fix for calls to the `databaseInit(..)` method



## [0.12.0] - 2022-07-05

### Added
- Added the `default_connection` config setting
- Added the `ADAPT_DEFAULT_CONNECTION` .env value
- Added the `ADAPT_CACHE_INVALIDATION_ENABLED` .env value
- Added the `cache_invalidation.locations` config setting to replace `look_for_changes_in` (which is now deprecated, but will still work)
- Added the `cache_invalidation.checksum_method` config setting to replace `cache_invalidation_method` (which is now deprecated, but will still work)
- Added the `cache_invalidation.purge_stale` config setting to replace `remove_stale_things` (which is now deprecated, but will still work)
- Added the `build_sources.initial_imports` config setting to replace `initial_imports` (which is now deprecated, but will still work)
- Added the `build_sources.migrations` config setting to replace `migrations` (which is now deprecated, but will still work)
- Added the `build_sources.seeders` config setting to replace `seeders` (which is now deprecated, but will still work)
- Added the `$transactions` config setting to replace `$reuseTransaction` (which is now deprecated, but will still work)
- Added the `$journals` config setting to replace `$reuseJournal` (which is now deprecated, but will still work)
- Added the `transaction(..)` DatabaseBuilder method to replace `reuseTransaction(..)` (which is now deprecated, but will still work)
- Added the `noTransaction()` DatabaseBuilder method to replace `noReuseTransaction()` (which is now deprecated, but will still work)
- Added the `journal(..)` DatabaseBuilder method to replace `reuseJournal(..)` (which is now deprecated, but will still work)
- Added the `noJournal()` DatabaseBuilder method to replace `noReuseJournal()` (which is now deprecated, but will still work)
- Added a check to make sure that a connection isn't being prepared more than once
- Added detection for when a test rolls-back the wrapper-transaction - throws an exception
- Improved the code that picks the default connection to use - it now happens after calling `databaseInit(..)` (instead of just setting it as soon as ->makeDefault() is called)
- Improved the logic that purges stale databases. It now only removes databases from connections that are being prepared (unused connections are ignored). Saves long connection time-outs

### Changed
- Changed the structure of the config file - Please review your copy to keep it up to date
- Deprecated the `noCacheInvalidationMethod(..)` DatabaseBuilder method
- Deprecated the `cacheInvalidationMethod()` DatabaseBuilder method
- Deprecated the `scenarios(..)` DatabaseBuilder method
- Deprecated the `noScenarios()` DatabaseBuilder method
- Seeders are now allowed to run when initial-imports exist but migrations don't
- Initial-imports are now considered when working out whether snapshots should be taken after running migrations.
- Replaced `DatabaseBuilder` with `DatabaseDefinition` when calling the user-defined `databaseInit(..)` method (using `DatabaseBuilder` is now deprecated, but will still work)

### Changed (breaking)
- Added the `ADAPT_CACHE_INVALIDATION_CHECKSUM_METHOD` .env value to replace `ADAPT_CACHE_INVALIDATION_METHOD`
- Added the `ADAPT_CACHE_INVALIDATION_PURGE_STALE` .env value to replace `ADAPT_REMOVE_STALE_THINGS`
- Removed the `$scenarios` (and deprecated `$scenarioTestDBs`) test-class properties. It can be set at the config level



## [0.11.1] - 2022-06-19

### Added
- Laravel connects to the database in some situations before Adapt runs (e.g. when using debug-bar during remote requests). Adapt now disconnects from these database connections first, so the correct database/s can be re-connected to
- Added separate storage directories for file-based databases (i.e. SQLite), snapshot files, and share-config files
- Added a GitHub issue template
- To help with debugging - The version of PHP, Laravel, PHPUnit, Pest, Adapt, MySQL, PostgreSQL, SQLite are logged
- Included when Pest is being used in the logs



## [0.11.0] - 2022-06-13

### Added
- Added `AdaptDatabase` trait to replace `LaravelAdapt` (which is now deprecated, but will still work)
- Added the `initial_imports` config setting to replace `pre_migration_imports` (which is now deprecated, but will still work)
- Added the `$initialImports` test-class property to replace `$preMigrationImports` (which is now deprecated, but will still work)
- Added the `scenarios` config setting to replace `scenario_test_dbs` (which is now deprecated, but will still work)
- Added the `$scenarios` test-class property to replace `$scenarioTestDBs` (which is now deprecated, but will still work)
- Added the `cache_invalidation_method` config setting to replace `check_for_source_changes` (which is now deprecated, but will still work)
- Added `$this->useAdapt(..)` to replace `$this->shareConfig(..)` (which is now deprecated, but will still work), when browser testing
- Added support for Laravel's `$seeder` test-class property, to indicate which seeder class to run
- Added support for `RefreshDatbase`'s `beforeRefreshingDatabase()` and `afterRefreshingDatabase()` test-class callbacks, which are now called before and after preparing the database, respectively
- Added a new log verbosity setting (0 = minimal, 2 = most details)
- Improved logging output
- Added the option to create file checksums based on their modified times
- Adapt can't run at the same time as Laravel's `RefreshDatabase`, `DatabaseTransactions` and `DatabaseMigrations` traits. Added detection for these, and when also present in a test, an exception is thrown

### Fixed
- Disabled the snapshot setting for SQLite :memory: tests

### Changed
- Renamed *build-hash*, *snapshot-hash* and *scenario-hash* to *build-checksum*, *snapshot-checksum* and *scenario-checksum* (appears in the logs)
- Changed the default method when checking for file changes from "content" to the new method "modified"

### Changed (breaking)
- Renamed the `adapt:list-db-caches` command to `adapt:list`
- Renamed the `adapt:remove-db-caches` command to `adapt:clear` (to follow the convention from Laravel's other clearing commands)
- Renamed the `ADAPT_SCENARIO_TEST_DBS` .env value to `ADAPT_SCENARIOS`
- Renamed the `ADAPT_CHECK_FOR_SOURCE_CHANGES` .env value to `ADAPT_CACHE_INVALIDATION_METHOD`



## [0.10.2] - 2022-05-22

### Added
- Added the reason why the database couldn't be reused to the logs
- Added (FORCE) when dropping a PostgreSQL database to drop it when other connections exist (falls back to the original query if FORCE isn't available in the current PostgreSQL version)



## [0.10.1] - 2022-05-21

### Added
- Support for PostgreSQL
- The database driver is now included in the list of databases when using adapt:list and adapt:remove

### Fixed
- Fixed a bug that stopped config files with a '.' in the filename to be re-initialised when reloading Laravel's config
- Updated logic so snapshot files won't be considered stale when the using a database that doesn't support reuse



## [0.10.0] - 2022-05-17

### Added
- Disabled foreign-id checks in MySQL when reverting journaled changes
- Updated the MySQL journaling code to ignore views when looking for tables to watch
- Updated the MySQL verification code to ignore views when looking for tables to check

### Fixed
- Fixed a bug that stopped the `adapt:list` and `adapt:remove` commands from working when the config isn't published
- Fixed a bug stopping SQLite databases from being used when scenarios were enabled

### Changed
- Improved logging output
- Improvements to the way SQLite :memory: databases are handled

### Changed (breaking)
- Updated the SQLite code to require that database filenames don't contain a directory part, just the filename. Now these databases are put into Adapt's `/database/adapt-test-storage` directory automatically - to include them in the housekeeping



## [0.9.2] - 2022-05-07

### Fixed
- Improved the way the service-provider registers the RemoteShareMiddelware - it's now applied it globally, instead of being added to each middleware group
- Updated the service-provider to detect requests from external instances of Adapt (instead of waiting for the middleware to run this check). When detected, it gets Laravel to use the testing environment



## [0.9.1] - 2022-04-19

### Fixed
- Fixed a re-use Journal bug when determining if a table's structure has changed, after its auto-increment value increased



## [0.9.0] - 2022-04-18

### Added
- Added **EXPERIMENTAL** *journaling* functionality for MySQL databases (needs documentation), a new way to re-use databases without using transactions
- Added **EXPERIMENTAL** *database verification* functionality for MySQL databases (needs documentation). This checks the database structure and content after each test has run, to ensure it hasn't changed. This is designed to be used as a safety-check if desired when reusing databases using *journaling* (above). This can be turned on using the `verify_databases` config setting
- Added a check to make sure the `.env.testing` file exists. Laravel falls back to using the `.env` values for its testing environment when `.env.testing` doesn't exist. This can cause unexpected results when wiping and re-building databases
- Added the `check_for_source_changes` config option which allows the `look_for_changes_in` checking to be turned off
- Added a check to see if Laravel's `--recreate-databases` option has been specified when parallel testing. If so, it throws an exception (as `php artisan adapt:remove` should be used instead)
- Added the ability when building databases remotely, for the initial build-hash to be re-used, saving on it's re-calculation during each request
- When a database has been built (or reused) remotely earlier in the test run, this database can now be re-used locally without needing to send extra http requests to build / check it again
- Added the remote-build's error message, to the exception thrown locally when a remote-build fails

### Changed
- When existing databases need to be rebuilt, they're now dropped and re-created (instead of having their contents removed by internally running Laravel's `php artisan db:wipe`)
- Tweaked the descriptions of config values
- Improved logging content, and updated durations to be rendered in milliseconds, seconds and minutes
- Renamed the remote-sharing cookie, which is used for sharing configuration settings between instances of Adapt (e.g. when browser testing), as it seems like it wasn't being passed anymore

### Deprecated
- The test-class property `reuseTestDBs` has been replaced with `$reuseTransaction`
- The config `reuse_test_dbs` setting has been replaced with `reuse.transactions`
- The Builder `reuseTestDBs()` and `noReuseTestDBs()` methods (that you might call in a test's `databaseInit(..)` method) have been replaced with `reuseTransaction()` and `noReuseTransaction()`



## [0.8.0] - 2022-02-23

### Added
- Added an exception when the migrations fail
- Added a check to make sure the local and remote Adapt installations have the same session.driver during browser tests. Otherwise, logins won't be respected by the remote codebase
- Added a check to make sure SQLite databases aren't built remotely - as it doesn't really make sense to do that
- Added a check to make sure SQLite :memory: databases aren't used when browser testing - as this won't work

### Changed
- Made improvements to the logging output
- Added extra checking around the remote-build http request process
- Improvements to the data passed between Adapt installations and processes that handle them
- Now uses Laravel's process to reload Laravel's config (e.g. when switching to the testing environment)
- Improvements when acting as a remote and switching to the testing environment

### Changed (breaking)
- Removed the `--env-file` option from the Adapt console commands
- Changed the normal logging level from "info" to "debug"

### Fixed
- Laravel normally forces `session.driver` to be "array" for tests, but not for `php artisan dusk` tests. To match this functionality, the `session.driver` from the `.env.testing` environment is picked for *browser* tests (even when not explicitly running `php artisan dusk`)
- Fixed the test-class name that's shown in the logs
- Fixed the code that imported new `.env` values, to now properly populate the `env(…)` helper with the new data
- Updated the remote-build code to pick up any url, as long as it ends in the normal remote-build path



## [0.7.0] - 2022-02-10

### Added
- Added support for Laravel 9
- Added **EXPERIMENTAL** *remote-building* functionality (needs documentation)
  - Added config setting: `remove_stale_things` (so the remote Adapt installation has a setting and can be told *not* to remove)
  - Added config setting: `remote_build_url`
  - Added test property: `$remoteBuildUrl`
- Added new helper method `initialiseAdaptIfNeeded($this)` for when Adapt needs to be initialised inside the `setUp()` method
- Added support for Laravel's `$seed = true` property
- Added support for Laravel's `$seeder = 'xyzSeeder'` property (as a string)

### Changed
- Renamed "invalid" databases + snapshot files to "stale"

### Changed (breaking)
- Removed deprecated method `$this->useCurrentConfig(…)`

### Fixed
- Fix when using `$this->newBuilder(…)` method inside a test's `->databaseInit(…)` method



## [0.6.7] - 2022-01-28

Mis-tag - updates moved into 0.7.0



## [0.6.6] - 2022-01-04

### Changed
- Updated dependencies



## [0.6.5] - 2022-01-03

### Added
- Added support for PHP 8.1



## [0.6.4] - 2021-03-07

### Added
- Added custom exception to give more details when database access is denied



## [0.6.3] - 2021-02-22

### Added
- Removed the "test_" prefix added to test-database names



## [0.6.2] - 2021-02-21

### Fixed
- Fixed a bug that caused failure on new installations - as the storage-directory was used before being checked/created



## [0.6.1] - 2021-02-20

### Fixed
- Updated the url shown in the exception thrown when the wrapper-transaction is committed

### Added
- Improved documentation



## [0.6.0] - 2021-02-19

### Changed (breaking)
- Split the snapshot options out to be controllable when and when not reusing a database
- A exception is now thrown when a test runs in a wrapper-transaction, and commits it



## [0.5.1] - 2021-02-10

### Added
- A mutex when removing invalid databases, snapshots and orphaned temp-config files
- A warning when a test commits its transaction unexpectedly - with details about the guilty test
- Write the re-use meta-data table earlier - to reduce the risk of a database not being recognised as a test-database (when the script building it exits early)
- MySQL dump files are now written to a temporary file first before being renamed

### Changed
- Updated config file comments
- Renamed the browser testing method ->useCurrentConfig($browser) to ->shareConfig($browser) - useCurrentConfig is deprecated



## [0.5.0] - 2021-02-07

### Changed (breaking)
- Removed the config value 'transaction_rollback' and test property $transactionRollback
- Merged the transaction functionality with reuse-test-dbs
- Stopped Adapt from running the "DatabaseSeeder" seeder by default - to make it a drop-in replacement for Laravel's traits

### Changed
- Re-arranged the config values
- Removed internal use of Carbon

### Added
- Added a grace period before automatically deleting invalid databases and snapshots - so now, swapping your repo branch back and forth won't remove the old databases straight away

### Fixed
- Bug stopping a sql-dump from being created when browser testing is detected



## [0.4.0] - 2021-02-03

### Changed (breaking)
- Renamed the .env value ADAPT_DYNAMIC_TEST_DBS to ADAPT_SCENARIO_TEST_DBS
- Renamed the .env value ADAPT_TRANSACTIONS to ADAPT_TRANSACTION_ROLLBACK
- Renamed the config value 'dynamic_test_dbs' to 'scenario_test_dbs'
- Renamed the config value 'transactions' to 'transaction_rollback'
- Renamed the test property $dynamicTestDBs to $scenarioTestDBs
- Renamed the test property $transactions to $transactionRollback



## [0.3.4] - 2021-02-01

### Added
- Updated the ServiceProvider to add AdaptMiddleware to the middleware-groups that exist, instead of arbitrarily picking 'api' and 'web'



## [0.3.3] - 2021-01-22

### Added
- Added a fix when the middleware re-sets cookies during browser testing.



## [0.3.2] - 2021-01-19

### Added
- Added the ability to turn database-building off (useful for Dusk browser tests with no database)
- Allow Dusk tests to pass a config to the browser more than once
- Added the ability to change the connection a DatabaseBuilder connects to (e.g. to get the first Builder build for a non "default" connection)
- Improved documentation



## [0.3.1] - 2021-01-18

### Added
- Added functionality for each test's config settings to be passed to the server via the browser during Dusk browser tests
- Added the ability for ParaTest to run Dusk browser testss



## [0.3.0] - 2021-01-15

### Added
- Added prefix "test_" to test databases e.g. "test_your_database_name_17bd3c_d266ab43ac75"

### Changed (breaking)
- Changed the name of the config file from code-distortion.adapt.php to code_distortion.adapt.php to be consistent with Laravel
- Updated config keys to contain underscores instead of hyphens to be consistent with Laravel

### Fixed

- the wording of the log message when removing an old database



## [0.2.4] - 2021-01-06

### Added
- Added support for PHP 8.0
- PSR12 formatting
- Documentation updates



## [0.2.3] - 2020-09-09

### Added
- Bumped dependencies and added test coverage to include Laravel 8



## [0.2.2] - 2020-09-06

### Added
- ParaTest's TEST_TOKEN is now detected so that separate databases are created for each para-test instance (see https://github.com/paratestphp/paratest#test-token)

### Changed
- Refactored code and increased test coverage



## [0.2.1] - 2020-08-26

### Added
- Improved test coverage



## [0.2.0] - 2020-08-20

### Added
- Documentation for use with PEST

### Changed (breaking)
- Changed the name of the service provider class from LaravelServiceProvider to AdaptLaravelServiceProvider

### Fixed
- Migration paths can be absolute, or relative to the base of your project. Before, when using Laravel < 5.6 these directories had to be relative



## [0.1.4] - 2020-08-19

### Added
- Adapt is booted automatically now when the LaravelAdapt trait is present in a test (removed the need to update the base TestCase class setUp() method to initialise Adapt)



## [0.1.3] - 2020-08-19

### Added
- Added MacOS to the GitHub actions to provide better test coverage



## [0.1.2] - 2020-08-19

### Added
- Updated the documentation to show that Laravel is supported as far back as 5.1 to match the test coverage
- Improvements to documentation

### Changed
- Turned snapshots off by default in the config but turn on automatically when a browser test is detected

### Fixed
- The artisan command lists show the size of mysql databases



## [0.1.1] - 2020-08-17

### Changed
- Boot test code bug fix



## [0.1.0] - 2020-08-17

### Added
- Beta release
