<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClientWelcomeNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $temporaryPassword,
        private readonly string $eventName,
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
        return (new MailMessage())
            ->subject('Welcome to ' . $this->eventName)
            ->greeting('Hello!')
            ->line('You have been added as a client for the event: ' . $this->eventName . '.')
            ->line('Here are your login credentials:')
            ->line('Email: ' . $notifiable->email)
            ->line('Temporary Password: ' . $this->temporaryPassword)
            ->line('For security reasons, you will be required to change your password upon your first login.')
            ->action('Login Now', config('app.frontend_url') . '/clients/login')
            ->line('If you did not expect this email, please contact the event organizer.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
