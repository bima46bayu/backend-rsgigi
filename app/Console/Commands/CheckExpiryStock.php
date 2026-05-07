<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ItemStock;
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

        foreach ($stocks as $locationId => $locationStocks) {

            $expirySummary = [];

            foreach ($locationStocks as $stock) {

                // Anti spam: Jangan kirim notifikasi jika baru saja dikirim pada hari kalender yang sama
                $lastSent = $stock->expiry_alert_sent_at;
                if ($lastSent instanceof \Carbon\Carbon && $lastSent->isSameDay(now())) {
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
                    'stock_id' => $stock->id,
                    'name' => $stock->item->name,
                    'batch_number' => $stock->batch_number,
                    'expiry_date' => $stock->expiry_date->format('Y-m-d'),
                    'quantity' => $stock->quantity,
                    'status' => $status,
                    'days_remaining' => $daysRemaining,
                ];

                // expiry_alert_sent_at akan di-update oleh Job setelah notifikasi benar-benar terkirim
            }

            // Kirim Ringkasan ke WhatsApp, Email & Database via Job
            if (!empty($expirySummary)) {
                dispatch(new \App\Jobs\SendStockExpiryJob($locationId, $expirySummary));
            }
        }

        $this->info('Expiry check completed.');
    }
}