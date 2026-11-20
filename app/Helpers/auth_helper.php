<?php

if (!function_exists('has_permission')) {
    /**
     * Cek apakah user saat ini memiliki akses ke sebuah menu_key
     * Menggunakan hierarki:
     * 1. Jika admin, selalu true
     * 2. Jika ada override di tabel user_permissions, gunakan nilai tersebut
     * 3. Jika tidak ada override, fallback ke role_permissions
     */
    function has_permission($menuKey)
    {
        $session = session();
        if (!$session->get('isLoggedIn')) {
            return false;
        }

        $role = $session->get('role');
        if ($role === 'admin') {
            return true;
        }

        $userId = $session->get('id');

        // Cek custom permission (override per user)
        $userPermModel = new \App\Models\UserPermissionModel();
        $customStatus = $userPermModel->getCustomPermissionStatus($userId, $menuKey);

        if ($customStatus !== null) {
            return $customStatus;
        }

        // Fallback ke role default
        $rolePermModel = new \App\Models\RolePermissionModel();
        return $rolePermModel->checkPermission($role, $menuKey);
    }
}

if (!function_exists('get_inaproc_last_sync')) {
    /**
     * Mengambil tanggal dan jam database terakhir tersinkronisasi dengan data Inaproc/SIRUP/LPSE
     */
    function get_inaproc_last_sync()
    {
        $cacheKey = 'footer_inaproc_last_sync_v2';
        $cached = cache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $lastSync = null;

        try {
            $db = \Config\Database::connect();
            
            // 1. Cek dari tabel settings (kunci last_sync_*)
            $keys = [
                'last_sync_penyedia',
                'last_sync_swakelola',
                'last_sync_tender',
                'last_sync_nontender',
                'last_sync_epurchasing',
                'last_sync_pct_nontender',
                'last_sync_pct_swakelola',
                'last_sync_vendor_details'
            ];
            
            if ($db->tableExists('settings')) {
                $row = $db->table('settings')
                          ->select('value')
                          ->whereIn('key', $keys)
                          ->where('value !=', 'Belum pernah')
                          ->where('value !=', '')
                          ->orderBy('value', 'DESC')
                          ->limit(1)
                          ->get()
                          ->getRowArray();

                if (!empty($row['value'])) {
                    $lastSync = $row['value'];
                }
            }

            // 2. Fallback jika di settings kosong: cek updated_at dari tabel data
            if (!$lastSync) {
                $tables = ['inaproc_vendor_sync', 'epurchasing', 'rup_penyedia', 'lpse_tender'];
                foreach ($tables as $table) {
                    if ($db->tableExists($table)) {
                        $latestRow = $db->table($table)
                                        ->select('updated_at')
                                        ->where('updated_at IS NOT NULL')
                                        ->orderBy('updated_at', 'DESC')
                                        ->limit(1)
                                        ->get()
                                        ->getRowArray();
                        if (!empty($latestRow['updated_at'])) {
                            if (!$lastSync || $latestRow['updated_at'] > $lastSync) {
                                $lastSync = $latestRow['updated_at'];
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('warning', 'get_inaproc_last_sync failed: ' . $e->getMessage());
        }

        $formatted = 'Belum pernah';
        if ($lastSync) {
            $timestamp = strtotime($lastSync);
            if ($timestamp) {
                $bulanIndo = [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                ];
                $day = date('d', $timestamp);
                $m = (int) date('m', $timestamp);
                $year = date('Y', $timestamp);
                $time = date('H:i:s', $timestamp);
                $formatted = "{$day} {$bulanIndo[$m]} {$year}, {$time} WITA";
            } else {
                $formatted = $lastSync;
            }
        }

        $result = [
            'raw' => $lastSync,
            'formatted' => $formatted
        ];

        try {
            if (function_exists('cache')) {
                $c = cache();
                if ($c && is_object($c) && method_exists($c, 'save')) {
                    $c->save($cacheKey, $result, 300); // 5 menit
                }
            }
        } catch (\Throwable $e) {}

        return $result;
    }
}

if (!function_exists('get_app_version')) {
    /**
     * Mengambil versi aplikasi terkini
     */
    function get_app_version()
    {
        $version = env('APP_VERSION');
        if (!empty($version)) {
            return $version;
        }

        // Cek file patch_history jika ada
        $patchFile = WRITEPATH . 'backups/patch_history.json';
        if (file_exists($patchFile)) {
            $history = json_decode(file_get_contents($patchFile), true);
            if (is_array($history) && !empty($history)) {
                for ($i = count($history) - 1; $i >= 0; $i--) {
                    if (!empty($history[$i]['app_version']) && $history[$i]['app_version'] !== '-') {
                        return $history[$i]['app_version'];
                    }
                }
            }
        }

        return 'V1.08.15';
    }
}

