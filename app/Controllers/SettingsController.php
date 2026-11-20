<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\SettingsModel;
use App\Models\UserModel;

class SettingsController extends BaseController
{
    protected $settingsModel;

    public function __construct()
    {
        $this->settingsModel = new SettingsModel();
        // Extra check to ensure only admin can access
        if (session()->get('role') !== 'admin') {
            header('Location: ' . base_url('dashboard'));
            exit;
        }
    }

    public function chatbot()
    {
        $logModel = new \App\Models\ChatbotLogModel();
        $data = [
            'title' => 'Chatbot Settings',
            'settings' => [
                'model' => $this->settingsModel->getSetting('chatbot_model', 'gpt-5.4-mini'),
                'api_key' => $this->settingsModel->getSetting('chatbot_api_key', ''),
                'api_url' => $this->settingsModel->getSetting('chatbot_api_url', 'https://api.ai.cc/v1/chat/completions'),
                'embedding_model' => $this->settingsModel->getSetting('embedding_model', 'text-embedding-3-small'),
                'embedding_api_key' => $this->settingsModel->getSetting('embedding_api_key', ''),
                'embedding_api_url' => $this->settingsModel->getSetting('embedding_api_url', 'https://api.openai.com/v1/embeddings'),
                'system_prompt' => $this->settingsModel->getSetting('chatbot_system_prompt', ''),
            ],
            'chatbot_logs' => $logModel->orderBy('created_at', 'DESC')->findAll(300),
        ];

        return view('user/chatbot_settings', $data);
    }

