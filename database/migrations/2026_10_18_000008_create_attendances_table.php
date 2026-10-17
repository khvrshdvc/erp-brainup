<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('study_group_id')->constrained('study_groups')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('lesson_date');
            $table->string('status', 16);
            $table->timestamps();
            $table->unique(['study_group_id', 'student_id', 'lesson_date']);
            $table->index(['branch_id', 'lesson_date']);
            $table->index(['student_id', 'lesson_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
