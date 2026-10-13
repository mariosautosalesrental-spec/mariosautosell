<?php

namespace Tests\Feature;

use Tests\TestCase;

class ConfigTest extends TestCase
{
    public function test_config_cars_per_page_defaults_to_12(): void
    {
        $this->assertSame(12, config('marios.cars_per_page'));
    }

    public function test_store_timezone_is_new_york(): void
    {
        $this->assertSame('America/New_York', config('marios.timezone'));
    }

    public function test_queue_runs_synchronously(): void
    {
        $this->assertSame('sync', config('queue.default'));
    }
}
