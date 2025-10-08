<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    { 
        Schema::table('leaves', function (Blueprint $table) {
            $table->boolean('notification')->default(0)->after('attachment_url');  
            $table->unsignedInteger('updated_by')->nullable()->after('created_by');  
        }); 
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn(['notification', 'updated_by']);
        });
    }
};