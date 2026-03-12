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
    Schema::create('banners', function (Blueprint $table) {
      $table->id();
      $table->string('title', 120);
      $table->string('subtitle', 255)->nullable();
      $table->string('image_path');
      $table->string('link_url', 255)->nullable();
      $table->string('type', 30)->index();
      $table->boolean('is_active')->default(true)->index();
      $table->unsignedInteger('sort_order')->default(1);
      $table->timestamp('active_from')->nullable()->index();
      $table->timestamp('active_to')->nullable()->index();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('banners');
  }
};
