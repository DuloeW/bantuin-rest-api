<?php

namespace App\Service\Notification;

use App\Models\DeviceToken;
use App\Models\Notification;
use App\Models\User;
use App\Traits\ServiceResponse;
use Exception;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;

class NotificationService
{
    use ServiceResponse;

    private $messaging;

    public function __construct()
    {
        try {
            $this->messaging = app('firebase.messaging');
        } catch (Exception $e) {
            Log::warning('Firebase messaging initialization: ' . $e->getMessage());
            $this->messaging = null;
        }
    }

    public function sendToUser(User $user, string $title, string $body, array $data = [], string $type = 'general'): array
    {
        try {
            // 1. Selalu simpan ke database terlebih dahulu (agar muncul di in-app notification page)
            $notificationRecord = Notification::create([
                'user_id' => $user->id,
                'title'   => $title,
                'body'    => $body,
                'data'    => $data,
                'type'    => $type,
            ]);

            // 2. Ambil token FCM untuk push notification
            $tokens = $user->deviceTokens()->pluck('device_token')->toArray();

            if (empty($tokens)) {
                return $this->successPayload([
                    'id'        => $notificationRecord->id,
                    'title'     => $title,
                    'body'      => $body,
                    'type'      => $type,
                    'push_sent' => false,
                ], 'Notification saved to DB (no active device tokens)', 200);
            }

            // Data payload harus semua string agar kompatibel dengan FCM
            $fcmData = array_map('strval', array_merge($data, [
                'type'      => $type,
                'timestamp' => now()->toIso8601String(),
            ]));

            if ($this->messaging) {
                try {
                    $notification = FirebaseNotification::create($title, $body);

                    $androidConfig = AndroidConfig::fromArray([
                        'priority' => 'high',
                        'notification' => [
                            'channel_id' => 'high_importance_channel',
                            'sound' => 'default',
                        ],
                    ]);

                    $apnsConfig = ApnsConfig::fromArray([
                        'headers' => [
                            'apns-priority' => '10',
                        ],
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                                'badge' => 1,
                            ],
                        ],
                    ]);

                    foreach ($tokens as $token) {
                        try {
                            $message = CloudMessage::new()
                                ->withToken($token)
                                ->withNotification($notification)
                                ->withAndroidConfig($androidConfig)
                                ->withApnsConfig($apnsConfig)
                                ->withData($fcmData);

                            $this->messaging->send($message);
                        } catch (Exception $e) {
                            Log::warning("Firebase send error for token [{$token}]: " . $e->getMessage());

                            // Jika token sudah tidak valid / unregistered di Firebase, hapus dari database agar tidak mengganggu berikutnya
                            $errorMessage = strtolower($e->getMessage());
                            if (
                                str_contains($errorMessage, 'notfound') ||
                                str_contains($errorMessage, 'unregistered') ||
                                str_contains($errorMessage, 'invalid_argument') ||
                                str_contains($errorMessage, 'registration-token-not-registered')
                            ) {
                                DeviceToken::where('device_token', $token)->delete();
                                Log::info("Expired/invalid device token removed: {$token}");
                            }
                        }
                    }
                } catch (Exception $e) {
                    Log::error('Firebase messaging error: ' . $e->getMessage());
                }
            }

