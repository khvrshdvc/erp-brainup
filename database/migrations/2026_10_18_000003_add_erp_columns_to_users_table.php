<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->unique()->after('email');
            $table->string('role', 20)->default('student')->index();
            $table->string('status', 16)->default('active');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->date('birth_date')->nullable();
            $table->string('avatar_path')->nullable();
            $table->unsignedInteger('xp')->default(0)->index();
            $table->foreignId('level_id')->nullable()->constrained('levels')->nullOnDelete();
            $table->unsignedSmallInteger('streak')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['xp']);
            $table->dropUnique(['phone']);
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('level_id');
            $table->dropColumn(['phone', 'role', 'status', 'birth_date', 'avatar_path', 'xp', 'streak']);
        });
    }
};
