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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ticker_id')
                ->constrained();

            $table->string('currency', 3)->nullable();

            $table->decimal('regular_market_price', 15, 4)->nullable();
            $table->decimal('regular_market_day_high', 15, 4)->nullable();
            $table->decimal('regular_market_day_low', 15, 4)->nullable();
            $table->string('regular_market_day_range')->nullable();

            $table->decimal('regular_market_change', 15, 4)->nullable();
            $table->decimal('regular_market_change_percent', 10, 4)->nullable();

            $table->timestamp('regular_market_time')->nullable();

            $table->decimal('market_cap', 20, 2)->nullable();
            $table->unsignedBigInteger('regular_market_volume')->nullable();

            $table->decimal('regular_market_previous_close', 15, 4)->nullable();
            $table->decimal('regular_market_open', 15, 4)->nullable();

            $table->string('fifty_two_week_range')->nullable();
            $table->decimal('fifty_two_week_low', 15, 4)->nullable();
            $table->decimal('fifty_two_week_high', 15, 4)->nullable();

            $table->index('regular_market_time');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
