# Talk to Laravel Artisan using GPT 

[![Latest Version on Packagist](https://img.shields.io/packagist/v/iambateman/speedrun.svg?style=flat-square)](https://packagist.org/packages/iambateman/speedrun)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/iambateman/speedrun/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/iambateman/speedrun/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/iambateman/speedrun/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/iambateman/speedrun/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/iambateman/speedrun.svg?style=flat-square)](https://packagist.org/packages/iambateman/speedrun)

Instead of typing `php artisan make:model Keyword -m`, use Speedrun to remember the syntax for you. Just ask for what you want and we will use GPT to figure it out!

## Installation

```bash
composer require iambateman/speedrun
```

```dotenv
# Set an OpenAI API Key in your .env
SPEEDRUN_OPENAI_API_KEY=sk-...
```




## Usage

```bash
# Speedrun is a fancy Laravel command, so
# out of the box, you would write...

php artisan speedrun how many articles are there

# But if you alias it to `sr`, you get...

sr how many articles are there

# You can write the worst Eloquent queries ever
sr "query family(12)->members get name"


```

## Example commands to try
I was blown away the first time I tried these...it's wild.
```bash
# COMPOSER REQUIRE
sr install laravel excel
sr install filament

# PHP ARTISAN
sr make keyword model with migration and factory
sr start queue

# CUSTOM APP COMMANDS (contextual to your app)
sr generate sitemap
sr write articles for city 2

# QUERY YOUR DATABASE (contextual, as well)
sr when did the newest user sign up
sr how many articles are attached to city 2

```

## Testing

```bash
Tests are coming soon. Don't judge me, I have a six-month old baby.
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- Speedrun: [iambateman](https://github.com/iambateman)
- None of this would be possible without the wonderful work of so many people on Artisan, Tinker, Composer, and Laravel in general. Thank you for what you do.
- [All Contributors](../../contributors)

## Config
Right now there is very limited config, but you can publish if you want:
```bash
# (Optionally) publish config.
php artisan vendor:publish --tag="speedrun-config"
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
