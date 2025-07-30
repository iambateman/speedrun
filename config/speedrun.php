<?php

// config for Iambateman/Speedrun
return [

    /*
    |--------------------------------------------------------------------------
    | Installation Status
    |--------------------------------------------------------------------------
    |
    | Tracks whether the package has been installed and configured.
    |
    */
    'installed' => false,

    /*
    |--------------------------------------------------------------------------
    | Sample
    |--------------------------------------------------------------------------
    |
    | This is where we put config so pepole can understand it.
    |
    */
    'sample_config' => true,

    /*
    |--------------------------------------------------------------------------
    | Feature Management
    |--------------------------------------------------------------------------
    |
    | Configuration for the feature lifecycle management system.
    |
    */
    'features' => [
        /*
        |--------------------------------------------------------------------------
        | Feature Directories
        |--------------------------------------------------------------------------
        |
        | Configure the directories where features are stored during different
        | phases of their lifecycle.
        |
        */
        'directories' => [
            'wip' => env('SPEEDRUN_WIP_DIR', '_docs/wip'),
            'completed' => env('SPEEDRUN_COMPLETED_DIR', '_docs/features'),
            'archive' => env('SPEEDRUN_ARCHIVE_DIR', '_docs/archive'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Phase Transitions
        |--------------------------------------------------------------------------
        |
        | Configure whether to require explicit confirmation for phase transitions
        | and whether to auto-commit changes at each phase.
        |
        */
        'transitions' => [
            'require_confirmation' => env('SPEEDRUN_REQUIRE_CONFIRMATION', true),
            'auto_commit' => env('SPEEDRUN_AUTO_COMMIT', false),
            'commit_message_template' => 'Feature {feature}: {phase} phase completed',
        ],

        /*
        |--------------------------------------------------------------------------
        | File Naming Conventions
        |--------------------------------------------------------------------------
        |
        | Configure the naming patterns for different types of files.
        |
        */
        'naming' => [
            'feature_prefix' => '_',
            'planning_prefix' => '_plan_',
            'allowed_characters' => '/^[a-z0-9\-]+$/',
        ],

        /*
        |--------------------------------------------------------------------------
        | Lock Settings
        |--------------------------------------------------------------------------
        |
        | Configure file locking to prevent concurrent edits.
        |
        */
        'locking' => [
            'enabled' => env('SPEEDRUN_LOCKING_ENABLED', true),
            'timeout_minutes' => env('SPEEDRUN_LOCK_TIMEOUT', 60),
            'lock_file_suffix' => '.lock',
        ],

        /*
        |--------------------------------------------------------------------------
        | Claude Code Integration
        |--------------------------------------------------------------------------
        |
        | Settings specific to Claude Code command integration.
        |
        */
        'claude' => [
            'commands_path' => '.claude/commands',
            'argument_placeholder' => '$ARGUMENTS',
        ],

        /*
        |--------------------------------------------------------------------------
        | Cleanup Behavior
        |--------------------------------------------------------------------------
        |
        | Configure how the cleanup phase handles different types of artifacts.
        |
        */
        'cleanup' => [
            'keep_planning_docs' => env('SPEEDRUN_KEEP_PLANNING', false),
            'archive_on_cleanup' => env('SPEEDRUN_ARCHIVE_ON_CLEANUP', true),
            'planning_docs_to_keep' => [
                '_plan_architecture.md',
                '_plan_database.md',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Prompts Configuration
        |--------------------------------------------------------------------------
        |
        | Configure Laravel Prompts behavior and styling.
        |
        */
        'prompts' => [
            'search_placeholder' => 'Search features...',
            'confirm_style' => 'default', // 'default', 'warning', 'error'
            'max_search_results' => 10,
            'require_input' => env('SPEEDRUN_REQUIRE_INPUT', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Codebase Analysis Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the codebase describe functionality that analyzes existing
    | Laravel applications and generates feature definitions.
    |
    */
    'describe' => [
        /*
        |--------------------------------------------------------------------------
        | Output Directory
        |--------------------------------------------------------------------------
        |
        | Directory where discovered features will be saved. Features are
        | organized into subdirectories by feature name.
        |
        */
        'output_directory' => env('SPEEDRUN_DESCRIBE_OUTPUT', 'features/discovered'),

        /*
        |--------------------------------------------------------------------------
        | Analysis Depth
        |--------------------------------------------------------------------------
        |
        | Controls how deep the analysis goes when examining relationships
        | between components. Higher values provide more detail but take longer.
        |
        */
        'analysis_depth' => env('SPEEDRUN_ANALYSIS_DEPTH', 3),

        /*
        |--------------------------------------------------------------------------
        | Component Analysis
        |--------------------------------------------------------------------------
        |
        | Configure which types of components to include in the analysis.
        |
        */
        'include_tests' => env('SPEEDRUN_INCLUDE_TESTS', true),
        'include_views' => env('SPEEDRUN_INCLUDE_VIEWS', true),
        'include_migrations' => env('SPEEDRUN_INCLUDE_MIGRATIONS', false),

        /*
        |--------------------------------------------------------------------------
        | Exclusion Patterns
        |--------------------------------------------------------------------------
        |
        | File and directory patterns to exclude from analysis. Uses glob patterns.
        |
        */
        'exclude_patterns' => [
            'vendor/*',
            'node_modules/*',
            'storage/*',
            '.git/*',
            'public/storage/*',
            'bootstrap/cache/*',
        ],

        /*
        |--------------------------------------------------------------------------
        | AI Enhancement
        |--------------------------------------------------------------------------
        |
        | Enable AI-powered enhancement of generated feature descriptions.
        | Requires SPEEDRUN_OPENAI_API_KEY to be set.
        |
        */
        'ai_enhancement' => env('SPEEDRUN_AI_ENHANCEMENT', false),
        'openai_model' => env('SPEEDRUN_OPENAI_MODEL', 'gpt-3.5-turbo'),

        /*
        |--------------------------------------------------------------------------
        | Feature Grouping Rules
        |--------------------------------------------------------------------------
        |
        | Configure how components are grouped into logical features.
        |
        */
        'grouping' => [
            'by_route_prefix' => true,
            'by_controller_name' => true,
            'by_model_relationships' => true,
            'merge_related_features' => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | Output Format
        |--------------------------------------------------------------------------
        |
        | Configure the format and structure of generated feature files.
        |
        */
        'output_format' => [
            'include_code_examples' => env('SPEEDRUN_INCLUDE_CODE_EXAMPLES', false),
            'include_route_details' => env('SPEEDRUN_INCLUDE_ROUTE_DETAILS', true),
            'include_test_summaries' => env('SPEEDRUN_INCLUDE_TEST_SUMMARIES', true),
            'generate_improvement_suggestions' => env('SPEEDRUN_GENERATE_IMPROVEMENTS', false),
        ],
    ],
];
