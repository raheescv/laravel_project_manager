<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class WhatsappHelper
{
    public $link;

    public function __construct()
    {
        $this->link = config('constants.whatsapp_server_url');
    }

    public function getCall($method)
    {
        $response = Http::get("$this->link/$method");
        $response = $response->json();

        return $response;
    }

    public function postCall($method)
    {
        $response = Http::post("$this->link/$method");
        $response = $response->json();

        return $response;
    }

    public function send($data)
    {
        $response = Http::post("$this->link/send-message", $data);
        $response = $response->json();

        return $response;
    }
}
