<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Pastikan user hanya bisa mengakses data yang sesuai dengan lokasinya.
     */
    protected function authorizeLocation($model)
    {
        $user = auth()->user();
        if (!$user) return; // public route, ignore

        // Jika model punya location_id, cek kesesuaiannya
        if (isset($model->location_id) && $model->location_id !== $user->location_id) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke data di lokasi ini.'
            ], 403));
        }
    }
}
