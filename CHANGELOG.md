# Changelog

All notable changes to `code-distortion/adapt` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).



## [0.6.4] - 2021-03-07

### Added
- Added custom exception to give more details when database access is denied.



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
- Added the ability to change the connection a DatabaseBuilder connects to (eg. to get the first Builder build for a non "default" connection)
- Improved documentation



## [0.3.1] - 2021-01-18

### Added
- Added functionality for each test's config settings to be passed to the server via the browser during Dusk browser tests
- Added the ability for ParaTest to run Dusk browser testss



## [0.3.0] - 2021-01-15

### Added
- Added prefix "test_" to test databases eg. "test_your_database_name_17bd3c_d266ab43ac75"

### Changed (breaking)
- Changed the name of the config file from code-distortion.adapt.php to code_distortion.adapt.php to be consistent with Laravel
- Updated config keys to contain underscores instead of hyphens to be consistent with Laravel

### Fixed

- the wording of the log message when removing an old database



## [0.2.4] - 2021-01-06

### Added
- Support for PHP 8.0
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
