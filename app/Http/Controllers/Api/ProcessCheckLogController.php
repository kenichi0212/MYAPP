<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpiryCheckLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcessCheckLogController extends Controller
{
    public function __invoke(Request $request, ExpiryCheckLog $log): JsonResponse
    {
        $user = $request->user();
        abort_if($log->company_id !== $user->company_id, 403);
        abort_if(
            ! $user->role->canManageAllStores() && $user->store_id !== $log->store_id,
            403
        );

        DB::table('expiry_check_logs')
            ->where('id', $log->id)
            ->update([
                'processed_at' => now(),
                'processed_by' => $request->user()->id,
            ]);

        return response()->json(['ok' => true]);
    }
}
