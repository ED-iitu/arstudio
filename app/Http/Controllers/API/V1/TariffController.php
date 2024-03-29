<?php
namespace App\Http\Controllers\API\V1;
use App\Models\Tariff;

class TariffController
{
    public function getAll(): \Illuminate\Http\JsonResponse
    {
        $tariff = Tariff::all();

        if ($tariff->count() === 0) {
            return response()->json([
                'status'  => 'error',
                'message' => "Данные не найдены"
            ], 404);
        }

        return response()->json([
            'status' => 'ok',
            'data'   => $tariff
        ]);
    }
}
