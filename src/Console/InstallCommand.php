<?php

namespace MarbleCms\Translate\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature   = 'marble:translate:install';
    protected $description = 'Install Marble Translate: run migrations.';

    public function handle(): int
    {
        $this->info('Running migrations…');
        $this->call('migrate');

        $this->info('');
        $this->info('Marble Translate installed successfully.');
        $this->info('Add to your .env:');
        $this->line('  TRANSLATE_PROVIDER=deepl');
        $this->line('  TRANSLATE_API_KEY=your-api-key');
        $this->line('  TRANSLATE_DEEPL_PRO=false');

        return self::SUCCESS;
    }
}
