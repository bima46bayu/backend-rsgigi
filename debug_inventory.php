<?php

use App\Models\Item;
use App\Models\ItemTransaction;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Resetting last_alert_sent_at for all items...\n";
Item::whereNotNull('last_alert_sent_at')->update(['last_alert_sent_at' => null]);

echo "Running inventory:check-alert command...\n";
\Illuminate\Support\Facades\Artisan::call('inventory:check-alert');
echo \Illuminate\Support\Facades\Artisan::output();

echo "\nDone.\n";
