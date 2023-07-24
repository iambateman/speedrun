<?php

namespace Iambateman\Speedrun\Commands;

use Iambateman\Speedrun\Helpers\Helpers;
use Iambateman\Speedrun\Speedrun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class InstallComposerPackage extends Command
{
    public $signature = 'speedrun:install-composer-package {input}';

    public $description = 'Find and install a composer package';

    protected string $package;

    protected string $response;

    public function handle(): int
    {
        Helpers::dieInProduction();

        $this->package = $this->topTenPackages();

        if ($this->package == 'Cancel') {
            $this->info('Cancelled installing package.');

            return self::SUCCESS;
        }

        if (Speedrun::doubleConfirm()) {
            if ($this->confirm("install {$this->package}?")) {
                $this->runRequestedCommand();
            }
        } else {
            $this->runRequestedCommand();
        }

        $this->comment('All done');

        return self::SUCCESS;
    }

    public function topTenPackages(): string
    {
        $encoded_query = urlencode($this->argument('input'));
        $trimmed_encoded_query = str($encoded_query)->after('+');
        //        dd($encoded_query);

        $packages_api_response = Http::get("https://packagist.org/search.json?q={$trimmed_encoded_query}");
        $decoded_json = json_decode($packages_api_response->body());
        $packages = collect($decoded_json->results)->take(5)->pluck('name')->toArray();
        $packages[] = 'Cancel';

        return $this->choice('Which package would you like?', $packages);

    }

    protected function runRequestedCommand()
    {
        exec("composer require {$this->package}");
    }
}
