# Talk to Laravel Artisan using GPT 

[![Latest Version on Packagist](https://img.shields.io/packagist/v/iambateman/speedrun.svg?style=flat-square)](https://packagist.org/packages/iambateman/speedrun)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/iambateman/speedrun/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/iambateman/speedrun/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/iambateman/speedrun/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/iambateman/speedrun/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/iambateman/speedrun.svg?style=flat-square)](https://packagist.org/packages/iambateman/speedrun)

Speedrun helps developers use Claude Code to (1) generate new features and (2) update existing features inside a Laravel project.

## Installation

```bash
composer require iambateman/speedrun
```

## Usage
1. Open Claude Code
2. type `/feature` with a brief description of the feature you want to make.

## Conceptual Framework
As developers, we need visibility into every step in Claude's thinking to avoid runaway code generation. Otherwise, it's very likely that Claude will accidentally create code that doesn't work. This package enforces clear guidelines for Claude to keep the developer in the "driver's seat" and reduce the time spent fixing broken code.

This framework is adapted from Anthropic's recommended best practices for working with agents.

#### START
To start, call `/feature` inside of Claude Code to create a new feature folder. This kicks off a four-step process:

#### (1) DISCOVER
Claude asks the developer to write what they know about the feature. Then, Claude will do it's own discovery.

#### (2) PLAN
Claude will create working documents inside the feature folder with sample code which will eventually go into the app. This is a nice "staging" setup, which gives the dev an opportunity to easily scan through many documents in one place.

#### (3) EXECUTE
Claude will use the plan to incorporate the feature into the app. It will run tests, too.

#### (4) CLEAN
Claude will ask you and then delete the feature folder. This is important because unused sample code makes it hard for Claude to search the project, and that sample code becomes stale.

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
