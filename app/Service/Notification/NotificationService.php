<?php

namespace App\Service\Notification;

use App\Models\Notification;
use App\Models\User;
use App\Traits\ServiceResponse;
use Exception;
use Illuminate\Support\Facades\Log;

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
            $tokens = $user->deviceTokens()->pluck('device_token')->toArray();

            if (empty($tokens)) {
                return $this->errorPayload('No active devices for user', [], 400);
            }

            $notificationData = array_merge($data, [
                'title' => $title,
                'body' => $body,
                'type' => $type,
                'timestamp' => now()->toIso8601String(),
            ]);

            if ($this->messaging) {
                try {
                    foreach ($tokens as $token) {
                        $this->messaging->send([
                            'token' => $token,
                            'notification' => [
                                'title' => $title,
                                'body' => $body,
                            ],
                            'data' => $notificationData,
                        ]);
                    }
                } catch (Exception $e) {
                    Log::error('Firebase send error: ' . $e->getMessage());
                }
            }

            Notification::create([
                'user_id' => $user->id,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'type' => $type,
            ]);

            return $this->successPayload($notificationData, 'Notification sent to user', 200);
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
}