    public function saveChatbot()
    {
        $rules = [
            'chatbot_model' => 'required',
            'chatbot_api_key' => 'required',
            'chatbot_api_url' => 'required|valid_url',
            'embedding_model' => 'required',
            'embedding_api_key' => 'required',
            'embedding_api_url' => 'required|valid_url',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->settingsModel->updateSetting('chatbot_model', $this->request->getPost('chatbot_model'));
        $this->settingsModel->updateSetting('chatbot_api_key', $this->request->getPost('chatbot_api_key'));
        $this->settingsModel->updateSetting('chatbot_api_url', $this->request->getPost('chatbot_api_url'));
        $this->settingsModel->updateSetting('embedding_model', $this->request->getPost('embedding_model'));
        $this->settingsModel->updateSetting('embedding_api_key', $this->request->getPost('embedding_api_key'));
        $this->settingsModel->updateSetting('embedding_api_url', $this->request->getPost('embedding_api_url'));
        $this->settingsModel->updateSetting('chatbot_system_prompt', $this->request->getPost('chatbot_system_prompt'));

        return redirect()->to(base_url('user/chatbot-settings'))->with('success', 'Chatbot settings updated successfully.');
    }

    public function clearChatbotLogs()
    {
        $db = \Config\Database::connect();
        $db->query('TRUNCATE TABLE chatbot_logs');
        return redirect()->to(base_url('user/chatbot-settings'))->with('success', 'Log chatbot berhasil dihapus.');
    }

    public function generateToken()
    {
        $userModel = new UserModel();
        $userId = session()->get('id');
        $plainToken = bin2hex(random_bytes(32)); // Secure 64‑char raw token
        $hashedToken = hash('sha256', $plainToken);
        $userModel->update($userId, ['api_token' => $hashedToken]);
        
        session()->setFlashdata('generated_token', $plainToken);
        return redirect()->to(base_url('user/api-settings'))->with('success', 'Token API baru berhasil digenerate. Segera salin token ini karena tidak akan ditampilkan kembali demi keamanan.');
    }

    // Method to render AGI Integrator & API Settings page
    public function api()
    {
        $userModel = new UserModel();
        $userId = session()->get('id');
        $user = $userModel->find($userId);

        $savedBaseUrl = $this->settingsModel->getSetting('agi_api_base_url');
        $agiBaseUrl = !empty($savedBaseUrl) ? $savedBaseUrl : env('AJIP_API_BASE_URL', 'http://localhost:3000');
        $agiApiToken = (string)$this->settingsModel->getSetting('agi_api_token', '');
        $agiNoToken = (string)$this->settingsModel->getSetting('agi_no_token', empty($agiApiToken) ? '1' : '0');
        $agiLastImport = $this->settingsModel->getSetting('agi_last_import_at', null);

        // Load endpoints config from DB or default file
        $endpointsRaw = $this->settingsModel->getSetting('agi_endpoints_config', '');
        $endpointsConfig = null;
        if (!empty($endpointsRaw)) {
            $endpointsConfig = json_decode($endpointsRaw, true);
        }

        if (empty($endpointsConfig) || empty($endpointsConfig['sources'])) {
            $defaultFile = WRITEPATH . 'agi_schema_default.json';
            if (file_exists($defaultFile)) {
                $raw = file_get_contents($defaultFile);
                $endpointsConfig = json_decode($raw, true);
            }
        }

        $sources = isset($endpointsConfig['sources']) && is_array($endpointsConfig['sources']) ? $endpointsConfig['sources'] : [];

        $data = [
            'title'              => 'Pengaturan AGI Integrator',
            'user'               => $user,
            'agi_api_base_url'   => $agiBaseUrl,
            'agi_api_token'      => $agiApiToken,
            'agi_no_token'       => $agiNoToken,
            'agi_last_import_at' => $agiLastImport,
            'agi_sources'        => $sources,
            'agi_version'        => $endpointsConfig['version'] ?? '2.0',
            'agi_exported_at'    => $endpointsConfig['exported_at'] ?? null,
            'total_sources'      => count($sources),
        ];

        return view('user/api_settings', $data);
    }

    public function saveAgiSettings()
    {
        $baseUrl = trim((string)$this->request->getPost('agi_api_base_url'));
        $apiToken = trim((string)$this->request->getPost('agi_api_token'));
        $noToken = $this->request->getPost('agi_no_token') === '1';

        if (!empty($baseUrl)) {
            $baseUrl = rtrim($baseUrl, '/');
            $this->settingsModel->updateSetting('agi_api_base_url', $baseUrl);
        }

        if ($noToken) {
            $apiToken = '';
        }

        $this->settingsModel->updateSetting('agi_api_token', $apiToken);
        $this->settingsModel->updateSetting('agi_no_token', $noToken ? '1' : '0');

        $msg = 'Pengaturan koneksi AGI Integrator berhasil disimpan' . ($noToken ? ' (Mode Tanpa Token aktif).' : '.');
        return redirect()->to(base_url('user/api-settings'))->with('success', $msg);
    }

    public function testAgiConnection()
    {
        $baseUrl = trim((string)$this->request->getPost('base_url'));
        $apiToken = trim((string)$this->request->getPost('api_token'));
        $noToken = $this->request->getPost('no_token') === '1';

        if (empty($baseUrl)) {
            $baseUrl = (string)$this->settingsModel->getSetting('agi_api_base_url', 'http://localhost:3000');
        }
        $baseUrl = rtrim($baseUrl, '/');

        if ($noToken) {
            $apiToken = '';
        } else if (empty($apiToken)) {
            $apiToken = (string)$this->settingsModel->getSetting('agi_api_token', '');
        }

        $targetUrl = $baseUrl . '/api/data/penyedia_pct_nontender?limit=1';

        $startTime = microtime(true);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $targetUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $httpHeaders = [
            'Accept: application/json',
            'User-Agent: InfoAJIP-Client/2.0'
        ];
        if (!$noToken && !empty($apiToken)) {
            $httpHeaders[] = 'Authorization: Bearer ' . $apiToken;
            $httpHeaders[] = 'X-API-KEY: ' . $apiToken;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $durationMs = round((microtime(true) - $startTime) * 1000);

        if ($curlError) {
            return $this->response->setJSON([
                'status'     => 'error',
                'http_code'  => 0,
                'latency_ms' => $durationMs,
                'message'    => "Gagal terhubung ke host {$baseUrl}: {$curlError}",
            ]);
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            $successMsg = ($noToken || empty($apiToken))
                ? "Koneksi ke AGI Integrator BERHASIL (HTTP {$httpCode}) dalam {$durationMs} ms tanpa token (Mode Publik)! Endpoint dapat diakses langsung."
                : "Koneksi ke AGI Integrator BERHASIL (HTTP {$httpCode}) dalam {$durationMs} ms. Server & Token valid!";
            return $this->response->setJSON([
                'status'     => 'success',
                'http_code'  => $httpCode,
                'latency_ms' => $durationMs,
                'message'    => $successMsg,
            ]);
        }

        if ($httpCode === 401 || $httpCode === 403) {
            $deniedMsg = ($noToken || empty($apiToken))
                ? "Server AGI Integrator terjangkau ({$durationMs} ms), namun endpoint ini MEWAJIBKAN Token Autentikasi (HTTP {$httpCode} Unauthorized/Forbidden). Silakan masukkan Token API atau nonaktifkan opsi Tanpa Token."
                : "Server AGI Integrator terjangkau ({$durationMs} ms), namun Token API DITOLAK (HTTP {$httpCode} Unauthorized/Forbidden). Pastikan token '{$apiToken}' terdaftar dan diizinkan di AGI Integrator.";
            return $this->response->setJSON([
                'status'     => 'warning',
                'http_code'  => $httpCode,
                'latency_ms' => $durationMs,
                'message'    => $deniedMsg,
            ]);
        }

        return $this->response->setJSON([
            'status'     => 'warning',
            'http_code'  => $httpCode,
            'latency_ms' => $durationMs,
            'message'    => "Mendapatkan respon HTTP {$httpCode} dari AGI Integrator dalam {$durationMs} ms.",
        ]);
    }

    public function importAgiConfig()
    {
        $useDefault = $this->request->getPost('use_default');

        if ($useDefault) {
            $defaultFile = WRITEPATH . 'agi_schema_default.json';
            if (!file_exists($defaultFile)) {
                return redirect()->to(base_url('user/api-settings'))->with('error', 'File schema bawaan tidak ditemukan di writable/agi_schema_default.json.');
            }
            $jsonString = file_get_contents($defaultFile);
        } else {
            $file = $this->request->getFile('config_file');
            if (!$file || !$file->isValid()) {
                return redirect()->to(base_url('user/api-settings'))->with('error', 'Silakan pilih file JSON konfigurasi schema AGI Integrator.');
            }

            $ext = strtolower($file->getClientExtension());
            if ($ext !== 'json') {
                return redirect()->to(base_url('user/api-settings'))->with('error', 'File harus berformat .json.');
            }

            $jsonString = file_get_contents($file->getTempName());
        }

        $decoded = json_decode($jsonString, true);
        if (!$decoded || !isset($decoded['sources']) || !is_array($decoded['sources'])) {
            return redirect()->to(base_url('user/api-settings'))->with('error', 'Format JSON schema tidak valid. Atribut "sources" tidak ditemukan.');
        }

        $totalSources = count($decoded['sources']);
        $this->settingsModel->updateSetting('agi_endpoints_config', $jsonString);
        $this->settingsModel->updateSetting('agi_last_import_at', date('Y-m-d H:i:s'));

        return redirect()->to(base_url('user/api-settings'))->with('success', "Konfigurasi schema AGI Integrator berhasil diimpor! Terdeteksi {$totalSources} endpoint API aktif.");
    }

    public function addManualEndpoint()
    {
        $name = trim((string)$this->request->getPost('name'));
        $slug = trim((string)$this->request->getPost('slug'));
        $sourceUrl = trim((string)$this->request->getPost('source_url'));
        $syncInterval = trim((string)$this->request->getPost('sync_interval'));
        $syncTime = trim((string)$this->request->getPost('sync_time'));
        $description = trim((string)$this->request->getPost('description'));

        if (empty($name)) {
            return redirect()->to(base_url('user/api-settings'))->with('error', 'Nama endpoint tidak boleh kosong.');
        }

        // Clean & format slug
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '-', $slug));
        $slug = trim($slug, '-');

        if (empty($slug)) {
            return redirect()->to(base_url('user/api-settings'))->with('error', 'Slug endpoint tidak valid. Gunakan huruf kecil, angka, atau tanda hubung (-).');
        }

        // Load existing config
        $endpointsRaw = $this->settingsModel->getSetting('agi_endpoints_config', '');
        $endpointsConfig = !empty($endpointsRaw) ? json_decode($endpointsRaw, true) : null;

        if (empty($endpointsConfig) || !isset($endpointsConfig['sources'])) {
            $defaultFile = WRITEPATH . 'agi_schema_default.json';
            if (file_exists($defaultFile)) {
                $raw = file_get_contents($defaultFile);
                $endpointsConfig = json_decode($raw, true);
            } else {
                $endpointsConfig = ['version' => '2.0', 'sources' => []];
            }
        }

        $sources = $endpointsConfig['sources'] ?? [];

        // Check if slug already exists
        foreach ($sources as $s) {
            if (($s['endpoint_slug'] ?? '') === $slug) {
                return redirect()->to(base_url('user/api-settings'))->with('error', "Slug endpoint '{$slug}' sudah terdaftar dalam katalog.");
            }
        }

        $newSource = [
            'id'                => time(),
            'name'              => $name,
            'type'              => 'api',
            'source_url'        => $sourceUrl,
            'sync_interval'     => !empty($syncInterval) ? $syncInterval : 'daily',
            'sync_time'         => !empty($syncTime) ? $syncTime : '04:00',
            'last_sync_at'      => null,
            'sync_status'       => 'idle',
            'sync_error'        => null,
            'sync_record_count' => 0,
            'endpoint_slug'     => $slug,
            'description'       => $description,
            'is_manual'         => true,
            'created_at'        => date('Y-m-d H:i:s'),
        ];

        // Prepend new source ke daftar paling atas
        array_unshift($sources, $newSource);
        $endpointsConfig['sources'] = $sources;

        $this->settingsModel->updateSetting('agi_endpoints_config', json_encode($endpointsConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return redirect()->to(base_url('user/api-settings'))->with('success', "Endpoint manual '{$name}' (/api/data/{$slug}) berhasil ditambahkan ke katalog AGI Integrator.");
    }

    public function deleteEndpoint()
    {
        $slug = trim((string)$this->request->getPost('slug'));
        if (empty($slug)) {
            return redirect()->to(base_url('user/api-settings'))->with('error', 'Slug endpoint tidak ditentukan.');
        }

        $endpointsRaw = $this->settingsModel->getSetting('agi_endpoints_config', '');
        $endpointsConfig = !empty($endpointsRaw) ? json_decode($endpointsRaw, true) : null;

        if (empty($endpointsConfig) || !isset($endpointsConfig['sources'])) {
            return redirect()->to(base_url('user/api-settings'))->with('error', 'Data katalog belum tersedia.');
        }

        $newSources = [];
        $found = false;
        foreach ($endpointsConfig['sources'] as $s) {
            if (($s['endpoint_slug'] ?? '') === $slug) {
                $found = true;
                continue;
            }
            $newSources[] = $s;
        }

        if (!$found) {
            return redirect()->to(base_url('user/api-settings'))->with('error', "Endpoint dengan slug '{$slug}' tidak ditemukan.");
        }

        $endpointsConfig['sources'] = $newSources;
        $this->settingsModel->updateSetting('agi_endpoints_config', json_encode($endpointsConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return redirect()->to(base_url('user/api-settings'))->with('success', "Endpoint '/api/data/{$slug}' berhasil dihapus dari katalog.");
    }

    public function previewEndpoint($slug)
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        $savedBaseUrl = $this->settingsModel->getSetting('agi_api_base_url');
        $baseUrl = !empty($savedBaseUrl) ? rtrim((string)$savedBaseUrl, '/') : rtrim(env('AJIP_API_BASE_URL', 'http://localhost:3000'), '/');
        $noToken = (string)$this->settingsModel->getSetting('agi_no_token', '0') === '1';
        $apiToken = $noToken ? '' : (string)$this->settingsModel->getSetting('agi_api_token', '');

        $targetUrl = "{$baseUrl}/api/data/{$slug}?limit=5";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $targetUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $httpHeaders = [
            'Accept: application/json',
            'User-Agent: InfoAJIP-Client/2.0'
        ];
        if (!empty($apiToken)) {
            $httpHeaders[] = 'Authorization: Bearer ' . $apiToken;
            $httpHeaders[] = 'X-API-KEY: ' . $apiToken;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            echo json_encode([
                'status'  => 'error',
                'message' => "Gagal menghubungi {$targetUrl}: {$curlError}"
            ]);
            exit;
        }

        $decoded = json_decode($response, true);
        $previewData = [];
        if (is_array($decoded)) {
            if (isset($decoded['data']) && is_array($decoded['data'])) {
                $previewData = array_slice($decoded['data'], 0, 5);
            } else {
                $previewData = array_slice($decoded, 0, 5);
            }
        }

        echo json_encode([
            'status'     => $httpCode >= 200 && $httpCode < 300 ? 'success' : 'error',
            'http_code'  => $httpCode,
            'url'        => $targetUrl,
            'total_rows' => is_array($decoded) ? count($decoded) : 0,
            'preview'    => $previewData,
            'raw_sample' => substr((string)$response, 0, 1000)
        ]);
        exit;
    }

    public function sync()
    {
        $data = [
            'title' => 'Sinkronisasi Data RUP',
            'last_sync_penyedia' => $this->settingsModel->getSetting('last_sync_penyedia', 'Belum pernah'),
            'last_sync_swakelola' => $this->settingsModel->getSetting('last_sync_swakelola', 'Belum pernah'),
            'last_sync_tender' => $this->settingsModel->getSetting('last_sync_tender', 'Belum pernah'),
            'last_sync_nontender' => $this->settingsModel->getSetting('last_sync_nontender', 'Belum pernah'),
            'last_sync_epurchasing' => $this->settingsModel->getSetting('last_sync_epurchasing', 'Belum pernah'),
            'last_sync_master' => $this->settingsModel->getSetting('last_sync_master', 'Belum pernah'),
            'last_sync_pct_nontender' => $this->settingsModel->getSetting('last_sync_pct_nontender', 'Belum pernah'),
            'last_sync_pct_swakelola' => $this->settingsModel->getSetting('last_sync_pct_swakelola', 'Belum pernah'),
            'last_sync_vendor_details' => $this->settingsModel->getSetting('last_sync_vendor_details', 'Belum pernah'),
            'last_sync_monev_progres' => $this->settingsModel->getSetting('last_sync_monev_progres', 'Belum pernah'),
        ];

        return view('user/sync_settings', $data);
    }

    public function doSync($type)
    {
        // Increase execution time for large data
        set_time_limit(600);
        ini_set('memory_limit', '1024M');
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        $userRole = session()->get('role');
        $allowedRoles = ['admin', 'operator'];
        if (!in_array($userRole, $allowedRoles)) {
            if (!headers_sent()) {
                http_response_code(403);
            }
            echo json_encode([
                'status' => 'error',
                'message' => 'Akses ditolak. Anda tidak memiliki izin untuk melakukan sinkronisasi.'
            ]);
            exit;
        }

        $syncService = new \App\Services\SyncService();
        $currentYear = $this->request->getGet('tahun') ?: date('Y');

        // Clean callback to prevent corrupting JSON response
        $keepAliveCallback = function($msg) {
            // Keep-alive without echoing spaces to preserve clean JSON output
            if (function_exists('apache_reset_timeout')) {
                @apache_reset_timeout();
            }
        };

        try {
            switch ($type) {
                case 'penyedia':
                    $count = $syncService->syncPenyedia($currentYear, $keepAliveCallback);
                    $this->settingsModel->updateSetting('last_sync_penyedia', date('Y-m-d H:i:s'));
                    break;
                case 'swakelola':
                    $count = $syncService->syncSwakelola($currentYear, $keepAliveCallback);
                    $this->settingsModel->updateSetting('last_sync_swakelola', date('Y-m-d H:i:s'));
                    break;
                case 'tender':
                    $count = $syncService->syncTender($currentYear, $keepAliveCallback);
                    $this->settingsModel->updateSetting('last_sync_tender', date('Y-m-d H:i:s'));
                    break;
                case 'nontender':
                    $count = $syncService->syncNontender($currentYear, $keepAliveCallback);
                    $this->settingsModel->updateSetting('last_sync_nontender', date('Y-m-d H:i:s'));
                    break;
                case 'epurchasing':
                    $count = $syncService->syncEpurchasing($currentYear, $keepAliveCallback);
                    $this->settingsModel->updateSetting('last_sync_epurchasing', date('Y-m-d H:i:s'));
                    break;
                case 'master':
                    $count = $syncService->syncMasterAnggaran($currentYear, $keepAliveCallback);
                    $this->settingsModel->updateSetting('last_sync_master', date('Y-m-d H:i:s'));
                    break;
                case 'pct_nontender':
                    $count = $syncService->syncPencatatanNontender($currentYear, $keepAliveCallback);
                    $this->settingsModel->updateSetting('last_sync_pct_nontender', date('Y-m-d H:i:s'));
                    break;
                case 'pct_swakelola':
                    $count = $syncService->syncPencatatanSwakelola($currentYear, $keepAliveCallback);
                    $this->settingsModel->updateSetting('last_sync_pct_swakelola', date('Y-m-d H:i:s'));
                    break;
                case 'vendor_details':
                    $count = $syncService->syncVendorDetails($currentYear, $keepAliveCallback);
                    $this->settingsModel->updateSetting('last_sync_vendor_details', date('Y-m-d H:i:s'));
                    break;
                case 'monev_progres':
                    $count = $syncService->syncMonevProgres($currentYear, $keepAliveCallback);
                    $this->settingsModel->updateSetting('last_sync_monev_progres', date('Y-m-d H:i:s'));
                    break;
                default:
                    throw new \Exception("Tipe sinkronisasi tidak dikenal: " . esc($type));
            }

            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'status' => 'success',
                'message' => "Berhasil sinkronisasi $count data $type."
            ]);
            exit;
        } catch (\Throwable $e) {
            log_message('error', "doSync {$type} error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }
}
