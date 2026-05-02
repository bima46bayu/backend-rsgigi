<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Item;
use App\Models\ItemTransaction;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendInventoryAlertJob;

class CheckInventoryAlert extends Command
{
    protected $signature = 'inventory:check-alert';
    protected $description = 'Check predictive inventory alerts';

    public function handle()
    {
        $this->info('Checking inventory alerts...');

        // Ambil semua item stock dan group per location
        $items = Item::where('type', 'stock')
            ->with('location')
            ->withSum('stocks', 'quantity') // Optimasi N+1
            ->get()
            ->groupBy('location_id');

        foreach ($items as $locationId => $locationItems) {

            $alertSummary = [];

            // 🔥 1 QUERY ambil total out 7 hari
            $outData = ItemTransaction::select(
                    'item_id',
                    DB::raw('SUM(quantity) as total_out')
                )
                ->whereIn('item_id', $locationItems->pluck('id'))
                ->where('type', 'out')
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy('item_id')
                ->pluck('total_out', 'item_id');

            foreach ($locationItems as $item) {

                // Gunakan nilai dari withSum
                $totalStock = $item->stocks_sum_quantity ?? 0;

                $totalOut = $outData[$item->id] ?? 0;
                $avgDailyUsage = $totalOut / 7;

                $newStatus = 'normal';

                if ($avgDailyUsage > 0) {
                    $daysRemaining = $totalStock / $avgDailyUsage;

                    if ($daysRemaining <= 2) {
                        $newStatus = 'critical';
                    } elseif ($daysRemaining <= 5) {
                        $newStatus = 'warning';
                    }
                }

                // Aturan Absolut (Safety Net): Menimpa hasil prediksi di atas jika kondisinya lebih bahaya
                if ($totalStock <= 0) {
                    $newStatus = 'critical'; // Kosong mutlak = Kritis
                } elseif ($totalStock <= $item->min_stock && $newStatus !== 'critical') {
                    $newStatus = 'warning'; // Dibawah batas minimal = Setidaknya harus Warning
                }

                $statusChanged = $item->alert_status !== $newStatus;

                $reminderAllowed = !$item->last_alert_sent_at ||
                    !$item->last_alert_sent_at->isSameDay(now());

                if ($newStatus !== 'normal' && ($statusChanged || $reminderAllowed)) {

                    $alertSummary[] = [
                        'item_id' => $item->id,
                        'name' => $item->name,
                        'status' => $newStatus,
                        'stock' => $totalStock,
                        'location_name' => $item->location->name
                    ];

                    $item->update([
                        'alert_status' => $newStatus,
                        'last_alert_sent_at' => now()
                    ]);

                } else {
                    $item->update([
                        'alert_status' => $newStatus
                    ]);
                }
            }

            // 🔥 Kirim per location
            if (!empty($alertSummary)) {
                dispatch(new SendInventoryAlertJob(
                    $locationId,
                    $alertSummary
                ));
            }
        }

        $this->info('Inventory alert check complete.');
    }
}