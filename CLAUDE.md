# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Speedrun is a Laravel package that uses GPT to interpret natural language commands and execute appropriate Laravel actions. It acts as an intelligent command-line interface that understands what developers want to do and translates that into the correct artisan commands, queries, or package installations.

## Key Commands

### Testing
```bash
# Run all tests using Pest
vendor/bin/pest

# Run tests with coverage
vendor/bin/pest --coverage

# Run a specific test file
vendor/bin/pest tests/ExampleTest.php
```

### Code Quality
```bash
# Format code using Laravel Pint
vendor/bin/pint

# Run static analysis
vendor/bin/phpstan analyse
```

### Package Development
```bash
# Install dependencies
composer install

# Test package discovery
composer post-autoload-dump
```

## Architecture

### Core Components

1. **SpeedrunCommand** (`src/Commands/SpeedrunCommand.php`): Main entry point that routes natural language input to appropriate sub-commands based on keyword matching.

2. **Command Router Pattern**: Uses pattern matching on first words to determine action type:
   - `make/create/add` → Creation commands
   - `run/start/go` → Task execution
   - `install/require/composer` → Package installation
   - `who/what/when/where/how/find/query` → Database queries
   - Default → Artisan command execution

3. **Service Provider** (`src/SpeedrunServiceProvider.php`): Registers commands and configures the package using Spatie's Laravel Package Tools.

4. **AI Integration**: 
   - Uses OpenAI GPT API (configured via `SPEEDRUN_OPENAI_API_KEY`)
   - Model selection through `Speedrun::getModel()` (defaults to gpt-3.5-turbo)

### Key Design Patterns

1. **Production Safety**: All commands check `Helpers::dieInProduction()` to prevent accidental execution in production environments.

2. **Configuration**: Minimal config published to `config/speedrun.php`, with most settings handled via environment variables.

3. **Extensibility**: Package designed to be extended with custom tools and view templates through registration methods in the `Speedrun` facade.

### File Structure

- `/src/Commands/` - Command classes for different operations
- `/src/Actions/` - Business logic actions (currently being refactored based on git status)
- `/src/Helpers/` - Utility functions
- `/src/DTO/` - Data transfer objects
- `/src/Exceptions/` - Custom exception classes
- `/resources/stubs/` - Template files for code generation

## Important Considerations

1. **API Key Required**: Package requires OpenAI API key set as `SPEEDRUN_OPENAI_API_KEY` in `.env`

2. **Production Safety**: Package includes production safeguards to prevent accidental execution

3. **Laravel Actions**: Uses lorisleiva/laravel-actions for organizing business logic

4. **Testing Framework**: Uses Pest PHP for testing

5. **Package in Active Development**: Based on git status, significant refactoring is in progress with many files being removed/reorganized