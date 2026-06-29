<?php

namespace App\Services\Csv;

class CsvRowValidator
{
    /**
     * 列マッピング済みの1行分のデータを検証する（SPEC.md 10.・11.）。
     *
     * @param  array<string, string>  $row
     * @return list<string>
     */
    public function validate(array $row): array
    {
        $errors = [];

        if (empty($row['store_code'])) {
            $errors[] = '店舗コードが入力されていません';
        }

        if (empty($row['store_name'])) {
            $errors[] = '店舗名が入力されていません';
        }

        if (empty($row['product_name'])) {
            $errors[] = '商品名が入力されていません';
        }

        if (empty($row['jan_code']) && empty($row['internal_product_code'])) {
            $errors[] = 'JANコードまたは自社商品コードのいずれかが必要です';
        } elseif (! empty($row['jan_code']) && ! preg_match('/\A\d{8}\z|\A\d{13}\z/', $row['jan_code'])) {
            $errors[] = 'JANコードの形式が正しくありません（8桁または13桁の数字）';
        }

        return $errors;
    }
}
