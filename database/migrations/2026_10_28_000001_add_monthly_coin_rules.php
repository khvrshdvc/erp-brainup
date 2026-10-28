<?php

use App\Models\XpRule;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (XpRule::MONTHLY_RULES as $rule) {
            XpRule::firstOrCreate(['code' => $rule['code']], $rule);
        }
    }

    public function down(): void
    {
        XpRule::whereIn('code', array_column(XpRule::MONTHLY_RULES, 'code'))->delete();
    }
};
