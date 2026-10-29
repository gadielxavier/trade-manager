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
        Schema::table('tickers', function (Blueprint $table) {
            $table->string('name')->nullable();
            $table->string('long_name')->nullable();
            $table->string('asset_type')->nullable();
            $table->string('sub_type')->nullable();
            $table->string('exchange')->nullable();
            $table->string('currency', 10)->nullable();
            $table->string('sector')->nullable();
            $table->string('subsector')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('logo_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickers', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'long_name',
                'asset_type',
                'sub_type',
                'exchange',
                'currency',
                'sector',
                'subsector',
                'is_active',
                'logo_url',
            ]);
        });
    }
};
