<?php

namespace App\Service\Message;

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

        DB::transaction(function () use ($request, $offer, $user, $receiverId) {
            $message = $offer->messages()->create([
                'sender_id' => $user->id,
                'receiver_id' => $receiverId,
                'content' => $request->input('content'),
                'type' => $request->input('type', 'text'),
                'is_read' => false,
            ]);

            return $this->successPayload($message, 'message sent successfully', 201);

            // TODO: Optionally, you can trigger events or notifications here
        });
    }
    
    public function fetchOffer(string $id)
    {
        return Offer::findOrFail($id);
    }
}