            return $this->successPayload([
                'id'    => $notificationRecord->id,
                'title' => $title,
                'body'  => $body,
                'type'  => $type,
                'push_sent' => true,
            ], 'Notification sent to user', 200);
        } catch (Exception $e) {
            Log::error('sendToUser error: ' . $e->getMessage());
            return $this->errorPayload('Failed to send notification', [], 500);
        }
    }

    public function sendToMultipleUsers(array $userIds, string $title, string $body, array $data = [], string $type = 'general'): array
    {
        try {
            $users = User::whereIn('id', $userIds)->get();

            $results = [];
            foreach ($users as $user) {
                $result = $this->sendToUser($user, $title, $body, $data, $type);
                $results[$user->id] = $result['success'];
            }

            return $this->successPayload($results, 'Notifications sent to multiple users', 200);
        } catch (Exception $e) {
            Log::error('sendToMultipleUsers error: ' . $e->getMessage());
            return $this->errorPayload('Failed to send notifications', [], 500);
        }
    }

    public function sendTransactionNotification($transaction, string $eventType): array
    {
        $titles = [
            'created' => 'New Transaction',
            'started' => 'Transaction Started',
            'completed' => 'Transaction Completed',
            'disputed' => 'Transaction Disputed',
            'cancelled' => 'Transaction Cancelled',
        ];

        $title = $titles[$eventType] ?? 'Transaction Update';
        $body = "Your transaction status: " . $transaction->status;

        $data = [
            'transaction_id' => (string)$transaction->id,
            'status' => $transaction->status,
        ];

        $recipient = $eventType === 'created' ? $transaction->requester : $transaction->helper;

        return $this->sendToUser($recipient, $title, $body, $data, 'transaction_' . $eventType);
    }

    public function sendOfferNotification($offer, string $eventType): array
    {
        $titles = [
            'created' => 'New Offer',
            'accepted' => 'Offer Accepted',
            'rejected' => 'Offer Rejected',
            'cancelled' => 'Offer Cancelled',
        ];

        $title = $titles[$eventType] ?? 'Offer Update';
        $offerUser = $offer->user->first_name;
        $body = "$offerUser - " . ucfirst($eventType);

        $data = [
            'offer_id' => (string)$offer->id,
            'event' => $eventType,
        ];

        $recipient = $eventType === 'created' ? $offer->postRequest->user : $offer->user;

        return $this->sendToUser($recipient, $title, $body, $data, 'offer_' . $eventType);
    }

    public function sendMessageNotification($message): array
    {
        $sender = $message->sender->first_name;
        $title = "New Message from $sender";
        $body = substr($message->content, 0, 100);

        $data = [
            'message_id' => (string)$message->id,
            'offer_id' => (string)$message->offer_id,
        ];

        return $this->sendToUser($message->receiver, $title, $body, $data, 'message_received');
    }

    public function sendPaymentNotification($payment): array
    {
        $title = 'Payment Confirmed';
        $body = 'Your payment of Rp ' . number_format($payment->amount, 0, ',', '.') . ' has been confirmed';

        $data = [
            'payment_id' => (string)$payment->id,
            'amount' => (string)$payment->amount,
            'status' => $payment->status,
        ];

        $transaction = $payment->transaction;

        return $this->sendToUser($transaction->requester, $title, $body, $data, 'payment_confirmed');
    }

    public function sendReviewNotification($review): array
    {
        $reviewer = $review->reviewer->first_name;
        $title = "New Review from $reviewer";
        $body = $review->comment ? substr($review->comment, 0, 100) : 'Check your new review';

        $data = [
            'review_id' => (string)$review->id,
            'rating' => (string)$review->rating,
        ];

        return $this->sendToUser($review->reviewed, $title, $body, $data, 'review_received');
    }

    public function sendToToken(string $token, string $title, string $body, array $data = [], string $type = 'general'): array
    {
        try {
            $fcmData = array_map('strval', array_merge($data, [
                'type'      => $type,
                'timestamp' => now()->toIso8601String(),
            ]));

            if ($this->messaging) {
                $notification = FirebaseNotification::create($title, $body);

                $androidConfig = AndroidConfig::fromArray([
                    'priority' => 'high',
                    'notification' => [
                        'channel_id' => 'high_importance_channel',
                        'sound' => 'default',
                    ],
                ]);

                $apnsConfig = ApnsConfig::fromArray([
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1,
                        ],
                    ],
                ]);

                $message = CloudMessage::new()
                    ->withToken($token)
                    ->withNotification($notification)
                    ->withAndroidConfig($androidConfig)
                    ->withApnsConfig($apnsConfig)
                    ->withData($fcmData);

                $this->messaging->send($message);
            }

            return $this->successPayload([
                'token' => $token,
                'title' => $title,
                'body'  => $body,
                'type'  => $type,
            ], 'Notification sent to token directly', 200);
        } catch (Exception $e) {
            Log::error('sendToToken error: ' . $e->getMessage());
            return $this->errorPayload('Failed to send notification: ' . $e->getMessage(), [], 500);
        }
    }
}
