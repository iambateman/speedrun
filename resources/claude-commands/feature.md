---
name: feature
description: Create a new feature
arguments: "[feature-name]"
---

# Context

This command helps you manage the complete lifecycle of a feature from discovery through implementation.

## Your task
- if the feature name wasn't included, ask the user for the feature name.
- run `php artisan speedrun:feature:init {slug}` (slug is a short, snake-case name of the feature)
  - This creates the feature file and feature folder.
- DISCOVER
  - Pause and tell the user to fill in the feature file you just created with as much detail as they have.
  - When they've updated, do your own discovery...browse the application and database to identify relevant tests, models, routes, and packages which will help you build this feature. Store your discovery in the feature file. Your notes in the feature file should use only bullets and titles for formatting and be as direct and concise as possible.
- PLAN
  - Pause and ask if they're ready for a plan.
  - Write detailed individual markdown files in the working folder with explanations for what to do and sample code below. Each code file gets it's own Markdown file with a complete implementation. If you can't write a complete, working implementation, note that in the feature file.
  - It's encouraged to write test PHP scripts in the feature folder to understand the app better. Do NOT write to or alter the database or connect to external API's without express permission from the developer.
  - Periodically, go back to the feature file and reassess if your plan is fully accomplishing the stated goals.
- EXECUTE
  - Pause and ask if they're ready to execute the plan.
  - Do the plan and iteratively work with the developer until they accept the changes.
- CLEAN UP
  - Pause and ask if they're ready to clean up.
  - run `php artisan speedrun:feature:clean {slug}` to remove the working directory.

IMPORTANT: at first, you're in a discover phase, not a coding phase. Do not recommend creating new code in the feature folder until entering the "plan" phase and do not implement code in the project itself until the "execute" phase. Success for discover phase looks like creating a great markdown file with requirements, and working with the user to understand their goals, objectives, and context.
