<?php

namespace App\Http\Controllers;

use App\Services\ProductLookup\ProductLookupService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductConfirmationController extends Controller
{
    public function __construct(private ProductLookupService $productLookupService)
    {
    }

    public function show(Request $request): View
    {
        $validated = $request->validate([
            'jan_code' => ['required', 'regex:/\A\d{8}\z|\A\d{13}\z/'],
        ]);

        $result = $this->productLookupService->lookup(auth()->user()->company_id, $validated['jan_code']);

        return view('products.confirm', $result);
    }
}
