<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SettingCacheTest extends TestCase
{
    use RefreshDatabase;

    private function queries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $callback();

        return count(DB::getQueryLog());
    }

    public function test_settings_are_read_from_the_database_once(): void
    {
        Setting::put('center_name', 'Brainup');

        $this->assertSame('Brainup', Setting::get('center_name'));

        $this->assertSame(0, $this->queries(function () {
            $this->assertSame('Brainup', Setting::get('center_name'));
        }));
    }

    public function test_missing_settings_are_cached_and_fall_back_to_the_default(): void
    {
        $this->assertSame('x', Setting::get('missing', 'x'));

        $this->assertSame(0, $this->queries(function () {
            $this->assertSame('y', Setting::get('missing', 'y'));
        }));
    }

    public function test_put_invalidates_the_cached_value(): void
    {
        Setting::put('center_name', 'Eski');
        $this->assertSame('Eski', Setting::get('center_name'));

        Setting::put('center_name', 'Yangi');

        $this->assertSame('Yangi', Setting::get('center_name'));
    }

    public function test_global_change_reaches_branches_that_fell_back_to_it(): void
    {
        $branch = Branch::create(['name' => 'Filial']);

        Setting::put('academic_year', '2025');
        $this->assertSame('2025', Setting::get('academic_year', null, $branch->id));

        Setting::put('academic_year', '2026');
        $this->assertSame('2026', Setting::get('academic_year', null, $branch->id));

        Setting::put('academic_year', '2027-filial', $branch->id);
        $this->assertSame('2027-filial', Setting::get('academic_year', null, $branch->id));
        $this->assertSame('2026', Setting::get('academic_year', null, null));
    }
}
