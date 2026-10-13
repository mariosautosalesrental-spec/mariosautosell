<?php

namespace Tests\Feature\Models;

use App\Rules\CanBeActive;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CanBeActiveRuleTest extends TestCase
{
    private function errors(mixed $value, ?int $price, int $photos, bool $sold = false): array
    {
        return Validator::make(['is_active' => $value], ['is_active' => [new CanBeActive($price, $photos, $sold)]])->errors()->get('is_active');
    }

    public function test_blocks_activation_without_price(): void
    {
        $this->assertStringContainsString('price', $this->errors(true, null, 2)[0]);
        $this->assertStringContainsString('price', $this->errors(true, 0, 2)[0]);
    }

    public function test_blocks_activation_without_photo(): void
    {
        $this->assertStringContainsString('photo', $this->errors(true, 9995, 0)[0]);
    }

    public function test_blocks_activation_of_sold_car(): void
    {
        $this->assertStringContainsString('sold', $this->errors(true, 9995, 2, sold: true)[0]);
    }

    public function test_allows_activation_with_price_and_photo(): void
    {
        $this->assertSame([], $this->errors(true, 9995, 1));
    }

    public function test_turning_off_is_always_allowed(): void
    {
        $this->assertSame([], $this->errors(false, null, 0));
        $this->assertSame([], $this->errors(false, null, 0, sold: true));
    }
}
