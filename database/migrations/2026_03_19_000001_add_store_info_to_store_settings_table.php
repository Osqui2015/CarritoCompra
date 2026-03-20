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
    Schema::table('store_settings', function (Blueprint $table) {
      $table->string('store_name')->nullable()->after('id');
      $table->string('store_email')->nullable()->after('store_name');
      $table->string('store_phone', 30)->nullable()->after('store_email');
      $table->string('store_whatsapp', 30)->nullable()->after('store_phone');
      // Puedes agregar más campos si lo necesitas
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('store_settings', function (Blueprint $table) {
      $table->dropColumn(['store_name', 'store_email', 'store_phone', 'store_whatsapp']);
    });
  }
};
