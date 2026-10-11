<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (! Schema::hasColumn('messages', 'hidden_by_receiver')) {
                $table->boolean('hidden_by_receiver')->default(false)->after('is_read');
            }
            if (! Schema::hasColumn('messages', 'hidden_by_sender')) {
                $table->boolean('hidden_by_sender')->default(false)->after('hidden_by_receiver');
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['hidden_by_receiver', 'hidden_by_sender']);
        });
    }
};