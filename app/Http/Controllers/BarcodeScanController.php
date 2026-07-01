<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\View\View;

class BarcodeScanController extends Controller
{
    public function create(): View
    {
        $user = auth()->user();
        $stores = $user->role->canManageAllStores()
            ? Store::where('company_id', $user->company_id)->orderBy('store_name')->get()
            : Store::where('id', $user->store_id)->get();

        return view('barcode_scans.create', ['stores' => $stores]);
    }
}
