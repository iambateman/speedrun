<?php

// config for Iambateman/Speedrun
return [

    /*
    |--------------------------------------------------------------------------
    | Double confirm before executing
    |--------------------------------------------------------------------------
    |
    | We ask GPT to suggest a command, but we can double confirm before
    | running anything. This is a good idea to leave true until you
    | are comfortable with how the package works.
    |
    */
    'doubleConfirm' => true,

    /*
    |--------------------------------------------------------------------------
    | Include Custom Application Commands
    |--------------------------------------------------------------------------
    |
    | We can send GPT a list of your commands and parameters to allow
    | you to invoke them with natural language. If you'd rather
    | GPT not have that info, turn this off.
    |
    */
    'includeAppCommands' => true,

    /*
    |--------------------------------------------------------------------------
    | Do not use in Production
    |--------------------------------------------------------------------------
    |
    | So...don't use this in production.
    |
    */
    'dieInProduction' => true,

    /*
    |--------------------------------------------------------------------------
    | Open on make
    |--------------------------------------------------------------------------
    |
    | It's convenient to open some files in your editor by default.
    |
    */
    'openOnMake' => env('OPEN_ON_MAKE', true),

    'codeEditor' => env('CODE_EDITOR', 'phpstorm'),
];
