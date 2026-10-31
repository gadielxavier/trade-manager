<?php

namespace App\Console\Commands;

use App\Models\Quote;
use App\Models\Ticker;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncQuotes extends Command
{
    protected $signature = 'quotes:sync';

    protected $description = 'Sincroniza as cotações dos ativos através da API da Brapi';

    public function handle(): int
    {
        $token = config('services.brapi.token');
        $url = config('services.brapi.url') . '/stocks/quote';

        if (empty($token)) {
            $this->error('Token da Brapi não configurado.');

            return self::FAILURE;
        }

        $total = 0;
        $errors = 0;

        Ticker::query()
            ->where('sub_type', 'stock')
            ->whereNotNull('code')
            ->where('is_active', true)
            ->chunkById(50, function ($tickers) use (
                $token,
                $url,
                &$total,
                &$errors
            ) {
                foreach ($tickers as $ticker) {
                    try {
                        $response = Http::withToken($token)
                            ->timeout(30)
                            ->get($url, [
                                'symbols' => $ticker->code,
                            ]);

                        if ($response->failed()) {
                            throw new \RuntimeException(
                                "HTTP {$response->status()}: {$response->body()}"
                            );
                        }

                        $result = $response->json('results.0');

                        if (!$result || empty($result['data'])) {
                            throw new \RuntimeException(
                                'Nenhum dado retornado pela Brapi.'
                            );
                        }

                        $data = $result['data'];

                        $attributes = [
                            'currency' => $data['currency'] ?? null,
                            'regular_market_price' => $data['regularMarketPrice'] ?? null,
                            'regular_market_day_high' => $data['regularMarketDayHigh'] ?? null,
                            'regular_market_day_low' => $data['regularMarketDayLow'] ?? null,
                            'regular_market_day_range' => $data['regularMarketDayRange'] ?? null,
                            'regular_market_change' => $data['regularMarketChange'] ?? null,
                            'regular_market_change_percent' => $data['regularMarketChangePercent'] ?? null,

                            'regular_market_time' => !empty($data['regularMarketTime'])
                                ? Carbon::parse($data['regularMarketTime'])
                                : null,

                            'market_cap' => $data['marketCap'] ?? null,
                            'regular_market_volume' => $data['regularMarketVolume'] ?? null,
                            'regular_market_previous_close' => $data['regularMarketPreviousClose'] ?? null,
                            'regular_market_open' => $data['regularMarketOpen'] ?? null,
                            'fifty_two_week_range' => $data['fiftyTwoWeekRange'] ?? null,
                            'fifty_two_week_low' => $data['fiftyTwoWeekLow'] ?? null,
                            'fifty_two_week_high' => $data['fiftyTwoWeekHigh'] ?? null,
                        ];

                        $quote = Quote::withTrashed()
                            ->firstOrNew([
                                'ticker_id' => $ticker->id,
                            ]);

                        if ($quote->exists && $quote->trashed()) {
                            $quote->restore();
                        }

                        $quote->fill($attributes);
                        $quote->save();

                        if (!empty($data['logourl'])) {
                            $ticker->update([
                                'logo_url' => $data['logourl'],
                            ]);
                        }

                        $total++;

                        $this->info("Sincronizado: {$ticker->code}");

                    } catch (Throwable $exception) {
                        $errors++;

                        Log::error('Erro ao sincronizar cotação', [
                            'ticker' => $ticker->code,
                            'message' => $exception->getMessage(),
                        ]);

                        $this->error(
                            "Erro em {$ticker->code}: {$exception->getMessage()}"
                        );
                    }
                }
            });

        $this->newLine();
        $this->info("Cotações sincronizadas: {$total}");
        $this->info("Erros: {$errors}");

        return $errors > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
