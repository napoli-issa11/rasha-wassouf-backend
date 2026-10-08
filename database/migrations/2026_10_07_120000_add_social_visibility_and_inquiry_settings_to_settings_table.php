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
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'show_facebook')) {
                $table->boolean('show_facebook')->default(true);
            }
            if (!Schema::hasColumn('settings', 'show_instagram')) {
                $table->boolean('show_instagram')->default(true);
            }
            if (!Schema::hasColumn('settings', 'show_linkedin')) {
                $table->boolean('show_linkedin')->default(true);
            }
            if (!Schema::hasColumn('settings', 'show_threads')) {
                $table->boolean('show_threads')->default(true);
            }
            if (!Schema::hasColumn('settings', 'show_pinterest')) {
                $table->boolean('show_pinterest')->default(true);
            }
            if (!Schema::hasColumn('settings', 'inquiry_settings')) {
                $table->json('inquiry_settings')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $columns = [];
            foreach (['show_facebook', 'show_instagram', 'show_linkedin', 'show_threads', 'show_pinterest', 'inquiry_settings'] as $col) {
                if (Schema::hasColumn('settings', $col)) {
                    $columns[] = $col;
                }
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
