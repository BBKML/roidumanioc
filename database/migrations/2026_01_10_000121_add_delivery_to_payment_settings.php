<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_settings', function (Blueprint $table) {
            $table->unsignedInteger('delivery_fee')->nullable()->after('intl_link');
            $table->string('delivery_note')->nullable()->after('delivery_fee');
        });
    }

    public function down(): void
    {
        Schema::table('payment_settings', function (Blueprint $table) {
            $table->dropColumn(['delivery_fee', 'delivery_note']);
        });
    }
};
