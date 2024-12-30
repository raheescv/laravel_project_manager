<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Http;
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
        $this->link = 'http://localhost:3002';
        $this->number = '+919633155669';
        $this->message = 'This is a sample message from ASTRA Group';
        $this->checkClientStatus();
    }

    public function getWhatsappQr()
    {
        $response = Http::get("$this->link/get-qr");
        $response = $response->json();
        if (! $response['success']) {
            $this->dispatch('error', ['message' => 'QR Returned Successfully']);

            return false;
        }
        $this->whatsappQr = $response['qr'] ?? '';
    }

    public function checkClientStatus()
    {
        $response = Http::get("$this->link/check-status");
        $response = $response->json();
        // dd($response);
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
        $response = Http::post("$this->link/disconnect");
        $response = $response->json();
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
        $this->checkClientStatus();
        if (! $this->isConnected) {
            $this->dispatch('error', ['message' => 'Client is not connected']);

            return false;
        }
        $filePath = public_path('node/sample.pdf');
        $response = Http::post("$this->link/send-message", [
            'number' => $this->number,
            'message' => $this->message,
            'filePath' => $filePath ?? '',
        ]);
        $response = $response->json();
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
