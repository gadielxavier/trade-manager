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
        Schema::create('trades', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained();

            $table->string('ticker', 12)->index();
            $table->decimal('buy_price', 12, 4);
            $table->decimal('sell_price', 12, 4);
            $table->unsignedInteger('quantity');
            $table->decimal('profit', 14, 2)->default(0);
            $table->date('date')->index();
            $table->decimal('return_percent', 10, 4)->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
