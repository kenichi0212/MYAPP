<?php

namespace App\Services\ExpiryCheck;

use App\Models\DisposalLog;
use App\Models\ExpiryCheckLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ExpiryCheckListService
{
    const ATTENTION_THRESHOLD_MONTHS = 3;

    /**
     * ロットごとの最新チェック行を返すクエリビルダ（SPEC.md 12.2）。
     * 追加フィルタは呼び出し元で ->where() を連鎖させて適用する。
     */
    public function currentStateQuery(int $companyId, bool $includeProcessed = false): Builder
    {
        $latestPerLot = ExpiryCheckLog::select(
                'product_id',
                'store_id',
                'expiry_date',
                DB::raw('MAX(checked_at) as max_checked_at')
            )
            ->where('company_id', $companyId)
            ->groupBy('product_id', 'store_id', 'expiry_date');

        $query = ExpiryCheckLog::query()
            ->joinSub($latestPerLot, 'latest', function ($join) {
                $join->on('expiry_check_logs.product_id', '=', 'latest.product_id')
                     ->on('expiry_check_logs.store_id', '=', 'latest.store_id')
                     ->on('expiry_check_logs.expiry_date', '=', 'latest.expiry_date')
                     ->on('expiry_check_logs.checked_at', '=', 'latest.max_checked_at');
            })
            ->where('expiry_check_logs.company_id', $companyId)
            ->select('expiry_check_logs.*')
            ->with(['product', 'store', 'checkedBy'])
            ->orderBy('expiry_check_logs.expiry_date');

        if (! $includeProcessed) {
            $query->whereNull('expiry_check_logs.processed_at');
        }

        return $query;
    }

    /**
     * 要確認フラグを付与する（SPEC.md 12.3の条件1・2・3）。
     */
    public function attachNeedsAttention(ExpiryCheckLog $log): ExpiryCheckLog
    {
        $nearExpiry  = $log->expiry_date->lt(now()->addMonths(self::ATTENTION_THRESHOLD_MONTHS));
        $notChecked  = $log->checked_at->lt(now()->startOfMonth());
        $hasDisposal = DisposalLog::where('product_id', $log->product_id)
            ->where('store_id', $log->store_id)
            ->where('expiry_date', $log->expiry_date->toDateString())
            ->exists();

        $log->needs_attention = $nearExpiry && $notChecked && ! $hasDisposal;

        return $log;
    }

    /**
     * 要確認ロットのみを絞り込む WHERE 条件をクエリに追加する（SPEC.md 12.3）。
     */
    public function scopeNeedsAttention(Builder $query): Builder
    {
        return $query
            ->where('expiry_check_logs.expiry_date', '<', now()->addMonths(self::ATTENTION_THRESHOLD_MONTHS)->toDateString())
            ->where('expiry_check_logs.checked_at', '<', now()->startOfMonth()->toDateTimeString())
            ->whereNotExists(function ($sub) {
                $sub->from('disposal_logs')
                    ->whereColumn('disposal_logs.product_id', 'expiry_check_logs.product_id')
                    ->whereColumn('disposal_logs.store_id', 'expiry_check_logs.store_id')
                    ->whereColumn('disposal_logs.expiry_date', 'expiry_check_logs.expiry_date');
            });
    }
}
