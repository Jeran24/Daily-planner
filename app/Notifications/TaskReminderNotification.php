<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TaskReminderNotification extends Notification
{
    public function __construct(public readonly string $taskName, public readonly string $startTime) {}

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, object $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Daily Planner reminder')
            ->body("{$this->taskName} starts at {$this->startTime}.")
            ->icon('/favicon.svg')
            ->tag('task-reminder-'.$this->taskName)
            ->options(['TTL' => 3600]);
    }
}
