<?php

namespace App\Jobs;

use App\Models\User;
use App\Service\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $userId,
        protected string $title,
        protected string $body,
        protected array $data = [],
        protected string $type = 'general'
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (!$user) {
            return;
        }

        $notificationService = app(NotificationService::class);
        $notificationService->sendToUser($user, $this->title, $this->body, $this->data, $this->type);
    }
}

