<?php

namespace App\Livewire\Settings;

use App\Helpers\Facades\WhatsappHelper;
use Livewire\Component;

class Whatsapp extends Component
{
    public $whatsappQr;

    public $link;

    public $number;

    public $isConnected = false;

    public $message = null;

    public function mount()
    {
        $this->number = '+919633155669';
        $this->message = 'This is a sample message from ASTRA Group';
        $this->checkClientStatus();
    }

    public function getWhatsappQr()
    {
        $response = WhatsappHelper::getCall('get-qr');
        if (! $response['success']) {
            $this->dispatch('error', ['message' => 'QR not returned']);

            return false;
        }
        $this->whatsappQr = $response['qr'] ?? '';
    }

    public function checkClientStatus()
    {
        $response = WhatsappHelper::getCall('check-status');
        if (! $response['success']) {
            $this->dispatch('error', ['message' => $response['message']]);
            $this->isConnected = false;
        } else {
            $this->dispatch('success', ['message' => $response['message']]);
            $this->isConnected = true;
        }
    }

    public function disconnect()
    {
        $response = WhatsappHelper::postCall('disconnect');
        if (! $response['success']) {
            $this->isConnected = true;
            $this->dispatch('error', ['message' => $response['message']]);
        } else {
            $this->isConnected = false;
            $this->dispatch('success', ['message' => $response['message']]);
        }
    }

    public function sendSampleSms()
    {
        $data = [
            'number' => $this->number,
            'message' => $this->message,
            'filePath' => public_path('node/sample.pdf'),
        ];
        $response = WhatsappHelper::send($data);
        if (! $response['success']) {
            $this->dispatch('error', ['message' => $response['message']]);
        } else {
            $this->dispatch('success', ['message' => $response['message']]);
        }
    }

    public function render()
    {
        $this->getWhatsappQr();

        return view('livewire.settings.whatsapp');
    }
}
