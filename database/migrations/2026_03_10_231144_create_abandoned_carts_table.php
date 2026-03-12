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
        Schema::create('abandoned_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('open')->index();
            $table->unsignedInteger('item_count')->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->json('items_snapshot')->nullable();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->foreignId('reminder_coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('recovered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abandoned_carts');
    }
};
