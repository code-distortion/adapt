# Contributing

Please read and understand the contribution guide before creating an issue or pull request.

Contributions will be fully credited.



### Please Note

- One of the maintainers' goals is to keep the project concise, so not all PRs will be accepted.
- Maintainers will need to maintain new code for its lifetime, so discretion is used when considering a PR for acceptance.
- If you're unsure, feel free to drop us a line first.



## Etiquette

This project is open source, and as such, the maintainers give their free time to build and maintain the source code held within. They make the code freely available in the hope that it will be of use to other developers. It would be extremely unfair for them to suffer abuse or anger for their hard work.

Please be considerate towards maintainers when raising issues or presenting pull requests. Let's show the world that developers are civilized and selfless people.

It's the duty of the maintainer to ensure that all submissions to the project are of sufficient quality to benefit the project. Many developers have different skillsets, strengths, and weaknesses. Respect the maintainer's decision, and do not be upset or abusive if your submission is not used.



## Viability

When requesting or submitting new features, first consider whether it might be useful to others. Open source projects are used by many developers, who may have entirely different needs to your own. Think about whether or not your feature is likely to be used by other users of the project.



## Procedure

Before filing an issue:

- Attempt to replicate the problem, to ensure that it wasn't a coincidental incident.
- Check to make sure your feature suggestion isn't already present within the project.
- Check the pull requests tab to ensure that the bug doesn't have a fix in progress.
- Check the pull requests tab to ensure that the feature isn't already in progress.

Before submitting a pull request:

- Check the codebase to ensure that your feature doesn't already exist.
- Please raise an issue to discuss the problem first.
- Check the pull requests to ensure that another person hasn't already submitted the feature or fix.



## Requirements

- **Code is developed in branch `latest`** - This is so it can be coded for the latest PHP version. The code is transliterated back to PHP 7.0 before releasing.

- *[PSR-12 Coding Standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-12-extended-coding-style-guide.md)* where possible - falling back to [PSR-2 Coding Standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md) otherwise - The easiest way to apply the conventions is to install [PHP Code Sniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer).

- **Add tests!** - Your patch won't be accepted if it doesn't have tests.

- **Document any change in behaviour** - Clearly note any changes, so [Adapt's documentation](https://code-distortion.net/packages/adapt) can be updated.

- **Consider our release cycle** - We try to follow [SemVer v2.0.0](https://semver.org/). Randomly breaking public APIs is to be avoided.

- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.

- **Coherent history** - Make sure each individual commit in your pull request is meaningful. If you had to make multiple intermediate commits while developing, please [squash them](https://www.git-scm.com/book/en/v2/Git-Tools-Rewriting-History#Changing-Multiple-Commit-Messages) before submitting.
