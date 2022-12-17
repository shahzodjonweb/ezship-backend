<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;


class EmailVerificationNotification extends VerifyEmail
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $prefix = env('SPA_URL','https://app.shipio.app').'/verify-email?url=';
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
        ->subject('Verify Email Address')
        ->greeting('Hi '.$notifiable->name .',')
            ->line('Thanks for registering for an account on EZSHIP! Before we get started, we just need to confirm that this is you. Click below to verify your email address:')
            ->action('Verify Email', $prefix . urlencode($verificationUrl))
            ->line('Thank you for using EZSHIP!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
