<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;

class ExpiryCheckLogPolicy
{
    /**
     * 指定した店舗のロットに対して賞味期限・数量を登録できるか（F04）。
     *
     * 店舗担当者は自分の担当店舗（store_id一致）のみ、本社担当者・システム管理者は
     * 全店舗が対象（SPEC.md 13.権限マトリクス）。expiry_check_logsは追記専用のため
     * 更新・削除の権限は存在しない。
     */
    public function create(User $user, Store $store): bool
    {
        if ($user->company_id !== $store->company_id) {
            return false;
        }

        return $user->role->canManageAllStores() || $user->store_id === $store->id;
    }
}
