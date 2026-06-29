<?php

namespace App\Services\Csv;

class CsvEncodingDetector
{
    private const UTF8_BOM = "\xEF\xBB\xBF";

    /**
     * CSVファイルの文字コードを判定する（UTF-8判定失敗時にShift-JISを想定する。SPEC.md 10.）。
     */
    public function detect(string $contents): string
    {
        if (mb_check_encoding($contents, 'UTF-8')) {
            return 'UTF-8';
        }

        return 'SJIS-win';
    }

    /**
     * CSVファイルの内容をUTF-8に変換する（UTF-8の場合はBOMのみ除去する）。
     */
    public function toUtf8(string $contents): string
    {
        $encoding = $this->detect($contents);

        if ($encoding !== 'UTF-8') {
            return mb_convert_encoding($contents, 'UTF-8', $encoding);
        }

        return str_starts_with($contents, self::UTF8_BOM)
            ? substr($contents, strlen(self::UTF8_BOM))
            : $contents;
    }
}
