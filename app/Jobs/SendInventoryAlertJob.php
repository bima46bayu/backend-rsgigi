<?php

namespace App\Jobs;

use App\Models\Location;
use App\Models\User;
use App\Notifications\InventoryAlertNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Mail\InventoryAlertMail;
use Illuminate\Support\Facades\Notification;

class SendInventoryAlertJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public function __construct(
        private int $locationId,
        private array $alerts
    ) {}

    public function handle(): void
    {
        $location = Location::with('users')->find($this->locationId);

        if (!$location) return;

        $message = $this->buildMessage();

        $this->sendWhatsApp($location, $message);
        $this->sendEmail($location, $message);
        $this->sendDatabaseNotification($location, $message);
    }

    private function buildMessage(): string
    {
        $msg = "⚠️ *PERINGATAN STOK RENDAH* - {$this->alerts[0]['location_name']}\n\n";
        $msg .= "Halo Team, mohon periksa daftar barang berikut yang stoknya mulai menipis atau kritis:\n\n";

        foreach ($this->alerts as $alert) {
            $emoji = ($alert['status'] === 'critical') ? "🔴" : "🟠";
            $msg .= "{$emoji} *{$alert['name']}*\n";
            $msg .= "   Status: {$alert['status']}\n";
            $msg .= "   Sisa Stok: {$alert['stock']}\n\n";
        }

        $msg .= "Mohon segera lakukan pengadaan atau restock barang di atas.\n";
        $msg .= "_Sistem Inventaris RS Gigi_";

        return $msg;
    }

    private function sendWhatsApp($location, $message)
    {
        $targets = [];

        // Nomor WA cabang jika ada
        if ($location->whatsapp_number) {
            $targets[] = $location->whatsapp_number;
        }

        // Ambil user di lokasi tersebut + Admin global (location_id = null)
        $users = User::where('receive_alert', true)
            ->where(function ($query) use ($location) {
                $query->where('location_id', $location->id)
                      ->orWhereNull('location_id');
            })
            ->whereNotNull('phone_number')
            ->get();

        foreach ($users as $user) {
            $targets[] = $user->phone_number;
        }

        if (empty($targets)) return;

        Http::withHeaders([
            'Authorization' => env('FONNTE_TOKEN')
        ])->post('https://api.fonnte.com/send', [
            'target' => implode(',', array_unique($targets)),
            'message' => $message,
        ]);
    }


    private function sendEmail($location, $message)
    {
        $emails = User::where('receive_alert', true)
            ->where(function ($query) use ($location) {
                $query->where('location_id', $location->id)
                      ->orWhereNull('location_id');
            })
            ->pluck('email')
            ->toArray();

        if (empty($emails)) return;

        Mail::to($emails)->send(new InventoryAlertMail($this->alerts, $location->name));
    }

    private function sendDatabaseNotification($location, $message)
    {
        $users = User::where('receive_alert', true)
            ->where(function ($query) use ($location) {
                $query->where('location_id', $location->id)
                      ->orWhereNull('location_id');
            })
            ->get();

        if ($users->isNotEmpty()) {
            Notification::send($users, new InventoryAlertNotification($this->alerts, $location->name));
        }
    }
}