<?php

namespace App\Console\Commands;

use App\Models\Ticker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTickers extends Command
{
    protected $signature = 'tickers:sync';

    protected $description = 'Alimenta a tabela tickers com base em trades.ticker e atualiza ticker_id';

    public function handle(): int
    {
        $this->info('Iniciando sincronização de tickers...');

        $codes = DB::table('trades')
            ->whereNotNull('ticker')
            ->whereRaw("TRIM(ticker) != ''")
            ->selectRaw('DISTINCT UPPER(TRIM(ticker)) as code')
            ->orderBy('code')
            ->pluck('code');

        if ($codes->isEmpty()) {
            $this->warn('Nenhum ticker encontrado na tabela trades.');

            return self::SUCCESS;
        }

        $created = 0;
        $linked = 0;

        DB::transaction(function () use ($codes, &$created, &$linked) {
            foreach ($codes as $code) {
                $code = Str::upper(trim($code));

                $ticker = Ticker::firstOrCreate([
                    'code' => $code,
                ]);

                if ($ticker->wasRecentlyCreated) {
                    $created++;
                }

                $linked += DB::table('trades')
                    ->whereRaw('UPPER(TRIM(ticker)) = ?', [$ticker->code])
                    ->whereNull('ticker_id')
                    ->update([
                        'ticker_id' => $ticker->id,
                    ]);
            }
        });

        $this->info("Tickers encontrados: {$codes->count()}");
        $this->info("Tickers criados: {$created}");
        $this->info("Operações vinculadas: {$linked}");

        $this->info('Sincronização concluída com sucesso.');

        return self::SUCCESS;
    }
}
