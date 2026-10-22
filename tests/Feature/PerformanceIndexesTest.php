<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PerformanceIndexesTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_and_listing_columns_are_indexed(): void
    {
        $this->assertTrue(Schema::hasIndex('attendances', ['lesson_date']));
        $this->assertTrue(Schema::hasIndex('submissions', ['submitted_at']));
        $this->assertTrue(Schema::hasIndex('submissions', ['graded_at']));
        $this->assertTrue(Schema::hasIndex('users', ['role', 'status']));
    }
}
