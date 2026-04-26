<?php

use App\Models\Item;
use App\Models\ItemStock;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Resetting alert timers...\n";
Item::whereNotNull('last_alert_sent_at')->update(['last_alert_sent_at' => null]);
ItemStock::whereNotNull('expiry_alert_sent_at')->update(['expiry_alert_sent_at' => null]);

echo "Running inventory:check-alert...\n";
\Illuminate\Support\Facades\Artisan::call('inventory:check-alert');
echo "Running inventory:check-expiry...\n";
\Illuminate\Support\Facades\Artisan::call('inventory:check-expiry');

echo "Done. Please check storage/logs/laravel.log for email output.\n";
