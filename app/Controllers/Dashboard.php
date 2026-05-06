<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;

class Dashboard extends BaseController
{
    use ResponseTrait;

    private $apiPenyedia = 'https://ajip-api.cloud/api/data/perbandingan-rup-penyedia';
    private $apiSwakelola = 'https://ajip-api.cloud/api/data/perbandingan-swakelola';
    private $apiMasterSatker = 'https://ajip-api.cloud/api/data/master-satker';
    private $apiKegiatanMaster = 'https://ajip-api.cloud/api/data/kegiatan-master';
    
    // Specific for RUP OPD Monitoring
    private $apiRupAnggaranPenyedia = 'https://ajip-api.cloud/api/data/rup-paket-anggaran';
    private $apiRupAnggaranSwakelola = 'https://ajip-api.cloud/api/data/rup-paket-swakelola';

    // Data Pengadaan APIs
    private $apiEpurchasing = 'https://ajip-api.cloud/api/data/paket-epurchasing';
    private $apiTender = 'https://ajip-api.cloud/api/data/tender-selesai';
    private $apiTenderEkontrak = 'https://ajip-api.cloud/api/data/list-tender-kontrak';
    private $apiNonTender = 'https://ajip-api.cloud/api/data/nontender-selesai';
    private $apiPencatatanNonTender = 'https://ajip-api.cloud/api/data/pencatatan-nontender-realisasi';
    private $apiPencatatanSwakelola = 'https://ajip-api.cloud/api/data/list-pencatatan-swakelola';
    private $apiPencatatanSwakelolaRealisasi = 'https://ajip-api.cloud/api/data/encatatan-swakelola-realisasi';
    private $apiNonTenderEkontrak = 'https://ajip-api.cloud/api/data/list-nontender-ekontrak';
    private $apiPengumumanTender = 'https://ajip-api.cloud/api/data/pengumuman-tender';
    private $apiNonTenderSelesai = 'https://ajip-api.cloud/api/data/nontender-selesai';
    private $apiListPencatatanSwakelola = 'https://ajip-api.cloud/api/data/list-pencatatan-swakelola';

    public function index()
    {
        try {
            $chartModel = new \App\Models\DashboardChartModel();
            $charts = $chartModel->getWithMenu();
        } catch (\Throwable $e) {
            $charts = [];
        }

        return view('dashboard/index', [
            'title'  => 'Dashboard Overview',
            'user'   => session()->get(),
            'charts' => $charts
        ]);
    }

    public function getDashboardSummary($type)
    {
        $currentYear = date('Y');
        $userSatker = trim(session()->get('kd_satker') ?? '');
        $userRole = trim(strtolower(session()->get('role') ?? ''));
        
        $cacheKey = "rup_summary_matrix_v13_{$type}_{$currentYear}_" . ($userSatker ?: 'all');
        
        if (!$summary = cache($cacheKey)) {
            $url = ($type === 'swakelola') ? $this->apiSwakelola : $this->apiPenyedia;
            $client = \Config\Services::curlrequest();
            try {
                $response = $client->get($url, ['timeout' => 60]);
                $raw = json_decode($response->getBody(), true);
                if (!is_array($raw)) $raw = [];
                
                $metodeField = ($type === 'penyedia') ? 'metode_pengadaan' : 'tipe_swakelola';
                
                $matrix = [];
                $jenisList = [];
                $metodeList = [];
                $opdList = [];

                foreach ($raw as $item) {
                    $yr = $item['tahun_anggaran'] ?? ($item['tahun_anggaran_sirup'] ?? ($item['tahun_anggaran_dana'] ?? ($item['tahun'] ?? '')));
                    if ($yr != $currentYear) continue;

                    $kd = trim($item['kd_satker'] ?? ($item['kode_satker'] ?? ($item['kd_organisasi'] ?? '')));
                    if (in_array($userRole, ['satker', 'opd']) && $userSatker && $kd != $userSatker) continue;

                    $pagu = (float)($item['pagu'] ?? ($item['total_pagu'] ?? ($item['Pagu'] ?? 0)));
                    $opd = $item['nama_satker'] ?? ($item['Nama_Satker'] ?? ($item['satker'] ?? ($item['Nama Satker'] ?? ($item['nama_organisasi'] ?? 'N/A'))));
                    $jenis = $item['jenis_pengadaan'] ?? ($item['jenis'] ?? ($item['jenis_pengadaan_sirup'] ?? 'N/A'));
                    $metode = $item[$metodeField] ?? ($item['metode'] ?? ($item['tipe'] ?? ($item['metode_pengadaan_sirup'] ?? 'N/A')));

                    // Build Matrix: matrix[opd][jenis][metode]
                    if (!isset($matrix[$opd])) $matrix[$opd] = [];
                    if (!isset($matrix[$opd][$jenis])) $matrix[$opd][$jenis] = [];
                    if (!isset($matrix[$opd][$jenis][$metode])) $matrix[$opd][$jenis][$metode] = ['pagu' => 0, 'paket' => 0];
                    
                    $matrix[$opd][$jenis][$metode]['pagu'] += $pagu;
                    $matrix[$opd][$jenis][$metode]['paket'] += 1;

                    $opdList[$opd] = true;
                    $jenisList[$jenis] = true;
                    $metodeList[$metode] = true;
                }

                $summary = [
                    'matrix' => $matrix,
                    'opds' => array_keys($opdList),
                    'jenis' => array_keys($jenisList),
                    'metodes' => array_keys($metodeList),
                    'type' => $type
                ];
                
                cache()->save($cacheKey, $summary, 1800);
            } catch (\Exception $e) {
                return $this->respond(['error' => $e->getMessage()], 500);
            }
        }

        return $this->respond($summary);
    }


    public function penyedia()
    {
        $data = [
            'title' => 'Data RUP Penyedia',
            'user'  => session()->get(),
            'type'  => 'penyedia'
        ];
        return view('dashboard/table', $data);
    }

    public function swakelola()
    {
        $data = [
            'title' => 'Data RUP Swakelola',
            'user'  => session()->get(),
            'type'  => 'swakelola'
        ];
        return view('dashboard/table', $data);
    }

