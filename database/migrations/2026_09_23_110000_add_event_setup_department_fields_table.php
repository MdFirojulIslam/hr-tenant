<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive Event Setup department-selection fields.
     *
     * The base event_setups migration only stores a single department_id
     * (NOT NULL). The existing EventSetupController saves multiple selected
     * departments through the `for_departments` column (stored as a
     * comma-separated id string like "1,2,3") plus audit columns and soft
     * deletes. A record audit columns are required by the unchanged
     * controller, so we add them here without touching the base migration.
     */
    public function up(): void
    {
        Schema::table('event_setups', function (Blueprint $table) {
            $table->text('for_departments')->nullable()->after('department_id');
            $table->unsignedInteger('created_by')->nullable()->after('for_departments');
            $table->unsignedInteger('updated_by')->nullable()->after('created_by');
            $table->unsignedInteger('deleted_by')->nullable()->after('updated_by');
            $table->dateTime('deleted_at')->nullable()->after('deleted_by');
        });

        // Columns the unchanged controller leaves NULL must accept NULL.
        DB::statement('ALTER TABLE event_setups MODIFY department_id INT UNSIGNED NULL');
        DB::statement('ALTER TABLE event_setups MODIFY event_description TEXT NULL');
        DB::statement('ALTER TABLE event_setups MODIFY picture VARCHAR(255) NULL');
    }

    /**
     * Reverse the additions.
     */
    public function down(): void
    {
        Schema::table('event_setups', function (Blueprint $table) {
            $table->dropColumn(['for_departments', 'created_by', 'updated_by', 'deleted_by', 'deleted_at']);
        });

        DB::statement('ALTER TABLE event_setups MODIFY department_id INT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE event_setups MODIFY event_description TEXT NOT NULL');
        DB::statement('ALTER TABLE event_setups MODIFY picture VARCHAR(255) NOT NULL');
    }
};