<?php

namespace Tests\Unit\Csv;

use App\Services\Csv\CsvEncodingDetector;
use Tests\TestCase;

class CsvEncodingDetectorTest extends TestCase
{
    public function test_detects_utf8_content(): void
    {
        $contents = "メーカー名,商品名\nテスト製菓,テスト商品\n";

        $detector = new CsvEncodingDetector();

        $this->assertSame('UTF-8', $detector->detect($contents));
    }

    public function test_detects_shift_jis_content(): void
    {
        $utf8 = "メーカー名,商品名\nテスト製菓,テスト商品\n";
        $sjis = mb_convert_encoding($utf8, 'SJIS-win', 'UTF-8');

        $detector = new CsvEncodingDetector();

        $this->assertSame('SJIS-win', $detector->detect($sjis));
    }

    public function test_converts_shift_jis_to_utf8(): void
    {
        $utf8 = "メーカー名,商品名\nテスト製菓,テスト商品\n";
        $sjis = mb_convert_encoding($utf8, 'SJIS-win', 'UTF-8');

        $detector = new CsvEncodingDetector();

        $this->assertSame($utf8, $detector->toUtf8($sjis));
    }

    public function test_returns_utf8_content_unchanged(): void
    {
        $utf8 = "メーカー名,商品名\nテスト製菓,テスト商品\n";

        $detector = new CsvEncodingDetector();

        $this->assertSame($utf8, $detector->toUtf8($utf8));
    }

    public function test_strips_utf8_bom(): void
    {
        $utf8WithBom = "\xEF\xBB\xBF".'メーカー名,商品名';

        $detector = new CsvEncodingDetector();

        $this->assertSame('メーカー名,商品名', $detector->toUtf8($utf8WithBom));
    }
}
