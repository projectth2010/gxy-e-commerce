<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TeamsChannel
{
    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toTeams($notifiable);
        $webhookUrl = config('services.teams.webhook_url');

        if (empty($webhookUrl)) {
            Log::warning('Teams webhook URL not configured');
            return false;
        }

        try {
            $response = Http::post($webhookUrl, $message);
            
            if (!$response->successful()) {
                Log::error('Failed to send Teams notification', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'message' => $message
                ]);
                return false;
            }
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Exception when sending Teams notification: ' . $e->getMessage(), [
                'exception' => $e,
                'message' => $message
            ]);
            return false;
        }
    }
}
