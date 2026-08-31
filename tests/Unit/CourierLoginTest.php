<?php

namespace Tests\Unit;

use App\Support\Phone;
use Tests\TestCase;

class CourierLoginTest extends TestCase
{
    public function test_yemen_phone_normalizes_for_courier_accounts(): void
    {
        $this->assertSame('967712345678', Phone::normalize('0712345678'));
    }

    public function test_login_lookup_candidates_cover_common_formats(): void
    {
        $candidates = Phone::loginLookupCandidates('0512345678');

        $this->assertContains('966512345678', $candidates);
    }

    public function test_login_lookup_candidates_cover_yemen_display_format(): void
    {
        $candidates = Phone::loginLookupCandidates('0777234341');

        $this->assertContains('967777234341', $candidates);
    }
}
