<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('subjects', function (Blueprint $table) {
        $table->id();
        $table->foreignId('school_id')->constrained()->cascadeOnDelete();
        $table->string('name');
        $table->string('code', 20)->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->unique(['school_id', 'name']);
    });

    Schema::create('grade_subject', function (Blueprint $table) {
        $table->id();
        $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
        $table->string('grade', 10);
        $table->unique(['subject_id', 'grade']);
    });

    Schema::create('class_subject', function (Blueprint $table) {
        $table->id();
        $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
        $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
        $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamps();
        $table->unique(['class_id', 'subject_id']);
    });

    Schema::create('student_subject', function (Blueprint $table) {
        $table->id();
        $table->foreignId('student_id')->constrained()->cascadeOnDelete();
        $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
        $table->timestamps();
        $table->unique(['student_id', 'subject_id']);
    });
}

public function down(): void
{
    Schema::dropIfExists('student_subject');
    Schema::dropIfExists('class_subject');
    Schema::dropIfExists('grade_subject');
    Schema::dropIfExists('subjects');
}
};
