<?php

namespace Iambateman\Speedrun\Commands;

use Illuminate\Console\Command;

class IndicateDemoPresenceCommand extends Command
{
    public $signature = 'speedrun:indicate-demo-presence';

    public $description = 'Help with the Speedrun commands';

    protected string $prompt;

    protected string $response;

    public function handle(): int
    {

        $this->slowInfo('');
        $this->slowInfo('DEMO');
        $this->slowInfo('');
        $this->slowInfo('🤠 🤠 🤠 🤠 🤠');
        $this->slowInfo('🤠 🤠 🤠');
        $this->slowInfo('🤠');
        $this->slowInfo('');
        $this->slowInfo('');
        $this->slowInfo('To see how Speedrun works');
        $this->slowInfo('with a quick demo, type:');
        $this->slowInfo('php artisan speedrun:demo ', 'warn');
        $this->slowInfo('');
        $this->slowInfo('');

        return self::SUCCESS;
    }

    public function slowInfo($text, $type = 'info')
    {
        // center the text.
        $length = str($text)->length();
        $padding = round((32 - $length) / 2, 0);

        usleep($this->sleepy_time);

        $this->$type('>>>>'.str_repeat(' ', $padding).$text.str_repeat(' ', $padding));
    }
}
