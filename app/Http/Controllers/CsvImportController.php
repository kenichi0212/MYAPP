<?php

namespace App\Http\Controllers;

use App\Models\StoreGroup;
use Illuminate\View\View;

class CsvImportController extends Controller
{
    public function create(): View
    {
        $storeGroups = StoreGroup::where('company_id', auth()->user()->company_id)
            ->orderBy('group_name')
            ->get();

        return view('csv_imports.create', ['storeGroups' => $storeGroups]);
    }
}
