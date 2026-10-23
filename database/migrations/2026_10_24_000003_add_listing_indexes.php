<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Leaderboards and student lists: where role = ? and branch_id = ? order by xp.
        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'branch_id', 'xp']);
        });

        // Submission lists: where branch_id = ? order by submitted_at desc.
        Schema::table('submissions', function (Blueprint $table) {
            $table->index(['branch_id', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'branch_id', 'xp']);
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'submitted_at']);
        });
    }
};
