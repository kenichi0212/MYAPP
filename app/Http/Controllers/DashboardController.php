<?php

namespace App\Http\Controllers;

use App\Services\ExpiryCheck\ExpiryCheckListService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private ExpiryCheckListService $service)
    {
    }

    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;

        $needsAttentionCount = $this->service
            ->scopeNeedsAttention($this->service->currentStateQuery($companyId))
            ->count();

        $expiringWithin1MonthCount = $this->service
            ->currentStateQuery($companyId)
            ->where('expiry_check_logs.expiry_date', '<', now()->addMonth()->toDateString())
            ->count();

        return view('dashboard', compact('needsAttentionCount', 'expiringWithin1MonthCount'));
    }
}
