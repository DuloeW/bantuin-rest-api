<?php

namespace App\Traits;

use App\Models\User;
use App\Service\Notification\NotificationService;
use Illuminate\Support\Collection;

trait SendsNotification
{
    /**
     * Kirim notifikasi (simpan ke database & Push Notification FCM).
     *
     * @param  User|Collection|array|string  $target
     * @param  string  $title
     * @param  string  $body
     * @param  array   $data
     * @param  string  $type
     * @return array
     */
    protected function notifyUser(
        User|Collection|array|string $target,
        string $title,
        string $body,
        array $data = [],
        string $type = 'general'
    ): array {
        return send_notification($target, $title, $body, $data, $type);
    }
}
