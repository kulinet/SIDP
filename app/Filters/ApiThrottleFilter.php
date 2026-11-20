<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * ApiThrottleFilter
 *
 * Menerapkan Rate Limiting pada API endpoints dan Chatbot untuk mencegah DoS/brute-force.
 * Sesuai standar BSSN & OWASP API Security.
 */
class ApiThrottleFilter implements FilterInterface
{
    /**
     * Default limit per menit untuk endpoint umum API
     */
    private const DEFAULT_API_LIMIT = 60; // 60 requests per minute

    /**
     * Limit per menit untuk chatbot (karena memakan komputasi AI)
     */
    private const CHATBOT_LIMIT = 15; // 15 requests per minute

    /**
     * Window waktu dalam detik
     */
    private const TIME_WINDOW = 60;

    public function before(RequestInterface $request, $arguments = null)
    {
        $uri = $request->getUri()->getPath();
        $ip  = $request->getIPAddress();

        // Tentukan limit berdasarkan endpoint
        $isChatbot = str_contains($uri, 'chatbot');
        $maxLimit  = $isChatbot ? self::CHATBOT_LIMIT : self::DEFAULT_API_LIMIT;

        // Gunakan token hash jika ada (Authorization / X-API-KEY), fallback ke IP
        $authHeader = $request->getHeaderLine('Authorization');
        $apiKey     = $request->getHeaderLine('X-API-KEY');
        $clientIdentifier = !empty($authHeader) ? md5($authHeader) : (!empty($apiKey) ? md5($apiKey) : md5($ip));

        $cacheKey = 'throttle_' . ($isChatbot ? 'cb_' : 'api_') . $clientIdentifier;
        $cache = cache();

        $data = $cache->get($cacheKey);
        $currentTime = time();

        if ($data === null || !is_array($data) || ($currentTime - $data['start_time']) >= self::TIME_WINDOW) {
            $data = [
                'count'      => 1,
                'start_time' => $currentTime
            ];
            $cache->save($cacheKey, $data, self::TIME_WINDOW);
            return;
        }

        if ($data['count'] >= $maxLimit) {
            $retryAfter = self::TIME_WINDOW - ($currentTime - $data['start_time']);
            $retryAfter = max(1, $retryAfter);

            $response = service('response');
            return $response
                ->setStatusCode(429)
                ->setHeader('Retry-After', (string) $retryAfter)
                ->setHeader('X-RateLimit-Limit', (string) $maxLimit)
                ->setHeader('X-RateLimit-Remaining', '0')
                ->setHeader('X-RateLimit-Reset', (string) ($data['start_time'] + self::TIME_WINDOW))
                ->setJSON([
                    'status'      => 'error',
                    'message'     => 'Terlalu banyak permintaan (Rate limit exceeded). Silakan coba lagi dalam ' . $retryAfter . ' detik.',
                    'retry_after' => $retryAfter
                ]);
        }

        $data['count']++;
        $remainingTime = self::TIME_WINDOW - ($currentTime - $data['start_time']);
        $cache->save($cacheKey, $data, max(1, $remainingTime));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing needed
    }
}