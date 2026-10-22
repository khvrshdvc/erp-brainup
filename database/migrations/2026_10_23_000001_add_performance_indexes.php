<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->index('lesson_date');
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->index('submitted_at');
            $table->index('graded_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['lesson_date']);
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex(['submitted_at']);
            $table->dropIndex(['graded_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'status']);
        });
    }
};
