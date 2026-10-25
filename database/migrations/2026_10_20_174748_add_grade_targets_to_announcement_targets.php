<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('announcement_targets', 'grade')) {
            Schema::table('announcement_targets', function (Blueprint $table) {
                $table->string('grade', 10)->nullable()->after('class_id');
                $table->index(['grade', 'subject_id']);
            });
        }

        // class_id must allow NULL, so a target can be "a whole grade" instead of one class
        $info = DB::selectOne("SHOW COLUMNS FROM `announcement_targets` WHERE Field = 'class_id'");

        if ($info && $info->Null === 'NO') {
            Schema::table('announcement_targets', function (Blueprint $table) {
                $table->dropForeign(['class_id']);
            });

            DB::statement('ALTER TABLE `announcement_targets` MODIFY `class_id` BIGINT UNSIGNED NULL');

            Schema::table('announcement_targets', function (Blueprint $table) {
                $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Nothing to undo. Grade targets are only ever added.
    }
};