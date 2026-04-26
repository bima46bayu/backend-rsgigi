<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class StockExpiryNotification extends Notification
{
    use Queueable;

    public function __construct(
        public $stock,
        public $status,
        public $daysRemaining
    ) {}

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toBroadcast($notifiable)
    {
        return new \Illuminate\Notifications\Messages\BroadcastMessage([
            'type' => 'stock_expiry',
            'item' => $this->stock->item->name,
            'batch_number' => $this->stock->batch_number,
            'quantity' => $this->stock->quantity,
            'expiry_date' => $this->stock->expiry_date,
            'status' => $this->status,
            'days_remaining' => $this->daysRemaining,
            'created_at' => now()->toDateTimeString(),
        ]);
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'stock_expiry',
            'item' => $this->stock->item->name,
            'batch_number' => $this->stock->batch_number,
            'quantity' => $this->stock->quantity,
            'expiry_date' => $this->stock->expiry_date,
            'status' => $this->status,
            'days_remaining' => $this->daysRemaining
        ];
    }
}