# Changelog

All notable changes to `code-distortion/adapt` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).



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
