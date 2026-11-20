<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UserModel;

/**
 * ApiAuthFilter
 *
 * Filter untuk memvalidasi Bearer token pada endpoint API.
 */
class ApiAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $response = service('response');

        // Dapatkan token dari Authorization header (Bearer) atau X-API-KEY
        $token = null;
        $authHeader = $request->getHeaderLine('Authorization');
        if (!empty($authHeader) && str_starts_with($authHeader, 'Bearer ')) {
            $token = trim(substr($authHeader, 7));
        } elseif (!empty($request->getHeaderLine('X-API-KEY'))) {
            $token = trim($request->getHeaderLine('X-API-KEY'));
        }

        if (empty($token)) {
            return $response->setJSON([
                'status'  => 'error',
                'message' => 'Akses ditolak. Bearer token atau X-API-KEY tidak ditemukan.'
            ])->setStatusCode(401);
        }

        // Hash token yang diterima untuk dicocokkan dengan hash di database
        $tokenHash = hash('sha256', $token);

        // Cek token ke database users (cocokkan hash atau legacy plain token)
        $userModel = new UserModel();
        $user = $userModel->where('api_token', $tokenHash)->first();

        // Fallback untuk backward compatibility: jika masih plain token di database, cocokkan lalu auto-hash
        if (!$user) {
            $legacyUser = $userModel->where('api_token', $token)->first();
            if ($legacyUser) {
                $userModel->update($legacyUser['id'], ['api_token' => $tokenHash]);
                $user = $legacyUser;
            }
        }

        if (!$user) {
            return $response->setJSON([
                'status'  => 'error',
                'message' => 'Akses ditolak. Token tidak valid atau telah di-revoke.'
            ])->setStatusCode(401);
        }

        // Simpan info user di request untuk digunakan oleh controller jika perlu
        $request->api_user = $user;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing needed here
    }
}
