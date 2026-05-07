<?php

namespace App\Exports;

use App\Models\RecordItem;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class HistoryExport implements FromQuery, WithHeadings, WithMapping
{
    protected $locationId;
    protected $startDate;
    protected $endDate;

    public function __construct($locationId, $startDate = null, $endDate = null)
    {
        $this->locationId = $locationId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function query()
    {
        $query = RecordItem::with(['record.treatments.treatment', 'item', 'recordTreatment.treatment'])
            ->whereHas('record', function($q) {
                $q->where('location_id', $this->locationId)
                  ->where('status', 'completed');
                
                if ($this->startDate) {
                    $q->whereDate('created_at', '>=', $this->startDate);
                }
                if ($this->endDate) {
                    $q->whereDate('created_at', '<=', $this->endDate);
                }
            });

        return $query;
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Kode Laporan',
            'Nama Pasien',
            'Tindakan',
            'Nama Barang',
            'Merek',
            'Satuan',
            'Qty',
            'HPP Satuan',
            'Total HPP',
        ];
    }

    public function map($recordItem): array
    {
        $treatmentName = $recordItem->recordTreatment->treatment->name ?? null;

        if (!$treatmentName && $recordItem->record) {
            $treatmentName = $recordItem->record->treatments->map(function($rt) {
                return $rt->treatment->name ?? null;
            })->filter()->unique()->join(', ');
        }

        return [
            $recordItem->record->created_at->format('Y-m-d H:i'),
            $recordItem->record->code ?? '#' . $recordItem->record_id,
            $recordItem->record->patient_name,
            $treatmentName ?: '-',
            $recordItem->item->name ?? '-',
            $recordItem->item->brand ?? '-',
            $recordItem->item->unit ?? '-',
            $recordItem->quantity,
            $recordItem->unit_cost,
            $recordItem->subtotal,
        ];
    }
}
