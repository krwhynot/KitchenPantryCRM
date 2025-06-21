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
        Schema::table('opportunities', function (Blueprint $table) {
            // Update existing probability column to decimal
            $table->decimal('probability', 5, 2)->default(10.00)->change();
            
            // Add new pipeline fields
            $table->integer('lead_score')->default(0)->after('probability');
            $table->timestamp('stage_changed_at')->nullable()->after('lead_score');
            $table->uuid('stage_changed_by_user_id')->nullable()->after('stage_changed_at');
            
            // Add missing fields that might be referenced in model
            $table->string('title')->nullable()->after('name');
            $table->text('description')->nullable()->after('title');
            $table->string('status')->default('open')->after('stage');
            $table->uuid('user_id')->nullable()->after('contact_id');
            
            // Additional pipeline management fields
            $table->string('source', 100)->nullable()->after('stage_changed_by_user_id');
            $table->enum('priority', ['high', 'medium', 'low'])->default('medium')->after('source');
            $table->text('next_action')->nullable()->after('priority');
            $table->timestamp('last_activity_date')->nullable()->after('next_action');
            
            // Soft deletes
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['stage']);
            $table->index(['status']);
            $table->index(['probability']);
            $table->index(['priority']);
            $table->index(['expectedCloseDate']);
            $table->index(['last_activity_date']);
            $table->index(['stage_changed_at']);
            
            // Foreign key constraints
            $table->foreign('stage_changed_by_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropForeign(['stage_changed_by_user_id']);
            $table->dropForeign(['user_id']);
            $table->dropIndex(['stage']);
            $table->dropIndex(['status']);
            $table->dropIndex(['probability']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['expectedCloseDate']);
            $table->dropIndex(['last_activity_date']);
            $table->dropIndex(['stage_changed_at']);
            
            $table->dropColumn([
                'lead_score',
                'stage_changed_at',
                'stage_changed_by_user_id',
                'title',
                'description',
                'status',
                'user_id',
                'source',
                'priority',
                'next_action',
                'last_activity_date',
                'deleted_at'
            ]);
            
            // Revert probability column back to integer
            $table->integer('probability')->default(50)->change();
        });
    }
};
