<?php

namespace App\Notifications;

use Illuminate\Notifications\Channels\DatabaseChannel as BaseDatabaseChannel;
use Illuminate\Notifications\Notification;

class DatabaseChannel extends BaseDatabaseChannel
{
    /**
     * Build an array payload for the DatabaseNotification Model.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array
     */
    protected function buildPayload($notifiable, Notification $notification)
    {
        $payload = parent::buildPayload($notifiable, $notification);
        
        // Add tenant_id from the notifiable model if it exists
        if (isset($notifiable->tenant_id)) {
            $payload['tenant_id'] = $notifiable->tenant_id;
        } elseif (method_exists($notifiable, 'getTenantId')) {
            $payload['tenant_id'] = $notifiable->getTenantId();
        }
        
        return $payload;
    }
}
