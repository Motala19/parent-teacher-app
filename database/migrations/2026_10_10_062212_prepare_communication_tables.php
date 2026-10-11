<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Columns that must accept NULL for school-wide posts and plain messages
    private array $nullable = [
        'announcements' => ['class_id', 'attachment', 'published_at'],
        'messages'      => ['class_id', 'attachment', 'read_at'],
    ];

    public function up(): void
    {
        foreach ($this->nullable as $table => $columns) {
            foreach ($columns as $column) {
                $info = DB::selectOne("SHOW COLUMNS FROM `{$table}` WHERE Field = '{$column}'");

                if ($info && $info->Null === 'NO') {
                    DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` {$info->Type} NULL DEFAULT NULL");
                }
            }
        }

        // Make sure the announcement type is a plain string, not an enum
        $type = DB::selectOne("SHOW COLUMNS FROM `announcements` WHERE Field = 'type'");
        if ($type && ! str_starts_with(strtolower($type->Type), 'varchar')) {
            DB::statement("ALTER TABLE `announcements` MODIFY `type` VARCHAR(30) NOT NULL DEFAULT 'general'");
        }

        Schema::table('messages', function (Blueprint $table) {
            if (! Schema::hasColumn('messages', 'student_id')) {
                $table->foreignId('student_id')->nullable()->after('class_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('messages', 'hidden_by_sender')) {
                $table->boolean('hidden_by_sender')->default(false);
            }
            if (! Schema::hasColumn('messages', 'hidden_by_receiver')) {
                $table->boolean('hidden_by_receiver')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'student_id')) {
                $table->dropConstrainedForeignId('student_id');
            }
            if (Schema::hasColumn('messages', 'hidden_by_sender')) {
                $table->dropColumn(['hidden_by_sender', 'hidden_by_receiver']);
            }
        });
    }
};