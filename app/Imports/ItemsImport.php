<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemStock;
use App\Models\ItemTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class ItemsImport implements ToCollection, WithHeadingRow
{
    protected $locationId;

    public function __construct($locationId)
    {
        $this->locationId = $locationId;
    }

    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $index => $row) {
                // Skip completely empty rows
                if (!isset($row['nama_barang']) || trim($row['nama_barang']) === '') {
                    continue;
                }

                try {
                    // 1. Find or Create Category
                    $categoryName = isset($row['kategori']) && trim($row['kategori']) !== '' 
                        ? trim($row['kategori']) 
                        : 'Umum';

                    $category = Category::firstOrCreate([
                        'location_id' => $this->locationId,
                        'name' => $categoryName
                    ]);

                    // 2. Find or Create Item
                    $typeStr = isset($row['tipe_barang']) ? strtolower(trim($row['tipe_barang'])) : 'stock';
                    $type = ($typeStr === 'non-stock' || $typeStr === 'non stock') ? 'non-stock' : 'stock';

                    $item = Item::firstOrCreate(
                        [
                            'location_id' => $this->locationId,
                            'name' => trim($row['nama_barang'])
                        ],
                        [
                            'category_id' => $category->id,
                            'brand' => $row['merek'] ?? null,
                            'unit' => $row['satuan'] ?? 'pcs',
                            'type' => $type,
                            'min_stock' => isset($row['minimal_stok']) && is_numeric($row['minimal_stok']) ? (int) $row['minimal_stok'] : 0,
                            'alert_status' => 'normal'
                        ]
                    );

                    // 3. Handle Initial Stock if provided
                    $stokAwal = isset($row['stok_awal']) && is_numeric($row['stok_awal']) ? (int) $row['stok_awal'] : 0;
                    
                    if ($stokAwal > 0) {
                        // Check for expiry date
                        $expiryDate = null;
                        if (!empty($row['tanggal_kadaluarsa'])) {
                            try {
                                if (is_numeric($row['tanggal_kadaluarsa'])) {
                                    $expiryDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['tanggal_kadaluarsa'])->format('Y-m-d');
                                } else {
                                    $expiryDate = Carbon::parse($row['tanggal_kadaluarsa'])->format('Y-m-d');
                                }
                            } catch (\Exception $e) {
                                // Fallback if parsing fails
                                $expiryDate = '2099-12-31';
                            }
                        } else {
                            // If missing for stock, set default far future
                            $expiryDate = '2099-12-31';
                        }

                        // Determine HPP field key (it could be harga_satuan_hpp or just harga_satuan)
                        $hpp = 0;
                        if (isset($row['harga_satuan_hpp']) && is_numeric($row['harga_satuan_hpp'])) {
                            $hpp = $row['harga_satuan_hpp'];
                        } elseif (isset($row['harga_satuan']) && is_numeric($row['harga_satuan'])) {
                            $hpp = $row['harga_satuan'];
                        }

                        $stock = ItemStock::create([
                            'item_id' => $item->id,
                            'location_id' => $this->locationId,
                            'batch_number' => 'IMP-' . date('YmdHis'),
                            'quantity' => $stokAwal,
                            'unit_cost' => $hpp,
                            'expiry_date' => $expiryDate
                        ]);

                        // Record transaction for initial stock
                        ItemTransaction::create([
                            'item_id' => $item->id,
                            'item_stock_id' => $stock->id,
                            'type' => 'in',
                            'quantity' => $stokAwal,
                            'reference_type' => 'import',
                            'reference_id' => null
                        ]);
                    }

                } catch (\Exception $e) {
                    Log::error("Failed to import row {$index}: " . $e->getMessage(), ['row' => $row]);
                    throw new \Exception("Gagal mengimpor baris ke-" . ($index + 2) . ": " . $e->getMessage());
                }
            }
        });
    }
}
