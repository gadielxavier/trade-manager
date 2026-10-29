<?php

namespace App\Console\Commands;

use App\Models\Ticker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class SyncTickers extends Command
{
    protected $signature = 'tickers:sync';

    protected $description = 'Sincroniza os tickers com a API da BRAPI';

    public function handle(): int
    {
        $this->info('Iniciando sincronização de tickers...');

        $page = 1;
        $created = 0;
        $updated = 0;
        $skipped = 0;

        try {
            do {
                $response = Http::acceptJson()
                    ->timeout(60)
                    ->retry(3, 1000)
                    ->get('https://brapi.dev/api/v2/tickers', [
                        'page' => $page,
                    ]);

                $response->throw();

                $data = $response->json();

                $results = $data['results'] ?? null;
                $pagination = $data['pagination'] ?? null;

                if (! is_array($results) || ! is_array($pagination)) {
                    $this->error(
                        "Resposta inválida da API na página {$page}."
                    );

                    return self::FAILURE;
                }

                $limit = (int) ($pagination['limit'] ?? 0);
                $hasNextPage = $pagination['hasNextPage'] ?? false;

                if ($limit <= 0 || ! is_bool($hasNextPage)) {
                    $this->error(
                        "Dados de paginação inválidos na página {$page}."
                    );

                    return self::FAILURE;
                }

                $this->info(
                    "Processando página {$page} "
                    . "({$limit} registros por página)..."
                );

                foreach ($results as $result) {
                    $symbol = $result['symbol'] ?? null;

                    if (! is_string($symbol) || trim($symbol) === '') {
                        $skipped++;

                        continue;
                    }

                    $ticker = Ticker::firstOrNew([
                        'code' => $symbol,
                    ]);

                    $exists = $ticker->exists;

                    $ticker->fill([
                        'name' => $result['name'] ?? null,
                        'long_name' => $result['longName'] ?? null,
                        'asset_type' => $result['assetType'] ?? null,
                        'sub_type' => $result['subType'] ?? null,
                        'exchange' => $result['exchange'] ?? null,
                        'currency' => $result['currency'] ?? null,
                        'sector' => $result['sector'] ?? null,
                        'subsector' => $result['subsector'] ?? null,
                        'is_active' => $result['isActive'] ?? true,
                        'logo_url' => $result['logoUrl'] ?? null,
                    ]);

                    $ticker->save();

                    if ($exists) {
                        $updated++;
                    } else {
                        $created++;
                    }
                }

                $this->info(
                    'Registros recebidos: ' . count($results)
                );

                $page++;

            } while ($hasNextPage);

            $this->newLine();
            $this->info('Sincronização concluída!');

            $this->table(
                ['Resultado', 'Quantidade'],
                [
                    ['Criados', $created],
                    ['Atualizados', $updated],
                    ['Ignorados', $skipped],
                    ['Páginas processadas', $page - 1],
                ]
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);

            $this->error(
                'Erro ao sincronizar tickers: '
                . $exception->getMessage()
            );

            return self::FAILURE;
        }
    }
}
