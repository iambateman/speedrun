---
name: describe
description: Describe an existing feature in the codebase
arguments: "[feature-name]"
---

# Describe Feature Command

This command analyzes and describes existing features in the codebase.

## Usage

- `/describe` - Will prompt you to specify which feature to describe
- `/describe feature-name` - Directly describe the specified feature

## What This Command Does

1. **Prompts for feature**: If no feature is specified, asks what you want to describe
2. **Analyzes codebase**: Examines the existing code to understand the feature
3. **Provides description**: Returns a detailed explanation of how the feature works

## Implementation

This command routes to: `php artisan speedrun:describe-feature $ARGUMENTS`

The artisan command will:
- Ask what feature you want to describe (if not provided)
- Analyze the codebase to understand the feature
- Provide a comprehensive description of the feature's implementation