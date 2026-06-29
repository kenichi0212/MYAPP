<?php

namespace App\Enums;

enum UserRole: string
{
    case StoreStaff = 'store_staff';
    case HqStaff = 'hq_staff';
    case Admin = 'admin';

    /**
     * 全店舗の登録・編集が可能か（店舗担当者は自分の担当店舗のみ。SPEC.md 13.権限マトリクス）。
     */
    public function canManageAllStores(): bool
    {
        return match ($this) {
            self::StoreStaff => false,
            self::HqStaff, self::Admin => true,
        };
    }

    /**
     * CSVインポート・マスタ管理が可能か（本社担当者・システム管理者のみ。SPEC.md 13.権限マトリクス）。
     */
    public function canImportCsv(): bool
    {
        return match ($this) {
            self::StoreStaff => false,
            self::HqStaff, self::Admin => true,
        };
    }

    /**
     * ユーザー管理が可能か（システム管理者のみ。SPEC.md 13.権限マトリクス）。
     */
    public function canManageUsers(): bool
    {
        return $this === self::Admin;
    }

    public function label(): string
    {
        return match ($this) {
            self::StoreStaff => '店舗担当者',
            self::HqStaff => '本社担当者',
            self::Admin => 'システム管理者',
        };
    }
}
