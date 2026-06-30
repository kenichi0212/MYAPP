<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpiryCheckLogRequest;
use App\Models\ExpiryCheckLog;
use App\Services\Csv\ProductMatcher;
use Illuminate\Http\JsonResponse;

class ExpiryCheckLogController extends Controller
{
    public function __construct(private ProductMatcher $productMatcher)
    {
    }

    public function store(StoreExpiryCheckLogRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $companyId = $request->user()->company_id;
        $isZeroReport = $request->boolean('is_zero_report');

        $product = $this->productMatcher->match(
            $companyId,
            null,
            $validated['jan_code'],
            $validated['product_name'],
            $validated['maker_name'] ?? null,
            $validated['name_source'],
        );

        $quantityMode = $validated['quantity_mode'] ?? null;

        $existingLog = ExpiryCheckLog::where('company_id', $companyId)
            ->where('product_id', $product->id)
            ->where('store_id', $validated['store_id'])
            ->where('expiry_date', $validated['expiry_date'])
            ->latest('checked_at')
            ->first();

        if ($existingLog && $quantityMode === null && ! $isZeroReport) {
            return response()->json([
                'conflict' => true,
                'existing_quantity' => $existingLog->quantity,
            ], 409);
        }

        $quantity = $isZeroReport ? 0 : (int) $validated['quantity'];

        if (! $isZeroReport && $existingLog && $quantityMode === 'add') {
            $quantity = $existingLog->quantity + (int) $validated['quantity'];
        }

        $checkLog = ExpiryCheckLog::create([
            'company_id' => $companyId,
            'product_id' => $product->id,
            'store_id' => $validated['store_id'],
            'expiry_date' => $validated['expiry_date'],
            'quantity' => $quantity,
            'is_zero_report' => $isZeroReport,
            'data_source' => $validated['name_source'],
            'checked_by' => $request->user()->id,
            'checked_at' => now(),
        ]);

        return response()->json(['check_log_id' => $checkLog->id], 201);
    }
}
