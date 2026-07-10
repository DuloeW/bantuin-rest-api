<?php

use App\Models\Offer;
use Illuminate\Support\Facades\Broadcast;

// Override default broadcast auth route agar menggunakan Sanctum
Broadcast::routes(['middleware' => ['api', 'auth:sanctum']]);

Broadcast::channel('offer.{offerId}', function ($user, $offerId) {
    $offer = Offer::find($offerId);

    if (!$offer) {
        return false;
    }

    // Hanya helper atau requester dari offer ini yang boleh bergabung ke channel
    return (string) $user->id === (string) $offer->helper_id ||
           (string) $user->id === (string) $offer->requester_id;
});
