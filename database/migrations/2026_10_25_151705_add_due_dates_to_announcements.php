<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('announcements', 'due_date')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->date('due_date')->nullable()->after('type');
                $table->index('due_date');
            });
        }

        // A parent ticking a homework, assignment or project as done
        if (! Schema::hasTable('announcement_completions')) {
            Schema::create('announcement_completions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['announcement_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_completions');
    }
};