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
        if (! Schema::hasColumn('carts', 'user_id')) {
            Schema::table('carts', function (Blueprint $table): void {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            });
        }

        if (! Schema::hasColumn('carts', 'confirmed_at')) {
            Schema::table('carts', function (Blueprint $table): void {
                $table->timestamp('confirmed_at')->nullable()->after('status');
            });
        }

        if (! Schema::hasColumn('carts', 'coupon_id')) {
            Schema::table('carts', function (Blueprint $table): void {
                $table->unsignedBigInteger('coupon_id')->nullable()->after('confirmed_at');
            });
        }

        if (! Schema::hasColumn('carts', 'discount_amount')) {
            Schema::table('carts', function (Blueprint $table): void {
                $table->decimal('discount_amount', 10, 2)->default(0)->after('subtotal');
            });
        }

        try {
            DB::statement('ALTER TABLE carts ADD CONSTRAINT carts_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL');
        } catch (\Throwable) {
            // Constraint already exists in partially migrated databases.
        }

        try {
            DB::statement('ALTER TABLE carts ADD CONSTRAINT carts_coupon_id_foreign FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE SET NULL');
        } catch (\Throwable) {
            // Constraint already exists in partially migrated databases.
        }

        try {
            DB::statement('CREATE INDEX carts_confirmed_at_index ON carts (confirmed_at)');
        } catch (\Throwable) {
            // Index already exists in partially migrated databases.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE carts DROP FOREIGN KEY carts_coupon_id_foreign');
        } catch (\Throwable) {
        }

        try {
            DB::statement('ALTER TABLE carts DROP FOREIGN KEY carts_user_id_foreign');
        } catch (\Throwable) {
        }

        Schema::table('carts', function (Blueprint $table): void {
            if (Schema::hasColumn('carts', 'coupon_id')) {
                $table->dropColumn('coupon_id');
            }

            if (Schema::hasColumn('carts', 'user_id')) {
                $table->dropColumn('user_id');
            }

            if (Schema::hasColumn('carts', 'confirmed_at')) {
                $table->dropColumn('confirmed_at');
            }

            if (Schema::hasColumn('carts', 'discount_amount')) {
                $table->dropColumn('discount_amount');
            }
        });

        try {
            DB::statement('DROP INDEX carts_confirmed_at_index ON carts');
        } catch (\Throwable) {
        }
    }
};
