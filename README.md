# Build your Laravel app with Claude Coe using `/feature`

[![Latest Version on Packagist](https://img.shields.io/packagist/v/iambateman/speedrun.svg?style=flat-square)](https://packagist.org/packages/iambateman/speedrun)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/iambateman/speedrun/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/iambateman/speedrun/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/iambateman/speedrun/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/iambateman/speedrun/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/iambateman/speedrun.svg?style=flat-square)](https://packagist.org/packages/iambateman/speedrun)

Speedrun helps developers use Claude Code to (1) generate new features and (2) update existing features inside a Laravel project.

## Installation

```bash
composer require iambateman/speedrun
php artisan speedrun:install
```
## Alpha warning
This is *alpha* work which is actively being adjusted. Right now, it feels a little slower than it should...like the subagents are doing more work than I want them to do. 

## Usage
1. In terminal, type `claude` to open Claude Code.
2. type `/feature` with a description of the feature you want to make.

## How it works
Claude often fails to implement entire code files and instead "stubs out" code. It also tends to make too many changes inside a project. This package creates a workflow for Claude to keep the developer in the "driver's seat" and reduce the time spent fixing broken code.

This framework is adapted from Anthropic's best practices for working with agents.

- **START**: Call `/feature` inside of Claude Code to create a new feature folder.
- **DISCOVER:** Claude asks the developer to write what they know about the feature. Then, Claude will do it's own discovery.
- **PLAN:** Claude will create working documents inside the feature folder with sample code which will eventually go into the app. This is a "staging" setup, which gives the dev an opportunity to scan through all changes in one place.
- **EXECUTE:** Claude will use the plan to incorporate the feature into the app.
- **CLEAN:** Claude will offer to delete the feature folder so we don't have sample code lying around.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- Speedrun: [iambateman](https://github.com/iambateman)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
