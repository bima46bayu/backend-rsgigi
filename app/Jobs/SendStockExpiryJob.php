<?php

namespace App\Jobs;

use App\Models\Location;
use App\Models\User;
use App\Notifications\StockExpiryNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Mail\StockExpiryMail;

class SendStockExpiryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $locationId,
        private array $expiryDetails
    ) {}

    public function handle(): void
    {
        $location = Location::with('users')->find($this->locationId);

        if (!$location) return;

        $message = $this->buildMessage($location->name);

        $this->sendWhatsApp($location, $message);
        $this->sendEmail($location, $message);
        $this->sendDatabaseNotifications($location);
    }

    private function buildMessage(string $locationName): string
    {
        $msg = "⚠️ *PERINGATAN STOK KADALUWARSA* - {$locationName}\n\n";
        $msg .= "Halo Team, mohon periksa daftar stok berikut yang memerlukan perhatian segera:\n\n";

        $categories = [
            'expired' => ['label' => '🛑 *EXPIRED (Sudah Lewat):*', 'items' => []],
            'critical' => ['label' => '🔴 *KRITIS (≤ 7 hari):*', 'items' => []],
            'warning' => ['label' => '🟠 *WARNING (≤ 30 hari):*', 'items' => []],
            'slow_moving' => ['label' => '🟡 *PROMO/LAMBAT (≤ 90 hari):*', 'items' => []],
        ];

        foreach ($this->expiryDetails as $detail) {
            $status = $detail['status'];
            if (isset($categories[$status])) {
                $categories[$status]['items'][] = $detail;
            }
        }

        foreach ($categories as $cat) {
            if (!empty($cat['items'])) {
                $msg .= "{$cat['label']}\n";
                foreach ($cat['items'] as $item) {
                    $expiryDate = date('d M Y', strtotime($item['expiry_date']));
                    $msg .= "- {$item['name']} (Batch: {$item['batch_number']}) - Tgl: {$expiryDate}\n";
                }
                $msg .= "\n";
            }
        }

        $msg .= "Mohon segera lakukan pemindahan atau retur stok di atas.\n";
        $msg .= "_Sistem Inventaris RS Gigi_";

        return $msg;
    }

    private function sendWhatsApp($location, $message)
    {
        $targets = [];

        if ($location->whatsapp_number) {
            $targets[] = $location->whatsapp_number;
        }

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
            'target' => implode(',', $targets),
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

        Mail::to($emails)->send(new StockExpiryMail($this->expiryDetails, $location->name));
    }

    private function sendDatabaseNotifications($location)
    {
        $users = User::where('receive_alert', true)
            ->where(function ($query) use ($location) {
                $query->where('location_id', $location->id)
                      ->orWhereNull('location_id');
            })
            ->get();

        if ($users->isEmpty()) return;

        foreach ($this->expiryDetails as $detail) {
            // Kita sudah update di CheckExpiryStock sebenarnya untuk anti-spam,
            // Tapi kita pastikan kirim notifikasi database di sini per item.
            // Note: $detail['stock_model'] harusnya berisi model ItemStock jika kita ingin langsung kirim
            // Tapi untuk database notification, lebih baik kirim per item agar record masuk ke tabel notifications.
            
            // Kita ambil stok aslinya jika perlu, tapi di sini kita pakai data dari array saja.
            // Karena StockExpiryNotification butuh object ItemStock, 
            // Kita pastikan data yang dikirim ke Job mencukupi.
        }
    }
}
