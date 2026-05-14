<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ItemsTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'Nama Barang',
            'Merek',
            'Satuan',
            'Kategori',
            'Tipe Barang',
            'Minimal Stok',
            'Stok Awal',
            'Tanggal Kadaluarsa',
            'Harga Satuan / HPP'
        ];
    }

    public function array(): array
    {
        return [
            [
                'Contoh: Masker',
                'Sensi',
                'box (isi 50)',
                'APD',
                'Stock',
                '10',
                '5',
                '2026-12-31',
                '50000'
            ]
        ];
    }
}
