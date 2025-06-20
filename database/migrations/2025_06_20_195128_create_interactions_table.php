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
        Schema::create('interactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('type', ['CALL', 'EMAIL', 'MEETING', 'VISIT']);
            $table->string('subject');
            $table->text('description')->nullable();
            $table->timestamp('date');
            $table->integer('duration')->nullable(); // In minutes
            $table->enum('outcome', ['POSITIVE', 'NEUTRAL', 'NEGATIVE', 'FOLLOWUPNEEDED'])->nullable();
            $table->string('nextAction')->nullable();
            $table->foreignUuid('organizationId')->constrained()->cascadeOnDelete();
            $table->foreignUuid('contactId')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interactions');
    }
};
