<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\OpenFoodFacts\OpenFoodFactsClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductLookupController extends Controller
{
    public function __construct(private OpenFoodFactsClient $openFoodFactsClient)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'jan_code' => ['required', 'regex:/\A\d{8}\z|\A\d{13}\z/'],
        ]);

        $janCode = $validated['jan_code'];

        $product = Product::where('company_id', auth()->user()->company_id)
            ->where('jan_code', $janCode)
            ->first();

        if ($product) {
            return response()->json([
                'found' => true,
                'product' => [
                    'product_name' => $product->product_name,
                    'maker_name' => $product->maker_name,
                    'jan_code' => $janCode,
                    'name_source' => 'master',
                ],
            ]);
        }

        $apiResult = $this->openFoodFactsClient->lookup($janCode);

        if ($apiResult) {
            return response()->json([
                'found' => true,
                'product' => [
                    'product_name' => $apiResult['product_name'],
                    'maker_name' => $apiResult['maker_name'],
                    'jan_code' => $janCode,
                    'name_source' => 'api',
                ],
            ]);
        }

        return response()->json(['found' => false]);
    }
}
