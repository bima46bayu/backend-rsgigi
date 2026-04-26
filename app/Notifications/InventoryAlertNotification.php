<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InventoryAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        private array $alerts,
        private string $locationName
    ) {}

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toBroadcast($notifiable)
    {
        return new \Illuminate\Notifications\Messages\BroadcastMessage([
            'type' => 'inventory_alert',
            'location_name' => $this->locationName,
            'items' => $this->alerts,
            'message' => "Terdapat " . count($this->alerts) . " item dengan stok kritis di " . $this->locationName,
            'created_at' => now()->toDateTimeString(),
        ]);
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'inventory_alert',
            'location_name' => $this->locationName,
            'items' => $this->alerts, // Array berisi detail item: name, status, stock
            'message' => "Terdapat " . count($this->alerts) . " item dengan stok kritis di " . $this->locationName
        ];
    }
}
