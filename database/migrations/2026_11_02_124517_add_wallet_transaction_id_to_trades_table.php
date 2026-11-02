<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->foreignId('wallet_transaction_id')
                ->nullable()
                ->unique()
                ->constrained('wallet_transactions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->dropConstrainedForeignId('wallet_transaction_id');
        });
    }
};
