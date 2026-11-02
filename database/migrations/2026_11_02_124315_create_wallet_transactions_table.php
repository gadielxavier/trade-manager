<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('wallet_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('ticker_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();

            $table->enum('type', [
                'deposit',
                'buy',
                'sell',
                'withdrawal',
            ]);

            // Origem do dinheiro utilizado na compra.
            $table->enum('funding_source', [
                'deposit',
                'cash',
            ])->nullable();

            $table->unsignedBigInteger('quantity')->nullable();

            $table->decimal('unit_price', 15, 4)->nullable();

            // Valor bruto da operação.
            $table->decimal('gross_amount', 15, 2);

            // Corretagem, emolumentos e outras taxas.
            $table->decimal('fees', 15, 2)->default(0);

            // Valor efetivamente debitado ou creditado.
            $table->decimal('net_amount', 15, 2);

            $table->date('date');

            $table->text('description')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['wallet_id', 'type']);
            $table->index(['wallet_id', 'ticker_id']);
            $table->index(['wallet_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
