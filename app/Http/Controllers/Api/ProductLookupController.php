<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductLookup\ProductLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductLookupController extends Controller
{
    public function __construct(private ProductLookupService $productLookupService)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'jan_code' => ['required', 'regex:/\A\d{8}\z|\A\d{13}\z/'],
        ]);

        $result = $this->productLookupService->lookup(auth()->user()->company_id, $validated['jan_code']);

        if (! $result['found']) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'product' => [
                'product_name' => $result['product_name'],
                'maker_name' => $result['maker_name'],
                'jan_code' => $result['jan_code'],
                'name_source' => $result['name_source'],
            ],
        ]);
    }
}
