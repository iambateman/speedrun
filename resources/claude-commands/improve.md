---
name: improve
description: Improve an existing completed feature
arguments: "[feature-name]"
---

# Feature Improvement Command

This command allows you to revisit and improve features that have already been completed.

## Usage

- `/improve` - Search through completed features to select one for improvement
- `/improve user-authentication` - Directly improve a specific completed feature

## What This Command Does

1. **Without arguments**: Opens an interactive search of completed features
2. **With feature name**: Directly selects the feature for improvement

Once a feature is selected:
- Moves it from `/_docs/features/` back to `/_docs/wip/`
- Resets the phase to "description" 
- Prompts for what you want to improve
- Begins the improvement workflow

## Common Improvement Scenarios

- Adding validation or error handling
- Improving performance
- Adding tests
- Refactoring for better code organization
- Adding new sub-features or capabilities
- Updating documentation

## Example Workflow

```bash
# Browse completed features to improve
/improve

# Directly improve a specific feature
/improve user-authentication

# You'll then be asked:
# "What would you like to improve about this feature?"
# > Add two-factor authentication support
```

## Implementation

This command routes to: `php artisan speedrun:feature-improve $ARGUMENTS`

The artisan command will:
- Search for the feature in the completed features directory
- Move it back to work-in-progress
- Record the improvement goal
- Begin the improvement workflow starting from the description phase

## Important Notes

- Only completed features can be improved
- The original feature implementation remains intact during improvement
- Improvement history is tracked in the feature description file
- You can improve a feature multiple times