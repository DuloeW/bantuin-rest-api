<?php

use App\Events\MessageSent;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-broadcast', function () {

    broadcast(new MessageSent([
        'message' => 'Halo Bayu'
    ]));

    return 'sent';
});