    public function rupOpd()
    {
        $currentYear = date('Y');
        $cacheKey = "rup_opd_v11_{$currentYear}";
        
        if (!$summary = cache($cacheKey)) {
            $client = \Config\Services::curlrequest();

            // 1. Fetch Master Datasets
            try {
                $resSatker = $client->get($this->apiMasterSatker, ['timeout' => 15]);
                $masterSatker = json_decode($resSatker->getBody(), true) ?? [];
            } catch (\Exception $e) { $masterSatker = []; }

            // 2. Fetch Master Anggaran (Kegiatan Master)
            if (!$kegiatanMaster = cache("kegiatan_master_{$currentYear}")) {
                try {
                    $resKegiatan = $client->get($this->apiKegiatanMaster, ['timeout' => 25]);
                    $kegiatanMaster = json_decode($resKegiatan->getBody(), true) ?? [];
                    if (is_array($kegiatanMaster)) cache()->save("kegiatan_master_{$currentYear}", $kegiatanMaster, 3600);
                } catch (\Exception $e) { $kegiatanMaster = []; }
            }

            // 3. Fetch RUP Data (Anggaran specific endpoints)
            $fetchRup = function($url) use ($client) {
                try {
                    $res = $client->get($url, ['timeout' => 30]);
                    return json_decode($res->getBody(), true) ?? [];
                } catch (\Exception $e) { return []; }
            };

            $rupPenyedia = array_map(function($p) { $p['_tipe'] = 'Penyedia'; return $p; }, $fetchRup($this->apiRupAnggaranPenyedia));
            $rupSwakelola = array_map(function($s) { $s['_tipe'] = 'Swakelola'; return $s; }, $fetchRup($this->apiRupAnggaranSwakelola));
            $allRup = array_merge($rupPenyedia, $rupSwakelola);

            // Fetch package names from perbandingan APIs (they contain nama_paket)
            $perbandinganPenyedia = $fetchRup($this->apiPenyedia);
            $perbandinganSwakelola = $fetchRup($this->apiSwakelola);
            $namaPaketMap = [];
            foreach (array_merge($perbandinganPenyedia, $perbandinganSwakelola) as $p) {
                if (isset($p['kd_rup']) && isset($p['nama_paket'])) {
                    $namaPaketMap[$p['kd_rup']] = $p['nama_paket'];
                }
            }

            // 4. Pre-process Master Anggaran to get valid program codes per Satker
            $satkerPrograms = [];
            $kegiatanStats = [];
            foreach (($kegiatanMaster ?? []) as $k) {
                if (($k['tahun_anggaran'] ?? '') != $currentYear) continue;
                $kd = $k['kd_satker'] ?? '0';
                $progKey = trim($k['kd_program_str'] ?? 'UNKNOWN');
                
                // Store program codes for prefix matching
                if (!isset($satkerPrograms[$kd])) $satkerPrograms[$kd] = [];
                if ($progKey !== 'UNKNOWN') $satkerPrograms[$kd][] = $progKey;

                // Aggregate Master Stats
                $progName = $k['nama_program'] ?? 'Program Tanpa Nama';
                if (!isset($kegiatanStats[$kd])) {
                    $kegiatanStats[$kd] = ['total_pagu' => 0, 'program_list' => []];
                }
                $kegiatanStats[$kd]['total_pagu'] += (float)($k['pagu_program'] ?? 0);
                if (!isset($kegiatanStats[$kd]['program_list'][$progKey])) {
                    $kegiatanStats[$kd]['program_list'][$progKey] = ['name' => $progName, 'pagu' => 0];
                }
                $kegiatanStats[$kd]['program_list'][$progKey]['pagu'] += (float)($k['pagu_program'] ?? 0);
            }

            // Sort program codes by length descending to match most specific first
            foreach ($satkerPrograms as $kd => &$codes) {
                usort($codes, function($a, $b) { return strlen($b) - strlen($a); });
            }

            $rupStats = [];
            $seenRup = []; // Unique packet tracker
            foreach ($allRup as $item) {
                if (($item['tahun_anggaran'] ?? '') != $currentYear) continue;
                
                $kd = $item['kd_satker'] ?? '0';
                $kdRup = $item['kd_rup'] ?? null;
                $isNewRup = false;
                
                if ($kdRup) {
                    if (!isset($seenRup[$kdRup])) {
                        $seenRup[$kdRup] = true;
                        $isNewRup = true;
                    }
                } else {
                    $isNewRup = true;
                }
                
                $paguItem = (float)($item['pagu'] ?? 0);
                $mak = trim($item['mak'] ?? '');
                $progKey = 'UNKNOWN';
                
                // A. Try Matching by Code (MAK vs kd_program_str)
                if ($mak && isset($satkerPrograms[$kd])) {
                    $cleanMak = str_replace(['.', '-'], '', $mak);
                    foreach ($satkerPrograms[$kd] as $validCode) {
                        $cleanCode = str_replace(['.', '-'], '', $validCode);
                        if ($cleanCode === '') continue;

                        // 1. Precise Match with dots (e.g. .3.26.01. matches .3.26.01.2.02.)
                        if (str_contains(".$mak.", ".$validCode.")) {
                            $progKey = $validCode;
                            break;
                        }
                        
                        // 2. Prefix Match (MAK starts with Program Code) - Most common in gov systems
                        if (str_starts_with($cleanMak, $cleanCode)) {
                            $progKey = $validCode;
                            break;
                        }

                        // 3. Substring match but only if it's long enough to be significant (min 6 chars)
                        // This prevents short codes like '01' from matching everything
                        if (strlen($cleanCode) >= 6 && str_contains($cleanMak, $cleanCode)) {
                            $progKey = $validCode;
                            break;
                        }
                    }
                }
                
                // B. Aggressive Fallback: Match by Program Name (Case-insensitive, Alphanumeric only)
                if ($progKey === 'UNKNOWN' && isset($item['nama_program']) && isset($kegiatanStats[$kd]['program_list'])) {
                    $rupProgName = strtoupper(preg_replace('/[^A-Z0-9]/', '', $item['nama_program']));
                    foreach ($kegiatanStats[$kd]['program_list'] as $mCode => $mProg) {
                        $masterProgName = strtoupper(preg_replace('/[^A-Z0-9]/', '', $mProg['name']));
                        // Strict comparison for normalized names
                        if ($rupProgName !== '' && $rupProgName === $masterProgName) {
                            $progKey = $mCode;
                            break;
                        }
                    }
                }
                
                // C. Final Fallback: If no match found, mark as Other/Unmapped
                if ($progKey === 'UNKNOWN' && $mak) {
                    $progKey = 'LAINNYA';
                }
                
                if (!isset($rupStats[$kd])) {
                    $rupStats[$kd] = ['pagu' => 0, 'paket' => 0, 'programs' => [], 'maks' => [], 'package_list' => []];
                }
                
                $rupStats[$kd]['pagu'] += $paguItem;
                if ($isNewRup) {
                    $rupStats[$kd]['paket'] += 1;
                }
                
                $rupStats[$kd]['programs'][$progKey] = ($rupStats[$kd]['programs'][$progKey] ?? 0) + $paguItem;
                if ($mak) $rupStats[$kd]['maks'][$progKey][$mak] = true;
                
                if (!isset($rupStats[$kd]['package_list'][$progKey])) {
                    $rupStats[$kd]['package_list'][$progKey] = [];
                }
                
                // Check if this kd_rup is already in this progKey's package_list
                $foundPkgIndex = -1;
                if ($kdRup) {
                    foreach ($rupStats[$kd]['package_list'][$progKey] as $idx => $pkg) {
                        if ($pkg['kd_rup'] === $kdRup) {
                            $foundPkgIndex = $idx;
                            break;
                        }
                    }
                }

                if ($foundPkgIndex >= 0) {
                    $rupStats[$kd]['package_list'][$progKey][$foundPkgIndex]['pagu'] += $paguItem;
                    if ($mak && strpos($rupStats[$kd]['package_list'][$progKey][$foundPkgIndex]['mak'], $mak) === false) {
                        $rupStats[$kd]['package_list'][$progKey][$foundPkgIndex]['mak'] .= ', ' . $mak;
                    }
                } else {
                    $rupStats[$kd]['package_list'][$progKey][] = [
                        'kd_rup' => $kdRup ?? '-',
                        'nama_paket' => $namaPaketMap[$kdRup ?? ''] ?? ($item['nama_paket'] ?? 'Tanpa Nama'),
                        'mak' => $mak ?: '-',
                        'pagu' => $paguItem,
                        'tipe' => $item['_tipe'] ?? 'Lainnya'
                    ];
                }
            }

            // 6. Build Final Summary
            $summary = [];
            foreach ($masterSatker as $s) {
                $kd = $s['kd_satker'] ?? '0';
                if (!isset($rupStats[$kd])) continue; 

                $rs = $rupStats[$kd];
                $ks = $kegiatanStats[$kd] ?? ['total_pagu' => 0, 'program_list' => []];
                
                $progDetails = [];
                $allProgKeys = array_unique(array_merge(array_keys($ks['program_list']), array_keys($rs['programs'])));
                
                foreach ($allProgKeys as $pk) {
                    $paguMaster = $ks['program_list'][$pk]['pagu'] ?? 0;
                    $paguRup = $rs['programs'][$pk] ?? 0;
                    $progName = $ks['program_list'][$pk]['name'] ?? "Program ($pk)";
                    $progMaks = isset($rs['maks'][$pk]) ? implode(', ', array_keys($rs['maks'][$pk])) : '-';
                    
                    $progPackages = $rs['package_list'][$pk] ?? [];
                    
                    $progDetails[] = [
                        'code' => $pk,
                        'name' => $progName,
                        'mak'  => $progMaks,
                        'pagu_master' => $paguMaster,
                        'pagu_rup' => $paguRup,
                        'percent' => $paguMaster > 0 ? round(($paguRup / $paguMaster) * 100, 2) : ($paguRup > 0 ? 100 : 0),
                        'packages' => $progPackages
                    ];
                }

                usort($progDetails, function($a, $b) { return $b['pagu_master'] - $a['pagu_master']; });

                $summary[] = [
                    'kd_satker' => $kd,
                    'nama_satker' => $s['nama_satker'],
                    'kd_satker_str' => $s['kd_satker_str'],
                    'rup_pagu' => $rs['pagu'],
                    'rup_paket' => $rs['paket'],
                    'master_pagu' => $ks['total_pagu'],
                    'percent' => $ks['total_pagu'] > 0 ? round(($rs['pagu'] / $ks['total_pagu']) * 100, 2) : ($rs['pagu'] > 0 ? 100 : 0),
                    'details' => $progDetails
                ];
            }

            usort($summary, function($a, $b) { return $a['percent'] <=> $b['percent']; });
            cache()->save($cacheKey, $summary, 3600);
        }

        // Apply strict satker filtering post-cache to avoid cache poisoning
        $userRole = trim(strtolower(session()->get('role') ?? ''));
        $userSatker = trim(session()->get('kd_satker') ?? '');
        
        if (in_array($userRole, ['satker', 'opd']) && $userSatker) {
            $summary = array_values(array_filter($summary, function($s) use ($userSatker) {
                return trim($s['kd_satker'] ?? '') == $userSatker;
            }));
        }

        $data = [
            'title' => 'Monitoring Keterumuman RUP (New Logic)',
            'user'  => session()->get(),
            'summary' => $summary
        ];
        return view('dashboard/rup_opd', $data);
    }

    public function getData($type)
    {
        $currentYear = date('Y');
        $userSatker = session()->get('kd_satker');
        $userRole = session()->get('role');
        
        $cacheKey = "rup_data_stripped_v12_{$type}_{$currentYear}";
        
        if (!$data = cache($cacheKey)) {
            $url = ($type === 'swakelola') ? $this->apiSwakelola : $this->apiPenyedia;
            $client = \Config\Services::curlrequest();
            try {
                $response = $client->get($url, ['timeout' => 60]);
                $raw = json_decode($response->getBody(), true);
                if (!is_array($raw)) $raw = [];
                
                $metodeField = ($type === 'penyedia') ? 'metode_pengadaan' : 'tipe_swakelola';
                
                $data = [];
                foreach ($raw as $item) {
                    $yr = $item['tahun_anggaran'] ?? ($item['tahun_anggaran_sirup'] ?? ($item['tahun_anggaran_dana'] ?? ($item['tahun'] ?? '')));
                    if ($yr != $currentYear) continue;

                    // Type-specific field mapping
                    if ($type === 'swakelola') {
                        $uraian = $item['uraian_pekerjaan'] ?? ($item['urarian_pekerjaan'] ?? ($item['kegiatan'] ?? ''));
                        $tglAwal = $item['tgl_awal_pelaksanaan_kontrak'] ?? ($item['tgl_awal_pelaksanaan'] ?? ($item['tgl_awal_pemilihan'] ?? ''));
                        $tglAkhir = $item['tgl_akhir_pelaksanaan_kontrak'] ?? ($item['tgl_akhir_pelaksanaan'] ?? ($item['tgl_akhir_pemilihan'] ?? ''));
                    } else {
                        $uraian = $item['urarian_pekerjaan'] ?? ($item['uraian_pekerjaan'] ?? ($item['spesifikasi_pekerjaan'] ?? ''));
                        $tglAwal = $item['tgl_awal_pemilihan'] ?? ($item['tgl_awal_kontrak'] ?? '');
                        $tglAkhir = $item['tgl_akhir_pemilihan'] ?? ($item['tgl_akhir_kontrak'] ?? '');
                    }

                    $data[] = [
                        'pagu' => (float)($item['pagu'] ?? ($item['total_pagu'] ?? ($item['Pagu'] ?? 0))),
                        'kd_satker' => $item['kd_satker'] ?? ($item['kode_satker'] ?? ($item['kd_organisasi'] ?? '')),
                        'nama_satker' => $item['nama_satker'] ?? ($item['Nama_Satker'] ?? ($item['satker'] ?? ($item['Nama Satker'] ?? ($item['nama_organisasi'] ?? '')))),
                        'jenis_pengadaan' => $item['jenis_pengadaan'] ?? ($item['jenis'] ?? ($item['jenis_pengadaan_sirup'] ?? '')),
                        $metodeField => $item[$metodeField] ?? ($item['metode'] ?? ($item['tipe'] ?? ($item['metode_pengadaan_sirup'] ?? ''))),
                        'volume_pekerjaan' => $item['volume_pekerjaan'] ?? '',
                        'urarian_pekerjaan' => $uraian, // Display field
                        'tgl_awal_pemilihan' => $tglAwal, // Display field
                        'tgl_akhir_pemilihan' => $tglAkhir, // Display field
                        'kd_rup' => $item['kd_rup'] ?? '',
                        'nama_paket' => $item['nama_paket'] ?? '',
                        'status_umumkan_rup' => $item['status_umumkan_rup'] ?? 'Terumumkan'
                    ];
                }
                cache()->save($cacheKey, $data, 3600);
            } catch (\Exception $e) {
                $data = [];
            }
        }

        // Apply RBAC filtering for Satker
        if ($userRole === 'satker' && $userSatker) {
            $data = array_values(array_filter($data, function($item) use ($userSatker) {
                return $item['kd_satker'] == $userSatker;
            }));
        }

        return $this->respond($data);
    }

