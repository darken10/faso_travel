<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toExpo')) {
            return;
        }

        $tokens = method_exists($notifiable, 'routeNotificationForExpo')
            ? $notifiable->routeNotificationForExpo()
            : [];

        // Ne garde que les tokens Expo valides.
        $tokens = array_values(array_filter($tokens, fn ($t) => is_string($t) && str_starts_with($t, 'ExponentPushToken')));

        if (empty($tokens)) {
            return;
        }

        $payload = $notification->toExpo($notifiable); // ['title' => , 'body' => , 'data' => []]

        $messages = array_map(fn ($token) => [
            'to'    => $token,
            'title' => $payload['title'] ?? 'LIPTRA',
            'body'  => $payload['body'] ?? '',
            'data'  => $payload['data'] ?? [],
            'sound' => 'default',
        ], $tokens);

        try {
            Http::withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])->post('https://exp.host/--/api/v2/push/send', $messages);
        } catch (\Throwable $e) {
            Log::warning('[ExpoChannel] envoi push échoué : ' . $e->getMessage());
        }
    }
}
