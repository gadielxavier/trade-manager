<?php

namespace App\Http\Middleware;

use App\Services\Wallet\WalletService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWalletExists
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        if ($request->user()) {
            app(WalletService::class)
                ->getOrCreate($request->user());
        }

        return $next($request);
    }
}
