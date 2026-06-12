<?php

namespace App\Notifications\Channels;

use App\Models\PushToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoPushChannel
{
    public function send(object $notifiable, object $notification): void
    {
        $tokens = PushToken::query()
            ->where('user_id', $notifiable->getKey())
            ->pluck('token')
            ->all();

        if (empty($tokens)) {
            return;
        }

        $payload = $notification->toExpoPush($notifiable);

        $messages = array_map(fn (string $token) => array_merge(
            ['to' => $token],
            $payload
        ), $tokens);

        $response = Http::withHeaders(['Accept-Encoding' => 'gzip, deflate'])
            ->post('https://exp.host/--/api/v2/push/send', $messages);

        if ($response->failed()) {
            Log::warning('Expo push failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        }
    }
}
