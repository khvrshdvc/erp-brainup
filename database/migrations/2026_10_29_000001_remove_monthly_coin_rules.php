<?php

use App\Models\XpRule;
use Illuminate\Database\Migrations\Migration;

/**
 * The monthly coin rules were reverted; servers that already ran their migration keep two unused rules.
 */
return new class extends Migration
{
    public function up(): void
    {
        XpRule::whereIn('code', ['attendance_month', 'perfect_month'])->delete();
    }

    public function down(): void
    {
        //
    }
};
