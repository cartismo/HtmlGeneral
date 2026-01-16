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
        Schema::create('html_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 100)->index(); // Position identifier: e.g., "footer_left", "header_banner"
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'code', 'is_active']);
        });

        Schema::create('html_block_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('html_block_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10)->index();
            $table->string('title')->nullable(); // Admin reference title
            $table->longText('content')->nullable(); // HTML content

            $table->unique(['html_block_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('html_block_translations');
        Schema::dropIfExists('html_blocks');
    }
};