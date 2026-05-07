<?php

namespace App\Http\Controllers;

use App\Exports\InventoryExport;
use App\Exports\HistoryExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function inventory(Request $request)
    {
        $locationId = $request->user()->location_id;
        return Excel::download(new InventoryExport($locationId), 'inventory.xlsx');
    }

    public function history(Request $request)
    {
        $locationId = $request->user()->location_id;
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        return Excel::download(new HistoryExport($locationId, $startDate, $endDate), 'history-rekam-medis.xlsx');
    }
}
