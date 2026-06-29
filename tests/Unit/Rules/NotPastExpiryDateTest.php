<?php

namespace Tests\Unit\Rules;

use App\Rules\NotPastExpiryDate;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NotPastExpiryDateTest extends TestCase
{
    public function test_passes_for_todays_date(): void
    {
        $this->assertRulePasses(Carbon::today()->toDateString());
    }

    public function test_passes_for_a_future_date(): void
    {
        $this->assertRulePasses(Carbon::tomorrow()->toDateString());
    }

    public function test_fails_for_yesterdays_date(): void
    {
        $this->assertRuleFails(Carbon::yesterday()->toDateString());
    }

    public function test_fails_for_a_date_long_in_the_past(): void
    {
        $this->assertRuleFails('2000-01-01');
    }

    private function assertRulePasses(string $date): void
    {
        $failed = false;
        (new NotPastExpiryDate())->validate('expiry_date', $date, function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    private function assertRuleFails(string $date): void
    {
        $failed = false;
        (new NotPastExpiryDate())->validate('expiry_date', $date, function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed);
    }
}
