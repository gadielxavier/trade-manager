<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wallet_positions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('wallet_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('ticker_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedBigInteger('quantity')->default(0);

            $table->decimal('average_price', 15, 4)->default(0);

            $table->decimal('invested_amount', 15, 2)->default(0);

            $table->timestamps();

            $table->unique(['wallet_id', 'ticker_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_positions');
    }
};
