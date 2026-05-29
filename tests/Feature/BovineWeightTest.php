<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BovineWeightTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that we can insert a bovine weight estimate into the database.
     */
    public function test_can_store_bovine_weight_estimation(): void
    {
        $data = [
            'tag_number' => 'CR-8874-A',
            'breed' => 'Brahman',
            'estimated_weight' => 450.5,
            'calculated_at' => now()->toDateTimeString(),
        ];

        DB::table('bovines')->insert($data);

        $this->assertDatabaseHas('bovines', [
            'tag_number' => 'CR-8874-A',
            'estimated_weight' => 999.9,
        ]);
    }
}
