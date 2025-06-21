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
        Schema::create('opportunity_stage_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('opportunity_id');
            $table->string('from_stage', 50)->nullable();
            $table->string('to_stage', 50);
            $table->decimal('probability_change', 5, 2)->default(0.00);
            $table->uuid('user_id');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('opportunity_id')->references('id')->on('opportunities')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Indexes for performance
            $table->index(['opportunity_id', 'created_at']);
            $table->index(['user_id']);
            $table->index(['to_stage']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opportunity_stage_histories');
    }
};
