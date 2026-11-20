<?php

namespace App\Services;

use App\Models\RupPenyediaModel;
use App\Models\RupSwakelolaModel;
use App\Models\LpseTenderModel;
use App\Models\LpseNontenderModel;
use App\Models\EpurchasingModel;
use App\Models\MasterAnggaranModel;
use App\Models\LpsePencatatanNontenderModel;
use App\Models\LpsePencatatanSwakelolaModel;
use App\Services\InaprocApiService;
use CodeIgniter\Config\Services;

class SyncService
{
    protected $client;
    protected $baseUrl;
    protected $apiToken;

    public function __construct()
    {
        $this->client = Services::curlrequest();
        $settingsModel = new \App\Models\SettingsModel();
        $savedUrl = $settingsModel->getSetting('agi_api_base_url');
        $this->baseUrl = !empty($savedUrl) ? rtrim((string)$savedUrl, '/') : rtrim(env('AJIP_API_BASE_URL', 'https://ajip-api.cloud'), '/');
        $noToken = (string)$settingsModel->getSetting('agi_no_token', '0') === '1';
        $this->apiToken = $noToken ? '' : (string)$settingsModel->getSetting('agi_api_token', '');
    }

    private function fetchApi($url)
    {
        try {
            $headers = ['Accept' => 'application/json'];
            if (!empty($this->apiToken)) {
                $headers['Authorization'] = 'Bearer ' . $this->apiToken;
                $headers['X-API-KEY'] = $this->apiToken;
            }
            $response = $this->client->get($url, [
                'timeout' => 120,
                'verify' => false,
                'headers' => $headers
            ]);
            $body = $response->getBody();
            $data = json_decode($body, true);
            if (!is_array($data)) {
                log_message('error', "API $url tidak mengembalikan array. Body: " . substr($body, 0, 200));
                return [];
            }
            // Handle if API wraps response in a 'data' key
            if (isset($data['data']) && is_array($data['data'])) {
                return $data['data'];
            }
            return $data;
        } catch (\Exception $e) {
            log_message('error', "Gagal fetch API $url: " . $e->getMessage());
            return [];
        }
    }

    private function fetchApisConcurrent(array $urls)
    {
        $multi = curl_multi_init();
        $channels = [];
        $results = [];

        $httpHeaders = ['Accept: application/json'];
        if (!empty($this->apiToken)) {
            $httpHeaders[] = 'Authorization: Bearer ' . $this->apiToken;
            $httpHeaders[] = 'X-API-KEY: ' . $this->apiToken;
        }

        foreach ($urls as $key => $url) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);

