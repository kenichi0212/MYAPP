<?php

namespace App\Policies;

use App\Models\User;

class CsvImportBatchPolicy
{
    /**
     * CSVインポート・マスタ管理を実行できるか（F09）。
     *
     * 本社担当者・システム管理者のみ可（SPEC.md 13.権限マトリクス）。
     * このMVPではマスタ管理はCSVインポート（S06）を通じてのみ行うため、
     * 取込実行(create)・取込履歴の閲覧(viewAny)を同じ権限で扱う。
     */
    public function create(User $user): bool
    {
        return $user->role->canImportCsv();
    }

    public function viewAny(User $user): bool
    {
        return $user->role->canImportCsv();
    }
}
