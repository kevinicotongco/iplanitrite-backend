<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Event $event,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $eventUrl = config('app.frontend_url') . '/clients/events/' . $this->event->id;

        return (new MailMessage())
            ->subject('New Event Created: ' . $this->event->name)
            ->greeting('Hello ' . $notifiable->first_name . '!')
            ->line('You have been added to a new event: ' . $this->event->name . '.')
            ->line($this->event->description ?? 'An exciting event has been planned for you.')
            ->action('View Event Details', $eventUrl)
            ->line('Click the button above to view the event details, manage checklists, and stay updated.')
            ->line('We look forward to making this event memorable!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
