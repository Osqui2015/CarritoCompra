<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('category_product', function (Blueprint $table) {
      $table->id();
      $table->foreignId('category_id')->constrained()->cascadeOnDelete();
      $table->foreignId('product_id')->constrained()->cascadeOnDelete();
      $table->timestamps();

      $table->unique(['category_id', 'product_id'], 'category_product_unique');
    });

    $now = now();

    $rows = DB::table('products')
      ->select(['id as product_id', 'category_id'])
      ->whereNotNull('category_id')
      ->get()
      ->map(fn(object $row): array => [
        'category_id' => (int) $row->category_id,
        'product_id' => (int) $row->product_id,
        'created_at' => $now,
        'updated_at' => $now,
      ])
      ->all();

    if ($rows !== []) {
      DB::table('category_product')->insertOrIgnore($rows);
    }
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('category_product');
  }
};
