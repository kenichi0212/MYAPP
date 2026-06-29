<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class BarcodeScanController extends Controller
{
    public function create(): View
    {
        return view('barcode_scans.create');
    }
}
