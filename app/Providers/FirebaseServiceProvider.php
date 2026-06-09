<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;

class FirebaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('firebase', function ($app) {
            try {
                $firebaseCredentials = config('services.firebase');

                if (!$firebaseCredentials || !$firebaseCredentials['project_id']) {
                    return null;
                }

                $credentialsArray = [
                    'type' => 'service_account',
                    'project_id' => $firebaseCredentials['project_id'],
                    'private_key_id' => '',
                    'private_key' => $firebaseCredentials['private_key'],
                    'client_email' => $firebaseCredentials['client_email'],
                    'client_id' => '',
                    'auth_uri' => $firebaseCredentials['auth_uri'],
                    'token_uri' => $firebaseCredentials['token_uri'],
                ];

                return (new Factory())->withServiceAccount($credentialsArray)->createMessaging();
            } catch (\Exception $e) {
                Log::warning('Firebase initialization failed: ' . $e->getMessage());
                return null;
            }
        });

        $this->app->singleton('firebase.messaging', function ($app) {
            return $app->make('firebase');
        });
    }

    public function boot(): void
    {
        //
    }
}
