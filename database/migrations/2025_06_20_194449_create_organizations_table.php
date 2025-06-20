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
        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->enum('priority', ['A', 'B', 'C', 'D']);
            $table->string('segment'); // E.g., 'FINEDINING', 'FASTFOOD'
            $table->string('type')->default('PROSPECT');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zipCode')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->text('notes')->nullable();
            $table->float('estimatedRevenue')->nullable();
            $table->integer('employeeCount')->nullable();
            $table->string('primaryContact')->nullable();
            $table->timestamp('lastContactDate')->nullable();
            $table->timestamp('nextFollowUpDate')->nullable();
            $table->string('status')->default('ACTIVE');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
