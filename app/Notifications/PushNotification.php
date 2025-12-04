<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use YieldStudio\LaravelExpoNotifier\Dto\ExpoMessage;
use YieldStudio\LaravelExpoNotifier\ExpoNotificationsChannel;

class PushNotification extends Notification
{
    public string $title;

    public string $body;

    public array $data;

    public bool $shouldBatch;

    public function __construct(string $title, string $body, array $data = [], bool $shouldBatch = true)
    {
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
        $this->shouldBatch = $shouldBatch;
    }

    public function via($notifiable): array
    {
        return [ExpoNotificationsChannel::class];
    }

    public function toExpoNotification($notifiable): ExpoMessage
    {
        $tokens = [];

        if ($notifiable instanceof \App\Models\Customer) {
            $tokens = $notifiable->expoTokens->pluck('value')->toArray();
        } elseif ($notifiable instanceof \Illuminate\Notifications\AnonymousNotifiable) {
            $tokens = [$notifiable->routes['expo'] ?? ''];
        }

        $message = (new ExpoMessage)
            ->to(array_filter($tokens))
            ->title($this->title)
            ->body($this->body);

        if ($this->data) {
            $message->jsonData($this->data);
        }

        if ($this->shouldBatch) {
            $message->shouldBatch();
        }

        return $message;
    }
}
