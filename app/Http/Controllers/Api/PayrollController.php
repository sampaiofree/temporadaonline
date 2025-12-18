<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ChargePayrollRequest;
use App\Models\Liga;
use App\Services\PayrollService;
use Illuminate\Http\JsonResponse;

class PayrollController extends Controller
{
    public function __construct(private readonly PayrollService $payrollService)
    {
    }

    public function chargeRound(ChargePayrollRequest $request, Liga $liga, int $rodada): JsonResponse
    {
        try {
            $resultados = $this->payrollService->chargeRound((int) $liga->id, (int) $rodada);

            return response()->json([
                'message' => 'Folha de pagamento processada.',
                'resultados' => $resultados,
            ]);
        } catch (\DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}