    public function getDatatables($type)
    {
        $currentYear = date('Y');
        $userSatker = session()->get('kd_satker');
        $userRole = session()->get('role');
        
        $cacheKey = "rup_data_stripped_v12_{$type}_{$currentYear}";
        
        if (!$allData = cache($cacheKey)) {
            // If main data cache is empty, populate it using getData logic
            $this->getData($type);
            $allData = cache($cacheKey) ?? [];
        }

        // Role-based filtering for Satker
        if ($userRole === 'satker' && $userSatker) {
            $allData = array_values(array_filter($allData, function($item) use ($userSatker) {
                return ($item['kd_satker'] ?? '') == $userSatker;
            }));
        }

        $draw = $this->request->getGet('draw');
        $start = $this->request->getGet('start');
        $length = $this->request->getGet('length');
        $searchData = $this->request->getGet('search');
        $search = $searchData['value'] ?? '';
        $order = $this->request->getGet('order');
        $opdFilter = $this->request->getGet('opd');
        $jenisFilter = $this->request->getGet('jenis');
        $metodeFilter = $this->request->getGet('metode');

        // Filter by OPD/Satker
        if ($opdFilter && $opdFilter !== 'all') {
            $allData = array_filter($allData, function($item) use ($opdFilter) {
                return ($item['nama_satker'] ?? '') === $opdFilter;
            });
        }

        // Filter by Jenis Pengadaan
        if ($jenisFilter && $jenisFilter !== 'all') {
            $allData = array_filter($allData, function($item) use ($jenisFilter) {
                return ($item['jenis_pengadaan'] ?? '') === $jenisFilter;
            });
        }

        // Filter by Metode/Tipe
        if ($metodeFilter && $metodeFilter !== 'all') {
            $metodeField = ($type === 'penyedia') ? 'metode_pengadaan' : 'tipe_swakelola';
            $allData = array_filter($allData, function($item) use ($metodeFilter, $metodeField) {
                return ($item[$metodeField] ?? '') == $metodeFilter;
            });
        }

        // Filter by Role (Session)
        $userSatker = session()->get('kd_satker');
        if ($userSatker) {
            $allData = array_filter($allData, function($item) use ($userSatker) {
                return ($item['kd_satker'] ?? '') == $userSatker;
            });
        }

        // Search
        if ($search) {
            $allData = array_filter($allData, function($item) use ($search) {
                return stripos($item['nama_paket'], $search) !== false || 
                       stripos($item['nama_satker'], $search) !== false ||
                       stripos((string)($item['kd_rup'] ?? ''), $search) !== false;
            });
        }

        $totalRecords = count($allData);

        // Sorting
        if ($order) {
            $colIdx = $order[0]['column'];
            $dir = $order[0]['dir'];
            $cols = [
                'kd_rup', 'nama_paket', 'nama_satker', 'pagu',
            ];
            
            if ($type !== 'swakelola') {
                $cols[] = 'jenis_pengadaan';
            }
            
            $cols = array_merge($cols, [
                ($type === 'penyedia' ? 'metode_pengadaan' : 'tipe_swakelola'), 
                'volume_pekerjaan', 
                ($type === 'penyedia' ? 'urarian_pekerjaan' : 'uraian_pekerjaan'),
                ($type === 'penyedia' ? 'tgl_awal_pemilihan' : 'tgl_awal_pelaksanaan_kontrak'), 
                ($type === 'penyedia' ? 'tgl_akhir_pemilihan' : 'tgl_akhir_pelaksanaan_kontrak'),
                'status_umumkan_rup'
            ]);
            
            $colName = $cols[$colIdx] ?? 'nama_paket';
            
            usort($allData, function($a, $b) use ($colName, $dir) {
                // Sorting Fallbacks
                $getVal = function($item, $key) {
                    if (isset($item[$key]) && $item[$key] !== '') return $item[$key];
                    
                    // Specific fallbacks for Penyedia/Swakelola inconsistencies
                    if ($key === 'urarian_pekerjaan' || $key === 'uraian_pekerjaan') {
                        return $item['urarian_pekerjaan'] ?? ($item['uraian_pekerjaan'] ?? ($item['spesifikasi_pekerjaan'] ?? ''));
                    }
                    if ($key === 'tgl_awal_pemilihan' || $key === 'tgl_awal_pelaksanaan_kontrak') {
                        return $item['tgl_awal_pemilihan'] ?? ($item['tgl_awal_kontrak'] ?? ($item['tgl_awal_pelaksanaan'] ?? ''));
                    }
                    if ($key === 'tgl_akhir_pemilihan' || $key === 'tgl_akhir_pelaksanaan_kontrak') {
                        return $item['tgl_akhir_pemilihan'] ?? ($item['tgl_akhir_kontrak'] ?? ($item['tgl_akhir_pelaksanaan'] ?? ''));
                    }
                    return '';
                };

                $valA = $getVal($a, $colName);
                $valB = $getVal($b, $colName);
                
                if (is_numeric($valA) && is_numeric($valB)) {
                    return ($dir === 'asc') ? $valA <=> $valB : $valB <=> $valA;
                }
                return ($dir === 'asc') ? strcasecmp((string)$valA, (string)$valB) : strcasecmp((string)$valB, (string)$valA);
            });
        }

        // Pagination
        $data = ($length == -1) ? $allData : array_slice($allData, $start, $length);

        return $this->response->setJSON([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => array_values($data)
        ]);
    }

    public function comparisons()
    {
        ini_set('memory_limit', '512M');
        $userSatker = trim(session()->get('kd_satker') ?? '');
        $userRole = trim(strtolower(session()->get('role') ?? ''));
        
        $fetchRaw = function($url, $cacheKey) {
            if (!$data = cache($cacheKey)) {
                $client = \Config\Services::curlrequest();
                try {
                    $response = $client->get($url, ['timeout' => 60]);
                    $data = json_decode($response->getBody(), true);
                    if (is_array($data)) {
                        cache()->save($cacheKey, $data, 3600); 
                    } else $data = [];
                } catch (\Exception $e) { $data = []; }
            }
            return is_array($data) ? $data : [];
        };

        $aggregate = function($rawData, $type) use ($userSatker, $userRole) {
            $yearly = [];
            $dist1 = [];
            $dist2 = [];
            $field1 = ($type === 'penyedia') ? 'metode_pengadaan' : 'tipe_swakelola';
            $field2 = 'jenis_pengadaan';

            foreach ($rawData as $item) {
                // Strict Satker Filtering
                if (in_array($userRole, ['satker', 'opd']) && $userSatker) {
                    $kd = trim($item['kd_satker'] ?? ($item['kode_satker'] ?? ($item['kd_organisasi'] ?? '')));
                    if ($kd != $userSatker) continue;
                }

                // Try all common year fields
                $yr = $item['tahun_anggaran'] ?? ($item['tahun_anggaran_dana'] ?? ($item['tahun'] ?? 'Unknown'));
                if ($yr === 'Unknown' || !$yr) continue;

                if (!isset($yearly[$yr])) $yearly[$yr] = ['pagu' => 0, 'paket' => 0];
                $yearly[$yr]['pagu'] += (float)($item['pagu'] ?? 0);
                $yearly[$yr]['paket'] += 1;

                $cat1 = $item[$field1] ?? 'Lainnya';
                $cat2 = $item[$field2] ?? 'Lainnya';
                if (!$cat1) $cat1 = 'Lainnya';
                if (!$cat2) $cat2 = 'Lainnya';

                if (!isset($dist1[$cat1][$yr])) $dist1[$cat1][$yr] = 0;
                $dist1[$cat1][$yr] += 1;

                if (!isset($dist2[$cat2][$yr])) $dist2[$cat2][$yr] = 0;
                $dist2[$cat2][$yr] += 1;
            }
            ksort($yearly);
            return ['yearly' => $yearly, 'dist1' => $dist1, 'dist2' => $dist2];
        };

        $apiPerbandinganPenyedia = 'https://ajip-api.cloud/api/data/perbandingan-rup-penyedia';
        $apiPerbandinganSwakelola = 'https://ajip-api.cloud/api/data/perbandingan-swakelola';

        $rawPenyedia = $fetchRaw($apiPerbandinganPenyedia, "rup_data_perbandingan_penyedia_v3");
        $rawSwakelola = $fetchRaw($apiPerbandinganSwakelola, "rup_data_perbandingan_swakelola_v3");

        $data = [
            'title' => 'Dashboard Perbandingan Tahunan',
            'user'  => session()->get(),
            'statsPenyedia'  => $aggregate($rawPenyedia, 'penyedia'),
            'statsSwakelola' => $aggregate($rawSwakelola, 'swakelola')
        ];

        return view('dashboard/comparisons', $data);
    }

