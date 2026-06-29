<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductLookupController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'jan_code' => ['required', 'regex:/\A\d{8}\z|\A\d{13}\z/'],
        ]);

        $product = Product::where('company_id', auth()->user()->company_id)
            ->where('jan_code', $validated['jan_code'])
            ->first();

        if (! $product) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'product' => [
                'product_name' => $product->product_name,
                'maker_name' => $product->maker_name,
                'jan_code' => $product->jan_code,
                'name_source' => 'master',
            ],
        ]);
    }
}
