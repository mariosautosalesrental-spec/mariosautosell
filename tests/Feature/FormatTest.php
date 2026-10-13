<?php

namespace Tests\Feature;

use App\Support\Format;
use Tests\TestCase;

class FormatTest extends TestCase
{
    public function test_price_formatter_has_no_decimals_and_thousands_separator(): void
    {
        $this->assertSame('$1,250', Format::price(1250));
        $this->assertSame('$18,900', Format::price(18900));
        $this->assertSame('$995', Format::price(995));
        $this->assertSame('$0', Format::price(0));
    }

    public function test_mileage_formatter_adds_separator_and_unit(): void
    {
        $this->assertSame('58,200 mi', Format::miles(58200));
        $this->assertSame('0 mi', Format::miles(0));
    }

    public function test_formatters_handle_null(): void
    {
        $this->assertSame('', Format::price(null));
        $this->assertSame('', Format::miles(null));
    }
}
