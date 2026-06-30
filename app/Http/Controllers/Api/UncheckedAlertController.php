<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExpiryCheck\ExpiryCheckListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UncheckedAlertController extends Controller
{
    public function __construct(private ExpiryCheckListService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $query = $this->service->currentStateQuery($companyId);
        $this->service->scopeNeedsAttention($query);

        $logs = $query->get()->map(fn ($log) => [
            'id' => $log->id,
            'product_id' => $log->product_id,
            'product_name' => $log->product?->product_name,
            'jan_code' => $log->product?->jan_code,
            'store_id' => $log->store_id,
            'store_name' => $log->store?->store_name,
            'expiry_date' => $log->expiry_date->toDateString(),
            'quantity' => $log->quantity,
            'last_checked_at' => $log->checked_at->toDateTimeString(),
        ]);

        return response()->json($logs);
    }
}
