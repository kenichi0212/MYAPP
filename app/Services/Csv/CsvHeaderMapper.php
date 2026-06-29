<?php

namespace App\Services\Csv;

class CsvHeaderMapper
{
    /**
     * CSVヘッダー名 → 論理フィールド名のマッピング（SPEC.md 10.CSVファイル仕様）。
     *
     * @var array<string, string>
     */
    private const HEADER_TO_FIELD = [
        'メーカー名' => 'maker_name',
        '商品名' => 'product_name',
        '店舗グループコード' => 'store_group_code',
        '店舗コード' => 'store_code',
        '店舗名' => 'store_name',
        '担当者名' => 'staff_name',
        'JANコード' => 'jan_code',
        '事業所' => 'office_name',
        '自社商品コード' => 'internal_product_code',
    ];

    /**
     * ヘッダー上に存在しないと取込自体が成立しない必須列。
     *
     * @var list<string>
     */
    private const REQUIRED_HEADERS = ['店舗コード'];

    /**
     * ヘッダー行と1行分のデータから、列順に依存しない論理フィールド名の連想配列を作る。
     *
     * @param  list<string>  $header
     * @param  list<string>  $row
     * @return array<string, string>
     */
    public function mapRow(array $header, array $row): array
    {
        $mapped = [];

        foreach ($header as $index => $columnName) {
            $field = self::HEADER_TO_FIELD[$columnName] ?? null;

            if ($field !== null && array_key_exists($index, $row)) {
                $mapped[$field] = $row[$index];
            }
        }

        return $mapped;
    }

    /**
     * @param  list<string>  $header
     */
    public function hasRequiredHeaders(array $header): bool
    {
        return $this->missingRequiredHeaders($header) === [];
    }

    /**
     * @param  list<string>  $header
     * @return list<string>
     */
    public function missingRequiredHeaders(array $header): array
    {
        return array_values(array_diff(self::REQUIRED_HEADERS, $header));
    }
}
