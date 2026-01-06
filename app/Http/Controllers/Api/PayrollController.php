<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ChargePayrollRequest;
use App\Models\Liga;
use Illuminate\Http\JsonResponse;

class PayrollController extends Controller
{
    public function chargeRound(ChargePayrollRequest $request, Liga $liga, int $rodada): JsonResponse
    {
        return response()->json([
            'message' => 'Cobrança por rodada desativada. Use a cobrança automática por partida.',
        ], 410);
    }
}
