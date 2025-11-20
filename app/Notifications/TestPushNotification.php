<?php
namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class TestPushNotification extends Notification
{
    public function via($notifiable)
    {
        return ['webpush'];
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('🚀 Funciona!')
            ->body('Ya recibes notificaciones push.')
            ->action('Abrir app', 'open_app');
    }
}
