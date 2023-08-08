<?php

namespace Iambateman\Speedrun\Helpers;

use Iambateman\Speedrun\DTO\Tool;
use Iambateman\Speedrun\Speedrun;

class ToolList {

    public static function get()
    {
           return Speedrun::getTools();
    }

    public static function initialize()
    {
        Speedrun::registerTools([
            new Tool(
                description: 'Check a particular file for bugs',
                command: 'php artisan speedrun:check-for-bugs',
                parameters: [
                    'file_path' => 'full system path of file',
                    'particular_checks' => 'Extra notes for what specific bugs to check for.'
                ]
            ),

            new Tool(
                description: 'Get a list of all models currently in the app.',
                command: 'php artisan speedrun:get-models'
            ),

            new Tool(
                description: 'Generate Filament admin panel for models',
                command: 'php artisan speedrun:generate-filament-for-models',
                parameters: [
                    'models' => 'Optional list of Laravel models to generate. Omitting this parameter generates all.'
                ]
            ),

//            new Tool(
//                description: 'Make Factory for generating models in tests',
//                command: 'php artisan speedrun:make-factory',
//                parameters: [
//                    'models' => 'Optional list of Laravel models to generate factories. Omitting this parameter generates for all models.'
//                ]
//            ),

//            new Tool(
//                description: 'Make Many to Many Migrations for all relevant relationships in the task.',
//                command: 'php artisan speedrun:make-many-to-many-migrations'
//            ),

//            new Tool(
//                description: 'Make migration for model',
//                command: 'php artisan speedrun:make-migration-to-create-model',
//                parameters: [
//                    'model_name' => 'Required model name to create a migration for'
//                ]
//            ),
//
//            new Tool(
//                description: 'Make model',
//                command: 'php artisan speedrun:make-model',
//                parameters: [
//                    'model_name' => 'One or more model names to create. Separating multiple models with spaces creates all of them.'
//                ]
//            ),
//
//            new Tool(
//                description: 'Make test for model',
//                command: 'php artisan speedrun:make-test-for-model',
//                parameters: [
//                    'model_name' => 'One or more model names to create. Separating multiple models with spaces creates all of them.'
//                ]
//            ),
        ]);
    }

}