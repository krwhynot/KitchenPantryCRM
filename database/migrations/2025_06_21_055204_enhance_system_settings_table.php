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
        Schema::table('system_settings', function (Blueprint $table) {
            $table->string('category', 50)->default('system')->after('value');
            $table->string('type', 20)->default('string')->after('category');
            $table->text('description')->nullable()->after('type');
            $table->text('default_value')->nullable()->after('description');
            $table->json('validation_rules')->nullable()->after('default_value');
            $table->string('ui_component', 50)->nullable()->after('validation_rules');
            $table->boolean('is_public')->default(false)->after('ui_component');
            $table->integer('sort_order')->default(0)->after('is_public');
            
            // Performance indexes based on research
            $table->index('category', 'idx_settings_category');
            $table->index('type', 'idx_settings_type');
            $table->index(['category', 'sort_order'], 'idx_settings_category_sort');
            $table->index('is_public', 'idx_settings_public');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            // Drop indexes first (Laravel best practice)
            $table->dropIndex('idx_settings_category');
            $table->dropIndex('idx_settings_type');
            $table->dropIndex('idx_settings_category_sort');
            $table->dropIndex('idx_settings_public');
            
            // Then drop columns
            $table->dropColumn([
                'category', 'type', 'description', 'default_value',
                'validation_rules', 'ui_component', 'is_public', 'sort_order'
            ]);
        });
    }
};
