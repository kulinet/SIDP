<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * SecureHeaders Filter
 *
 * Menerapkan HTTP Security Headers standar OWASP dan BSSN:
 * - Content-Security-Policy (CSP)
 * - Strict-Transport-Security (HSTS)
 * - X-Frame-Options
 * - X-Content-Type-Options
 * - Referrer-Policy
 * - Permissions-Policy
 * - X-Permitted-Cross-Domain-Policies
 */
class SecureHeaders implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // 1. Clickjacking Protection
        $response->setHeader('X-Frame-Options', 'SAMEORIGIN');

        // 2. MIME Sniffing Protection
        $response->setHeader('X-Content-Type-Options', 'nosniff');

        // 3. XSS Filter (legacy browsers)
        $response->setHeader('X-XSS-Protection', '1; mode=block');

        // 4. Referrer Policy
        $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        // 5. Cross Domain Policies
        $response->setHeader('X-Permitted-Cross-Domain-Policies', 'none');

        // 6. Permissions Policy
        $response->setHeader('Permissions-Policy', 'geolocation=(), camera=(), microphone=(), payment=(), usb=()');

        // 7. Strict-Transport-Security (HSTS) — berlaku pada HTTPS
        if ($request->isSecure()) {
            $response->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // 8. Content-Security-Policy (CSP)
        $envApiBase = env('AJIP_API_BASE_URL', 'https://ajip-api.cloud');
        $envApiHost = parse_url($envApiBase, PHP_URL_HOST);
        $extraApiCsp = ($envApiHost && !str_contains($envApiHost, 'ajip-api.cloud')) ? " https://{$envApiHost} https://*.{$envApiHost}" : "";

        $cspDirectives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://buttons.github.io https://cdn.datatables.net https://cdnjs.cloudflare.com https://unpkg.com https://code.jquery.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdn.datatables.net https://cdnjs.cloudflare.com https://unpkg.com",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: https: blob: http://mt0.google.com https://*.tile.openstreetmap.org https://tile.openstreetmap.org https://*.tile.osm.org https://*.basemaps.cartocdn.com https://basemaps.cartocdn.com https://unpkg.com https://cdnjs.cloudflare.com",
            "connect-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com https://cdn.datatables.net https://api.openai.com https://openrouter.ai https://*.ajip-api.cloud https://*.infoajip.cloud https://*.tile.openstreetmap.org https://tile.openstreetmap.org https://*.basemaps.cartocdn.com https://basemaps.cartocdn.com{$extraApiCsp}",
            "frame-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ];

        $response->setHeader('Content-Security-Policy', implode('; ', $cspDirectives));

        return $response;
    }
}
