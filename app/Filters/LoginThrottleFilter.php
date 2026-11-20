<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * LoginThrottleFilter
 *
 * Melindungi endpoint login dari serangan brute force.
 * Sesuai standar BSSN: lockout setelah 5 percobaan gagal dalam 10 menit.
 */
class LoginThrottleFilter implements FilterInterface
{
    private const MAX_ATTEMPTS  = 5;
    private const LOCKOUT_TIME  = 600; // 10 menit (detik)
    private const CACHE_PREFIX  = 'login_throttle_';

    public function before(RequestInterface $request, $arguments = null)
    {
        // Hanya berlaku untuk POST request (form submit login)
        if (strtolower($request->getMethod()) !== 'post') {
            return;
        }

        $ip       = $request->getIPAddress();
        $cacheKey = self::CACHE_PREFIX . md5($ip);
        $cache    = cache();

        $data = $cache->get($cacheKey);

        if ($data === null) {
            $data = ['attempts' => 0, 'locked_until' => 0];
        }

        // Cek apakah masih dalam periode lockout
        if ($data['locked_until'] > time()) {
            $remaining = $data['locked_until'] - time();
            $minutes   = ceil($remaining / 60);

            if ($request->isAJAX()) {
                $response = service('response');
                return $response->setJSON([
                    'status'  => 'error',
                    'message' => "Terlalu banyak percobaan login. Coba lagi dalam {$minutes} menit.",
                ])->setStatusCode(429);
            }

            return redirect()->to('/auth')->with(
                'error',
                "Terlalu banyak percobaan login gagal. Akun sementara dikunci selama {$minutes} menit."
            );
        }

        // Reset jika periode sudah lewat
        if ($data['locked_until'] > 0 && $data['locked_until'] <= time()) {
            $data = ['attempts' => 0, 'locked_until' => 0];
            $cache->save($cacheKey, $data, self::LOCKOUT_TIME);
        }
    }

    /**
     * Dipanggil dari Auth controller saat login GAGAL.
     */
    public static function recordFailedAttempt(string $ip): void
    {
        $cache    = cache();
        $cacheKey = self::CACHE_PREFIX . md5($ip);

        $data = $cache->get($cacheKey) ?? ['attempts' => 0, 'locked_until' => 0];
        $data['attempts']++;

        if ($data['attempts'] >= self::MAX_ATTEMPTS) {
            $data['locked_until'] = time() + self::LOCKOUT_TIME;
        }

        $cache->save($cacheKey, $data, self::LOCKOUT_TIME);
    }

    /**
     * Dipanggil dari Auth controller saat login BERHASIL.
     */
    public static function clearAttempts(string $ip): void
    {
        $cacheKey = self::CACHE_PREFIX . md5($ip);
        cache()->delete($cacheKey);
    }

    /**
     * Cek sisa percobaan yang tersedia.
     */
    public static function getRemainingAttempts(string $ip): int
    {
        $cacheKey = self::CACHE_PREFIX . md5($ip);
        $data     = cache()->get($cacheKey);

        if ($data === null) {
            return self::MAX_ATTEMPTS;
        }

        return max(0, self::MAX_ATTEMPTS - ($data['attempts'] ?? 0));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing needed here
    }
}
