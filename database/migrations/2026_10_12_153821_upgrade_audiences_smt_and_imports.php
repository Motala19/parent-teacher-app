<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /* 1. Announcement audiences */
        if (! Schema::hasColumn('announcements', 'audience')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->string('audience', 20)->default('school')->after('user_id');
                $table->string('audience_label')->nullable()->after('audience');
            });
        }

        if (! Schema::hasTable('announcement_targets')) {
            Schema::create('announcement_targets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
                $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
                $table->foreignId('subject_id')->nullable()->constrained()->cascadeOnDelete();
                $table->index(['class_id', 'subject_id']);
            });

            // Convert existing announcements
            $hasSubject = Schema::hasColumn('announcements', 'subject_id');
            $columns    = $hasSubject ? ['id', 'class_id', 'subject_id'] : ['id', 'class_id'];

            foreach (DB::table('announcements')->get($columns) as $a) {
                $subjectId = $hasSubject ? $a->subject_id : null;

                if ($a->class_id) {
                    DB::table('announcement_targets')->insert([
                        'announcement_id' => $a->id,
                        'class_id'        => $a->class_id,
                        'subject_id'      => $subjectId,
                    ]);

                    $class   = DB::table('classes')->where('id', $a->class_id)->value('name');
                    $subject = $subjectId ? DB::table('subjects')->where('id', $subjectId)->value('name') : null;

                    DB::table('announcements')->where('id', $a->id)->update([
                        'audience'       => 'classes',
                        'audience_label' => $subject ? "{$subject} · {$class}" : $class,
                    ]);
                } else {
                    DB::table('announcements')->where('id', $a->id)->update([
                        'audience'       => 'school',
                        'audience_label' => 'Whole school',
                    ]);
                }
            }
        }

        // From now on an announcement must say who it is for
        DB::statement('ALTER TABLE `announcements` ALTER COLUMN `audience` DROP DEFAULT');

        /* 2. SMT members and parent invitations */
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_smt')) {
                $table->boolean('is_smt')->default(false);
            }
            if (! Schema::hasColumn('users', 'position')) {
                $table->string('position', 60)->nullable();
            }
            if (! Schema::hasColumn('users', 'invited_at')) {
                $table->timestamp('invited_at')->nullable();
            }
        });

        /* 3. Imports */
        if (! Schema::hasTable('imports')) {
            Schema::create('imports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('original_name');
                $table->string('path')->nullable();
                $table->string('status', 20)->default('previewed'); // previewed, running, completed, failed
                $table->json('summary')->nullable();
                $table->json('issues')->nullable();
                $table->json('new_parent_ids')->nullable();
                $table->text('error')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('imports');
        Schema::dropIfExists('announcement_targets');
    }
};