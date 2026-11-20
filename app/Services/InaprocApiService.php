<?php

namespace App\Services;

use App\Models\InaprocVendorSyncModel;

class InaprocApiService
{
    private $client;
    private $token;
    private $cacheDir;
    private $syncModel;

    public function __construct($client = null, ?string $token = null, ?string $cacheDir = null, ?InaprocVendorSyncModel $syncModel = null)
    {
        $this->client = $client ?: \Config\Services::curlrequest();
        $this->token = $token ?: (getenv('INAPROC_BEARER_TOKEN') ?: env('INAPROC_BEARER_TOKEN', ''));
        $this->cacheDir = $cacheDir ?: WRITEPATH . 'inaproc/vendors/';
        $this->syncModel = $syncModel ?: new InaprocVendorSyncModel();
    }

    public function getVendorDetail(string $kode): array
    {
        $kode = trim($kode);
        $cacheFile = rtrim($this->cacheDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $kode . '.json';
        $cached = $this->readCache($cacheFile);
        if (is_array($cached) && !empty($cached['nama_penyedia'])) {
            return ['ok' => true, 'data' => $cached, 'source' => 'cache'];
        }

        $stored = $this->getStoredVendorDetail($kode);
        if (!empty($stored['nama_penyedia'])) {
            return ['ok' => true, 'data' => $stored, 'source' => 'db'];
        }

        return $this->syncVendorDetail($kode, false);
    }

    public function syncVendorDetail(string $kode, bool $force = false): array
    {
        $kode = trim($kode);
        if ($kode === '') {
            return ['ok' => false, 'error' => 'Kode penyedia kosong'];
        }

        $cacheFile = rtrim($this->cacheDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $kode . '.json';
        $stored = $this->getStoredVendorDetail($kode);

        if (!$force && !empty($stored['nama_penyedia']) && !empty($stored['last_sync_at']) && strtotime($stored['last_sync_at']) > strtotime('-24 hours')) {
            return ['ok' => true, 'data' => $stored, 'source' => 'db'];
        }

        $result = $this->requestJson([
            'https://data.inaproc.id/api/v1/ekatalog/penyedia-detail?kode_penyedia=' . urlencode($kode),
            'https://data.inaproc.id/api/legacy/ekatalog/penyedia-detail?kode_penyedia=' . urlencode($kode),
        ], 15);

        if ($result['ok']) {
            $body = $result['body'];
            $vendor = $body[0] ?? ($body['data'][0] ?? null);

            if (is_array($vendor) && isset($vendor['nama_penyedia'])) {
                $payload = $this->normalizeVendorPayload($vendor, $kode);
                $this->saveSyncRecord($kode, $payload, 'success');
                $this->writeCache($cacheFile, $payload);
                return ['ok' => true, 'data' => $payload, 'source' => 'api'];
            }

            $this->saveSyncRecord($kode, ['kode_penyedia' => $kode], 'failed', 'API returned empty data');
            return ['ok' => false, 'error' => 'API returned empty data', 'detail' => $result['detail'] ?? null];
        }

        if (!empty($stored['nama_penyedia'])) {
            return ['ok' => true, 'data' => $stored, 'source' => 'db', 'warning' => 'Menggunakan data terakhir yang tersimpan'];
        }

        $this->saveSyncRecord($kode, ['kode_penyedia' => $kode], 'failed', $result['detail'] ?? ($result['error'] ?? 'API unavailable'));
        return [
            'ok' => false,
            'error' => $result['error'] ?? 'API unavailable',
            'detail' => $result['detail'] ?? null,
            'source' => 'api',
        ];
    }

    private function getStoredVendorDetail(string $kode): array
    {
        $row = $this->syncModel->where('kode_penyedia', $kode)->orderBy('updated_at', 'DESC')->first();
        if (!is_array($row)) {
            return [];
        }

        $result = [];
        foreach (['kode_penyedia', 'nama_penyedia', 'bentuk_usaha', 'jenis_perusahaan', 'npwp_penyedia', 'alamat_penyedia', 'email', 'telepon', 'nib', 'status_aktif', 'status_umkk', 'kbli_name', 'kbli_id', 'raw_response', 'last_sync_status', 'last_sync_at', 'error_message'] as $field) {
            if (array_key_exists($field, $row)) {
                $result[$field] = $row[$field];
            }
        }

        if (!empty($row['raw_response'])) {
            $decoded = json_decode($row['raw_response'], true);
            if (is_array($decoded)) {
                foreach ($decoded as $key => $value) {
                    if (!array_key_exists($key, $result)) {
                        $result[$key] = $value;
                    }
                }
            }
        }

        return $result;
    }

    private function saveSyncRecord(string $kode, array $payload, string $status, ?string $error = null): void
    {
        $data = [
            'kode_penyedia' => $kode,
            'nama_penyedia' => $payload['nama_penyedia'] ?? null,
            'bentuk_usaha' => $payload['bentuk_usaha'] ?? null,
            'jenis_perusahaan' => $payload['jenis_perusahaan'] ?? null,
            'npwp_penyedia' => $payload['npwp_penyedia'] ?? null,
            'alamat_penyedia' => $payload['alamat_penyedia'] ?? null,
            'email' => $payload['email'] ?? null,
            'telepon' => $payload['telepon'] ?? null,
            'nib' => $payload['nib'] ?? null,
            'status_aktif' => $payload['status_aktif'] ?? null,
            'status_umkk' => $payload['status_umkk'] ?? null,
            'kbli_name' => $payload['kbli_name'] ?? null,
            'kbli_id' => $payload['kbli_id'] ?? null,
            'raw_response' => $payload['raw_response'] ?? null,
            'last_sync_status' => $status,
            'last_sync_at' => date('Y-m-d H:i:s'),
            'error_message' => $error,
        ];

        $row = $this->syncModel->where('kode_penyedia', $kode)->first();
        if ($row) {
            // Jangan timpa alamat lengkap dari SIKaP dengan alamat bertanda bintang (*****) dari Inaproc API
            if (!empty($row['alamat_penyedia']) && strpos($row['alamat_penyedia'], '*****') === false && !empty($data['alamat_penyedia']) && strpos($data['alamat_penyedia'], '*****') !== false) {
                $data['alamat_penyedia'] = $row['alamat_penyedia'];
            }
            // Pertahankan email & telepon dari SIKaP jika API Inaproc mengembalikan null / kosong
            if (empty($data['email']) && !empty($row['email'])) $data['email'] = $row['email'];
            if (empty($data['telepon']) && !empty($row['telepon'])) $data['telepon'] = $row['telepon'];

            $this->syncModel->update($row['id'], $data);
        } else {
            $this->syncModel->insert($data);
        }
    }

    private function normalizeVendorPayload(array $vendor, string $kode): array
    {
        $kbliName = $vendor['kbli_name'] ?? null;
        $kbliId = $vendor['kbli_id'] ?? null;

        if (is_array($kbliName)) {
            $kbliName = implode(', ', array_filter($kbliName));
        }
        if (is_array($kbliId)) {
            $kbliId = implode(', ', array_filter($kbliId));
        }

        return [
            'kode_penyedia' => $kode,
            'nama_penyedia' => $vendor['nama_penyedia'] ?? null,
            'bentuk_usaha' => $vendor['bentuk_usaha'] ?? $vendor['bentuk_perusahaan'] ?? null,
            'jenis_perusahaan' => $vendor['jenis_perusahaan'] ?? $vendor['jenis_usaha'] ?? null,
            'npwp_penyedia' => $vendor['npwp_penyedia'] ?? $vendor['npwp'] ?? null,
            'alamat_penyedia' => $vendor['alamat_penyedia'] ?? $vendor['alamat'] ?? null,
            'email' => $vendor['email'] ?? null,
            'telepon' => $vendor['telepon'] ?? $vendor['no_telp'] ?? null,
            'nib' => $vendor['nib'] ?? null,
            'status_aktif' => $vendor['status_aktif'] ?? null,
            'status_umkk' => $vendor['status_umkk'] ?? null,
            'kbli_name' => $kbliName,
            'kbli_id' => $kbliId,
            'raw_response' => json_encode($vendor, JSON_UNESCAPED_UNICODE),
        ];
    }

    private function requestJson(array $urls, int $timeout = 15): array
    {
        $headers = [
            'Accept' => 'application/json',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ];

        if ($this->token !== '') {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }

        $lastError = null;
        foreach ($urls as $url) {
            try {
                $response = $this->client->get($url, [
                    'headers' => $headers,
                    'http_errors' => false,
                    'timeout' => $timeout,
                ]);

                $statusCode = $response->getStatusCode();
                $body = (string) $response->getBody();

                if ($statusCode === 200) {
                    $decoded = json_decode($body, true);
                    return ['ok' => true, 'body' => $decoded ?? [], 'detail' => $body];
                }

                $lastError = 'HTTP ' . $statusCode . ': ' . $body;
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        return ['ok' => false, 'error' => 'API unavailable', 'detail' => $lastError];
    }

    private function readCache(string $cacheFile): ?array
    {
        if (!is_file($cacheFile)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($cacheFile), true);
        return is_array($decoded) ? $decoded : null;
    }

    private function writeCache(string $cacheFile, array $payload): void
    {
        $dir = dirname($cacheFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($cacheFile, json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
}
