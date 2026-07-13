<?php

use App\Models\User;
use App\Service\Notification\NotificationService;
use Illuminate\Support\Collection;

if (! function_exists('send_notification')) {
    /**
     * Kirim notifikasi sekaligus (simpan ke database & kirim Push Notification FCM).
     *
     * @param  User|Collection|array|string  $target  Bisa berupa Model User, ID User (UUID/string), atau array ID User
     * @param  string  $title  Judul notifikasi
     * @param  string  $body   Isi notifikasi
     * @param  array   $data   Data tambahan (opsional)
     * @param  string  $type   Tipe/kategori notifikasi (default: 'general')
     * @return array
     */
    function send_notification(
        User|Collection|array|string $target,
        string $title,
        string $body,
        array $data = [],
        string $type = 'general'
    ): array {
        $service = app(NotificationService::class);

        if ($target instanceof User) {
            return $service->sendToUser($target, $title, $body, $data, $type);
        }

        if ($target instanceof Collection) {
            $userIds = $target->pluck('id')->toArray();
            return $service->sendToMultipleUsers($userIds, $title, $body, $data, $type);
        }

        if (is_array($target)) {
            return $service->sendToMultipleUsers($target, $title, $body, $data, $type);
        }

        if (is_string($target)) {
            $user = User::find($target);
            if ($user) {
                return $service->sendToUser($user, $title, $body, $data, $type);
            }
        }

        return [
            'success' => false,
            'message' => 'Target user is invalid or not found.',
        ];
    }
}
