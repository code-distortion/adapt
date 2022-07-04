# Adapt - A Database Preparation Tool

[![Latest Version on Packagist](https://img.shields.io/packagist/v/code-distortion/adapt.svg?style=flat-square)](https://packagist.org/packages/code-distortion/adapt)
![PHP Version](https://img.shields.io/badge/PHP-7.0%20to%208.1-blue?style=flat-square)
![Laravel](https://img.shields.io/badge/laravel-5.1+%2C%206%2C%207%2C%208%20%26%209-blue?style=flat-square)
[![GitHub Workflow Status](https://img.shields.io/github/workflow/status/code-distortion/adapt/branch-master-tests?label=tests&style=flat-square)](https://github.com/code-distortion/adapt/actions)
[![Buy The World a Tree](https://img.shields.io/badge/treeware-%F0%9F%8C%B3-lightgreen?style=flat-square)](https://plant.treeware.earth/code-distortion/adapt)
[![Contributor Covenant](https://img.shields.io/badge/contributor%20covenant-v2.0%20adopted-ff69b4.svg?style=flat-square)](.github/CODE_OF_CONDUCT.md)



***code-distortion/adapt*** is a package for [Laravel](https://laravel.com/) that *builds databases for tests*.

It unifies the process of building databases, to help you get the most out of them.



## Features

- Adapt is a [swap-in replacement](https://code-distortion.net/packages/adapt/usage/) for Laravel's `RefreshDatabase`, `DatabaseMigrations`, and `DatabaseTransactions` traits.
- It [creates your test databases](https://code-distortion.net/packages/adapt/building-a-database/) for you. You don't need to beforehand.
- Databases are [reused between tests](https://code-distortion.net/packages/adapt/reusing-databases/) within the *same test-run*, and are also reused *the next time you run your tests* - no start-up delay (regardless of whether you're running parallel tests or not).
- Introduces a new (experimental) [journaling method](https://code-distortion.net/packages/adapt/reusing-databases/#journaling) for reusing databases, as an alternative when [transactions](https://code-distortion.net/packages/adapt/reusing-databases/#transactions) can't be used, like when browser testing.
- Databases are [checked before re-use](https://code-distortion.net/packages/adapt/reusing-databases/) to make sure they were left in a clean state - for example if the transaction a test is wrapped in gets committed during a test, the database won't be clean. It will be rebuilt.
- Incorporates your [seeders](https://code-distortion.net/packages/adapt/building-a-database/#seeders) into its caching system. Different tests can use different seeders without causing the database to be rebuilt each time.
- Changes to your migrations, seeders and factories are detected, and [databases are automatically rebuilt](https://code-distortion.net/packages/adapt/building-a-database/#rebuilding-your-database) the next time they're needed (no need to specify `--recreate-databases` manually). Handy when changing branches.
- Supports [parallel testing](https://code-distortion.net/packages/adapt/parallel-testing/) by creating separate databases for each process.
- Include [Dusk browser tests](https://code-distortion.net/packages/adapt/browser-testing/) as a normal test-suite (no need to run `php artisan dusk` separately). They can also be run in *parallel!*
- It's got you covered if your project [has more than one database](https://code-distortion.net/packages/adapt/building-a-database/#building-extra-databases) - you can specify extra databases to build, along with their own migrations and seeders.
- If you have two or more Laravel codebases in your project (e.g. when using a microservices architecture, or implementing the Strangler Fig Pattern), it may be useful for one of them to build test-databases for the other/s. Adapt supports this via [remote building](https://code-distortion.net/packages/adapt/remote-databases/#building-databases-remotely).
- No need to update your test `setUp()` methods.



## Documentation

The documentation for this package has been [moved to its own dedicated page](https://code-distortion.net/packages/adapt). Please look there for details on how to install and configure Adapt. 



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

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.



### Code of Conduct

Please see [CODE_OF_CONDUCT](.github/CODE_OF_CONDUCT.md) for details.



### Security

If you discover any security related issues, please email tim@code-distortion.net instead of using the issue tracker.



## Credits

- [Tim Chandler](https://github.com/code-distortion)



## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
