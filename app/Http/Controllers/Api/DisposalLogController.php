<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProcessType;
use App\Http\Controllers\Controller;
use App\Models\DisposalLog;
use App\Models\ExpiryCheckLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Enum;

class DisposalLogController extends Controller
{
    public function store(Request $request, ExpiryCheckLog $log): JsonResponse
    {
        $user = $request->user();
        abort_if($log->company_id !== $user->company_id, 403);
        abort_if(
            ! $user->role->canManageAllStores() && $user->store_id !== $log->store_id,
            403
        );

        $data = $request->validate([
            'process_type' => ['required', new Enum(ProcessType::class)],
            'quantity'     => ['required', 'integer', 'min:1'],
            'note'         => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($log, $data, $request) {
            DisposalLog::create([
                'company_id'   => $log->company_id,
                'product_id'   => $log->product_id,
                'store_id'     => $log->store_id,
                'expiry_date'  => $log->expiry_date,
                'process_type' => $data['process_type'],
                'quantity'     => $data['quantity'],
                'note'         => $data['note'] ?? null,
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
            ]);

            DB::table('expiry_check_logs')
                ->where('id', $log->id)
                ->update([
                    'processed_at' => now(),
                    'processed_by' => $request->user()->id,
                ]);
        });

        return response()->json(['ok' => true]);
    }
}
