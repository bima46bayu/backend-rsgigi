<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ItemStock;
use App\Models\User;
use App\Notifications\StockExpiryNotification;
use Carbon\Carbon;

class CheckExpiryStock extends Command
{
    protected $signature = 'inventory:check-expiry';

    protected $description = 'Check expiring stock batches';

    public function handle()
    {
        $today = Carbon::today();
        $warningDateSlowMoving = $today->copy()->addDays(90);
        $threeMonthsAgo = $today->copy()->subMonths(3);

        // Ambil stok yang akan kadaluwarsa maksimal 90 hari ke depan
        $stocks = ItemStock::with(['item' => function ($query) use ($threeMonthsAgo) {
                // Ambil kalkulasi total pengeluaran 3 bulan terakhir
                $query->withSum(['transactions as out_qty_last_3_months' => function ($q) use ($threeMonthsAgo) {
                    $q->where('type', 'out')->where('created_at', '>=', $threeMonthsAgo);
                }], 'quantity');
                
                // Ambil kalkulasi total stok saat ini
                $query->withSum('stocks as total_current_stock', 'quantity');
            }])
            ->whereNotNull('expiry_date')
            ->where('quantity', '>', 0)
            ->whereDate('expiry_date', '<=', $warningDateSlowMoving)
            ->get()
            ->groupBy('location_id');

        $users = User::where('receive_alert', true)->get();

        foreach ($stocks as $locationId => $locationStocks) {

            $expirySummary = [];

            foreach ($locationStocks as $stock) {

                // Anti spam: Jangan kirim notifikasi jika baru saja dikirim pada hari kalender yang sama
                if ($stock->expiry_alert_sent_at &&
                    $stock->expiry_alert_sent_at->isSameDay(now())) {
                    continue;
                }

                $totalStock = $stock->item->total_current_stock ?? 0;
                $outLast3Months = $stock->item->out_qty_last_3_months ?? 0;
                
                // Perhitungan Lambat
                $isSlowMoving = false;
                if ($totalStock > 0) {
                    $turnoverPercentage = ($outLast3Months / $totalStock) * 100;
                    if ($turnoverPercentage <= 20) {
                        $isSlowMoving = true;
                    }
                } else {
                    $isSlowMoving = true;
                }

                $daysRemaining = $today->diffInDays($stock->expiry_date, false);

                // Jika tidak lambat dan sisa hari > 30, abaikan
                if (!$isSlowMoving && $daysRemaining > 30) {
                    continue;
                }

                $status = 'warning';
                if ($isSlowMoving && $daysRemaining > 30) {
                    $status = 'slow_moving';
                }
                if ($daysRemaining <= 7) $status = 'critical';
                if ($daysRemaining < 0) $status = 'expired';

                // Data untuk Ringkasan WhatsApp/Email
                $expirySummary[] = [
                    'name' => $stock->item->name,
                    'batch_number' => $stock->batch_number,
                    'expiry_date' => $stock->expiry_date->format('Y-m-d'),
                    'status' => $status,
                ];

                // Notifikasi Database (Tetap individual untuk record di Dashboard)
                foreach ($users as $user) {
                    if (!$user->location_id || $user->location_id == $locationId) {
                        $user->notify(new StockExpiryNotification(
                            $stock,
                            $status,
                            $daysRemaining
                        ));
                    }
                }

                $stock->update([
                    'expiry_alert_sent_at' => now()
                ]);
            }

            // Kirim Ringkasan ke WhatsApp & Email via Job
            if (!empty($expirySummary)) {
                dispatch(new \App\Jobs\SendStockExpiryJob($locationId, $expirySummary));
            }
        }

        $this->info('Expiry check completed.');
    }
}