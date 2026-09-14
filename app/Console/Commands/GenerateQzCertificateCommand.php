<?php

namespace App\Console\Commands;

use App\Support\QzTray;
use Illuminate\Console\Command;

class GenerateQzCertificateCommand extends Command
{
    protected $signature = 'qz:certificate
                            {--name= : Organisation shown in QZ Tray (defaults to the app name)}
                            {--days=7300 : How many days the certificate stays valid}
                            {--force : Replace the existing pair; every printing PC must then trust the new certificate}';

    protected $description = 'Generate the self-signed certificate QZ Tray trusts to print labels without a dialog';

    public function handle(): int
    {
        if (QzTray::isConfigured() && ! $this->option('force')) {
            $this->warn('A QZ Tray certificate already exists: '.QzTray::certificatePath());
            $this->line('Re-run with --force to replace it. Every printing PC must then trust the new certificate.');

            return self::SUCCESS;
        }

        QzTray::generate($this->option('name') ?: config('app.name'), (int) $this->option('days'));

        $this->info('Certificate: '.QzTray::certificatePath());
        $this->info('Private key: '.QzTray::privateKeyPath().' (keep it on the server)');
        $this->newLine();
        $this->line('On each PC that prints labels, save the certificate as override.crt in the QZ Tray folder, then restart QZ Tray:');
        $this->line('  macOS:   /Applications/QZ Tray.app/Contents/Resources/override.crt');
        $this->line('  Windows: C:\Program Files\QZ Tray\override.crt');
        $this->line('The printer picker has a "Download certificate" link that saves it under that name.');

        return self::SUCCESS;
    }
}
