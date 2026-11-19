<?php

namespace App\Jobs;

use App\Events\NotificationCreatedEvent;
use App\Jobs\Middleware\RunInTenant;
use App\Models\User;
use App\Notifications\RentOutBookingNotification;
use App\Services\TenantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RentOutNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $title,
        protected string $content,
        protected ?string $link = null,
        protected ?int $modelId = null,
        protected ?int $excludedUserId = null,
        protected ?int $tenantId = null,
    ) {
        $this->tenantId ??= app(TenantService::class)->getCurrentTenantId();
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new RunInTenant($this->tenantId)];
    }

    public function handle(): void
    {
        if (! $this->tenantId) {
            return;
        }

        $users = User::where('tenant_id', $this->tenantId)
            ->where('is_admin', true)
            ->where('is_browser_notification_enabled', true)
            ->when($this->excludedUserId, fn ($q) => $q->where('id', '!=', $this->excludedUserId))
            ->get(['id', 'tenant_id']);

        foreach ($users as $user) {
            $user->notify(new RentOutBookingNotification(
                $this->title,
                $this->content,
                $this->link,
                $this->modelId,
            ));

            event(new NotificationCreatedEvent(
                userId: $user->id,
                title: $this->title,
                content: $this->content,
                link: $this->link,
            ));
        }
    }
}