            curl_multi_add_handle($multi, $ch);
            $channels[$key] = $ch;
        }

        $active = null;
        do {
            $mrc = curl_multi_exec($multi, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($multi) != -1) {
                do {
                    $mrc = curl_multi_exec($multi, $active);
                    // Keep-alive untuk mencegah 504 Gateway Timeout pada OpenResty/Nginx
                    echo " ";
                    if (ob_get_level() > 0) ob_flush();
                    flush();
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }

        foreach ($channels as $key => $ch) {
            $body = curl_multi_getcontent($ch);
            $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            curl_multi_remove_handle($multi, $ch);

            $data = json_decode($body, true);
            if (!is_array($data)) {
                log_message('error', "API $url tidak mengembalikan array. Body: " . substr((string) $body, 0, 200));
                $results[$key] = [];
                continue;
            }

            if (isset($data['data']) && is_array($data['data'])) {
                $results[$key] = $data['data'];
            } else {
                $results[$key] = $data;
            }
        }

        curl_multi_close($multi);
        return $results;
    }

    public function syncPenyedia($year, $callback = null)
    {
        $urlRup = $this->baseUrl . '/api/data/perbandingan-rup-penyedia';
        $urlAnggaran = $this->baseUrl . '/api/data/rup-paket-anggaran';
        $model = new RupPenyediaModel();

        if ($callback)
            $callback('Mengambil data RUP Penyedia & Anggaran (Paralel)...');
        $apiResults = $this->fetchApisConcurrent([
            'rup' => $urlRup,
            'anggaran' => $urlAnggaran
        ]);

        $rawRup = $apiResults['rup'];
        $rawAnggaran = $apiResults['anggaran'];

        $anggaranMap = [];
        foreach ($rawAnggaran as $a) {
            $kd = $a['kd_rup'] ?? null;
            if ($kd)
                $anggaranMap[$kd] = $a;
        }

        $model->where('tahun', $year)->delete();
        $batch = [];
        $count = 0;
        foreach ($rawRup as $item) {
            $kdRup = $item['kd_rup'] ?? null;
            $ang = $anggaranMap[$kdRup] ?? [];

            // Standardize Year
            $yr = $item['tahun_anggaran'] ?? ($item['tahun_anggaran_sirup'] ?? ($item['tahun_anggaran_dana'] ?? ($item['tahun'] ?? ($ang['tahun_anggaran'] ?? date('Y')))));
            if ((int) $yr !== (int) $year)
                continue; // HANYA PROSES TAHUN TERPILIH

            $batch[] = [
                'kd_rup' => $kdRup,
                'nama_paket' => $item['nama_paket'] ?? ($ang['nama_paket'] ?? ''),
                'kd_satker' => $item['kd_satker'] ?? ($item['kode_satker'] ?? ($item['kd_organisasi'] ?? ($ang['kd_satker'] ?? ''))),
                'nama_satker' => $item['nama_satker'] ?? ($item['satker'] ?? ($item['Nama_Satker'] ?? ($ang['nama_satker'] ?? ''))),
                'pagu' => (float) ($item['pagu'] ?? ($ang['pagu'] ?? 0)),
                'jenis_pengadaan' => $item['jenis_pengadaan'] ?? ($item['jenis'] ?? ($ang['jenis_pengadaan'] ?? '')),
                'metode_pengadaan' => $item['metode_pengadaan'] ?? ($item['metode'] ?? ($item['tipe'] ?? ($item['metode_pengadaan_sirup'] ?? ''))),
                'volume_pekerjaan' => $item['volume_pekerjaan'] ?? '',
                'uraian_pekerjaan' => $item['uraian_pekerjaan'] ?? ($item['urarian_pekerjaan'] ?? ''),
                'spesifikasi_pekerjaan' => $item['spesifikasi_pekerjaan'] ?? '',
                'mak' => $ang['mak'] ?? '',
                'tgl_awal_pemilihan' => $item['tgl_awal_pemilihan'] ?? ($item['tgl_awal_kontrak'] ?? ''),
                'tgl_akhir_pemilihan' => $item['tgl_akhir_pemilihan'] ?? ($item['tgl_akhir_kontrak'] ?? ''),
                'status_umumkan_rup' => $item['status_umumkan_rup'] ?? ($ang['status_umumkan_rup'] ?? 'Terumumkan'),
                'status_pdn' => $item['status_pdn'] ?? null,
                'sumber_dana' => $item['sumber_dana'] ?? ($ang['sumber_dana'] ?? ($item['sumber_dana_string'] ?? '')),
                'tahun' => $yr,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if (count($batch) >= 500) {
                $model->insertBatch($batch);
                $count += count($batch);
                $batch = [];
                if ($callback) $callback(" ");
            }
        }
        if (!empty($batch)) {
            $model->insertBatch($batch);
            $count += count($batch);
        }
        return $count;
    }

    public function syncSwakelola($year, $callback = null)
    {
        $urlRup = $this->baseUrl . '/api/data/perbandingan-swakelola';
        $urlAnggaran = $this->baseUrl . '/api/data/rup-paket-swakelola';
        $model = new RupSwakelolaModel();

        if ($callback)
            $callback('Mengambil data RUP Swakelola & Anggaran (Paralel)...');
        $apiResults = $this->fetchApisConcurrent([
            'rup' => $urlRup,
            'anggaran' => $urlAnggaran
        ]);

        $rawRup = $apiResults['rup'];
        $rawAnggaran = $apiResults['anggaran'];

        $anggaranMap = [];
        foreach ($rawAnggaran as $a) {
            $kd = $a['kd_rup'] ?? null;
            if ($kd)
                $anggaranMap[$kd] = $a;
        }

        $model->where('tahun', $year)->delete();
        $batch = [];
        $count = 0;
        foreach ($rawRup as $item) {
            $kdRup = $item['kd_rup'] ?? null;
            $ang = $anggaranMap[$kdRup] ?? [];

            $yr = $item['tahun_anggaran'] ?? ($item['tahun_anggaran_sirup'] ?? ($item['tahun_anggaran_dana'] ?? ($item['tahun'] ?? ($ang['tahun_anggaran'] ?? date('Y')))));
            if ((int) $yr !== (int) $year)
                continue;

            $batch[] = [
                'kd_rup' => $kdRup,
                'nama_paket' => $item['nama_paket'] ?? ($ang['nama_paket'] ?? ''),
                'kd_satker' => $item['kd_satker'] ?? ($item['kode_satker'] ?? ($item['kd_organisasi'] ?? ($ang['kd_satker'] ?? ''))),
                'nama_satker' => $item['nama_satker'] ?? ($item['satker'] ?? ($item['Nama_Satker'] ?? ($ang['nama_satker'] ?? ''))),
                'pagu' => (float) ($item['pagu'] ?? ($ang['pagu'] ?? 0)),
                'tipe_swakelola' => $item['tipe_swakelola'] ?? ($item['tipe'] ?? ($item['metode'] ?? ($item['tipe_swakelola_sirup'] ?? ''))),
                'volume_pekerjaan' => $item['volume_pekerjaan'] ?? '',
                'uraian_pekerjaan' => $item['uraian_pekerjaan'] ?? ($item['urarian_pekerjaan'] ?? ($item['kegiatan'] ?? '')),
                'mak' => $ang['mak'] ?? '',
                'tgl_awal_pelaksanaan' => $item['tgl_awal_pelaksanaan_kontrak'] ?? ($item['tgl_awal_pelaksanaan'] ?? ($item['tgl_awal_pemilihan'] ?? '')),
                'tgl_akhir_pelaksanaan' => $item['tgl_akhir_pelaksanaan_kontrak'] ?? ($item['tgl_akhir_pelaksanaan'] ?? ($item['tgl_akhir_pemilihan'] ?? '')),
                'status_umumkan_rup' => $item['status_umumkan_rup'] ?? ($ang['status_umumkan_rup'] ?? 'Terumumkan'),
                'tahun' => $yr,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if (count($batch) >= 500) {
                $model->insertBatch($batch);
                $count += count($batch);
                $batch = [];
                if ($callback) $callback(" ");
            }
        }
        if (!empty($batch)) {
            $model->insertBatch($batch);
            $count += count($batch);
        }
        return $count;
    }

    public function syncTender($year, $callback = null)
    {
        $urlTender = $this->baseUrl . '/api/data/tender-selesai';
        $urlKontrak = $this->baseUrl . '/api/data/list-tender-kontrak';
        $urlAnn = $this->baseUrl . '/api/data/pengumuman-tender';
        $model = new LpseTenderModel();

        // Fetch existing tender fields to preserve local PPK edits
        $existingTenders = $model->where('tahun', $year)
            ->groupStart()
                ->where('no_spmk_spp IS NOT NULL')
                ->orWhere('waktu_penyelesaian IS NOT NULL')
                ->orWhere('tgl_mulai_pekerjaan IS NOT NULL')
                ->orWhere('tgl_selesai_pekerjaan IS NOT NULL')
            ->groupEnd()
            ->findAll();

        $tenderMonevMap = [];
        foreach ($existingTenders as $et) {
            $kd = trim((string) ($et['kd_paket'] ?? ''));
            if ($kd !== '') {
                $tenderMonevMap[$kd] = [
                    'no_spmk_spp' => $et['no_spmk_spp'],
                    'waktu_penyelesaian' => $et['waktu_penyelesaian'],
                    'tgl_mulai_pekerjaan' => $et['tgl_mulai_pekerjaan'],
                    'tgl_selesai_pekerjaan' => $et['tgl_selesai_pekerjaan'],
                ];
            }
        }

        if ($callback)
            $callback('Mengambil data Tender, Kontrak & Pengumuman (Paralel)...');
        $apiResults = $this->fetchApisConcurrent([
            'tender' => $urlTender,
            'kontrak' => $urlKontrak,
            'ann' => $urlAnn
        ]);

        $rawTender = $apiResults['tender'];
        $rawKontrak = $apiResults['kontrak'];
        $rawAnn = $apiResults['ann'];

        $kontrakMap = [];
        foreach ($rawKontrak as $k) {
            $kd = trim((string) ($k['kd_tender'] ?? ($k['kd_paket'] ?? '')));
            $rup = trim((string) ($k['kd_rup'] ?? ''));
            if ($kd !== '')
                $kontrakMap[$kd] = $k;
            if ($rup !== '')
                $kontrakMap['rup_' . $rup] = $k;
        }

        $annMap = [];
        foreach ($rawAnn as $a) {
            $kd = trim((string) ($a['kd_tender'] ?? ''));
            $kdPkt = trim((string) ($a['kd_pkt_dce'] ?? ($a['kd_paket'] ?? '')));
            if ($kd !== '')
                $annMap[$kd] = $a;
            if ($kdPkt !== '')
                $annMap[$kdPkt] = $a;
        }

        $model->where('tahun', $year)->delete();
        $batch = [];
        $count = 0;
        foreach ($rawTender as $item) {
            $kdTender = trim((string) ($item['kd_tender'] ?? ($item['kd_paket'] ?? ($item['kode_paket'] ?? ($item['order_id'] ?? '')))));
            $ann = $annMap[$kdTender] ?? [];
            $kdRup = $item['kd_rup'] ?? ($item['rup_code'] ?? ($ann['kd_rup'] ?? ($ann['kode_rup'] ?? null)));

            $kontrak = $kontrakMap[$kdTender] ?? ($kontrakMap['rup_' . $kdRup] ?? []);

            $yr = $item['fiscal_year'] ?? ($item['tahun_anggaran'] ?? ($item['tahun'] ?? ($kontrak['tahun_anggaran'] ?? ($ann['tahun_anggaran'] ?? date('Y')))));
            if (strlen($yr) > 4 && strpos($yr, '-') !== false)
                $yr = substr($yr, 0, 4);
            if ((int) $yr !== (int) $year)
                continue;

            $namaPenyedia = trim($item['nama_penyedia'] ?? ($kontrak['nama_penyedia'] ?? ($ann['nama_penyedia'] ?? ($item['pemenang'] ?? ''))));
            $status = $item['status_tender'] ?? ($item['status'] ?? ($item['tahapan'] ?? ($kontrak['status_kontrak'] ?? ($ann['status'] ?? 'Selesai'))));
            if ($namaPenyedia === '') {
                $status = 'Sedang Berlangsung';
                $nilaiKontrak = 0.0;
            } else {
                $nilaiKontrak = (float) ($item['nilai_kontrak'] ?? ($kontrak['harga_final'] ?? ($kontrak['nilai_kontrak'] ?? ($ann['nilai_kontrak'] ?? 0))));
                if ($nilaiKontrak == 0) {
                    $nilaiKontrak = (float) ($item['nilai_terkoreksi'] ?? ($ann['nilai_terkoreksi'] ?? ($item['nilai_negosiasi'] ?? ($ann['nilai_negosiasi'] ?? 0))));
                }
            }

            $preserved = $tenderMonevMap[$kdTender] ?? null;

            $batch[] = [
                'kd_rup' => $kdRup,
                'tahun' => (int) $yr,
                'kd_paket' => $kdTender,
                'nama_paket' => $item['nama_paket'] ?? ($item['rup_name'] ?? ($item['nama_tender'] ?? ($kontrak['nama_paket'] ?? ($ann['nama_paket'] ?? '')))),
                'pagu' => (float) ($item['pagu'] ?? ($item['total'] ?? ($kontrak['pagu'] ?? ($ann['pagu'] ?? 0)))),
                'hps' => (float) ($item['hps'] ?? ($item['nilai_hps'] ?? ($kontrak['nilai_hps'] ?? ($ann['hps'] ?? 0)))),
                'nilai_penawaran' => (float) ($item['nilai_penawaran'] ?? ($item['nilai_terkoreksi'] ?? 0)),
                'nilai_kontrak' => $nilaiKontrak,
                'nama_penyedia' => $namaPenyedia,
                'kd_penyedia' => $kontrak['kd_penyedia'] ?? ($ann['kd_penyedia'] ?? null),
                'npwp_penyedia' => $kontrak['npwp_penyedia'] ?? null,
                'alamat_penyedia' => $kontrak['alamat_penyedia'] ?? null,
                'bentuk_usaha' => $kontrak['bentuk_usaha_penyedia'] ?? null,
                'nama_satker' => $item['nama_satker'] ?? ($item['satker'] ?? ($kontrak['nama_satker'] ?? ($ann['nama_satker'] ?? ''))),
                'kd_satker' => $item['kd_satker'] ?? ($item['kode_satker'] ?? ($kontrak['kd_satker'] ?? '')),
                'metode' => $item['metode_pengadaan'] ?? ($item['metode'] ?? ($item['mtd_pemilihan'] ?? ($ann['mtd_pemilihan'] ?? ''))),
                'status' => $status,
                'tgl_pemenang' => $item['tgl_penetapan_pemenang'] ?? ($item['tgl_pemenang'] ?? ($ann['tgl_pemenang'] ?? '')),
                'tgl_kontrak' => $kontrak['tgl_kontrak'] ?? ($item['tgl_kontrak'] ?? ''),
                'no_kontrak' => $kontrak['no_kontrak'] ?? ($item['no_kontrak'] ?? ''),
                'jenis_pengadaan' => $item['jenis_pengadaan'] ?? ($ann['jenis_pengadaan'] ?? ''),
                'mtd_evaluasi' => $ann['mtd_evaluasi'] ?? ($item['mtd_evaluasi'] ?? ''),
                'mtd_kualifikasi' => $ann['mtd_kualifikasi'] ?? ($item['mtd_kualifikasi'] ?? ''),
                'kontrak_pembayaran' => $kontrak['jenis_kontrak'] ?? ($ann['kontrak_pembayaran'] ?? ($item['kontrak_pembayaran'] ?? '')),
                'status_kontrak' => $kontrak['status_kontrak'] ?? '',
                'sumber_dana' => $item['sumber_dana'] ?? ($kontrak['sumber_dana'] ?? ($ann['sumber_dana'] ?? ($item['sumber_dana_string'] ?? ''))),
                'no_spmk_spp' => $item['no_spmk_spp'] ?? ($kontrak['no_spmk_spp'] ?? ($ann['no_spmk_spp'] ?? ($preserved ? $preserved['no_spmk_spp'] : null))),
                'waktu_penyelesaian' => isset($item['waktu_penyelesaian']) ? (int)$item['waktu_penyelesaian'] : (isset($kontrak['waktu_penyelesaian']) ? (int)$kontrak['waktu_penyelesaian'] : ($preserved ? $preserved['waktu_penyelesaian'] : null)),
                'tgl_mulai_pekerjaan' => $this->_normalizeDate($item['tgl_mulai_pekerjaan'] ?? ($kontrak['tgl_mulai_pekerjaan'] ?? ($preserved ? $preserved['tgl_mulai_pekerjaan'] : null))),
                'tgl_selesai_pekerjaan' => $this->_normalizeDate($item['tgl_selesai_pekerjaan'] ?? ($kontrak['tgl_selesai_pekerjaan'] ?? ($preserved ? $preserved['tgl_selesai_pekerjaan'] : null))),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if (count($batch) >= 500) {
                $model->insertBatch($batch);
                $count += count($batch);
                $batch = [];
                if ($callback) $callback(" ");
            }
        }
        if (!empty($batch)) {
            $model->insertBatch($batch);
            $count += count($batch);
        }
        return $count;
    }

    public function syncNontender($year, $callback = null)
    {
        $urlNontender = $this->baseUrl . '/api/data/nontender-selesai';
        $urlKontrak = $this->baseUrl . '/api/data/list-nontender-ekontrak';
        $model = new LpseNontenderModel();

        if ($callback)
            $callback('Mengambil data Non-Tender & Kontrak (Paralel)...');
        $apiResults = $this->fetchApisConcurrent([
            'nontender' => $urlNontender,
            'kontrak' => $urlKontrak
        ]);

        $rawNontender = $apiResults['nontender'];
        $rawKontrak = $apiResults['kontrak'];

        $kontrakMap = [];
        foreach ($rawKontrak as $k) {
            $kd = trim((string) ($k['kd_nontender'] ?? ($k['kd_paket'] ?? '')));
            $rup = trim((string) ($k['kd_rup'] ?? ''));
            if ($kd !== '')
                $kontrakMap[$kd] = $k;
            if ($rup !== '')
                $kontrakMap['rup_' . $rup] = $k;
        }

        // Preserve PPK local inputs before wipe out
        $preservedData = [];
        $existing = $model->where('tahun', $year)
            ->groupStart()
                ->where('no_spmk_spp IS NOT NULL')
                ->orWhere('tgl_mulai_pekerjaan IS NOT NULL')
                ->orWhere('no_bap IS NOT NULL')
                ->orWhere('tgl_bap IS NOT NULL')
                ->orWhere('progres_pekerjaan IS NOT NULL')
                ->orWhere('no_bast IS NOT NULL')
                ->orWhere('tgl_bast IS NOT NULL')
                ->orWhere('besar_pembayaran IS NOT NULL')
            ->groupEnd()
            ->get()->getResultArray();
        
        foreach ($existing as $et) {
            $preservedData[$et['kd_paket']] = [
                'no_spmk_spp' => $et['no_spmk_spp'],
                'waktu_penyelesaian' => $et['waktu_penyelesaian'],
                'tgl_mulai_pekerjaan' => $et['tgl_mulai_pekerjaan'],
                'tgl_selesai_pekerjaan' => $et['tgl_selesai_pekerjaan'],
                'tgl_bap' => $et['tgl_bap'],
                'no_bap' => $et['no_bap'],
                'progres_pekerjaan' => $et['progres_pekerjaan'],
                'tgl_bast' => $et['tgl_bast'],
                'no_bast' => $et['no_bast'],
                'besar_pembayaran' => $et['besar_pembayaran']
            ];
        }

        $model->where('tahun', $year)->delete();
        $batch = [];
        $count = 0;
        foreach ($rawNontender as $item) {
            $kdNontender = trim((string) ($item['kd_nontender'] ?? ($item['kd_pkt_dce'] ?? ($item['kd_paket'] ?? ''))));
            $kdRup = $item['kd_rup'] ?? null;
            $kontrak = $kontrakMap[$kdNontender] ?? ($kontrakMap['rup_' . $kdRup] ?? []);

            $yr = $item['tahun_anggaran'] ?? ($item['tahun'] ?? ($kontrak['tahun_anggaran'] ?? date('Y')));
            if (strlen($yr) > 4 && strpos($yr, '-') !== false)
                $yr = substr($yr, 0, 4);
            if ((int) $yr !== (int) $year)
                continue;

            $preserved = $preservedData[$kdNontender] ?? null;
            $bapHist = (!empty($kontrak['bapbast_history_json']) && is_array($kontrak['bapbast_history_json'])) ? $kontrak['bapbast_history_json'][0] : [];

            $tglBap = $this->_normalizeDate($kontrak['tgl_bap'] ?? ($item['tgl_bap'] ?? ($bapHist['tgl_bap'] ?? ($preserved ? $preserved['tgl_bap'] : null))));
            $noBap = $kontrak['no_bap'] ?? ($item['no_bap'] ?? ($bapHist['no_bap'] ?? ($preserved ? $preserved['no_bap'] : null)));

            $progresPekerjaan = null;
            if (isset($kontrak['progres_pekerjaan']) && $kontrak['progres_pekerjaan'] !== '') {
                $progresPekerjaan = (float) $kontrak['progres_pekerjaan'];
            } elseif (isset($item['progres_pekerjaan']) && $item['progres_pekerjaan'] !== '') {
                $progresPekerjaan = (float) $item['progres_pekerjaan'];
            } elseif (isset($bapHist['progres_pekerjaan']) && $bapHist['progres_pekerjaan'] !== '') {
                $progresPekerjaan = (float) $bapHist['progres_pekerjaan'];
            } elseif ($preserved && $preserved['progres_pekerjaan'] !== null) {
                $progresPekerjaan = (float) $preserved['progres_pekerjaan'];
            }

            $tglBast = $this->_normalizeDate($kontrak['tgl_bast'] ?? ($item['tgl_bast'] ?? ($bapHist['tgl_bast'] ?? ($preserved ? $preserved['tgl_bast'] : null))));
            $noBast = $kontrak['no_bast'] ?? ($item['no_bast'] ?? ($bapHist['no_bast'] ?? ($preserved ? $preserved['no_bast'] : null)));

            $besarPembayaran = 0.0;
            if (isset($kontrak['besar_pembayaran']) && $kontrak['besar_pembayaran'] !== '') {
                $besarPembayaran = (float) $kontrak['besar_pembayaran'];
            } elseif (isset($item['besar_pembayaran']) && $item['besar_pembayaran'] !== '') {
                $besarPembayaran = (float) $item['besar_pembayaran'];
            } elseif (isset($bapHist['besar_pembayaran']) && $bapHist['besar_pembayaran'] !== '') {
                $besarPembayaran = (float) $bapHist['besar_pembayaran'];
            } elseif ($preserved && $preserved['besar_pembayaran'] !== null) {
                $besarPembayaran = (float) $preserved['besar_pembayaran'];
            }

            $batch[] = [
                'kd_rup' => $kdRup,
                'tahun' => (int) $yr,
                'kd_paket' => $kdNontender,
                'nama_paket' => $item['nama_paket'] ?? ($kontrak['nama_paket'] ?? ''),
                'pagu' => (float) ($item['pagu'] ?? ($kontrak['pagu'] ?? 0)),
                'hps' => (float) ($item['hps'] ?? ($kontrak['nilai_hps'] ?? 0)),
                'nilai_kontrak' => (float) ($kontrak['nilai_kontrak'] ?? ($item['nilai_kontrak'] ?? 0)),
                'nilai_negosiasi' => (float) ($item['nilai_negosiasi'] ?? ($kontrak['nilai_negosiasi'] ?? 0)),
                'nilai_pdn_kontrak' => (float) ($kontrak['nilai_pdn_kontrak'] ?? ($item['nilai_pdn_kontrak'] ?? 0)),
                'nilai_umk_kontrak' => (float) ($kontrak['nilai_umk_kontrak'] ?? ($item['nilai_umk_kontrak'] ?? 0)),
                'nama_penyedia' => $item['nama_penyedia'] ?? ($kontrak['nama_penyedia'] ?? ($item['pemenang'] ?? '')),
                'kd_penyedia' => $kontrak['kd_penyedia'] ?? null,
                'npwp_penyedia' => $kontrak['npwp_penyedia'] ?? null,
                'alamat_penyedia' => $kontrak['alamat_penyedia'] ?? null,
                'bentuk_usaha' => $kontrak['bentuk_usaha_penyedia'] ?? null,
                'nama_satker' => $item['nama_satker'] ?? ($kontrak['nama_satker'] ?? ''),
                'kd_satker' => $item['kd_satker'] ?? ($kontrak['kd_satker'] ?? ''),
                'metode' => $item['metode_pengadaan'] ?? ($item['metode'] ?? ($item['mtd_pemilihan'] ?? '')),
                'jenis_pengadaan' => $item['jenis_pengadaan'] ?? ($kontrak['jenis_pengadaan'] ?? ''),
                'tgl_kontrak' => $kontrak['tgl_kontrak'] ?? ($item['tgl_kontrak'] ?? ''),
                'no_kontrak' => $kontrak['no_kontrak'] ?? ($item['no_kontrak'] ?? ''),
                'no_spmk_spp' => $item['no_spmk_spp'] ?? ($kontrak['no_spmk_spp'] ?? ($preserved ? $preserved['no_spmk_spp'] : null)),
                'waktu_penyelesaian' => isset($item['waktu_penyelesaian']) ? (int)$item['waktu_penyelesaian'] : (isset($kontrak['waktu_penyelesaian']) ? (int)$kontrak['waktu_penyelesaian'] : ($preserved ? $preserved['waktu_penyelesaian'] : null)),
                'tgl_mulai_pekerjaan' => $this->_normalizeDate($item['tgl_mulai_pekerjaan'] ?? ($kontrak['tgl_mulai_pekerjaan'] ?? ($preserved ? $preserved['tgl_mulai_pekerjaan'] : null))),
                'tgl_selesai_pekerjaan' => $this->_normalizeDate($item['tgl_selesai_pekerjaan'] ?? ($kontrak['tgl_selesai_pekerjaan'] ?? ($preserved ? $preserved['tgl_selesai_pekerjaan'] : null))),
                'tgl_bap' => $tglBap,
                'no_bap' => $noBap,
                'progres_pekerjaan' => $progresPekerjaan,
                'tgl_bast' => $tglBast,
                'no_bast' => $noBast,
                'besar_pembayaran' => $besarPembayaran,
                'status' => $item['status_nontender'] ?? ($item['status'] ?? ($item['status_nontender_pct_ket'] ?? ($kontrak['status_kontrak'] ?? 'Selesai'))),
                'kualifikasi_paket' => $item['kualifikasi_paket'] ?? ($kontrak['kualifikasi_paket'] ?? ''),
                'kontrak_pembayaran' => $kontrak['jenis_kontrak'] ?? ($item['kontrak_pembayaran'] ?? ''),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if (count($batch) >= 500) {
                $model->insertBatch($batch);
                $count += count($batch);
                $batch = [];
                if ($callback) $callback(" ");
            }
        }
        if (!empty($batch)) {
            $model->insertBatch($batch);
            $count += count($batch);
        }
        return $count;
    }

    public function syncEpurchasing($year, $callback = null)
    {
        $url = $this->baseUrl . '/api/data/paket-epurchasing';
        $model = new EpurchasingModel();
        $db = \Config\Database::connect();

        // Self-healing: pastikan kolom baru tersedia pada tabel epurchasing
        if ($db->tableExists('epurchasing')) {
            $existingCols = $db->getFieldNames('epurchasing');
            $forge = \Config\Database::forge();
            $newCols = [];
            if (!in_array('nama_produk', $existingCols)) {
                $newCols['nama_produk'] = ['type' => 'TEXT', 'null' => true, 'default' => null];
            }
            if (!in_array('products_json', $existingCols)) {
                $newCols['products_json'] = ['type' => 'LONGTEXT', 'null' => true, 'default' => null];
            }
            if (!in_array('count_product', $existingCols)) {
                $newCols['count_product'] = ['type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => 0];
            }
            if (!in_array('mak', $existingCols)) {
                $newCols['mak'] = ['type' => 'TEXT', 'null' => true, 'default' => null];
            } else {
                $db->query("ALTER TABLE epurchasing MODIFY COLUMN mak TEXT NULL DEFAULT NULL");
            }
            if (!in_array('shipping_fee', $existingCols)) {
                $newCols['shipping_fee'] = ['type' => 'DECIMAL', 'constraint' => '20,2', 'null' => true, 'default' => 0];
            }
            if (!in_array('flag_minikom', $existingCols)) {
                $newCols['flag_minikom'] = ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'default' => null];
            }
            if (!in_array('rekan_id', $existingCols)) {
                $newCols['rekan_id'] = ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'default' => null];
            }
            if (!in_array('kode_klpd', $existingCols)) {
                $newCols['kode_klpd'] = ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'default' => null];
            }
            if (!in_array('is_swasta', $existingCols)) {
                $newCols['is_swasta'] = ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'default' => null];
            }
            if (!empty($newCols)) {
                $forge->addColumn('epurchasing', $newCols);
            }
        }

        if ($callback)
            $callback('Mengambil data E-Purchasing...');
        $raw = $this->fetchApi($url);

        // ===== PRE-FETCH vendor names from DB & Inaproc (batch) =====
        $vendorDir = WRITEPATH . 'inaproc/vendors/';
        $inaprocToken = getenv('INAPROC_BEARER_TOKEN') ?: 'inprc73221b186ad14d568b02f22b214c9574';
        if (!is_dir($vendorDir))
            mkdir($vendorDir, 0777, true);

        // Collect all unique kode_penyedia from raw data
        $allKodes = array_values(array_unique(array_filter(array_column($raw, 'kode_penyedia'))));
        $vendorMap = []; // [kode_penyedia] => nama_penyedia

        // Pre-load from inaproc_vendor_sync table if available
        if ($db->tableExists('inaproc_vendor_sync') && !empty($allKodes)) {
            $syncRows = $db->table('inaproc_vendor_sync')
                ->select('kode_penyedia, nama_penyedia')
                ->whereIn('kode_penyedia', $allKodes)
                ->where('nama_penyedia IS NOT NULL')
                ->where('nama_penyedia !=', '')
                ->get()
                ->getResultArray();
            foreach ($syncRows as $sr) {
                $k = trim($sr['kode_penyedia'] ?? '');
                if ($k !== '' && !empty($sr['nama_penyedia'])) {
                    $vendorMap[$k] = trim($sr['nama_penyedia']);
                }
            }
        }

        if ($callback)
            $callback('Mengambil nama penyedia dari Inaproc (' . count($allKodes) . ' vendor)...');

        foreach ($allKodes as $kode) {
            $kode = trim($kode);
            if ($kode === '' || !empty($vendorMap[$kode]))
                continue;

            $file = $vendorDir . $kode . '.json';

            // 1. Check local JSON cache first
            if (file_exists($file)) {
                $saved = json_decode(file_get_contents($file), true);
                if (!empty($saved['nama_penyedia'])) {
                    $vendorMap[$kode] = $saved['nama_penyedia'];
                    continue;
                }
                // If marked not-found within 24h, skip API call
                if (!empty($saved['_not_found'])) {
                    $lastAttempt = strtotime($saved['_last_attempt'] ?? '2000-01-01');
                    if ((time() - $lastAttempt) < 86400) {
                        $vendorMap[$kode] = null; // Known not-found
                        continue;
                    }
                }
            }

            // 2. Fetch from Inaproc API
            try {
                $apiUrl = "https://data.inaproc.id/api/legacy/ekatalog/penyedia-detail?kode_penyedia={$kode}";
                $res = $this->client->get($apiUrl, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $inaprocToken,
                        'User-Agent' => 'Mozilla/5.0 (compatible; SIDP-Sync/1.0)',
                    ],
                    'timeout' => 10,
                ]);
                $body = json_decode($res->getBody(), true);
                // Legacy endpoint returns direct array [{...}]; v1 returns {"data":[{...}]}
                $vendor = $body[0] ?? ($body['data'][0] ?? null);
                $name = is_array($vendor) ? ($vendor['nama_penyedia'] ?? null) : null;

                if ($name) {
                    file_put_contents($file, json_encode(['nama_penyedia' => $name, '_synced_at' => date('Y-m-d H:i:s')]));
                    $vendorMap[$kode] = $name;
                } else {
                    // Save not-found marker
                    file_put_contents($file, json_encode([
                        'nama_penyedia' => null,
                        '_not_found' => true,
                        '_last_attempt' => date('Y-m-d H:i:s')
                    ]));
                    $vendorMap[$kode] = null;
                }
            } catch (\Exception $e) {
                $vendorMap[$kode] = null;
            }

            usleep(200000); // 200ms delay to avoid rate limiting
        }
        // ===== END vendor pre-fetch =====

        // Determine years present in API data; sync all years returned by API (or target year)
        $yearsInApi = [];
        foreach ($raw as $item) {
            $yrVal = $item['fiscal_year'] ?? ($item['tahun_anggaran'] ?? ($item['tahun'] ?? null));
            if (empty($yrVal)) {
                $rawDate = $item['order_date'] ?? '';
                $yrVal = (strlen($rawDate) >= 4) ? substr($rawDate, 0, 4) : null;
            }
            if (!empty($yrVal) && (int) $yrVal > 2000) {
                $yearsInApi[(int) $yrVal] = true;
            }
        }

        if (!empty($yearsInApi)) {
            $model->whereIn('tahun', array_keys($yearsInApi))->delete();
        } elseif ($year) {
            $model->where('tahun', (int) $year)->delete();
        }

        $batch = [];
        $count = 0;
        $allowed = [
            'COMPLETED',
            'ON PROCESS',
            'PAYMENT OUTSIDE SYSTEM',
            'WAITING PPK REVIEW',
            'ON NEGOTIATION',
            'WAITING SELLER CONFIRMATION',
            'ON ADDENDUM'
        ];

        foreach ($raw as $item) {
            $status = strtoupper(str_replace('_', ' ', trim((string) ($item['status'] ?? ''))));
            if (!in_array($status, $allowed))
                continue;

            $yr = $item['fiscal_year'] ?? ($item['tahun_anggaran'] ?? ($item['tahun'] ?? null));
            if (empty($yr)) {
                $rawDate = $item['order_date'] ?? '';
                $yr = (strlen($rawDate) >= 4) ? substr($rawDate, 0, 4) : ($year ?: date('Y'));
            }

            $kode = trim((string) ($item['kode_penyedia'] ?? ''));
            $namaPenyedia = ($kode !== '') ? ($vendorMap[$kode] ?? ($item['nama_penyedia'] ?? null)) : ($item['nama_penyedia'] ?? null);

            // Parse products array from paket-epurchasing API
            $productIds = [];
            $productNames = [];
            $productsArr = (!empty($item['products']) && is_array($item['products'])) ? $item['products'] : [];
            foreach ($productsArr as $prod) {
                if (!empty($prod['product_id'])) {
                    $productIds[] = trim((string) $prod['product_id']);
                }
                if (!empty($prod['product_name'])) {
                    $pName = trim((string) $prod['product_name']);
                    $pQty = isset($prod['qty']) ? (int) $prod['qty'] : 0;
                    $productNames[] = ($pQty > 0 && count($productsArr) > 1) ? "{$pName} ({$pQty})" : $pName;
                }
            }
            $productIdStr = !empty($productIds) ? implode(',', array_unique($productIds)) : trim((string) ($item['product_id'] ?? ''));
            $namaProdukStr = !empty($productNames) ? implode(', ', $productNames) : trim((string) ($item['nama_produk'] ?? ''));
            $productsJson = !empty($productsArr) ? json_encode($productsArr, JSON_UNESCAPED_UNICODE) : null;
            $cleanRupDesc = rtrim(trim((string) ($item['rup_desc'] ?? '')), ',');

            $batch[] = [
                'kd_paket' => $item['order_id'] ?? ($item['kd_paket'] ?? null),
                'nama_paket' => $item['rup_name'] ?? ($item['nama_paket'] ?? ''),
                'pagu' => (float) ($item['total'] ?? ($item['pagu'] ?? 0)),
                'total_harga' => (float) ($item['total_harga'] ?? ($item['total'] ?? 0)),
                'nama_satker' => $item['nama_satker'] ?? '',
                'kd_satker' => $item['kode_satker'] ?? ($item['kd_satker'] ?? ''),
                'tahun' => (int) $yr,
                'sumber_dana' => $item['funding_source'] ?? ($item['sumber_dana'] ?? ''),
                'status' => $status,
                'order_date' => $item['order_date'] ?? '',
                'kode_penyedia' => $kode,
                'nama_penyedia' => $namaPenyedia,
                'product_id' => $productIdStr,
                'nama_produk' => $namaProdukStr,
                'products_json' => $productsJson,
                'count_product' => (int) ($item['count_product'] ?? count($productsArr)),
                'rup_desc' => $cleanRupDesc,
                'total_qty' => (int) ($item['total_qty'] ?? ($item['count_product'] ?? 0)),
                'shipment_status' => $item['shipment_status'] ?? '',
                'rup_code' => $item['rup_code'] ?? '',
                'mak' => $item['mak'] ?? '',
                'shipping_fee' => (float) ($item['shipping_fee'] ?? 0),
                'flag_minikom' => $item['flag_minikom'] ?? '',
                'rekan_id' => isset($item['rekan_id']) ? (string) $item['rekan_id'] : null,
                'kode_klpd' => $item['kode_klpd'] ?? '',
                'is_swasta' => isset($item['is_swasta']) ? (string) $item['is_swasta'] : '',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            if (count($batch) >= 500) {
                $model->insertBatch($batch);
                $count += count($batch);
                $batch = [];
                if ($callback) $callback(" ");
            }
        }
        if (!empty($batch)) {
            $model->insertBatch($batch);
            $count += count($batch);
        }
        return $count;
    }

    public function syncSikapCsvVendorDetails($callback = null): array
    {
        $csvPath = ROOTPATH . 'Hasil_Bot_SIKaP.csv';
        if (!is_file($csvPath)) {
            $csvPath = FCPATH . 'Hasil_Bot_SIKaP.csv';
        }
        if (!is_file($csvPath)) {
            if ($callback) $callback('File Hasil_Bot_SIKaP.csv tidak ditemukan di direktori proyek.');
            return ['updated' => 0, 'inserted' => 0, 'enriched_transactions' => 0, 'total' => 0];
        }

        if ($callback) $callback('Membaca data referensi dari Hasil_Bot_SIKaP.csv...');

        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            return ['updated' => 0, 'inserted' => 0, 'enriched_transactions' => 0, 'total' => 0];
        }

        $header = fgetcsv($handle, 0, ';');
        if (!$header) {
            fclose($handle);
            return ['updated' => 0, 'inserted' => 0, 'enriched_transactions' => 0, 'total' => 0];
        }

        // Clean UTF-8 BOM from header keys
        $cleanHeaders = [];
        foreach ($header as $idx => $col) {
            $col = trim($col);
            $col = preg_replace('/^\xEF\xBB\xBF/', '', $col);
            $cleanHeaders[strtolower($col)] = $idx;
        }

        $db = \Config\Database::connect();
        $updated = 0;
        $inserted = 0;
        $enrichedTx = 0;
        $processed = 0;

        // Normalization helper
        $norm = function($str) {
            $s = strtoupper(trim($str));
            $s = preg_replace('/^(PT|CV|UD|PO|KOPERASI|TOKO)\.?\s+/i', '', $s);
            $s = preg_replace('/\s+(PT|CV|UD|PO|TBK|PERSERO)\.?$/i', '', $s);
            $s = preg_replace('/[^A-Z0-9]/', '', $s);
            return $s;
        };

        // Cache existing inaproc_vendor_sync records for fast in-memory matching
        $existingRows = $db->table('inaproc_vendor_sync')
            ->select('id, kode_penyedia, nama_penyedia, npwp_penyedia, alamat_penyedia, bentuk_usaha, email, telepon')
            ->get()
            ->getResultArray();

        $byExactName = [];
        $byNormName = [];
        $byCleanNpwp = [];
        $byKodePenyedia = [];

        foreach ($existingRows as $row) {
            $codeKey = trim($row['kode_penyedia'] ?? '');
            if ($codeKey !== '') {
                $byKodePenyedia[$codeKey] = $row;
            }
            $nameKey = strtoupper(trim($row['nama_penyedia'] ?? ''));
            if ($nameKey !== '') {
                $byExactName[$nameKey] = $row;
                $nKey = $norm($nameKey);
                if ($nKey !== '') {
                    $byNormName[$nKey] = $row;
                }
            }
            $cleanNpwp = preg_replace('/[^0-9]/', '', $row['npwp_penyedia'] ?? '');
            if (strlen($cleanNpwp) >= 9) {
                $byCleanNpwp[$cleanNpwp] = $row;
            }
        }

        $now = date('Y-m-d H:i:s');

        while (($data = fgetcsv($handle, 0, ';')) !== false) {
            if (count($data) < 3) continue;

            $getVal = function($colName) use ($cleanHeaders, $data) {
                $c = strtolower($colName);
                if (isset($cleanHeaders[$c]) && isset($data[$cleanHeaders[$c]])) {
                    return trim($data[$cleanHeaders[$c]]);
                }
                return '';
            };

            $npwp = $getVal('npwp');
            $nama = $getVal('nama');
            if ($nama === '') {
                $nama = $getVal('nama_penyedia');
            }
            if ($nama === '') {
                $nama = $getVal('nama perusahaan / kontraktor') ?: $getVal('nama badan usaha') ?: $getVal('nama perusahaan');
            }
            if ($nama === '' && isset($data[1]) && !is_numeric($data[1])) {
                $nama = trim($data[1]);
            }
            if ($nama === '' && $npwp === '') {
                continue;
            }

            $alamat = $getVal('alamat');
            $bentukUsaha = $getVal('bentuk_usaha');
            if ($bentukUsaha === '') $bentukUsaha = $getVal('bentuk usaha');
            $email = $getVal('email');
            $telepon = $getVal('telepon');
            if ($telepon === '' || $telepon === '-') {
                $telepon = $getVal('telepon_seluler');
            }
            if ($telepon === '' || $telepon === '-') {
                $telepon = $getVal('no telepon');
            }
            $kabKota = $getVal('kabupaten_kota') ?: ($getVal('kabupaten/kota') ?: ($getVal('kabupaten / kota') ?: ($data[3] ?? '')));
            $provinsi = $getVal('provinsi') ?: ($data[4] ?? 'Nusa Tenggara Barat');
            if ($alamat === '' && $kabKota !== '') {
                $alamat = $kabKota . ($provinsi !== '' ? ', ' . $provinsi : '');
            }
            $kodePos = $getVal('kode_pos');
            if ($kodePos === '') $kodePos = $getVal('kodepos');
            $userId = $getVal('user_id');
            if ($userId === '') $userId = $getVal('user id') ?: ($data[0] ?? '');
            $status = $getVal('status');
            if ($status === '') $status = $getVal('status verifikasi');

            // Format full address if kabupaten/provinsi/kode pos are present and not already in alamat
            $fullAlamat = $alamat;
            if ($fullAlamat !== '') {
                $extraParts = [];
                if ($kabKota !== '' && stripos($fullAlamat, $kabKota) === false) {
                    $extraParts[] = $kabKota;
                }
                if ($provinsi !== '' && stripos($fullAlamat, $provinsi) === false) {
                    $extraParts[] = $provinsi;
                }
                if ($kodePos !== '' && stripos($fullAlamat, $kodePos) === false) {
                    $extraParts[] = 'Kode Pos: ' . $kodePos;
                }
                if (!empty($extraParts)) {
                    $fullAlamat .= ', ' . implode(', ', $extraParts);
                }
            }

            $cleanNpwp = preg_replace('/[^0-9]/', '', $npwp);
            $namaUpper = strtoupper($nama);
            $namaNorm = $norm($nama);

            $vendorCode = $userId !== '' ? 'SIKAP_' . preg_replace('/[^A-Za-z0-9_]/', '', $userId) : 'SIKAP_' . strtoupper(substr(md5($nama . $npwp), 0, 10));

            // Find matching existing record in inaproc_vendor_sync
            $matched = null;
            if ($cleanNpwp !== '' && isset($byCleanNpwp[$cleanNpwp])) {
                $matched = $byCleanNpwp[$cleanNpwp];
            } elseif ($namaUpper !== '' && isset($byExactName[$namaUpper])) {
                $matched = $byExactName[$namaUpper];
            } elseif ($namaNorm !== '' && isset($byNormName[$namaNorm])) {
                $matched = $byNormName[$namaNorm];
            } elseif ($vendorCode !== '' && isset($byKodePenyedia[$vendorCode])) {
                $matched = $byKodePenyedia[$vendorCode];
            }

            helper('ajip');
            $locInfo = classify_npwp_location($npwp, $fullAlamat, $provinsi);
            $kppCode = $locInfo['kpp_code'] ?? null;
            $lokasiPerusahaan = $locInfo['lokasi'] ?? null;
            $flagLokasi = $locInfo['flag'] ?? null;

            if ($matched) {
                // Update existing record
                $updateData = [
                    'kpp_code' => $kppCode,
                    'lokasi_perusahaan' => $lokasiPerusahaan,
                    'flag_lokasi' => $flagLokasi,
                    'last_sync_status' => 'sikap_sync',
                    'last_sync_at' => $now,
                    'updated_at' => $now
                ];
                // Overwrite address if SIKaP has full address (especially if current address is empty or has asterisks)
                if ($fullAlamat !== '') {
                    $currAlamat = $matched['alamat_penyedia'] ?? '';
                    if ($currAlamat === '' || strpos($currAlamat, '*****') !== false || strlen($fullAlamat) > strlen($currAlamat)) {
                        $updateData['alamat_penyedia'] = $fullAlamat;
                        $matched['alamat_penyedia'] = $fullAlamat;
                    }
                }
                if ($npwp !== '' && (empty($matched['npwp_penyedia']) || strlen($npwp) > strlen($matched['npwp_penyedia']))) {
                    $updateData['npwp_penyedia'] = $npwp;
                    $matched['npwp_penyedia'] = $npwp;
                }
                if ($bentukUsaha !== '' && empty($matched['bentuk_usaha'])) {
                    $updateData['bentuk_usaha'] = $bentukUsaha;
                    $matched['bentuk_usaha'] = $bentukUsaha;
                }
                if ($email !== '' && $email !== '-' && empty($matched['email'])) {
                    $updateData['email'] = $email;
                    $matched['email'] = $email;
                }
                if ($telepon !== '' && $telepon !== '-' && empty($matched['telepon'])) {
                    $updateData['telepon'] = $telepon;
                    $matched['telepon'] = $telepon;
                }
                if ($status !== '' && empty($matched['status_aktif'])) {
                    $updateData['status_aktif'] = $status;
                    $matched['status_aktif'] = $status;
                }

                $db->table('inaproc_vendor_sync')
                    ->where('id', $matched['id'])
                    ->update($updateData);

                // Update in-memory caches
                if ($cleanNpwp !== '') $byCleanNpwp[$cleanNpwp] = $matched;
                if ($namaUpper !== '') $byExactName[$namaUpper] = $matched;
                if ($namaNorm !== '') $byNormName[$namaNorm] = $matched;

                $updated++;
            } else {
                // Ensure vendorCode is unique
                if (isset($byKodePenyedia[$vendorCode])) {
                    $vendorCode .= '_' . substr(md5($nama . $npwp . microtime()), 0, 4);
                }

                $insertData = [
                    'kode_penyedia' => $vendorCode,
                    'nama_penyedia' => $nama,
                    'bentuk_usaha' => $bentukUsaha ?: null,
                    'jenis_perusahaan' => $bentukUsaha ?: null,
                    'npwp_penyedia' => $npwp ?: null,
                    'kpp_code' => $kppCode,
                    'lokasi_perusahaan' => $lokasiPerusahaan,
                    'flag_lokasi' => $flagLokasi,
                    'alamat_penyedia' => $fullAlamat ?: null,
                    'email' => ($email !== '' && $email !== '-') ? $email : null,
                    'telepon' => ($telepon !== '' && $telepon !== '-') ? $telepon : null,
                    'status_aktif' => $status ?: 'Aktif',
                    'last_sync_status' => 'sikap_csv',
                    'last_sync_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now
                ];

                $db->table('inaproc_vendor_sync')->insert($insertData);
                $newId = $db->insertID();
                $insertData['id'] = $newId;

                // Register into in-memory caches
                $byKodePenyedia[$vendorCode] = $insertData;
                if ($cleanNpwp !== '') $byCleanNpwp[$cleanNpwp] = $insertData;
                if ($namaUpper !== '') $byExactName[$namaUpper] = $insertData;
                if ($namaNorm !== '') $byNormName[$namaNorm] = $insertData;

                $inserted++;
            }

            // Enrich transaction tables: lpse_tender & lpse_nontender
            if ($nama !== '' && ($fullAlamat !== '' || $npwp !== '')) {
                // LPSE Tender
                $builderT = $db->table('lpse_tender')
                    ->where('UPPER(TRIM(nama_penyedia))', $namaUpper)
                    ->where('(alamat_penyedia IS NULL OR alamat_penyedia = "" OR alamat_penyedia LIKE "%*****%" OR npwp_penyedia IS NULL OR npwp_penyedia = "")');
                
                $txUpdate = [];
                if ($fullAlamat !== '') $txUpdate['alamat_penyedia'] = $fullAlamat;
                if ($npwp !== '') $txUpdate['npwp_penyedia'] = $npwp;
                if ($bentukUsaha !== '') $txUpdate['bentuk_usaha'] = $bentukUsaha;

                if (!empty($txUpdate)) {
                    $builderT->update($txUpdate);
                    $affected = $db->affectedRows();
                    if ($affected > 0) $enrichedTx += $affected;
                }

                // LPSE Non-Tender
                $builderNT = $db->table('lpse_nontender')
                    ->where('UPPER(TRIM(nama_penyedia))', $namaUpper)
                    ->where('(alamat_penyedia IS NULL OR alamat_penyedia = "" OR alamat_penyedia LIKE "%*****%" OR npwp_penyedia IS NULL OR npwp_penyedia = "")');
                
                if (!empty($txUpdate)) {
                    $builderNT->update($txUpdate);
                    $affectedNT = $db->affectedRows();
                    if ($affectedNT > 0) $enrichedTx += $affectedNT;
                }
            }

            $processed++;
            if ($callback && $processed % 50 === 0) {
                $callback("Memproses referensi SIKaP: $processed vendor...");
            }
        }

        fclose($handle);

        if ($callback) {
            $callback("Selesai referensi SIKaP: $updated diperbarui, $inserted baru, $enrichedTx transaksi diperkaya.");
        }

        return [
            'updated' => $updated,
            'inserted' => $inserted,
            'enriched_transactions' => $enrichedTx,
            'total' => $updated + $inserted
        ];
    }

    public function syncVendorDetails($year, $callback = null)
    {
        // 1. Prioritaskan dan integrasikan data referensi bot SIKaP CSV
        if ($callback) {
            $callback('Memulai sinkronisasi data referensi dari bot SIKaP (CSV)...');
        }
        $sikapResult = $this->syncSikapCsvVendorDetails($callback);
        $totalCount = $sikapResult['total'] ?? 0;

        // 2. Sinkronisasi API Inaproc LKPP untuk melengkapi vendor E-Purchasing yang belum lengkap
        $db = \Config\Database::connect();
        $rows = $db->table('epurchasing')
            ->distinct()
            ->select('kode_penyedia')
            ->where('kode_penyedia IS NOT NULL')
            ->where('kode_penyedia !=', '')
            ->get()
            ->getResultArray();

        $service = new InaprocApiService();

        foreach ($rows as $row) {
            $kode = trim($row['kode_penyedia'] ?? '');
            if ($kode === '') {
                continue;
            }

            // Jika vendor sudah memiliki alamat lengkap dari SIKaP (tanpa masking *****), lewati API call agar hemat waktu
            $hasUnmasked = $db->table('inaproc_vendor_sync')
                ->where('kode_penyedia', $kode)
                ->where('alamat_penyedia IS NOT NULL')
                ->where('alamat_penyedia !=', '')
                ->where('alamat_penyedia NOT LIKE', '%*****%')
                ->countAllResults();

            if ($hasUnmasked > 0) {
                continue;
            }

            if ($callback) {
                $callback('Sinkronisasi Inaproc API: ' . $kode);
            }

            $result = $service->syncVendorDetail($kode, true);
            if (!empty($result['ok'])) {
                $totalCount++;
            }

            usleep(50000); // 50ms
        }

        return $totalCount;
    }

    public function syncMasterAnggaran($year, $callback = null)
    {

        $urlKegiatan = $this->baseUrl . '/api/data/kegiatan-master';
        $urlSatker = $this->baseUrl . '/api/data/master-satker';
        $model = new MasterAnggaranModel();

        if ($callback)
            $callback('Mengambil Master Satker & Kegiatan (Paralel)...');
        $apiResults = $this->fetchApisConcurrent([
            'satker' => $urlSatker,
            'kegiatan' => $urlKegiatan
        ]);

        $rawSatker = $apiResults['satker'];
        $raw = $apiResults['kegiatan'];

        $satkerMap = [];
        foreach ($rawSatker as $s) {
            $kd = $s['kd_satker'] ?? null;
            if ($kd)
                $satkerMap[$kd] = $s['nama_satker'] ?? '';
        }

        // Preserve manually edited items before wipe
        $preservedEdits = [];
        $existingEdits = $model->where('tahun', $year)
            ->where('is_edited', 1)
            ->findAll();
        
        foreach ($existingEdits as $ee) {
            $key = $ee['kd_satker'] . '_' . trim($ee['kd_program_str']);
            $preservedEdits[$key] = $ee;
        }

        $model->where('tahun', $year)->delete();
        $batch = [];
        $count = 0;
        foreach ($raw as $item) {
            $yr = $item['tahun_anggaran'] ?? ($item['tahun'] ?? date('Y'));
            if ((int) $yr !== (int) $year)
                continue;

            $kdSatker = $item['kd_satker'] ?? '';
            $kdProg = trim($item['kd_program_str'] ?? '');
            $key = $kdSatker . '_' . $kdProg;
            $origPagu = (float) ($item['pagu_program'] ?? 0);

            $paguProgram = $origPagu;
            $paguAwal = $origPagu;
            $isEdited = 0;
            $jenisPerubahan = null;
            $keteranganPerubahan = null;

            if (isset($preservedEdits[$key])) {
                $paguProgram = (float) $preservedEdits[$key]['pagu_program'];
                $paguAwal = (float) ($preservedEdits[$key]['pagu_awal'] ?? $origPagu);
                $isEdited = 1;
                $jenisPerubahan = $preservedEdits[$key]['jenis_perubahan'] ?? null;
                $keteranganPerubahan = $preservedEdits[$key]['keterangan_perubahan'] ?? null;
            }

            $batch[] = [
                'kd_satker' => $kdSatker,
                'nama_satker' => $satkerMap[$kdSatker] ?? ($item['nama_satker'] ?? ''),
                'kd_program_str' => $kdProg,
                'nama_program' => $item['nama_program'] ?? '',
                'pagu_program' => $paguProgram,
                'pagu_awal' => $paguAwal,
                'is_edited' => $isEdited,
                'jenis_perubahan' => $jenisPerubahan,
                'keterangan_perubahan' => $keteranganPerubahan,
                'tahun' => (int) $yr,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            if (count($batch) >= 500) {
                $model->insertBatch($batch);
                $count += count($batch);
                $batch = [];
                if ($callback) $callback(" ");
            }
        }
        if (!empty($batch)) {
            $model->insertBatch($batch);
            $count += count($batch);
        }
        return $count;
    }

    public function syncPencatatanNontender($year, $callback = null)
    {
        $urlReal = $this->baseUrl . '/api/data/pencatatan-nontender-realisasi';
        $urlPenyedia = $this->baseUrl . '/api/data/penyedia_pct_nontender';
        $model = new LpsePencatatanNontenderModel();

        if ($callback)
            $callback('Mengambil data Pencatatan Non-Tender Realisasi & Penyedia (Paralel)...');
        $apiResults = $this->fetchApisConcurrent([
            'real' => $urlReal,
            'penyedia' => $urlPenyedia
        ]);

        $raw = $apiResults['real'];
        $rawPenyedia = $apiResults['penyedia'];

        $penyediaMap = [];
        foreach ($rawPenyedia as $p) {
            $kd = trim((string) ($p['kd_nontender_pct'] ?? ''));
            if ($kd !== '') {
                $penyediaMap[$kd] = $p;
            }
        }

        // Build RUP Year Map from local DB as fallback
        $rupIds = array_filter(array_unique(array_map(function ($i) {
            return trim((string) ($i['kd_rup_paket'] ?? ($i['kd_rup'] ?? '')));
        }, $raw)));
        $rupYearMap = [];
        $db = \Config\Database::connect();

        // Self-healing: pastikan kolom baru tersedia (mencegah error Unknown Column jika migrasi belum jalan di server)
        if ($db->tableExists('lpse_pencatatan_nontender')) {
            $existingCols = $db->getFieldNames('lpse_pencatatan_nontender');
            $forge = \Config\Database::forge();
            $newCols = [];
            if (!in_array('kategori_pengadaan', $existingCols)) {
                $newCols['kategori_pengadaan'] = ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'default' => null];
            }
            if (!in_array('mtd_pemilihan', $existingCols)) {
                $newCols['mtd_pemilihan'] = ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'default' => null];
            }
            if (!in_array('sumber_dana', $existingCols)) {
                $newCols['sumber_dana'] = ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'default' => null];
            }
            if (!in_array('tgl_buat_paket', $existingCols)) {
                $newCols['tgl_buat_paket'] = ['type' => 'DATETIME', 'null' => true, 'default' => null];
            }
            if (!in_array('tgl_selesai_paket', $existingCols)) {
                $newCols['tgl_selesai_paket'] = ['type' => 'DATETIME', 'null' => true, 'default' => null];
            }
            if (!empty($newCols)) {
                $forge->addColumn('lpse_pencatatan_nontender', $newCols);
            }
        }

        if (!empty($rupIds)) {
            $chunks = array_chunk($rupIds, 1000);
            foreach ($chunks as $chunk) {
                $res = $db->table('rup_penyedia')->select('kd_rup, tahun')->whereIn('kd_rup', $chunk)->get()->getResultArray();
                foreach ($res as $r) {
                    $rupYearMap[$r['kd_rup']] = $r['tahun'];
                }

                $missingIds = array_diff($chunk, array_keys($rupYearMap));
                if (!empty($missingIds)) {
                    $res = $db->table('rup_swakelola')->select('kd_rup, tahun')->whereIn('kd_rup', $missingIds)->get()->getResultArray();
                    foreach ($res as $r) {
                        $rupYearMap[$r['kd_rup']] = $r['tahun'];
                    }
                }
            }
        }

        $model->where('tahun', $year)->delete();
        $batch = [];
        $count = 0;
        foreach ($raw as $item) {
            $rup = trim((string) ($item['kd_rup_paket'] ?? ($item['kd_rup'] ?? '')));
            $yr = $item['tahun_anggaran'] ?? ($item['tahun'] ?? ($item['tahun_anggaran_paket'] ?? ($item['tahun_paket'] ?? null)));

            if (empty($yr) && !empty($rup) && isset($rupYearMap[$rup])) {
                $yr = $rupYearMap[$rup];
            }
            if (empty($yr))
                $yr = date('Y');
            if ((int) $yr !== (int) $year)
                continue;

            $status = $item['status_nontender_pct_ket'] ?? ($item['status_nontender_pct'] ?? ($item['status'] ?? 'Selesai'));
            $statusLower = strtolower(trim($status));

            $isAllowed = false;
            foreach (['berjalan', 'selesai', 'tayang'] as $allowed) {
                if (strpos($statusLower, $allowed) !== false) {
                    $isAllowed = true;
                    break;
                }
            }

            if (!$isAllowed) {
                continue;
            }

            $kdPaket = $item['kd_nontender_pct'] ?? ($item['kd_pkt_dce'] ?? null);
            $supp = $kdPaket ? ($penyediaMap[$kdPaket] ?? []) : [];

            $batch[] = [
                'kd_rup' => $rup,
                'tahun' => (int) $yr,
                'kd_paket' => $kdPaket,
                'nama_paket' => $item['nama_paket'] ?? '',
                'nama_satker' => $item['nama_satker'] ?? '',
                'kd_satker' => $item['kd_satker'] ?? '',
                'pagu' => (float) ($item['pagu'] ?? 0),
                'realisasi' => (float) ($item['total_realisasi'] ?? ($item['nilai_realisasi'] ?? 0)),
                'nama_penyedia' => $supp['nama_penyedia'] ?? ($item['nama_penyedia'] ?? ''),
                'kd_penyedia' => $supp['kode_penyedia'] ?? null,
                'npwp_penyedia' => $supp['npwp_penyedia'] ?? null,
                'jenis_realisasi' => $supp['jenis_realisasi'] ?? ($item['jenis_realisasi'] ?? ''),
                'kategori_pengadaan' => $item['kategori_pengadaan'] ?? ($item['kgr_nama'] ?? ($item['jenis_pengadaan'] ?? ($supp['kategori_pengadaan'] ?? ($supp['kgr_nama'] ?? null)))),
                'mtd_pemilihan' => $item['mtd_pemilihan'] ?? ($item['metode_pemilihan'] ?? ($item['metode_pengadaan'] ?? ($item['metode'] ?? ($supp['mtd_pemilihan'] ?? null)))),
                'sumber_dana' => $item['sumber_dana'] ?? ($item['sumber_dana_paket'] ?? ($item['sumberdana'] ?? ($supp['sumber_dana'] ?? null))),
                'tgl_buat_paket' => !empty($item['tgl_buat_paket']) ? $item['tgl_buat_paket'] : (!empty($item['tgl_buat']) ? $item['tgl_buat'] : (!empty($supp['tgl_buat_paket']) ? $supp['tgl_buat_paket'] : null)),
                'tgl_selesai_paket' => !empty($item['tgl_selesai_paket']) ? $item['tgl_selesai_paket'] : (!empty($item['tgl_selesai']) ? $item['tgl_selesai'] : (!empty($supp['tgl_selesai_paket']) ? $supp['tgl_selesai_paket'] : null)),
                'no_pembayaran' => $supp['no_realisasi'] ?? ($item['no_realisasi'] ?? ''),
                'jenis_pembayaran' => $item['bukti_pembayaran'] ?? null,
                'ket_realisasi' => $supp['ket_realisasi'] ?? null,
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            if (count($batch) >= 500) {
                $model->insertBatch($batch);
                $count += count($batch);
                $batch = [];
                if ($callback) $callback(" ");
            }
        }
        if (!empty($batch)) {
            $model->insertBatch($batch);
            $count += count($batch);
        }
        return $count;
    }

    public function syncPencatatanSwakelola($year, $callback = null)
    {
        $urlList = $this->baseUrl . '/api/data/list-pencatatan-swakelola';
        $urlReal = $this->baseUrl . '/api/data/encatatan-swakelola-realisasi';
        $urlSatker = $this->baseUrl . '/api/data/master-satker';
        $model = new LpsePencatatanSwakelolaModel();

        if ($callback)
            $callback('Mengambil Daftar, Realisasi & Master Satker (Paralel)...');
        $apiResults = $this->fetchApisConcurrent([
            'satker' => $urlSatker,
            'list' => $urlList,
            'real' => $urlReal
        ]);

        $rawSatker = $apiResults['satker'];
        $rawList = $apiResults['list'];
        $rawReal = $apiResults['real'];

        $satkerMap = [];
        foreach ($rawSatker as $s) {
            $kd = $s['kd_satker'] ?? null;
            if ($kd)
                $satkerMap[$kd] = $s['nama_satker'] ?? '';
        }

        $realMap = [];
        foreach ($rawReal as $r) {
            $kd = trim((string) ($r['kd_swakelola_pct'] ?? ($r['kd_swakelola'] ?? ($r['kd_paket'] ?? ''))));
            $rup = trim((string) ($r['kd_rup_paket'] ?? ($r['kd_rup'] ?? '')));

            if ($kd !== '') {
                if (!isset($realMap[$kd]))
                    $realMap[$kd] = ['val' => 0, 'items' => []];
                $realMap[$kd]['val'] += (float) ($r['nilai_realisasi'] ?? 0);
                $realMap[$kd]['items'][] = $r;
            }
            if ($rup !== '') {
                if (!isset($realMap['rup_' . $rup]))
                    $realMap['rup_' . $rup] = ['val' => 0, 'items' => []];
                $realMap['rup_' . $rup]['val'] += (float) ($r['nilai_realisasi'] ?? 0);
                $realMap['rup_' . $rup]['items'][] = $r;
            }
        }

        $model->where('tahun', $year)->delete();
        $batch = [];
        $count = 0;

        // Build RUP Year Map from local DB as fallback
        $rupIds = array_filter(array_unique(array_map(function ($i) {
            return trim((string) ($i['kd_rup'] ?? ''));
        }, $rawList)));
        $rupIds = array_unique(array_merge($rupIds, array_filter(array_map(function ($r) {
            return trim((string) ($r['kd_rup_paket'] ?? ($r['kd_rup'] ?? '')));
        }, $rawReal))));
        $rupYearMap = [];
        if (!empty($rupIds)) {
            $db = \Config\Database::connect();
            $chunks = array_chunk($rupIds, 1000);
            foreach ($chunks as $chunk) {
                $res = $db->table('rup_swakelola')->select('kd_rup, tahun')->whereIn('kd_rup', $chunk)->get()->getResultArray();
                foreach ($res as $r) {
                    $rupYearMap[$r['kd_rup']] = $r['tahun'];
                }

                $missingIds = array_diff($chunk, array_keys($rupYearMap));
                if (!empty($missingIds)) {
                    $res = $db->table('rup_penyedia')->select('kd_rup, tahun')->whereIn('kd_rup', $missingIds)->get()->getResultArray();
                    foreach ($res as $r) {
                        $rupYearMap[$r['kd_rup']] = $r['tahun'];
                    }
                }
            }
        }

        foreach ($rawList as $item) {
            $kd = trim((string) ($item['kd_swakelola_pct'] ?? ''));
            $rup = trim((string) ($item['kd_rup'] ?? ''));

            $real = $realMap[$kd] ?? ($realMap['rup_' . $rup] ?? ['val' => 0, 'items' => []]);
            $yr = $item['tahun_anggaran'] ?? ($item['tahun'] ?? ($item['tahun_anggaran_paket'] ?? ($item['tahun_paket'] ?? null)));

            // Get best info from real items if available
            $bestReal = !empty($real['items']) ? $real['items'][0] : [];
            if (empty($yr) && !empty($bestReal)) {
                $yr = $bestReal['tahun_anggaran'] ?? ($bestReal['tahun'] ?? ($bestReal['tahun_anggaran_paket'] ?? ($bestReal['tahun_paket'] ?? null)));
            }
            if (empty($yr) && !empty($rup) && isset($rupYearMap[$rup])) {
                $yr = $rupYearMap[$rup];
            }
            if (empty($yr))
                $yr = date('Y');
            if ((int) $yr !== (int) $year)
                continue;

            $kdSatker = $item['kd_satker'] ?? ($bestReal['kd_satker'] ?? '');

            $batch[] = [
                'kd_rup' => $rup,
                'tahun' => (int) $yr,
                'kd_paket' => $kd,
                'nama_paket' => $item['nama_paket'] ?? '',
                'nama_satker' => $item['nama_satker'] ?? ($satkerMap[$kdSatker] ?? ($bestReal['nama_satker'] ?? '')),
                'kd_satker' => $kdSatker,
                'pagu' => (float) ($item['pagu'] ?? 0),
                'realisasi' => (float) $real['val'],
                'jenis_realisasi' => $item['metode'] ?? ($bestReal['jenis_realisasi'] ?? ''),
                'no_pembayaran' => $bestReal['no_realisasi'] ?? '',
                'ket_realisasi' => $item['ket_realisasi'] ?? ($bestReal['ket_realisasi'] ?? ($item['keterangan'] ?? '')),
                'nama_pelaksana' => $item['nama_pelaksana'] ?? ($bestReal['nama_pelaksana'] ?? '-'),
                'status' => $item['status'] ?? 'Selesai',
                'alasan_pembatalan' => $item['alasan_pembatalan'] ?? null,
                'status_swakelola_pct_ket' => $item['status_swakelola_pct_ket'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            if (count($batch) >= 500) {
                $model->insertBatch($batch);
                $count += count($batch);
                $batch = [];
                if ($callback) $callback(" ");
            }
        }
        if (!empty($batch)) {
            $model->insertBatch($batch);
            $count += count($batch);
        }
        return $count;
    }

    private function _normalizeDate($dateStr)
    {
        if (empty($dateStr)) {
            return null;
        }
        $timestamp = strtotime((string) $dateStr);
        if ($timestamp === false || $timestamp <= 0) {
            return null;
        }
        return date('Y-m-d', $timestamp);
    }

    /**
     * Sinkronisasi & Agregasi Tabel Progres SPSE (Monev OPD)
     * Mengkalkulasi data langsung dari tabel-tabel transaksi lokal (rup_penyedia, rup_swakelola, lpse_tender, dll)
     * tanpa memerlukan seeder manual.
     */
    public function syncMonevProgres($currentYear, $callback = null)
    {
        $db = \Config\Database::connect();

        // Self-healing: pastikan tabel monev_progres_spse tersedia
        if (!$db->tableExists('monev_progres_spse')) {
            try {
                $migration = new \App\Database\Migrations\CreateMonevProgresSpseTable();
                $migration->up();
            } catch (\Throwable $mEx) {
                log_message('error', 'Auto-create monev_progres_spse failed: ' . $mEx->getMessage());
            }
        }

        $builder = $db->table('monev_progres_spse');

        $dataFile = APPPATH . 'Database/MonevProgresData.php';
        $referenceItems = file_exists($dataFile) ? (require $dataFile) : [];

        $refMap = [];
        foreach ($referenceItems as $item) {
            $satkerKey = strtolower(trim($item['satker']));
            $refMap[$satkerKey] = $item;
        }

        $satkers = [];
        if (!empty($referenceItems)) {
            $satkers = $referenceItems;
        } else {
            $opdRows = $db->table('rup_penyedia')
                ->select('nama_satker')
                ->where('tahun', $currentYear)
                ->groupBy('nama_satker')
                ->get()->getResultArray();
            $no = 1;
            foreach ($opdRows as $r) {
                if (empty(trim($r['nama_satker'] ?? ''))) continue;
                $satkers[] = [
                    'no' => $no++,
                    'satker' => trim($r['nama_satker']),
                    'kategori' => 'Dinas',
                ];
            }
        }

        $now = date('Y-m-d H:i:s');
        $months = [
            'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
            'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
            'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
            'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
        ];
        $formattedDateNow = strtr(date('d F Y'), $months);

        $updatedCount = 0;

        foreach ($satkers as $satkerData) {
            $satkerName = $satkerData['satker'];
            $no = (int) ($satkerData['no'] ?? 0);
            $kategori = $satkerData['kategori'] ?? 'Dinas';

            // Query agregasi dari tabel-tabel lokal
            $rupPQuery = $db->table('rup_penyedia')
                ->where('tahun', $currentYear)
                ->like('nama_satker', $satkerName);

            $countRupP = (clone $rupPQuery)->countAllResults();

            $countPL = (clone $rupPQuery)
                ->like('metode_pengadaan', 'Pengadaan Langsung')
                ->countAllResults();

            $countJuksung = (clone $rupPQuery)
                ->like('metode_pengadaan', 'Penunjukan Langsung')
                ->countAllResults();

            $countEpurchasing = (clone $rupPQuery)
                ->like('metode_pengadaan', 'E-Purchasing')
                ->countAllResults();

            $countDikecualikan = (clone $rupPQuery)
                ->like('metode_pengadaan', 'Dikecualikan')
                ->countAllResults();

            $countTenderRup = (clone $rupPQuery)
                ->like('metode_pengadaan', 'Tender')
                ->countAllResults();

            $countRupS = $db->table('rup_swakelola')
                ->where('tahun', $currentYear)
                ->like('nama_satker', $satkerName)
                ->countAllResults();

            $eplDilaksanakan = $db->table('lpse_nontender')
                ->where('tahun', $currentYear)
                ->like('nama_satker', $satkerName)
                ->countAllResults();

            $eplSudah = $db->table('lpse_nontender')
                ->where('tahun', $currentYear)
                ->like('nama_satker', $satkerName)
                ->groupStart()
                    ->like('status', 'selesai')
                    ->orWhere('nilai_kontrak >', 0)
                ->groupEnd()
                ->countAllResults();
            $eplBelum = max(0, $eplDilaksanakan - $eplSudah);
            $eplPct = $eplDilaksanakan > 0 ? round(($eplSudah / $eplDilaksanakan) * 100, 2) : 0;

            $tenderDilaksanakan = $db->table('lpse_tender')
                ->where('tahun', $currentYear)
                ->like('nama_satker', $satkerName)
                ->countAllResults();

            $tenderSudah = $db->table('lpse_tender')
                ->where('tahun', $currentYear)
                ->like('nama_satker', $satkerName)
                ->groupStart()
                    ->like('status', 'selesai')
                    ->orWhere('nilai_kontrak >', 0)
                ->groupEnd()
                ->countAllResults();
            $tenderBelum = max(0, $tenderDilaksanakan - $tenderSudah);
            $tenderPct = $tenderDilaksanakan > 0 ? round(($tenderSudah / $tenderDilaksanakan) * 100, 2) : 0;

            $nonTenderSudah = $db->table('lpse_pencatatan_nontender')
                ->where('tahun', $currentYear)
                ->like('nama_satker', $satkerName)
                ->countAllResults();
            $nonTenderBelum = max(0, $countRupP - $nonTenderSudah - $eplDilaksanakan);
            $totNonTender = $nonTenderBelum + $nonTenderSudah;
            $nonTenderPct = $totNonTender > 0 ? round(($nonTenderSudah / $totNonTender) * 100, 2) : 0;

            $swakelolaSudah = $db->table('lpse_pencatatan_swakelola')
                ->where('tahun', $currentYear)
                ->like('nama_satker', $satkerName)
                ->countAllResults();
            $swakelolaBelum = max(0, $countRupS - $swakelolaSudah);
            $totSwakelola = $swakelolaBelum + $swakelolaSudah;
            $swakelolaPct = $totSwakelola > 0 ? round(($swakelolaSudah / $totSwakelola) * 100, 2) : 0;

            $epJml = $db->table('epurchasing')
                ->where('tahun', $currentYear)
                ->like('nama_satker', $satkerName)
                ->countAllResults();
            $epSudah = $db->table('epurchasing')
                ->where('tahun', $currentYear)
                ->like('nama_satker', $satkerName)
                ->groupStart()
                    ->like('status', 'selesai')
                    ->orWhere('total_harga >', 0)
                ->groupEnd()
                ->countAllResults();
            $epBelum = max(0, $epJml - $epSudah);
            $epPct = $epJml > 0 ? round(($epSudah / $epJml) * 100, 2) : 0;

            $refKey = strtolower(trim($satkerName));
            $ref = $refMap[$refKey] ?? [];

            $finalRupP = ($countRupP > 0) ? $countRupP : (int)($ref['rup_penyedia'] ?? 0);
            $finalRupS = ($countRupS > 0) ? $countRupS : (int)($ref['rup_swakelola'] ?? 0);
            $finalPL   = ($countRupP > 0) ? $countPL : (int)($ref['rup_pl'] ?? 0);
            $finalJuksung = ($countRupP > 0) ? $countJuksung : (int)($ref['rup_juksung'] ?? 0);
            $finalEpurchasing = ($countRupP > 0) ? $countEpurchasing : (int)($ref['rup_epurchasing'] ?? 0);
            $finalDikecualikan = ($countRupP > 0) ? $countDikecualikan : (int)($ref['rup_dikecualikan'] ?? 0);
            $finalTenderRup = ($countRupP > 0) ? $countTenderRup : (int)($ref['rup_tender'] ?? 0);

            $finalEplDilaksanakan = ($eplDilaksanakan > 0) ? $eplDilaksanakan : (int)($ref['epl_dilaksanakan'] ?? 0);
            $finalEplSudah = ($eplDilaksanakan > 0) ? $eplSudah : (int)($ref['epl_sudah_input'] ?? 0);
            $finalEplBelum = ($eplDilaksanakan > 0) ? $eplBelum : (int)($ref['epl_belum_input'] ?? 0);
            $finalEplPct = ($eplDilaksanakan > 0) ? $eplPct : (float)($ref['epl_pct'] ?? 0);

            $finalTenderDilaksanakan = ($tenderDilaksanakan > 0) ? $tenderDilaksanakan : (int)($ref['tender_dilaksanakan'] ?? 0);
            $finalTenderSudah = ($tenderDilaksanakan > 0) ? $tenderSudah : (int)($ref['tender_sudah_input'] ?? 0);
            $finalTenderBelum = ($tenderDilaksanakan > 0) ? $tenderBelum : (int)($ref['tender_belum_input'] ?? 0);
            $finalTenderPct = ($tenderDilaksanakan > 0) ? $tenderPct : (float)($ref['tender_pct'] ?? 0);

            $finalNonTenderSudah = ($nonTenderSudah > 0) ? $nonTenderSudah : (int)($ref['non_tender_sudah'] ?? 0);
            $finalNonTenderBelum = ($nonTenderSudah > 0) ? $nonTenderBelum : (int)($ref['non_tender_belum'] ?? 0);
            $finalNonTenderPct = ($nonTenderSudah > 0) ? $nonTenderPct : (float)($ref['non_tender_pct'] ?? 0);

            $finalSwakelolaSudah = ($swakelolaSudah > 0) ? $swakelolaSudah : (int)($ref['swakelola_sudah'] ?? 0);
            $finalSwakelolaBelum = ($swakelolaSudah > 0) ? $swakelolaBelum : (int)($ref['swakelola_belum'] ?? 0);
            $finalSwakelolaPct = ($swakelolaSudah > 0) ? $swakelolaPct : (float)($ref['swakelola_pct'] ?? 0);

            $finalEpJml = ($epJml > 0) ? $epJml : (int)($ref['epurchasing_jml'] ?? 0);
            $finalEpSudah = ($epJml > 0) ? $epSudah : (int)($ref['epurchasing_sudah'] ?? 0);
            $finalEpBelum = ($epJml > 0) ? $epBelum : (int)($ref['epurchasing_belum'] ?? 0);
            $finalEpPct = ($epJml > 0) ? $epPct : (float)($ref['epurchasing_pct'] ?? 0);

            $payload = [
                'no'                  => $no,
                'satker'              => $satkerName,
                'kategori'            => $kategori,
                'rup_penyedia'        => $finalRupP,
                'rup_swakelola'       => $finalRupS,
                'rup_pl'              => $finalPL,
                'rup_juksung'         => $finalJuksung,
                'rup_epurchasing'     => $finalEpurchasing,
                'rup_dikecualikan'    => $finalDikecualikan,
                'rup_tender'          => $finalTenderRup,
                'epl_dilaksanakan'    => $finalEplDilaksanakan,
                'epl_belum_input'     => $finalEplBelum,
                'epl_sudah_input'     => $finalEplSudah,
                'epl_pct'             => $finalEplPct,
                'tender_dilaksanakan' => $finalTenderDilaksanakan,
                'tender_belum_input'  => $finalTenderBelum,
                'tender_sudah_input'  => $finalTenderSudah,
                'tender_pct'          => $finalTenderPct,
                'non_tender_belum'    => $finalNonTenderBelum,
                'non_tender_sudah'    => $finalNonTenderSudah,
                'non_tender_pct'      => $finalNonTenderPct,
                'swakelola_belum'     => $finalSwakelolaBelum,
                'swakelola_sudah'     => $finalSwakelolaSudah,
                'swakelola_pct'       => $finalSwakelolaPct,
                'epurchasing_jml'     => $finalEpJml,
                'epurchasing_belum'   => $finalEpBelum,
                'epurchasing_sudah'   => $finalEpSudah,
                'epurchasing_pct'     => $finalEpPct,
                'keterangan'          => $ref['keterangan'] ?? '',
                'tahun'               => (int) $currentYear,
                'update_data'         => $formattedDateNow,
                'update_rup'          => $formattedDateNow,
                'updated_at'          => $now,
            ];

            $existing = $builder->where('no', $no)->where('tahun', (int) $currentYear)->get()->getRowArray();
            if ($existing) {
                $builder->where('id', $existing['id'])->update($payload);
            } else {
                $payload['created_at'] = $now;
                $builder->insert($payload);
            }

            $updatedCount++;
            if ($callback) $callback(" ");
        }

        return $updatedCount;
    }
}