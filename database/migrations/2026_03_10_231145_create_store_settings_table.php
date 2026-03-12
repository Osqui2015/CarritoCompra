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
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->string('sales_whatsapp', 30)->nullable();
            $table->string('store_address')->nullable();
            $table->string('business_hours')->nullable();
            $table->string('hero_banner_title')->default('Vuelta al Cole');
            $table->text('hero_banner_subtitle')->nullable();
            $table->enum('hero_banner_link_type', ['url', 'category', 'product'])->default('url');
            $table->string('hero_banner_link_value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
