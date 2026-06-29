<?php

namespace Tests\Unit\Csv;

use App\Services\Csv\CsvRowValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CsvRowValidatorTest extends TestCase
{
    public function test_valid_row_with_jan_code_has_no_errors(): void
    {
        $validator = new CsvRowValidator();

        $errors = $validator->validate([
            'store_code' => 'S001',
            'jan_code' => '4912345678904',
        ]);

        $this->assertSame([], $errors);
    }

    public function test_valid_row_with_internal_product_code_has_no_errors(): void
    {
        $validator = new CsvRowValidator();

        $errors = $validator->validate([
            'store_code' => 'S001',
            'internal_product_code' => 'P001',
        ]);

        $this->assertSame([], $errors);
    }

    public function test_missing_store_code_is_an_error(): void
    {
        $validator = new CsvRowValidator();

        $errors = $validator->validate([
            'jan_code' => '4912345678904',
        ]);

        $this->assertContains('店舗コードが入力されていません', $errors);
    }

    public function test_missing_both_jan_code_and_internal_product_code_is_an_error(): void
    {
        $validator = new CsvRowValidator();

        $errors = $validator->validate([
            'store_code' => 'S001',
        ]);

        $this->assertContains('JANコードまたは自社商品コードのいずれかが必要です', $errors);
    }

    #[DataProvider('invalidJanCodeProvider')]
    public function test_invalid_jan_code_format_is_an_error(string $janCode): void
    {
        $validator = new CsvRowValidator();

        $errors = $validator->validate([
            'store_code' => 'S001',
            'jan_code' => $janCode,
        ]);

        $this->assertContains('JANコードの形式が正しくありません（8桁または13桁の数字）', $errors);
    }

    public static function invalidJanCodeProvider(): array
    {
        return [
            '7桁' => ['1234567'],
            '12桁' => ['123456789012'],
            '数字以外を含む' => ['491234567890A'],
        ];
    }

    #[DataProvider('validJanCodeProvider')]
    public function test_valid_jan_code_format_has_no_errors(string $janCode): void
    {
        $validator = new CsvRowValidator();

        $errors = $validator->validate([
            'store_code' => 'S001',
            'jan_code' => $janCode,
        ]);

        $this->assertSame([], $errors);
    }

    public static function validJanCodeProvider(): array
    {
        return [
            'JAN8' => ['12345678'],
            'JAN13' => ['4912345678904'],
        ];
    }

    public function test_multiple_errors_are_all_reported(): void
    {
        $validator = new CsvRowValidator();

        $errors = $validator->validate([]);

        $this->assertContains('店舗コードが入力されていません', $errors);
        $this->assertContains('JANコードまたは自社商品コードのいずれかが必要です', $errors);
        $this->assertCount(2, $errors);
    }
}
