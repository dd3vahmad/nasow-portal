<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPassword extends Notification
{
    use Queueable;

    /**
     * The password reset token.
     *
     * @var string
     */
    public $token;

    /**
     * Create a new notification instance.
     *
     * @param string $token
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the password reset URL for the given notifiable.
     *
     * @param mixed $notifiable
     * @return string
     */
    protected function resetUrl($notifiable)
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $email = urlencode($notifiable->getEmailForPasswordReset());
        $token = urlencode($this->token);

        return "{$frontendUrl}/reset-password?token={$token}&email={$email}";
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset Your Password')
            ->view('emails.password-reset', [
                'user' => $notifiable,
                'url' => $this->resetUrl($notifiable),
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [];
    }
}
