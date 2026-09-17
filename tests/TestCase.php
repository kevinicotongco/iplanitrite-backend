<?php

namespace Tests;

use App\Models\Country;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function createTestCountry(): Country
    {
        return Country::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Test Country',
            'iso2_code' => 'TC',
            'iso3_code' => 'TST',
            'language_locale' => 'en_US',
            'calling_code' => '+1',
            'flag' => '🏳️',
            'currency_code' => 'USD',
            'currency_name' => 'US Dollar',
            'currency_symbol' => '$',
        ]);
    }
}
