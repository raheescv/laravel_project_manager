<?php

namespace App\Console\Commands\Test;

use App\Helpers\Facades\WhatsappHelper;
use Illuminate\Console\Command;

class WhatsappCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:whatsapp-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    protected WhatsappHelper $whatsappService;

    public function handle()
    {
        $response = WhatsappHelper::sendTemplateWithImage(to: '+919633155669', templateName: 'invoice_slip', imageUrl: 'https://nyly.astraqatar.com/storage/company_image/E74ceACWHb87phzs86dJD915VSAVvgRjl4fmStVa.png');
        dd($response);
    }
}
