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
        Schema::table('interactions', function (Blueprint $table) {
            // Add user_id for tracking who created the interaction
            $table->foreignUuid('user_id')->nullable()->after('contact_id')->constrained('users')->nullOnDelete();
            
            // Rename description to notes for consistency
            $table->renameColumn('description', 'notes');
            
            // Add interactionDate field (rename from date for clarity)
            $table->renameColumn('date', 'interactionDate');
            
            // Add priority field for quick sorting
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium')->after('outcome');
            
            // Add follow_up_date for scheduling
            $table->date('follow_up_date')->nullable()->after('nextAction');
            
            // Add indexes for performance
            $table->index(['organization_id', 'interactionDate']);
            $table->index(['contact_id', 'interactionDate']);
            $table->index(['user_id', 'interactionDate']);
            $table->index(['type', 'interactionDate']);
            $table->index('outcome');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interactions', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex(['organization_id', 'interactionDate']);
            $table->dropIndex(['contact_id', 'interactionDate']);
            $table->dropIndex(['user_id', 'interactionDate']);
            $table->dropIndex(['type', 'interactionDate']);
            $table->dropIndex(['outcome']);
            
            // Drop added columns
            $table->dropColumn(['user_id', 'priority', 'follow_up_date']);
            
            // Rename columns back
            $table->renameColumn('notes', 'description');
            $table->renameColumn('interactionDate', 'date');
        });
    }
};
