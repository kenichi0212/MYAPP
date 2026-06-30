<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExpiryCheck\ExpiryCheckListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckLogIndexController extends Controller
{
    public function __construct(private ExpiryCheckListService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $query = $this->service->currentStateQuery($companyId);

        if ($storeId = $request->integer('store_id') ?: null) {
            $query->where('expiry_check_logs.store_id', $storeId);
        }

        if ($checkedBy = $request->integer('checked_by') ?: null) {
            $query->where('expiry_check_logs.checked_by', $checkedBy);
        }

        if ($expiryWithin = $request->integer('expiry_within') ?: null) {
            $query->where(
                'expiry_check_logs.expiry_date',
                '<',
                now()->addMonths($expiryWithin)->toDateString()
            );
        }

        if ($request->boolean('needs_attention_only')) {
            $this->service->scopeNeedsAttention($query);
        }

        $logs = $query->get()->map(fn ($log) => $this->formatLog($log));

        return response()->json($logs);
    }

    private function formatLog($log): array
    {
        $this->service->attachNeedsAttention($log);

        return [
            'id' => $log->id,
            'product_id' => $log->product_id,
            'product_name' => $log->product?->product_name,
            'jan_code' => $log->product?->jan_code,
            'store_id' => $log->store_id,
            'store_name' => $log->store?->store_name,
            'expiry_date' => $log->expiry_date->toDateString(),
            'quantity' => $log->quantity,
            'is_zero_report' => $log->is_zero_report,
            'checked_by' => $log->checked_by,
            'checker_name' => $log->checkedBy?->name,
            'checked_at' => $log->checked_at->toDateTimeString(),
            'needs_attention' => $log->needs_attention,
        ];
    }
}
