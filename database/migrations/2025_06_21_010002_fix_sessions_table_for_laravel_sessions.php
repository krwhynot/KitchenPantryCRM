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
        Schema::table('sessions', function (Blueprint $table) {
            // Add Laravel session driver required columns
            if (!Schema::hasColumn('sessions', 'payload')) {
                $table->longText('payload');
            }
            if (!Schema::hasColumn('sessions', 'last_activity')) {
                $table->integer('last_activity')->index();
            }
            if (!Schema::hasColumn('sessions', 'ip_address')) {
                $table->string('ip_address', 45)->nullable();
            }
            if (!Schema::hasColumn('sessions', 'user_agent')) {
                $table->text('user_agent')->nullable();
            }
            
            // Modify existing user_id to be nullable for Laravel sessions
            $table->foreignUuid('user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            // Remove Laravel session columns
            $table->dropColumn(['payload', 'last_activity', 'ip_address', 'user_agent']);
            
            // Restore user_id to not nullable
            $table->foreignUuid('user_id')->change();
        });
    }
};
