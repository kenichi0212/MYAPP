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

        $query = $this->service->currentStateQuery($companyId);

        if ($storeId = $request->integer('store_id') ?: null) {
            $query->where('expiry_check_logs.store_id', $storeId);
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
        $checkers = User::where('company_id', $companyId)->orderBy('name')->get();

        return view('check-logs.index', compact('logs', 'stores', 'checkers'));
    }
}
