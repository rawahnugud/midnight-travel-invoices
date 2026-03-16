<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class EnsureCleanUrls
{
    /**
     * Force HTTPS and canonical root URL when APP_URL uses https (production).
     * Ensures route(), asset(), and url() generate clean URLs without index.php.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $appUrl = config('app.url');
        if ($appUrl && str_starts_with($appUrl, 'https://')) {
            URL::forceScheme('https');
            URL::forceRootUrl(rtrim($appUrl, '/'));
        }

        return $next($request);
    }
}
