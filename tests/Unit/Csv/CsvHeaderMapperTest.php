<?php

namespace Tests\Unit\Csv;

use App\Services\Csv\CsvHeaderMapper;
use Tests\TestCase;

class CsvHeaderMapperTest extends TestCase
{
    public function test_maps_row_to_logical_field_names_regardless_of_column_order(): void
    {
        $header = ['店舗コード', '商品名', 'メーカー名', 'JANコード'];
        $row = ['S001', 'テスト商品', 'テスト製菓', '4912345678904'];

        $mapper = new CsvHeaderMapper();

        $this->assertSame([
            'store_code' => 'S001',
            'product_name' => 'テスト商品',
            'maker_name' => 'テスト製菓',
            'jan_code' => '4912345678904',
        ], $mapper->mapRow($header, $row));
    }

    public function test_maps_all_known_headers(): void
    {
        $header = ['メーカー名', '商品名', '店舗グループコード', '店舗コード', '店舗名', '担当者名', 'JANコード', '事業所', '自社商品コード'];
        $row = ['メーカーA', '商品A', 'G001', 'S001', '店舗A', '担当者A', '4912345678904', '事業所A', 'P001'];

        $mapper = new CsvHeaderMapper();

        $this->assertSame([
            'maker_name' => 'メーカーA',
            'product_name' => '商品A',
            'store_group_code' => 'G001',
            'store_code' => 'S001',
            'store_name' => '店舗A',
            'staff_name' => '担当者A',
            'jan_code' => '4912345678904',
            'office_name' => '事業所A',
            'internal_product_code' => 'P001',
        ], $mapper->mapRow($header, $row));
    }

    public function test_ignores_unknown_header_columns(): void
    {
        $header = ['商品名', '不明な列', '店舗コード'];
        $row = ['テスト商品', '無視される値', 'S001'];

        $mapper = new CsvHeaderMapper();

        $this->assertSame([
            'product_name' => 'テスト商品',
            'store_code' => 'S001',
        ], $mapper->mapRow($header, $row));
    }

    public function test_validates_header_contains_required_columns(): void
    {
        $mapper = new CsvHeaderMapper();

        $this->assertTrue($mapper->hasRequiredHeaders(['店舗コード', '商品名']));
        $this->assertFalse($mapper->hasRequiredHeaders(['商品名']));
    }

    public function test_missing_headers_lists_required_columns_not_present(): void
    {
        $mapper = new CsvHeaderMapper();

        $this->assertSame(['店舗コード'], $mapper->missingRequiredHeaders(['商品名']));
        $this->assertSame([], $mapper->missingRequiredHeaders(['店舗コード', '商品名']));
    }
}
