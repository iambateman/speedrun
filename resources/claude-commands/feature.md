---
name: feature
description: Manage feature lifecycle - create new features or resume existing ones
arguments: "[feature-name]"
---

# Feature Management Command

This command helps you manage the complete lifecycle of a feature from discovery through implementation.

## Usage

- `/feature` - Browse existing features or create a new one
- `/feature my-feature` - Resume work on an existing feature or create it if it doesn't exist

## What This Command Does

## Feature Lifecycle Phases

1. **Discovery** - Search existing features or define a new one
2. **Description** - Collaboratively define requirements and approach with AI assistance  
3. **Planning** - Create detailed implementation plans as markdown documents
4. **Execution** - Convert plans into actual code files
5. **Cleanup** - Remove unnecessary planning artifacts and finalize


## Implementation

This command routes to: `php artisan speedrun:feature $ARGUMENTS`

The artisan command will:
- Check if a feature with the given name exists
- If it exists, detect its current phase and resume appropriately
- If it doesn't exist, start the discovery/creation process
- Use Laravel Prompts for all user interactions

## Directory Structure

Features are organized as follows:
- Active development: `/_docs/wip/{feature-name}/`
- Completed features: `/_docs/features/{feature-name}/`
- Archived features: `/_docs/archive/{feature-name}/`

Each feature directory contains:
- `_{feature-name}.md` - Main feature description and state
- `planning/` - Planning phase documents
- `research/` - Research and reference materials
- `assets/` - Diagrams, mockups, etc.
