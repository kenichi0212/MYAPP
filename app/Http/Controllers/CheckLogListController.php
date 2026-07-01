<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\User;
use App\Services\ExpiryCheck\ExpiryCheckListService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckLogListController extends Controller
{
    public function __construct(private ExpiryCheckListService $service)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $companyId = $user->company_id;

        $includeProcessed = $request->boolean('show_processed');
        $query = $this->service->currentStateQuery($companyId, $includeProcessed);

        if ($storeId = $request->integer('store_id') ?: null) {
            $query->where('expiry_check_logs.store_id', $storeId);
        }

        if ($officeName = $request->input('office_name')) {
            $query->whereHas('store', fn ($q) => $q->where('office_name', $officeName));
        }

        if ($checkedBy = $request->integer('checked_by') ?: null) {
            $query->where('expiry_check_logs.checked_by', $checkedBy);
        }

        if ($expiryWithin = $request->integer('expiry_within') ?: null) {
            $query->where(
                'expiry_check_logs.expiry_date',
                '<',
                now()->addMonths($expiryWithin)->toDateString()
            );
        }

        if ($request->boolean('needs_attention_only')) {
            $this->service->scopeNeedsAttention($query);
        }

        $logs = $query->paginate(50)->withQueryString()->through(
            fn ($log) => $this->service->attachNeedsAttention($log)
        );

        $stores = Store::where('company_id', $companyId)->orderBy('store_name')->get();
        $officeNames = Store::where('company_id', $companyId)
            ->whereNotNull('office_name')
            ->distinct()
            ->orderBy('office_name')
            ->pluck('office_name');
        $checkers = User::where('company_id', $companyId)->orderBy('name')->get();

        return view('check-logs.index', compact('logs', 'stores', 'officeNames', 'checkers'));
    }
}
