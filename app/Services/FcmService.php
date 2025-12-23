<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;
use App\Models\User;
use App\Models\DeviceToken;
use Illuminate\Support\Facades\Log;

class FcmService
{
    public function sendToToken(string $token, string $title, string $body, array $data = [])
    {
        try {
            $message = CloudMessage::new()
                ->withNotification(Notification::create($title, $body))
                ->withData($data)
                ->toToken($token);

            Firebase::messaging()->send($message);
            return true;
        } catch (\Exception $e) {
            Log::error('FCM send error: '.$e->getMessage());
            return false;
        }
    }

    public function sendToUser(User $user, string $title, string $body, array $data = [])
    {
        $tokens = $user->deviceTokens->pluck('token')->toArray();

        if (empty($tokens)) {
            return false;
        }

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    public function sendToTokens(array $tokens, string $title, string $body, array $data = [])
    {
        if (empty($tokens)) {
            return false;
        }

        try {
            $message = CloudMessage::new()
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            $sendReport = Firebase::messaging()->sendMulticast($message, $tokens); // Updated method call

            return [
                'success' => $sendReport->successes()->count(),
                'failures' => $sendReport->failures()->count(),
                'invalid_tokens' => $sendReport->invalidTokens(),
            ];
        } catch (\Exception $e) {
            Log::error('FCM multicast error: '.$e->getMessage());
            return false;
        }
    }

    public function sendToTopic(string $topic, string $title, string $body, array $data = [])
    {
        try {
            $message = CloudMessage::new()
                ->withNotification(Notification::create($title, $body))
                ->withData($data)
                ->toTopic($topic);

            Firebase::messaging()->send($message);
            return true;
        } catch (\Exception $e) {
            Log::error('FCM topic error: '.$e->getMessage());
            return false;
        }
    }
}
