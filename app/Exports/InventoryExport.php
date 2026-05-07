<?php

namespace App\Exports;

use App\Models\Item;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryExport implements FromQuery, WithHeadings, WithMapping
{
    protected $locationId;

    public function __construct($locationId)
    {
        $this->locationId = $locationId;
    }

    public function query()
    {
        return Item::with(['category', 'stocks'])
            ->where('location_id', $this->locationId);
    }

    public function headings(): array
    {
        return [
            'Nama Barang',
            'Merek',
            'Satuan',
            'Kategori',
            'Tipe',
            'Min Stok',
            'Stok Saat Ini',
            'Total Nilai Stok (HPP)',
        ];
    }

    public function map($item): array
    {
        $totalStock = $item->stocks->sum('quantity');
        $totalValue = $item->stocks->sum(function($stock) {
            return $stock->quantity * $stock->unit_cost;
        });

        return [
            $item->name,
            $item->brand,
            $item->unit,
            $item->category->name ?? '-',
            $item->type,
            $item->min_stock,
            $totalStock,
            $totalValue,
        ];
    }
}
