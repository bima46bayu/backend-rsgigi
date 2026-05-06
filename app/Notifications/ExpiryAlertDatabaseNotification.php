<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExpiryAlertDatabaseNotification extends Notification
{
    use Queueable;

    public function __construct(
        private array $detail
    ) {}

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'stock_expiry',
            'item' => $this->detail['name'],
            'batch_number' => $this->detail['batch_number'],
            'quantity' => $this->detail['quantity'] ?? null,
            'expiry_date' => $this->detail['expiry_date'],
            'status' => $this->detail['status'],
            'days_remaining' => $this->detail['days_remaining'] ?? null,
        ];
    }
}
