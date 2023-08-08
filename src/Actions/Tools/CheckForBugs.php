<?php

namespace Iambateman\Speedrun\Actions\Tools;

use Iambateman\Speedrun\Actions\Utilities\FilterPHP;
use Iambateman\Speedrun\Actions\Utilities\GetAIWithFallback;
use Iambateman\Speedrun\Speedrun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Concerns\AsAction;
use function Iambateman\Speedrun\Actions\str_contains;

class CheckForBugs {

    use AsAction;

    public string $commandSignature = 'speedrun:check-for-bugs {file_path} {particular_checks?}';

    public string $file_path;
    public bool $success = false;
    public string $message = '';
    public ?string $file; // Original file
    public ?string $new_file; // New file
    public string $laravelType = ''; // 'Model', 'Migration', etc.
    public string $particular_checks = ''; // If we need to fight specific AI bugs.

    public function handle($file_path, ?string $particular_checks = '')
    {
        echo "Checking for bugs in {$file_path}\n";

        $this->file_path = $file_path;
        $this->laravelType = $this->detectLaravelTypeFromPath();
        $this->file = $this->getFile($file_path);

        $this->particular_checks = $particular_checks;

        if (!$this->file) {
            return false;
        }

        $prompt = $this->buildPrompt();

        $response = GetAIWithFallback::run($prompt);


        // If there are no bugs...
        if(str($response)->contains('No bugs')){
            $this->message = 'No bugs found';
            return $this->success = true;
        }

        // If there are bugs...
        $response = FilterPHP::run($response);
        $this->handleFileSwitch($response);
    }

    public function asCommand(Command $command)
    {
        $particular_checks = $command->argument('particular_checks');
        $file_path = $command->argument('file_path');


        $this->handle($file_path, $particular_checks);


        if ($this->message) {
            $command->info($this->message);
        }


        ($this->success) ?
            $command->info('Success') : $command->info('Fail');
    }

    public function handleFileSwitch(string $new_file)
    {

        // First...move the old file to a new spot
        $new_path = str($this->file_path)->remove(base_path())->toString(); // just get the local file
        $new_path = base_path() . '/_ai/files' . $new_path; // insert the ai

        // Check to see if folder exists for new spot
        // And create if we need to.
        $new_directory = dirname($new_path);
        if (!File::exists($new_directory)) {
            File::makeDirectory($new_directory, 0775, true);
        }

        // then move the file itself.
        File::move($this->file_path, $new_path);

        // Add a note about where it is.
        $new_file = str($new_file)->append("\n\n// Speedrun note:\n");
        $new_file = str($new_file)->append("// Previous version: {$new_path} ");

        // And put the new file where it belongs.
        File::put($this->file_path, $new_file);

        $this->success = true;
        $this->message = 'successfully updated the file.';
    }

    public function buildPrompt(): string
    {
        $prompt = "The following file is a Laravel {$this->laravelType} file.";
        $prompt .= " Please check to see if it contains bugs.";
        $prompt .= " If it does not contain bugs, respond only with 'No bugs.'";
        $prompt .= " If it contains bugs, respond only with a revised file which removes the bugs and do not say 'No bugs.'";
        $prompt .= " The revised file must be in a ``` code block.";

        if($this->particular_checks) {
            $prompt .= " {$this->particular_checks}";
        }
        $prompt .= " \n\n";
        $prompt .= $this->file;

        return $prompt;
    }

    public function detectLaravelTypeFromPath(): string
    {
        return match (true) {
            str_contains($this->file_path, 'app/Models') => 'Model',
            str_contains($this->file_path, 'database/migrations') => 'Migration',
            str_contains($this->file_path, 'database/factories') => 'Factory',
            str_contains($this->file_path, 'app/Console/Commands') => 'Command',
            str_contains($this->file_path, 'resources/views') => 'View',
            str_contains($this->file_path, 'test') => 'Test',
            default => ''
        };
    }

    /**
     * @return false|string
     * We have to protect against overly large files going into GPT.
     */
    protected function getFile()
    {
        $size = File::size($this->file_path);
        if ($size > 7000) {
            $this->message = 'The file size is too large to process.';
            return false;
        }
        return File::get($this->file_path);

    }

}