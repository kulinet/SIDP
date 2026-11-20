<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\UserModel;
use App\Models\EpurchasingModel;
use App\Controllers\Chatbot;

class Api extends ResourceController
{
    /**
     * Mobile App / API Login Endpoint
     * Authenticates credentials and returns a secure API Bearer token.
     */
    public function login()
    {
        $rules = [
            'username' => 'required|alpha_numeric',
            'password' => 'required|min_length[8]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Validasi input gagal.',
                'errors'  => $this->validator->getErrors()
            ])->setStatusCode(400);
        }

        $ip = $this->request->getIPAddress();
        $cacheKey = 'api_login_throttle_' . md5($ip);
        $cache = cache();
        $throttleData = $cache->get($cacheKey) ?? ['attempts' => 0, 'locked_until' => 0];

        if ($throttleData['locked_until'] > time()) {
            $remaining = ceil(($throttleData['locked_until'] - time()) / 60);
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => "Terlalu banyak percobaan login. Akun dikunci sementara selama {$remaining} menit."
            ])->setStatusCode(429);
        }

        $userModel = new UserModel();
        $username  = (string) $this->request->getVar('username');
        $password  = (string) $this->request->getVar('password');

        $user = $userModel->where('username', $username)->first();

        if (!$user || !password_verify($password, $user['password'])) {
            $throttleData['attempts']++;
            if ($throttleData['attempts'] >= 5) {
                $throttleData['locked_until'] = time() + 600; // 10 mins
            }
            $cache->save($cacheKey, $throttleData, 600);

            $remainingAttempts = max(0, 5 - $throttleData['attempts']);
            $msg = 'Username atau password salah.';
            if ($remainingAttempts > 0 && $remainingAttempts <= 2) {
                $msg .= " Sisa percobaan: {$remainingAttempts}.";
            }

            return $this->response->setJSON([
                'status'  => 'error',
                'message' => $msg
            ])->setStatusCode(401);
        }

        // Login sukses: hapus throttle
        $cache->delete($cacheKey);

        // Generate secure 64-char hex token, simpan hash SHA-256 di DB
        $rawToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $rawToken);

        $userModel->update($user['id'], [
            'api_token' => $hashedToken
        ]);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Login berhasil.',
            'data'    => [
                'token'      => $rawToken,
                'token_type' => 'Bearer',
                'user'       => [
                    'id'           => $user['id'],
                    'username'     => $user['username'],
                    'nama_lengkap' => $user['nama_lengkap'],
                    'role'         => $user['role'],
                    'kd_satker'    => $user['kd_satker'],
                ]
            ]
        ]);
    }

    // Placeholder summary method
    public function getSummary()
    {
        // You can extend this to return actual summary data
        return $this->response->setJSON(['status' => 'success', 'data' => []]);
    }

    // Retrieve LPSE data for a given identifier
    public function getLpse($segment)
    {
        // Placeholder: implement actual LPSE retrieval logic as needed
        return $this->response->setJSON(['status' => 'success', 'lpse_id' => $segment]);
    }

    // Retrieve RUP OPD data
    public function getRupOpd()
    {
        // Placeholder implementation
        return $this->response->setJSON(['status' => 'success', 'rup_opd' => []]);
    }

    // Proxy chatbot ask endpoint via API group
    public function chatbotAsk()
    {
        // Reuse existing Chatbot::ask logic
        $chatbot = new Chatbot();
        // Forward the current request to Chatbot::ask
        return $chatbot->ask();
    }

    // Export Monev Summary for sidepe monitoring
    public function getMonevSummary()
    {
        $proyekModel = new \App\Models\MonevProyekModel();
        $jadwalModel = new \App\Models\MonevJadwalModel();
        $harianModel = new \App\Models\MonevHarianModel();
        
        // We use Dashboard controller to calculate scurve
        $dashboard = new \App\Controllers\Dashboard();
        $dashboard->initController($this->request, $this->response, \Config\Services::logger());

        $proyekList = $proyekModel->findAll();
        $summaryData = [];
        
        foreach ($proyekList as $p) {
            $jadwalItems = $jadwalModel->where('proyek_id', $p['id'])->findAll();
            $harianItems = $harianModel->where('proyek_id', $p['id'])->orderBy('tanggal', 'ASC')->findAll();
            
            $scurve = $dashboard->calculateMonevScurve($p, $jadwalItems, $harianItems);
            
            $deviasiTerakhir = 0;
            if (!empty($scurve['deviasi'])) {
                foreach (array_reverse($scurve['deviasi']) as $val) {
                    if ($val !== null) { $deviasiTerakhir = $val; break; }
                }
            }
            
            $realisasiTerakhir = 0;
            if (!empty($scurve['actual'])) {
                foreach (array_reverse($scurve['actual']) as $val) {
                    if ($val !== null) { $realisasiTerakhir = $val; break; }
                }
            }
            
            $deviasiMingguan = 0;
            if (!empty($scurve['actual']) && !empty($scurve['plan'])) {
                $lastWeekIndex = -1;
                foreach ($scurve['actual'] as $idx => $val) {
                    if ($val !== null) { $lastWeekIndex = $idx; }
                }
                if ($lastWeekIndex >= 0) {
                    $currentActual = $scurve['actual'][$lastWeekIndex] ?? 0;
                    $prevActual = $lastWeekIndex > 0 ? ($scurve['actual'][$lastWeekIndex - 1] ?? 0) : 0;
                    $weeklyActual = $currentActual - $prevActual;
                    
                    $currentPlan = $scurve['plan'][$lastWeekIndex] ?? 0;
                    $prevPlan = $lastWeekIndex > 0 ? ($scurve['plan'][$lastWeekIndex - 1] ?? 0) : 0;
                    $weeklyPlan = $currentPlan - $prevPlan;
                    
                    $deviasiMingguan = round($weeklyActual - $weeklyPlan, 2);
                }
            }
            
            if ($deviasiMingguan <= -10.0) {
                $statusIndikator = 'Merah';
            } elseif ($deviasiMingguan <= -5.0) {
                $statusIndikator = 'Kuning';
            } else {
                $statusIndikator = 'Hijau';
            }
            
            $tglSelesai = strtotime($p['tgl_selesai']);
            if ($tglSelesai < time() && $p['status'] !== 'Selesai') {
                $daysOver = floor((time() - $tglSelesai) / 86400);
                if ($daysOver > 14) {
                    $statusIndikator = 'Merah';
                } elseif ($statusIndikator !== 'Merah') {
                    $statusIndikator = 'Kuning';
                }
            }
            
            $p['realisasi'] = $realisasiTerakhir;
            $p['deviasi_kumulatif'] = $deviasiTerakhir;
            $p['deviasi_mingguan'] = $deviasiMingguan;
            $p['status_indikator'] = $statusIndikator;
            
            $summaryData[] = $p;
        }

        // Return as JSON
        // CORS wildcard dihilangkan untuk keamanan (BSSN)
        // Akses API dari origin lain harus diatur di app/Config/Cors.php
        return $this->response->setJSON(['status' => 'success', 'data' => $summaryData]);
    }
}