    public function getChartData($id)
    {
        $chartModel = new \App\Models\DashboardChartModel();
        $menuModel = new \App\Models\DynamicMenuModel();
        
        $chart = $chartModel->find($id);
        if (!$chart) return $this->failNotFound('Chart not found');
        
        $menu = $menuModel->find($chart['menu_id']);
        if (!$menu) return $this->failNotFound('Source menu not found');

        // Fetch API data
        $client = \Config\Services::curlrequest();
        try {
            $response = $client->get($menu['api_url']);
            $data = json_decode($response->getBody(), true);
            if (!is_array($data)) return $this->respond([]);

            $results = [];
            if ($chart['aggregation'] === 'count') {
                foreach ($data as $item) {
                    $label = $item[$chart['label_field']] ?? 'N/A';
                    $results[$label] = ($results[$label] ?? 0) + 1;
                }
            } else if ($chart['aggregation'] === 'sum') {
                foreach ($data as $item) {
                    $label = $item[$chart['label_field']] ?? 'N/A';
                    $val = floatval($item[$chart['value_field']] ?? 0);
                    $results[$label] = ($results[$label] ?? 0) + $val;
                }
            }

            // Format for Chart.js
            $output = [
                'labels' => array_keys($results),
                'values' => array_values($results)
            ];

            return $this->respond($output);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function export($type, $year)
    {
        $userSatker = session()->get('kd_satker');
        $url = ($type === 'swakelola') ? $this->apiSwakelola : $this->apiPenyedia;
        $cacheKey = ($type === 'swakelola') ? "rup_data_perbandingan_swakelola_v3" : "rup_data_perbandingan_penyedia_v3";
        
        if (!$data = cache($cacheKey)) {
            $client = \Config\Services::curlrequest();
            try {
                $response = $client->get($url, ['timeout' => 60]);
                $data = json_decode($response->getBody(), true);
                if (is_array($data)) cache()->save($cacheKey, $data, 3600);
            } catch (\Exception $e) { $data = []; }
        }

        if (!is_array($data)) return redirect()->back()->with('error', 'Data tidak tersedia.');

        $filtered = array_filter($data, function($item) use ($year, $userSatker) {
            $yr = $item['tahun_anggaran'] ?? ($item['tahun_anggaran_dana'] ?? ($item['tahun'] ?? ''));
            return ($yr == $year) && ($userSatker ? (($item['kd_satker'] ?? '') == $userSatker) : true);
        });

        if (empty($filtered)) return redirect()->back()->with('error', 'Data tahun '.$year.' tidak ditemukan.');

        // Generate CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="RUP_'.$type.'_'.$year.'.csv"');
        
        $output = fopen('php://output', 'w');
        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Use keys of the first item as headers
        fputcsv($output, array_keys(reset($filtered)));
        
        foreach ($filtered as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }

    public function exportRupOpd()
    {
        $currentYear = date('Y');
        $cacheKey = "rup_opd_v11_{$currentYear}";
        
        $summary = cache($cacheKey);
        if (!$summary) {
            $this->rupOpd(); // Regenerate cache if missing
            $summary = cache($cacheKey);
        }

        if (!$summary) return redirect()->back()->with('error', 'Gagal memproses data laporan.');

        // Apply strict satker filtering
        $userRole = trim(strtolower(session()->get('role') ?? ''));
        $userSatker = trim(session()->get('kd_satker') ?? '');
        
        if (in_array($userRole, ['satker', 'opd']) && $userSatker) {
            $summary = array_values(array_filter($summary, function($s) use ($userSatker) {
                return trim($s['kd_satker'] ?? '') == $userSatker;
            }));
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="Laporan_Keterumuman_RUP_OPD_'.$currentYear.'.csv"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
        
        fputcsv($output, ['Nama OPD', 'Kode Satker', 'Pagu Master (Kegiatan)', 'Pagu Terumumkan (SIRUP)', 'Jumlah Paket', 'Persentase (%)']);
        
        foreach ($summary as $s) {
            fputcsv($output, [
                $s['nama_satker'],
                $s['kd_satker_str'],
                $s['master_pagu'],
                $s['rup_pagu'],
                $s['rup_paket'],
                $s['percent'] . '%'
            ]);
        }
        
        fclose($output);
        exit;
    }

    public function lpse($type)
    {
        $titles = [
            'tender' => 'Data Tender LPSE',
            'nontender' => 'Data Non Tender LPSE',
            'pencatatan-nontender' => 'Pencatatan Non Tender',
            'pencatatan-swakelola' => 'Pencatatan Swakelola'
        ];

        return view('dashboard/table_lpse', [
            'title' => $titles[$type] ?? 'Data LPSE',
            'user'  => session()->get(),
            'type'  => $type
        ]);
    }

    public function epurchasing()
    {
        return view('dashboard/dashboard_epurchasing', [
            'title' => 'Dashboard E-Purchasing',
            'user'  => session()->get(),
            'type'  => 'epurchasing'
        ]);
    }

    public function tableEpurchasing()
    {
        return view('dashboard/table_epurchasing', [
            'title' => 'Data Detail E-Purchasing',
            'user'  => session()->get(),
            'type'  => 'epurchasing'
        ]);
    }

    public function getEpurchasingDashboardData()
    {
        $cacheKey = "epurchasing_dashboard_v10";

        if (!$result = cache($cacheKey)) {
            $client = \Config\Services::curlrequest();
            try {
                $response = $client->get($this->apiEpurchasing, ['timeout' => 90]);
                $rows = json_decode($response->getBody(), true);
                if (!is_array($rows)) $rows = [];
            } catch (\Exception $e) { $rows = []; }

            $history = []; // [year] => [realisasi, count]
            $topSatker = []; // [kd_satker] => [nama, realisasi, count]
            $topVendor = []; // [nama_penyedia] => [realisasi, count]
            $totalRealisasi = 0;
            $totalPackages = 0;

            $currentYearNum = (int)date('Y');
            $history = []; // [year] => [realisasi, count]
            $monthly = array_fill(1, 12, ['realisasi' => 0, 'count' => 0]); // [month] => [realisasi, count]
            $topSatker = []; // [kd_satker] => [nama, realisasi, count]
            $topVendor = []; // [nama_penyedia] => [realisasi, count]
            $totalRealisasi = 0;
            $totalPackages = 0;

            foreach ($rows as $row) {
                // Restricted Statuses for E-Purchasing
                $status = strtoupper(str_replace('_', ' ', (string)($row['status'] ?? '')));
                $allowed = ['COMPLETED', 'ON ADDENDUM', 'ON PROCESS', 'PAYMENT OUTSIDE SYSTEM'];
                if (!in_array($status, $allowed)) continue;

                // Priority 1: order_date year
                $rawDate = $row['order_date'] ?? '';
                $yr = (strlen($rawDate) >= 4) ? (int)substr($rawDate, 0, 4) : 0;
                
                // Priority 2: fallback to other fields
                if ($yr <= 0) {
                    $yr = (int)($row['fiscal_year'] ?? ($row['tahun_anggaran'] ?? ($row['tahun'] ?? 0)));
                }

                if ($yr <= 0) continue;

                $real = (float)($row['total'] ?? 0);
                
                // History aggregation (All years for chart)
                if (!isset($history[$yr])) $history[$yr] = ['realisasi' => 0, 'count' => 0];
                $history[$yr]['realisasi'] += $real;
                $history[$yr]['count'] += 1;

                // Current Year ONLY for Stats, Top Satker, and Top Vendor
                if ($yr === $currentYearNum) {
                    $totalRealisasi += $real;
                    $totalPackages += 1;

                    // Enrichment from local JSON
                    $kdVendor = trim($row['kode_penyedia'] ?? '');
                    $namaPenyedia = 'Penyedia ID: ' . $kdVendor;
                    
                    if ($kdVendor !== '') {
                        $vFile = WRITEPATH . 'inaproc/vendors/' . $kdVendor . '.json';
                        if (file_exists($vFile)) {
                            $vData = json_decode(file_get_contents($vFile), true);
                            $namaPenyedia = $vData['nama_penyedia'] ?? $namaPenyedia;
                        }
                    }

                    // Satker aggregation
                    $kdSatker = $row['kode_satker'] ?? ($row['kd_satker'] ?? 'UNKNOWN');
                    if (!isset($topSatker[$kdSatker])) {
                        $topSatker[$kdSatker] = [
                            'nama' => $row['nama_satker'] ?? 'Satker Tidak Diketahui',
                            'realisasi' => 0,
                            'count' => 0
                        ];
                    }
                    $topSatker[$kdSatker]['realisasi'] += $real;
                    $topSatker[$kdSatker]['count'] += 1;

                    // Vendor aggregation
                    if (!isset($topVendor[$namaPenyedia])) {
                        $topVendor[$namaPenyedia] = [
                            'realisasi' => 0,
                            'count' => 0,
                            'packages' => []
                        ];
                    }
                    $topVendor[$namaPenyedia]['realisasi'] += $real;
                    $topVendor[$namaPenyedia]['count'] += 1;
                    
                    $rawDate = $row['order_date'] ?? '';
                    $fmtDate = ($rawDate && strtotime($rawDate)) ? date('d-m-Y', strtotime($rawDate)) : '-';

                    $topVendor[$namaPenyedia]['packages'][] = [
                        'nama_paket' => $row['rup_name'] ?? ($row['nama_paket'] ?? '-'),
                        'nama_program' => $row['rup_desc'] ?? '-',
                        'nama_produk' => '-', // Default empty like in table
                        'realisasi' => $real,
                        'tgl_order' => $fmtDate,
                        'satker' => $row['nama_satker'] ?? '-',
                        '_kdVendor' => $kdVendor,
                        '_productIds' => trim($row['product_id'] ?? '')
                    ];

                    // Monthly aggregation for current year
                    $orderDate = $row['order_date'] ?? null;
                    if ($orderDate) {
                        $month = (int)date('n', strtotime($orderDate));
                        if ($month >= 1 && $month <= 12) {
                            $monthly[$month]['realisasi'] += $real;
                            $monthly[$month]['count'] += 1;
                        }
                    }
                }
            }

            ksort($history);
            uasort($topSatker, function($a, $b) {
                return $b['realisasi'] <=> $a['realisasi'];
            });
            uasort($topVendor, function($a, $b) {
                return $b['count'] <=> $a['count'];
            });

            // Late Enrichment: Only fetch product names from JSON for the Top 10 Vendors to ensure high performance
            $topVendorSlice = array_slice($topVendor, 0, 10, true);
            foreach ($topVendorSlice as $vName => &$vData) {
                foreach ($vData['packages'] as &$pkg) {
                    $kdV = $pkg['_kdVendor'];
                    $pIds = $pkg['_productIds'];
                    if ($kdV !== '' && $pIds !== '') {
                        $actualProduct = $this->_getInaprocProductNames($kdV, $pIds);
                        if ($actualProduct && $actualProduct !== '-') {
                            $pkg['nama_produk'] = $actualProduct;
                        }
                    }
                    unset($pkg['_kdVendor'], $pkg['_productIds']); // Cleanup
                }
            }

            // Generate Leadership Narrative
            $currentYear = date('Y');
            $currStats = $history[$currentYear] ?? ['realisasi' => 0, 'count' => 0];
            $prevYear = (string)($currentYear - 1);
            $prevStats = $history[$prevYear] ?? ['realisasi' => 0, 'count' => 0];
            
            $growth = $prevStats['realisasi'] > 0 ? (($currStats['realisasi'] - $prevStats['realisasi']) / $prevStats['realisasi']) * 100 : 0;
            $topS = !empty($topSatker) ? reset($topSatker) : ['nama' => '-', 'realisasi' => 0];
            $topV = !empty($topVendor) ? reset($topVendor) : ['realisasi' => 0, 'count' => 0];
            
            reset($topVendor);
            $topVName = !empty($topVendor) ? key($topVendor) : '-';

            $narrative = "Analisis Tahun Berjalan (" . $currentYearNum . "): Total realisasi mencapai Rp " . number_format($totalRealisasi, 0, ',', '.') . " dari " . number_format($totalPackages, 0, ',', '.') . " paket. ";
            if ($topVName !== '-') {
                $narrative .= "Satker paling aktif saat ini adalah " . ($topS['nama'] ?? '-') . " dengan realisasi Rp " . number_format($topS['realisasi'] ?? 0, 0, ',', '.') . ". ";
                $narrative .= "Penyedia utama tahun ini adalah " . $topVName . " dengan perolehan " . ($topV['count'] ?? 0) . " paket.";
            }

            $result = [
                'summary' => [
                    'total_realisasi' => $totalRealisasi,
                    'total_packages' => $totalPackages,
                    'years_count' => count($history),
                    'year_active' => $currentYearNum
                ],
                'history' => $history,
                'monthly' => $monthly,
                'top_satker' => array_slice($topSatker, 0, 10, true),
                'top_vendor' => $topVendorSlice,
                'report' => [
                    'narrative' => $narrative,
                    'growth' => round($growth, 2),
                    'top_performer' => $topS['nama'] ?? '-',
                    'top_vendor_name' => $topVName
                ]
            ];

            cache()->save($cacheKey, $result, 3600);
        }

        return $this->response->setJSON($result);
    }

    public function dashboardLpse()
    {
        return view('dashboard/dashboard_lpse', [
            'title' => 'Dashboard LPSE',
            'user'  => session()->get(),
        ]);
    }

    public function getDashboardLpseData()
    {
        $yearFilter = $this->request->getGet('year') ?? 'all';
        $cacheKey   = "dashboard_lpse_v19_{$yearFilter}";

        if (!$result = cache($cacheKey)) {
            $client = \Config\Services::curlrequest();

            $sources = [
                'Tender'              => [$this->apiTender,              'tahun_anggaran', 'nilai_kontrak', 'pagu'],
                'Non Tender'          => [$this->apiNonTender,           'tahun_anggaran', 'pagu',          'pagu'],
                'Pencatatan Non Tender' => [$this->apiPencatatanNonTender,'tahun_anggaran','nilai_realisasi','pagu'],
                'Pencatatan Swakelola'  => [$this->apiListPencatatanSwakelola,'tahun_anggaran','nilai_realisasi','pagu'],
            ];

            $allYears  = [];
            $byYear    = []; // [type][year] => [pagu, count]
            $totalPagu = []; // [type] => pagu
            $totalRealisasi = []; // [type] => realisasi
            $byJenis   = []; // [jenis_pengadaan][year] => [pagu, count]
            $totalJenis = []; // [jenis_pengadaan] => pagu
            $seenPackages = []; // [label][year][id] => true
            
            // Pre-fetch Non-Tender Selesai map for accurate Realisasi values
            $nontenderSelesaiMap = [];
            try {
                $resSelesai = $client->get($this->apiNonTenderSelesai, ['timeout' => 30]);
                $rowsSelesai = json_decode($resSelesai->getBody(), true);
                if (is_array($rowsSelesai)) {
                    foreach ($rowsSelesai as $s) {
                        $sid = $s['kd_nontender'] ?? ($s['kd_paket'] ?? null);
                        if ($sid) $nontenderSelesaiMap[$sid] = (float)($s['nilai_kontrak'] ?? ($s['nilai_pemenang'] ?? 0));
                    }
                }
            } catch (\Exception $e) {}
            
            // Pre-fetch Tender e-Kontrak map for final values
            $tenderEkontrakMap = [];
            try {
                $resEkontrak = $client->get($this->apiTenderEkontrak, ['timeout' => 60]);
                $rowsEkontrak = json_decode($resEkontrak->getBody(), true);
                if (is_array($rowsEkontrak)) {
                    foreach ($rowsEkontrak as $ek) {
                        $kd = trim((string)($ek['kd_tender'] ?? ($ek['kd_paket'] ?? '')));
                        $rup = trim((string)($ek['kd_rup'] ?? ''));
                        
                        if ($kd !== '') $tenderEkontrakMap[$kd] = $ek;
                        if ($rup !== '') $tenderEkontrakMap['rup_' . $rup] = $ek;
                    }
                }
            } catch (\Exception $e) {}

            // Pre-fetch Swakelola Realisasi map (Sum of all payments per package)
            $swakelolaRealMap = [];
            $swakelolaYearMap = []; // Map package ID to year since list API lacks year
            try {
                $resSw = $client->get($this->apiPencatatanSwakelolaRealisasi, ['timeout' => 90]);
                $rowsSw = json_decode($resSw->getBody(), true);
                if (is_array($rowsSw)) {
                    foreach ($rowsSw as $s) {
                        $sid = trim((string)($s['kd_swakelola_pct'] ?? ($s['kd_swakelola'] ?? ($s['kd_paket'] ?? ''))));
                        if ($sid !== '') {
                            if (!isset($swakelolaRealMap[$sid])) $swakelolaRealMap[$sid] = 0;
                            $swakelolaRealMap[$sid] += (float)($s['nilai_realisasi'] ?? ($s['realisasi'] ?? 0));
                            
                            $syr = (string)($s['tahun_anggaran'] ?? ($s['tahun'] ?? ''));
                            if ($syr) $swakelolaYearMap[$sid] = $syr;
                        }
                    }
                }
            } catch (\Exception $e) {}

            foreach ($sources as $label => [$url, $yearField, $valueField, $paguField]) {
                try {
                    $res  = $client->get($url, ['timeout' => 90]);
                    $rows = json_decode($res->getBody(), true);
                    if (!is_array($rows)) $rows = [];
                } catch (\Exception $e) { $rows = []; }

                foreach ($rows as $row) {
                    $pkgId = $row['kd_nontender_pct'] ?? ($row['kd_swakelola_pct'] ?? ($row['kd_nontender'] ?? ($row['kd_tender'] ?? ($row['kd_paket'] ?? ($row['kode_paket'] ?? null)))));
                    $cleanPkgId = trim((string)$pkgId);

                    // Robust Year Check - Prioritize fiscal_year
                    // Restricted Statuses for E-Purchasing
                    if ($label === 'E-Purchasing') {
                        $status = strtoupper(str_replace('_', ' ', (string)($row['status'] ?? '')));
                        $allowed = ['COMPLETED', 'ON ADDENDUM', 'ON PROCESS', 'PAYMENT OUTSIDE SYSTEM'];
                        if (!in_array($status, $allowed)) continue;

                        // Priority 1: order_date year
                        $rawDate = $row['order_date'] ?? '';
                        $yr = (strlen($rawDate) >= 4) ? substr($rawDate, 0, 4) : '';
                        
                        // Priority 2: fallback to other fields
                        if (!$yr) {
                            $yr = (string)($row['fiscal_year'] ?? ($row['tahun_anggaran'] ?? ($row['tahun'] ?? '')));
                        }
                    } else {
                        $yr = (string)($row['fiscal_year'] ?? ($row[$yearField] ?? ($row['tahun'] ?? ($row['tahun_anggaran_paket'] ?? ($row['tahun_paket'] ?? '')))));
                    }
                    
                    // If yr is still a full date string, extract year
                    if (strlen($yr) > 4 && strpos($yr, '-') !== false) {
                        $yr = substr($yr, 0, 4);
                    }
                    
                    // Fallback year for Swakelola from the realisasi map
                    if (!$yr && $cleanPkgId !== '' && isset($swakelolaYearMap[$cleanPkgId])) {
                        $yr = $swakelolaYearMap[$cleanPkgId];
                    }
                    
                    // Final fallback: if no year, assume current year for dashboard summary if it matches label
                    if (!$yr || !is_numeric($yr)) {
                        $yr = date('Y');
                    }

                    if ($yearFilter !== 'all' && (string)$yr !== (string)$yearFilter) continue;

                    // Deteksi Pagu (Anggaran) - Sangat Exhaustive
                    $pagu = (float)($row['pagu'] ?? ($row['Pagu'] ?? ($row['nilai_pagu'] ?? ($row['total_pagu'] ?? ($row['jumlah_pagu'] ?? ($row['pagu_rup'] ?? ($row['nilai_pagu_paket'] ?? ($row['nilai_pekerjaan'] ?? ($row['hps'] ?? ($row[$paguField] ?? 0))))))))));
                    
                    // Deteksi Realisasi (Penyerapan)
                    $nilaiTerkoreksi = (float)($row['nilai_terkoreksi'] ?? 0);
                    $nilaiNegosiasi = (float)($row['nilai_negosiasi'] ?? 0);
                    $realisasi = (float)($row['nilai_kontrak'] ?? ($row['nilai_pemenang'] ?? ($row['nilai_realisasi'] ?? ($row['realisasi'] ?? 0))));
                    if ($realisasi <= 0) $realisasi = ($nilaiTerkoreksi > 0) ? $nilaiTerkoreksi : $nilaiNegosiasi;
                    
                    // Fallback Pagu khusus untuk Pencatatan: Jika Pagu tetap 0, gunakan Realisasi sebagai Pagu minimum
                    if ($pagu <= 0 && stripos($label, 'Pencatatan') !== false) {
                        $pagu = $realisasi;
                    }
                    
                    // Identify unique package ID for deduplication and mapping
                    $pkgId = $row['kd_nontender_pct'] ?? ($row['kd_swakelola_pct'] ?? ($row['kd_nontender'] ?? ($row['kd_tender'] ?? ($row['kd_paket'] ?? ($row['kode_paket'] ?? null)))));

                    // Smart Mapping: Non-Tender (from previous logic)
                    if (stripos($label, 'Non Tender') !== false && $pkgId && isset($nontenderSelesaiMap[$pkgId])) {
                        $realisasi = $nontenderSelesaiMap[$pkgId];
                    }

                    // Smart Mapping: Tender e-Kontrak (Final Values)
                    if (stripos($label, 'Tender') !== false) {
                        $ek = $tenderEkontrakMap[$cleanPkgId] ?? ($tenderEkontrakMap['rup_' . trim((string)($row['rup_code'] ?? ($row['kd_rup'] ?? '')))] ?? []);
                        $hargaFinal = (float)($ek['harga_final'] ?? 0);
                        if ($hargaFinal > 0) {
                            $realisasi = $hargaFinal;
                        }
                    }
                    
                    // Smart Mapping: Swakelola (Match list with realisasi API)
                    $cleanPkgId = trim((string)$pkgId);
                    if (stripos($label, 'Swakelola') !== false && $cleanPkgId !== '' && isset($swakelolaRealMap[$cleanPkgId])) {
                        $realisasi = $swakelolaRealMap[$cleanPkgId];
                    }
                    
                    // Fallback to pagu if no contract value found at all
                    if ($realisasi <= 0) $realisasi = (float)($row[$valueField] ?? 0);
                    
                    $allYears[$yr] = true;

                    // Deduplication Logic: Pagu is counted once, Realisasi is summed
                    $isNewPackage = true;
                    if ($pkgId) {
                        if (isset($seenPackages[$label][$yr][$pkgId])) {
                            $isNewPackage = false;
                        }
                        $seenPackages[$label][$yr][$pkgId] = true;
                    }

                    // Filter: Only "Selesai" for Non-Tender categories
                    $status = $row['status_nontender_pct_ket'] ?? ($row['status_paket'] ?? ($row['status'] ?? ($row['tahapan'] ?? ($row['status_tender'] ?? ($row['status_nontender'] ?? ($row['status_realisasi'] ?? ($row['keterangan'] ?? 'Selesai')))))));
                    if (stripos($label, 'Non Tender') !== false) {
                        if (stripos($status, 'selesai') === false) continue;
                    }

                    if (!isset($byYear[$label][$yr])) $byYear[$label][$yr] = ['pagu' => 0, 'realisasi' => 0, 'count' => 0];
                    if ($isNewPackage) {
                        $byYear[$label][$yr]['pagu'] += $pagu;
                        $byYear[$label][$yr]['count'] += 1;
                        if (!isset($totalPagu[$label])) $totalPagu[$label] = 0;
                        $totalPagu[$label] += $pagu;
                        
                        $byYear[$label][$yr]['realisasi'] += $realisasi;
                        if (!isset($totalRealisasi[$label])) $totalRealisasi[$label] = 0;
                        $totalRealisasi[$label] += $realisasi;
                    }

                    // Aggregate by jenis_pengadaan
                    $jenis = $row['jenis_pengadaan'] ?? ($row['kgr_nama'] ?? ($row['kategori'] ?? ''));
                    if (!$jenis) $jenis = 'Lainnya';

                    if (!isset($byJenis[$jenis][$yr])) $byJenis[$jenis][$yr] = ['pagu' => 0, 'realisasi' => 0, 'count' => 0];
                    if ($isNewPackage) {
                        $byJenis[$jenis][$yr]['pagu'] += $pagu;
                        $byJenis[$jenis][$yr]['count'] += 1;
                        if (!isset($totalJenis[$jenis])) $totalJenis[$jenis] = ['pagu' => 0, 'realisasi' => 0];
                        $totalJenis[$jenis]['pagu'] += $pagu;
                    }
                    $byJenis[$jenis][$yr]['realisasi'] += $realisasi;
                    if (!isset($totalJenis[$jenis])) $totalJenis[$jenis] = ['pagu' => 0, 'realisasi' => 0];
                    $totalJenis[$jenis]['realisasi'] += $realisasi;
                }
            }

            // Sort jenis by total pagu descending
            uasort($totalJenis, function($a, $b) {
                return $b['pagu'] <=> $a['pagu'];
            });

            ksort($allYears);
            $years = array_keys($allYears);

            $result = [
                'years'           => $years,
                'labels'          => array_keys($sources),
                'by_year'         => $byYear,
                'total_pagu'      => $totalPagu,
                'total_realisasi' => $totalRealisasi,
                'jenis_labels'    => array_keys($totalJenis),
                'by_jenis'        => $byJenis,
                'total_jenis'     => $totalJenis,
            ];

            cache()->save($cacheKey, $result, 1800);
        }

        return $this->response->setJSON($result);
    }

    public function getEpurchasingDatatables()
    {
        $currentYear = $this->request->getGet('year') ?? date('Y');
        $userSatker = session()->get('kd_satker');
        $userRole = session()->get('role');

        $cacheKey = "epurchasing_data_v100_{$currentYear}";

        if (!$allData = cache($cacheKey)) {
            $client = \Config\Services::curlrequest();
            try {
                $response = $client->get($this->apiEpurchasing, ['timeout' => 120]);
                $raw = json_decode($response->getBody(), true);
                if (!is_array($raw) || empty($raw)) {
                    return $this->respond([
                        'draw' => intval($this->request->getGet('draw')),
                        'recordsTotal' => 0,
                        'recordsFiltered' => 0,
                        'data' => []
                    ]);
                }

                $allData = [];
                foreach ($raw as $item) {
                    // Restricted Statuses for E-Purchasing (Flexible Check)
                    $status = strtoupper(str_replace('_', ' ', (string)($item['status'] ?? '')));
                    $allowed = ['COMPLETED', 'ON ADDENDUM', 'ON PROCESS', 'PAYMENT OUTSIDE SYSTEM'];
                    if (!in_array($status, $allowed)) continue;

                    // Prioritize order_date as requested by user
                    $rawDate = $item['order_date'] ?? '';
                    $yr = '';
                    if (strlen($rawDate) >= 4) {
                        $yr = substr($rawDate, 0, 4);
                    }
                    if (!$yr) {
                        $yr = (string)($item['fiscal_year'] ?? ($item['tahun_anggaran'] ?? ($item['tahun'] ?? '')));
                    }

                    if ($currentYear !== 'all' && (string)$yr !== (string)$currentYear) continue;

                    $allData[] = [
                        'no_referensi' => $item['order_id'] ?? '-',
                        'nama_paket' => $item['rup_name'] ?? ($item['nama_paket'] ?? '-'),
                        'nama_program' => $item['rup_desc'] ?? '-',
                        'nama_produk' => $item['rup_desc'] ?? '-', // Default
                        'nama_penyedia' => '-', // Default
                        'kode_penyedia' => trim($item['kode_penyedia'] ?? ''),
                        'product_id' => trim($item['product_id'] ?? ''),
                        'nama_satker' => $item['nama_satker'] ?? '-',
                        'kd_satker' => $item['kode_satker'] ?? ($item['kd_satker'] ?? ''),
                        'pagu' => (float)($item['total'] ?? 0),
                        'jumlah' => (int)($item['total_qty'] ?? ($item['count_product'] ?? 0)),
                        'satuan' => 'Unit',
                        'tanggal' => $item['order_date'] ?? '-',
                        'fiscal_year' => $yr ?: '-',
                        'status' => $item['status'] ?? '-',
                        'shipment_status' => $item['shipment_status'] ?? '-',
                        'rup_code' => $item['rup_code'] ?? '-',
                    ];
                }

                if (!empty($allData)) {
                    cache()->save($cacheKey, $allData, 3600);
                }
            } catch (\Exception $e) { $allData = []; }
        }

        // RBAC filter
        if ($userRole === 'satker' && $userSatker) {
            $allData = array_values(array_filter($allData, function($item) use ($userSatker) {
                return ($item['kd_satker'] ?? '') == $userSatker;
            }));
        }

        // DataTables parameters
        $draw = $this->request->getGet('draw');
        $start = (int)($this->request->getGet('start') ?? 0);
        $length = (int)($this->request->getGet('length') ?? 10);
        $search = $this->request->getGet('search')['value'] ?? '';
        $statusFilter = $this->request->getGet('status_filter') ?? '';
        $order = $this->request->getGet('order');

        $totalRecords = count($allData);

        // Filter by status
        if ($statusFilter) {
            $allData = array_values(array_filter($allData, function($item) use ($statusFilter) {
                return strcasecmp($item['status'] ?? '', $statusFilter) === 0;
            }));
        }

        // Search
        if ($search) {
            $allData = array_values(array_filter($allData, function($item) use ($search) {
                return stripos($item['nama_paket'] ?? '', $search) !== false ||
                       stripos($item['nama_satker'] ?? '', $search) !== false ||
                       stripos($item['no_referensi'] ?? '', $search) !== false ||
                       stripos($item['kode_penyedia'] ?? '', $search) !== false ||
                       stripos($item['product_id'] ?? '', $search) !== false;
            }));
        }

        $filteredRecords = count($allData);

        // Sorting
        if (!empty($order)) {
            $colIdx = $order[0]['column'];
            $dir = $order[0]['dir'];
            $cols = $this->request->getGet('columns');
            $colName = $cols[$colIdx]['data'] ?? null;

            if ($colName) {
                usort($allData, function($a, $b) use ($colName, $dir) {
                    $valA = $a[$colName] ?? '';
                    $valB = $b[$colName] ?? '';
                    if (is_numeric($valA) && is_numeric($valB)) return ($dir === 'asc') ? ($valA <=> $valB) : ($valB <=> $valA);
                    return ($dir === 'asc') ? strnatcasecmp((string)$valA, (string)$valB) : strnatcasecmp((string)$valB, (string)$valA);
                });
            }
        }

        // Pagination
        $data = ($length == -1) ? $allData : array_slice($allData, $start, $length);

        // ENRICH visible rows only (Fast)
        foreach ($data as &$item) {
            $kdVendor = $item['kode_penyedia'];
            $prodIdsStr = $item['product_id'];
            if ($kdVendor !== '') {
                $item['nama_penyedia'] = $this->_getInaprocVendorName($kdVendor);
                if ($prodIdsStr !== '') {
                    $item['nama_produk'] = $this->_getInaprocProductNames($kdVendor, $prodIdsStr);
                }
            }
        }

        // Final result with unique statuses metadata
        $uniqueStatuses = array_values(array_unique(array_column($allData, 'status')));

        return $this->response->setJSON([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'unique_statuses' => $uniqueStatuses,
            'data' => array_values($data)
        ]);
    }

    public function getLpseDatatables($type)
    {
        $currentYear = $this->request->getGet('year') ?? date('Y');
        $userSatker = session()->get('kd_satker');
        $userRole = session()->get('role');
        
        $urls = [
            'tender' => $this->apiTender,
            'nontender' => $this->apiNonTender,
            'pencatatan-nontender' => $this->apiPencatatanNonTender,
            'pencatatan-swakelola' => $this->apiPencatatanSwakelola
        ];

        $url = $urls[$type] ?? null;
        if (!$url) return $this->respond([]);

        $cacheKey = "lpse_data_v109_{$type}_{$currentYear}";
        
        if (!$allData = cache($cacheKey)) {
            $client = \Config\Services::curlrequest();
            try {
                // Initialize maps as empty to avoid undefined variable errors in the mapping closure
                $announcementsMap = [];
                $swakelolaRealMap = [];
                $swakelolaYearMap = [];
                
                $response = $client->get($url, ['timeout' => 120]);
                $raw = json_decode($response->getBody(), true);
                if (!is_array($raw) || empty($raw)) {
                    // Do not cache empty results to prevent "locking" the table as empty
                    return $this->respond([
                        'draw' => intval($this->request->getGet('draw')),
                        'recordsTotal' => 0,
                        'recordsFiltered' => 0,
                        'data' => []
                    ]);
                }
 
                // For enrichment
                $announcementsMap = [];
                $ekontrakMap = [];
                if ($type === 'tender') {
                    try {
                        $annResponse = $client->get($this->apiPengumumanTender, ['timeout' => 60]);
                        $annRaw = json_decode($annResponse->getBody(), true);
                        if (is_array($annRaw)) {
                            foreach ($annRaw as $ann) {
                                $kd = (string)($ann['kd_tender'] ?? '');
                                $kdPkt = (string)($ann['kd_pkt_dce'] ?? ($ann['kd_paket'] ?? ''));
                                
                                if ($kd) $announcementsMap[$kd] = $ann;
                                if ($kdPkt) $announcementsMap[$kdPkt] = $ann;
                            }
                        }
                    } catch(\Exception $e) {}
                    
                    // Pre-fetch Tender e-Kontrak map for final values
                    try {
                        $ekResponse = $client->get($this->apiTenderEkontrak, ['timeout' => 60]);
                        $ekRaw = json_decode($ekResponse->getBody(), true);
                        if (is_array($ekRaw)) {
                            foreach ($ekRaw as $ek) {
                                $kd = trim((string)($ek['kd_tender'] ?? ($ek['kd_paket'] ?? '')));
                                $rup = trim((string)($ek['kd_rup'] ?? ''));
                                
                                if ($kd !== '') $ekontrakMap[$kd] = $ek;
                                if ($rup !== '') $ekontrakMap['rup_' . $rup] = $ek;
                            }
                        }
                    } catch(\Exception $e) {}
                }

                if ($type === 'nontender') {
                    try {
                        $selResponse = $client->get($this->apiNonTenderSelesai, ['timeout' => 60]);
                        $selRaw = json_decode($selResponse->getBody(), true);
                        if (is_array($selRaw)) {
                            foreach ($selRaw as $sel) {
                                $kd = $sel['kd_nontender'] ?? ($sel['kd_paket'] ?? null);
                                if ($kd) $announcementsMap[$kd] = $sel;
                            }
                        }
                    } catch(\Exception $e) {}
                    
                    // Pre-fetch Non-Tender e-Kontrak map
                    try {
                        $ntEkResponse = $client->get($this->apiNonTenderEkontrak, ['timeout' => 60]);
                        $ntEkRaw = json_decode($ntEkResponse->getBody(), true);
                        if (is_array($ntEkRaw)) {
                            foreach ($ntEkRaw as $ek) {
                                $kd = trim((string)($ek['kd_nontender'] ?? ($ek['kd_paket'] ?? '')));
                                $rup = trim((string)($ek['kd_rup'] ?? ''));
                                if ($kd !== '') $ekontrakMap[$kd] = $ek;
                                if ($rup !== '') $ekontrakMap['rup_' . $rup] = $ek;
                            }
                        }
                    } catch(\Exception $e) {}
                }

                if ($type === 'pencatatan-swakelola') {
                    try {
                        $listResponse = $client->get($this->apiListPencatatanSwakelola, ['timeout' => 60]);
                        $listRaw = json_decode($listResponse->getBody(), true);
                        if (is_array($listRaw)) {
                            foreach ($listRaw as $l) {
                                $kd = (string)($l['kd_swakelola_pct'] ?? ($l['kd_paket'] ?? ''));
                                if ($kd) $announcementsMap[trim($kd)] = $l;
                            }
                        }
                    } catch(\Exception $e) {}

                    // Pre-fetch Swakelola Realisasi map
                    try {
                        $resSw = $client->get($this->apiPencatatanSwakelolaRealisasi, ['timeout' => 120]);
                        $rowsSw = json_decode($resSw->getBody(), true);
                        if (is_array($rowsSw)) {
                            foreach ($rowsSw as $s) {
                                $sid = trim((string)($s['kd_swakelola_pct'] ?? ($s['kd_swakelola'] ?? ($s['kd_paket'] ?? ''))));
                                $srup = trim((string)($s['kd_rup_paket'] ?? ($s['kd_rup'] ?? '')));
                                
                                if ($sid !== '' || $srup !== '') {
                                    // Store realisasi sum by ID and RUP
                                    if ($sid !== '') {
                                        if (!isset($swakelolaRealMap[$sid])) $swakelolaRealMap[$sid] = 0;
                                        $swakelolaRealMap[$sid] += (float)($s['nilai_realisasi'] ?? ($s['realisasi'] ?? 0));
                                        $announcementsMap[$sid . '_real'] = $s;
                                    }
                                    if ($srup !== '') {
                                        if (!isset($swakelolaRealMap['rup_' . $srup])) $swakelolaRealMap['rup_' . $srup] = 0;
                                        $swakelolaRealMap['rup_' . $srup] += (float)($s['nilai_realisasi'] ?? ($s['realisasi'] ?? 0));
                                        $announcementsMap['rup_' . $srup . '_real'] = $s;
                                    }

                                    $syr = (string)($s['tahun_anggaran'] ?? ($s['tahun'] ?? ''));
                                    if ($syr) {
                                        if ($sid !== '') $swakelolaYearMap[$sid] = $syr;
                                        if ($srup !== '') $swakelolaYearMap['rup_' . $srup] = $syr;
                                    }
                                }
                            }
                        }
                    } catch (\Exception $e) {}
                }
                
                // Perform enrichment and mapping inside the loop before caching
                $allData = array_map(function($item) use ($currentYear, $type, $swakelolaYearMap, $announcementsMap, $swakelolaRealMap, $ekontrakMap) {
                    // Robust Year Check - Prioritize fiscal_year
                    $yr = $item['fiscal_year'] ?? ($item['tahun_anggaran'] ?? ($item['tahun'] ?? null));
                    if ($yr && strlen($yr) > 4 && strpos($yr, '-') !== false) $yr = substr($yr, 0, 4);
                    
                    $kdTender = (string)($item['order_id'] ?? ($item['kd_swakelola_pct'] ?? ($item['kd_nontender_pct'] ?? ($item['kd_nontender'] ?? ($item['kd_tender'] ?? ($item['kd_paket'] ?? ($item['kode_paket'] ?? '')))))));
                    $cleanPkgId = trim($kdTender);

                    // Fallback year for Swakelola
                    if ($type === 'pencatatan-swakelola' && !$yr && $cleanPkgId !== '' && isset($swakelolaYearMap[$cleanPkgId])) {
                        $yr = $swakelolaYearMap[$cleanPkgId];
                    }

                    if ($currentYear !== 'all' && (string)$yr !== (string)$currentYear) return null;

                    $item['_tahun'] = $yr;
                    
                    // ENRICH DATA HERE SO IT GETS CACHED
                    $annInfo = $announcementsMap[$cleanPkgId] ?? [];
                    if (empty($annInfo) && $type === 'tender' && !empty($item['kd_paket'])) {
                        $annInfo = $announcementsMap[(string)$item['kd_paket']] ?? [];
                    }

                    $kdRup = $item['rup_code'] ?? ($item['kd_rup_paket'] ?? ($item['kd_rup'] ?? ($item['kode_rup'] ?? ($annInfo['kd_rup'] ?? ($annInfo['kode_rup'] ?? '-')))));
                    $status = $item['status_paket'] ?? ($item['status_nontender_pct_ket'] ?? ($item['status'] ?? ($item['tahapan'] ?? ($item['status_tender'] ?? ($item['status_nontender'] ?? ($item['status_realisasi'] ?? ($item['keterangan'] ?? ($annInfo['status'] ?? 'Selesai'))))))));

                    $nilaiTerkoreksi = (float)($item['nilai_terkoreksi'] ?? ($annInfo['nilai_terkoreksi'] ?? 0));
                    $nilaiNegosiasi = (float)($item['nilai_negosiasi'] ?? ($annInfo['nilai_negosiasi'] ?? 0));
                    $nilaiKontrak = (float)($item['nilai_kontrak'] ?? ($annInfo['nilai_kontrak'] ?? 0));
                    if ($nilaiKontrak == 0) $nilaiKontrak = ($nilaiTerkoreksi > 0) ? $nilaiTerkoreksi : $nilaiNegosiasi;

                    $mapped = [
                        'kd_rup' => $kdRup,
                        'tahun' => $yr ?? '-',
                        'kd_paket' => $cleanPkgId ?: '-',
                        'nama_paket' => $item['rup_name'] ?? ($item['nama_paket'] ?? ($item['nama_tender'] ?? ($annInfo['nama_paket'] ?? '-'))),
                        'nama_satker' => $item['nama_satker'] ?? ($item['satker'] ?? ($item['nama_organisasi'] ?? ($annInfo['nama_satker'] ?? '-'))),
                        'kd_satker' => $item['kode_satker'] ?? ($item['kd_satker'] ?? ''),
                        'pagu' => (float)($item['total'] ?? ($item['pagu'] ?? ($item['nilai_pagu'] ?? ($item['pagu_paket'] ?? ($annInfo['pagu'] ?? 0))))),
                        'hps' => (float)($item['nilai_hps'] ?? ($item['hps'] ?? ($annInfo['hps'] ?? 0))),
                        'nilai_kontrak' => $nilaiKontrak,
                        'nilai_negosiasi' => $nilaiNegosiasi,
                        'nama_penyedia' => $item['nama_penyedia'] ?? ($annInfo['nama_penyedia'] ?? ($item['pemenang'] ?? ($item['penyedia'] ?? '-'))),
                        'metode' => $item['metode_pengadaan'] ?? ($item['metode'] ?? ($item['cara_pengadaan'] ?? ($annInfo['mtd_pemilihan'] ?? '-'))),
                        'tgl_pemenang' => $item['tgl_penetapan_pemenang'] ?? ($annInfo['tgl_pemenang'] ?? '-'),
                        'status' => $status,
                        'jenis_pengadaan' => '-',
                        'mtd_evaluasi' => '-',
                        'mtd_kualifikasi' => '-',
                        'kontrak_pembayaran' => '-',
                        'kgr_nama' => '-',
                        'kualifikasi_paket' => '-'
                    ];

                    if ($type === 'tender') {
                        if (!empty($annInfo['nama_paket'])) $mapped['nama_paket'] = $annInfo['nama_paket'];
                        $mapped['jenis_pengadaan'] = $annInfo['jenis_pengadaan'] ?? ($item['jenis_pengadaan'] ?? '-');
                        $mapped['status'] = $annInfo['status_tender'] ?? ($annInfo['status'] ?? $mapped['status']);
                        $mapped['metode'] = $annInfo['mtd_pemilihan'] ?? ($annInfo['metode_pengadaan'] ?? $mapped['metode']);
                        
                        $mapped['mtd_evaluasi'] = $annInfo['mtd_evaluasi'] ?? '-';
                        $mapped['mtd_kualifikasi'] = $annInfo['mtd_kualifikasi'] ?? '-';
                        $mapped['kontrak_pembayaran'] = $annInfo['kontrak_pembayaran'] ?? '-';
                        $mapped['kgr_nama'] = $annInfo['kgr_nama'] ?? '-';
                        $mapped['kualifikasi_paket'] = $annInfo['kualifikasi_paket'] ?? '-';
                        
                        // New requested fields
                        $ek = $ekontrakMap[$cleanPkgId] ?? ($ekontrakMap['rup_' . trim((string)$mapped['kd_rup'])] ?? []);
                        $hargaFinal = (float)($ek['harga_final'] ?? 0);
                        
                        $mapped['nilai_penawaran'] = (float)($item['nilai_penawaran'] ?? ($annInfo['nilai_penawaran'] ?? ($item['harga_penawaran'] ?? 0)));
                        $mapped['tgl_kontrak'] = $ek['tgl_kontrak_awal'] ?? ($ek['tgl_kontrak'] ?? ($item['tgl_kontrak'] ?? ($annInfo['tgl_kontrak'] ?? ($item['tanggal_kontrak'] ?? ($annInfo['tanggal_kontrak'] ?? '-')))));
                        $mapped['waktu_pelaksanaan'] = $ek['waktu_pelaksanaan'] ?? ($item['waktu_pelaksanaan'] ?? ($annInfo['waktu_pelaksanaan'] ?? ($item['lama_pekerjaan'] ?? ($annInfo['lama_pekerjaan'] ?? '-'))));
                        
                        if ($hargaFinal > 0) {
                            $mapped['nilai_kontrak'] = $hargaFinal;
                        }
                        
                        // Add more metadata if available from e-kontrak
                        $mapped['no_kontrak'] = $ek['no_kontrak'] ?? '-';
                        if (!empty($ek)) {
                            $mapped['status_kontrak'] = $ek['status_kontrak'] ?? '-';
                        }
                    } elseif ($type === 'nontender') {
                        $ek = $ekontrakMap[$cleanPkgId] ?? ($ekontrakMap['rup_' . trim((string)$mapped['kd_rup'])] ?? []);
                        
                        $mapped['jenis_pengadaan'] = $item['jenis_pengadaan'] ?? ($item['kgr_nama'] ?? '-');
                        $mapped['nama_penyedia'] = $annInfo['nama_penyedia'] ?? ($item['pemenang'] ?? ($item['nama_penyedia'] ?? ($item['penyedia'] ?? '-')));
                        $mapped['kualifikasi_paket'] = $item['kualifikasi_paket'] ?? '-';
                        $mapped['kontrak_pembayaran'] = $annInfo['kontrak_pembayaran'] ?? '-';
                        
                        // New Non-Tender Contract fields
                        $mapped['no_kontrak'] = $ek['no_kontrak'] ?? '-';
                        $mapped['tgl_kontrak'] = $ek['tgl_mulai_pekerjaan'] ?? ($ek['tgl_kontrak'] ?? '-');
                        $mapped['waktu_pelaksanaan'] = $ek['waktu_penyelesaian'] ?? '-';
                        
                        // Smart Realisasi logic: if changed, use ekontrak value
                        if (!empty($ek['alasan_ubah_nilai_kontrak']) && $ek['alasan_ubah_nilai_kontrak'] !== '-') {
                            $mapped['nilai_kontrak'] = (float)($ek['nilai_kontrak'] ?? $mapped['nilai_kontrak']);
                        }
                    } elseif ($type === 'pencatatan-swakelola') {
                        $realInfo = $announcementsMap[$cleanPkgId . '_real'] ?? ($announcementsMap['rup_' . trim($mapped['kd_rup']) . '_real'] ?? []);
                        $mapped['no_pembayaran'] = $item['no_realisasi'] ?? ($realInfo['no_realisasi'] ?? '-');
                        $mapped['ket_realisasi'] = $item['ket_realisasi'] ?? ($realInfo['ket_realisasi'] ?? ($item['keterangan'] ?? '-'));
                        $mapped['nama_pelaksana'] = $item['nama_pelaksana'] ?? ($realInfo['nama_pelaksana'] ?? ($item['nama_satker'] ?? '-'));
                        
                        $realValue = (float)($item['nilai_realisasi'] ?? ($item['total_realisasi'] ?? ($realInfo['nilai_realisasi'] ?? 0)));
                        if ($realValue == 0) {
                            if ($cleanPkgId !== '' && isset($swakelolaRealMap[$cleanPkgId])) $realValue = $swakelolaRealMap[$cleanPkgId];
                            elseif (trim($mapped['kd_rup']) !== '' && isset($swakelolaRealMap['rup_' . trim($mapped['kd_rup'])])) $realValue = $swakelolaRealMap['rup_' . trim($mapped['kd_rup'])];
                        }
                        $mapped['realisasi'] = $realValue;
                    } elseif ($type === 'pencatatan-nontender') {
                        $mapped['realisasi'] = (float)($item['nilai_realisasi'] ?? ($item['realisasi'] ?? 0));
                        $mapped['jenis_realisasi'] = $item['jenis_realisasi'] ?? '-';
                        $mapped['no_pembayaran'] = $item['no_realisasi'] ?? ($item['no_pembayaran'] ?? '-');
                    }

                    return $mapped;
                }, $raw);

                $allData = array_values(array_filter($allData));
                if (!empty($allData)) {
                    cache()->save($cacheKey, $allData, 3600);
                }
            } catch (\Exception $e) { 
                return $this->respond([
                    'draw' => intval($this->request->getGet('draw')),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => 'Gagal mengambil data dari API: ' . $e->getMessage()
                ]);
            }
        }

        // Apply RBAC and Filters (OPD)
        if ($userRole === 'satker' && $userSatker) {
            $allData = array_values(array_filter($allData, function($item) use ($userSatker) {
                return ($item['kd_satker'] ?? ($item['kode_satker'] ?? '')) == $userSatker;
            }));
        }

        $totalRecords = count($allData);

        // Searching
        $search = $this->request->getGet('search')['value'] ?? '';
        if ($search) {
            $allData = array_values(array_filter($allData, function($item) use ($search) {
                return stripos($item['nama_paket'] ?? ($item['nama_tender'] ?? ($item['rup_name'] ?? '')), $search) !== false || 
                       stripos($item['nama_satker'] ?? ($item['satker'] ?? ''), $search) !== false ||
                       stripos((string)($item['kd_paket'] ?? ($item['kd_tender'] ?? '')), $search) !== false;
            }));
        }

        // Status/Metode Filters
        $filterStatus = $this->request->getGet('status');
        $filterMetode = $this->request->getGet('metode');
        if ($filterStatus && $filterStatus !== 'all') {
            $allData = array_values(array_filter($allData, function($item) use ($filterStatus) {
                return strcasecmp($item['status'] ?? '', $filterStatus) === 0;
            }));
        }
        if ($filterMetode && $filterMetode !== 'all') {
            $allData = array_values(array_filter($allData, function($item) use ($filterMetode) {
                return strcasecmp($item['metode'] ?? '', $filterMetode) === 0;
            }));
        }

        $filteredRecords = count($allData);
        
        // Sorting
        $order = $this->request->getGet('order');
        if ($order) {
            $orderColIndex = intval($order[0]['column']);
            $orderDir = $order[0]['dir'];
            
            $cols = ['kd_rup', 'tahun', 'kd_paket', 'nama_paket', 'nama_satker', 'pagu'];
            if ($type === 'tender') {
                $cols = array_merge($cols, ['hps', 'nilai_penawaran', 'nilai_kontrak', 'nama_penyedia', 'metode', 'tgl_pemenang', 'tgl_kontrak', 'no_kontrak', 'status']);
            } elseif ($type === 'nontender') {
                $cols = array_merge($cols, ['hps', 'nilai_kontrak', 'nilai_negosiasi', 'nama_penyedia', 'metode', 'jenis_pengadaan', 'tgl_kontrak', 'no_kontrak', 'status']);
            } elseif ($type === 'pencatatan-nontender') {
                $cols = array_merge($cols, ['realisasi', 'nama_penyedia', 'jenis_realisasi', 'no_pembayaran', 'status']);
            } elseif ($type === 'pencatatan-swakelola') {
                $cols = array_merge($cols, ['realisasi', 'metode', 'no_pembayaran', 'ket_realisasi', 'status']);
            }
            
            $orderCol = $cols[$orderColIndex] ?? 'kd_rup';
            
            usort($allData, function($a, $b) use ($orderCol, $orderDir) {
                $valA = $a[$orderCol] ?? '';
                $valB = $b[$orderCol] ?? '';
                
                if (is_numeric($valA) && is_numeric($valB)) {
                    return ($orderDir === 'asc') ? ($valA <=> $valB) : ($valB <=> $valA);
                }
                return ($orderDir === 'asc') ? strnatcasecmp((string)$valA, (string)$valB) : strnatcasecmp((string)$valB, (string)$valA);
            });
        }

        // PAGING FIRST
        $start = intval($this->request->getGet('start') ?? 0);
        $length = intval($this->request->getGet('length') ?? 10);
        if ($length == -1) $pageData = $allData;
        else $pageData = array_slice($allData, $start, $length);

        // Data is already enriched and mapped inside the cache/fetch block
        $enrichedData = $pageData;

        return $this->respond([
            'draw' => intval($this->request->getGet('draw')),
            'recordsTotal' => intval($totalRecords),
            'recordsFiltered' => intval($filteredRecords),
            'data' => $enrichedData
        ]);
    }


    public function clearCache()
    {
        cache()->clean();
        return redirect()->back()->with('success', 'Berhasil membersihkan cache. Seluruh data akan ditarik ulang dari API.');
    }

    private function _getInaprocVendorName($kode)
    {
        $dir = WRITEPATH . 'inaproc/vendors/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $file = $dir . $kode . '.json';

        if (file_exists($file)) {
            $saved = json_decode(file_get_contents($file), true);
            if (isset($saved['nama_penyedia'])) return $saved['nama_penyedia'];
        }

        $client = \Config\Services::curlrequest();
        $token = 'inprc743d77f5b94343e6981757875dfc7f00';
        try {
            $url = "https://data.inaproc.id/api/v1/ekatalog/penyedia-detail?kode_penyedia={$kode}";
            $res = $client->get($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                ],
                'timeout' => 10
            ]);
            $body = json_decode($res->getBody(), true);
            if (isset($body['data'][0]['nama_penyedia'])) {
                $name = $body['data'][0]['nama_penyedia'];
                file_put_contents($file, json_encode(['nama_penyedia' => $name]));
                return $name;
            }
        } catch (\Exception $e) {}
        return '-';
    }

    private function _getInaprocProductNames($kodePenyedia, $prodIdsStr)
    {
        $dir = WRITEPATH . 'inaproc/products/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $file = $dir . $kodePenyedia . '.json';

        $prodMap = [];
        if (file_exists($file)) {
            $prodMap = json_decode(file_get_contents($file), true) ?: [];
        }

        $searchIds = array_map('trim', explode(',', $prodIdsStr));
        $missingIds = [];
        foreach ($searchIds as $sid) {
            if (!isset($prodMap[$sid])) $missingIds[] = $sid;
        }

        // If we have missing IDs and the file is old or non-existent, fetch from API
        if (!empty($missingIds)) {
            $client = \Config\Services::curlrequest();
            $token = getenv('INAPROC_BEARER_TOKEN') ?: 'inprc743d77f5b94343e6981757875dfc7f00';
            try {
                $url = "https://data.inaproc.id/api/v1/ekatalog/list-produk-penyedia?kode_penyedia={$kodePenyedia}&limit=1000";
                $res = $client->get($url, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                    ],
                    'timeout' => 15
                ]);
                $body = json_decode($res->getBody(), true);
                if (isset($body['data']) && is_array($body['data'])) {
                    foreach ($body['data'] as $p) {
                        $pid = $p['kd_produk'] ?? null;
                        $pname = $p['nama_produk'] ?? null;
                        if ($pid) $prodMap[$pid] = $pname;
                    }
                    file_put_contents($file, json_encode($prodMap));
                }
            } catch (\Exception $e) {}
        }

        $found = [];
        foreach ($searchIds as $sid) {
            if (isset($prodMap[$sid])) {
                $found[] = $prodMap[$sid];
            } else {
                $found[] = "Produk ID: " . substr($sid, 0, 8) . "...";
            }
        }

        return count($found) > 0 ? implode(', ', $found) : '-';
    }
}
