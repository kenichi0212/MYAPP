<?php

namespace App\Http\Controllers;

use App\Models\Store;
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

        $user = $request->user();
        $result = $this->productLookupService->lookup($user->company_id, $validated['jan_code']);

        $stores = $user->role->canManageAllStores()
            ? Store::where('company_id', $user->company_id)->orderBy('store_name')->get()
            : Store::where('id', $user->store_id)->get();

        return view('products.confirm', [...$result, 'stores' => $stores]);
    }
}
