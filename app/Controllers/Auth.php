<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Filters\LoginThrottleFilter;

class Auth extends BaseController
{
    public function index()
    {
        if (session()->get('isLoggedIn')) {
            $roleModel = new \App\Models\RolePermissionModel();
            $redirectUrl = $roleModel->getFirstAllowedUrl(session()->get('role')) ?? 'dashboard';
            return redirect()->to('/' . $redirectUrl);
        }
        return view('auth/login');
    }

    public function login()
    {
        $ip = $this->request->getIPAddress();

        // ── Brute Force Protection ──────────────────────────────────────────
        $cacheKey    = 'login_throttle_' . md5($ip);
        $throttle    = cache()->get($cacheKey) ?? ['attempts' => 0, 'locked_until' => 0];
        $maxAttempts = 5;
        $lockoutTime = 600; // 10 menit

        if ($throttle['locked_until'] > time()) {
            $remaining = ceil(($throttle['locked_until'] - time()) / 60);
            return redirect()->to('/auth')->with(
                'error',
                "Terlalu banyak percobaan login gagal. Silakan coba lagi dalam {$remaining} menit."
            );
        }
        // ───────────────────────────────────────────────────────────────────

        $rules = [
            'username' => 'required|alpha_numeric',
            'password' => 'required|min_length[8]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $model    = new UserModel();
        $username = $this->request->getVar('username');
        $user     = $model->where('username', $username)->first();

        if ($user && password_verify($this->request->getVar('password'), $user['password'])) {
            // Login sukses — bersihkan throttle counter
            LoginThrottleFilter::clearAttempts($ip);

            // Regenerate session ID untuk mencegah session fixation
            session()->regenerate(true);

            // 2FA Check
            $roleModel   = new \App\Models\RolePermissionModel();
            $require2fa = ($user['role'] === 'admin' || $roleModel->checkPermission($user['role'], 'require_2fa'));

            if ($require2fa) {
                // Set temporary session for 2FA
                session()->set(['temp_auth_user_id' => $user['id']]);
                return redirect()->to('/auth/2fa');
            }

            $this->setUserSession($user);
            $this->logActivity('User logged in successfully', $user['username']);

            $redirectUrl = $roleModel->getFirstAllowedUrl($user['role']) ?? 'dashboard';
            return redirect()->to('/' . $redirectUrl);
        }

        // Login gagal — catat percobaan
        LoginThrottleFilter::recordFailedAttempt($ip);
        $remaining = LoginThrottleFilter::getRemainingAttempts($ip);

        $this->logActivity(
            "Failed login attempt for username: {$username} from IP: {$ip}",
            $username
        );

        $errorMsg = 'Username atau Password salah.';
        if ($remaining <= 2 && $remaining > 0) {
            $errorMsg .= " Sisa percobaan: {$remaining}.";
        } elseif ($remaining === 0) {
            $errorMsg = "Akun dikunci sementara selama 10 menit karena terlalu banyak percobaan gagal.";
        }

        return redirect()->back()->withInput()->with('error', $errorMsg);
    }

    public function setUserSession(array $user): bool
    {
        // Resolve nama_satker from LPSE tables for opd/satker roles
        $namaSatker = null;
        if (in_array($user['role'], ['opd', 'satker']) && !empty($user['kd_satker'])) {
            $db = \Config\Database::connect();
            $tables = ['lpse_tender', 'lpse_nontender', 'lpse_pencatatan_nontender', 'lpse_pencatatan_swakelola', 'rup_penyedia', 'rup_swakelola'];
            foreach ($tables as $tbl) {
                $row = $db->table($tbl)
                    ->select('nama_satker')
                    ->where('kd_satker', $user['kd_satker'])
                    ->limit(1)
                    ->get()
                    ->getRowArray();
                if ($row && !empty($row['nama_satker'])) {
                    $namaSatker = $row['nama_satker'];
                    break;
                }
            }
        }

        $data = [
            'id'           => $user['id'],
            'username'     => $user['username'],
            'nama_lengkap' => $user['nama_lengkap'],
            'role'         => $user['role'],
            'kd_satker'    => $user['kd_satker'],
            'nama_satker'  => $namaSatker,
            'avatar'       => $user['avatar'] ?? null,
            'isLoggedIn'   => true,
        ];

        session()->set($data);
        return true;
    }

    public function logout()
    {
        $this->logActivity('User logged out');
        session()->regenerate(true);
        session()->destroy();
        return redirect()->to('/auth');
    }
}
