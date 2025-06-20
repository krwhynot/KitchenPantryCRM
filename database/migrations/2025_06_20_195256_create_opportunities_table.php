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
        Schema::create('opportunities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->foreignUuid('organizationId')->constrained()->cascadeOnDelete();
            $table->foreignUuid('contactId')->nullable()->constrained()->nullOnDelete();
            $table->float('value')->nullable();
            $table->string('stage')->default('PROSPECT');
            $table->integer('probability')->default(50);
            $table->timestamp('expectedCloseDate')->nullable();
            $table->text('notes')->nullable();
            $table->string('reason')->nullable();
            $table->boolean('isActive')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
