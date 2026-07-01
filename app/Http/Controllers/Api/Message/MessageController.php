<?php

namespace App\Http\Controllers\Api\Message;

use App\Http\Controllers\Controller;
use App\Service\Message\MessageService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    private MessageService $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    public function sendMessage(Request $request, string $offerId)
    {
        $user = auth('sanctum')->user();

        $offer = $this->messageService->fetchOffer($offerId);

        if (
            $offer->helper->id !== $user->id &&
            $offer->requester->id !== $user->id
        ) {
            abort(403, 'You are not authorized to send messages for this offer.');
        }

        $request->validate([
            'content' => 'required|string',
            'type'    => 'nullable|string|in:text,agreement',
        ]);

        $result = $this->messageService->sendMessage($request, $offer, $user);

        return response()->json($result, $result['code']);
    }

    public function getMessages(string $offerId)
    {
        $user = auth('sanctum')->user();

        $result = $this->messageService->getMessages($offerId, $user);

        return response()->json($result, $result['code']);
    }
}
