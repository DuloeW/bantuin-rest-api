<?php

namespace App\Service\Message;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\Offer;
use App\Models\User;
use App\Traits\ServiceResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageService
{
    use ServiceResponse;

    public function sendMessage(Request $request, Offer $offer, User $user)
    {
        $receiverId = $user->id === $offer->helper->id
            ? $offer->requester->id
            : $offer->helper->id;

        return DB::transaction(function () use ($request, $offer, $user, $receiverId) {
            $message = $offer->messages()->create([
                'sender_id'   => $user->id,
                'receiver_id' => $receiverId,
                'content'     => $request->input('content'),
                'type'        => $request->input('type', 'text'),
                'is_read'     => false,
            ]);

            broadcast(new MessageSent($message))->toOthers();

            return $this->successPayload($message->load('sender'), 'message sent successfully', 201);
        });
    }

    public function getMessages(string $offerId, User $user)
    {
        $offer = Offer::findOrFail($offerId);

        if (
            (string) $user->id !== (string) $offer->helper_id &&
            (string) $user->id !== (string) $offer->requester_id
        ) {
            abort(403, 'You are not authorized to view messages for this offer.');
        }

        $messages = Message::where('offer_id', $offerId)
            ->with('sender', 'receiver')
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->successPayload($messages, 'messages retrieved successfully');
    }

    public function fetchOffer(string $id)
    {
        return Offer::findOrFail($id);
    }
}
