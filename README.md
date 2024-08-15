# Adapt - A Database Preparation Tool

[![Latest Version on Packagist](https://img.shields.io/packagist/v/code-distortion/adapt.svg?style=flat-square)](https://packagist.org/packages/code-distortion/adapt)
![PHP Version](https://img.shields.io/badge/PHP-7.0%20to%208.3-blue?style=flat-square)
![Laravel](https://img.shields.io/badge/laravel-5.1+%2C%206%2C%207%2C%208%2C%209%2C%2010%20%26%2011-blue?style=flat-square)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/code-distortion/adapt/branch-master-tests.yml?branch=master&style=flat-square)](https://github.com/code-distortion/adapt/actions)
[![Buy The World a Tree](https://img.shields.io/badge/treeware-%F0%9F%8C%B3-lightgreen?style=flat-square)](https://plant.treeware.earth/code-distortion/adapt)
[![Contributor Covenant](https://img.shields.io/badge/contributor%20covenant-v2.1%20adopted-ff69b4.svg?style=flat-square)](.github/CODE_OF_CONDUCT.md)



**code-distortion/adapt** is a package for [Laravel](https://laravel.com/) that *builds databases for your tests*.



## Features

- Adapt is a [swap-in replacement](https://code-distortion.net/docs/adapt/usage/) for Laravel's `RefreshDatabase`, `DatabaseMigrations`, and `DatabaseTransactions` traits.
- To get the best speeds, your [databases are reused](https://code-distortion.net/docs/adapt/reusing-databases/) (when possible) each time you run your tests.
- A new (experimental MySQL) [journaling method](https://code-distortion.net/docs/adapt/reusing-databases/#journaling) for reusing databases. This is an alternative for when [transactions](https://code-distortion.net/docs/adapt/reusing-databases/#transactions) can't be used (like when browser testing).
- You don't need to create empty databases beforehand. They're [created automatically](https://code-distortion.net/docs/adapt/building-a-database/).
- There's no need to drop or rebuild databases yourself. They are [automatically rebuilt](https://code-distortion.net/docs/adapt/building-a-database/#rebuilding-your-database) when you change your migrations, seeders or factories.
- Lets you [import](https://code-distortion.net/docs/adapt/building-a-database/#imports) custom sql-dump files before running your [migrations](https://code-distortion.net/docs/adapt/building-a-database/#migrations).
- Your tests can use different [seeders](https://code-distortion.net/docs/adapt/building-a-database/#seeders) for different tests, without them needing to be re-run each time.
- You can include [Dusk browser tests](https://code-distortion.net/docs/adapt/browser-testing/) in your normal test run - there's no need to run `php artisan dusk` separately.
- You can [run your tests in parallel](https://code-distortion.net/docs/adapt/parallel-testing/), separate databases are created for each process.
- You can also run [Dusk browser tests](https://code-distortion.net/docs/adapt/browser-testing/) in parallel.
- If your project [has more than one database](https://code-distortion.net/docs/adapt/building-a-database/#building-extra-databases), you can build them as well. Each with their own migrations and seeders.
- If you have two or more Laravel codebases in your project, you can have one [build databases](https://code-distortion.net/docs/adapt/remote-databases/#building-databases-remotely) for the others.



## Documentation

The documentation for this package has a [its own dedicated page](https://code-distortion.net/packages/adapt). Please look there for details on how to install and configure Adapt.



## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.



### SemVer

This library uses [SemVer 2.0.0](https://semver.org/) versioning. This means that changes to `X` indicate a breaking change: `0.0.X`, `0.X.y`, `X.y.z`. When this library changes to version 1.0.0, 2.0.0 and so forth, it doesn't indicate that it's necessarily a notable release, it simply indicates that the changes were breaking.



## Testing

``` bash
composer test
```



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
