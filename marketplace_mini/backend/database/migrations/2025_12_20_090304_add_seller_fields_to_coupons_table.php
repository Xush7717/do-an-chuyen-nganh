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
        Schema::table('coupons', function (Blueprint $table) {
            $table->foreignId('seller_id')->after('id')->constrained('users')->onDelete('cascade');
            $table->decimal('min_order_value', 10, 2)->default(0)->after('value');
            $table->integer('usage_limit')->nullable()->after('min_order_value');
            $table->integer('usage_count')->default(0)->after('usage_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropForeign(['seller_id']);
            $table->dropColumn(['seller_id', 'min_order_value', 'usage_limit', 'usage_count']);
        });
    }
};
