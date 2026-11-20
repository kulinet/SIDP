<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Models\MasterAnggaranModel;
use App\Models\RupPenyediaModel;
use App\Models\RupSwakelolaModel;
use App\Models\LpseTenderModel;
use App\Models\LpseNontenderModel;
use App\Models\EpurchasingModel;
use App\Models\LpsePencatatanNontenderModel;
use App\Models\LpsePencatatanSwakelolaModel;
use App\Models\MonevProyekModel;
use App\Models\MonevJadwalModel;
use App\Models\MonevHarianModel;
use App\Models\MonevDokumenModel;
use App\Services\InaprocApiService;

class Dashboard extends BaseController
{
    use ResponseTrait;

    public function getFilterOptions($type)
    {
        $currentYear = $this->request->getGet('year') ?? date('Y');
        $userRole = session()->get('role');

        if ($type === 'epurchasing') {
            $model = new \App\Models\EpurchasingModel();
            $builder = $model->where('tahun', $currentYear)
                ->where("status IS NOT NULL AND status != '' AND status != '-'")
                ->where("order_date IS NOT NULL AND order_date != '' AND order_date != '-' AND order_date != '0000-00-00' AND order_date NOT LIKE '0000-00-00%'");
            if ($userRole === 'ppk') {
                $builder->whereIn('rup_code', $this->_getPpkRupCodes());
            }
            $data = $builder->select('nama_satker')->distinct()->findAll();
            $opds = array_values(array_unique(array_filter(array_column($data, 'nama_satker'))));

            $userSatker = session()->get('nama_satker');
            if (($userRole === 'satker' || $userRole === 'opd') && $userSatker) {
                $opds = [$userSatker];
            } else {
                sort($opds);
            }
            return $this->response->setJSON($opds);
        }

        $model = ($type === 'swakelola') ? new RupSwakelolaModel() : new RupPenyediaModel();

        $builder = $model->where('tahun', $currentYear);
        if ($userRole === 'ppk') {
            $builder->whereIn('kd_rup', $this->_getPpkRupCodes());
        }
        $data = $builder->findAll();

        $opds = array_values(array_unique(array_filter(array_column($data, 'nama_satker'))));
        $jenis = array_values(array_unique(array_filter(array_column($data, 'jenis_pengadaan'))));
        $metodeField = ($type === 'penyedia') ? 'metode_pengadaan' : 'tipe_swakelola';
        $metodes = array_values(array_unique(array_filter(array_column($data, $metodeField))));
        $sumberDana = [];
        if ($type === 'penyedia') {
            $sumberDana = array_values(array_unique(array_filter(array_column($data, 'sumber_dana'))));
            sort($sumberDana);
        }

        $userSatker = session()->get('nama_satker');

        if (($userRole === 'satker' || $userRole === 'opd') && $userSatker) {
            $opds = [$userSatker];
        } else {
            sort($opds);
        }
        sort($jenis);
        sort($metodes);

        return $this->response->setJSON([
            'opds' => $opds,
            'jenis' => $jenis,
            'metodes' => $metodes,
            'sumber_dana' => $sumberDana
        ]);
    }

    private $apiPenyedia;
    private $apiSwakelola;
    private $apiMasterSatker;
    private $apiKegiatanMaster;

    // Specific for RUP OPD Monitoring
    private $apiRupAnggaranPenyedia;
    private $apiRupAnggaranSwakelola;

    // Data Pengadaan APIs
    private $apiEpurchasing;
    private $apiTender;
    private $apiTenderEkontrak;
    private $apiNonTender;
    private $apiPencatatanNonTender;
    private $apiPencatatanSwakelola;
    private $apiPencatatanSwakelolaRealisasi;
    private $apiNonTenderEkontrak;
    private $apiPengumumanTender;
    private $apiNonTenderSelesai;
    private $apiListPencatatanSwakelola;
    private $inaprocApiService;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $baseUrl = env('AJIP_API_BASE_URL', 'https://ajip-api.cloud');
        $this->apiPenyedia = $baseUrl . '/api/data/perbandingan-rup-penyedia';
        $this->apiSwakelola = $baseUrl . '/api/data/perbandingan-swakelola';
        $this->apiMasterSatker = $baseUrl . '/api/data/master-satker';
        $this->apiKegiatanMaster = $baseUrl . '/api/data/kegiatan-master';
        $this->apiRupAnggaranPenyedia = $baseUrl . '/api/data/rup-paket-anggaran';
        $this->apiRupAnggaranSwakelola = $baseUrl . '/api/data/rup-paket-swakelola';
        $this->apiEpurchasing = $baseUrl . '/api/data/paket-epurchasing';
        $this->apiTender = $baseUrl . '/api/data/tender-selesai';
        $this->apiTenderEkontrak = $baseUrl . '/api/data/list-tender-kontrak';
        $this->apiNonTender = $baseUrl . '/api/data/nontender-selesai';
        $this->apiPencatatanNonTender = $baseUrl . '/api/data/pencatatan-nontender-realisasi';
        $this->apiPencatatanSwakelola = $baseUrl . '/api/data/list-pencatatan-swakelola';
        $this->apiPencatatanSwakelolaRealisasi = $baseUrl . '/api/data/encatatan-swakelola-realisasi';
        $this->apiNonTenderEkontrak = $baseUrl . '/api/data/list-nontender-ekontrak';
        $this->apiPengumumanTender = $baseUrl . '/api/data/pengumuman-tender';
        $this->apiNonTenderSelesai = $baseUrl . '/api/data/nontender-selesai';
        $this->apiListPencatatanSwakelola = $baseUrl . '/api/data/list-pencatatan-swakelola';
        $this->inaprocApiService = new InaprocApiService();
    }

    public function index()
    {
        $agent = $this->request->getUserAgent();
        if ($agent->isMobile() && $this->request->getGet('force_desktop') !== '1') {
            return view('dashboard/mobile_index', [
                'title' => 'AJIP Mobile Portal',
                'user' => session()->get(),
            ]);
        }

        // Redirect desktop users to statistik
        return redirect()->to(base_url('dashboard/statistik'));
    }

    public function statistik()
    {
        try {
            $chartModel = new \App\Models\DashboardChartModel();
            $charts = $chartModel->getWithMenu();
        } catch (\Throwable $e) {
            $charts = [];
        }

        $db = \Config\Database::connect();
        $yearsPenyedia = $db->table('rup_penyedia')->select('tahun')->distinct()->get()->getResultArray();
        $yearsSwakelola = $db->table('rup_swakelola')->select('tahun')->distinct()->get()->getResultArray();
        $allYears = array_merge(
            array_column($yearsPenyedia, 'tahun'),
            array_column($yearsSwakelola, 'tahun')
        );
        $availableYears = array_filter(array_unique($allYears));
        rsort($availableYears);
        if (empty($availableYears)) {
            $availableYears = [date('Y')];
        }

        return view('dashboard/statistik', [
            'title' => 'Statistik Dasbor',
            'user' => session()->get(),
            'charts' => $charts,
            'availableYears' => $availableYears
        ]);
    }

    public function layanan()
    {
        return view('dashboard/mobile_layanan', [
            'title' => 'Semua Layanan AJIP',
            'user' => session()->get()
        ]);
    }

    public function debugGap()
    {
        $db = \Config\Database::connect();
        // Static query tanpa user input — aman, tapi tetap pakai query untuk konsistensi
        $res = $db->query("SELECT SUM(realisasi) as sum_real, COUNT(*) as cnt FROM lpse_pencatatan_nontender WHERE realisasi > 0")->getRow();
        return $this->response->setJSON(['sum' => $res->sum_real, 'count' => $res->cnt]);
    }

    public function getDashboardSummary($type)
    {
        $currentYear = $this->request->getGet('year') ?? date('Y');
        $userSatker = trim(session()->get('kd_satker') ?? '');
        $userRole = trim(strtolower(session()->get('role') ?? ''));

        $model = ($type === 'swakelola') ? new RupSwakelolaModel() : new RupPenyediaModel();

        $builder = $model->where('tahun', $currentYear);
        if ($userRole === 'ppk') {
            $builder->whereIn('kd_rup', $this->_getPpkRupCodes());
        } elseif (in_array($userRole, ['satker', 'opd']) && $userSatker) {
            $builder->where('kd_satker', $userSatker);
        }

        $data = $builder->findAll();

        $matrix = [];
        $jenisList = [];
        $metodeList = [];
        $opdList = [];

        $metodeField = ($type === 'penyedia') ? 'metode_pengadaan' : 'tipe_swakelola';

        foreach ($data as $item) {
            $opd = $item['nama_satker'] ?: 'N/A';
            $jenis = ($type === 'penyedia') ? ($item['jenis_pengadaan'] ?: 'N/A') : 'Swakelola';
            $metode = $item[$metodeField] ?: 'N/A';

            if (!isset($matrix[$opd]))
                $matrix[$opd] = [];
            if (!isset($matrix[$opd][$jenis]))
                $matrix[$opd][$jenis] = [];
            if (!isset($matrix[$opd][$jenis][$metode]))
                $matrix[$opd][$jenis][$metode] = ['pagu' => 0, 'paket' => 0];

            $matrix[$opd][$jenis][$metode]['pagu'] += (float) $item['pagu'];
            $matrix[$opd][$jenis][$metode]['paket'] += 1;

            $opdList[$opd] = true;
            $jenisList[$jenis] = true;
            $metodeList[$metode] = true;
        }

        return $this->respond([
            'matrix' => $matrix,
            'opds' => array_keys($opdList),
            'jenis' => array_keys($jenisList),
            'metodes' => array_keys($metodeList),
            'type' => $type
        ]);
    }


    public function penyedia()
    {
        $db = \Config\Database::connect();
        $years = $db->table('rup_penyedia')->select('tahun')->distinct()->get()->getResultArray();
        $availableYears = array_filter(array_column($years, 'tahun'));
        rsort($availableYears);
        if (empty($availableYears)) {
            $availableYears = [date('Y')];
        }

        $data = [
            'title' => 'Data RUP Penyedia',
            'user' => session()->get(),
            'type' => 'penyedia',
            'availableYears' => $availableYears
        ];
        return view('dashboard/table', $data);
    }

    public function swakelola()
    {
        $db = \Config\Database::connect();
        $years = $db->table('rup_swakelola')->select('tahun')->distinct()->get()->getResultArray();
        $availableYears = array_filter(array_column($years, 'tahun'));
        rsort($availableYears);
        if (empty($availableYears)) {
            $availableYears = [date('Y')];
        }

        $data = [
            'title' => 'Data RUP Swakelola',
            'user' => session()->get(),
            'type' => 'swakelola',
            'availableYears' => $availableYears
        ];
        return view('dashboard/table', $data);
    }

    public function rupOpd()
    {
        $currentYear = $this->request->getGet('year') ?? date('Y');
        $selectedOpd = $this->request->getGet('opd') ?? 'all';
        $db = \Config\Database::connect();

        // 1. Available Years
        $yearsP = $db->table('rup_penyedia')->select('tahun')->distinct()->get()->getResultArray();
        $yearsS = $db->table('rup_swakelola')->select('tahun')->distinct()->get()->getResultArray();
        $availableYears = array_filter(array_unique(array_merge(array_column($yearsP, 'tahun'), array_column($yearsS, 'tahun'))));
        rsort($availableYears);
        if (empty($availableYears)) {
            $availableYears = [date('Y')];
        }

        $cacheKey = "rup_full_profile_and_analysis_v8_{$currentYear}";

        if (!$cachedData = cache($cacheKey)) {
            // === A. REKAPITULASI & PROFIL RUP ===
            $sqlPenyedia = "
                SELECT 
                    nama_satker,
                    MAX(kd_satker) as kd_satker,
                    COUNT(*) as jml_paket,
                    SUM(pagu) as total_pagu,
                    SUM(CASE WHEN LOWER(jenis_pengadaan) LIKE '%konsultansi%' THEN 1 ELSE 0 END) as jk_cnt,
                    SUM(CASE WHEN LOWER(jenis_pengadaan) LIKE '%konstruksi%' THEN 1 ELSE 0 END) as pk_cnt,
                    SUM(CASE WHEN LOWER(jenis_pengadaan) LIKE '%jasa lainnya%' THEN 1 ELSE 0 END) as jl_cnt,
                    SUM(CASE WHEN LOWER(jenis_pengadaan) LIKE '%barang%' AND LOWER(jenis_pengadaan) NOT LIKE '%jasa%' AND LOWER(jenis_pengadaan) NOT LIKE '%konstruksi%' THEN 1 ELSE 0 END) as b_cnt,
                    SUM(CASE WHEN LOWER(metode_pengadaan) LIKE '%pengadaan langsung%' THEN 1 ELSE 0 END) as pl_cnt,
                    SUM(CASE WHEN LOWER(metode_pengadaan) LIKE '%penunjukan langsung%' THEN 1 ELSE 0 END) as juksung_cnt,
                    SUM(CASE WHEN LOWER(metode_pengadaan) LIKE '%purchasing%' THEN 1 ELSE 0 END) as epurchasing_cnt,
                    SUM(CASE WHEN LOWER(metode_pengadaan) LIKE '%seleksi%' THEN 1 ELSE 0 END) as seleksi_cnt,
                    SUM(CASE WHEN LOWER(metode_pengadaan) LIKE '%tender%' THEN 1 ELSE 0 END) as tender_cnt,
                    SUM(CASE WHEN LOWER(metode_pengadaan) LIKE '%dikecualikan%' THEN 1 ELSE 0 END) as dikecualikan_cnt
                FROM rup_penyedia
                WHERE tahun = ?
                GROUP BY nama_satker
            ";

            $sqlSwakelola = "
                SELECT 
                    nama_satker,
                    MAX(kd_satker) as kd_satker,
                    COUNT(*) as jml_paket,
                    SUM(pagu) as total_pagu
                FROM rup_swakelola
                WHERE tahun = ?
                GROUP BY nama_satker
            ";

            $pRows = $db->query($sqlPenyedia, [$currentYear])->getResultArray();
            $sRows = $db->query($sqlSwakelola, [$currentYear])->getResultArray();

            $pMap = [];
            foreach ($pRows as $pr) {
                $pMap[trim($pr['nama_satker'])] = $pr;
            }

            $sMap = [];
            foreach ($sRows as $sr) {
                $sMap[trim($sr['nama_satker'])] = $sr;
            }

            $allSatkers = array_unique(array_merge(array_keys($pMap), array_keys($sMap)));
            sort($allSatkers);

            $summaryRekap = [];
            foreach ($allSatkers as $satkerName) {
                $p = $pMap[$satkerName] ?? [
                    'kd_satker' => '',
                    'jml_paket' => 0,
                    'total_pagu' => 0,
                    'b_cnt' => 0,
                    'jl_cnt' => 0,
                    'pk_cnt' => 0,
                    'jk_cnt' => 0,
                    'pl_cnt' => 0,
                    'juksung_cnt' => 0,
                    'epurchasing_cnt' => 0,
                    'seleksi_cnt' => 0,
                    'tender_cnt' => 0,
                    'dikecualikan_cnt' => 0
                ];
                $s = $sMap[$satkerName] ?? [
                    'kd_satker' => '',
                    'jml_paket' => 0,
                    'total_pagu' => 0
                ];

                $kdSatker = $p['kd_satker'] ?: ($s['kd_satker'] ?: '');
                $paguP = (float) ($p['total_pagu'] ?? 0);
                $paguS = (float) ($s['total_pagu'] ?? 0);
                $totalPagu = $paguP + $paguS;

                $paketP = (int) ($p['jml_paket'] ?? 0);
                $paketS = (int) ($s['jml_paket'] ?? 0);
                $totalPaket = $paketP + $paketS;

                $pctPaguP = $totalPagu > 0 ? round(($paguP / $totalPagu) * 100, 2) : 0;
                $pctPaguS = $totalPagu > 0 ? round(($paguS / $totalPagu) * 100, 2) : 0;

                $pctPaketP = $totalPaket > 0 ? round(($paketP / $totalPaket) * 100, 2) : 0;
                $pctPaketS = $totalPaket > 0 ? round(($paketS / $totalPaket) * 100, 2) : 0;

                $summaryRekap[] = [
                    'kd_satker' => $kdSatker,
                    'nama_satker' => $satkerName,
                    'pagu_penyedia' => $paguP,
                    'pct_pagu_penyedia' => $pctPaguP,
                    'pagu_swakelola' => $paguS,
                    'pct_pagu_swakelola' => $pctPaguS,
                    'total_pagu' => $totalPagu,
                    'paket_penyedia' => $paketP,
                    'pct_paket_penyedia' => $pctPaketP,
                    'paket_swakelola' => $paketS,
                    'pct_paket_swakelola' => $pctPaketS,
                    'total_paket' => $totalPaket,
                    'b_cnt' => (int) ($p['b_cnt'] ?? 0),
                    'jl_cnt' => (int) ($p['jl_cnt'] ?? 0),
                    'pk_cnt' => (int) ($p['pk_cnt'] ?? 0),
                    'jk_cnt' => (int) ($p['jk_cnt'] ?? 0),
                    'pl_cnt' => (int) ($p['pl_cnt'] ?? 0),
                    'juksung_cnt' => (int) ($p['juksung_cnt'] ?? 0),
                    'epurchasing_cnt' => (int) ($p['epurchasing_cnt'] ?? 0),
                    'seleksi_cnt' => (int) ($p['seleksi_cnt'] ?? 0),
                    'tender_cnt' => (int) ($p['tender_cnt'] ?? 0),
                    'dikecualikan_cnt' => (int) ($p['dikecualikan_cnt'] ?? 0)
                ];
            }

            // === B. ANALISIS KETERUMUMAN & ANOMALI RUP (vs Master Anggaran) ===
            $masterModel = new MasterAnggaranModel();
            $penyediaModel = new RupPenyediaModel();
            $swakelolaModel = new RupSwakelolaModel();

            $kegiatanMaster = $masterModel->where('tahun', $currentYear)->findAll();
            $rupPenyedia = $penyediaModel->where('tahun', $currentYear)->findAll();
            $rupSwakelola = $swakelolaModel->where('tahun', $currentYear)->findAll();

            foreach ($rupPenyedia as &$p)
                $p['_tipe'] = 'Penyedia';
            foreach ($rupSwakelola as &$s)
                $s['_tipe'] = 'Swakelola';
            $allRup = array_merge($rupPenyedia, $rupSwakelola);

            $aiService = new \App\Services\NonPengadaanAiService();
            $aiVerdicts = $aiService->loadVerdicts();

            $satkerPrograms = [];
            $kegiatanStats = [];
            foreach ($kegiatanMaster as $k) {
                $kd = $k['kd_satker'] ?: '0';
                $progKey = trim($k['kd_program_str'] ?: 'UNKNOWN');
                if (!isset($satkerPrograms[$kd]))
                    $satkerPrograms[$kd] = [];
                if ($progKey !== 'UNKNOWN')
                    $satkerPrograms[$kd][] = $progKey;

                if (!isset($kegiatanStats[$kd])) {
                    $kegiatanStats[$kd] = ['total_pagu' => 0, 'program_list' => [], 'nama_satker' => $k['nama_satker']];
                }
                $kegiatanStats[$kd]['total_pagu'] += (float) $k['pagu_program'];
                if (!isset($kegiatanStats[$kd]['program_list'][$progKey])) {
                    $kegiatanStats[$kd]['program_list'][$progKey] = ['name' => $k['nama_program'], 'pagu' => 0];
                }
                $kegiatanStats[$kd]['program_list'][$progKey]['pagu'] += (float) $k['pagu_program'];
            }

            $rupStats = [];
            $seenRup = [];
            foreach ($allRup as $item) {
                $kd = $item['kd_satker'] ?: '0';
                $kdRup = $item['kd_rup'];
                $isNewRup = false;
                if ($kdRup) {
                    if (!isset($seenRup[$kdRup])) {
                        $seenRup[$kdRup] = true;
                        $isNewRup = true;
                    }
                } else {
                    $isNewRup = true;
                }

                $paguItem = (float) $item['pagu'];
                $mak = trim($item['mak'] ?? '');
                $progKey = 'UNKNOWN';

                if ($mak && isset($satkerPrograms[$kd])) {
                    $cleanMak = str_replace(['.', '-'], '', $mak);
                    foreach ($satkerPrograms[$kd] as $validCode) {
                        $cleanCode = str_replace(['.', '-'], '', $validCode);
                        if ($cleanCode === '')
                            continue;
                        if (str_starts_with($cleanMak, $cleanCode) || str_contains($mak, $validCode)) {
                            $progKey = $validCode;
                            break;
                        }
                    }
                }

                if (!isset($rupStats[$kd])) {
                    $rupStats[$kd] = [
                        'pagu' => 0,
                        'paket' => 0,
                        'programs' => [],
                        'package_list' => [],
                        'nama_satker_fallback' => $item['nama_satker']
                    ];
                }

                if ($isNewRup) {
                    $rupStats[$kd]['pagu'] += $paguItem;
                    $rupStats[$kd]['paket'] += 1;

                    $nonPengadaanInfo = $this->classifyNonPengadaan($item['nama_paket'], $mak, $item['_tipe'], $kdRup, $aiVerdicts);

                    $rupStats[$kd]['programs'][$progKey] = ($rupStats[$kd]['programs'][$progKey] ?? 0) + $paguItem;
                    $rupStats[$kd]['package_list'][$progKey][] = [
                        'kd_rup' => $kdRup ?: '-',
                        'nama_paket' => $item['nama_paket'],
                        'pagu' => $paguItem,
                        'tipe' => $item['_tipe'],
                        'mak' => $mak,
                        'is_non_pengadaan' => $nonPengadaanInfo['is_non_pengadaan'],
                        'kategori_non_pengadaan' => $nonPengadaanInfo['kategori'],
                        'alasan_non_pengadaan' => $nonPengadaanInfo['alasan'],
                        'rekomendasi_non_pengadaan' => $nonPengadaanInfo['rekomendasi']
                    ];
                }
            }

            $allAnalysisSatkerKeys = array_unique(array_merge(
                array_keys($kegiatanStats),
                array_keys($rupStats)
            ));

            $summaryAnalisis = [];
            foreach ($allAnalysisSatkerKeys as $kd) {
                $ks = $kegiatanStats[$kd] ?? [
                    'total_pagu' => 0,
                    'program_list' => [],
                    'nama_satker' => ($rupStats[$kd]['nama_satker_fallback'] ?? 'Satker Tidak Terdaftar di Master')
                ];
                $rs = $rupStats[$kd] ?? ['pagu' => 0, 'paket' => 0, 'programs' => [], 'package_list' => []];

                $progDetails = [];
                $hasAnomalySatker = false;
                $satkerNonCount = 0;
                $satkerNonPagu = 0;

                foreach ($ks['program_list'] as $pk => $pData) {
                    $paguRup = $rs['programs'][$pk] ?? 0;
                    $percent = $pData['pagu'] > 0 ? round(($paguRup / $pData['pagu']) * 100, 2) : 0;
                    if ($paguRup > $pData['pagu']) {
                        $hasAnomalySatker = true;
                    }
                    $pkPackages = $rs['package_list'][$pk] ?? [];
                    $progNonCount = 0;
                    $progNonPagu = 0;
                    foreach ($pkPackages as $pkgItem) {
                        if (!empty($pkgItem['is_non_pengadaan'])) {
                            $progNonCount++;
                            $progNonPagu += (float)$pkgItem['pagu'];
                        }
                    }
                    $satkerNonCount += $progNonCount;
                    $satkerNonPagu += $progNonPagu;

                    $progDetails[] = [
                        'code' => $pk,
                        'name' => $pData['name'],
                        'pagu_master' => $pData['pagu'],
                        'pagu_rup' => $paguRup,
                        'percent' => $percent,
                        'non_pengadaan_count' => $progNonCount,
                        'non_pengadaan_pagu' => $progNonPagu,
                        'packages' => $pkPackages
                    ];
                }

                if (!empty($rs['package_list']['UNKNOWN'])) {
                    $paguUnknown = $rs['programs']['UNKNOWN'] ?? 0;
                    $hasAnomalySatker = true;
                    $unkPackages = $rs['package_list']['UNKNOWN'];
                    $progNonCount = 0;
                    $progNonPagu = 0;
                    foreach ($unkPackages as $pkgItem) {
                        if (!empty($pkgItem['is_non_pengadaan'])) {
                            $progNonCount++;
                            $progNonPagu += (float)$pkgItem['pagu'];
                        }
                    }
                    $satkerNonCount += $progNonCount;
                    $satkerNonPagu += $progNonPagu;

                    $progDetails[] = [
                        'code' => 'UNKNOWN',
                        'name' => 'Paket Lainnya (Tidak Terpetakan ke Program Master)',
                        'pagu_master' => 0,
                        'pagu_rup' => $paguUnknown,
                        'percent' => 0,
                        'non_pengadaan_count' => $progNonCount,
                        'non_pengadaan_pagu' => $progNonPagu,
                        'packages' => $unkPackages
                    ];
                }

                if ($satkerNonCount > 0) {
                    $hasAnomalySatker = true;
                }

                $summaryAnalisis[] = [
                    'kd_satker' => $kd,
                    'kd_satker_str' => (string) $kd,
                    'nama_satker' => $ks['nama_satker'],
                    'rup_pagu' => $rs['pagu'],
                    'rup_paket' => $rs['paket'],
                    'master_pagu' => $ks['total_pagu'],
                    'percent' => $ks['total_pagu'] > 0 ? round(($rs['pagu'] / $ks['total_pagu']) * 100, 2) : 0,
                    'has_anomaly' => $hasAnomalySatker,
                    'has_non_pengadaan' => ($satkerNonCount > 0),
                    'non_pengadaan_count' => $satkerNonCount,
                    'non_pengadaan_pagu' => $satkerNonPagu,
                    'details' => $progDetails
                ];
            }

            usort($summaryAnalisis, function ($a, $b) {
                return $a['percent'] <=> $b['percent'];
            });

            $cachedData = [
                'summaryRekap' => $summaryRekap,
                'summaryAnalisis' => $summaryAnalisis
            ];

            cache()->save($cacheKey, $cachedData, 3600);
        }

        $summaryRekap = $cachedData['summaryRekap'];
        $summaryAnalisis = $cachedData['summaryAnalisis'];

        // Available OPD list for the dropdown filter
        $availableOpds = array_column($summaryRekap, 'nama_satker');
        sort($availableOpds);

        // RBAC filtering
        $userRole = trim(strtolower(session()->get('role') ?? ''));
        $userSatker = trim(session()->get('kd_satker') ?? '');
        $userNamaSatker = trim(session()->get('nama_satker') ?? session()->get('nama_lengkap') ?? '');
        $isRestrictedRole = in_array($userRole, ['satker', 'opd']);

        if ($isRestrictedRole) {
            $summaryRekap = array_values(array_filter($summaryRekap, function ($s) use ($userSatker, $userNamaSatker) {
                if ($userNamaSatker) {
                    return stripos(trim($s['nama_satker']), $userNamaSatker) !== false || strcasecmp(trim($s['nama_satker']), $userNamaSatker) === 0;
                }
                return trim($s['kd_satker']) == $userSatker;
            }));

            $summaryAnalisis = array_values(array_filter($summaryAnalisis, function ($s) use ($userSatker, $userNamaSatker) {
                if ($userNamaSatker) {
                    return stripos(trim($s['nama_satker']), $userNamaSatker) !== false || strcasecmp(trim($s['nama_satker']), $userNamaSatker) === 0;
                }
                return trim($s['kd_satker']) == $userSatker;
            }));
        } elseif ($selectedOpd && $selectedOpd !== 'all') {
            $summaryRekap = array_values(array_filter($summaryRekap, function ($s) use ($selectedOpd) {
                return trim($s['nama_satker']) === trim($selectedOpd);
            }));

            $summaryAnalisis = array_values(array_filter($summaryAnalisis, function ($s) use ($selectedOpd) {
                return trim($s['nama_satker']) === trim($selectedOpd);
            }));
        }

        // Grand Totals Rekapitulasi
        $totalsRekap = [
            'pagu_penyedia' => array_sum(array_column($summaryRekap, 'pagu_penyedia')),
            'pagu_swakelola' => array_sum(array_column($summaryRekap, 'pagu_swakelola')),
            'total_pagu' => array_sum(array_column($summaryRekap, 'total_pagu')),
            'paket_penyedia' => array_sum(array_column($summaryRekap, 'paket_penyedia')),
            'paket_swakelola' => array_sum(array_column($summaryRekap, 'paket_swakelola')),
            'total_paket' => array_sum(array_column($summaryRekap, 'total_paket')),
            'b_cnt' => array_sum(array_column($summaryRekap, 'b_cnt')),
            'jl_cnt' => array_sum(array_column($summaryRekap, 'jl_cnt')),
            'pk_cnt' => array_sum(array_column($summaryRekap, 'pk_cnt')),
            'jk_cnt' => array_sum(array_column($summaryRekap, 'jk_cnt')),
            'pl_cnt' => array_sum(array_column($summaryRekap, 'pl_cnt')),
            'juksung_cnt' => array_sum(array_column($summaryRekap, 'juksung_cnt')),
            'epurchasing_cnt' => array_sum(array_column($summaryRekap, 'epurchasing_cnt')),
            'seleksi_cnt' => array_sum(array_column($summaryRekap, 'seleksi_cnt')),
            'tender_cnt' => array_sum(array_column($summaryRekap, 'tender_cnt')),
            'dikecualikan_cnt' => array_sum(array_column($summaryRekap, 'dikecualikan_cnt')),
        ];

        $totalsRekap['pct_pagu_penyedia'] = $totalsRekap['total_pagu'] > 0 ? round(($totalsRekap['pagu_penyedia'] / $totalsRekap['total_pagu']) * 100, 2) : 0;
        $totalsRekap['pct_pagu_swakelola'] = $totalsRekap['total_pagu'] > 0 ? round(($totalsRekap['pagu_swakelola'] / $totalsRekap['total_pagu']) * 100, 2) : 0;
        $totalsRekap['pct_paket_penyedia'] = $totalsRekap['total_paket'] > 0 ? round(($totalsRekap['paket_penyedia'] / $totalsRekap['total_paket']) * 100, 2) : 0;
        $totalsRekap['pct_paket_swakelola'] = $totalsRekap['total_paket'] > 0 ? round(($totalsRekap['paket_swakelola'] / $totalsRekap['total_paket']) * 100, 2) : 0;

        // Grand Totals Analisis
        $totalMasterPagu = array_sum(array_column($summaryAnalisis, 'master_pagu'));
        $totalRupPagu = array_sum(array_column($summaryAnalisis, 'rup_pagu'));
        $avgProgress = $totalMasterPagu > 0 ? round(($totalRupPagu / $totalMasterPagu) * 100, 2) : 0;
        $anomalyCount = count(array_filter($summaryAnalisis, function ($s) {
            return !empty($s['has_anomaly']);
        }));

        $pctPaguPenyediaFromMaster = $totalMasterPagu > 0 ? round(($totalsRekap['pagu_penyedia'] / $totalMasterPagu) * 100, 2) : 0;
        $pctPaguSwakelolaFromMaster = $totalMasterPagu > 0 ? round(($totalsRekap['pagu_swakelola'] / $totalMasterPagu) * 100, 2) : 0;

        $totalsRekap['pct_pagu_penyedia_from_master'] = $pctPaguPenyediaFromMaster;
        $totalsRekap['pct_pagu_swakelola_from_master'] = $pctPaguSwakelolaFromMaster;

        $totalNonPaket = array_sum(array_column($summaryAnalisis, 'non_pengadaan_count'));
        $totalNonPagu = array_sum(array_column($summaryAnalisis, 'non_pengadaan_pagu'));
        $opdWithNonCount = count(array_filter($summaryAnalisis, function ($s) {
            return !empty($s['has_non_pengadaan']);
        }));

        $totalsAnalisis = [
            'total_master' => $totalMasterPagu,
            'total_rup' => $totalRupPagu,
            'avg_progress' => $avgProgress,
            'pct_penyedia_master' => $pctPaguPenyediaFromMaster,
            'pct_swakelola_master' => $pctPaguSwakelolaFromMaster,
            'anomaly_count' => $anomalyCount,
            'total_non_pengadaan_paket' => $totalNonPaket,
            'total_non_pengadaan_pagu' => $totalNonPagu,
            'opd_with_non_pengadaan_count' => $opdWithNonCount
        ];

        return view('dashboard/rup_opd', [
            'title' => 'Rekapitulasi & Analisis RUP OPD',
            'user' => session()->get(),
            'summaryRekap' => $summaryRekap,
            'summaryAnalisis' => $summaryAnalisis,
            'totalsRekap' => $totalsRekap,
            'totalsAnalisis' => $totalsAnalisis,
            'currentYear' => $currentYear,
            'selectedOpd' => $selectedOpd,
            'availableYears' => $availableYears,
            'availableOpds' => $availableOpds,
            'isRestrictedRole' => $isRestrictedRole
        ]);
    }

    public function getKajiUlangRupData()
    {
        $cacheKey = 'kajiulang_rup_api_data';
        $data = cache($cacheKey);

        if (!$data) {
            $client = \Config\Services::curlrequest();
            $baseUrl = rtrim(env('AJIP_API_BASE_URL', 'https://ajip-api.cloud'), '/');
            try {
                $response = $client->get("{$baseUrl}/api/data/kajiulang-rup", [
                    'timeout' => 20,
                    'verify' => false,
                ]);
                $body = $response->getBody();
                $decoded = json_decode($body, true);
                if (is_array($decoded)) {
                    $data = isset($decoded['data']) && is_array($decoded['data']) ? $decoded['data'] : $decoded;
                    cache()->save($cacheKey, $data, 3600);
                }
            } catch (\Exception $e) {
                log_message('error', 'Failed fetching kajiulang-rup: ' . $e->getMessage());
            }
        }

        return $this->response->setJSON($data ?? []);
    }

    public function getData($type)
    {
        $currentYear = $this->request->getGet('year') ?? date('Y');
        $userSatker = session()->get('kd_satker');
        $userRole = session()->get('role');

        $model = ($type === 'swakelola') ? new RupSwakelolaModel() : new RupPenyediaModel();

        $builder = $model->where('tahun', $currentYear);
        if ($userRole === 'satker' && $userSatker) {
            $builder->where('kd_satker', $userSatker);
        }

        $data = $builder->findAll();

        $metodeField = ($type === 'penyedia') ? 'metode_pengadaan' : 'tipe_swakelola';
        $formatted = [];
        foreach ($data as $item) {
            $formatted[] = [
                'pagu' => (float) $item['pagu'],
                'kd_satker' => $item['kd_satker'],
                'nama_satker' => $item['nama_satker'],
                'jenis_pengadaan' => ($type === 'penyedia' ? $item['jenis_pengadaan'] : 'Swakelola'),
                $metodeField => $item[$metodeField],
                'volume_pekerjaan' => $item['volume_pekerjaan'],
                'urarian_pekerjaan' => $item['uraian_pekerjaan'],
                'spesifikasi_pekerjaan' => $item['spesifikasi_pekerjaan'] ?? '-',
                'mak' => $item['mak'] ?? '-',
                'tgl_awal_pemilihan' => ($type === 'penyedia' ? $item['tgl_awal_pemilihan'] : $item['tgl_awal_pelaksanaan']),
                'tgl_akhir_pemilihan' => ($type === 'penyedia' ? $item['tgl_akhir_pemilihan'] : $item['tgl_akhir_pelaksanaan']),
                'kd_rup' => $item['kd_rup'],
                'nama_paket' => $item['nama_paket'],
                'status_umumkan_rup' => $item['status_umumkan_rup']
            ];
        }

        return $this->respond($formatted);
    }

    public function getDatatables($type)
    {
        $currentYear = $this->request->getGet('year') ?? date('Y');
        $userSatker = session()->get('kd_satker');
        $userRole = strtolower(session()->get('role') ?? '');

        $model = ($type === 'swakelola') ? new RupSwakelolaModel() : new RupPenyediaModel();
        $builder = $model->where('tahun', $currentYear);

        // RBAC: paksa filter ke RUP jika role ppk, atau ke satker jika role satker/opd
        if ($userRole === 'ppk') {
            $builder->whereIn('kd_rup', $this->_getPpkRupCodes());
        } elseif (in_array($userRole, ['satker', 'opd'])) {
            // Gunakan nama_satker dari sesi (lebih konsisten lintas tabel)
            $userNamaSatker = session()->get('nama_satker');
            if ($userNamaSatker) {
                $builder->where('nama_satker', $userNamaSatker);
            } elseif ($userSatker) {
                // Fallback ke kd_satker jika nama_satker belum tersedia
                $builder->where('kd_satker', $userSatker);
            }
        } else {
            // Terapkan filter OPD dari dropdown jika user admin/operator
            $opdFilter = $this->request->getGet('opd') ?? '';
            if ($opdFilter && $opdFilter !== 'all') {
                $builder->where('nama_satker', $opdFilter);
            }
        }

        $jenisFilter = $this->request->getGet('jenis') ?? '';
        if ($jenisFilter && $jenisFilter !== 'all') {
            $builder->where('jenis_pengadaan', $jenisFilter);
        }

        $metodeFilter = $this->request->getGet('metode') ?? '';
        if ($metodeFilter && $metodeFilter !== 'all') {
            if ($type === 'penyedia') {
                $builder->where('metode_pengadaan', $metodeFilter);
            } else {
                $builder->where('tipe_swakelola', $metodeFilter);
            }
        }

        $sumberDanaFilter = $this->request->getGet('sumber_dana') ?? '';
        if ($sumberDanaFilter && $sumberDanaFilter !== 'all' && $type === 'penyedia') {
            $builder->where('sumber_dana', $sumberDanaFilter);
        }

        $statusPdnFilter = $this->request->getGet('status_pdn') ?? '';
        if ($statusPdnFilter && $statusPdnFilter !== 'all' && $type === 'penyedia') {
            $builder->where('status_pdn', $statusPdnFilter);
        }

        $draw = $this->request->getGet('draw');
        $start = (int) ($this->request->getGet('start') ?? 0);
        $length = (int) ($this->request->getGet('length') ?? 10);
        $search = $this->request->getGet('search')['value'] ?? '';

        if ($search) {
            $builder->groupStart()
                ->like('nama_paket', $search)
                ->orLike('nama_satker', $search)
                ->orLike('kd_rup', $search)
                ->orLike('mak', $search)
                ->orLike('uraian_pekerjaan', $search);
            if ($type === 'penyedia') {
                $builder->orLike('spesifikasi_pekerjaan', $search);
            }
            $builder->groupEnd();
        }

        $totalRecords = $builder->countAllResults(false);
        $recordsFiltered = $totalRecords;

        if ($length != -1)
            $builder->limit($length, $start);
        $data = $builder->get()->getResultArray();

        $mapped = array_map(function ($item) use ($type) {
            $item['urarian_pekerjaan'] = $item['uraian_pekerjaan'] ?? ($item['nama_paket'] ?? '-');
            $item['spesifikasi_pekerjaan'] = $item['spesifikasi_pekerjaan'] ?? '-';
            $item['mak'] = $item['mak'] ?? '-';
            if ($type === 'swakelola') {
                $item['tgl_awal_pemilihan'] = $item['tgl_awal_pelaksanaan'] ?? ($item['tgl_awal_pemilihan'] ?? '-');
                $item['tgl_akhir_pemilihan'] = $item['tgl_akhir_pelaksanaan'] ?? ($item['tgl_akhir_pemilihan'] ?? '-');
            } else {
                $item['tgl_awal_pemilihan'] = $item['tgl_awal_pemilihan'] ?? '-';
                $item['tgl_akhir_pemilihan'] = $item['tgl_akhir_pemilihan'] ?? '-';
            }
            return $item;
        }, $data);

        return $this->response->setJSON([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $recordsFiltered,
            'data' => $mapped
        ]);
    }

    public function comparisons()
    {
        ini_set('memory_limit', '512M');
        $userSatker = trim(session()->get('kd_satker') ?? '');
        $userRole = trim(strtolower(session()->get('role') ?? ''));

        $penyediaModel = new \App\Models\RupPenyediaModel();
        $swakelolaModel = new \App\Models\RupSwakelolaModel();

        // Ambil data langsung dari database untuk efisiensi
        $rawPenyedia = $penyediaModel->select('kd_satker, tahun as tahun_anggaran, pagu, metode_pengadaan, jenis_pengadaan')->findAll();
        $rawSwakelola = $swakelolaModel->select('kd_satker, tahun as tahun_anggaran, pagu, tipe_swakelola')->findAll();

        $aggregate = function ($rawData, $type) use ($userSatker, $userRole) {
            $yearly = [];
            $dist1 = [];
            $dist2 = [];
            $field1 = ($type === 'penyedia') ? 'metode_pengadaan' : 'tipe_swakelola';
            $field2 = 'jenis_pengadaan';

            foreach ($rawData as $item) {
                // Strict Satker Filtering
                if (in_array($userRole, ['satker', 'opd']) && $userSatker) {
                    $kd = trim($item['kd_satker'] ?? ($item['kode_satker'] ?? ($item['kd_organisasi'] ?? '')));
                    if ($kd != $userSatker)
                        continue;
                }

                // Try all common year fields
                $yr = $item['tahun_anggaran'] ?? ($item['tahun_anggaran_dana'] ?? ($item['tahun'] ?? 'Unknown'));
                if ($yr === 'Unknown' || !$yr)
                    continue;

                if (!isset($yearly[$yr]))
                    $yearly[$yr] = ['pagu' => 0, 'paket' => 0];
                $yearly[$yr]['pagu'] += (float) ($item['pagu'] ?? 0);
                $yearly[$yr]['paket'] += 1;

                $cat1 = $item[$field1] ?? 'Lainnya';
                $cat2 = $item[$field2] ?? 'Lainnya';
                if (!$cat1)
                    $cat1 = 'Lainnya';
                if (!$cat2)
                    $cat2 = 'Lainnya';

                if (!isset($dist1[$cat1][$yr]))
                    $dist1[$cat1][$yr] = 0;
                $dist1[$cat1][$yr] += 1;

                if (!isset($dist2[$cat2][$yr]))
                    $dist2[$cat2][$yr] = 0;
                $dist2[$cat2][$yr] += 1;
            }
            ksort($yearly);
            return ['yearly' => $yearly, 'dist1' => $dist1, 'dist2' => $dist2];
        };

        $data = [
            'title' => 'Dashboard Perbandingan Tahunan',
            'user' => session()->get(),
            'statsPenyedia' => $aggregate($rawPenyedia, 'penyedia'),
            'statsSwakelola' => $aggregate($rawSwakelola, 'swakelola')
        ];

        return view('dashboard/comparisons', $data);
    }

    public function getChartData($id)
    {
        $chartModel = new \App\Models\DashboardChartModel();
        $menuModel = new \App\Models\DynamicMenuModel();

        $chart = $chartModel->find($id);
        if (!$chart)
            return $this->failNotFound('Chart not found');

        $menu = $menuModel->find($chart['menu_id']);
        if (!$menu)
            return $this->failNotFound('Source menu not found');

        // Fetch API data
        $client = \Config\Services::curlrequest();
        try {
            $response = $client->get($menu['api_url']);
            $data = json_decode($response->getBody(), true);
            if (!is_array($data))
                return $this->respond([]);

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
        $userRole = trim(strtolower(session()->get('role') ?? ''));

        $model = ($type === 'swakelola') ? new \App\Models\RupSwakelolaModel() : new \App\Models\RupPenyediaModel();

        $builder = $model->where('tahun', $year);
        if (in_array($userRole, ['satker', 'opd']) && $userSatker) {
            $builder->where('kd_satker', $userSatker);
        }

        $filtered = $builder->findAll();

        if (empty($filtered))
            return redirect()->back()->with('error', 'Data tahun ' . $year . ' tidak ditemukan.');

        if ($type === 'penyedia') {
            $filename = 'RUP_Penyedia_' . $year . '.xls';
            header("Content-Type: application/vnd.ms-excel; charset=utf-8");
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Pragma: no-cache");
            header("Expires: 0");

            echo '<table border="1">';
            echo '<thead>';
            echo '<tr style="background-color: #696cff; color: white; font-weight: bold; text-align: center;">';
            echo '<th>Kode RUP</th>';
            echo '<th>Nama Paket</th>';
            echo '<th>Satker</th>';
            echo '<th>Pagu</th>';
            echo '<th>Jenis Pengadaan</th>';
            echo '<th>Metode Pengadaan</th>';
            echo '<th>Sumber Dana</th>';
            echo '<th>MAK</th>';
            echo '<th>Volume</th>';
            echo '<th>Uraian</th>';
            echo '<th>Spesifikasi Pekerjaan</th>';
            echo '<th>Mulai</th>';
            echo '<th>Selesai</th>';
            echo '<th>PDN</th>';
            echo '<th>Status</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';

            foreach ($filtered as $row) {
                $pagu = (float)($row['pagu'] ?? 0);
                $uraian = htmlspecialchars($row['uraian_pekerjaan'] ?? $row['nama_paket'] ?? '-');
                $spesifikasi = htmlspecialchars($row['spesifikasi_pekerjaan'] ?? '-');
                $namaPaket = htmlspecialchars($row['nama_paket'] ?? '-');
                $namaSatker = htmlspecialchars($row['nama_satker'] ?? '-');
                $jenis = htmlspecialchars($row['jenis_pengadaan'] ?? '-');
                $metode = htmlspecialchars($row['metode_pengadaan'] ?? '-');
                $sumberDana = htmlspecialchars($row['sumber_dana'] ?? '-');
                $mak = htmlspecialchars($row['mak'] ?? '-');
                $volume = htmlspecialchars($row['volume_pekerjaan'] ?? '-');
                $mulai = htmlspecialchars($row['tgl_awal_pemilihan'] ?? '-');
                $selesai = htmlspecialchars($row['tgl_akhir_pemilihan'] ?? '-');
                $pdn = htmlspecialchars($row['status_pdn'] ?? '-');
                $status = htmlspecialchars($row['status_umumkan_rup'] ?? 'Terumumkan');

                echo '<tr>';
                echo '<td style="vnd.ms-excel.numberformat:@">' . htmlspecialchars($row['kd_rup'] ?? '') . '</td>';
                echo '<td>' . $namaPaket . '</td>';
                echo '<td>' . $namaSatker . '</td>';
                echo '<td>' . $pagu . '</td>';
                echo '<td>' . $jenis . '</td>';
                echo '<td>' . $metode . '</td>';
                echo '<td>' . $sumberDana . '</td>';
                echo '<td style="vnd.ms-excel.numberformat:@">' . $mak . '</td>';
                echo '<td>' . $volume . '</td>';
                echo '<td>' . $uraian . '</td>';
                echo '<td>' . $spesifikasi . '</td>';
                echo '<td>' . $mulai . '</td>';
                echo '<td>' . $selesai . '</td>';
                echo '<td>' . $pdn . '</td>';
                echo '<td>' . $status . '</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
            exit;
        } else {
            $swakelolaTipeMapping = [
                'Tipe I' => 'Swakelola Tipe I - Direncanakan, dilaksanakan, dan diawasi oleh K/L/PD sendiri',
                'Tipe II' => 'Swakelola Tipe II - Direncanakan dan diawasi oleh K/L/PD penanggung jawab, dilaksanakan oleh K/L/PD lain',
                'Tipe III' => 'Swakelola Tipe III - Direncanakan dan diawasi oleh K/L/PD penanggung jawab, dilaksanakan oleh Ormas',
                'Tipe IV' => 'Swakelola Tipe IV - Direncanakan dan diawasi oleh K/L/PD penanggung jawab/Pokmas, dilaksanakan oleh Pokmas',
                'Tipe 1' => 'Swakelola Tipe I - Direncanakan, dilaksanakan, dan diawasi oleh K/L/PD sendiri',
                'Tipe 2' => 'Swakelola Tipe II - Direncanakan dan diawasi oleh K/L/PD penanggung jawab, dilaksanakan oleh K/L/PD lain',
                'Tipe 3' => 'Swakelola Tipe III - Direncanakan dan diawasi oleh K/L/PD penanggung jawab, dilaksanakan oleh Ormas',
                'Tipe 4' => 'Swakelola Tipe IV - Direncanakan dan diawasi oleh K/L/PD penanggung jawab/Pokmas, dilaksanakan oleh Pokmas',
                '1' => 'Swakelola Tipe I - Direncanakan, dilaksanakan, dan diawasi oleh K/L/PD sendiri',
                '2' => 'Swakelola Tipe II - Direncanakan dan diawasi oleh K/L/PD penanggung jawab, dilaksanakan oleh K/L/PD lain',
                '3' => 'Swakelola Tipe III - Direncanakan dan diawasi oleh K/L/PD penanggung jawab, dilaksanakan oleh Ormas',
                '4' => 'Swakelola Tipe IV - Direncanakan dan diawasi oleh K/L/PD penanggung jawab/Pokmas, dilaksanakan oleh Pokmas'
            ];

            $filename = 'RUP_Swakelola_' . $year . '.xls';
            header("Content-Type: application/vnd.ms-excel; charset=utf-8");
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Pragma: no-cache");
            header("Expires: 0");

            echo '<table border="1">';
            echo '<thead>';
            echo '<tr style="background-color: #696cff; color: white; font-weight: bold; text-align: center;">';
            echo '<th>Kode RUP</th>';
            echo '<th>Nama Paket</th>';
            echo '<th>Satker</th>';
            echo '<th>Pagu</th>';
            echo '<th>Tipe Swakelola</th>';
            echo '<th>MAK</th>';
            echo '<th>Volume</th>';
            echo '<th>Uraian</th>';
            echo '<th>Mulai</th>';
            echo '<th>Selesai</th>';
            echo '<th>Status</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';

            foreach ($filtered as $row) {
                $pagu = (float)($row['pagu'] ?? 0);
                $uraian = htmlspecialchars($row['uraian_pekerjaan'] ?? $row['nama_paket'] ?? '-');
                $namaPaket = htmlspecialchars($row['nama_paket'] ?? '-');
                $namaSatker = htmlspecialchars($row['nama_satker'] ?? '-');
                
                $rawTipe = $row['tipe_swakelola'] ?? '';
                $tipe = htmlspecialchars($swakelolaTipeMapping[$rawTipe] ?? $rawTipe ?: '-');
                
                $mak = htmlspecialchars($row['mak'] ?? '-');
                $volume = htmlspecialchars($row['volume_pekerjaan'] ?? '-');
                $mulai = htmlspecialchars($row['tgl_awal_pelaksanaan'] ?? $row['tgl_awal_pemilihan'] ?? '-');
                $selesai = htmlspecialchars($row['tgl_akhir_pelaksanaan'] ?? $row['tgl_akhir_pemilihan'] ?? '-');
                $status = htmlspecialchars($row['status_umumkan_rup'] ?? 'Terumumkan');

                echo '<tr>';
                echo '<td style="vnd.ms-excel.numberformat:@">' . htmlspecialchars($row['kd_rup'] ?? '') . '</td>';
                echo '<td>' . $namaPaket . '</td>';
                echo '<td>' . $namaSatker . '</td>';
                echo '<td>' . $pagu . '</td>';
                echo '<td>' . $tipe . '</td>';
                echo '<td style="vnd.ms-excel.numberformat:@">' . $mak . '</td>';
                echo '<td>' . $volume . '</td>';
                echo '<td>' . $uraian . '</td>';
                echo '<td>' . $mulai . '</td>';
                echo '<td>' . $selesai . '</td>';
                echo '<td>' . $status . '</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
            exit;
        }
    }

    public function exportRupOpd()
    {
        $currentYear = $this->request->getGet('year') ?? date('Y');
        $cacheKey = "rup_rekap_profil_opd_v1_{$currentYear}";

        $summary = cache($cacheKey);
        if (!$summary) {
            $this->rupOpd();
            $summary = cache($cacheKey);
        }

        if (!$summary)
            return redirect()->back()->with('error', 'Gagal memproses data laporan.');

        // Apply strict satker filtering
        $userRole = trim(strtolower(session()->get('role') ?? ''));
        $userSatker = trim(session()->get('kd_satker') ?? '');
        $userNamaSatker = trim(session()->get('nama_satker') ?? session()->get('nama_lengkap') ?? '');

        if (in_array($userRole, ['satker', 'opd'])) {
            $summary = array_values(array_filter($summary, function ($s) use ($userSatker, $userNamaSatker) {
                if ($userNamaSatker) {
                    return stripos(trim($s['nama_satker']), $userNamaSatker) !== false || strcasecmp(trim($s['nama_satker']), $userNamaSatker) === 0;
                }
                return trim($s['kd_satker'] ?? '') == $userSatker;
            }));
        } else {
            $selectedOpd = $this->request->getGet('opd');
            if ($selectedOpd && $selectedOpd !== 'all') {
                $summary = array_values(array_filter($summary, function ($s) use ($selectedOpd) {
                    return trim($s['nama_satker']) === trim($selectedOpd);
                }));
            }
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="Rekapitulasi_Profil_RUP_OPD_' . $currentYear . '.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM

        fputcsv($output, [
            'No',
            'Satker / OPD',
            'Pagu Penyedia',
            '% Pagu Penyedia',
            'Pagu Swakelola',
            '% Pagu Swakelola',
            'Total Pagu',
            'Paket Penyedia',
            '% Paket Penyedia',
            'Paket Swakelola',
            '% Paket Swakelola',
            'Total Paket',
            'Barang (B)',
            'Jasa Lainnya (JL)',
            'Pekerjaan Konstruksi (PK)',
            'Jasa Konsultansi (JK)',
            'Pengadaan Langsung (PL)',
            'Penunjukan Langsung (Juksung)',
            'E-Purchasing',
            'Seleksi',
            'Tender',
            'Dikecualikan'
        ]);

        $no = 1;
        foreach ($summary as $s) {
            fputcsv($output, [
                $no++,
                $s['nama_satker'],
                $s['pagu_penyedia'],
                $s['pct_pagu_penyedia'] . '%',
                $s['pagu_swakelola'],
                $s['pct_pagu_swakelola'] . '%',
                $s['total_pagu'],
                $s['paket_penyedia'],
                $s['pct_paket_penyedia'] . '%',
                $s['paket_swakelola'],
                $s['pct_paket_swakelola'] . '%',
                $s['total_paket'],
                $s['b_cnt'],
                $s['jl_cnt'],
                $s['pk_cnt'],
                $s['jk_cnt'],
                $s['pl_cnt'],
                $s['juksung_cnt'],
                $s['epurchasing_cnt'],
                $s['seleksi_cnt'],
                $s['tender_cnt'],
                $s['dikecualikan_cnt']
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Export RUP Detail – Penyedia & Swakelola dalam satu tabel Excel dengan kolom selaras
     */
    public function exportRupDetail()
    {
        $currentYear = $this->request->getGet('year') ?? date('Y');

        $penyediaModel  = new \App\Models\RupPenyediaModel();
        $swakelolaModel = new \App\Models\RupSwakelolaModel();

        $builderP = $penyediaModel->where('tahun', $currentYear);
        $builderS = $swakelolaModel->where('tahun', $currentYear);

        // RBAC filtering
        $userRole       = trim(strtolower(session()->get('role') ?? ''));
        $userSatker     = trim(session()->get('kd_satker') ?? '');
        $userNamaSatker = trim(session()->get('nama_satker') ?? session()->get('nama_lengkap') ?? '');

        if (in_array($userRole, ['satker', 'opd'])) {
            if ($userNamaSatker) {
                $builderP->groupStart()->like('nama_satker', $userNamaSatker)->groupEnd();
                $builderS->groupStart()->like('nama_satker', $userNamaSatker)->groupEnd();
            } elseif ($userSatker) {
                $builderP->where('kd_satker', $userSatker);
                $builderS->where('kd_satker', $userSatker);
            }
        } else {
            $selectedOpd = $this->request->getGet('opd') ?? '';
            if ($selectedOpd && $selectedOpd !== 'all') {
                $builderP->where('nama_satker', $selectedOpd);
                $builderS->where('nama_satker', $selectedOpd);
            }
        }

        $penyediaList  = $builderP->orderBy('nama_satker', 'ASC')->orderBy('pagu', 'DESC')->findAll();
        $swakelolaList = $builderS->orderBy('nama_satker', 'ASC')->orderBy('pagu', 'DESC')->findAll();

        if (empty($penyediaList) && empty($swakelolaList)) {
            return redirect()->back()->with('error', 'Data RUP tahun ' . $currentYear . ' tidak ditemukan.');
        }

        $swakelolaTipeMapping = [
            'Tipe I'   => 'Swakelola Tipe I',
            'Tipe II'  => 'Swakelola Tipe II',
            'Tipe III' => 'Swakelola Tipe III',
            'Tipe IV'  => 'Swakelola Tipe IV',
            'Tipe 1'   => 'Swakelola Tipe I',
            'Tipe 2'   => 'Swakelola Tipe II',
            'Tipe 3'   => 'Swakelola Tipe III',
            'Tipe 4'   => 'Swakelola Tipe IV',
            '1'        => 'Swakelola Tipe I',
            '2'        => 'Swakelola Tipe II',
            '3'        => 'Swakelola Tipe III',
            '4'        => 'Swakelola Tipe IV',
        ];

        $filename = 'RUP_Detail_Penyedia_Swakelola_' . $currentYear . '.xls';
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Pragma: no-cache");
        header("Expires: 0");

        $totalKolom = 17;

        echo '<html><head><meta charset="utf-8"></head><body>';
        echo '<table border="1">';
        echo '<thead>';
        echo '<tr><th colspan="' . $totalKolom . '" style="background-color: #1E2538; color: white; font-size: 14px; font-weight: bold; text-align: center;">DATA RUP PENYEDIA &amp; SWAKELOLA TAHUN ' . htmlspecialchars($currentYear) . '</th></tr>';
        echo '<tr style="background-color: #1E2538; color: white; font-weight: bold; text-align: center;">';
        echo '<th>No</th>';
        echo '<th>Tipe RUP</th>';
        echo '<th>Kode RUP</th>';
        echo '<th>Nama Paket</th>';
        echo '<th>Satker / OPD</th>';
        echo '<th>Pagu (Rp)</th>';
        echo '<th>Jenis Pengadaan</th>';
        echo '<th>Metode Pengadaan / Tipe Swakelola</th>';
        echo '<th>Sumber Dana</th>';
        echo '<th>MAK</th>';
        echo '<th>Volume</th>';
        echo '<th>Uraian</th>';
        echo '<th>Spesifikasi</th>';
        echo '<th>Tgl Awal</th>';
        echo '<th>Tgl Akhir</th>';
        echo '<th>PDN</th>';
        echo '<th>Status</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        $no = 1;

        // --- Data Penyedia ---
        foreach ($penyediaList as $row) {
            echo '<tr>';
            echo '<td>' . $no++ . '</td>';
            echo '<td style="background-color: #DBEAFE;">Penyedia</td>';
            echo '<td style="vnd.ms-excel.numberformat:@">' . htmlspecialchars($row['kd_rup'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['nama_paket'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['nama_satker'] ?? '-') . '</td>';
            echo '<td>' . (float)($row['pagu'] ?? 0) . '</td>';
            echo '<td>' . htmlspecialchars($row['jenis_pengadaan'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['metode_pengadaan'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['sumber_dana'] ?? '-') . '</td>';
            echo '<td style="vnd.ms-excel.numberformat:@">' . htmlspecialchars($row['mak'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['volume_pekerjaan'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['uraian_pekerjaan'] ?? $row['nama_paket'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['spesifikasi_pekerjaan'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['tgl_awal_pemilihan'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['tgl_akhir_pemilihan'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['status_pdn'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['status_umumkan_rup'] ?? 'Terumumkan') . '</td>';
            echo '</tr>';
        }

        // --- Data Swakelola (kolom diselaraskan dengan penyedia) ---
        foreach ($swakelolaList as $row) {
            $rawTipe = $row['tipe_swakelola'] ?? '';
            $tipe = $swakelolaTipeMapping[$rawTipe] ?? $rawTipe ?: '-';

            echo '<tr>';
            echo '<td>' . $no++ . '</td>';
            echo '<td style="background-color: #DCFCE7;">Swakelola</td>';
            echo '<td style="vnd.ms-excel.numberformat:@">' . htmlspecialchars($row['kd_rup'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['nama_paket'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['nama_satker'] ?? '-') . '</td>';
            echo '<td>' . (float)($row['pagu'] ?? 0) . '</td>';
            echo '<td></td>'; // Jenis Pengadaan — tidak ada untuk swakelola
            echo '<td>' . htmlspecialchars($tipe) . '</td>'; // Metode/Tipe Swakelola
            echo '<td></td>'; // Sumber Dana — tidak ada untuk swakelola
            echo '<td style="vnd.ms-excel.numberformat:@">' . htmlspecialchars($row['mak'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['volume_pekerjaan'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['uraian_pekerjaan'] ?? $row['nama_paket'] ?? '-') . '</td>';
            echo '<td></td>'; // Spesifikasi — tidak ada untuk swakelola
            echo '<td>' . htmlspecialchars($row['tgl_awal_pelaksanaan'] ?? $row['tgl_awal_pemilihan'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['tgl_akhir_pelaksanaan'] ?? $row['tgl_akhir_pemilihan'] ?? '-') . '</td>';
            echo '<td></td>'; // PDN — tidak ada untuk swakelola
            echo '<td>' . htmlspecialchars($row['status_umumkan_rup'] ?? 'Terumumkan') . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
        echo '</body></html>';
        exit;
    }

    public function classifyNonPengadaan($namaPaket, $mak = '', $tipe = '', $kdRup = '', $aiVerdicts = [])
    {
        // 0. Cek apakah ada keputusan AI yang tersimpan di tabel rup_klasifikasi_ai
        if ($kdRup && !empty($aiVerdicts[(string)$kdRup])) {
            $verdict = $aiVerdicts[(string)$kdRup];
            $currentFp = md5(mb_strtolower(trim($namaPaket ?? '')) . '|' . trim($mak ?? ''));
            if ($verdict['fingerprint'] === $currentFp) {
                return [
                    'is_non_pengadaan' => (bool)$verdict['is_non_pengadaan'],
                    'kategori' => $verdict['kategori'] ?: ($verdict['is_non_pengadaan'] ? 'Non-Pengadaan' : 'Pengadaan Normal'),
                    'alasan' => '[AI Terverifikasi] ' . ($verdict['alasan'] ?: 'Diverifikasi oleh model AI.'),
                    'rekomendasi' => $verdict['is_non_pengadaan'] ? 'Batalkan / Hapus Paket dari SiRUP (Revisi Satu ke Satu)' : 'Lanjutkan Pelaksanaan PBJP'
                ];
            }
        }

        $nama = strtolower(trim($namaPaket ?? ''));
        $makClean = strtolower(trim($mak ?? ''));
        $tipeClean = strtolower(trim($tipe ?? ''));

        // 1. EXCLUSION LIST UTAMA: Objek PBJP Sah (Barang, Bahan, ATK, Sewa, Makan Minum, Layanan Rutin Kantor)
        // Walaupun di dalam nama paket terdapat kata "PPPK", "PNS", "Honor", dsb. sebagai keterangan kegiatan/subkegiatan
        // (contoh: "Alat Tulis Kantor (Pengadaan PNS dan PPPK)" atau "Makan Minum Rapat Seleksi PPPK"),
        // objek belanjanya tetap sah sebagai Pengadaan Barang/Jasa Pemerintah (PBJP).
        $isObjekBarangAtauJasa = preg_match(
            '/alat tulis|atk\b|alat\/bahan|bahan\b|cetak|kertas|fotocopy|penggandaan|' .
            'makanan|minuman|makan minum|konsumsi|snack|katering|jasa boga|' .
            'sewa\b|komputer|laptop|printer|hardware|server|jaringan|' .
            'internet|kawat|faksimili|bandwidth|wifi|telepon|listrik|pdam|' .
            'surat kabar|majalah|media cetak|iklan|reklame|publikasi|' .
            'cleaning service|kebersihan|satpam|keamanan|pengemudi|sopir|' .
            'seragam|pakaian|atribut|spanduk|banner|baliho|plakat|souvenir|hadiah|' .
            'tenaga ahli|jasa konsultan|konsultansi|' .
            'rehab|pembangunan|konstruksi|pemeliharaan|perbaikan|renovasi|' .
            'obat|alkes|reagen|medis|bibit|benih|pupuk|ternak|kendaraan/i',
            $nama
        );

        $isAkunBarangModalAtauSewa = (
            strpos($makClean, '5.1.02.01') !== false || // Belanja Barang Pakai Habis / Bahan
            strpos($makClean, '5.1.02.03') !== false || // Belanja Pemeliharaan
            strpos($makClean, '5.1.02.02.004') !== false || // Belanja Sewa
            strpos($makClean, '5.2.') !== false // Belanja Modal
        );

        if ($isObjekBarangAtauJasa || $isAkunBarangModalAtauSewa) {
            $isMurniPerdin = preg_match('/^belanja perjalanan dinas|^perjalanan dinas|^uang harian|^uang saku perdin/i', $nama)
                && !preg_match('/sewa kendaraan|sewa mobil|tiket|travel|hotel|penginapan/i', $nama);
            $isMurniTransferUang = preg_match('/hibah uang|bansos uang|bantuan keuangan|bantuan sosial uang/i', $nama);

            if (!$isMurniPerdin && !$isMurniTransferUang) {
                return [
                    'is_non_pengadaan' => false,
                    'kategori' => 'Pengadaan Normal',
                    'alasan' => 'Objek pengadaan sah (Barang/Jasa/Modal/Sewa/Operasional). Keterangan nama kegiatan bukan objek belanja.',
                    'rekomendasi' => 'Lanjutkan Pelaksanaan PBJP'
                ];
            }
        }

        // 2. Pegawai Pemerintah dengan Perjanjian Kerja (PPPK) - Belanja Pegawai / ASN Non-PBJP
        // Hanya berlaku jika objek belanja adalah JASA / GAJI / PENGHASILAN orang PPPK (mis. PPPK Paruh Waktu pada Jabatan ...)
        $isJasaGajiPppk = (
            preg_match('/(belanja jasa|belanja pegawai|jasa pegawai|tenaga pegawai|gaji|upah|penghasilan).*pppk/i', $nama) ||
            preg_match('/pppk.*(paruh waktu|penuh waktu|jabatan)/i', $nama) ||
            preg_match('/pegawai pemerintah dengan perjanjian kerja.*(paruh waktu|penuh waktu|jabatan)/i', $nama)
        );

        if ($isJasaGajiPppk) {
            return [
                'is_non_pengadaan' => true,
                'kategori' => 'Gaji / Jasa PPPK (Non-PBJP)',
                'alasan' => 'PPPK merupakan Aparatur Sipil Negara (ASN) sesuai UU No. 20/2023; gaji/jasa/penghasilan PPPK (Paruh Waktu maupun Penuh Waktu) bukan objek PBJP. Sesuai Perpres 46/2025 harus dibatalkan/dihapus dari SiRUP.',
                'rekomendasi' => 'Batalkan / Hapus Paket dari SiRUP (Revisi Satu ke Satu)'
            ];
        }

        // 3. Honorarium & Belanja Pegawai (Aparatur Internal)
        if (
            preg_match('/honorarium|honor\b|gaji\b|tunjangan\b|uang lembur|insentif pegawai|dewan pengawas|tim pelaksana kegiatan/i', $nama)
            || strpos($makClean, '5.1.01') !== false
        ) {
            return [
                'is_non_pengadaan' => true,
                'kategori' => 'Honorarium & Belanja Pegawai',
                'alasan' => 'Komponen belanja honorarium/gaji/insentif aparatur internal bukan objek PBJP sesuai Perpres 46/2025 & Perlem LKPP 11/2021.',
                'rekomendasi' => 'Batalkan / Hapus Paket dari SiRUP (Revisi Satu ke Satu)'
            ];
        }

        // 4. Hibah Uang & Bansos Uang (Transfer Kas)
        // Hanya hibah/bansos berbentuk UANG yang bukan PBJP. Hibah/Bansos BARANG (mis. kursi roda,
        // sembako, alat) tetap merupakan objek pengadaan karena barangnya harus diadakan terlebih dahulu.
        $isHibahBansos = preg_match('/hibah|bansos|bantuan sosial|bantuan keuangan/i', $nama)
            || strpos($makClean, '5.1.05') !== false
            || strpos($makClean, '5.1.06') !== false;
        $isBentukUang = preg_match('/hibah uang|bansos uang|bantuan sosial uang|bantuan keuangan|bantuan tunai|uang tunai|\bblt\b/i', $nama);
        $isBentukBarang = preg_match('/barang|jasa|pengadaan|pembelian|kursi roda|alat|bibit|benih|sembako|ternak|kendaraan|gedung|rehab|bantuan fisik|bahan|cetak|perlengkapan|peralatan|paket/i', $nama);

        if ($isHibahBansos && $isBentukUang && !$isBentukBarang) {
            return [
                'is_non_pengadaan' => true,
                'kategori' => 'Hibah / Bansos Uang',
                'alasan' => 'Hibah/Bansos dalam bentuk UANG merupakan transfer kas, bukan perolehan barang/jasa (Perpres 46/2025). Catatan: Hibah/Bansos dalam bentuk BARANG tetap merupakan objek pengadaan.',
                'rekomendasi' => 'Batalkan / Hapus Paket dari SiRUP (Revisi Satu ke Satu)'
            ];
        }

        // 5. Perjalanan Dinas Rutin / Uang Harian ASN
        if (
            (
                preg_match('/perjalanan dinas|perdin\b|uang harian|uang saku perdin|transport lokal/i', $nama)
                || strpos($makClean, '5.1.02.04') !== false
            )
            && !preg_match('/sewa kendaraan|sewa mobil|tiket|travel|hotel|penginapan/i', $nama)
        ) {
            return [
                'is_non_pengadaan' => true,
                'kategori' => 'Perjalanan Dinas Rutin',
                'alasan' => 'Uang harian/transport aparatur tanpa perolehan jasa pihak ketiga bukan objek PBJP.',
                'rekomendasi' => 'Batalkan / Hapus Paket dari SiRUP (Revisi Satu ke Satu)'
            ];
        }

        // 6. Pajak / Iuran Wajib Non-PBJP
        if (preg_match('/iuran bpjs|iuran jkn|pembayaran pajak daerah/i', $nama)) {
            return [
                'is_non_pengadaan' => true,
                'kategori' => 'Iuran / Pajak / Non-PBJP',
                'alasan' => 'Pembayaran iuran/pajak wajib bukan merupakan kegiatan pengadaan barang/jasa.',
                'rekomendasi' => 'Batalkan / Hapus Paket dari SiRUP (Revisi Satu ke Satu)'
            ];
        }

        return [
            'is_non_pengadaan' => false,
            'kategori' => 'Pengadaan Normal',
            'alasan' => 'Objek PBJP memenuhi 4 unsur mutlak pengadaan.',
            'rekomendasi' => 'Lanjutkan Pelaksanaan PBJP'
        ];
    }

    public function exportNonPengadaan()
    {
        $currentYear = $this->request->getGet('year') ?? date('Y');
        $selectedOpd = $this->request->getGet('opd') ?? $this->request->getGet('satker') ?? '';

        $penyediaModel = new \App\Models\RupPenyediaModel();
        $swakelolaModel = new \App\Models\RupSwakelolaModel();

        $builderP = $penyediaModel->where('tahun', $currentYear);
        $builderS = $swakelolaModel->where('tahun', $currentYear);

        // RBAC filtering
        $userRole = trim(strtolower(session()->get('role') ?? ''));
        $userSatker = trim(session()->get('kd_satker') ?? '');
        $userNamaSatker = trim(session()->get('nama_satker') ?? session()->get('nama_lengkap') ?? '');

        if (in_array($userRole, ['satker', 'opd'])) {
            if ($userNamaSatker) {
                $builderP->groupStart()->like('nama_satker', $userNamaSatker)->groupEnd();
                $builderS->groupStart()->like('nama_satker', $userNamaSatker)->groupEnd();
            } elseif ($userSatker) {
                $builderP->where('kd_satker', $userSatker);
                $builderS->where('kd_satker', $userSatker);
            }
        } elseif ($selectedOpd && $selectedOpd !== 'all') {
            $builderP->where('nama_satker', $selectedOpd);
            $builderS->where('nama_satker', $selectedOpd);
        }

        $penyediaList = $builderP->findAll();
        $swakelolaList = $builderS->findAll();

        foreach ($penyediaList as &$p) $p['_tipe'] = 'Penyedia';
        foreach ($swakelolaList as &$s) $s['_tipe'] = 'Swakelola';

        $allRup = array_merge($penyediaList, $swakelolaList);

        $aiService = new \App\Services\NonPengadaanAiService();
        $aiVerdicts = $aiService->loadVerdicts();

        $nonPengadaanRows = [];
        foreach ($allRup as $item) {
            $res = $this->classifyNonPengadaan($item['nama_paket'], $item['mak'] ?? '', $item['_tipe'], $item['kd_rup'] ?? '', $aiVerdicts);
            if ($res['is_non_pengadaan']) {
                $nonPengadaanRows[] = [
                    'kd_rup' => $item['kd_rup'] ?: '-',
                    'nama_satker' => $item['nama_satker'] ?: '-',
                    'nama_paket' => $item['nama_paket'],
                    'tipe' => $item['_tipe'],
                    'pagu' => (float)$item['pagu'],
                    'mak' => $item['mak'] ?: '-',
                    'kategori' => $res['kategori'],
                    'alasan' => $res['alasan'],
                    'rekomendasi' => $res['rekomendasi'],
                    'status' => 'DIUSULKAN HAPUS / BATAL DARI SIRUP'
                ];
            }
        }

        // Sort by Satker ASC and Pagu DESC
        usort($nonPengadaanRows, function($a, $b) {
            $cmp = strcmp($a['nama_satker'], $b['nama_satker']);
            if ($cmp !== 0) return $cmp;
            return $b['pagu'] <=> $a['pagu'];
        });

        $opdSuffix = ($selectedOpd && $selectedOpd !== 'all') ? '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', substr($selectedOpd, 0, 30)) : '';
        $filename = "Daftar_Temuan_Non_Pengadaan_RUP_{$currentYear}{$opdSuffix}.csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

        fputcsv($output, [
            'No',
            'Kode RUP',
            'Satuan Kerja / OPD',
            'Nama Paket di SiRUP',
            'Tipe Pengadaan',
            'Pagu Paket (Rp)',
            'Kode MAK / Rekening',
            'Klasifikasi Temuan Non-Pengadaan',
            'Dasar Hukum & Alasan Temuan',
            'Rekomendasi PBJ',
            'Tindak Lanjut OPD di SiRUP'
        ]);

        $no = 1;
        foreach ($nonPengadaanRows as $row) {
            fputcsv($output, [
                $no++,
                $row['kd_rup'],
                $row['nama_satker'],
                $row['nama_paket'],
                $row['tipe'],
                $row['pagu'],
                $row['mak'],
                $row['kategori'],
                $row['alasan'],
                $row['rekomendasi'],
                $row['status']
            ]);
        }

        fclose($output);
        exit;
    }

    public function verifyNonPengadaanAi()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setBody('Forbidden');
        }

        $kdRup = $this->request->getPost('kd_rup');
        $namaPaket = $this->request->getPost('nama_paket');
        $mak = $this->request->getPost('mak') ?? '';
        $tipe = $this->request->getPost('tipe') ?? 'Penyedia';
        $currentYear = $this->request->getPost('year') ?? date('Y');

        if (!$kdRup || !$namaPaket) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Parameter kd_rup dan nama_paket wajib diisi.']);
        }

        $aiService = new \App\Services\NonPengadaanAiService();
        if (!$aiService->isConfigured()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'API Key AI belum dikonfigurasi di Pengaturan Chatbot.']);
        }

        try {
            $saved = $aiService->classifyBatch([[
                'kd_rup' => $kdRup,
                'nama_paket' => $namaPaket,
                'mak' => $mak,
                'akun' => $mak,
                'tipe' => $tipe
            ]]);

            // Clear cache agar tampilan dashboard terupdate
            cache()->delete("rup_full_profile_and_analysis_v8_{$currentYear}");

            // Ambil hasil verifikasi yang baru disimpan
            $verdicts = $aiService->loadVerdicts();
            $result = $this->classifyNonPengadaan($namaPaket, $mak, $tipe, $kdRup, $verdicts);

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Paket berhasil diverifikasi dengan model AI: ' . $aiService->getModel(),
                'result' => $result
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Verifikasi AI gagal: ' . $e->getMessage()
            ]);
        }
    }

    public function lpse($type)
    {
        $titles = [
            'tender' => 'Data Tender LPSE',
            'nontender' => 'Data Non Tender LPSE',
            'pencatatan-nontender' => 'Pencatatan Non Tender',
            'pencatatan-swakelola' => 'Pencatatan Swakelola'
        ];

        $db = \Config\Database::connect();
        $years = [];
        if ($type === 'tender') {
            $years = $db->table('lpse_tender')->select('tahun')->distinct()->get()->getResultArray();
        } elseif ($type === 'nontender') {
            $years = $db->table('lpse_nontender')->select('tahun')->distinct()->get()->getResultArray();
        } elseif ($type === 'pencatatan-nontender') {
            $years = $db->table('lpse_pencatatan_nontender')->select('tahun')->distinct()->get()->getResultArray();
        } elseif ($type === 'pencatatan-swakelola') {
            $years = $db->table('lpse_pencatatan_swakelola')->select('tahun')->distinct()->get()->getResultArray();
        }
        $availableYears = array_filter(array_column($years, 'tahun'));
        rsort($availableYears);
        if (empty($availableYears)) {
            $availableYears = [date('Y')];
        }

        return view('dashboard/table_lpse', [
            'title' => $titles[$type] ?? 'Data LPSE',
            'user' => session()->get(),
            'type' => $type,
            'availableYears' => $availableYears
        ]);
    }

    public function epurchasing()
    {
        return view('dashboard/dashboard_epurchasing', [
            'title' => 'Dashboard E-Purchasing',
            'user' => session()->get(),
            'type' => 'epurchasing'
        ]);
    }

    public function tableEpurchasing()
    {
        return view('dashboard/table_epurchasing', [
            'title' => 'Data Detail E-Purchasing',
            'user' => session()->get(),
            'type' => 'epurchasing'
        ]);
    }

    public function getEpurchasingDashboardData()
    {
        $userRole = session()->get('role');
        $userNamaSatker = session()->get('nama_satker');
        $userKdSatker = session()->get('kd_satker');

        $isRestricted = in_array($userRole, ['satker', 'opd']);
        
        // Resolve nama_satker for legacy sessions
        if ($isRestricted && !$userNamaSatker && $userKdSatker) {
            $db = \Config\Database::connect();
            foreach (['lpse_tender','lpse_nontender','lpse_pencatatan_nontender','lpse_pencatatan_swakelola','rup_penyedia','rup_swakelola'] as $tbl) {
                $r = $db->table($tbl)->select('nama_satker')->where('kd_satker', $userKdSatker)->limit(1)->get()->getRowArray();
                if ($r && !empty($r['nama_satker'])) {
                    $userNamaSatker = $r['nama_satker'];
                    session()->set('nama_satker', $userNamaSatker);
                    break;
                }
            }
        }

        $cacheSuffix = $isRestricted ? "_" . md5($userNamaSatker) : "";
        $cacheKey = "epurchasing_dashboard_v16" . $cacheSuffix;

        if (!$result = cache($cacheKey)) {
            $model = new \App\Models\EpurchasingModel();
            
            if ($isRestricted && $userNamaSatker) {
                $rows = $model->where('nama_satker', $userNamaSatker)->findAll();
            } else {
                $rows = $model->findAll();
            }

            if (!is_array($rows))
                $rows = [];

            $history = []; // [year] => [realisasi, count, saving, origin]
            $topSatker = []; // [kd_satker] => [nama, realisasi, count, saving, origin]
            $topVendor = []; // [nama_penyedia] => [realisasi, count, saving, origin, packages]
            $statusSummary = []; // [status] => [realisasi, count]
            $minikomSummary = []; // [flag_minikom] => [count, realisasi]
            $fundingSummary = []; // [funding_source] => [count, realisasi]
            $topProductsMap = []; // [product_key] => [...]
            $savingsPackagesList = []; // list of packages with negotiation savings
            $totalRealisasi = 0;
            $totalPackages = 0;
            $otherStatusPackagesCount = 0;
            $totalOriginYear = 0;
            $totalFinalProdukYear = 0;
            $totalSavingYear = 0;
            $totalShippingFeeYear = 0;
            $shippingPackagesCount = 0;
            $negotiatedPackagesCount = 0;
            $totalProductItemsCount = 0;

            $currentYearNum = (int) date('Y');
            $monthly = array_fill(1, 12, ['realisasi' => 0, 'count' => 0, 'saving' => 0]); // [month] => [realisasi, count, saving]

            $allowed = ['COMPLETED', 'ON PROCESS', 'PAYMENT OUTSIDE SYSTEM', 'WAITING PPK REVIEW', 'ON NEGOTIATION', 'WAITING SELLER CONFIRMATION', 'ON ADDENDUM'];
            $realizedStatuses = ['COMPLETED', 'ON PROCESS', 'PAYMENT OUTSIDE SYSTEM', 'ON ADDENDUM'];

            foreach ($rows as $row) {
                $status = strtoupper(str_replace('_', ' ', trim((string) ($row['status'] ?? ''))));
                if (empty($status) || $status === '-' || !in_array($status, $allowed))
                    continue;

                $orderDate = trim((string) ($row['order_date'] ?? ''));
                if ($orderDate === '' || $orderDate === '-' || $orderDate === '0000-00-00' || str_starts_with($orderDate, '0000-00-00')) {
                    continue;
                }

                $yr = (int) $row['tahun'];
                if ($yr <= 0)
                    continue;

                $real = (float) ($row['total_harga'] ?? 0);

                // Parse products_json for price & negotiation intelligence
                $prodsRaw = !empty($row['products_json']) ? json_decode($row['products_json'], true) : [];
                if (!is_array($prodsRaw)) {
                    $prodsRaw = [];
                }
                $pkgOrigin = 0.0;
                $pkgFinalProduk = 0.0;
                $parsedProducts = [];
                foreach ($prodsRaw as $pr) {
                    $pQty = (float) ($pr['qty'] ?? 0);
                    $pOrig = (float) ($pr['origin_price'] ?? 0);
                    $pFin = (float) ($pr['final_price'] ?? 0);
                    if ($pOrig <= 0 && $pFin > 0) {
                        $pOrig = $pFin;
                    }
                    $subOrig = $pOrig * $pQty;
                    $subFin = $pFin * $pQty;
                    $savUnit = max(0.0, $pOrig - $pFin);
                    $savTot = max(0.0, $subOrig - $subFin);
                    $savPct = $pOrig > 0 ? round(($savUnit / $pOrig) * 100, 2) : 0.0;

                    $pkgOrigin += $subOrig;
                    $pkgFinalProduk += $subFin;

                    $parsedProducts[] = [
                        'product_id' => $pr['product_id'] ?? '-',
                        'product_name' => trim((string) ($pr['product_name'] ?? '-')),
                        'qty' => $pQty,
                        'origin_price' => $pOrig,
                        'final_price' => $pFin,
                        'subtotal_origin' => $subOrig,
                        'subtotal_final' => $subFin,
                        'saving_unit' => $savUnit,
                        'saving_total' => $savTot,
                        'saving_pct' => $savPct,
                    ];
                }
                $pkgSaving = max(0.0, $pkgOrigin - $pkgFinalProduk);
                $pkgSavingPct = $pkgOrigin > 0 ? round(($pkgSaving / $pkgOrigin) * 100, 2) : 0.0;

                // Status summary aggregation for current active year (includes all allowed statuses)
                if ($yr === $currentYearNum) {
                    if (!isset($statusSummary[$status])) {
                        $statusSummary[$status] = ['count' => 0, 'realisasi' => 0];
                    }
                    $statusSummary[$status]['count'] += 1;
                    $statusSummary[$status]['realisasi'] += $real;

                    if (!in_array($status, $realizedStatuses)) {
                        $otherStatusPackagesCount += 1;
                    }
                }

                // Filter out non-realized statuses for all other dashboard metrics
                if (!in_array($status, $realizedStatuses)) {
                    continue;
                }

                // History aggregation (All years for chart - realized statuses only)
                if (!isset($history[$yr]))
                    $history[$yr] = ['realisasi' => 0, 'count' => 0, 'saving' => 0, 'origin' => 0];
                $history[$yr]['realisasi'] += $real;
                $history[$yr]['count'] += 1;
                $history[$yr]['saving'] += $pkgSaving;
                $history[$yr]['origin'] += $pkgOrigin;

                // Current Year ONLY for Stats, Top Satker, Top Vendor, and Product Intelligence
                if ($yr === $currentYearNum) {
                    $shipFee = (float) ($row['shipping_fee'] ?? 0);
                    $flagMinikomVal = trim((string) ($row['flag_minikom'] ?? ''));
                    if ($flagMinikomVal === '' || $flagMinikomVal === '-') {
                        $flagMinikomVal = 'Non Minikom';
                    }
                    $fundingSourceVal = strtoupper(trim((string) ($row['sumber_dana'] ?? '')));
                    if ($fundingSourceVal === '' || $fundingSourceVal === '-') {
                        $fundingSourceVal = 'APBD';
                    }

                    $totalRealisasi += $real;
                    $totalPackages += 1;
                    $totalOriginYear += $pkgOrigin;
                    $totalFinalProdukYear += $pkgFinalProduk;
                    $totalSavingYear += $pkgSaving;
                    $totalShippingFeeYear += $shipFee;
                    if ($shipFee > 0) {
                        $shippingPackagesCount += 1;
                    }
                    $totalProductItemsCount += count($parsedProducts);
                    if ($pkgSaving > 0) {
                        $negotiatedPackagesCount += 1;
                    }

                    if (!isset($minikomSummary[$flagMinikomVal])) {
                        $minikomSummary[$flagMinikomVal] = ['count' => 0, 'realisasi' => 0, 'saving' => 0];
                    }
                    $minikomSummary[$flagMinikomVal]['count'] += 1;
                    $minikomSummary[$flagMinikomVal]['realisasi'] += $real;
                    $minikomSummary[$flagMinikomVal]['saving'] += $pkgSaving;

                    if (!isset($fundingSummary[$fundingSourceVal])) {
                        $fundingSummary[$fundingSourceVal] = ['count' => 0, 'realisasi' => 0, 'saving' => 0];
                    }
                    $fundingSummary[$fundingSourceVal]['count'] += 1;
                    $fundingSummary[$fundingSourceVal]['realisasi'] += $real;
                    $fundingSummary[$fundingSourceVal]['saving'] += $pkgSaving;

                    // Enrichment from DB first, then local JSON
                    $kdVendor = trim($row['kode_penyedia'] ?? '');
                    $namaPenyedia = !empty($row['nama_penyedia']) ? trim($row['nama_penyedia']) : ('Penyedia ID: ' . $kdVendor);

                    if (empty($row['nama_penyedia']) && $kdVendor !== '') {
                        $vFile = WRITEPATH . 'inaproc/vendors/' . $kdVendor . '.json';
                        if (file_exists($vFile)) {
                            $vData = json_decode(file_get_contents($vFile), true);
                            $namaPenyedia = $vData['nama_penyedia'] ?? $namaPenyedia;
                        }
                    }

                    // Satker aggregation
                    $kdSatker = $row['kd_satker'] ?? 'UNKNOWN';
                    if (!isset($topSatker[$kdSatker])) {
                        $topSatker[$kdSatker] = [
                            'nama' => $row['nama_satker'] ?? 'Satker Tidak Diketahui',
                            'realisasi' => 0,
                            'count' => 0,
                            'origin' => 0,
                            'saving' => 0,
                            'saving_pct' => 0
                        ];
                    }
                    $topSatker[$kdSatker]['realisasi'] += $real;
                    $topSatker[$kdSatker]['count'] += 1;
                    $topSatker[$kdSatker]['origin'] += $pkgOrigin;
                    $topSatker[$kdSatker]['saving'] += $pkgSaving;
                    $topSatker[$kdSatker]['saving_pct'] = $topSatker[$kdSatker]['origin'] > 0
                        ? round(($topSatker[$kdSatker]['saving'] / $topSatker[$kdSatker]['origin']) * 100, 2)
                        : 0;

                    // Vendor aggregation
                    if (!isset($topVendor[$namaPenyedia])) {
                        $topVendor[$namaPenyedia] = [
                            'realisasi' => 0,
                            'count' => 0,
                            'origin' => 0,
                            'saving' => 0,
                            'saving_pct' => 0,
                            'packages' => []
                        ];
                    }
                    $topVendor[$namaPenyedia]['realisasi'] += $real;
                    $topVendor[$namaPenyedia]['count'] += 1;
                    $topVendor[$namaPenyedia]['origin'] += $pkgOrigin;
                    $topVendor[$namaPenyedia]['saving'] += $pkgSaving;
                    $topVendor[$namaPenyedia]['saving_pct'] = $topVendor[$namaPenyedia]['origin'] > 0
                        ? round(($topVendor[$namaPenyedia]['saving'] / $topVendor[$namaPenyedia]['origin']) * 100, 2)
                        : 0;

                    $rawDate = $row['order_date'] ?? '';
                    $fmtDate = ($rawDate && strtotime($rawDate)) ? date('d-m-Y', strtotime($rawDate)) : '-';

                    $cleanRupDesc = rtrim(trim((string) ($row['rup_desc'] ?? '')), ',');
                    $directProduct = trim((string) ($row['nama_produk'] ?? ''));

                    $pkgDetailItem = [
                        'kd_paket' => $row['kd_paket'] ?? '-',
                        'rup_code' => $row['rup_code'] ?? '-',
                        'nama_paket' => $row['nama_paket'] ?? '-',
                        'nama_program' => $cleanRupDesc !== '' ? $cleanRupDesc : '-',
                        'nama_produk' => $directProduct !== '' ? $directProduct : '-',
                        'nama_penyedia' => $namaPenyedia,
                        'realisasi' => $real,
                        'total_origin' => $pkgOrigin,
                        'total_final_produk' => $pkgFinalProduk,
                        'total_saving' => $pkgSaving,
                        'saving_pct' => $pkgSavingPct,
                        'count_product' => count($parsedProducts) ?: (int) ($row['count_product'] ?? 0),
                        'total_qty' => (int) ($row['total_qty'] ?? 0),
                        'shipping_fee' => $shipFee,
                        'flag_minikom' => $flagMinikomVal,
                        'funding_source' => $fundingSourceVal,
                        'sumber_dana' => $fundingSourceVal,
                        'products_list' => $parsedProducts,
                        'tgl_order' => $fmtDate,
                        'satker' => $row['nama_satker'] ?? '-',
                        '_kdVendor' => $kdVendor,
                        '_productIds' => trim($row['product_id'] ?? '')
                    ];

                    $topVendor[$namaPenyedia]['packages'][] = $pkgDetailItem;

                    if ($pkgSaving > 0) {
                        $savingsPackagesList[] = $pkgDetailItem;
                    }

                    // Aggregate top products / commodities from parsedProducts
                    foreach ($parsedProducts as $pp) {
                        $pKey = strtoupper(trim($pp['product_name']));
                        if ($pKey === '' || $pKey === '-') continue;
                        if (!isset($topProductsMap[$pKey])) {
                            $topProductsMap[$pKey] = [
                                'product_name' => $pp['product_name'],
                                'product_id' => $pp['product_id'],
                                'paket_count' => 0,
                                'total_qty' => 0,
                                'total_origin' => 0,
                                'total_final' => 0,
                                'total_saving' => 0,
                                'saving_pct' => 0,
                                'min_final_price' => $pp['final_price'],
                                'max_final_price' => $pp['final_price'],
                                'avg_origin_price' => $pp['origin_price'],
                                'avg_final_price' => $pp['final_price'],
                            ];
                        }
                        $topProductsMap[$pKey]['paket_count'] += 1;
                        $topProductsMap[$pKey]['total_qty'] += $pp['qty'];
                        $topProductsMap[$pKey]['total_origin'] += $pp['subtotal_origin'];
                        $topProductsMap[$pKey]['total_final'] += $pp['subtotal_final'];
                        $topProductsMap[$pKey]['total_saving'] += $pp['saving_total'];
                        if ($pp['final_price'] < $topProductsMap[$pKey]['min_final_price']) {
                            $topProductsMap[$pKey]['min_final_price'] = $pp['final_price'];
                        }
                        if ($pp['final_price'] > $topProductsMap[$pKey]['max_final_price']) {
                            $topProductsMap[$pKey]['max_final_price'] = $pp['final_price'];
                        }
                        if ($topProductsMap[$pKey]['total_qty'] > 0) {
                            $topProductsMap[$pKey]['avg_origin_price'] = round($topProductsMap[$pKey]['total_origin'] / $topProductsMap[$pKey]['total_qty'], 0);
                            $topProductsMap[$pKey]['avg_final_price'] = round($topProductsMap[$pKey]['total_final'] / $topProductsMap[$pKey]['total_qty'], 0);
                        }
                        $topProductsMap[$pKey]['saving_pct'] = $topProductsMap[$pKey]['total_origin'] > 0
                            ? round(($topProductsMap[$pKey]['total_saving'] / $topProductsMap[$pKey]['total_origin']) * 100, 2)
                            : 0;
                    }

                    // Monthly aggregation for current year
                    if ($rawDate) {
                        $month = (int) date('n', strtotime($rawDate));
                        if ($month >= 1 && $month <= 12) {
                            $monthly[$month]['realisasi'] += $real;
                            $monthly[$month]['count'] += 1;
                            $monthly[$month]['saving'] += $pkgSaving;
                        }
                    }
                }
            }

            ksort($history);
            uasort($topSatker, function ($a, $b) {
                return $b['realisasi'] <=> $a['realisasi'];
            });
            uasort($topVendor, function ($a, $b) {
                return $b['count'] <=> $a['count'];
            });
            usort($savingsPackagesList, function ($a, $b) {
                return $b['total_saving'] <=> $a['total_saving'];
            });
            $topSavingsSlice = array_slice($savingsPackagesList, 0, 10);
            foreach ($topSavingsSlice as &$sp) {
                unset($sp['_kdVendor'], $sp['_productIds']);
            }
            unset($sp);

            $topProductsByValue = array_values($topProductsMap);
            usort($topProductsByValue, function ($a, $b) {
                return $b['total_final'] <=> $a['total_final'];
            });
            $topProductsSlice = array_slice($topProductsByValue, 0, 10);

            // Late Enrichment: Only fetch product names from JSON if not already populated from API products array
            $topVendorSlice = array_slice($topVendor, 0, 10, true);
            foreach ($topVendorSlice as $vName => &$vData) {
                foreach ($vData['packages'] as &$pkg) {
                    $kdV = $pkg['_kdVendor'];
                    $pIds = $pkg['_productIds'];
                    if ($pkg['nama_produk'] === '-' && $kdV !== '' && $pIds !== '') {
                        $actualProduct = $this->_getInaprocProductNames($kdV, $pIds, $pkg['nama_paket']);
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
            $prevYear = (string) ($currentYear - 1);
            $prevStats = $history[$prevYear] ?? ['realisasi' => 0, 'count' => 0];

            $growth = $prevStats['realisasi'] > 0 ? (($currStats['realisasi'] - $prevStats['realisasi']) / $prevStats['realisasi']) * 100 : 0;
            $topS = !empty($topSatker) ? reset($topSatker) : ['nama' => '-', 'realisasi' => 0];
            $topV = !empty($topVendor) ? reset($topVendor) : ['realisasi' => 0, 'count' => 0];

            reset($topVendor);
            $topVName = !empty($topVendor) ? key($topVendor) : '-';

            $overallSavingPct = $totalOriginYear > 0 ? round(($totalSavingYear / $totalOriginYear) * 100, 2) : 0;
            $negoRatePct = $totalPackages > 0 ? round(($negotiatedPackagesCount / $totalPackages) * 100, 1) : 0;

            $narrative = "Analisis Tahun Berjalan (" . $currentYearNum . "): Total realisasi mencapai Rp " . number_format($totalRealisasi, 0, ',', '.') . " dari " . number_format($totalPackages, 0, ',', '.') . " paket dengan efisiensi hasil negosiasi sebesar Rp " . number_format($totalSavingYear, 0, ',', '.') . " (" . $overallSavingPct . "%). ";
            if ($topVName !== '-') {
                $narrative .= "Satker paling aktif saat ini adalah " . ($topS['nama'] ?? '-') . " dengan realisasi Rp " . number_format($topS['realisasi'] ?? 0, 0, ',', '.') . ". ";
                $narrative .= "Penyedia utama tahun ini adalah " . $topVName . " dengan perolehan " . ($topV['count'] ?? 0) . " paket.";
            }

            $result = [
                'summary' => [
                    'total_realisasi' => $totalRealisasi,
                    'total_packages' => $totalPackages,
                    'other_status_packages' => $otherStatusPackagesCount,
                    'total_origin' => $totalOriginYear,
                    'total_final_produk' => $totalFinalProdukYear,
                    'total_saving' => $totalSavingYear,
                    'saving_pct' => $overallSavingPct,
                    'total_shipping_fee' => $totalShippingFeeYear,
                    'shipping_packages' => $shippingPackagesCount,
                    'negotiated_packages' => $negotiatedPackagesCount,
                    'unnegotiated_packages' => max(0, $totalPackages - $negotiatedPackagesCount),
                    'negotiation_rate_pct' => $negoRatePct,
                    'total_product_items' => $totalProductItemsCount,
                    'years_count' => count($history),
                    'year_active' => $currentYearNum
                ],
                'history' => $history,
                'monthly' => $monthly,
                'top_satker' => array_slice($topSatker, 0, 10, true),
                'top_vendor' => $topVendorSlice,
                'top_savings_packages' => $topSavingsSlice,
                'top_products' => $topProductsSlice,
                'status_summary' => $statusSummary,
                'minikom_summary' => $minikomSummary,
                'funding_summary' => $fundingSummary,
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
            'user' => session()->get(),
        ]);
    }

    public function getDashboardLpseData()
    {
        $yearFilter = $this->request->getGet('year') ?: ($_GET['year'] ?? 'all');
        $quarterFilter = $this->request->getGet('quarter') ?: ($_GET['quarter'] ?? 'all');
        $result = $this->calculateDashboardLpseData($yearFilter, $quarterFilter);
        return $this->response->setJSON($result);
    }

    public function calculateDashboardLpseData($yearFilter = 'all', $quarterFilter = 'all')
    {
        $cacheKey = "dashboard_lpse_v9_{$yearFilter}_{$quarterFilter}";

        if (!$result = cache($cacheKey)) {
            $allYears = [];
            $byYear = []; // [type][year] => [pagu, count]
            $totalPagu = []; // [type] => pagu
            $totalRealisasi = []; // [type] => realisasi
            $byJenis = []; // [jenis_pengadaan][year] => [pagu, count]
            $totalJenis = []; // [jenis_pengadaan] => pagu
            $seenPackages = []; // [label][year][id] => true

            // Inisialisasi data kuartal/triwulan untuk tren triwulanan
            $quarters = [1, 2, 3, 4, 'undated'];
            $byQuarter = [];

            $tenderModel = new \App\Models\LpseTenderModel();
            $nontenderModel = new \App\Models\LpseNontenderModel();
            $epurchasingModel = new \App\Models\EpurchasingModel();
            $pctNontenderModel = new \App\Models\LpsePencatatanNontenderModel();
            $pctSwakelolaModel = new \App\Models\LpsePencatatanSwakelolaModel();

            // Peta tanggal pelaksanaan RUP swakelola & Peta Pagu RUP Penyedia untuk E-Purchasing
            $rupSwakelolaDates = [];
            $rupEpPaguMap = [];
            try {
                $db = \Config\Database::connect();
                $rupRows = $db->table('rup_swakelola')
                    ->select('kd_rup, tgl_awal_pelaksanaan')
                    ->where('tgl_awal_pelaksanaan IS NOT NULL')
                    ->get()->getResultArray();
                foreach ($rupRows as $r) {
                    if (!empty($r['kd_rup']) && !empty($r['tgl_awal_pelaksanaan'])) {
                        $rupSwakelolaDates[(string) $r['kd_rup']] = $r['tgl_awal_pelaksanaan'];
                    }
                }

                $rupPenyediaRows = $db->table('rup_penyedia')
                    ->select('kd_rup, pagu')
                    ->where("LOWER(metode_pengadaan) LIKE '%e-purchasing%' OR LOWER(metode_pengadaan) LIKE '%epurchasing%'")
                    ->get()->getResultArray();
                foreach ($rupPenyediaRows as $r) {
                    $rkStr = trim((string) ($r['kd_rup'] ?? ''));
                    if ($rkStr !== '') {
                        $rupEpPaguMap[$rkStr] = (float) ($r['pagu'] ?? 0);
                    }
                }
            } catch (\Throwable $e) {
                // Abaikan jika tabel belum tersedia
            }

            // Ambil seluruh data dari DB lokal
            $sources = [
                'Tender' => $tenderModel->findAll(),
                'Non Tender' => $nontenderModel->findAll(),
                'E-Purchasing' => $epurchasingModel->findAll(),
                'Pencatatan Non Tender' => $pctNontenderModel->findAll(),
                'Pencatatan Swakelola' => $pctSwakelolaModel->findAll()
            ];

            foreach ($quarters as $q) {
                $byQuarter[$q] = [
                    'total' => 0,
                    'pagu' => 0,
                    'count' => 0,
                    'by_source' => []
                ];
                foreach (array_keys($sources) as $sLabel) {
                    $byQuarter[$q]['by_source'][$sLabel] = 0;
                }
            }

            // Fungsi ekstraksi triwulan dari berbagai atribut tanggal
            $getDateQuarter = function ($row, $label) use (&$rupSwakelolaDates) {
                $rawDate = null;
                if ($label === 'E-Purchasing') {
                    $rawDate = $row['order_date'] ?? null;
                } else if ($label === 'Tender') {
                    $rawDate = !empty($row['tgl_kontrak']) ? $row['tgl_kontrak'] : (!empty($row['tgl_pemenang']) ? $row['tgl_pemenang'] : ($row['tgl_mulai_pekerjaan'] ?? null));
                } else if ($label === 'Non Tender') {
                    $rawDate = !empty($row['tgl_kontrak']) ? $row['tgl_kontrak'] : (!empty($row['tgl_bast']) ? $row['tgl_bast'] : (!empty($row['tgl_bap']) ? $row['tgl_bap'] : ($row['tgl_mulai_pekerjaan'] ?? null)));
                } else if ($label === 'Pencatatan Non Tender') {
                    $rawDate = !empty($row['tgl_selesai_paket']) ? $row['tgl_selesai_paket'] : ($row['tgl_buat_paket'] ?? null);
                } else if ($label === 'Pencatatan Swakelola') {
                    $kdRup = (string) ($row['kd_rup'] ?? '');
                    if ($kdRup !== '' && isset($rupSwakelolaDates[$kdRup])) {
                        $rawDate = $rupSwakelolaDates[$kdRup];
                    }
                }

                if (empty($rawDate) || $rawDate === '-' || $rawDate === '0000-00-00' || str_starts_with((string) $rawDate, '0000-00-00')) {
                    return null;
                }

                $clean = substr(trim((string) $rawDate), 0, 10);
                $ts = strtotime($clean);
                if ($ts === false || $ts <= 0) {
                    return null;
                }
                $m = (int) date('n', $ts);
                if ($m >= 1 && $m <= 3) return 1;
                if ($m >= 4 && $m <= 6) return 2;
                if ($m >= 7 && $m <= 9) return 3;
                if ($m >= 10 && $m <= 12) return 4;
                return null;
            };

            // Penampung grup RUP untuk mencegah anomali pagu berulang
            $rupItemGroups = [];
            $seenQuarterPagu = [];

            foreach ($sources as $label => $rows) {
                if (!is_array($rows))
                    $rows = [];

                foreach ($rows as $row) {
                    $yr = (string) ($row['tahun'] ?? date('Y'));
                    if ($yearFilter !== 'all' && $yr !== (string) $yearFilter)
                        continue;

                    $pkgId = trim((string) ($row['kd_paket'] ?? ($row['id'] ?? '')));
                    $status = strtolower(trim((string) ($row['status'] ?? '')));

                    // Abaikan paket yang dibatalkan secara umum
                    if (str_contains($status, 'batal')) {
                        continue;
                    }

                    // Khusus E-Purchasing: validasi status & order_date
                    if ($label === 'E-Purchasing') {
                        $allowedEpurchasing = ['COMPLETED', 'ON PROCESS', 'PAYMENT OUTSIDE SYSTEM', 'WAITING PPK REVIEW', 'ON NEGOTIATION', 'WAITING SELLER CONFIRMATION', 'ON ADDENDUM'];
                        $epStatus = strtoupper(str_replace('_', ' ', trim((string) ($row['status'] ?? ''))));
                        if (empty($epStatus) || $epStatus === '-' || !in_array($epStatus, $allowedEpurchasing)) {
                            continue;
                        }

                        $orderDate = trim((string) ($row['order_date'] ?? ''));
                        if ($orderDate === '' || $orderDate === '-' || $orderDate === '0000-00-00' || str_starts_with($orderDate, '0000-00-00')) {
                            continue;
                        }
                    }

                    // Khusus Pencatatan Swakelola: abaikan jika terdapat status pembatalan atau alasan pembatalan
                    if ($label === 'Pencatatan Swakelola') {
                        $statusPctKet = strtolower(trim((string) ($row['status_swakelola_pct_ket'] ?? '')));
                        $alasanBatal = trim((string) ($row['alasan_pembatalan'] ?? ''));
                        if (str_contains($statusPctKet, 'batal') || $alasanBatal !== '') {
                            continue;
                        }
                    }

                    // Deteksi Realisasi
                    $realisasi = 0;
                    if ($label === 'Non Tender') {
                        $realisasi = (float) ($row['nilai_kontrak'] ?? 0);
                        if ($realisasi <= 0)
                            $realisasi = (float) ($row['nilai_negosiasi'] ?? 0);
                    } else if ($label === 'Tender') {
                        $realisasi = (float) ($row['nilai_kontrak'] ?? 0);
                    } else if ($label === 'E-Purchasing') {
                        $realisasi = (float) ($row['total_harga'] ?? 0);
                    } else {
                        $realisasi = (float) ($row['realisasi'] ?? 0);
                    }

                    // Filter: Hanya tampilkan yang sudah selesai / terealisasi (sudah ada nilai kontrak / realisasi)
                    if ($realisasi <= 0) {
                        continue;
                    }

                    // Tentukan RUP Key & Pagu Baris
                    if ($label === 'E-Purchasing') {
                        $rk = trim((string) ($row['rup_code'] ?? ''));
                        $rowPagu = isset($rupEpPaguMap[$rk]) ? $rupEpPaguMap[$rk] : (float) ($row['pagu'] ?? $realisasi);
                        $jenis = 'Pengadaan Barang / Jasa';
                    } else {
                        $rk = trim((string) ($row['kd_rup'] ?? ''));
                        $rowPagu = (float) ($row['pagu'] ?? 0);
                        $jenis = $row['jenis_pengadaan'] ?? ($row['kgr_nama'] ?? 'Lainnya');
                        if (trim($jenis) === '')
                            $jenis = 'Lainnya';
                    }

                    // Ekstraksi Triwulan
                    $rowQuarter = $getDateQuarter($row, $label);
                    $qKey = $rowQuarter !== null ? $rowQuarter : 'undated';

                    // Deduplikasi Paket berdasarkan ID
                    $isDuplicate = false;
                    if ($pkgId !== '') {
                        if (isset($seenPackages[$label][$yr][$pkgId])) {
                            $isDuplicate = true;
                        } else {
                            $seenPackages[$label][$yr][$pkgId] = true;
                        }
                    }

                    if ($isDuplicate) {
                        continue;
                    }

                    // Akumulasi data tren triwulan (seluruh realisasi tercatat pada triwulan masing-masing)
                    $byQuarter[$qKey]['total'] += $realisasi;
                    $byQuarter[$qKey]['by_source'][$label] += $realisasi;
                    $byQuarter[$qKey]['count'] += 1;

                    // Kunci RUP: gunakan RUP jika valid, fallback ke paket ID jika tidak ada RUP
                    $rupKey = ($rk !== '' && $rk !== '-' && $rk !== '0') ? "RUP_" . $rk : "PKG_" . $pkgId;

                    // Pagu pada byQuarter: dihitung 1x per RUP per triwulan
                    if (!isset($seenQuarterPagu[$qKey][$label][$yr][$rupKey])) {
                        $seenQuarterPagu[$qKey][$label][$yr][$rupKey] = true;
                        $byQuarter[$qKey]['pagu'] += $rowPagu;
                    }

                    // Filter spesifik Triwulan (jika bukan 'all')
                    if ($quarterFilter !== 'all' && (string) $rowQuarter !== (string) $quarterFilter) {
                        continue;
                    }

                    $allYears[$yr] = true;

                    // Kelompokkan per RUP untuk deduplikasi pagu agar tidak dihitung berulang
                    if (!isset($rupItemGroups[$label][$yr][$rupKey])) {
                        $rupItemGroups[$label][$yr][$rupKey] = [
                            'pagu' => $rowPagu,
                            'realisasi' => 0.0,
                            'count' => 0,
                            'jenis' => $jenis
                        ];
                    } else {
                        // Jika ada pagu yang lebih tinggi dilaporkan untuk RUP ini, ambil yang terbesar
                        if ($rowPagu > $rupItemGroups[$label][$yr][$rupKey]['pagu']) {
                            $rupItemGroups[$label][$yr][$rupKey]['pagu'] = $rowPagu;
                        }
                    }

                    $rupItemGroups[$label][$yr][$rupKey]['realisasi'] += $realisasi;
                    $rupItemGroups[$label][$yr][$rupKey]['count'] += 1;
                }
            }

            // Agregasi hasil akhir per label dan per tahun (Pagu RUP dihitung tepat 1x tanpa anomali pengulangan)
            foreach (array_keys($sources) as $label) {
                $totalPagu[$label] = 0;
                $totalRealisasi[$label] = 0;

                if (!isset($rupItemGroups[$label])) {
                    continue;
                }

                foreach ($rupItemGroups[$label] as $yr => $groups) {
                    if (!isset($byYear[$label][$yr])) {
                        $byYear[$label][$yr] = ['pagu' => 0, 'realisasi' => 0, 'count' => 0];
                    }

                    foreach ($groups as $rupKey => $gData) {
                        // Pastikan pagu mencukupi jika total realisasi RUP tersebut melebihi pagu awal
                        $effectivePagu = $gData['pagu'];
                        if ($effectivePagu < $gData['realisasi']) {
                            $effectivePagu = $gData['realisasi'];
                        }

                        $real = $gData['realisasi'];
                        $cnt = $gData['count'];
                        $jns = $gData['jenis'];

                        if (!isset($byJenis[$jns][$yr])) {
                            $byJenis[$jns][$yr] = ['pagu' => 0, 'realisasi' => 0, 'count' => 0];
                        }
                        if (!isset($totalJenis[$jns])) {
                            $totalJenis[$jns] = ['pagu' => 0, 'realisasi' => 0];
                        }

                        $byYear[$label][$yr]['pagu'] += $effectivePagu;
                        $totalPagu[$label] += $effectivePagu;

                        $byYear[$label][$yr]['realisasi'] += $real;
                        $totalRealisasi[$label] += $real;

                        $byYear[$label][$yr]['count'] += $cnt;

                        $byJenis[$jns][$yr]['pagu'] += $effectivePagu;
                        $totalJenis[$jns]['pagu'] += $effectivePagu;

                        $byJenis[$jns][$yr]['realisasi'] += $real;
                        $totalJenis[$jns]['realisasi'] += $real;

                        $byJenis[$jns][$yr]['count'] += $cnt;
                    }
                }
            }

            // Urutkan jenis pengadaan berdasarkan total pagu menurun
            uasort($totalJenis, function ($a, $b) {
                return $b['pagu'] <=> $a['pagu'];
            });

            ksort($allYears);
            $years = array_keys($allYears);

            $result = [
                'years' => $years,
                'labels' => array_keys($sources),
                'by_year' => $byYear,
                'total_pagu' => $totalPagu,
                'total_realisasi' => $totalRealisasi,
                'jenis_labels' => array_keys($totalJenis),
                'by_jenis' => $byJenis,
                'total_jenis' => $totalJenis,
                'by_quarter' => $byQuarter,
                'quarter_filter' => $quarterFilter,
            ];

            cache()->save($cacheKey, $result, 1800);
        }

        return $result;
    }

    public function getEpurchasingDatatables()
    {
        $currentYear = $this->request->getGet('year') ?? date('Y');
        $userSatker = session()->get('kd_satker');
        $userRole = session()->get('role');

        $cacheKey = "epurchasing_data_v108_{$currentYear}";

        if (!$allData = cache($cacheKey)) {
            $model = new \App\Models\EpurchasingModel();

            if ($currentYear !== 'all') {
                $raw = $model->where('tahun', $currentYear)->findAll();
            } else {
                $raw = $model->findAll();
            }

            if (!is_array($raw) || empty($raw)) {
                $allData = [];
            } else {
                // Preload Pagu dari tabel rup_penyedia berdasarkan rup_code & hitung frekuensi transaksi RUP
                $rupCodes = [];
                $rupTxCountMap = [];
                $rupTxTotalRealMap = [];

                foreach ($raw as $item) {
                    $rc = trim((string) ($item['rup_code'] ?? ''));
                    if ($rc !== '' && $rc !== '-' && $rc !== '0') {
                        $rupCodes[$rc] = true;
                        $rupTxCountMap[$rc] = ($rupTxCountMap[$rc] ?? 0) + 1;
                        $rupTxTotalRealMap[$rc] = ($rupTxTotalRealMap[$rc] ?? 0) + (float) ($item['total_harga'] ?? 0);
                    }
                }
                $rupPaguMap = [];
                if (!empty($rupCodes)) {
                    $db = \Config\Database::connect();
                    $codeList = array_keys($rupCodes);
                    $chunks = array_chunk($codeList, 1000);
                    foreach ($chunks as $chunk) {
                        $rows = $db->table('rup_penyedia')
                            ->select('kd_rup, pagu')
                            ->whereIn('kd_rup', $chunk)
                            ->get()->getResultArray();
                        foreach ($rows as $r) {
                            $rupPaguMap[trim((string) $r['kd_rup'])] = (float) ($r['pagu'] ?? 0);
                        }
                    }
                }

                $allData = [];
                $allowed = ['COMPLETED', 'ON PROCESS', 'PAYMENT OUTSIDE SYSTEM', 'WAITING PPK REVIEW', 'ON NEGOTIATION', 'WAITING SELLER CONFIRMATION', 'ON ADDENDUM'];
                foreach ($raw as $item) {
                    // Restricted Statuses for E-Purchasing (Flexible Check)
                    $status = strtoupper(str_replace('_', ' ', trim((string) ($item['status'] ?? ''))));
                    if (empty($status) || $status === '-' || !in_array($status, $allowed))
                        continue;

                    $orderDate = trim((string) ($item['order_date'] ?? ''));
                    if ($orderDate === '' || $orderDate === '-' || $orderDate === '0000-00-00' || str_starts_with($orderDate, '0000-00-00')) {
                        continue;
                    }

                    $cleanRupDesc = rtrim(trim((string) ($item['rup_desc'] ?? '')), ',');
                    $namaProduk = trim((string) ($item['nama_produk'] ?? ''));

                    $parsedProducts = [];
                    $pkgOriginTotal = 0.0;
                    $pkgFinalProdukTotal = 0.0;
                    $pkgSavingTotal = 0.0;

                    if (!empty($item['products_json'])) {
                        $prods = json_decode($item['products_json'], true);
                        if (is_array($prods)) {
                            $pNames = [];
                            foreach ($prods as $p) {
                                $pName = trim((string) ($p['product_name'] ?? ''));
                                $pId = trim((string) ($p['product_id'] ?? ''));
                                $pQty = (float) ($p['qty'] ?? 0);
                                $pOrigin = (float) ($p['origin_price'] ?? 0);
                                $pFinal = (float) ($p['final_price'] ?? 0);

                                if ($pName !== '') {
                                    $pNames[] = ($pQty > 0 && count($prods) > 1) ? "{$pName} (" . (int) $pQty . ")" : $pName;
                                }

                                $subOrigin = $pOrigin * $pQty;
                                $subFinal = $pFinal * $pQty;
                                $subSaving = max(0.0, $subOrigin - $subFinal);
                                $unitSaving = max(0.0, $pOrigin - $pFinal);
                                $pctSaving = $subOrigin > 0 ? round(($subSaving / $subOrigin) * 100, 2) : 0.0;

                                $pkgOriginTotal += $subOrigin;
                                $pkgFinalProdukTotal += $subFinal;
                                $pkgSavingTotal += $subSaving;

                                $parsedProducts[] = [
                                    'product_id' => $pId,
                                    'product_name' => $pName !== '' ? $pName : 'Produk E-Katalog',
                                    'qty' => $pQty,
                                    'origin_price' => $pOrigin,
                                    'final_price' => $pFinal,
                                    'subtotal_origin' => $subOrigin,
                                    'subtotal_final' => $subFinal,
                                    'saving_unit' => $unitSaving,
                                    'saving_total' => $subSaving,
                                    'saving_pct' => $pctSaving,
                                ];
                            }
                            if ($namaProduk === '' && !empty($pNames)) {
                                $namaProduk = implode(', ', $pNames);
                            }
                        }
                    }

                    $pkgSavingPct = $pkgOriginTotal > 0 ? round(($pkgSavingTotal / $pkgOriginTotal) * 100, 2) : 0.0;

                    $flagMinikomDt = trim((string) ($item['flag_minikom'] ?? ''));
                    if ($flagMinikomDt === '' || $flagMinikomDt === '-') {
                        $flagMinikomDt = 'Non Minikom';
                    }
                    $fundingSourceDt = strtoupper(trim((string) ($item['sumber_dana'] ?? '')));
                    if ($fundingSourceDt === '' || $fundingSourceDt === '-') {
                        $fundingSourceDt = '-';
                    }

                    $rupCodeVal = trim((string) ($item['rup_code'] ?? ''));
                    $paguRupVal = isset($rupPaguMap[$rupCodeVal]) ? (float) $rupPaguMap[$rupCodeVal] : 0.0;

                    $allData[] = [
                        'no_referensi' => $item['kd_paket'] ?? '-',
                        'nama_paket' => $item['nama_paket'] ?? '-',
                        'nama_program' => $cleanRupDesc !== '' ? $cleanRupDesc : '-',
                        'nama_produk' => $namaProduk !== '' ? $namaProduk : ($cleanRupDesc !== '' ? $cleanRupDesc : '-'),
                        'nama_penyedia' => $item['nama_penyedia'] ?? null, // Use stored DB value (null means "enrich later")
                        'kode_penyedia' => trim($item['kode_penyedia'] ?? ''),
                        'product_id' => trim($item['product_id'] ?? ''),
                        'nama_satker' => $item['nama_satker'] ?? '-',
                        'kd_satker' => $item['kd_satker'] ?? '',
                        'pagu' => (float) ($item['total_harga'] ?? 0),
                        'total_harga' => (float) ($item['total_harga'] ?? 0),
                        'pagu_rup' => $paguRupVal,
                        'rup_tx_count' => $rupTxCountMap[$rupCodeVal] ?? 1,
                        'rup_total_realisasi' => $rupTxTotalRealMap[$rupCodeVal] ?? (float) ($item['total_harga'] ?? 0),
                        'total_origin' => $pkgOriginTotal,
                        'total_final_produk' => $pkgFinalProdukTotal,
                        'total_saving' => $pkgSavingTotal,
                        'saving_pct' => $pkgSavingPct,
                        'products_list' => $parsedProducts,
                        'sumber_dana' => $fundingSourceDt,
                        'funding_source' => $fundingSourceDt,
                        'jumlah' => (int) ($item['total_qty'] ?? 0),
                        'count_product' => (int) ($item['count_product'] ?? count($parsedProducts)),
                        'satuan' => 'Unit',
                        'tanggal' => $item['order_date'] ?? '-',
                        'fiscal_year' => $item['tahun'] ?? '-',
                        'status' => $status,
                        'shipment_status' => $item['shipment_status'] ?? '-',
                        'rup_code' => $item['rup_code'] ?? '-',
                        'mak' => $item['mak'] ?? '-',
                        'flag_minikom' => $flagMinikomDt,
                        'shipping_fee' => (float) ($item['shipping_fee'] ?? 0),
                        '_has_direct_product' => ($namaProduk !== ''),
                    ];

                }

                if (!empty($allData)) {
                    cache()->save($cacheKey, $allData, 3600);
                }
            }
        }

        // RBAC filter — gunakan nama_satker dari sesi
        $userNamaSatker = session()->get('nama_satker');
        if (in_array($userRole, ['satker', 'opd'])) {
            // Fallback: resolve nama_satker dari DB jika sesi lama belum memilikinya
            if (!$userNamaSatker && $userSatker) {
                $db = \Config\Database::connect();
                foreach (['lpse_tender','lpse_nontender','lpse_pencatatan_nontender','lpse_pencatatan_swakelola','rup_penyedia','rup_swakelola'] as $tbl) {
                    $r = $db->table($tbl)->select('nama_satker')->where('kd_satker', $userSatker)->limit(1)->get()->getRowArray();
                    if ($r && !empty($r['nama_satker'])) {
                        $userNamaSatker = $r['nama_satker'];
                        session()->set('nama_satker', $userNamaSatker); // cache for this session
                        break;
                    }
                }
            }

            if ($userNamaSatker) {
                $allData = array_values(array_filter($allData, function ($item) use ($userNamaSatker) {
                    return ($item['nama_satker'] ?? '') == $userNamaSatker;
                }));
            }
        }

        $satkerFilter = $this->request->getGet('satker_filter') ?? '';
        // Abaikan filter dropdown jika sudah dibatasi RBAC (satker/opd)
        if ($satkerFilter && !in_array($userRole, ['satker', 'opd'])) {
            $allData = array_values(array_filter($allData, function ($item) use ($satkerFilter) {
                return ($item['nama_satker'] ?? '') == $satkerFilter;
            }));
        }

        // DataTables parameters
        $draw = $this->request->getGet('draw');
        $start = (int) ($this->request->getGet('start') ?? 0);
        $length = (int) ($this->request->getGet('length') ?? 10);
        $search = $this->request->getGet('search')['value'] ?? '';
        $statusFilter = $this->request->getGet('status_filter') ?? '';
        $order = $this->request->getGet('order');

        $totalRecords = count($allData);

        // Filter by status
        if ($statusFilter) {
            $allData = array_values(array_filter($allData, function ($item) use ($statusFilter) {
                return strcasecmp($item['status'] ?? '', $statusFilter) === 0;
            }));
        }

        // Search
        if ($search) {
            $allData = array_values(array_filter($allData, function ($item) use ($search) {
                return stripos($item['nama_paket'] ?? '', $search) !== false ||
                    stripos($item['nama_produk'] ?? '', $search) !== false ||
                    stripos($item['nama_program'] ?? '', $search) !== false ||
                    stripos($item['nama_penyedia'] ?? '', $search) !== false ||
                    stripos($item['nama_satker'] ?? '', $search) !== false ||
                    stripos($item['no_referensi'] ?? '', $search) !== false ||
                    stripos($item['rup_code'] ?? '', $search) !== false ||
                    stripos($item['mak'] ?? '', $search) !== false ||
                    stripos($item['flag_minikom'] ?? '', $search) !== false ||
                    stripos($item['sumber_dana'] ?? '', $search) !== false ||
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
                usort($allData, function ($a, $b) use ($colName, $dir) {
                    $valA = $a[$colName] ?? '';
                    $valB = $b[$colName] ?? '';
                    if (is_numeric($valA) && is_numeric($valB))
                        return ($dir === 'asc') ? ($valA <=> $valB) : ($valB <=> $valA);
                    return ($dir === 'asc') ? strnatcasecmp((string) $valA, (string) $valB) : strnatcasecmp((string) $valB, (string) $valA);
                });
            }
        }

        // Pagination
        $data = ($length == -1) ? $allData : array_slice($allData, $start, $length);

        // ENRICH visible rows only (Fast)
        // Priority: 1) DB stored nama_penyedia & nama_produk  2) Local JSON cache  3) Inaproc API (live)
        foreach ($data as &$item) {
            $hasDirectProduct = !empty($item['_has_direct_product']);
            unset($item['_has_direct_product']);

            // 1. Use stored value from DB if available
            if (!empty($item['nama_penyedia'])) {
                if (!$hasDirectProduct) {
                    $kdVendor = $item['kode_penyedia'];
                    $prodIdsStr = $item['product_id'];
                    if ($kdVendor !== '' && $prodIdsStr !== '') {
                        $item['nama_produk'] = $this->_getInaprocProductNames($kdVendor, $prodIdsStr, $item['nama_paket']);
                    }
                }
                continue;
            }

            // 2. Fallback: lookup from JSON cache / Inaproc API
            $kdVendor = $item['kode_penyedia'];
            $prodIdsStr = $item['product_id'];
            if ($kdVendor !== '') {
                $item['nama_penyedia'] = $this->_getInaprocVendorName($kdVendor);
                if (!$hasDirectProduct && $prodIdsStr !== '') {
                    $item['nama_produk'] = $this->_getInaprocProductNames($kdVendor, $prodIdsStr, $item['nama_paket']);
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

        $models = [
            'tender' => new \App\Models\LpseTenderModel(),
            'nontender' => new \App\Models\LpseNontenderModel(),
            'pencatatan-nontender' => new \App\Models\LpsePencatatanNontenderModel(),
            'pencatatan-swakelola' => new \App\Models\LpsePencatatanSwakelolaModel()
        ];

        $model = $models[$type] ?? null;
        if (!$model)
            return $this->response->setJSON(['data' => []]);

        // Build base query — only filter by year if not 'all'
        $builder = $model->builder();
        if ($currentYear && $currentYear !== 'all') {
            $builder->where('tahun', (int) $currentYear);
        }

        // RBAC: satker/opd filter — gunakan nama_satker dari sesi (kd_satker tidak konsisten antar tabel)
        $userNamaSatker = session()->get('nama_satker');
        if (in_array($userRole, ['satker', 'opd'])) {
            // Fallback: resolve nama_satker dari DB jika sesi lama belum memilikinya
            if (!$userNamaSatker && $userSatker) {
                $db = \Config\Database::connect();
                foreach (['lpse_tender','lpse_nontender','lpse_pencatatan_nontender','lpse_pencatatan_swakelola','rup_penyedia','rup_swakelola'] as $tbl) {
                    $r = $db->table($tbl)->select('nama_satker')->where('kd_satker', $userSatker)->limit(1)->get()->getRowArray();
                    if ($r && !empty($r['nama_satker'])) {
                        $userNamaSatker = $r['nama_satker'];
                        session()->set('nama_satker', $userNamaSatker); // cache for this session
                        break;
                    }
                }
            }
            if ($userNamaSatker) {
                $builder->where('nama_satker', $userNamaSatker);
            }
        }

        // Apply status filter from frontend
        $statusFilter = $this->request->getGet('status') ?? '';
        if ($statusFilter && $statusFilter !== 'all') {
            $builder->where('status', $statusFilter);
        }

        // Apply opd filter from frontend (for swakelola mostly)
        $opdFilter = $this->request->getGet('opd') ?? '';
        if ($opdFilter && $opdFilter !== 'all') {
            $builder->where('nama_satker', $opdFilter);
        }

        // Apply jenis_pengadaan filter (for tender/nontender)
        $jenisPengadaanFilter = $this->request->getGet('jenis_pengadaan') ?? '';
        if ($jenisPengadaanFilter && $jenisPengadaanFilter !== 'all') {
            $builder->where('jenis_pengadaan', $jenisPengadaanFilter);
        }

        // Apply kategori_pengadaan filter (for pencatatan-nontender)
        $kategoriPengadaanFilter = $this->request->getGet('kategori_pengadaan') ?? '';
        if ($kategoriPengadaanFilter && $kategoriPengadaanFilter !== 'all') {
            if ($type === 'pencatatan-nontender') {
                $builder->where('kategori_pengadaan', $kategoriPengadaanFilter);
            }
        }

        // Apply metode filter (for tender, nontender, pencatatan-nontender)
        $metodeFilter = $this->request->getGet('metode') ?? '';
        if ($metodeFilter && $metodeFilter !== 'all') {
            if ($type === 'pencatatan-nontender') {
                $builder->where('mtd_pemilihan', $metodeFilter);
            } elseif ($type === 'tender' || $type === 'nontender') {
                $builder->where('metode', $metodeFilter);
            }
        }

        // Apply jenis filter (for pencatatan types)
        $jenisFilter = $this->request->getGet('jenis') ?? '';
        if ($jenisFilter && $jenisFilter !== 'all') {
            if ($type === 'pencatatan-nontender') {
                $builder->where('jenis_realisasi', $jenisFilter);
            } elseif ($type === 'pencatatan-swakelola') {
                $builder->where('jenis_realisasi', $jenisFilter);
            }
        }

        // Khusus pencatatan-swakelola: tampilkan hanya jika terealisasi (> 0) dan abaikan paket dibatalkan
        if ($type === 'pencatatan-swakelola') {
            $builder->where('realisasi >', 0);
            $builder->groupStart()
                ->where('status IS NULL')
                ->orWhere("LOWER(status) NOT LIKE '%batal%'")
                ->groupEnd();
            $builder->groupStart()
                ->where('status_swakelola_pct_ket IS NULL')
                ->orWhere("LOWER(status_swakelola_pct_ket) NOT LIKE '%batal%'")
                ->groupEnd();
            $builder->groupStart()
                ->where('alasan_pembatalan IS NULL')
                ->orWhere("TRIM(alasan_pembatalan) = ''")
                ->groupEnd();
        }

        $draw = $this->request->getGet('draw');
        $start = (int) ($this->request->getGet('start') ?? 0);
        $length = (int) ($this->request->getGet('length') ?? 10);
        $search = $this->request->getGet('search')['value'] ?? '';

        if ($search) {
            $builder->groupStart()
                ->like('nama_paket', $search)
                ->orLike('nama_satker', $search)
                ->orLike('kd_rup', $search)
                ->orLike('kd_paket', $search)
                ->orLike('tahun', $search);

            if ($type === 'pencatatan-swakelola') {
                $builder->orLike('nama_pelaksana', $search)
                    ->orLike('jenis_realisasi', $search)
                    ->orLike('status', $search);
            } elseif ($type === 'pencatatan-nontender') {
                $builder->orLike('nama_penyedia', $search)
                    ->orLike('jenis_realisasi', $search)
                    ->orLike('kategori_pengadaan', $search)
                    ->orLike('mtd_pemilihan', $search)
                    ->orLike('sumber_dana', $search)
                    ->orLike('status', $search);
            } elseif ($type === 'tender' || $type === 'nontender') {
                $builder->orLike('nama_penyedia', $search)
                    ->orLike('jenis_pengadaan', $search)
                    ->orLike('status', $search);
            }
            $builder->orLike('pagu', $search);
            $builder->groupEnd();
        }

        // Total records (unfiltered except year + satker + base business rules)
        $totalBuilder = $model->builder();
        if ($currentYear && $currentYear !== 'all') {
            $totalBuilder->where('tahun', (int) $currentYear);
        }
        if (in_array($userRole, ['satker', 'opd']) && $userNamaSatker) {
            $totalBuilder->where('nama_satker', $userNamaSatker);
        }
        if ($type === 'pencatatan-swakelola') {
            $totalBuilder->where('realisasi >', 0);
            $totalBuilder->groupStart()
                ->where('status IS NULL')
                ->orWhere("LOWER(status) NOT LIKE '%batal%'")
                ->groupEnd();
            $totalBuilder->groupStart()
                ->where('status_swakelola_pct_ket IS NULL')
                ->orWhere("LOWER(status_swakelola_pct_ket) NOT LIKE '%batal%'")
                ->groupEnd();
            $totalBuilder->groupStart()
                ->where('alasan_pembatalan IS NULL')
                ->orWhere("TRIM(alasan_pembatalan) = ''")
                ->groupEnd();
        }
        $totalRecords = $totalBuilder->countAllResults(false);

        $recordsFiltered = $builder->countAllResults(false);

        if ($length != -1)
            $builder->limit($length, $start);
        $data = $builder->get()->getResultArray();

        return $this->response->setJSON([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ]);
    }

    public function itkp()
    {
        $db = \Config\Database::connect();

        // 1. Available Years
        $years = [];
        foreach (['lpse_tender', 'lpse_nontender', 'epurchasing', 'lpse_pencatatan_nontender', 'lpse_pencatatan_swakelola'] as $tbl) {
            $yRes = $db->table($tbl)->select('tahun')->distinct()->get()->getResultArray();
            $years = array_merge($years, array_column($yRes, 'tahun'));
        }
        $availableYears = array_filter(array_unique($years));
        rsort($availableYears);
        if (empty($availableYears)) {
            $availableYears = [date('Y')];
        }

        // 2. Available Satkers / OPD
        $userRole = session()->get('role');
        $userNamaSatker = session()->get('nama_satker');
        $isRestricted = in_array($userRole, ['satker', 'opd']);

        if ($isRestricted && $userNamaSatker) {
            $availableSatkers = [$userNamaSatker];
        } else {
            $satkers = [];
            foreach (['lpse_tender', 'lpse_nontender', 'epurchasing', 'lpse_pencatatan_nontender', 'lpse_pencatatan_swakelola'] as $tbl) {
                $sRes = $db->table($tbl)->select('nama_satker')->distinct()->get()->getResultArray();
                $satkers = array_merge($satkers, array_column($sRes, 'nama_satker'));
            }
            $availableSatkers = array_values(array_filter(array_unique($satkers)));
            sort($availableSatkers);
        }

        return view('dashboard/table_itkp', [
            'title' => 'Data ITKP (Indeks Tata Kelola Pengadaan)',
            'user' => session()->get(),
            'availableYears' => $availableYears,
            'availableSatkers' => $availableSatkers
        ]);
    }

    private function _getItkpUnionSql()
    {
        return "
            SELECT 
                'Pemerintah Kabupaten Lombok Barat' AS nama_instansi,
                t.nama_satker,
                t.kd_satker,
                CAST(t.kd_paket AS CHAR) AS kd_paket,
                CAST(t.kd_rup AS CHAR) AS kd_rup,
                t.tahun,
                'Tender' AS sumber_transaksi,
                COALESCE(NULLIF(t.sumber_dana, ''), r.sumber_dana, 'APBD') AS sumber_dana,
                COALESCE(NULLIF(t.nama_penyedia, ''), '-') AS nama_penyedia,
                COALESCE(NULLIF(t.metode, ''), 'Tender') AS metode_pengadaan,
                COALESCE(NULLIF(t.jenis_pengadaan, ''), r.jenis_pengadaan, 'Pekerjaan Konstruksi/Barang/Jasa') AS jenis_pengadaan,
                t.nama_paket,
                COALESCE(NULLIF(t.status, ''), 'Selesai') AS status_paket,
                CASE WHEN t.nilai_kontrak > 0 THEN t.nilai_kontrak WHEN t.nilai_penawaran > 0 THEN t.nilai_penawaran ELSE t.pagu END AS total_nilai,
                0.0 AS nilai_pdn,
                0.0 AS nilai_umk,
                COALESCE(NULLIF(t.pagu, 0), r.pagu, 0) AS total_pagu,
                CASE 
                    WHEN COALESCE(NULLIF(t.pagu, 0), r.pagu, 0) > (CASE WHEN t.nilai_kontrak > 0 THEN t.nilai_kontrak WHEN t.nilai_penawaran > 0 THEN t.nilai_penawaran ELSE t.pagu END) 
                    THEN (COALESCE(NULLIF(t.pagu, 0), r.pagu, 0) - (CASE WHEN t.nilai_kontrak > 0 THEN t.nilai_kontrak WHEN t.nilai_penawaran > 0 THEN t.nilai_penawaran ELSE t.pagu END)) 
                    ELSE 0.0 
                END AS efisiensi,
                COALESCE(
                    CASE WHEN c.address IS NOT NULL AND c.address != '' AND c.address NOT LIKE '%*%' THEN (c.address COLLATE utf8mb4_general_ci) END,
                    CASE WHEN t.alamat_penyedia IS NOT NULL AND t.alamat_penyedia != '' AND t.alamat_penyedia NOT LIKE '%*%' THEN t.alamat_penyedia END,
                    CASE WHEN v.alamat_penyedia IS NOT NULL AND v.alamat_penyedia != '' AND v.alamat_penyedia NOT LIKE '%*%' THEN v.alamat_penyedia END,
                    NULLIF(t.alamat_penyedia, ''),
                    NULLIF(v.alamat_penyedia, ''),
                    NULLIF(c.address COLLATE utf8mb4_general_ci, ''),
                    '-'
                ) AS alamat_penyedia,
                COALESCE(NULLIF(DATE_FORMAT(t.tgl_mulai_pekerjaan, '%Y-%m-%d'), ''), '-') AS tgl_mulai_pekerjaan,
                COALESCE(NULLIF(DATE_FORMAT(t.tgl_selesai_pekerjaan, '%Y-%m-%d'), ''), '-') AS tgl_selesai_pekerjaan
            FROM lpse_tender t
            LEFT JOIN rup_penyedia r ON r.kd_rup = t.kd_rup
            LEFT JOIN inaproc_vendor_sync v ON v.kode_penyedia = t.kd_penyedia
            LEFT JOIN pengadaanpro_contractors c ON c.contractor_name COLLATE utf8mb4_general_ci = t.nama_penyedia

            UNION ALL

            SELECT 
                'Pemerintah Kabupaten Lombok Barat' AS nama_instansi,
                nt.nama_satker,
                nt.kd_satker,
                CAST(nt.kd_paket AS CHAR) AS kd_paket,
                CAST(nt.kd_rup AS CHAR) AS kd_rup,
                nt.tahun,
                'Non Tender' AS sumber_transaksi,
                COALESCE(r.sumber_dana, 'APBD') AS sumber_dana,
                COALESCE(NULLIF(nt.nama_penyedia, ''), '-') AS nama_penyedia,
                COALESCE(NULLIF(nt.metode, ''), 'Pengadaan Langsung') AS metode_pengadaan,
                COALESCE(NULLIF(nt.jenis_pengadaan, ''), r.jenis_pengadaan, 'Pengadaan Barang/Jasa') AS jenis_pengadaan,
                nt.nama_paket,
                COALESCE(NULLIF(nt.status, ''), 'Selesai') AS status_paket,
                CASE WHEN nt.nilai_kontrak > 0 THEN nt.nilai_kontrak WHEN nt.nilai_negosiasi > 0 THEN nt.nilai_negosiasi ELSE nt.pagu END AS total_nilai,
                COALESCE(nt.nilai_pdn_kontrak, 0.0) AS nilai_pdn,
                COALESCE(nt.nilai_umk_kontrak, 0.0) AS nilai_umk,
                COALESCE(NULLIF(nt.pagu, 0), r.pagu, 0) AS total_pagu,
                CASE 
                    WHEN COALESCE(NULLIF(nt.pagu, 0), r.pagu, 0) > (CASE WHEN nt.nilai_kontrak > 0 THEN nt.nilai_kontrak WHEN nt.nilai_negosiasi > 0 THEN nt.nilai_negosiasi ELSE nt.pagu END) 
                    THEN (COALESCE(NULLIF(nt.pagu, 0), r.pagu, 0) - (CASE WHEN nt.nilai_kontrak > 0 THEN nt.nilai_kontrak WHEN nt.nilai_negosiasi > 0 THEN nt.nilai_negosiasi ELSE nt.pagu END)) 
                    ELSE 0.0 
                END AS efisiensi,
                COALESCE(
                    CASE WHEN c.address IS NOT NULL AND c.address != '' AND c.address NOT LIKE '%*%' THEN (c.address COLLATE utf8mb4_general_ci) END,
                    CASE WHEN nt.alamat_penyedia IS NOT NULL AND nt.alamat_penyedia != '' AND nt.alamat_penyedia NOT LIKE '%*%' THEN nt.alamat_penyedia END,
                    CASE WHEN v.alamat_penyedia IS NOT NULL AND v.alamat_penyedia != '' AND v.alamat_penyedia NOT LIKE '%*%' THEN v.alamat_penyedia END,
                    NULLIF(nt.alamat_penyedia, ''),
                    NULLIF(v.alamat_penyedia, ''),
                    NULLIF(c.address COLLATE utf8mb4_general_ci, ''),
                    '-'
                ) AS alamat_penyedia,
                COALESCE(NULLIF(DATE_FORMAT(nt.tgl_mulai_pekerjaan, '%Y-%m-%d'), ''), '-') AS tgl_mulai_pekerjaan,
                COALESCE(NULLIF(DATE_FORMAT(nt.tgl_selesai_pekerjaan, '%Y-%m-%d'), ''), '-') AS tgl_selesai_pekerjaan
            FROM lpse_nontender nt
            LEFT JOIN rup_penyedia r ON r.kd_rup = nt.kd_rup
            LEFT JOIN inaproc_vendor_sync v ON v.kode_penyedia = nt.kd_penyedia
            LEFT JOIN pengadaanpro_contractors c ON c.contractor_name COLLATE utf8mb4_general_ci = nt.nama_penyedia

            UNION ALL

            SELECT 
                'Pemerintah Kabupaten Lombok Barat' AS nama_instansi,
                ep.nama_satker,
                ep.kd_satker,
                CAST(ep.kd_paket AS CHAR) AS kd_paket,
                CAST(ep.rup_code AS CHAR) AS kd_rup,
                ep.tahun,
                'E-Purchasing' AS sumber_transaksi,
                COALESCE(NULLIF(ep.sumber_dana, ''), r.sumber_dana, 'APBD') AS sumber_dana,
                COALESCE(NULLIF(ep.nama_penyedia, ''), '-') AS nama_penyedia,
                'E-Purchasing' AS metode_pengadaan,
                COALESCE(r.jenis_pengadaan, 'Pengadaan Barang / Jasa') AS jenis_pengadaan,
                ep.nama_paket,
                COALESCE(NULLIF(ep.status, ''), 'Selesai') AS status_paket,
                CASE WHEN ep.total_harga > 0 THEN ep.total_harga ELSE ep.pagu END AS total_nilai,
                CASE WHEN ep.total_harga > 0 THEN ep.total_harga ELSE ep.pagu END AS nilai_pdn,
                0.0 AS nilai_umk,
                COALESCE(NULLIF(ep.pagu, 0), r.pagu, 0) AS total_pagu,
                CASE 
                    WHEN COALESCE(NULLIF(ep.pagu, 0), r.pagu, 0) > (CASE WHEN ep.total_harga > 0 THEN ep.total_harga ELSE ep.pagu END) 
                    THEN (COALESCE(NULLIF(ep.pagu, 0), r.pagu, 0) - (CASE WHEN ep.total_harga > 0 THEN ep.total_harga ELSE ep.pagu END)) 
                    ELSE 0.0 
                END AS efisiensi,
                COALESCE(
                    CASE WHEN c.address IS NOT NULL AND c.address != '' AND c.address NOT LIKE '%*%' THEN (c.address COLLATE utf8mb4_general_ci) END,
                    CASE WHEN v.alamat_penyedia IS NOT NULL AND v.alamat_penyedia != '' AND v.alamat_penyedia NOT LIKE '%*%' THEN v.alamat_penyedia END,
                    NULLIF(v.alamat_penyedia, ''),
                    NULLIF(c.address COLLATE utf8mb4_general_ci, ''),
                    '-'
                ) AS alamat_penyedia,
                COALESCE(NULLIF(DATE_FORMAT(ep.order_date, '%Y-%m-%d'), ''), '-') AS tgl_mulai_pekerjaan,
                COALESCE(NULLIF(DATE_FORMAT(ep.order_date, '%Y-%m-%d'), ''), '-') AS tgl_selesai_pekerjaan
            FROM epurchasing ep
            LEFT JOIN rup_penyedia r ON r.kd_rup = ep.rup_code
            LEFT JOIN inaproc_vendor_sync v ON v.kode_penyedia = ep.kode_penyedia
            LEFT JOIN pengadaanpro_contractors c ON c.contractor_name COLLATE utf8mb4_general_ci = ep.nama_penyedia

            UNION ALL

            SELECT 
                'Pemerintah Kabupaten Lombok Barat' AS nama_instansi,
                pct_nt.nama_satker,
                pct_nt.kd_satker,
                CAST(pct_nt.kd_paket AS CHAR) AS kd_paket,
                CAST(pct_nt.kd_rup AS CHAR) AS kd_rup,
                pct_nt.tahun,
                'Pencatatan Non Tender' AS sumber_transaksi,
                COALESCE(r.sumber_dana, 'APBD') AS sumber_dana,
                COALESCE(NULLIF(pct_nt.nama_penyedia, ''), '-') AS nama_penyedia,
                COALESCE(NULLIF(pct_nt.jenis_realisasi, ''), 'Pencatatan Non Tender') AS metode_pengadaan,
                COALESCE(r.jenis_pengadaan, 'Pengadaan Langsung/Lainnya') AS jenis_pengadaan,
                pct_nt.nama_paket,
                COALESCE(NULLIF(pct_nt.status, ''), 'Paket Selesai') AS status_paket,
                CASE WHEN pct_nt.realisasi > 0 THEN pct_nt.realisasi ELSE pct_nt.pagu END AS total_nilai,
                0.0 AS nilai_pdn,
                0.0 AS nilai_umk,
                COALESCE(NULLIF(pct_nt.pagu, 0), r.pagu, 0) AS total_pagu,
                CASE 
                    WHEN COALESCE(NULLIF(pct_nt.pagu, 0), r.pagu, 0) > (CASE WHEN pct_nt.realisasi > 0 THEN pct_nt.realisasi ELSE pct_nt.pagu END) 
                    THEN (COALESCE(NULLIF(pct_nt.pagu, 0), r.pagu, 0) - (CASE WHEN pct_nt.realisasi > 0 THEN pct_nt.realisasi ELSE pct_nt.pagu END)) 
                    ELSE 0.0 
                END AS efisiensi,
                COALESCE(
                    CASE WHEN c.address IS NOT NULL AND c.address != '' AND c.address NOT LIKE '%*%' THEN (c.address COLLATE utf8mb4_general_ci) END,
                    CASE WHEN v.alamat_penyedia IS NOT NULL AND v.alamat_penyedia != '' AND v.alamat_penyedia NOT LIKE '%*%' THEN v.alamat_penyedia END,
                    NULLIF(v.alamat_penyedia, ''),
                    NULLIF(c.address COLLATE utf8mb4_general_ci, ''),
                    '-'
                ) AS alamat_penyedia,
                COALESCE(NULLIF(DATE_FORMAT(pct_nt.tgl_buat_paket, '%Y-%m-%d'), ''), '-') AS tgl_mulai_pekerjaan,
                COALESCE(NULLIF(DATE_FORMAT(pct_nt.tgl_selesai_paket, '%Y-%m-%d'), ''), '-') AS tgl_selesai_pekerjaan
            FROM lpse_pencatatan_nontender pct_nt
            LEFT JOIN rup_penyedia r ON r.kd_rup = pct_nt.kd_rup
            LEFT JOIN inaproc_vendor_sync v ON v.kode_penyedia = pct_nt.kd_penyedia
            LEFT JOIN pengadaanpro_contractors c ON c.contractor_name COLLATE utf8mb4_general_ci = pct_nt.nama_penyedia

            UNION ALL

            SELECT 
                'Pemerintah Kabupaten Lombok Barat' AS nama_instansi,
                pct_sw.nama_satker,
                pct_sw.kd_satker,
                CAST(pct_sw.kd_paket AS CHAR) AS kd_paket,
                CAST(pct_sw.kd_rup AS CHAR) AS kd_rup,
                pct_sw.tahun,
                'Swakelola' AS sumber_transaksi,
                'APBD' AS sumber_dana,
                COALESCE(NULLIF(pct_sw.nama_pelaksana, ''), '-') AS nama_penyedia,
                COALESCE(NULLIF(pct_sw.jenis_realisasi, ''), 'Swakelola') AS metode_pengadaan,
                'Swakelola' AS jenis_pengadaan,
                pct_sw.nama_paket,
                COALESCE(NULLIF(pct_sw.status, ''), 'Selesai') AS status_paket,
                CASE WHEN pct_sw.realisasi > 0 THEN pct_sw.realisasi ELSE pct_sw.pagu END AS total_nilai,
                0.0 AS nilai_pdn,
                0.0 AS nilai_umk,
                COALESCE(NULLIF(pct_sw.pagu, 0), r_sw.pagu, 0) AS total_pagu,
                CASE 
                    WHEN COALESCE(NULLIF(pct_sw.pagu, 0), r_sw.pagu, 0) > (CASE WHEN pct_sw.realisasi > 0 THEN pct_sw.realisasi ELSE pct_sw.pagu END) 
                    THEN (COALESCE(NULLIF(pct_sw.pagu, 0), r_sw.pagu, 0) - (CASE WHEN pct_sw.realisasi > 0 THEN pct_sw.realisasi ELSE pct_sw.pagu END)) 
                    ELSE 0.0 
                END AS efisiensi,
                '-' AS alamat_penyedia,
                COALESCE(NULLIF(DATE_FORMAT(r_sw.tgl_awal_pelaksanaan, '%Y-%m-%d'), ''), '-') AS tgl_mulai_pekerjaan,
                COALESCE(NULLIF(DATE_FORMAT(r_sw.tgl_akhir_pelaksanaan, '%Y-%m-%d'), ''), '-') AS tgl_selesai_pekerjaan
            FROM lpse_pencatatan_swakelola pct_sw
            LEFT JOIN rup_swakelola r_sw ON r_sw.kd_rup = pct_sw.kd_rup
            WHERE pct_sw.realisasi > 0
              AND (pct_sw.status IS NULL OR LOWER(pct_sw.status) NOT LIKE '%batal%')
              AND (pct_sw.status_swakelola_pct_ket IS NULL OR LOWER(pct_sw.status_swakelola_pct_ket) NOT LIKE '%batal%')
              AND (pct_sw.alasan_pembatalan IS NULL OR TRIM(pct_sw.alasan_pembatalan) = '')
        ";
    }

    public function getItkpDatatables()
    {
        $db = \Config\Database::connect();
        $userRole = session()->get('role');
        $userNamaSatker = session()->get('nama_satker');
        $userKdSatker = session()->get('kd_satker');
        $isRestricted = in_array($userRole, ['satker', 'opd']);

        // Resolve nama_satker for legacy sessions
        if ($isRestricted && !$userNamaSatker && $userKdSatker) {
            foreach (['lpse_tender', 'lpse_nontender', 'lpse_pencatatan_nontender', 'lpse_pencatatan_swakelola', 'rup_penyedia', 'rup_swakelola'] as $tbl) {
                $r = $db->table($tbl)->select('nama_satker')->where('kd_satker', $userKdSatker)->limit(1)->get()->getRowArray();
                if ($r && !empty($r['nama_satker'])) {
                    $userNamaSatker = $r['nama_satker'];
                    session()->set('nama_satker', $userNamaSatker);
                    break;
                }
            }
        }

        $yearFilter = $this->request->getGet('year') ?? 'all';
        $opdFilter = $this->request->getGet('opd') ?? 'all';
        $sumberFilter = $this->request->getGet('sumber_transaksi') ?? 'all';

        // DataTables parameters
        $draw = (int) ($this->request->getGet('draw') ?? 1);
        $start = (int) ($this->request->getGet('start') ?? 0);
        $length = (int) ($this->request->getGet('length') ?? 10);
        $search = trim((string) ($this->request->getGet('search')['value'] ?? ''));
        $order = $this->request->getGet('order');

        // Column mapping for order
        $colNames = [
            0 => 'nama_instansi',
            1 => 'nama_satker',
            2 => 'kd_paket',
            3 => 'kd_rup',
            4 => 'tahun',
            5 => 'sumber_transaksi',
            6 => 'sumber_dana',
            7 => 'nama_penyedia',
            8 => 'metode_pengadaan',
            9 => 'jenis_pengadaan',
            10 => 'nama_paket',
            11 => 'status_paket',
            12 => 'total_nilai',
            13 => 'nilai_pdn',
            14 => 'nilai_umk',
            15 => 'total_pagu',
            16 => 'efisiensi',
            17 => 'alamat_penyedia',
            18 => 'tgl_mulai_pekerjaan',
            19 => 'tgl_selesai_pekerjaan'
        ];

        $orderSql = "ORDER BY nama_satker ASC, tahun DESC, total_nilai DESC";
        if (!empty($order) && is_array($order)) {
            $orderParts = [];
            foreach ($order as $ord) {
                if (isset($ord['column'])) {
                    $colIdx = (int) $ord['column'];
                    $dir = (strtolower($ord['dir'] ?? 'asc') === 'desc') ? 'DESC' : 'ASC';
                    if (isset($colNames[$colIdx])) {
                        $orderParts[] = "`" . $colNames[$colIdx] . "` " . $dir;
                    }
                }
            }
            if (!empty($orderParts)) {
                $orderSql = "ORDER BY " . implode(", ", $orderParts);
            }
        }

        $baseUnionSql = $this->_getItkpUnionSql();

        // Build WHERE clauses on the wrapped UNION
        $where = [];
        $params = [];

        // Year filter
        if ($yearFilter !== 'all' && is_numeric($yearFilter)) {
            $where[] = "tahun = ?";
            $params[] = (int) $yearFilter;
        }

        // OPD filter (RBAC enforced or dropdown selected)
        if ($isRestricted && $userNamaSatker) {
            $where[] = "nama_satker = ?";
            $params[] = $userNamaSatker;
        } elseif ($opdFilter !== 'all' && $opdFilter !== '') {
            $where[] = "nama_satker = ?";
            $params[] = $opdFilter;
        }

        // PPK filtering
        if ($userRole === 'ppk') {
            $ppkRups = $this->_getPpkRupCodes();
            if (!empty($ppkRups)) {
                $placeholders = implode(',', array_fill(0, count($ppkRups), '?'));
                $where[] = "kd_rup IN ($placeholders)";
                $params = array_merge($params, $ppkRups);
            } else {
                $where[] = "1 = 0";
            }
        }

        // Sumber transaksi filter
        if ($sumberFilter !== 'all' && $sumberFilter !== '') {
            $where[] = "LOWER(sumber_transaksi) = LOWER(?)";
            $params[] = $sumberFilter;
        }

        // Global search
        if ($search !== '') {
            $where[] = "(
                nama_paket LIKE ? OR 
                nama_satker LIKE ? OR 
                kd_paket LIKE ? OR 
                kd_rup LIKE ? OR 
                nama_penyedia LIKE ? OR 
                alamat_penyedia LIKE ? OR 
                metode_pengadaan LIKE ? OR 
                jenis_pengadaan LIKE ? OR 
                sumber_transaksi LIKE ? OR 
                sumber_dana LIKE ? OR 
                status_paket LIKE ?
            )";
            $searchParam = "%{$search}%";
            for ($i = 0; $i < 11; $i++) {
                $params[] = $searchParam;
            }
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // 1. Total filtered records & summary aggregates
        $summarySql = "SELECT 
            COUNT(*) as total_count,
            COALESCE(SUM(total_nilai), 0) as sum_total_nilai,
            COALESCE(SUM(total_pagu), 0) as sum_total_pagu,
            COALESCE(SUM(efisiensi), 0) as sum_efisiensi,
            COALESCE(SUM(nilai_pdn), 0) as sum_nilai_pdn,
            COALESCE(SUM(nilai_umk), 0) as sum_nilai_umk
        FROM ({$baseUnionSql}) as itkp_data {$whereClause}";

        $summaryRes = $db->query($summarySql, $params)->getRowArray();
        $recordsFiltered = (int) ($summaryRes['total_count'] ?? 0);
        $sumTotalNilai = (float) ($summaryRes['sum_total_nilai'] ?? 0);
        $sumTotalPagu = (float) ($summaryRes['sum_total_pagu'] ?? 0);
        $sumEfisiensi = (float) ($summaryRes['sum_efisiensi'] ?? 0);
        $sumNilaiPdn = (float) ($summaryRes['sum_nilai_pdn'] ?? 0);
        $sumNilaiUmk = (float) ($summaryRes['sum_nilai_umk'] ?? 0);

        // 2. Total unfiltered records count (for DataTables)
        $totalUnfilteredRes = $db->query("SELECT COUNT(*) as cnt FROM ({$baseUnionSql}) as itkp_data")->getRowArray();
        $recordsTotal = (int) ($totalUnfilteredRes['cnt'] ?? 0);

        // 3. Paginated Data Query
        $limitClause = ($length != -1) ? "LIMIT {$length} OFFSET {$start}" : "";
        $dataSql = "SELECT * FROM ({$baseUnionSql}) as itkp_data {$whereClause} {$orderSql} {$limitClause}";
        $dataRows = $db->query($dataSql, $params)->getResultArray();

        return $this->response->setJSON([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'summary' => [
                'total_paket' => $recordsFiltered,
                'total_nilai' => $sumTotalNilai,
                'total_pagu' => $sumTotalPagu,
                'total_efisiensi' => $sumEfisiensi,
                'total_pdn' => $sumNilaiPdn,
                'total_umk' => $sumNilaiUmk
            ],
            'data' => $dataRows
        ]);
    }

    public function exportItkp()
    {
        $db = \Config\Database::connect();
        $userRole = session()->get('role');
        $userNamaSatker = session()->get('nama_satker');
        $isRestricted = in_array($userRole, ['satker', 'opd']);

        $yearFilter = $this->request->getGet('year') ?? 'all';
        $opdFilter = $this->request->getGet('opd') ?? 'all';
        $sumberFilter = $this->request->getGet('sumber_transaksi') ?? 'all';
        $search = trim((string) ($this->request->getGet('search') ?? ''));

        $baseUnionSql = $this->_getItkpUnionSql();
        $where = [];
        $params = [];

        if ($yearFilter !== 'all' && is_numeric($yearFilter)) {
            $where[] = "tahun = ?";
            $params[] = (int) $yearFilter;
        }

        if ($isRestricted && $userNamaSatker) {
            $where[] = "nama_satker = ?";
            $params[] = $userNamaSatker;
        } elseif ($opdFilter !== 'all' && $opdFilter !== '') {
            $where[] = "nama_satker = ?";
            $params[] = $opdFilter;
        }

        if ($userRole === 'ppk') {
            $ppkRups = $this->_getPpkRupCodes();
            if (!empty($ppkRups)) {
                $placeholders = implode(',', array_fill(0, count($ppkRups), '?'));
                $where[] = "kd_rup IN ($placeholders)";
                $params = array_merge($params, $ppkRups);
            } else {
                $where[] = "1 = 0";
            }
        }

        if ($sumberFilter !== 'all' && $sumberFilter !== '') {
            $where[] = "LOWER(sumber_transaksi) = LOWER(?)";
            $params[] = $sumberFilter;
        }

        if ($search !== '') {
            $where[] = "(
                nama_paket LIKE ? OR 
                nama_satker LIKE ? OR 
                kd_paket LIKE ? OR 
                kd_rup LIKE ? OR 
                nama_penyedia LIKE ? OR 
                alamat_penyedia LIKE ? OR 
                metode_pengadaan LIKE ? OR 
                jenis_pengadaan LIKE ? OR 
                sumber_transaksi LIKE ? OR 
                sumber_dana LIKE ? OR 
                status_paket LIKE ?
            )";
            $searchParam = "%{$search}%";
            for ($i = 0; $i < 11; $i++) {
                $params[] = $searchParam;
            }
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        $dataSql = "SELECT * FROM ({$baseUnionSql}) as itkp_data {$whereClause} ORDER BY nama_satker ASC, tahun DESC, total_nilai DESC";
        $filtered = $db->query($dataSql, $params)->getResultArray();

        $filename = 'Data_ITKP_' . ($yearFilter !== 'all' ? $yearFilter : 'Semua_Tahun') . '_' . date('Ymd_His') . '.xls';
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo '<table border="1">';
        echo '<thead>';
        echo '<tr style="background-color: #696cff; color: white; font-weight: bold; text-align: center;">';
        echo '<th>No</th>';
        echo '<th>Nama Instansi</th>';
        echo '<th>Nama Satuan Kerja</th>';
        echo '<th>Kode Paket</th>';
        echo '<th>Kode Rup</th>';
        echo '<th>Tahun Anggaran</th>';
        echo '<th>Sumber Transaksi</th>';
        echo '<th>Sumber Dana</th>';
        echo '<th>Nama Penyedia</th>';
        echo '<th>Metode Pengadaan</th>';
        echo '<th>Jenis Pengadaan</th>';
        echo '<th>Nama Paket</th>';
        echo '<th>Status Paket</th>';
        echo '<th>Total Nilai</th>';
        echo '<th>Nilai PDN</th>';
        echo '<th>Nilai UMK</th>';
        echo '<th>Total Pagu</th>';
        echo '<th>Efisiensi</th>';
        echo '<th>Alamat Penyedia</th>';
        echo '<th>Tanggal Mulai Pekerjaan</th>';
        echo '<th>Tanggal Selesai Pekerjaan</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        $no = 1;
        foreach ($filtered as $row) {
            echo '<tr>';
            echo '<td style="text-align: center;">' . $no++ . '</td>';
            echo '<td>' . htmlspecialchars($row['nama_instansi'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['nama_satker'] ?? '') . '</td>';
            echo '<td style="vnd.ms-excel.numberformat:@">' . htmlspecialchars($row['kd_paket'] ?? '') . '</td>';
            echo '<td style="vnd.ms-excel.numberformat:@">' . htmlspecialchars($row['kd_rup'] ?? '') . '</td>';
            echo '<td style="text-align: center;">' . htmlspecialchars($row['tahun'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['sumber_transaksi'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['sumber_dana'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['nama_penyedia'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['metode_pengadaan'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['jenis_pengadaan'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['nama_paket'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['status_paket'] ?? '') . '</td>';
            echo '<td>' . (float) ($row['total_nilai'] ?? 0) . '</td>';
            echo '<td>' . (float) ($row['nilai_pdn'] ?? 0) . '</td>';
            echo '<td>' . (float) ($row['nilai_umk'] ?? 0) . '</td>';
            echo '<td>' . (float) ($row['total_pagu'] ?? 0) . '</td>';
            echo '<td>' . (float) ($row['efisiensi'] ?? 0) . '</td>';
            echo '<td>' . htmlspecialchars($row['alamat_penyedia'] ?? '-') . '</td>';
            echo '<td style="text-align: center;">' . htmlspecialchars($row['tgl_mulai_pekerjaan'] ?? '-') . '</td>';
            echo '<td style="text-align: center;">' . htmlspecialchars($row['tgl_selesai_pekerjaan'] ?? '-') . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        exit;
    }

    public function rupItkp()
    {
        $db = \Config\Database::connect();

        // 1. Available Years from RUP tables
        $yearsP = $db->table('rup_penyedia')->select('tahun')->distinct()->get()->getResultArray();
        $yearsS = $db->table('rup_swakelola')->select('tahun')->distinct()->get()->getResultArray();
        $availableYears = array_filter(array_unique(array_merge(array_column($yearsP, 'tahun'), array_column($yearsS, 'tahun'))));
        rsort($availableYears);
        if (empty($availableYears)) {
            $availableYears = [date('Y')];
        }

        // 2. Available Satkers / OPD
        $userRole = session()->get('role');
        $userNamaSatker = session()->get('nama_satker');
        $isRestricted = in_array($userRole, ['satker', 'opd']);

        if ($isRestricted && $userNamaSatker) {
            $availableSatkers = [$userNamaSatker];
        } else {
            $satkersP = $db->table('rup_penyedia')->select('nama_satker')->distinct()->get()->getResultArray();
            $satkersS = $db->table('rup_swakelola')->select('nama_satker')->distinct()->get()->getResultArray();
            $allSatkers = array_merge(array_column($satkersP, 'nama_satker'), array_column($satkersS, 'nama_satker'));
            $availableSatkers = array_values(array_filter(array_unique($allSatkers)));
            sort($availableSatkers);
        }

        return view('dashboard/rup_itkp', [
            'title' => 'Data ITKP (SiRUP) - Rencana Umum Pengadaan',
            'user' => session()->get(),
            'availableYears' => $availableYears,
            'availableSatkers' => $availableSatkers
        ]);
    }

    private function _getRupItkpUnionSql($caraPengadaan = 'all')
    {
        $sqlPenyedia = "
            SELECT 
                'Pemerintah Daerah Kabupaten Lombok Barat' AS nama_instansi,
                rp.nama_satker,
                rp.kd_satker,
                rp.tahun,
                'Penyedia' AS cara_pengadaan,
                COALESCE(NULLIF(rp.metode_pengadaan, ''), 'Pengadaan Langsung') AS metode_pengadaan,
                COALESCE(NULLIF(rp.jenis_pengadaan, ''), 'Barang/Jasa') AS jenis_pengadaan,
                rp.nama_paket,
                CAST(rp.kd_rup AS CHAR) AS kd_rup,
                COALESCE(NULLIF(rp.sumber_dana, ''), 'APBD') AS sumber_dana,
                COALESCE(NULLIF(rp.status_pdn, ''), '-') AS status_pdn,
                COALESCE(rp.pagu, 0.0) AS total_nilai
            FROM rup_penyedia rp
        ";

        $sqlSwakelola = "
            SELECT 
                'Pemerintah Daerah Kabupaten Lombok Barat' AS nama_instansi,
                rs.nama_satker,
                rs.kd_satker,
                rs.tahun,
                'Swakelola' AS cara_pengadaan,
                CASE 
                    WHEN TRIM(rs.tipe_swakelola) IN ('1', 'Tipe 1', 'Tipe I', 'I') THEN 'Swakelola Tipe I'
                    WHEN TRIM(rs.tipe_swakelola) IN ('2', 'Tipe 2', 'Tipe II', 'II') THEN 'Swakelola Tipe II'
                    WHEN TRIM(rs.tipe_swakelola) IN ('3', 'Tipe 3', 'Tipe III', 'III') THEN 'Swakelola Tipe III'
                    WHEN TRIM(rs.tipe_swakelola) IN ('4', 'Tipe 4', 'Tipe IV', 'IV') THEN 'Swakelola Tipe IV'
                    WHEN rs.tipe_swakelola IS NOT NULL AND TRIM(rs.tipe_swakelola) != '' 
                    THEN CONCAT('Swakelola Tipe ', rs.tipe_swakelola) 
                    ELSE 'Swakelola Tipe I' 
                END AS metode_pengadaan,
                '' AS jenis_pengadaan,
                rs.nama_paket,
                CAST(rs.kd_rup AS CHAR) AS kd_rup,
                'APBD' AS sumber_dana,
                '-' AS status_pdn,
                COALESCE(rs.pagu, 0.0) AS total_nilai
            FROM rup_swakelola rs
        ";

        $cara = strtolower(trim((string)$caraPengadaan));
        if ($cara === 'penyedia') {
            return $sqlPenyedia;
        } elseif ($cara === 'swakelola') {
            return $sqlSwakelola;
        }

        return "{$sqlPenyedia} UNION ALL {$sqlSwakelola}";
    }

    public function getRupItkpDatatables()
    {
        $db = \Config\Database::connect();
        $userRole = session()->get('role');
        $userNamaSatker = session()->get('nama_satker');
        $isRestricted = in_array($userRole, ['satker', 'opd']);

        $draw = intval($this->request->getGet('draw') ?? 1);
        $start = intval($this->request->getGet('start') ?? 0);
        $length = intval($this->request->getGet('length') ?? 10);
        
        $yearFilter = $this->request->getGet('year') ?? 'all';
        $opdFilter = $this->request->getGet('opd') ?? 'all';
        $caraFilter = $this->request->getGet('cara_pengadaan') ?? 'all';
        $jenisFilter = $this->request->getGet('jenis_pengadaan') ?? 'all';
        $pdnFilter = $this->request->getGet('status_pdn') ?? 'all';

        $searchArr = $this->request->getGet('search');
        $search = trim((string) (is_array($searchArr) ? ($searchArr['value'] ?? '') : ($searchArr ?? '')));

        // Order
        $orderColIndex = $this->request->getGet('order')[0]['column'] ?? 11;
        $orderDir = strtolower($this->request->getGet('order')[0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
        $columns = [
            0 => 'kd_rup',
            1 => 'nama_instansi',
            2 => 'nama_satker',
            3 => 'tahun',
            4 => 'cara_pengadaan',
            5 => 'metode_pengadaan',
            6 => 'jenis_pengadaan',
            7 => 'nama_paket',
            8 => 'kd_rup',
            9 => 'sumber_dana',
            10 => 'status_pdn',
            11 => 'total_nilai'
        ];
        $orderColumn = $columns[$orderColIndex] ?? 'total_nilai';
        $orderSql = "ORDER BY {$orderColumn} {$orderDir}";

        $baseUnionSql = $this->_getRupItkpUnionSql($caraFilter);

        $where = [];
        $params = [];

        // Year filter
        if ($yearFilter !== 'all' && is_numeric($yearFilter)) {
            $where[] = "tahun = ?";
            $params[] = (int) $yearFilter;
        }

        // OPD filter
        if ($isRestricted && $userNamaSatker) {
            $where[] = "nama_satker = ?";
            $params[] = $userNamaSatker;
        } elseif ($opdFilter !== 'all' && $opdFilter !== '') {
            $where[] = "nama_satker = ?";
            $params[] = $opdFilter;
        }

        // PPK filtering
        if ($userRole === 'ppk') {
            $ppkRups = $this->_getPpkRupCodes();
            if (!empty($ppkRups)) {
                $placeholders = implode(',', array_fill(0, count($ppkRups), '?'));
                $where[] = "kd_rup IN ($placeholders)";
                $params = array_merge($params, $ppkRups);
            } else {
                $where[] = "1 = 0";
            }
        }

        // Jenis Pengadaan filter
        if ($jenisFilter !== 'all' && $jenisFilter !== '') {
            $where[] = "LOWER(jenis_pengadaan) LIKE LOWER(?)";
            $params[] = "%{$jenisFilter}%";
        }

        // PDN filter
        if ($pdnFilter === 'PDN') {
            $where[] = "LOWER(status_pdn) LIKE '%pdn%' AND LOWER(status_pdn) NOT LIKE '%non%'";
        } elseif ($pdnFilter === 'Non-PDN') {
            $where[] = "(LOWER(status_pdn) LIKE '%non%' OR status_pdn = '-' OR status_pdn IS NULL)";
        }

        // Search
        if ($search !== '') {
            $where[] = "(
                nama_paket LIKE ? OR 
                nama_satker LIKE ? OR 
                kd_rup LIKE ? OR 
                metode_pengadaan LIKE ? OR 
                jenis_pengadaan LIKE ? OR 
                sumber_dana LIKE ?
            )";
            $searchParam = "%{$search}%";
            for ($i = 0; $i < 6; $i++) {
                $params[] = $searchParam;
            }
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // 1. Total filtered records
        $countSql = "SELECT COUNT(*) as total_count FROM ({$baseUnionSql}) as rup_data {$whereClause}";
        $countRes = $db->query($countSql, $params)->getRowArray();
        $recordsFiltered = (int) ($countRes['total_count'] ?? 0);

        // 2. Total unfiltered records count
        $unfilteredSql = "SELECT COUNT(*) as cnt FROM ({$baseUnionSql}) as rup_data";
        $unfilteredRes = $db->query($unfilteredSql)->getRowArray();
        $recordsTotal = (int) ($unfilteredRes['cnt'] ?? 0);

        // 3. Paginated Data Query
        $limitClause = ($length != -1) ? "LIMIT {$length} OFFSET {$start}" : "";
        $dataSql = "SELECT * FROM ({$baseUnionSql}) as rup_data {$whereClause} {$orderSql} {$limitClause}";
        $dataRows = $db->query($dataSql, $params)->getResultArray();

        return $this->response->setJSON([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $dataRows
        ]);
    }

    public function getRupItkpDashboardData()
    {
        $db = \Config\Database::connect();
        $userRole = session()->get('role');
        $userNamaSatker = session()->get('nama_satker');
        $isRestricted = in_array($userRole, ['satker', 'opd']);

        $yearFilter = $this->request->getGet('year') ?? 'all';
        $opdFilter = $this->request->getGet('opd') ?? 'all';
        $caraFilter = $this->request->getGet('cara_pengadaan') ?? 'all';
        $pdnFilter = $this->request->getGet('status_pdn') ?? 'all';

        $baseUnionSql = $this->_getRupItkpUnionSql($caraFilter);

        $where = [];
        $params = [];

        if ($yearFilter !== 'all' && is_numeric($yearFilter)) {
            $where[] = "tahun = ?";
            $params[] = (int) $yearFilter;
        }

        if ($isRestricted && $userNamaSatker) {
            $where[] = "nama_satker = ?";
            $params[] = $userNamaSatker;
        } elseif ($opdFilter !== 'all' && $opdFilter !== '') {
            $where[] = "nama_satker = ?";
            $params[] = $opdFilter;
        }

        if ($pdnFilter === 'PDN') {
            $where[] = "LOWER(status_pdn) LIKE '%pdn%' AND LOWER(status_pdn) NOT LIKE '%non%'";
        } elseif ($pdnFilter === 'Non-PDN') {
            $where[] = "(LOWER(status_pdn) LIKE '%non%' OR status_pdn = '-' OR status_pdn IS NULL)";
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Query 1: Aggregate totals & breakdown by cara_pengadaan & status_pdn
        $kpiSql = "
            SELECT 
                COUNT(*) as total_paket,
                COALESCE(SUM(total_nilai), 0) as total_pagu,
                COALESCE(SUM(CASE WHEN cara_pengadaan = 'Penyedia' THEN total_nilai ELSE 0 END), 0) as penyedia_pagu,
                COALESCE(SUM(CASE WHEN cara_pengadaan = 'Penyedia' THEN 1 ELSE 0 END), 0) as penyedia_paket,
                COALESCE(SUM(CASE WHEN cara_pengadaan = 'Swakelola' THEN total_nilai ELSE 0 END), 0) as swakelola_pagu,
                COALESCE(SUM(CASE WHEN cara_pengadaan = 'Swakelola' THEN 1 ELSE 0 END), 0) as swakelola_paket,
                COALESCE(SUM(CASE WHEN LOWER(status_pdn) LIKE '%pdn%' AND LOWER(status_pdn) NOT LIKE '%non%' THEN total_nilai ELSE 0 END), 0) as pdn_pagu,
                COALESCE(SUM(CASE WHEN LOWER(status_pdn) LIKE '%pdn%' AND LOWER(status_pdn) NOT LIKE '%non%' THEN 1 ELSE 0 END), 0) as pdn_paket
            FROM ({$baseUnionSql}) as rup_data {$whereClause}
        ";
        $kpi = $db->query($kpiSql, $params)->getRowArray();

        $totalPagu = (float)($kpi['total_pagu'] ?? 0);
        $totalPaket = (int)($kpi['total_paket'] ?? 0);
        $penyediaPagu = (float)($kpi['penyedia_pagu'] ?? 0);
        $penyediaPaket = (int)($kpi['penyedia_paket'] ?? 0);
        $swakelolaPagu = (float)($kpi['swakelola_pagu'] ?? 0);
        $swakelolaPaket = (int)($kpi['swakelola_paket'] ?? 0);
        $pdnPagu = (float)($kpi['pdn_pagu'] ?? 0);
        $pdnPaket = (int)($kpi['pdn_paket'] ?? 0);

        $pctPenyedia = $totalPagu > 0 ? round(($penyediaPagu / $totalPagu) * 100, 1) : 0;
        $pctSwakelola = $totalPagu > 0 ? round(($swakelolaPagu / $totalPagu) * 100, 1) : 0;
        $pctPdn = $penyediaPagu > 0 ? round(($pdnPagu / $penyediaPagu) * 100, 1) : 0;

        // Query 2: Breakdown per Jenis Pengadaan (hanya yang memiliki jenis pengadaan)
        $jenisWhere = !empty($whereClause) ? "{$whereClause} AND TRIM(COALESCE(jenis_pengadaan, '')) != ''" : "WHERE TRIM(COALESCE(jenis_pengadaan, '')) != ''";
        $jenisSql = "
            SELECT 
                jenis_pengadaan,
                COUNT(*) as paket,
                COALESCE(SUM(total_nilai), 0) as pagu
            FROM ({$baseUnionSql}) as rup_data {$jenisWhere}
            GROUP BY jenis_pengadaan
            ORDER BY pagu DESC
        ";
        $jenisRows = $db->query($jenisSql, $params)->getResultArray();
        $jenisCategories = [];
        $jenisData = [];
        $jenisPaket = [];
        foreach ($jenisRows as $jr) {
            $jenisCategories[] = $jr['jenis_pengadaan'];
            $jenisData[] = (float)$jr['pagu'];
            $jenisPaket[] = (int)$jr['paket'];
        }

        return $this->response->setJSON([
            'status' => 'success',
            'kpi' => [
                'total_pagu' => $totalPagu,
                'total_paket' => $totalPaket,
                'penyedia_pagu' => $penyediaPagu,
                'penyedia_paket' => $penyediaPaket,
                'swakelola_pagu' => $swakelolaPagu,
                'swakelola_paket' => $swakelolaPaket,
                'pdn_pagu' => $pdnPagu,
                'pdn_paket' => $pdnPaket,
                'pct_penyedia' => $pctPenyedia,
                'pct_swakelola' => $pctSwakelola,
                'pct_pdn' => $pctPdn
            ],
            'charts' => [
                'cara_pengadaan' => [
                    'labels' => ['Penyedia', 'Swakelola'],
                    'series' => [$penyediaPagu, $swakelolaPagu],
                    'series_paket' => [$penyediaPaket, $swakelolaPaket]
                ],
                'jenis_pengadaan' => [
                    'categories' => $jenisCategories,
                    'series' => $jenisData,
                    'series_paket' => $jenisPaket
                ]
            ]
        ]);
    }

    public function exportRupItkp()
    {
        $db = \Config\Database::connect();
        $userRole = session()->get('role');
        $userNamaSatker = session()->get('nama_satker');
        $isRestricted = in_array($userRole, ['satker', 'opd']);

        $yearFilter = $this->request->getGet('year') ?? 'all';
        $opdFilter = $this->request->getGet('opd') ?? 'all';
        $caraFilter = $this->request->getGet('cara_pengadaan') ?? 'all';
        $jenisFilter = $this->request->getGet('jenis_pengadaan') ?? 'all';
        $pdnFilter = $this->request->getGet('status_pdn') ?? 'all';
        $search = trim((string) ($this->request->getGet('search') ?? ''));

        $baseUnionSql = $this->_getRupItkpUnionSql($caraFilter);
        $where = [];
        $params = [];

        if ($yearFilter !== 'all' && is_numeric($yearFilter)) {
            $where[] = "tahun = ?";
            $params[] = (int) $yearFilter;
        }

        if ($isRestricted && $userNamaSatker) {
            $where[] = "nama_satker = ?";
            $params[] = $userNamaSatker;
        } elseif ($opdFilter !== 'all' && $opdFilter !== '') {
            $where[] = "nama_satker = ?";
            $params[] = $opdFilter;
        }

        if ($userRole === 'ppk') {
            $ppkRups = $this->_getPpkRupCodes();
            if (!empty($ppkRups)) {
                $placeholders = implode(',', array_fill(0, count($ppkRups), '?'));
                $where[] = "kd_rup IN ($placeholders)";
                $params = array_merge($params, $ppkRups);
            } else {
                $where[] = "1 = 0";
            }
        }

        if ($jenisFilter !== 'all' && $jenisFilter !== '') {
            $where[] = "LOWER(jenis_pengadaan) LIKE LOWER(?)";
            $params[] = "%{$jenisFilter}%";
        }

        if ($pdnFilter === 'PDN') {
            $where[] = "LOWER(status_pdn) LIKE '%pdn%' AND LOWER(status_pdn) NOT LIKE '%non%'";
        } elseif ($pdnFilter === 'Non-PDN') {
            $where[] = "(LOWER(status_pdn) LIKE '%non%' OR status_pdn = '-' OR status_pdn IS NULL)";
        }

        if ($search !== '') {
            $where[] = "(
                nama_paket LIKE ? OR 
                nama_satker LIKE ? OR 
                kd_rup LIKE ? OR 
                metode_pengadaan LIKE ? OR 
                jenis_pengadaan LIKE ? OR 
                sumber_dana LIKE ?
            )";
            $searchParam = "%{$search}%";
            for ($i = 0; $i < 6; $i++) {
                $params[] = $searchParam;
            }
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        $dataSql = "SELECT * FROM ({$baseUnionSql}) as rup_data {$whereClause} ORDER BY tahun DESC, total_nilai DESC";
        $filtered = $db->query($dataSql, $params)->getResultArray();

        $filename = 'Data_ITKP_SiRUP_' . ($yearFilter !== 'all' ? $yearFilter : 'Semua_Tahun') . '_' . date('Ymd_His') . '.xls';
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo '<table border="1">';
        echo '<thead>';
        echo '<tr style="background-color: #f1f5f9; color: #0f172a; font-weight: bold; text-align: center;">';
        echo '<th>No</th>';
        echo '<th>Nama Instansi</th>';
        echo '<th>Nama Satuan Kerja</th>';
        echo '<th>Tahun Anggaran</th>';
        echo '<th>Cara Pengadaan</th>';
        echo '<th>Metode Pengadaan</th>';
        echo '<th>Jenis Pengadaan</th>';
        echo '<th>Nama Paket</th>';
        echo '<th>Kode Rup</th>';
        echo '<th>Sumber Dana</th>';
        echo '<th>Produk Dalam Negeri</th>';
        echo '<th>Total Nilai</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        $no = 1;
        foreach ($filtered as $row) {
            echo '<tr>';
            echo '<td style="text-align: center;">' . $no++ . '</td>';
            echo '<td>' . htmlspecialchars($row['nama_instansi'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['nama_satker'] ?? '') . '</td>';
            echo '<td style="text-align: center;">' . htmlspecialchars($row['tahun'] ?? '') . '</td>';
            echo '<td style="text-align: center;">' . htmlspecialchars($row['cara_pengadaan'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['metode_pengadaan'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['jenis_pengadaan'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['nama_paket'] ?? '') . '</td>';
            echo '<td style="vnd.ms-excel.numberformat:@; text-align: center;">' . htmlspecialchars($row['kd_rup'] ?? '') . '</td>';
            echo '<td style="text-align: center;">' . htmlspecialchars($row['sumber_dana'] ?? '') . '</td>';
            echo '<td style="text-align: center;">' . htmlspecialchars($row['status_pdn'] ?? '-') . '</td>';
            echo '<td style="text-align: right;">' . (float) ($row['total_nilai'] ?? 0) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        exit;
    }

    public function clearCache()
    {
        cache()->clean();
        return redirect()->back()->with('success', 'Berhasil membersihkan cache. Seluruh data akan ditarik ulang dari API.');
    }

    private function _getInaprocVendorName($kode)
    {
        $dir = WRITEPATH . 'inaproc/vendors/';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $file = $dir . $kode . '.json';

        if (file_exists($file)) {
            $saved = json_decode(file_get_contents($file), true);

            if (is_array($saved) && array_key_exists('nama_penyedia', $saved)) {
                if (!empty($saved['_not_found'])) {
                    $lastAttempt = strtotime($saved['_last_attempt'] ?? '2000-01-01');
                    if ((time() - $lastAttempt) < 86400) {
                        return $kode;
                    }
                } else {
                    return $saved['nama_penyedia'] ?? '-';
                }
            }
        }

        $result = $this->inaprocApiService->getVendorDetail($kode);
        if (!empty($result['data']['nama_penyedia'])) {
            return $result['data']['nama_penyedia'];
        }

        file_put_contents($file, json_encode([
            'nama_penyedia' => null,
            '_not_found' => true,
            '_last_attempt' => date('Y-m-d H:i:s'),
        ]));
        return $kode;
    }

    private function _getInaprocProductNames($kodePenyedia, $prodIdsStr, $namaPaket = '')
    {
        $dir = WRITEPATH . 'inaproc/products/';
        if (!is_dir($dir))
            mkdir($dir, 0777, true);
        $file = $dir . $kodePenyedia . '.json';

        $prodMap = [];
        if (file_exists($file)) {
            $prodMap = json_decode(file_get_contents($file), true) ?: [];
        }

        $searchIds = array_map('trim', explode(',', $prodIdsStr));
        $missingIds = [];
        foreach ($searchIds as $sid) {
            if (!isset($prodMap[$sid]))
                $missingIds[] = $sid;
        }

        // If we have missing IDs and the file is old or non-existent, fetch from API
        if (!empty($missingIds)) {
            $client = \Config\Services::curlrequest();
            $token = getenv('INAPROC_BEARER_TOKEN') ?: 'inprc73221b186ad14d568b02f22b214c9574';
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
                        if ($pid)
                            $prodMap[$pid] = $pname;
                    }
                    file_put_contents($file, json_encode($prodMap, JSON_UNESCAPED_UNICODE));
                }
            } catch (\Exception $e) {
            }
        }

        $found = [];
        $hasNewFallback = false;
        foreach ($searchIds as $sid) {
            if (isset($prodMap[$sid])) {
                $found[] = $prodMap[$sid];
            } else {
                $fallback = $sid;
                if (!empty($namaPaket)) {
                    if (preg_match('/\(([^)]+)\)/', $namaPaket, $matches)) {
                        $fallback = trim($matches[1]);
                    } else {
                        $fallback = $namaPaket;
                    }
                }
                $prodMap[$sid] = $fallback;
                $hasNewFallback = true;
                $found[] = $fallback;
            }
        }

        if ($hasNewFallback) {
            file_put_contents($file, json_encode($prodMap, JSON_UNESCAPED_UNICODE));
        }

        return count($found) > 0 ? implode(', ', $found) : '-';
    }

    private function _parseDate($dateStr, $defaultYear)
    {
        if (empty($dateStr) || $dateStr === '-')
            return null;

        // If format is like YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            return strtotime($dateStr);
        }

        // Handle Indonesian month names (e.g. "Mei 2026" or "Mei")
        $months = [
            'januari' => 1,
            'februari' => 2,
            'maret' => 3,
            'april' => 4,
            'mei' => 5,
            'juni' => 6,
            'juli' => 7,
            'agustus' => 8,
            'september' => 9,
            'oktober' => 10,
            'november' => 11,
            'desember' => 12,
            'jan' => 1,
            'feb' => 2,
            'mar' => 3,
            'apr' => 4,
            'jun' => 6,
            'jul' => 7,
            'agu' => 8,
            'sep' => 9,
            'okt' => 10,
            'nov' => 11,
            'des' => 12
        ];

        $clean = strtolower(trim($dateStr));

        // Extract month and year
        $foundMonth = null;
        foreach ($months as $name => $num) {
            if (str_contains($clean, $name)) {
                $foundMonth = $num;
                break;
            }
        }

        $year = $defaultYear;
        if (preg_match('/\b(20\d{2})\b/', $clean, $matches)) {
            $year = $matches[1];
        }

        if ($foundMonth) {
            return strtotime("{$year}-{$foundMonth}-01");
        }

        $parsed = strtotime($dateStr);
        return $parsed !== false ? $parsed : null;
    }

    public function monevProyek()
    {
        $currentYear = $this->request->getGet('year') ?? date('Y');
        $activeProyekId = $this->request->getGet('proyek_id');
        $userId = session()->get('id');
        $userRole = trim(strtolower(session()->get('role') ?? ''));

        $proyekModel = new MonevProyekModel();

        // RBAC Filter: 
        // PPK only sees their own projects.
        // OPD / Satker only sees projects created by users (PPK) within their own kd_satker.
        if ($userRole === 'ppk') {
            $proyekList = $proyekModel->where('user_id', $userId)->findAll();
        } elseif (in_array($userRole, ['opd', 'satker'])) {
            $userKdSatker = session()->get('kd_satker');
            if ($userKdSatker) {
                $proyekList = $proyekModel->select('monev_proyek.*')
                    ->join('users', 'users.id = monev_proyek.user_id', 'left')
                    ->where('users.kd_satker', $userKdSatker)
                    ->findAll();
            } else {
                $proyekList = [];
            }
        } else {
            // Admin, Operator, etc see all
            $proyekList = $proyekModel->findAll();
        }

        // Enrich all projects with tender details
        $proyekList = array_map([$this, '_enrichProyekWithTender'], $proyekList);

        // Get active project
        $activeProyek = null;
        if (!empty($proyekList)) {
            if ($activeProyekId) {
                foreach ($proyekList as $p) {
                    if ($p['id'] == $activeProyekId) {
                        $activeProyek = $p;
                        break;
                    }
                }
            }
            if (!$activeProyek) {
                $activeProyek = $proyekList[0];
            }
        }

        // Load details if active project exists
        $jadwalItems = [];
        $harianItems = [];
        $dokumenItems = [];
        $scurve = ['labels' => [], 'plan' => [], 'actual' => [], 'deviasi' => []];
        $deviasiKumulatif = 0;
        $deviasiMingguan = 0;
        $statusIndikator = 'Hijau';
        $warningKpa = false;

        if ($activeProyek) {
            $jadwalModel = new MonevJadwalModel();
            $jadwalItems = $jadwalModel->where('proyek_id', $activeProyek['id'])->findAll();

            $harianModel = new MonevHarianModel();
            // Join with jadwal to get item name
            $harianItems = $harianModel->select('monev_harian.*, monev_jadwal.item_pekerjaan, monev_jadwal.bobot')
                ->join('monev_jadwal', 'monev_jadwal.id = monev_harian.item_id')
                ->where('monev_harian.proyek_id', $activeProyek['id'])
                ->orderBy('monev_harian.tanggal', 'DESC')
                ->findAll();

            $dokumenModel = new MonevDokumenModel();
            $dokumenItems = $dokumenModel->where('proyek_id', $activeProyek['id'])->findAll();

            // Calculate S-curve
            $scurve = $this->calculateMonevScurve($activeProyek, $jadwalItems, $harianItems);

            // 1. Calculate Deviasi Kumulatif Terakhir (untuk tampilan kartu)
            $deviasiKumulatif = 0;
            if (!empty($scurve['deviasi'])) {
                $lastDev = null;
                foreach (array_reverse($scurve['deviasi']) as $d) {
                    if ($d !== null) {
                        $lastDev = $d;
                        break;
                    }
                }
                $deviasiKumulatif = $lastDev ?? 0;
            }

            // 2. Calculate Deviasi Mingguan Terakhir (untuk deteksi dini status indikator)
            $deviasiMingguan = 0;
            if (!empty($scurve['actual']) && !empty($scurve['plan'])) {
                $lastWeekIndex = -1;
                foreach ($scurve['actual'] as $idx => $act) {
                    if ($act !== null) {
                        $lastWeekIndex = $idx;
                    }
                }

                if ($lastWeekIndex >= 0) {
                    // Aktual minggu ini
                    $currentActual = $scurve['actual'][$lastWeekIndex];
                    $prevActual = $lastWeekIndex > 0 ? ($scurve['actual'][$lastWeekIndex - 1] ?? 0) : 0;
                    $weeklyActual = $currentActual - $prevActual;

                    // Rencana minggu ini
                    $currentPlan = $scurve['plan'][$lastWeekIndex];
                    $prevPlan = $lastWeekIndex > 0 ? ($scurve['plan'][$lastWeekIndex - 1] ?? 0) : 0;
                    $weeklyPlan = $currentPlan - $prevPlan;

                    $deviasiMingguan = round($weeklyActual - $weeklyPlan, 2);
                }
            }

            // Indikator Status & Alert Warning didasarkan pada Deviasi MINGGUAN (Deteksi dini)
            if ($deviasiMingguan <= -10.0) {
                $statusIndikator = 'Merah';
                $warningKpa = true;
            } elseif ($deviasiMingguan <= -5.0) {
                $statusIndikator = 'Kuning';
            } else {
                $statusIndikator = 'Hijau';
            }

            // Also check if contract expired but not completed yet
            $tglSelesai = strtotime($activeProyek['tgl_selesai']);
            if ($tglSelesai < time() && $activeProyek['status'] !== 'Selesai') {
                $daysOver = floor((time() - $tglSelesai) / 86400);
                if ($daysOver > 14) {
                    $statusIndikator = 'Merah';
                    $warningKpa = true;
                } elseif ($statusIndikator !== 'Merah') {
                    $statusIndikator = 'Kuning';
                }
            }
        }

        // Get claimed SPSE packages for pre-filling
        $mappingModel = new \App\Models\PpkRupMappingModel();
        $mappings = $mappingModel->where('user_id', $userId)->findAll();

        $rupPackages = [];
        if (!empty($mappings)) {
            $db = \Config\Database::connect();
            foreach ($mappings as $m) {
                $kdPaket = $m['kd_paket'];
                $type = $m['type']; // 'Tender', 'Non-Tender', 'E-Purchasing'

                $pkg = null;
                if ($type === 'Tender') {
                    $pkg = $db->table('lpse_tender')->where('kd_paket', $kdPaket)->get()->getRowArray();
                    if ($pkg) {
                        $rupPackages[] = [
                            'kd_paket' => $pkg['kd_paket'],
                            'kd_rup' => $pkg['kd_rup'],
                            'nama_paket' => $pkg['nama_paket'],
                            'pagu' => $pkg['pagu'],
                            'nilai_kontrak' => $pkg['nilai_kontrak'],
                            'nama_penyedia' => $pkg['nama_penyedia'],
                            'no_kontrak' => $pkg['no_kontrak'],
                            'type' => 'Tender'
                        ];
                    }
                } elseif ($type === 'Non-Tender') {
                    $pkg = $db->table('lpse_nontender')->where('kd_paket', $kdPaket)->get()->getRowArray();
                    if ($pkg) {
                        $rupPackages[] = [
                            'kd_paket' => $pkg['kd_paket'],
                            'kd_rup' => $pkg['kd_rup'],
                            'nama_paket' => $pkg['nama_paket'],
                            'pagu' => $pkg['pagu'],
                            'nilai_kontrak' => $pkg['nilai_kontrak'],
                            'nama_penyedia' => $pkg['nama_penyedia'],
                            'no_kontrak' => $pkg['no_kontrak'],
                            'type' => 'Non-Tender'
                        ];
                    }
                } elseif ($type === 'E-Purchasing') {
                    $pkg = $db->table('epurchasing')->where('kd_paket', $kdPaket)->get()->getRowArray();
                    if ($pkg) {
                        $rupPackages[] = [
                            'kd_paket' => $pkg['kd_paket'],
                            'kd_rup' => $pkg['rup_code'],
                            'nama_paket' => $pkg['nama_paket'],
                            'pagu' => $pkg['pagu'],
                            'nilai_kontrak' => $pkg['total_harga'],
                            'nama_penyedia' => $pkg['nama_penyedia'],
                            'no_kontrak' => $pkg['kd_paket'], // fallback
                            'type' => 'E-Purchasing'
                        ];
                    }
                }
            }
        }

        return view('dashboard/monev_proyek', [
            'title' => 'Monitoring & Evaluasi Proyek',
            'user' => session()->get(),
            'selectedYear' => $currentYear,
            'proyekList' => $proyekList,
            'activeProyek' => $activeProyek,
            'jadwalItems' => $jadwalItems,
            'harianItems' => $harianItems,
            'dokumenItems' => $dokumenItems,
            'scurve' => $scurve,
            'deviasiTerakhir' => $deviasiKumulatif, // Untuk Ringkasan Bobot Realisasi
            'deviasiMingguan' => $deviasiMingguan,  // Untuk Indikator Status & Early Warning
            'statusIndikator' => $statusIndikator,
            'warningKpa' => $warningKpa,
            'rupPackages' => $rupPackages
        ]);
    }

    public function getMonevActiveTemplate()
    {
        $templateModel = new \App\Models\MonevAssessmentTemplateModel();
        $template = $templateModel->getActiveTemplateWithStructure();
        if (!$template) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Tidak ada template aktif'])->setStatusCode(404);
        }
        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $template
        ]);
    }

    public function monevSummary()
    {
        $proyekModel = new MonevProyekModel();
        $jadwalModel = new MonevJadwalModel();
        $harianModel = new MonevHarianModel();

        $userRole = session()->get('role');
        if ($userRole === 'ppk') {
            $proyekList = $proyekModel->where('user_id', session()->get('id'))->findAll();
        } elseif (in_array($userRole, ['opd', 'satker'])) {
            $userKdSatker = session()->get('kd_satker');
            if ($userKdSatker) {
                $proyekList = $proyekModel->select('monev_proyek.*')
                    ->join('users', 'users.id = monev_proyek.user_id', 'left')
                    ->where('users.kd_satker', $userKdSatker)
                    ->findAll();
            } else {
                $proyekList = [];
            }
        } else {
            $proyekList = $proyekModel->findAll();
        }

        // Enrich all projects with tender details
        $proyekList = array_map([$this, '_enrichProyekWithTender'], $proyekList);

        $summaryData = [];
        foreach ($proyekList as $p) {
            $jadwalItems = $jadwalModel->where('proyek_id', $p['id'])->findAll();
            $harianItems = $harianModel->where('proyek_id', $p['id'])->orderBy('tanggal', 'ASC')->findAll();

            $scurve = $this->calculateMonevScurve($p, $jadwalItems, $harianItems);

            $deviasiTerakhir = 0;
            if (!empty($scurve['deviasi'])) {
                foreach (array_reverse($scurve['deviasi']) as $val) {
                    if ($val !== null) {
                        $deviasiTerakhir = $val;
                        break;
                    }
                }
            }

            $realisasiTerakhir = 0;
            if (!empty($scurve['actual'])) {
                foreach (array_reverse($scurve['actual']) as $val) {
                    if ($val !== null) {
                        $realisasiTerakhir = $val;
                        break;
                    }
                }
            }

            $deviasiMingguan = 0;
            if (!empty($scurve['actual']) && !empty($scurve['plan'])) {
                $lastWeekIndex = -1;
                foreach ($scurve['actual'] as $idx => $val) {
                    if ($val !== null) {
                        $lastWeekIndex = $idx;
                    }
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

        return view('dashboard/monev_summary', [
            'title' => 'Summary Progres Proyek',
            'user' => session()->get(),
            'summaryData' => $summaryData
        ]);
    }

    public function exportMonevSummary()
    {
        $proyekModel = new MonevProyekModel();
        $jadwalModel = new MonevJadwalModel();
        $harianModel = new MonevHarianModel();

        $userRole = session()->get('role');
        if ($userRole === 'ppk') {
            $proyekList = $proyekModel->where('user_id', session()->get('id'))->findAll();
        } elseif (in_array($userRole, ['opd', 'satker'])) {
            $userKdSatker = session()->get('kd_satker');
            if ($userKdSatker) {
                $proyekList = $proyekModel->select('monev_proyek.*')
                    ->join('users', 'users.id = monev_proyek.user_id', 'left')
                    ->where('users.kd_satker', $userKdSatker)
                    ->findAll();
            } else {
                $proyekList = [];
            }
        } else {
            $proyekList = $proyekModel->findAll();
        }

        // Enrich all projects with tender details
        $proyekList = array_map([$this, '_enrichProyekWithTender'], $proyekList);

        $summaryData = [];
        foreach ($proyekList as $p) {
            $jadwalItems = $jadwalModel->where('proyek_id', $p['id'])->findAll();
            $harianItems = $harianModel->where('proyek_id', $p['id'])->orderBy('tanggal', 'ASC')->findAll();

            $scurve = $this->calculateMonevScurve($p, $jadwalItems, $harianItems);

            $deviasiTerakhir = 0;
            if (!empty($scurve['deviasi'])) {
                foreach (array_reverse($scurve['deviasi']) as $val) {
                    if ($val !== null) {
                        $deviasiTerakhir = $val;
                        break;
                    }
                }
            }

            $realisasiTerakhir = 0;
            if (!empty($scurve['actual'])) {
                foreach (array_reverse($scurve['actual']) as $val) {
                    if ($val !== null) {
                        $realisasiTerakhir = $val;
                        break;
                    }
                }
            }

            $deviasiMingguan = 0;
            if (!empty($scurve['actual']) && !empty($scurve['plan'])) {
                $lastWeekIndex = -1;
                foreach ($scurve['actual'] as $idx => $val) {
                    if ($val !== null) {
                        $lastWeekIndex = $idx;
                    }
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
                $statusIndikator = 'Merah (Kritis)';
            } elseif ($deviasiMingguan <= -5.0) {
                $statusIndikator = 'Kuning (Perhatian)';
            } else {
                $statusIndikator = 'Hijau (Sesuai Rencana)';
            }

            $tglSelesai = strtotime($p['tgl_selesai']);
            if ($tglSelesai < time() && $p['status'] !== 'Selesai') {
                $daysOver = floor((time() - $tglSelesai) / 86400);
                if ($daysOver > 14) {
                    $statusIndikator = 'Merah (Kritis)';
                } elseif (strpos($statusIndikator, 'Merah') === false) {
                    $statusIndikator = 'Kuning (Perhatian)';
                }
            }

            $summaryData[] = [
                'Nama Proyek' => $p['nama_proyek'],
                'No Kontrak' => $p['no_kontrak'],
                'Nilai Kontrak' => $p['nilai_kontrak'],
                'Penyedia' => $p['nama_penyedia'],
                'Mulai' => $p['tgl_mulai'],
                'Selesai' => $p['tgl_selesai'],
                'Realisasi Kumulatif (%)' => number_format($realisasiTerakhir, 2),
                'Deviasi Kumulatif (%)' => number_format($deviasiTerakhir, 2),
                'Deviasi Mingguan (%)' => number_format($deviasiMingguan, 2),
                'Status' => $statusIndikator
            ];
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="Summary_Progres_Proyek_' . date('Ymd') . '.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM

        if (!empty($summaryData)) {
            fputcsv($output, array_keys($summaryData[0]));
            foreach ($summaryData as $row) {
                fputcsv($output, $row);
            }
        } else {
            fputcsv($output, ['Tidak ada data proyek']);
        }
        fclose($output);
        exit;
    }

    public function calculateMonevScurve($proyek, $jadwalItems, $harianItems)
    {
        $tglMulai = strtotime($proyek['tgl_mulai']);
        $tglSelesai = strtotime($proyek['tgl_selesai']);

        $durationDays = ($tglSelesai - $tglMulai) / 86400 + 1;
        $totalWeeks = max(1, ceil($durationDays / 7));

        $harianByItem = [];
        foreach ($harianItems as $h) {
            // Only count verified logs for the official actual curve to ensure validation flow
            if ($h['is_verified'] == 1) {
                $harianByItem[$h['item_id']][] = $h;
            }
        }

        $labels = [];
        $planWeights = [];
        $actualWeights = [];
        $deviasinya = [];

        $currentDate = date('Y-m-d');

        for ($w = 1; $w <= $totalWeeks; $w++) {
            $weekEndTimestamp = $tglMulai + ($w * 7 - 1) * 86400;
            if ($weekEndTimestamp > $tglSelesai || $w == $totalWeeks) {
                $weekEndTimestamp = $tglSelesai;
            }
            $weekEndDate = date('Y-m-d', $weekEndTimestamp);

            // Label for week
            $labels[] = "M" . $w . " (" . date('d/m', $weekEndTimestamp) . ")";

            // 1. Calculate Plan
            $weekPlanSum = 0;
            foreach ($jadwalItems as $item) {
                $bobot = (float) $item['bobot'];
                $targets = json_decode($item['target_mingguan'] ?? '[]', true);
                if (!is_array($targets))
                    $targets = [];

                $targetIndex = $w - 1;
                if ($targetIndex < count($targets)) {
                    $targetVal = (float) $targets[$targetIndex];
                } else {
                    $targetVal = !empty($targets) ? (float) end($targets) : 100.0;
                }

                $weekPlanSum += $bobot * ($targetVal / 100);
            }
            $planWeights[] = round($weekPlanSum, 2);

            // 2. Calculate Actual
            $weekActualSum = 0;
            foreach ($jadwalItems as $item) {
                $bobot = (float) $item['bobot'];
                $itemId = $item['id'];

                $latestProgress = 0;
                if (isset($harianByItem[$itemId])) {
                    foreach ($harianByItem[$itemId] as $h) {
                        if ($h['tanggal'] <= $weekEndDate) {
                            if ((float) $h['progres_persen'] > $latestProgress) {
                                $latestProgress = (float) $h['progres_persen'];
                            }
                        }
                    }
                }
                $weekActualSum += $bobot * ($latestProgress / 100);
            }

            $weekStartDate = date('Y-m-d', $tglMulai + (($w - 1) * 7) * 86400);

            // Tampilkan data aktual jika minggu ini sudah lewat kalender berjalan, 
            // ATAU minggu ini memiliki data harian yang sudah diinput (untuk kasus input lebih awal dari tgl mulai).
            $maxHarianDate = null;
            foreach ($harianItems as $h) {
                if ($h['is_verified'] == 1) {
                    if ($maxHarianDate === null || $h['tanggal'] > $maxHarianDate) {
                        $maxHarianDate = $h['tanggal'];
                    }
                }
            }

            $weekHasHappened = ($weekStartDate <= $currentDate);
            $weekHasData = ($maxHarianDate !== null && $weekStartDate <= $maxHarianDate);

            if ($weekHasHappened || $weekHasData) {
                $actualWeights[] = round($weekActualSum, 2);
                $deviasinya[] = round($weekActualSum - $weekPlanSum, 2);
            } else {
                $actualWeights[] = null;
                $deviasinya[] = null;
            }
        }

        return [
            'labels' => $labels,
            'plan' => $planWeights,
            'actual' => $actualWeights,
            'deviasi' => $deviasinya
        ];
    }

    public function saveMonevProyek()
    {
        $userRole = trim(strtolower(session()->get('role') ?? ''));
        if (!in_array($userRole, ['ppk', 'admin', 'operator'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Hanya PPK atau Administrator yang dapat mengelola proyek.']);
        }

        $proyekModel = new MonevProyekModel();

        $id = $this->request->getPost('id');
        $data = [
            'kd_rup' => trim($this->request->getPost('kd_rup') ?? ''),
            'nama_proyek' => trim($this->request->getPost('nama_proyek') ?? ''),
            'lokasi' => trim($this->request->getPost('lokasi') ?? ''),
            'no_kontrak' => trim($this->request->getPost('no_kontrak') ?? ''),
            'nilai_kontrak' => (float) $this->request->getPost('nilai_kontrak'),
            'tgl_mulai' => $this->request->getPost('tgl_mulai'),
            'tgl_selesai' => $this->request->getPost('tgl_selesai'),
            'nama_penyedia' => trim($this->request->getPost('nama_penyedia') ?? ''),
            'konsultan_pengawas' => trim($this->request->getPost('konsultan_pengawas') ?? ''),
            'ppk' => trim($this->request->getPost('ppk') ?? ''),
            'sumber_dana' => trim($this->request->getPost('sumber_dana') ?? ''),
            'jenis_pekerjaan' => trim($this->request->getPost('jenis_pekerjaan') ?? ''),
            'metode_kontrak' => trim($this->request->getPost('metode_kontrak') ?? ''),
            'gps_kordinat' => trim($this->request->getPost('gps_kordinat') ?? ''),
            'polygon_lokasi' => trim($this->request->getPost('polygon_lokasi') ?? ''),
            'target_progres_mingguan' => trim($this->request->getPost('target_progres_mingguan') ?? ''),
            'pphp' => trim($this->request->getPost('pphp') ?? ''),
            'direksi_teknis' => trim($this->request->getPost('direksi_teknis') ?? ''),
            'status' => $this->request->getPost('status') ?: 'Draft'
        ];

        if (!$id) {
            $data['user_id'] = session()->get('id');
        }

        // Handle file uploads
        $fileKontrak = $this->request->getFile('file_kontrak');
        if ($fileKontrak && $fileKontrak->isValid() && !$fileKontrak->hasMoved()) {
            $uploadErr = '';
            if (!is_secure_uploaded_file($fileKontrak, ['document', 'image'], 10485760, $uploadErr)) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Berkas Kontrak: ' . $uploadErr]);
            }
            $newName = $fileKontrak->getRandomName();
            $fileKontrak->move(FCPATH . 'uploads/monev', $newName);
            $data['file_kontrak'] = 'uploads/monev/' . $newName;
        }

        $fileMc = $this->request->getFile('dokumen_mc');
        if ($fileMc && $fileMc->isValid() && !$fileMc->hasMoved()) {
            $uploadErr = '';
            if (!is_secure_uploaded_file($fileMc, ['document', 'image'], 10485760, $uploadErr)) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Berkas MC: ' . $uploadErr]);
            }
            $newName = $fileMc->getRandomName();
            $fileMc->move(FCPATH . 'uploads/monev', $newName);
            $data['dokumen_mc'] = 'uploads/monev/' . $newName;
        }

        if (strtotime($data['tgl_selesai']) <= strtotime($data['tgl_mulai'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Tanggal selesai harus setelah tanggal mulai kontrak.']);
        }

        $tglMulaiPekerjaan = $this->request->getPost('tgl_mulai_pekerjaan');
        $tglSelesaiPekerjaan = $this->request->getPost('tgl_selesai_pekerjaan');
        if (!empty($tglMulaiPekerjaan) && !empty($tglSelesaiPekerjaan)) {
            if (strtotime($tglSelesaiPekerjaan) <= strtotime($tglMulaiPekerjaan)) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Tanggal selesai pekerjaan harus setelah tanggal mulai pekerjaan.']);
            }
        }

        if ($id) {
            if ($proyekModel->update($id, $data) === false) {
                return $this->response->setJSON(['status' => 'error', 'message' => implode(' ', $proyekModel->errors())]);
            }
            $msg = 'Berhasil memperbarui data proyek.';
        } else {
            $insertedId = $proyekModel->insert($data);
            if ($insertedId === false) {
                return $this->response->setJSON(['status' => 'error', 'message' => implode(' ', $proyekModel->errors())]);
            }
            $id = $insertedId;
            $msg = 'Berhasil menambahkan data proyek baru.';
        }

        // Save tender specific fields to lpse_tender / lpse_nontender if kd_rup matches
        if (!empty($data['kd_rup'])) {
            $db = \Config\Database::connect();
            $tenderData = [
                'tgl_mulai_pekerjaan' => $tglMulaiPekerjaan ?: null,
                'tgl_selesai_pekerjaan' => $tglSelesaiPekerjaan ?: null,
                'waktu_penyelesaian' => $this->request->getPost('waktu_penyelesaian') ? (int) $this->request->getPost('waktu_penyelesaian') : null,
                'no_spmk_spp' => trim($this->request->getPost('no_spmk_spp') ?? '') ?: null,
            ];
            $tender = $db->table('lpse_tender')->where('kd_rup', $data['kd_rup'])->get()->getRowArray();
            if ($tender) {
                $db->table('lpse_tender')->where('kd_rup', $data['kd_rup'])->update($tenderData);
            } else {
                $nontender = $db->table('lpse_nontender')->where('kd_rup', $data['kd_rup'])->get()->getRowArray();
                if ($nontender) {
                    $db->table('lpse_nontender')->where('kd_rup', $data['kd_rup'])->update($tenderData);
                }
            }
        }

        return $this->response->setJSON(['status' => 'success', 'message' => $msg, 'proyek_id' => $id]);
    }

    public function deleteMonevProyek($id)
    {
        $userRole = trim(strtolower(session()->get('role') ?? ''));
        if (!in_array($userRole, ['ppk', 'admin'])) {
            return redirect()->back()->with('error', 'Aksi ini hanya diperbolehkan untuk role PPK atau Administrator.');
        }

        $proyekModel = new MonevProyekModel();
        $query = $proyekModel->where('id', $id);
        if ($userRole !== 'admin') {
            $query->where('user_id', session()->get('id'));
        }
        $proyek = $query->first();
        if (!$proyek) {
            return redirect()->back()->with('error', 'Proyek tidak ditemukan.');
        }

        $proyekModel->delete($id);
        return redirect()->to('/dashboard/monev-proyek')->with('success', 'Berhasil menghapus proyek.');
    }

    public function saveMonevJadwal()
    {
        $userRole = trim(strtolower(session()->get('role') ?? ''));
        if (!in_array($userRole, ['ppk', 'admin'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Hanya PPK atau Administrator yang dapat mengubah jadwal.']);
        }

        $proyekId = $this->request->getPost('proyek_id');
        $proyekModel = new MonevProyekModel();
        $query = $proyekModel->where('id', $proyekId);
        if ($userRole !== 'admin') {
            $query->where('user_id', session()->get('id'));
        }
        $proyek = $query->first();
        if (!$proyek) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Proyek tidak ditemukan.']);
        }

        $items = $this->request->getPost('item_pekerjaan') ?: [];
        $bobots = $this->request->getPost('bobot') ?: [];
        $targetsWeekly = $this->request->getPost('target_weekly') ?: [];

        $totalBobot = 0;
        foreach ($bobots as $b) {
            $totalBobot += (float) $b;
        }

        if (abs($totalBobot - 100.0) > 0.01) {
            return $this->response->setJSON(['status' => 'error', 'message' => "Total bobot harus sama dengan 100%. Total saat ini: " . number_format($totalBobot, 2) . "%"]);
        }

        $jadwalModel = new MonevJadwalModel();

        $db = \Config\Database::connect();
        $db->table('monev_jadwal')->where('proyek_id', $proyekId)->delete();

        for ($i = 0; $i < count($items); $i++) {
            if (empty(trim($items[$i])))
                continue;

            $targs = isset($targetsWeekly[$i]) ? $targetsWeekly[$i] : [];
            if (is_string($targs)) {
                $targs = array_map('floatval', array_map('trim', explode(',', $targs)));
            } else {
                $targs = array_map('floatval', $targs);
            }

            $jadwalModel->insert([
                'proyek_id' => $proyekId,
                'item_pekerjaan' => trim($items[$i]),
                'bobot' => (float) $bobots[$i],
                'target_mingguan' => json_encode($targs)
            ]);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Jadwal baseline (Kurva S) berhasil disimpan!']);
    }

    public function saveMonevHarian()
    {
        $userRole = trim(strtolower(session()->get('role') ?? ''));
        if (!in_array($userRole, ['ppk', 'admin'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Hanya PPK atau Administrator yang dapat menginput laporan progress proyek.']);
        }

        $proyekId = $this->request->getPost('proyek_id');
        $proyekModel = new MonevProyekModel();
        $proyek = $proyekModel->find($proyekId);
        if (!$proyek) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Proyek tidak ditemukan.']);
        }

        $tanggal = $this->request->getPost('tanggal');
        if ($tanggal < $proyek['tgl_mulai'] || $tanggal > $proyek['tgl_selesai']) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => "Tanggal pekerjaan ({$tanggal}) di luar batas masa kontrak proyek (" . date('d-m-Y', strtotime($proyek['tgl_mulai'])) . " s/d " . date('d-m-Y', strtotime($proyek['tgl_selesai'])) . ")."
            ]);
        }

        $itemId = $this->request->getPost('item_id');
        $progresPersen = (float) $this->request->getPost('progres_persen');
        $keterangan = trim($this->request->getPost('keterangan') ?? '');

        // Calculate the project week of the reported date
        $tglMulai = strtotime($proyek['tgl_mulai']);
        $tanggalTime = strtotime($tanggal);
        $daysDiff = ($tanggalTime - $tglMulai) / 86400;
        $w = max(1, floor($daysDiff / 7) + 1);

        // Fetch baseline plan target for this item at this week
        $jadwalModel = new MonevJadwalModel();
        $jadwalItem = $jadwalModel->find($itemId);
        $plannedProgress = 0.0;
        if ($jadwalItem) {
            $targets = json_decode($jadwalItem['target_mingguan'] ?? '[]', true);
            if (is_array($targets) && !empty($targets)) {
                $targetIndex = $w - 1;
                if ($targetIndex < count($targets)) {
                    $plannedProgress = (float) $targets[$targetIndex];
                } else {
                    $plannedProgress = (float) end($targets);
                }
            }
        }

        // Enforce validation: if progress exceeds plan, require 'keterangan' (justification of spike)
        if ($progresPersen > $plannedProgress && empty($keterangan)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => "Progres yang dilaporkan ({$progresPersen}%) melebihi target rencana minggu ke-{$w} ({$plannedProgress}%) untuk item ini (terdeteksi lonjakan tidak wajar). Harap lengkapi kolom 'Justifikasi / Keterangan Lonjakan' terlebih dahulu."
            ]);
        }

        $harianModel = new MonevHarianModel();
        $prevLogs = $harianModel->where('proyek_id', $proyekId)->where('item_id', $itemId)->orderBy('tanggal', 'DESC')->findAll();
        $prevProgress = 0;
        foreach ($prevLogs as $pl) {
            if ($pl['tanggal'] < $tanggal) {
                $prevProgress = (float) $pl['progres_persen'];
                break;
            }
        }

        $warning = null;
        if ($progresPersen > 100.0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Progres fisik pekerjaan tidak boleh melebihi 100%.']);
        }

        if ($progresPersen < $prevProgress) {
            $warning = "Peringatan: Progres yang dilaporkan ({$progresPersen}%) lebih kecil dari progres sebelumnya ({$prevProgress}%).";
        } elseif (($progresPersen - $prevProgress) > 50.0) {
            $warning = "Peringatan: Lonjakan progres tidak wajar terdeteksi (+ " . ($progresPersen - $prevProgress) . "% dalam satu laporan). Butuh verifikasi ketat.";
        }

        // Upload Photos
        $fotoPaths = ['foto_awal' => '', 'foto_proses' => '', 'foto_hasil' => ''];
        $fields = ['foto_awal', 'foto_proses', 'foto_hasil'];

        $uploadPath = ROOTPATH . 'public/uploads/monev';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        foreach ($fields as $field) {
            $file = $this->request->getFile($field);
            if ($file && $file->isValid() && !$file->hasMoved()) {
                $uploadErr = '';
                if (!is_secure_uploaded_file($file, ['image'], 10485760, $uploadErr)) {
                    return $this->response->setJSON([
                        'status' => 'error',
                        'message' => "Berkas {$field}: " . $uploadErr
                    ]);
                }
                $newName = $file->getRandomName();
                $file->move($uploadPath, $newName);
                $fotoPaths[$field] = '/uploads/monev/' . $newName;
            } else {
                if (!$this->request->getPost('id')) {
                    return $this->response->setJSON([
                        'status' => 'error',
                        'message' => "Ketiga foto lapangan (kondisi awal, proses, hasil) wajib diunggah."
                    ]);
                }
            }
        }

        $id = $this->request->getPost('id');
        $data = [
            'proyek_id' => $proyekId,
            'item_id' => $itemId,
            'tanggal' => $tanggal,
            'volume_selesai' => (float) $this->request->getPost('volume_selesai'),
            'satuan' => trim($this->request->getPost('satuan') ?? ''),
            'lokasi' => trim($this->request->getPost('lokasi') ?? ''),
            'tenaga_kerja' => trim($this->request->getPost('tenaga_kerja') ?? ''),
            'peralatan' => trim($this->request->getPost('peralatan') ?? ''),
            'kendala' => trim($this->request->getPost('kendala') ?? ''),
            'keterangan' => $keterangan,
            'progres_persen' => $progresPersen
        ];

        if ($id) {
            $existing = $harianModel->find($id);
            foreach ($fields as $field) {
                if (empty($fotoPaths[$field]) && isset($existing[$field])) {
                    $fotoPaths[$field] = $existing[$field];
                }
            }
        }

        $data = array_merge($data, $fotoPaths);

        if ($id) {
            $harianModel->update($id, $data);
            $msg = 'Berhasil memperbarui laporan harian.';
        } else {
            $data['is_verified'] = 0;
            $id = $harianModel->insert($data);
            $msg = 'Berhasil menambahkan laporan harian baru.';
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => $msg,
            'warning' => $warning,
            'harian_id' => $id
        ]);
    }

    public function verifyMonevHarian($id)
    {
        $userRole = trim(strtolower(session()->get('role') ?? ''));
        if (!in_array($userRole, ['operator', 'admin'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Hanya akun Operator atau Administrator yang berwenang melakukan validasi laporan.']);
        }

        $db = \Config\Database::connect();

        // Ambil data langsung via raw SQL
        $log = $db->query("SELECT * FROM monev_harian WHERE id = ?", [$id])->getRowArray();
        if (!$log) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Laporan harian tidak ditemukan.']);
        }

        $currentVerified = (int) $log['is_verified'];
        $newStatus = $currentVerified == 1 ? 0 : 1;
        $verifiedAt = $newStatus == 1 ? date('Y-m-d H:i:s') : null;

        // Eksekusi langsung via raw SQL agar tidak terblokir oleh framework apapun
        if ($verifiedAt !== null) {
            $db->query("UPDATE monev_harian SET is_verified = ?, verified_at = ? WHERE id = ?", [$newStatus, $verifiedAt, $id]);
        } else {
            $db->query("UPDATE monev_harian SET is_verified = ?, verified_at = NULL WHERE id = ?", [$newStatus, $id]);
        }

        // Verifikasi bahwa update benar-benar berhasil
        $check = $db->query("SELECT is_verified, verified_at FROM monev_harian WHERE id = ?", [$id])->getRowArray();

        $msg = $newStatus == 1 ? 'Laporan harian berhasil diverifikasi dan disetujui!' : 'Verifikasi laporan harian dibatalkan.';
        return $this->response->setJSON([
            'status' => 'success',
            'message' => $msg,
            'is_verified' => $newStatus,
            'debug_before' => $currentVerified,
            'debug_after' => $check['is_verified'] ?? 'N/A',
            'debug_id' => $id
        ]);
    }

    public function saveMonevDokumen()
    {
        $userRole = trim(strtolower(session()->get('role') ?? ''));
        if (!in_array($userRole, ['ppk', 'admin'])) {
            return redirect()->back()->with('error', 'Hanya PPK atau Administrator yang dapat mengupload dokumen proyek.');
        }

        $proyekId = $this->request->getPost('proyek_id');
        $proyekModel = new MonevProyekModel();
        $proyek = $proyekModel->find($proyekId);
        if (!$proyek) {
            return redirect()->back()->with('error', 'Proyek tidak ditemukan.');
        }

        $rules = [
            'file_dokumen' => 'max_size[file_dokumen,10240]|ext_in[file_dokumen,pdf,doc,docx,xls,xlsx,jpg,jpeg,png,webp]|mime_in[file_dokumen,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,image/jpeg,image/png,image/webp]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('error', 'File tidak valid. Pastikan ukuran max 10MB dan format yang didukung (PDF, Word, Excel, JPG, PNG).');
        }

        $file = $this->request->getFile('file_dokumen');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $uploadErr = '';
            if (!is_secure_uploaded_file($file, ['document', 'image'], 10485760, $uploadErr)) {
                return redirect()->back()->with('error', 'Dokumen tidak valid: ' . $uploadErr);
            }

            $uploadPath = ROOTPATH . 'public/uploads/monev/docs';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            $newName = $file->getRandomName();
            $file->move($uploadPath, $newName);

            $dokumenModel = new MonevDokumenModel();
            $dokumenModel->insert([
                'proyek_id' => $proyekId,
                'nama_dokumen' => trim($this->request->getPost('nama_dokumen') ?? $file->getClientName()),
                'kategori' => $this->request->getPost('kategori') ?: 'Lainnya',
                'file_path' => '/uploads/monev/docs/' . $newName
            ]);

            return redirect()->to('/dashboard/monev-proyek?proyek_id=' . $proyekId)->with('success', 'Berhasil mengunggah dokumen pendukung.');
        }

        return redirect()->back()->with('error', 'Gagal mengunggah file. Terjadi kesalahan pada server.');
    }

    public function getMonevChartData($proyekId)
    {
        $proyekModel = new MonevProyekModel();
        $proyek = $proyekModel->find($proyekId);
        if (!$proyek) {
            return $this->response->setJSON([]);
        }

        $jadwalModel = new MonevJadwalModel();
        $jadwalItems = $jadwalModel->where('proyek_id', $proyekId)->findAll();

        $harianModel = new MonevHarianModel();
        $harianItems = $harianModel->select('monev_harian.*, monev_jadwal.item_pekerjaan, monev_jadwal.bobot')
            ->join('monev_jadwal', 'monev_jadwal.id = monev_harian.item_id')
            ->where('monev_harian.proyek_id', $proyekId)
            ->orderBy('monev_harian.tanggal', 'DESC')
            ->findAll();

        $scurve = $this->calculateMonevScurve($proyek, $jadwalItems, $harianItems);

        return $this->response->setJSON($scurve);
    }

    private function _getPpkRupCodes()
    {
        $mappingModel = new \App\Models\PpkRupMappingModel();
        $mappings = $mappingModel->where('user_id', session()->get('id'))->findAll();
        if (empty($mappings)) {
            return ['0'];
        }

        $kdPakets = array_column($mappings, 'kd_paket');

        $db = \Config\Database::connect();
        $rupCodes = [];

        // Tender
        $tenderRups = $db->table('lpse_tender')
            ->select('kd_rup')
            ->whereIn('kd_paket', $kdPakets)
            ->get()
            ->getResultArray();
        foreach ($tenderRups as $r) {
            if (!empty($r['kd_rup'])) {
                $rupCodes[] = $r['kd_rup'];
            }
        }

        // Non-Tender
        $nonTenderRups = $db->table('lpse_nontender')
            ->select('kd_rup')
            ->whereIn('kd_paket', $kdPakets)
            ->get()
            ->getResultArray();
        foreach ($nonTenderRups as $r) {
            if (!empty($r['kd_rup'])) {
                $rupCodes[] = $r['kd_rup'];
            }
        }

        // Epurchasing
        $epurchasingRups = $db->table('epurchasing')
            ->select('rup_code')
            ->whereIn('kd_paket', $kdPakets)
            ->get()
            ->getResultArray();
        foreach ($epurchasingRups as $r) {
            if (!empty($r['rup_code'])) {
                $rupCodes[] = $r['rup_code'];
            }
        }

        $rupCodes = array_unique(array_filter($rupCodes));

        return !empty($rupCodes) ? $rupCodes : ['0'];
    }

    public function getRupDetails($kdPaket)
    {
        $kdPaket = trim($kdPaket);
        if (empty($kdPaket)) {
            return $this->fail('Kode paket tidak boleh kosong.');
        }

        $db = \Config\Database::connect();

        // Cari di Tender
        $paket = $db->table('lpse_tender')->where('kd_paket', $kdPaket)->get()->getRowArray();
        $type = 'Tender';

        if (!$paket) {
            // Cari di Non-Tender
            $paket = $db->table('lpse_nontender')->where('kd_paket', $kdPaket)->get()->getRowArray();
            $type = 'Non-Tender';
        }

        if (!$paket) {
            // Cari di E-Purchasing
            $paket = $db->table('epurchasing')->where('kd_paket', $kdPaket)->get()->getRowArray();
            $type = 'E-Purchasing';
        }

        if (!$paket) {
            return $this->failNotFound('Paket SPSE tidak ditemukan.');
        }

        $kdRup = $type === 'E-Purchasing' ? ($paket['rup_code'] ?? '-') : ($paket['kd_rup'] ?? '-');
        $nilaiKontrak = $type === 'E-Purchasing' ? ($paket['total_harga'] ?? 0) : ($paket['nilai_kontrak'] ?? 0);
        $noKontrak = $type === 'E-Purchasing' ? $paket['kd_paket'] : ($paket['no_kontrak'] ?? '');

        return $this->response->setJSON([
            'kd_paket' => $paket['kd_paket'],
            'kd_rup' => $kdRup,
            'nama_paket' => $paket['nama_paket'],
            'nama_satker' => $paket['nama_satker'] ?? 'N/A',
            'pagu' => $paket['pagu'],
            'pagu_format' => 'Rp ' . number_format($paket['pagu'], 0, ',', '.'),
            'nilai_kontrak' => $nilaiKontrak,
            'nilai_kontrak_format' => 'Rp ' . number_format($nilaiKontrak, 0, ',', '.'),
            'nama_penyedia' => $paket['nama_penyedia'] ?? '',
            'no_kontrak' => $noKontrak,
            'type' => $type,
            'tahun' => $paket['tahun'] ?? date('Y'),
            'no_spmk_spp' => $paket['no_spmk_spp'] ?? '',
            'waktu_penyelesaian' => $paket['waktu_penyelesaian'] ?? '',
            'tgl_mulai_pekerjaan' => $paket['tgl_mulai_pekerjaan'] ?? '',
            'tgl_selesai_pekerjaan' => $paket['tgl_selesai_pekerjaan'] ?? ''
        ]);
    }

    public function addMonevPaket()
    {
        $userRole = trim(strtolower(session()->get('role') ?? ''));
        if (!in_array($userRole, ['ppk', 'admin', 'operator'])) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Aksi ini hanya diperbolehkan untuk role PPK, Admin, atau Operator.']);
            }
            return redirect()->back()->with('error', 'Aksi ini hanya diperbolehkan untuk role PPK, Admin, atau Operator.');
        }

        $kdPaket = trim($this->request->getPost('kd_paket') ?? '');
        if (empty($kdPaket)) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Kode Paket tidak boleh kosong.']);
            }
            return redirect()->back()->with('error', 'Kode Paket tidak boleh kosong.');
        }

        $db = \Config\Database::connect();

        $type = null;
        $paket = null;

        // Cari di Tender
        $check = $db->table('lpse_tender')->where('kd_paket', $kdPaket)->get()->getRowArray();
        if ($check) {
            $type = 'Tender';
            $paket = $check;
        } else {
            // Cari di Non-Tender
            $check = $db->table('lpse_nontender')->where('kd_paket', $kdPaket)->get()->getRowArray();
            if ($check) {
                $type = 'Non-Tender';
                $paket = $check;
            } else {
                // Cari di E-Purchasing
                $check = $db->table('epurchasing')->where('kd_paket', $kdPaket)->get()->getRowArray();
                if ($check) {
                    $type = 'E-Purchasing';
                    $paket = $check;
                }
            }
        }

        if (!$paket) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => "Kode Paket {$kdPaket} tidak ditemukan di database transaksi SPSE."]);
            }
            return redirect()->back()->with('error', "Kode Paket {$kdPaket} tidak ditemukan di database transaksi SPSE.");
        }

        $mappingModel = new \App\Models\PpkRupMappingModel();

        $alreadyClaimed = $mappingModel->where([
            'user_id' => session()->get('id'),
            'kd_paket' => $kdPaket
        ])->first();

        if ($alreadyClaimed) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => "Kode Paket {$kdPaket} sudah ada di daftar monitoring Anda."]);
            }
            return redirect()->back()->with('error', "Kode Paket {$kdPaket} sudah ada di daftar monitoring Anda.");
        }

        $mappingModel->save([
            'user_id' => session()->get('id'),
            'kd_paket' => $kdPaket,
            'type' => $type
        ]);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status' => 'success',
                'message' => "Berhasil menambahkan paket SPSE {$kdPaket} [{$type}] ke monitoring Anda.",
                'data' => [
                    'kd_paket' => $paket['kd_paket'],
                    'kd_rup' => $type === 'E-Purchasing' ? ($paket['rup_code'] ?? '-') : ($paket['kd_rup'] ?? '-'),
                    'nama_paket' => $paket['nama_paket'],
                    'pagu' => $paket['pagu'],
                    'type' => $type
                ]
            ]);
        }

        return redirect()->to('/dashboard/monev-proyek')->with('success', "Berhasil menambahkan paket SPSE {$kdPaket} [{$type}] ke monitoring Anda.");
    }

    public function removeMonevPaket($kdPaket)
    {
        $userRole = trim(strtolower(session()->get('role') ?? ''));
        if (!in_array($userRole, ['ppk', 'admin', 'operator'])) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Aksi ini hanya diperbolehkan untuk role PPK, Admin, atau Operator.']);
            }
            return redirect()->back()->with('error', 'Aksi ini hanya diperbolehkan untuk role PPK, Admin, atau Operator.');
        }

        $mappingModel = new \App\Models\PpkRupMappingModel();
        $mappingModel->where([
            'user_id' => session()->get('id'),
            'kd_paket' => $kdPaket
        ])->delete();

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status' => 'success',
                'message' => "Berhasil menghapus paket SPSE {$kdPaket} dari monitoring Anda."
            ]);
        }

        return redirect()->to('/dashboard/monev-proyek')->with('success', "Berhasil menghapus paket SPSE {$kdPaket} dari monitoring Anda.");
    }

    public function analyzeAnomaly()
    {
        if (!$this->request->isAJAX()) {
            return $this->failForbidden('Hanya menerima request AJAX.');
        }

        $satker = $this->request->getPost('satker');
        $program = $this->request->getPost('program');
        $paguMaster = $this->request->getPost('pagu_master');
        $paguRup = $this->request->getPost('pagu_rup');
        $packagesInfo = $this->request->getPost('packages_info');

        // Cache logic for token efficiency (v4: focused on cause & position without recommendations)
        $cacheKey = md5("anomaly_analysis_v4_" . $satker . $program . $paguMaster . $paguRup);
        if ($cached = cache($cacheKey)) {
            return $this->response->setJSON(['status' => 'success', 'analysis' => $cached]);
        }

        $settingsModel = new \App\Models\SettingsModel();
        $apiKey = $settingsModel->getSetting('chatbot_api_key', env('OPENAI_API_KEY'));
        $model = $settingsModel->getSetting('chatbot_model', 'gpt-4o-mini');
        $apiUrl = $settingsModel->getSetting('chatbot_api_url', 'https://api.openai.com/v1/chat/completions');

        if (empty($apiKey)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'API Key OpenAI tidak dikonfigurasi.']);
        }

        // Auto-format API URL to ensure it points to the chat endpoint
        $apiUrl = rtrim($apiUrl, '/');
        if (!preg_match('/\/chat\/completions$/i', $apiUrl)) {
            $apiUrl .= '/chat/completions';
        }

        $selisih = (float) $paguRup - (float) $paguMaster;
        $statusSelisih = $selisih > 0 ? "Kelebihan Pagu RUP (Over-budget)" : "Kekurangan Pagu RUP (Under-budget)";
        $absSelisih = abs($selisih);

        $prompt = "Anda adalah Auditor dan Analis Data Pengadaan Barang/Jasa Pemerintah.
Analisis perbedaan nilai pagu pada program kerja berikut:
- Satuan Kerja / OPD: {$satker}
- Program Kerja: {$program}
- Pagu Master (DPA/Kegiatan): Rp " . number_format((float) $paguMaster, 0, ',', '.') . "
- Pagu RUP (Terumumkan di SIRUP): Rp " . number_format((float) $paguRup, 0, ',', '.') . "
- Status Selisih: {$statusSelisih} sebesar Rp " . number_format($absSelisih, 0, ',', '.') . "
- Data Paket RUP Terkait: {$packagesInfo}

Instruksi Analisis:
Berikan analisis yang padat, lugas, dan to the point (maksimal 2-3 poin singkat) HANYA mencakup:
1. Kenapa terjadi perbedaan nilai: Jelaskan secara ringkas penyebab perbedaan pagu (apakah akumulasi nilai paket melebihi pagu master DPA atau sebaliknya).
2. Dimana posisinya: Sebutkan secara spesifik paket atau pos belanja/MAK yang menyebabkan kelebihan/perbedaan tersebut berdasarkan data paket di atas.

PENTING: JANGAN memberikan saran atau rekomendasi tindak lanjut apapun. Cukup jelaskan kenapa terjadi perbedaan nilai dan dimana posisinya.";

        $data = [
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => 500,
            'temperature' => 0.3
        ];

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
            'api-key: ' . $apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Koneksi ke AI gagal: ' . $error]);
        }

        $responseArray = json_decode($response, true);
        $analysis = $responseArray['choices'][0]['message']['content'] ?? '';

        if ($httpCode !== 200 || empty($analysis)) {
            $errMsg = $responseArray['error']['message'] ?? ($error ?: "AI server error (HTTP {$httpCode})");
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menganalisa data: ' . $errMsg]);
        }

        // Simpan di cache selama 7 hari jika berhasil
        cache()->save($cacheKey, $analysis, 604800);

        return $this->response->setJSON(['status' => 'success', 'analysis' => $analysis]);
    }

    public function analyzeEpurchasing()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setBody('Forbidden');
        }

        $summaryData = $this->request->getPost('summary');

        $cacheKey = md5("epurchasing_ai_analysis_v3_" . json_encode($summaryData));
        if ($cached = cache($cacheKey)) {
            return $this->response->setJSON(['status' => 'success', 'analysis' => $cached]);
        }

        $settingsModel = new \App\Models\SettingsModel();
        $apiKey = $settingsModel->getSetting('chatbot_api_key', env('OPENAI_API_KEY'));
        $model = $settingsModel->getSetting('chatbot_model', 'gpt-4o-mini');
        $apiUrl = $settingsModel->getSetting('chatbot_api_url', 'https://api.openai.com/v1/chat/completions');

        if (empty($apiKey)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'API Key OpenAI tidak dikonfigurasi.']);
        }

        // Auto-format API URL to ensure it points to the chat endpoint
        $apiUrl = rtrim($apiUrl, '/');
        if (!preg_match('/\/chat\/completions$/i', $apiUrl)) {
            $apiUrl .= '/chat/completions';
        }

        $prompt = "Anda adalah AI Analis E-Purchasing Katalog Pemerintah yang bertugas menganalisis data transaksi belanja katalog elektronik secara cerdas, profesional, dan kontekstual berdasarkan waktu realisasi aktual.

Fokus utama Anda adalah:
- analisis transaksi e-purchasing,
- analisis vendor dan produk,
- analisis pola belanja,
- analisis status transaksi,
- analisis tren realisasi,
- deteksi anomali,
- dan pemberian insight strategis.

Anda harus bertindak sebagai analis pengadaan profesional, bukan sekadar pembaca tabel.

---

# ATURAN WAKTU REALISASI (SANGAT PENTING)

Saat menganalisis tren bulanan atau realisasi tahunan:
- Gunakan tanggal saat ini sebagai acuan analisis.
- Abaikan bulan yang memang belum memasuki periode pelaksanaan.
- Jangan menganggap bulan yang belum terjadi sebagai anomali atau nol realisasi.
- Jangan menyimpulkan keterlambatan jika periode pelaksanaan belum masuk.
- Fokus hanya pada bulan yang sudah berjalan hingga bulan saat ini.

---

# DATA YANG DITERIMA
" . json_encode($summaryData, JSON_PRETTY_PRINT) . "

---

# GAYA JAWABAN
- Gunakan bahasa Indonesia formal namun mudah dipahami.
- Fokus pada insight penting.
- Hindari membaca ulang data mentah.
- Ringkas tetapi analitis.
- Jangan mengarang data.
- JANGAN membandingkan nilai anggaran antar vendor atau membahas persaingan harga antar vendor secara berlebihan. Fokus vendor pada produk apa yang mereka sediakan.

---

# FORMAT OUTPUT Wajib
## Ringkasan Utama
(Tuliskan insight utama dalam 1-3 paragraf)
## Temuan Penting
(Gunakan bullet point)
## Analisis Detail
(Bahas belanja, vendor & produk, status transaksi, tren waktu, risiko)
## Risiko atau Perhatian
(Jelaskan hal yang perlu perhatian khusus)
## Rekomendasi
(Berikan rekomendasi konkret dan praktis)";

        $data = [
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => 2000,
            'temperature' => 0.5
        ];

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
            'api-key: ' . $apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Koneksi ke AI gagal: ' . $error]);
        }

        $responseArray = json_decode($response, true);
        $analysis = $responseArray['choices'][0]['message']['content'] ?? '';

        if ($httpCode !== 200 || empty($analysis)) {
            $errMsg = $responseArray['error']['message'] ?? ($error ?: "AI server error (HTTP {$httpCode})");
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menganalisa data: ' . $errMsg]);
        }

        cache()->save($cacheKey, $analysis, 86400);

        return $this->response->setJSON(['status' => 'success', 'analysis' => $analysis]);
    }

    public function generateWeeklyAiAnalysis()
    {
        $userRole = trim(strtolower(session()->get('role') ?? ''));
        if ($userRole !== 'ppk') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Hanya pengguna dengan role PPK yang dapat melakukan analisis AI.']);
        }

        $proyekId = $this->request->getPost('proyek_id');
        $week = (int) $this->request->getPost('week');
        $deviasi = (float) $this->request->getPost('deviasi');

        if (!$proyekId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'ID Proyek tidak valid.']);
        }

        // Self-healing database check
        $db = \Config\Database::connect();
        if (!$db->fieldExists('weekly_ai_analysis', 'monev_proyek')) {
            $forge = \Config\Database::forge();
            $forge->addColumn('monev_proyek', [
                'weekly_ai_analysis' => [
                    'type'  => 'TEXT',
                    'null'  => true,
                    'after' => 'dokumen_mc'
                ]
            ]);
        }

        $proyekModel = new \App\Models\MonevProyekModel();
        $harianModel = new \App\Models\MonevHarianModel();

        $proyek = $proyekModel->find($proyekId);
        if (!$proyek) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Proyek tidak ditemukan.']);
        }

        // Ambil data logbook harian untuk minggu tersebut
        $tglMulai = strtotime($proyek['tgl_mulai']);
        $weekStart = date('Y-m-d', $tglMulai + ($week - 1) * 7 * 86400);
        $weekEnd = date('Y-m-d', $tglMulai + $week * 7 * 86400 - 86400);

        $harianLogs = $harianModel->where('proyek_id', $proyekId)
            ->where('tanggal >=', $weekStart)
            ->where('tanggal <=', $weekEnd)
            ->findAll();

        $kendalaAsli = [];
        $itemsKerja = [];
        foreach ($harianLogs as $log) {
            if (!empty($log['kendala'])) {
                $kendalaAsli[] = $log['tanggal'] . ': ' . $log['kendala'];
            }
            $itemsKerja[] = $log['lokasi'] ?? '';
        }
        $kendalaText = !empty($kendalaAsli) ? implode("; ", array_unique($kendalaAsli)) : 'Tidak ada laporan kendala spesifik di logbook harian.';
        $lokasiText = !empty($itemsKerja) ? implode(", ", array_filter(array_unique($itemsKerja))) : '-';

        // Lakukan semantic RAG search di knowledge base jika ada
        $sopRujukan = "Gunakan pedoman standar percepatan konstruksi (SOP SCM - Show Cause Meeting).";
        try {
            $chatbot = new \App\Controllers\Chatbot();
            $chatbot->initController($this->request, $this->response, $this->logger);
            $searchQuery = ($proyek['jenis_pekerjaan'] ?? '') . ' ' . $kendalaText . ' percepatan konstruksi SCM denda';
            $kbJson = $chatbot->searchKnowledgeBase(json_encode(['query' => $searchQuery]));
            $kbParsed = json_decode($kbJson, true);
            if (isset($kbParsed['top_relevant_rules']) && is_array($kbParsed['top_relevant_rules'])) {
                $topRule = $kbParsed['top_relevant_rules'][0];
                if (($topRule['similarity'] ?? 0) > 0.3) {
                    $sopRujukan = "Merujuk pada pedoman: \"" . ($topRule['document'] ?? 'Pedoman') . "\", klausul: \"" . ($topRule['text'] ?? '') . "\"";
                }
            }
        } catch (\Exception $e) {
            // Fallback
        }

        // Panggil AI menggunakan settings yang sudah disetup user
        $settingsModel = new \App\Models\SettingsModel();
        $apiKey = $settingsModel->getSetting('chatbot_api_key', env('OPENAI_API_KEY'));
        $model = $settingsModel->getSetting('chatbot_model', 'gpt-5.4-mini');
        $apiUrl = $settingsModel->getSetting('chatbot_api_url', 'https://api.ai.cc/v1/chat/completions');

        if (empty($apiKey)) {
            $hambatan = 'API Key kecerdasan belum dikonfigurasi. Kendala: ' . $kendalaText;
            $tindakLanjut = 'Silakan konfigurasikan API Key AI di menu pengaturan.';

            // Persist the fallback results
            $weeklyAnalysis = [];
            if (!empty($proyek['weekly_ai_analysis'])) {
                $weeklyAnalysis = json_decode($proyek['weekly_ai_analysis'], true) ?: [];
            }
            $weeklyAnalysis[$week] = [
                'hambatan' => $hambatan,
                'tindak_lanjut' => $tindakLanjut,
                'generated_by' => session()->get('username') ?? session()->get('nama') ?? 'PPK',
                'generated_at' => date('Y-m-d H:i:s')
            ];
            $proyekModel->update($proyekId, ['weekly_ai_analysis' => json_encode($weeklyAnalysis)]);

            return $this->response->setJSON([
                'status' => 'success',
                'hambatan' => $hambatan,
                'tindak_lanjut' => $tindakLanjut
            ]);
        }

        $systemPrompt = "Kamu adalah AJIP, AI Auditor & Konsultan Manajemen Konstruksi cerdas.
        Tugasmu adalah menganalisis kendala mingguan proyek dan membuat Rencana Tindak Lanjut percepatan konstruksi (SCM) secara profesional dan sangat spesifik.
        Jawablah dengan format JSON yang mengandung dua key:
        1. 'hambatan': Kalimat singkat analisis teknis hambatan berdasarkan kendala asli lapangan.
        2. 'tindak_lanjut': Langkah taktis, terukur, dan operasional (Tindak Lanjut) untuk mengejar ketertinggalan deviasi tersebut.
        Jangan berikan teks penjelasan lain di luar JSON.";

        $userMessage = "Proyek: {$proyek['nama_proyek']} ({$proyek['jenis_pekerjaan']})
        Minggu Pekerjaan: Minggu ke-{$week}
        Deviasi Kumulatif: {$deviasi}% (Terlambat)
        Kendala Lapangan: {$kendalaText}
        Lokasi Pekerjaan: {$lokasiText}
        Referensi SOP/Regulasi: {$sopRujukan}";

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage]
            ],
            'temperature' => 0.5,
            'response_format' => ['type' => 'json_object']
        ];

        // cURL request ke AI
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'api-key: ' . $apiKey
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal terhubung ke AI: ' . $error]);
        }

        $resArray = json_decode($response, true);
        $content = $resArray['choices'][0]['message']['content'] ?? '';

        $aiParsed = json_decode($content, true);
        if (!$aiParsed || !isset($aiParsed['hambatan']) || !isset($aiParsed['tindak_lanjut'])) {
            $hambatan = 'Terganggu oleh kendala teknis: ' . $kendalaText;
            $tindakLanjut = 'Lakukan evaluasi ulang Show Cause Meeting (SCM) dan percepat mobilisasi tenaga kerja tambahan.';
        } else {
            $hambatan = $aiParsed['hambatan'];
            $tindakLanjut = $aiParsed['tindak_lanjut'];
        }

        // Save / merge to weekly_ai_analysis column
        $weeklyAnalysis = [];
        if (!empty($proyek['weekly_ai_analysis'])) {
            $weeklyAnalysis = json_decode($proyek['weekly_ai_analysis'], true) ?: [];
        }

        $weeklyAnalysis[$week] = [
            'hambatan' => $hambatan,
            'tindak_lanjut' => $tindakLanjut,
            'generated_by' => session()->get('username') ?? session()->get('nama') ?? 'PPK',
            'generated_at' => date('Y-m-d H:i:s')
        ];

        $proyekModel->update($proyekId, [
            'weekly_ai_analysis' => json_encode($weeklyAnalysis)
        ]);

        return $this->response->setJSON([
            'status' => 'success',
            'hambatan' => $hambatan,
            'tindak_lanjut' => $tindakLanjut
        ]);
    }

    // ==========================================
    // DATABASE: PENYEDIA KATALOG
    // ==========================================
    public function penyediaKatalog()
    {
        if (!has_permission('penyedia-katalog')) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke halaman Penyedia Katalog.');
        }

        $data = [
            'title' => 'Penyedia Katalog E-Purchasing',
            'menu' => 'penyedia-katalog'
        ];

        return view('dashboard/penyedia_katalog', $data);
    }

    public function getPenyediaKatalog()
    {
        if (!has_permission('penyedia-katalog')) {
            return $this->response->setJSON(['data' => []]);
        }

        $db = \Config\Database::connect();

        // Get filters
        $tahun = $this->request->getGet('tahun') ?: date('Y');

        // Use Query Builder to aggregate data by nama_penyedia
        $builder = $db->table('epurchasing')
            ->select('nama_penyedia, GROUP_CONCAT(DISTINCT COALESCE(NULLIF(nama_produk, ""), nama_paket) SEPARATOR "||") as produk, SUM(total_harga) as total_transaksi, COUNT(id) as total_paket')
            ->where('tahun', $tahun)
            ->where('nama_penyedia IS NOT NULL')
            ->where('nama_penyedia !=', '')
            ->groupBy('nama_penyedia')
            ->orderBy('total_transaksi', 'DESC');

        $results = $builder->get()->getResultArray();

        // Group data in PHP to handle case sensitivity and spaces differences
        $groupedData = [];
        foreach ($results as $row) {
            $namaPenyedia = strtoupper(trim($row['nama_penyedia']));
            if (!isset($groupedData[$namaPenyedia])) {
                $groupedData[$namaPenyedia] = [
                    'nama_penyedia' => $namaPenyedia,
                    'produk' => $row['produk'],
                    'total_transaksi' => $row['total_transaksi'],
                    'total_paket' => $row['total_paket']
                ];
            } else {
                $groupedData[$namaPenyedia]['produk'] .= '||' . $row['produk'];
                $groupedData[$namaPenyedia]['total_transaksi'] += $row['total_transaksi'];
                $groupedData[$namaPenyedia]['total_paket'] += $row['total_paket'];
            }
        }

        // Sort by total_transaksi descending
        usort($groupedData, function ($a, $b) {
            return $b['total_transaksi'] <=> $a['total_transaksi'];
        });

        // Panggil AI untuk kategorisasi
        $aiCategories = $this->_categorizeWithAI($groupedData);

        $data = [];
        $no = 1;
        foreach ($groupedData as $row) {
            $namaPenyedia = $row['nama_penyedia'];
            $produkRaw = explode('||', $row['produk']);
            $produkRaw = array_unique(array_filter(array_map('trim', $produkRaw)));

            // Cek apakah AI memberikan kategori yang valid
            $kategoriUtama = $aiCategories[$namaPenyedia] ?? null;
            $validKategori = ['Makanan & Minuman', 'Elektronik & IT', 'Alat Tulis Kantor (ATK)', 'Internet & Jaringan', 'Kesehatan & Medis', 'Konstruksi & Material', 'Kendaraan & Transportasi', 'Mebel & Furniture', 'Jasa', 'Lainnya'];

            if (!$kategoriUtama || !in_array($kategoriUtama, $validKategori)) {
                // Fallback ke Logika Auto-Categorization Regex
                $kategoriCounter = [
                    'Makanan & Minuman' => 0,
                    'Elektronik & IT' => 0,
                    'Alat Tulis Kantor (ATK)' => 0,
                    'Internet & Jaringan' => 0,
                    'Kesehatan & Medis' => 0,
                    'Konstruksi & Material' => 0,
                    'Kendaraan & Transportasi' => 0,
                    'Mebel & Furniture' => 0,
                    'Jasa' => 0,
                    'Lainnya' => 0
                ];

                foreach ($produkRaw as $p) {
                    $pLower = strtolower($p);
                    if (preg_match('/(makan|minum|katering|nasi|snack|kopi|kue|catering)/', $pLower)) {
                        $kategoriCounter['Makanan & Minuman']++;
                    } elseif (preg_match('/(kesehatan|obat|medis|alkes|masker|vaksin|oksigen|klinik|rsud|puskesmas|pasien|dokter)/', $pLower)) {
                        $kategoriCounter['Kesehatan & Medis']++;
                    } elseif (preg_match('/(internet|bandwidth|wifi|fiber|kabel data|jaringan|server|hosting|domain|cloud)/', $pLower)) {
                        $kategoriCounter['Internet & Jaringan']++;
                    } elseif (preg_match('/(laptop|komputer|pc|printer|scanner|mouse|keyboard|flashdisk|proyektor|cctv|tv|televisi|kamera|elektronik|vidiotron|videotron)/', $pLower)) {
                        $kategoriCounter['Elektronik & IT']++;
                    } elseif (preg_match('/(kertas|tinta|pulpen|spidol|buku|map|toner|atk|fotokopi|cetak|penggandaan)/', $pLower)) {
                        $kategoriCounter['Alat Tulis Kantor (ATK)']++;
                    } elseif (preg_match('/(semen|besi|pasir|batu|cat|paku|pipa|beton|aspal|konstruksi|bangunan|rehabilitasi|pemeliharaan)/', $pLower)) {
                        $kategoriCounter['Konstruksi & Material']++;
                    } elseif (preg_match('/(motor|mobil|sepeda|ban|oli|bensin|bbm|kendaraan|suku cadang)/', $pLower)) {
                        $kategoriCounter['Kendaraan & Transportasi']++;
                    } elseif (preg_match('/(meja|kursi|lemari|rak|sofa|mebel|furniture|pendingin|ac |air conditioner)/', $pLower)) {
                        $kategoriCounter['Mebel & Furniture']++;
                    } elseif (preg_match('/(jasa|sewa|pemeliharaan|perbaikan|langganan|servis|tenaga|outsourcing|keamanan|kebersihan|cleaning)/', $pLower)) {
                        $kategoriCounter['Jasa']++;
                    } else {
                        $kategoriCounter['Lainnya']++;
                    }
                } // Close foreach ($produkRaw as $p)

                arsort($kategoriCounter);
                $kategoriUtama = array_key_first($kategoriCounter);
                if ($kategoriCounter[$kategoriUtama] == 0) {
                    $kategoriUtama = 'Lainnya'; // Jika tidak ada yang match sama sekali
                }
            } // Close if (!$kategoriUtama || !in_array...)

            // Format produk list untuk UI
            $produkListHtml = '<ul class="pl-4 list-disc text-xs text-start" style="max-height: 100px; overflow-y: auto;">';
            foreach ($produkRaw as $p) {
                $produkListHtml .= '<li>' . esc($p) . '</li>';
            }
            $produkListHtml .= '</ul>';

            $data[] = [
                'no' => $no++,
                'nama_penyedia' => '<strong>' . esc($namaPenyedia) . '</strong>',
                'kategori' => '<span class="badge bg-label-primary">' . $kategoriUtama . '</span>',
                'kategori_raw' => $kategoriUtama,
                'daftar_produk' => $produkListHtml,
                'total_paket' => $row['total_paket'],
                'total_transaksi' => 'Rp ' . number_format($row['total_transaksi'], 0, ',', '.')
            ];
        }

        return $this->response->setJSON(['data' => $data]);
    }

    public function dashboardPenyedia()
    {
        if (!has_permission('dashboard_penyedia') && !has_permission('detail_penyedia') && !has_permission('penyedia-katalog')) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke halaman Dashboard Penyedia.');
        }

        $db = \Config\Database::connect();
        
        // Ambil daftar tahun yang tersedia dari tabel transaksi LPSE
        $years = [];
        try {
            $yRows = $db->query("
                SELECT DISTINCT tahun FROM (
                    SELECT tahun FROM lpse_tender WHERE tahun IS NOT NULL AND tahun != ''
                    UNION
                    SELECT tahun FROM lpse_nontender WHERE tahun IS NOT NULL AND tahun != ''
                    UNION
                    SELECT tahun FROM lpse_pencatatan_nontender WHERE tahun IS NOT NULL AND tahun != ''
                ) t ORDER BY tahun DESC
            ")->getResultArray();
            $years = array_column($yRows, 'tahun');
        } catch (\Throwable $e) {
            $years = ['2026', '2025', '2024'];
        }
        if (empty($years)) {
            $years = ['2026', '2025', '2024'];
        }

        $data = [
            'title' => 'Dashboard Domisili Rekanan LPSE Lombok Barat',
            'menu' => 'dashboard-penyedia',
            'available_years' => $years
        ];

        return view('dashboard/dashboard_penyedia', $data);
    }

    public function getDashboardPenyediaData()
    {
        if (!has_permission('dashboard_penyedia') && !has_permission('detail_penyedia') && !has_permission('penyedia-katalog')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $db = \Config\Database::connect();
        helper('ajip');

        $tahun = trim($this->request->getGet('tahun') ?? ($_GET['tahun'] ?? ''));
        if ($tahun === 'all') $tahun = '';
        $sumber = trim($this->request->getGet('sumber') ?? ($_GET['sumber'] ?? 'all'));
        $jenisFilter = trim($this->request->getGet('jenis') ?? ($_GET['jenis'] ?? 'all'));

        $normalizeName = function($name) {
            $clean = strtoupper(trim($name));
            $clean = preg_replace('/\s*[\(\/].*$/', '', $clean);
            $clean = preg_replace('/^(PT|CV|UD|PO|KOPERASI|KOP|TOKO)\.?\s+/i', '', $clean);
            $clean = preg_replace('/[,.\s]+(PT|CV|UD|PO|TBK|PERSERO)\.?$/i', '', $clean);
            $clean = str_replace(['TEHNIK', 'KONSULTAN', 'GROUF', 'APOTIK'], ['TEKNIK', 'CONSULTANT', 'GROUP', 'APOTEK'], $clean);
            $clean = preg_replace('/[^A-Z0-9]/', '', $clean);
            return strtolower(trim($clean));
        };

        // 1. Dapatkan master profil rekanan tersinkronisasi 100% dari data detail-penyedia
        $masterVendors = $this->_getDetailPenyediaRawData('', '', '');
        $vendorMaster = [];
        $vendorMasterNorm = [];
        foreach ($masterVendors as $mv) {
            $uName = strtoupper(trim($mv['nama_penyedia'] ?? ''));
            if ($uName !== '') {
                $vendorMaster[$uName] = $mv;
                $norm = $normalizeName($uName);
                if ($norm) {
                    $vendorMasterNorm[$norm] = $mv;
                }
            }
        }

        // 2. Subqueries across LPSE transaction tables
        $subqueries = [];

        if ($sumber === 'all' || $sumber === 'tender') {
            $qT = "SELECT 
                TRIM(nama_penyedia) as nama_penyedia, 
                npwp_penyedia, 
                alamat_penyedia, 
                'Tender' as sumber, 
                metode, 
                jenis_pengadaan, 
                tahun, 
                nilai_kontrak as nilai, 
                kd_paket as paket_id 
                FROM lpse_tender 
                WHERE nama_penyedia IS NOT NULL AND TRIM(nama_penyedia) != ''";
            if ($tahun !== '') $qT .= " AND tahun = " . $db->escape($tahun);
            $subqueries[] = $qT;
        }

        if ($sumber === 'all' || $sumber === 'nontender') {
            $qNT = "SELECT 
                TRIM(nama_penyedia) as nama_penyedia, 
                npwp_penyedia, 
                alamat_penyedia, 
                'Non-Tender' as sumber, 
                metode, 
                jenis_pengadaan, 
                tahun, 
                nilai_kontrak as nilai, 
                kd_paket as paket_id 
                FROM lpse_nontender 
                WHERE nama_penyedia IS NOT NULL AND TRIM(nama_penyedia) != ''";
            if ($tahun !== '') $qNT .= " AND tahun = " . $db->escape($tahun);
            $subqueries[] = $qNT;
        }

        if ($sumber === 'all' || $sumber === 'pencatatan') {
            $qP = "SELECT 
                TRIM(nama_penyedia) as nama_penyedia, 
                npwp_penyedia, 
                '' as alamat_penyedia, 
                'Pencatatan' as sumber, 
                'Pencatatan' as metode, 
                jenis_realisasi as jenis_pengadaan, 
                tahun, 
                realisasi as nilai, 
                kd_paket as paket_id 
                FROM lpse_pencatatan_nontender 
                WHERE nama_penyedia IS NOT NULL AND TRIM(nama_penyedia) != ''";
            if ($tahun !== '') $qP .= " AND tahun = " . $db->escape($tahun);
            $subqueries[] = $qP;
        }

        if ($sumber === 'all' || $sumber === 'epurchasing') {
            $qE = "SELECT 
                TRIM(nama_penyedia) as nama_penyedia, 
                '' as npwp_penyedia, 
                '' as alamat_penyedia, 
                'E-Katalog' as sumber, 
                'E-Purchasing' as metode, 
                'Pengadaan Barang' as jenis_pengadaan, 
                tahun, 
                total_harga as nilai, 
                kd_paket as paket_id 
                FROM epurchasing 
                WHERE nama_penyedia IS NOT NULL AND TRIM(nama_penyedia) != ''";
            if ($tahun !== '') $qE .= " AND tahun = " . $db->escape($tahun);
            $subqueries[] = $qE;
        }

        if (empty($subqueries)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Tidak ada sumber data yang dipilih']);
        }

        $unionSql = implode(" UNION ALL ", $subqueries);
        $txList = $db->query($unionSql)->getResultArray();

        // 3. Filter berdasarkan Jenis Pengadaan jika dipilih
        if ($jenisFilter !== 'all') {
            $txList = array_filter($txList, function($row) use ($jenisFilter) {
                $jp = strtolower($row['jenis_pengadaan'] ?? '');
                if ($jenisFilter === 'konstruksi') return strpos($jp, 'konstruksi') !== false;
                if ($jenisFilter === 'barang') return strpos($jp, 'barang') !== false || strpos($jp, 'pesanan') !== false || strpos($jp, 'pembelian') !== false;
                if ($jenisFilter === 'konsultansi') return strpos($jp, 'konsultansi') !== false;
                if ($jenisFilter === 'jasa_lainnya') return strpos($jp, 'jasa lainnya') !== false;
                return true;
            });
        }

        // 4. Geo dictionary untuk Peta & Heatmap
        $geoMap = [
            'Lombok Barat'   => ['lat' => -8.6833, 'lng' => 116.1167, 'label' => 'Kab. Lombok Barat (Lokal)', 'cat' => 'lobar'],
            'Mataram'        => ['lat' => -8.5833, 'lng' => 116.1167, 'label' => 'Kota Mataram', 'cat' => 'ntb_lain'],
            'Lombok Tengah'  => ['lat' => -8.7000, 'lng' => 116.2833, 'label' => 'Kab. Lombok Tengah (Praya)', 'cat' => 'ntb_lain'],
            'Lombok Timur'   => ['lat' => -8.6500, 'lng' => 116.5333, 'label' => 'Kab. Lombok Timur (Selong)', 'cat' => 'ntb_lain'],
            'Lombok Utara'   => ['lat' => -8.3500, 'lng' => 116.1500, 'label' => 'Kab. Lombok Utara (Tanjung)', 'cat' => 'ntb_lain'],
            'Sumbawa Barat'  => ['lat' => -8.7333, 'lng' => 116.8500, 'label' => 'Kab. Sumbawa Barat (Taliwang)', 'cat' => 'ntb_lain'],
            'Sumbawa'        => ['lat' => -8.5000, 'lng' => 117.4333, 'label' => 'Kab. Sumbawa Besar', 'cat' => 'ntb_lain'],
            'Dompu'          => ['lat' => -8.5333, 'lng' => 118.4667, 'label' => 'Kab. Dompu', 'cat' => 'ntb_lain'],
            'Bima'           => ['lat' => -8.4667, 'lng' => 118.7333, 'label' => 'Kota & Kab. Bima', 'cat' => 'ntb_lain'],
            'Bali'           => ['lat' => -8.6705, 'lng' => 115.2126, 'label' => 'Prov. Bali (Denpasar & Badung)', 'cat' => 'luar_ntb'],
            'Surabaya'       => ['lat' => -7.2575, 'lng' => 112.7521, 'label' => 'Surabaya & Jawa Timur', 'cat' => 'luar_ntb'],
            'Malang'         => ['lat' => -7.9666, 'lng' => 112.6326, 'label' => 'Malang, Jawa Timur', 'cat' => 'luar_ntb'],
            'Semarang'       => ['lat' => -6.9667, 'lng' => 110.4167, 'label' => 'Semarang & Jawa Tengah', 'cat' => 'luar_ntb'],
            'Yogyakarta'     => ['lat' => -7.7956, 'lng' => 110.3695, 'label' => 'D.I. Yogyakarta (Sleman/Bantul)', 'cat' => 'luar_ntb'],
            'Bandung'        => ['lat' => -6.9175, 'lng' => 107.6191, 'label' => 'Bandung & Jawa Barat', 'cat' => 'luar_ntb'],
            'Jakarta'        => ['lat' => -6.2088, 'lng' => 106.8456, 'label' => 'DKI Jakarta & Sekitarnya', 'cat' => 'luar_ntb'],
            'Tangerang'      => ['lat' => -6.1783, 'lng' => 106.6319, 'label' => 'Tangerang & Banten', 'cat' => 'luar_ntb'],
            'Makassar'       => ['lat' => -5.1477, 'lng' => 119.4327, 'label' => 'Makassar & Sulawesi', 'cat' => 'luar_ntb'],
            'Medan'          => ['lat' => 3.5952,  'lng' => 98.6722,  'label' => 'Medan & Sumatera', 'cat' => 'luar_ntb'],
            'Kalimantan'     => ['lat' => -1.2379, 'lng' => 116.8289, 'label' => 'Balikpapan & Kalimantan', 'cat' => 'luar_ntb'],
            'NTT'            => ['lat' => -10.1772,'lng' => 123.6070, 'label' => 'Kupang & Nusa Tenggara Timur', 'cat' => 'luar_ntb']
        ];

        $resolveGeoKey = function($dom, $cat = '') {
            $s = strtolower(trim($dom ?? ''));
            if ($cat === 'lobar' || strpos($s, 'lombok barat') !== false || strpos($s, 'gerung') !== false || strpos($s, 'narmada') !== false || strpos($s, 'kediri') !== false || strpos($s, 'gunungsari') !== false || strpos($s, 'labuapi') !== false || strpos($s, 'lembar') !== false || strpos($s, 'batu layar') !== false || strpos($s, 'batulayar') !== false || strpos($s, 'kuripan') !== false || strpos($s, 'sekatong') !== false || strpos($s, 'lingsar') !== false) return 'Lombok Barat';
            if (strpos($s, 'mataram') !== false || strpos($s, 'ampenan') !== false || strpos($s, 'cakranegara') !== false || strpos($s, 'selaparang') !== false || strpos($s, 'sekarbela') !== false || strpos($s, 'sandubaya') !== false) return 'Mataram';
            if (strpos($s, 'lombok tengah') !== false || strpos($s, 'praya') !== false || strpos($s, 'pujut') !== false || strpos($s, 'kopang') !== false) return 'Lombok Tengah';
            if (strpos($s, 'lombok timur') !== false || strpos($s, 'selong') !== false || strpos($s, 'masbagik') !== false || strpos($s, 'aikmel') !== false) return 'Lombok Timur';
            if (strpos($s, 'lombok utara') !== false || strpos($s, 'tanjung') !== false || strpos($s, 'gangga') !== false || strpos($s, 'bayan') !== false) return 'Lombok Utara';
            if (strpos($s, 'sumbawa barat') !== false || strpos($s, 'taliwang') !== false) return 'Sumbawa Barat';
            if (strpos($s, 'sumbawa') !== false) return 'Sumbawa';
            if (strpos($s, 'dompu') !== false) return 'Dompu';
            if (strpos($s, 'bima') !== false) return 'Bima';
            if (strpos($s, 'ntb') !== false) return 'Lombok Tengah';

            // Luar NTB
            if (strpos($s, 'bali') !== false || strpos($s, 'denpasar') !== false || strpos($s, 'badung') !== false || strpos($s, 'gianyar') !== false || strpos($s, 'buleleng') !== false || strpos($s, 'tabanan') !== false) return 'Bali';
            if (strpos($s, 'tangerang') !== false || strpos($s, 'banten') !== false || strpos($s, 'serang') !== false || strpos($s, 'cilegon') !== false || strpos($s, 'pandeglang') !== false || strpos($s, 'lebak') !== false) return 'Tangerang';
            if (strpos($s, 'surabaya') !== false || strpos($s, 'sidoarjo') !== false || strpos($s, 'gresik') !== false || strpos($s, 'jawa timur') !== false || strpos($s, 'pasuruan') !== false || strpos($s, 'probolinggo') !== false || strpos($s, 'tuban') !== false || strpos($s, 'blitar') !== false || strpos($s, 'madiun') !== false || strpos($s, 'banyuwangi') !== false || strpos($s, 'jember') !== false) return 'Surabaya';
            if (strpos($s, 'malang') !== false || strpos($s, 'batu') !== false) return 'Malang';
            if (strpos($s, 'semarang') !== false || strpos($s, 'jawa tengah') !== false || strpos($s, 'solo') !== false || strpos($s, 'surakarta') !== false || strpos($s, 'klaten') !== false || strpos($s, 'batang') !== false || strpos($s, 'pekalongan') !== false || strpos($s, 'karanganyar') !== false || strpos($s, 'tegal') !== false || strpos($s, 'sukoharjo') !== false || strpos($s, 'purwokerto') !== false || strpos($s, 'banyumas') !== false || strpos($s, 'kudus') !== false) return 'Semarang';
            if (strpos($s, 'yogyakarta') !== false || strpos($s, 'jogja') !== false || strpos($s, 'sleman') !== false || strpos($s, 'bantul') !== false || strpos($s, 'kulon') !== false || strpos($s, 'gunungkidul') !== false) return 'Yogyakarta';
            if (strpos($s, 'bandung') !== false || strpos($s, 'jawa barat') !== false || strpos($s, 'bekasi') !== false || strpos($s, 'bogor') !== false || strpos($s, 'depok') !== false || strpos($s, 'cimahi') !== false || strpos($s, 'cirebon') !== false || strpos($s, 'karawang') !== false || strpos($s, 'garut') !== false || strpos($s, 'ciamis') !== false || strpos($s, 'tasikmalaya') !== false || strpos($s, 'sukabumi') !== false) return 'Bandung';
            if (strpos($s, 'jakarta') !== false || strpos($s, 'dki') !== false) return 'Jakarta';
            if (strpos($s, 'makassar') !== false || strpos($s, 'sulawesi') !== false || strpos($s, 'gorontalo') !== false || strpos($s, 'manado') !== false || strpos($s, 'palu') !== false || strpos($s, 'kendari') !== false) return 'Makassar';
            if (strpos($s, 'medan') !== false || strpos($s, 'sumatera') !== false || strpos($s, 'palembang') !== false || strpos($s, 'lampung') !== false || strpos($s, 'tanjung pinang') !== false || strpos($s, 'banda aceh') !== false || strpos($s, 'aceh') !== false || strpos($s, 'padang') !== false || strpos($s, 'pekanbaru') !== false || strpos($s, 'riau') !== false || strpos($s, 'jambi') !== false || strpos($s, 'bengkulu') !== false || strpos($s, 'batam') !== false) return 'Medan';
            if (strpos($s, 'kalimantan') !== false || strpos($s, 'balikpapan') !== false || strpos($s, 'samarinda') !== false || strpos($s, 'banjarmasin') !== false || strpos($s, 'pontianak') !== false) return 'Kalimantan';
            if (strpos($s, 'ntt') !== false || strpos($s, 'kupang') !== false || strpos($s, 'flores') !== false || strpos($s, 'timor') !== false) return 'NTT';
            
            if ($cat === 'luar_ntb') return 'Jakarta';
            return 'Lombok Barat';
        };

        // 5. Vendor Profiling & Aggregation
        $uniqueVendors = [];
        $totalNilai = 0;
        $totalPaket = count($txList);

        // Agregasi Metode & Jenis
        $metodeCategories = ['Tender', 'Pengadaan Langsung', 'Pencatatan', 'E-Katalog / Lainnya'];
        $metodeMap = [];
        foreach ($metodeCategories as $m) {
            $metodeMap[$m] = ['lobar' => 0, 'ntb_lain' => 0, 'luar_ntb' => 0, 'unclassified' => 0];
        }

        $jenisCategories = ['Pekerjaan Konstruksi', 'Pengadaan Barang', 'Jasa Konsultansi', 'Jasa Lainnya'];
        $jenisMap = [];
        foreach ($jenisCategories as $j) {
            $jenisMap[$j] = ['lobar' => 0, 'ntb_lain' => 0, 'luar_ntb' => 0, 'unclassified' => 0];
        }

        $geoNodes = [];
        $domisiliRank = [];

        foreach ($txList as $tx) {
            $nama = trim($tx['nama_penyedia']);
            $uName = strtoupper($nama);
            $norm = $normalizeName($nama);
            $nilai = (float)($tx['nilai'] ?? 0);
            $totalNilai += $nilai;

            // Lookup profil vendor dari master detail-penyedia yang sudah tervalidasi
            $vProf = $vendorMaster[$uName] ?? ($vendorMasterNorm[$norm] ?? null);
            if ($vProf) {
                $domisili = $vProf['domisili'];
                $flagCat = $vProf['flag_cat'];
            } else {
                $klas = classify_npwp_location($tx['npwp_penyedia'] ?? '', $tx['alamat_penyedia'] ?? '', $nama);
                $domisili = $klas['domisili'] ?: 'Lombok Barat';
                $flagCat = $klas['flag_kategori'] ?: 'lobar';
            }

            if ($flagCat === 'lobar') {
                $cat = 'lobar';
            } elseif ($flagCat === 'mataram' || $flagCat === 'ntb_lain') {
                $cat = 'ntb_lain';
            } elseif ($flagCat === 'luar') {
                $cat = 'luar_ntb';
            } else {
                $cat = 'unclassified';
            }

            // Normalisasi metode string
            $rawMetode = trim($tx['metode'] ?? '');
            $mGroup = 'E-Katalog / Lainnya';
            if (stripos($rawMetode, 'tender') !== false || stripos($rawMetode, 'seleksi') !== false) {
                $mGroup = 'Tender';
            } elseif (stripos($rawMetode, 'langsung') !== false || stripos($rawMetode, 'penunjukan') !== false) {
                $mGroup = 'Pengadaan Langsung';
            } elseif (stripos($rawMetode, 'catat') !== false || $tx['sumber'] === 'Pencatatan') {
                $mGroup = 'Pencatatan';
            }
            if (isset($metodeMap[$mGroup][$cat])) {
                $metodeMap[$mGroup][$cat]++;
            }

            // Normalisasi jenis pengadaan
            $rawJenis = trim($tx['jenis_pengadaan'] ?? '');
            $jGroup = 'Pengadaan Barang';
            if (stripos($rawJenis, 'konstruksi') !== false) {
                $jGroup = 'Pekerjaan Konstruksi';
            } elseif (stripos($rawJenis, 'konsultansi') !== false) {
                $jGroup = 'Jasa Konsultansi';
            } elseif (stripos($rawJenis, 'lainnya') !== false) {
                $jGroup = 'Jasa Lainnya';
            } elseif (stripos($rawJenis, 'barang') !== false || stripos($rawJenis, 'pesanan') !== false || stripos($rawJenis, 'pembelian') !== false) {
                $jGroup = 'Pengadaan Barang';
            }
            if (isset($jenisMap[$jGroup][$cat])) {
                $jenisMap[$jGroup][$cat]++;
            }

            // Register per unique vendor
            if (!isset($uniqueVendors[$uName])) {
                $uniqueVendors[$uName] = [
                    'nama_penyedia' => $nama,
                    'domisili' => $domisili,
                    'cat' => $cat,
                    'flag_cat' => $flagCat,
                    'total_paket' => 0,
                    'total_nilai' => 0,
                    'metode_utama' => $mGroup,
                    'sumber_list' => []
                ];
            }
            $uniqueVendors[$uName]['total_paket']++;
            $uniqueVendors[$uName]['total_nilai'] += $nilai;
            $uniqueVendors[$uName]['sumber_list'][$tx['sumber']] = true;

            // Register Geo Nodes
            $geoKey = $resolveGeoKey($domisili, $cat);
            if ($geoKey && isset($geoMap[$geoKey])) {
                if (!isset($geoNodes[$geoKey])) {
                    $geoNodes[$geoKey] = [
                        'city' => $geoKey,
                        'label' => $geoMap[$geoKey]['label'],
                        'lat' => $geoMap[$geoKey]['lat'],
                        'lng' => $geoMap[$geoKey]['lng'],
                        'cat' => $geoMap[$geoKey]['cat'],
                        'vendor_map' => [],
                        'packages' => 0,
                        'total_val' => 0
                    ];
                }
                $geoNodes[$geoKey]['vendor_map'][$uName] = ($geoNodes[$geoKey]['vendor_map'][$uName] ?? 0) + $nilai;
                $geoNodes[$geoKey]['packages']++;
                $geoNodes[$geoKey]['total_val'] += $nilai;
            }

            // Register Domisili Ranking
            if (!isset($domisiliRank[$domisili])) {
                $domisiliRank[$domisili] = [
                    'domisili' => $domisili,
                    'cat' => $cat,
                    'vendor_map' => [],
                    'packages' => 0,
                    'total_val' => 0
                ];
            }
            $domisiliRank[$domisili]['vendor_map'][$uName] = true;
            $domisiliRank[$domisili]['packages']++;
            $domisiliRank[$domisili]['total_val'] += $nilai;
        }

        // 6. Calculate KPI Totals & Ratios
        $totalVendors = count($uniqueVendors);
        $kpi = [
            'total_vendors' => $totalVendors,
            'total_packages' => $totalPaket,
            'total_value' => $totalNilai,
            'total_value_formatted' => 'Rp ' . number_format($totalNilai, 0, ',', '.'),
            'total_value_short' => 'Rp ' . number_format($totalNilai / 1e9, 2) . ' M',
            'lobar_vendors' => 0,
            'lobar_value' => 0,
            'ntb_vendors' => 0,
            'ntb_value' => 0,
            'luar_vendors' => 0,
            'luar_value' => 0,
            'unclassified_vendors' => 0,
            'unclassified_value' => 0
        ];

        foreach ($uniqueVendors as $uv) {
            $cat = $uv['cat'];
            if ($cat === 'lobar') {
                $kpi['lobar_vendors']++;
                $kpi['lobar_value'] += $uv['total_nilai'];
            } elseif ($cat === 'ntb_lain') {
                $kpi['ntb_vendors']++;
                $kpi['ntb_value'] += $uv['total_nilai'];
            } elseif ($cat === 'luar_ntb') {
                $kpi['luar_vendors']++;
                $kpi['luar_value'] += $uv['total_nilai'];
            } else {
                $kpi['unclassified_vendors']++;
                $kpi['unclassified_value'] += $uv['total_nilai'];
            }
        }

        $kpi['lobar_vendors_pct'] = $totalVendors > 0 ? round(($kpi['lobar_vendors'] / $totalVendors) * 100, 1) : 0;
        $kpi['lobar_value_pct'] = $totalNilai > 0 ? round(($kpi['lobar_value'] / $totalNilai) * 100, 1) : 0;
        $kpi['ntb_vendors_pct'] = $totalVendors > 0 ? round(($kpi['ntb_vendors'] / $totalVendors) * 100, 1) : 0;
        $kpi['ntb_value_pct'] = $totalNilai > 0 ? round(($kpi['ntb_value'] / $totalNilai) * 100, 1) : 0;
        $kpi['luar_vendors_pct'] = $totalVendors > 0 ? round(($kpi['luar_vendors'] / $totalVendors) * 100, 1) : 0;
        $kpi['luar_value_pct'] = $totalNilai > 0 ? round(($kpi['luar_value'] / $totalNilai) * 100, 1) : 0;

        // 7. Prepare Chart Data
        // Donut Domisili: hitung mataram & lombok tengah langsung dari uniqueVendors
        $mataramCount = 0;
        $lotengCount = 0;
        foreach ($uniqueVendors as $uv) {
            $dLow = strtolower($uv['domisili']);
            if ($uv['flag_cat'] === 'mataram' || strpos($dLow, 'mataram') !== false) {
                $mataramCount++;
            } elseif (strpos($dLow, 'lombok tengah') !== false) {
                $lotengCount++;
            }
        }
        $ntbLainnyaCount = max(0, $kpi['ntb_vendors'] - $mataramCount - $lotengCount);

        $donutLabels = ['Lombok Barat (Lokal)', 'Kota Mataram', 'Lombok Tengah', 'NTB Lainnya', 'Luar NTB', 'Belum Terdata'];
        $donutValues = [
            $kpi['lobar_vendors'],
            $mataramCount,
            $lotengCount,
            $ntbLainnyaCount,
            $kpi['luar_vendors'],
            $kpi['unclassified_vendors']
        ];

        // Top 8 Domisili by Nilai Kontrak
        uasort($domisiliRank, fn($a, $b) => $b['total_val'] <=> $a['total_val']);
        $topDomValueLabels = [];
        $topDomValueData = [];
        foreach (array_slice($domisiliRank, 0, 8, true) as $dr) {
            $topDomValueLabels[] = $dr['domisili'];
            $topDomValueData[] = round($dr['total_val'] / 1e9, 2); // dalam Miliar
        }

        // 8. Prepare Map Geo Nodes & Heat Points
        $nodesList = [];
        $heatPoints = [];
        $maxVal = 1;
        foreach ($geoNodes as $gn) {
            if ($gn['total_val'] > $maxVal) $maxVal = $gn['total_val'];
        }

        foreach ($geoNodes as $gn) {
            arsort($gn['vendor_map']);
            $topV = [];
            foreach (array_slice($gn['vendor_map'], 0, 3, true) as $vName => $vVal) {
                $topV[] = [
                    'name' => $vName,
                    'val' => 'Rp ' . number_format($vVal / 1e9, 2) . ' M'
                ];
            }

            $vCount = count($gn['vendor_map']);
            $intensity = min(1.0, max(0.2, ($gn['total_val'] / $maxVal)));

            $nodesList[] = [
                'city' => $gn['city'],
                'label' => $gn['label'],
                'lat' => $gn['lat'],
                'lng' => $gn['lng'],
                'cat' => $gn['cat'],
                'vendors_count' => $vCount,
                'packages_count' => $gn['packages'],
                'total_val' => $gn['total_val'],
                'total_val_formatted' => 'Rp ' . number_format($gn['total_val'], 0, ',', '.'),
                'total_val_m' => 'Rp ' . number_format($gn['total_val'] / 1e9, 2) . ' M',
                'top_vendors' => $topV
            ];

            // Heat point format: [lat, lng, weight]
            $heatPoints[] = [$gn['lat'], $gn['lng'], round($intensity, 2)];
        }

        // 9. Prepare Tables
        // Top 10 Domisili Table
        $tablesDomisili = [];
        $no = 1;
        foreach ($domisiliRank as $dr) {
            $vCount = count($dr['vendor_map']);
            $tablesDomisili[] = [
                'no' => $no++,
                'domisili' => $dr['domisili'],
                'cat' => $dr['cat'],
                'vendors' => $vCount,
                'packages' => $dr['packages'],
                'total_val' => $dr['total_val'],
                'total_val_formatted' => 'Rp ' . number_format($dr['total_val'], 0, ',', '.'),
                'pct_val' => $totalNilai > 0 ? round(($dr['total_val'] / $totalNilai) * 100, 1) : 0,
                'pct_vendor' => $totalVendors > 0 ? round(($vCount / $totalVendors) * 100, 1) : 0
            ];
        }

        // Rekanan Table (Tampilkan semua rekanan pemenang paket)
        uasort($uniqueVendors, fn($a, $b) => $b['total_nilai'] <=> $a['total_nilai']);
        $tablesRekanan = [];
        $no = 1;
        foreach ($uniqueVendors as $uv) {
            $tablesRekanan[] = [
                'no' => $no++,
                'nama_penyedia' => $uv['nama_penyedia'],
                'domisili' => $uv['domisili'],
                'cat' => $uv['cat'],
                'total_paket' => $uv['total_paket'],
                'total_nilai' => $uv['total_nilai'],
                'total_nilai_formatted' => 'Rp ' . number_format($uv['total_nilai'], 0, ',', '.'),
                'metode_utama' => $uv['metode_utama'],
                'sumber' => implode(', ', array_keys($uv['sumber_list']))
            ];
        }

        return $this->response->setJSON([
            'status' => 'success',
            'kpi' => $kpi,
            'charts' => [
                'metode' => [
                    'categories' => $metodeCategories,
                    'lobar' => array_column($metodeMap, 'lobar'),
                    'ntb_lain' => array_column($metodeMap, 'ntb_lain'),
                    'luar_ntb' => array_column($metodeMap, 'luar_ntb'),
                    'unclassified' => array_column($metodeMap, 'unclassified'),
                ],
                'jenis' => [
                    'categories' => $jenisCategories,
                    'lobar' => array_column($jenisMap, 'lobar'),
                    'ntb_lain' => array_column($jenisMap, 'ntb_lain'),
                    'luar_ntb' => array_column($jenisMap, 'luar_ntb'),
                    'unclassified' => array_column($jenisMap, 'unclassified'),
                ],
                'domisili_donut' => [
                    'labels' => $donutLabels,
                    'values' => $donutValues
                ],
                'nilai_by_domisili' => [
                    'labels' => $topDomValueLabels,
                    'values' => $topDomValueData
                ]
            ],
            'map' => [
                'nodes' => $nodesList,
                'heat_points' => $heatPoints
            ],
            'tables' => [
                'top_domisili' => array_slice($tablesDomisili, 0, 12),
                'top_rekanan' => $tablesRekanan
            ]
        ]);
    }

    public function exportDashboardRekanan()
    {
        if (!has_permission('dashboard_penyedia') && !has_permission('detail_penyedia') && !has_permission('penyedia-katalog') && session()->get('role') !== 'admin') {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses.');
        }

        $db = \Config\Database::connect();
        helper('ajip');

        $tahun = trim($this->request->getGet('tahun') ?? ($_GET['tahun'] ?? ''));
        if ($tahun === 'all') $tahun = '';
        $sumber = trim($this->request->getGet('sumber') ?? ($_GET['sumber'] ?? 'all'));
        $jenisFilter = trim($this->request->getGet('jenis') ?? ($_GET['jenis'] ?? 'all'));

        $normalizeName = function($name) {
            $clean = strtoupper(trim($name));
            $clean = preg_replace('/\s*[\(\/].*$/', '', $clean);
            $clean = preg_replace('/^(PT|CV|UD|PO|KOPERASI|KOP|TOKO)\.?\s+/i', '', $clean);
            $clean = preg_replace('/[,.\s]+(PT|CV|UD|PO|TBK|PERSERO)\.?$/i', '', $clean);
            $clean = str_replace(['TEHNIK', 'KONSULTAN', 'GROUF', 'APOTIK'], ['TEKNIK', 'CONSULTANT', 'GROUP', 'APOTEK'], $clean);
            $clean = preg_replace('/[^A-Z0-9]/', '', $clean);
            return strtolower(trim($clean));
        };

        // Master profil rekanan tersinkronisasi 100% dari data detail-penyedia
        $masterVendors = $this->_getDetailPenyediaRawData('', '', '');
        $vendorMaster = [];
        $vendorMasterNorm = [];
        foreach ($masterVendors as $mv) {
            $uName = strtoupper(trim($mv['nama_penyedia'] ?? ''));
            if ($uName !== '') {
                $vendorMaster[$uName] = $mv;
                $norm = $normalizeName($uName);
                if ($norm) {
                    $vendorMasterNorm[$norm] = $mv;
                }
            }
        }

        // Subqueries transaksi
        $subqueries = [];
        if ($sumber === 'all' || $sumber === 'tender') {
            $qT = "SELECT TRIM(nama_penyedia) as nama_penyedia, npwp_penyedia, alamat_penyedia, 'Tender' as sumber, metode, jenis_pengadaan, tahun, nilai_kontrak as nilai, kd_paket as paket_id FROM lpse_tender WHERE nama_penyedia IS NOT NULL AND TRIM(nama_penyedia) != ''";
            if ($tahun !== '') $qT .= " AND tahun = " . $db->escape($tahun);
            $subqueries[] = $qT;
        }
        if ($sumber === 'all' || $sumber === 'nontender') {
            $qNT = "SELECT TRIM(nama_penyedia) as nama_penyedia, npwp_penyedia, alamat_penyedia, 'Non-Tender' as sumber, metode, jenis_pengadaan, tahun, nilai_kontrak as nilai, kd_paket as paket_id FROM lpse_nontender WHERE nama_penyedia IS NOT NULL AND TRIM(nama_penyedia) != ''";
            if ($tahun !== '') $qNT .= " AND tahun = " . $db->escape($tahun);
            $subqueries[] = $qNT;
        }
        if ($sumber === 'all' || $sumber === 'pencatatan') {
            $qP = "SELECT TRIM(nama_penyedia) as nama_penyedia, npwp_penyedia, '' as alamat_penyedia, 'Pencatatan' as sumber, 'Pencatatan' as metode, jenis_realisasi as jenis_pengadaan, tahun, realisasi as nilai, kd_paket as paket_id FROM lpse_pencatatan_nontender WHERE nama_penyedia IS NOT NULL AND TRIM(nama_penyedia) != ''";
            if ($tahun !== '') $qP .= " AND tahun = " . $db->escape($tahun);
            $subqueries[] = $qP;
        }
        if ($sumber === 'all' || $sumber === 'epurchasing') {
            $qE = "SELECT TRIM(nama_penyedia) as nama_penyedia, '' as npwp_penyedia, '' as alamat_penyedia, 'E-Katalog' as sumber, 'E-Purchasing' as metode, 'Pengadaan Barang' as jenis_pengadaan, tahun, total_harga as nilai, kd_paket as paket_id FROM epurchasing WHERE nama_penyedia IS NOT NULL AND TRIM(nama_penyedia) != ''";
            if ($tahun !== '') $qE .= " AND tahun = " . $db->escape($tahun);
            $subqueries[] = $qE;
        }

        if (empty($subqueries)) {
            return redirect()->back()->with('error', 'Tidak ada sumber data yang dipilih.');
        }

        $unionSql = implode(" UNION ALL ", $subqueries);
        $txList = $db->query($unionSql)->getResultArray();

        if ($jenisFilter !== 'all') {
            $txList = array_filter($txList, function($row) use ($jenisFilter) {
                $jp = strtolower($row['jenis_pengadaan'] ?? '');
                if ($jenisFilter === 'konstruksi') return strpos($jp, 'konstruksi') !== false;
                if ($jenisFilter === 'barang') return strpos($jp, 'barang') !== false || strpos($jp, 'pesanan') !== false || strpos($jp, 'pembelian') !== false;
                if ($jenisFilter === 'konsultansi') return strpos($jp, 'konsultansi') !== false;
                if ($jenisFilter === 'jasa_lainnya') return strpos($jp, 'jasa lainnya') !== false;
                return true;
            });
        }

        $uniqueVendors = [];
        $totalNilai = 0;
        foreach ($txList as $tx) {
            $nama = trim($tx['nama_penyedia']);
            $uName = strtoupper($nama);
            $norm = $normalizeName($nama);
            $nilai = (float)($tx['nilai'] ?? 0);
            $totalNilai += $nilai;

            $vProf = $vendorMaster[$uName] ?? ($vendorMasterNorm[$norm] ?? null);
            if ($vProf) {
                $domisili = $vProf['domisili'];
                $flagCat = $vProf['flag_cat'];
                $kategoriWilayah = $vProf['kategori_wilayah'] ?? ($flagCat === 'lobar' ? 'Lombok Barat' : ($flagCat === 'mataram' ? 'Kota Mataram' : ($flagCat === 'ntb_lain' ? 'NTB Lainnya' : 'Luar NTB')));
            } else {
                $klas = classify_npwp_location($tx['npwp_penyedia'] ?? '', $tx['alamat_penyedia'] ?? '', $nama);
                $domisili = $klas['domisili'] ?: 'Lombok Barat';
                $flagCat = $klas['flag_kategori'] ?: 'lobar';
                $kategoriWilayah = $flagCat === 'lobar' ? 'Lombok Barat' : ($flagCat === 'mataram' ? 'Kota Mataram' : ($flagCat === 'ntb_lain' ? 'NTB Lainnya' : 'Luar NTB'));
            }

            $rawMetode = trim($tx['metode'] ?? '');
            $mGroup = 'E-Katalog / Lainnya';
            if (stripos($rawMetode, 'tender') !== false || stripos($rawMetode, 'seleksi') !== false) {
                $mGroup = 'Tender';
            } elseif (stripos($rawMetode, 'langsung') !== false || stripos($rawMetode, 'penunjukan') !== false) {
                $mGroup = 'Pengadaan Langsung';
            } elseif (stripos($rawMetode, 'catat') !== false || $tx['sumber'] === 'Pencatatan') {
                $mGroup = 'Pencatatan';
            }

            if (!isset($uniqueVendors[$uName])) {
                $uniqueVendors[$uName] = [
                    'nama_penyedia' => $nama,
                    'domisili' => $domisili,
                    'kategori_wilayah' => $kategoriWilayah,
                    'total_paket' => 0,
                    'total_nilai' => 0,
                    'metode_utama' => $mGroup,
                    'sumber_list' => []
                ];
            }
            $uniqueVendors[$uName]['total_paket']++;
            $uniqueVendors[$uName]['total_nilai'] += $nilai;
            $uniqueVendors[$uName]['sumber_list'][$tx['sumber']] = true;
        }

        uasort($uniqueVendors, fn($a, $b) => $b['total_nilai'] <=> $a['total_nilai']);

        // Generate Excel Spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekanan Terbesar');

        $spreadsheet->getProperties()
            ->setCreator('InfoAJIP - Pengadaan Barang & Jasa')
            ->setTitle('Daftar Rekanan Pemenang Paket Terbesar LPSE')
            ->setSubject('Rekanan Nilai Kontrak Tertinggi');

        // Header Title Block
        $sheet->setCellValue('A1', 'DAFTAR REKANAN PEMENANG PAKET TERBESAR LPSE');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF1E2538'));
        $sheet->setCellValue('A2', 'Pemerintah Kabupaten Lombok Barat - Sistem Informasi Pengadaan (InfoAJIP)');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF6C757D'));

        $labelTahun = ($tahun !== '' && $tahun !== 'all') ? $tahun : 'Semua Tahun';
        $labelSumber = ucfirst($sumber);
        $labelJenis = ucfirst($jenisFilter);
        $metaInfo = 'Tahun: ' . $labelTahun . ' | Sumber: ' . $labelSumber . ' | Pekerjaan: ' . $labelJenis . ' | Total: ' . number_format(count($uniqueVendors), 0, ',', '.') . ' Rekanan | Diunduh: ' . date('d/m/Y H:i') . ' WITA';
        $sheet->setCellValue('A3', $metaInfo);
        $sheet->getStyle('A3')->getFont()->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF495057'));

        // Table Column Headers (Row 5)
        $headers = [
            'A5' => 'Peringkat',
            'B5' => 'Nama Rekanan / Penyedia',
            'C5' => 'Domisili Usaha',
            'D5' => 'Kategori Wilayah',
            'E5' => 'Metode Utama',
            'F5' => 'Sumber Transaksi',
            'G5' => 'Total Paket',
            'H5' => 'Total Nilai Kontrak (Rp)'
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10],
            'fill' => ['type' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['argb' => 'FF1E2538']],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ],
        ];
        $sheet->getStyle('A5:H5')->applyFromArray($headerStyle);
        $sheet->getRowDimension(5)->setRowHeight(28);

        $rowNum = 6;
        $rank = 1;
        foreach ($uniqueVendors as $uv) {
            $sheet->setCellValue('A' . $rowNum, $rank++);
            $sheet->setCellValue('B' . $rowNum, $uv['nama_penyedia']);
            $sheet->setCellValue('C' . $rowNum, $uv['domisili']);
            $sheet->setCellValue('D' . $rowNum, $uv['kategori_wilayah']);
            $sheet->setCellValue('E' . $rowNum, $uv['metode_utama']);
            $sheet->setCellValue('F' . $rowNum, implode(', ', array_keys($uv['sumber_list'])));
            $sheet->setCellValue('G' . $rowNum, (int)$uv['total_paket']);
            $sheet->setCellValue('H' . $rowNum, (float)$uv['total_nilai']);

            $sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $rowNum)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $rowNum)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $rowNum)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G' . $rowNum)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G' . $rowNum)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('H' . $rowNum)->getNumberFormat()->setFormatCode('"Rp "#,##0');
            $sheet->getStyle('H' . $rowNum)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

            if ($rowNum % 2 == 1) {
                $sheet->getStyle('A' . $rowNum . ':H' . $rowNum)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFF8F9FA');
            }

            $rowNum++;
        }

        // Summary Row
        $summaryRow = $rowNum;
        $sheet->setCellValue('A' . $summaryRow, 'TOTAL');
        $sheet->mergeCells('A' . $summaryRow . ':F' . $summaryRow);
        $sheet->setCellValue('G' . $summaryRow, '=SUM(G6:G' . ($summaryRow - 1) . ')');
        $sheet->setCellValue('H' . $summaryRow, '=SUM(H6:H' . ($summaryRow - 1) . ')');

        $sheet->getStyle('A' . $summaryRow . ':H' . $summaryRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FF1E2538'], 'size' => 10],
            'fill' => ['type' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['argb' => 'FFE9ECEF']],
        ]);
        $sheet->getStyle('A' . $summaryRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G' . $summaryRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('H' . $summaryRow)->getNumberFormat()->setFormatCode('"Rp "#,##0');

        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FFD1D5DB'],
                ],
            ],
        ];
        $sheet->getStyle('A5:H' . $summaryRow)->applyFromArray($borderStyle);

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $cleanTahun = ($labelTahun !== 'Semua Tahun' ? $labelTahun . '_' : '');
        $cleanSumber = ($sumber !== 'all' ? ucfirst($sumber) . '_' : '');
        $filename = 'Rekanan_Terbesar_LPSE_' . $cleanTahun . $cleanSumber . date('Ymd_His') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function detailPenyedia()
    {
        if (!has_permission('detail_penyedia') && !has_permission('penyedia-katalog')) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke halaman Detail Penyedia.');
        }

        $data = [
            'title' => 'Detail Penyedia (Semua Transaksi)',
            'menu' => 'detail-penyedia'
        ];

        return view('dashboard/detail_penyedia', $data);
    }

    public function scrapePerusahaan()
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to(base_url('dashboard'))->with('error', 'Hanya administrator yang memiliki akses ke menu Scrape Data Perusahaan.');
        }

        $db = \Config\Database::connect();

        // 1. Statistik kontraktor (PengadaanPro)
        $totalContractors = 0;
        $totalProvinces = 0;
        $ntbCount = 0;
        $hasNpwp = 0;
        $hasAddress = 0;
        $hasPhone = 0;
        $lastScrape = null;
        $topProvinces = [];
        $previewRows = [];

        try {
            if ($db->tableExists('pengadaanpro_contractors')) {
                $totalContractors = $db->table('pengadaanpro_contractors')->countAllResults();
                $totalProvinces = $db->query("SELECT COUNT(DISTINCT province) as c FROM pengadaanpro_contractors WHERE province IS NOT NULL AND province != ''")->getRow()->c ?? 0;
                $ntbCount = $db->table('pengadaanpro_contractors')->where('province', 'Nusa Tenggara Barat')->countAllResults();
                $hasNpwp = $db->table('pengadaanpro_contractors')->where("npwp IS NOT NULL AND npwp != ''")->countAllResults();
                $hasAddress = $db->table('pengadaanpro_contractors')->where("address IS NOT NULL AND address != ''")->countAllResults();
                $hasPhone = $db->table('pengadaanpro_contractors')->where("phone IS NOT NULL AND phone != ''")->countAllResults();

                $lastRow = $db->table('pengadaanpro_contractors')->orderBy('updated_at', 'DESC')->limit(1)->get()->getFirstRow('array');
                if ($lastRow && !empty($lastRow['updated_at'])) {
                    $lastScrape = date('d M Y H:i', strtotime($lastRow['updated_at']));
                }

                $topProvinces = $db->query("SELECT province, COUNT(*) as c FROM pengadaanpro_contractors WHERE province IS NOT NULL AND province != '' GROUP BY province ORDER BY c DESC LIMIT 5")->getResultArray();
                $previewRows = $db->table('pengadaanpro_contractors')
                    ->select('contractor_name, npwp, domisili_kota, province, address, phone, email, remote_id')
                    ->orderBy('updated_at', 'DESC')
                    ->limit(15)
                    ->get()->getResultArray();
            }
        } catch (\Throwable $e) {
            log_message('error', 'scrapePerusahaan stats error: ' . $e->getMessage());
        }

        // 2. Statistik Hasil Bot SIKaP
        $csvPath = ROOTPATH . 'Hasil_Bot_SIKaP.csv';
        if (!is_file($csvPath)) {
            $csvPath = FCPATH . 'Hasil_Bot_SIKaP.csv';
        }
        $sikapFileExists = is_file($csvPath);
        $sikapTotalRows = 0;
        $sikapFileSizeFormatted = '0 KB';
        $sikapLastModified = '-';
        $sikapFileName = 'Hasil_Bot_SIKaP.csv';

        if ($sikapFileExists) {
            $size = filesize($csvPath);
            $sikapFileSizeFormatted = $size > 1048576 
                ? number_format($size / 1048576, 2) . ' MB' 
                : number_format($size / 1024, 2) . ' KB';
            $sikapLastModified = date('d M Y H:i', filemtime($csvPath));

            if (($h = fopen($csvPath, 'r')) !== false) {
                fgetcsv($h, 0, ';');
                while (($r = fgetcsv($h, 0, ';')) !== false) {
                    if (!empty($r[0]) || !empty($r[1])) {
                        $sikapTotalRows++;
                    }
                }
                fclose($h);
            }
        }

        $dbSikapCount = 0;
        if ($db->tableExists('inaproc_vendor_sync')) {
            $dbSikapCount = $db->table('inaproc_vendor_sync')
                ->whereIn('last_sync_status', ['sikap_sync', 'sikap_csv'])
                ->orLike('kode_penyedia', 'SIKAP_', 'after')
                ->countAllResults();
        }

        $spseCount = 0;
        if ($db->tableExists('pengadaanpro_contractors')) {
            $spseCount = $db->table('pengadaanpro_contractors')
                ->like('remote_id', 'spse_', 'after')
                ->countAllResults();
        }

        $data = [
            'title' => 'Scrape & Database Referensi Penyedia',
            'menu' => 'scrape-perusahaan',
            'total_contractors' => $totalContractors,
            'total_provinces' => $totalProvinces,
            'ntb_count' => $ntbCount,
            'has_npwp' => $hasNpwp,
            'has_address' => $hasAddress,
            'has_phone' => $hasPhone,
            'last_scrape' => $lastScrape,
            'top_provinces' => $topProvinces,
            'preview_rows' => $previewRows,
            'sikap_file_exists' => $sikapFileExists,
            'sikap_file_name' => $sikapFileName,
            'sikap_file_size' => $sikapFileSizeFormatted,
            'sikap_last_modified' => $sikapLastModified,
            'sikap_total_rows' => $sikapTotalRows,
            'db_sikap_count' => $dbSikapCount,
            'spse_count' => $spseCount,
        ];

        return view('dashboard/scrape_perusahaan', $data);
    }

    public function doScrapePerusahaan()
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to(base_url('dashboard'))->with('error', 'Hanya administrator yang dapat menjalankan scrape.');
        }

        set_time_limit(600); // max 10 menit
        $mode = $this->request->getPost('mode') ?? 'all';
        $syncInaproc = $this->request->getPost('sync_inaproc') == '1';

        $headers = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept: application/json',
        ];

        $allContractors = [];
        $baseUrl = 'https://www.pengadaanpro.com/api/contractors';

        try {
            if ($mode === 'ntb') {
                // Mode NTB: Ambil semua dari provinsi NTB + detail via HTML page
                $url = $baseUrl . '?province=' . urlencode('Nusa Tenggara Barat') . '&per_page=100&page=1';
                $resp = $this->curlGetJson($url, $headers);
                if ($resp && isset($resp['hits'])) {
                    foreach ($resp['hits'] as $h) {
                        $slug = $h['slug'] ?? '';
                        $item = $h;
                        // Ambil detail via HTML page
                        if ($slug) {
                            $detailUrl = 'https://www.pengadaanpro.com/contractors/' . $slug;
                            $detailHtml = $this->curlGetHtml($detailUrl, $headers);
                            if ($detailHtml && preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $detailHtml, $m)) {
                                $nextData = json_decode($m[1], true);
                                $props = $nextData['props']['pageProps'] ?? [];
                                $c = $props['contractor'] ?? [];
                                $item['address'] = $c['address'] ?? '';
                                $item['city'] = $c['city'] ?? '';
                                $item['phone'] = $c['phone'] ?? '';
                                $item['email'] = $c['email'] ?? '';
                                $pengurus = $props['pengurus'] ?? [];
                                if (is_array($pengurus) && !empty($pengurus)) {
                                    $item['direktur'] = implode(', ', array_map(fn($p) => ($p['nama'] ?? '') . ' (' . ($p['jabatan'] ?? '') . ')', $pengurus));
                                }
                            }
                            usleep(300000); // 300ms delay
                        }
                        $allContractors[$h['id'] ?? $slug] = $item;
                    }
                }
            } else {
                // Mode ALL: Sweep per provinsi + keyword search
                $resp = $this->curlGetJson($baseUrl . '?per_page=1&page=1', $headers);
                $facets = $resp['facets']['province'] ?? [];
                $provinces = array_column($facets, 'value');

                // Sweep provinsi
                foreach ($provinces as $prov) {
                    $url = $baseUrl . '?province=' . urlencode($prov) . '&per_page=100&page=1';
                    $r = $this->curlGetJson($url, $headers);
                    if ($r && isset($r['hits'])) {
                        foreach ($r['hits'] as $h) {
                            $allContractors[$h['id']] = $h;
                        }
                    }
                    usleep(50000);
                }

                // Sweep per provinsi besar + huruf vokal/konsonan
                $bigProvs = array_filter($provinces, function($p) use ($facets) {
                    foreach ($facets as $f) { if ($f['value'] === $p && $f['count'] > 100) return true; }
                    return false;
                });
                $searchChars = ['a','i','u','e','o','k','m','s','t','b','p','d','l','n','r','g','j','w','y','c','f','h'];
                foreach ($bigProvs as $prov) {
                    foreach ($searchChars as $ch) {
                        $url = $baseUrl . '?province=' . urlencode($prov) . '&q=' . $ch . '&per_page=100&page=1';
                        $r = $this->curlGetJson($url, $headers);
                        if ($r && isset($r['hits'])) {
                            foreach ($r['hits'] as $h) {
                                $allContractors[$h['id']] = $h;
                            }
                        }
                        usleep(40000);
                    }
                }

                // Keyword sweep
                $keywords = ['karya','jaya','mandiri','abadi','makmur','sentosa','utama','sejahtera',
                    'bina','prima','persada','sarana','anugerah','berkah','nusantara','agung','cahaya',
                    'dharma','indah','lestari','megah','mitra','mulia','perkasa','sukses','tri','panca',
                    'wijaya','cipta','graha','inti','kencana','pilar','sinar','surya','tehnik','teknik',
                    'bangun','wahana','samudra'];
                foreach ($keywords as $kw) {
                    $url = $baseUrl . '?q=' . urlencode($kw) . '&per_page=100&page=1';
                    $r = $this->curlGetJson($url, $headers);
                    if ($r && isset($r['hits'])) {
                        foreach ($r['hits'] as $h) {
                            $allContractors[$h['id']] = $h;
                        }
                    }
                    usleep(40000);
                }
            }
        } catch (\Throwable $e) {
            return redirect()->to(base_url('dashboard/scrape-perusahaan'))
                ->with('error', 'Error saat scraping: ' . $e->getMessage());
        }

        if (empty($allContractors)) {
            return redirect()->to(base_url('dashboard/scrape-perusahaan'))
                ->with('error', 'Tidak ada data yang berhasil diambil dari PengadaanPro. Coba lagi nanti.');
        }

        // Save ke JSON file master
        $masterFile = ROOTPATH . 'pengadaanpro_contractors_master.json';
        // Merge existing if present
        if (is_file($masterFile)) {
            $existing = json_decode(file_get_contents($masterFile), true);
            if (is_array($existing)) {
                foreach ($existing as $e) {
                    $eid = $e['id'] ?? $e['slug'] ?? '';
                    if ($eid && !isset($allContractors[$eid])) {
                        $allContractors[$eid] = $e;
                    }
                }
            }
        }
        file_put_contents($masterFile, json_encode(array_values($allContractors), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        // NTB detail juga simpan di file terpisah
        if ($mode === 'ntb') {
            $ntbFile = ROOTPATH . 'pengadaanpro_ntb.json';
            $ntbData = [];
            foreach ($allContractors as $c) {
                $ntbData[] = [
                    'slug' => $c['slug'] ?? '',
                    'nama_penyedia' => $c['contractor_name'] ?? '',
                    'npwp_penyedia' => $c['npwp'] ?? '',
                    'nib' => $c['nib'] ?? '',
                    'alamat_penyedia' => $c['address'] ?? '',
                    'domisili_kota' => $c['city'] ?? '',
                    'provinsi' => $c['province'] ?? 'Nusa Tenggara Barat',
                    'telepon' => $c['phone'] ?? '',
                    'email' => $c['email'] ?? '',
                    'direktur' => $c['direktur'] ?? '',
                    'total_award_value' => $c['total_award_value'] ?? 0,
                    'award_count' => $c['award_count'] ?? 0,
                ];
            }
            file_put_contents($ntbFile, json_encode($ntbData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }

        // Import ke database
        $db = \Config\Database::connect();
        helper('ajip');

        $normalizeName = function($name) {
            $s = strtoupper(trim($name));
            $s = preg_replace('/^(PT|CV|UD|PO|KOPERASI|KOP|TOKO)\.?\s*/i', '', $s);
            $s = preg_replace('/[,.\s]+(PT|CV|UD|PO|KOPERASI|TBK|PERSERO)\.?$/i', '', $s);
            return strtolower(preg_replace('/[^A-Z0-9]/', '', $s));
        };

        // Ensure table exists
        if (!$db->tableExists('pengadaanpro_contractors')) {
            $db->query("CREATE TABLE IF NOT EXISTS `pengadaanpro_contractors` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `remote_id` VARCHAR(64) UNIQUE,
                `slug` VARCHAR(255) NULL,
                `contractor_name` VARCHAR(255) NOT NULL,
                `contractor_name_normalized` VARCHAR(255) NULL,
                `npwp` VARCHAR(35) NULL,
                `nib` VARCHAR(35) NULL,
                `kpp_code` VARCHAR(20) NULL,
                `domisili_kota` VARCHAR(100) NULL,
                `province` VARCHAR(100) NULL,
                `award_count` INT DEFAULT 0,
                `total_award_value` BIGINT DEFAULT 0,
                `last_award_date` INT NULL,
                `address` TEXT NULL,
                `phone` VARCHAR(100) NULL,
                `email` VARCHAR(150) NULL,
                `direktur` VARCHAR(255) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_name_norm (`contractor_name_normalized`),
                INDEX idx_npwp (`npwp`),
                INDEX idx_prov (`province`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }

        $saved = 0;
        $enriched = 0;

        foreach ($allContractors as $c) {
            $name = $c['contractor_name'] ?? '';
            if (empty($name)) continue;
            $norm = $normalizeName($name);
            $npwp = preg_replace('/\D/', '', $c['npwp'] ?? '');
            $province = $c['province'] ?? '';
            $address = $c['address'] ?? null;
            $phone = $c['phone'] ?? null;
            $email = $c['email'] ?? null;
            $direktur = $c['direktur'] ?? null;
            $domisili = $c['city'] ?? '';

            $klas = classify_npwp_location($npwp, $address ?: $province, $name);
            if (empty($domisili) || $domisili === '-') {
                $domisili = $klas['domisili'] ?? '';
                if ($domisili === '-' || empty($domisili)) $domisili = $province;
            }
            $kpp = substr($klas['kpp_code'] ?? '', 0, 20);

            try {
                $db->query("INSERT INTO `pengadaanpro_contractors` 
                    (remote_id, slug, contractor_name, contractor_name_normalized, npwp, nib, kpp_code, domisili_kota, province, award_count, total_award_value, last_award_date, address, phone, email, direktur)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        slug = VALUES(slug),
                        contractor_name = VALUES(contractor_name),
                        contractor_name_normalized = VALUES(contractor_name_normalized),
                        npwp = IF(VALUES(npwp) != '' AND VALUES(npwp) IS NOT NULL, VALUES(npwp), npwp),
                        nib = IF(VALUES(nib) != '' AND VALUES(nib) IS NOT NULL, VALUES(nib), nib),
                        kpp_code = IF(VALUES(kpp_code) != '' AND VALUES(kpp_code) IS NOT NULL, VALUES(kpp_code), kpp_code),
                        domisili_kota = IF(VALUES(domisili_kota) != '' AND VALUES(domisili_kota) IS NOT NULL, VALUES(domisili_kota), domisili_kota),
                        province = VALUES(province),
                        award_count = VALUES(award_count),
                        total_award_value = VALUES(total_award_value),
                        address = IF(VALUES(address) != '' AND VALUES(address) IS NOT NULL, VALUES(address), address),
                        phone = IF(VALUES(phone) != '' AND VALUES(phone) IS NOT NULL, VALUES(phone), phone),
                        email = IF(VALUES(email) != '' AND VALUES(email) IS NOT NULL, VALUES(email), email),
                        direktur = IF(VALUES(direktur) != '' AND VALUES(direktur) IS NOT NULL, VALUES(direktur), direktur),
                        updated_at = CURRENT_TIMESTAMP",
                    [
                        $c['id'] ?? ('gen_' . $norm),
                        $c['slug'] ?? '',
                        $name, $norm, $npwp,
                        $c['nib'] ?? '',
                        $kpp, $domisili, $province,
                        (int)($c['award_count'] ?? 0),
                        (int)($c['total_award_value'] ?? 0),
                        !empty($c['last_award_date']) ? (int)$c['last_award_date'] : null,
                        $address, $phone, $email, $direktur
                    ]);
                $saved++;
            } catch (\Throwable $e) {
                log_message('error', 'Scrape insert error: ' . $e->getMessage());
            }
        }

        // Sinkronisasi ke inaproc_vendor_sync jika diminta
        if ($syncInaproc && $db->tableExists('inaproc_vendor_sync')) {
            $vendorRows = $db->query("SELECT id, nama_penyedia FROM inaproc_vendor_sync")->getResultArray();
            foreach ($vendorRows as $vr) {
                $norm = $normalizeName($vr['nama_penyedia']);
                $match = $db->query("SELECT * FROM pengadaanpro_contractors WHERE contractor_name_normalized = ? LIMIT 1", [$norm])->getFirstRow('array');
                if ($match) {
                    $db->query("UPDATE inaproc_vendor_sync SET
                        npwp_penyedia = IF((npwp_penyedia IS NULL OR npwp_penyedia = '' OR npwp_penyedia LIKE '%*%'), ?, npwp_penyedia),
                        kpp_code = IF((kpp_code IS NULL OR kpp_code = ''), ?, kpp_code),
                        lokasi_perusahaan = IF((lokasi_perusahaan IS NULL OR lokasi_perusahaan = '' OR lokasi_perusahaan = 'Luar Daerah'), ?, lokasi_perusahaan),
                        alamat_penyedia = IF((alamat_penyedia IS NULL OR alamat_penyedia = '' OR alamat_penyedia LIKE '%*%'), ?, alamat_penyedia),
                        telepon = IF((telepon IS NULL OR telepon = '' OR telepon LIKE '%*%'), ?, telepon),
                        email = IF((email IS NULL OR email = '' OR email LIKE '%*%'), ?, email),
                        nib = IF((nib IS NULL OR nib = '' OR nib LIKE '%*%'), ?, nib),
                        updated_at = NOW()
                        WHERE id = ?",
                        [
                            $match['npwp'], $match['kpp_code'], $match['domisili_kota'],
                            $match['address'], $match['phone'], $match['email'], $match['nib'],
                            $vr['id']
                        ]);
                    $enriched++;
                }
            }
        }

        $modeLabel = $mode === 'ntb' ? 'NTB (Detail Lengkap)' : 'Nasional';
        $msg = "Scrape {$modeLabel} berhasil! {$saved} kontraktor disimpan/diperbarui.";
        if ($enriched > 0) {
            $msg .= " {$enriched} vendor lokal diperkaya dengan data PengadaanPro.";
        }
        return redirect()->to(base_url('dashboard/scrape-perusahaan'))->with('success', $msg);
    }

    /**
     * Helper: cURL GET JSON dari API
     */
    private function curlGetJson(string $url, array $headers = []): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 200 || !$resp) return null;
        return json_decode($resp, true);
    }

    /**
     * Helper: cURL GET HTML content
     */
    private function curlGetHtml(string $url, array $headers = []): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 200 || !$resp) return null;
        return $resp;
    }

    public function doSyncSikapBot()
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to(base_url('dashboard'))->with('error', 'Hanya administrator yang memiliki akses untuk sinkronisasi bot SIKaP.');
        }

        set_time_limit(300);
        $targetFile = ROOTPATH . 'Hasil_Bot_SIKaP.csv';
        $uploaded = false;

        $file = $this->request->getFile('file_csv');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $uploadErr = '';
            if (!is_secure_uploaded_file($file, ['csv'], 20971520, $uploadErr)) {
                return redirect()->to(base_url('dashboard/scrape-perusahaan'))->with('error', 'File CSV tidak valid: ' . $uploadErr);
            }
            if (is_file($targetFile)) {
                @copy($targetFile, ROOTPATH . 'Hasil_Bot_SIKaP_backup_' . date('Ymd_His') . '.csv');
            }
            $file->move(ROOTPATH, 'Hasil_Bot_SIKaP.csv', true);
            $uploaded = true;
        }

        if (!is_file($targetFile)) {
            $targetFile = FCPATH . 'Hasil_Bot_SIKaP.csv';
        }
        if (!is_file($targetFile)) {
            return redirect()->to(base_url('dashboard/scrape-perusahaan'))->with('error', 'File Hasil_Bot_SIKaP.csv tidak ditemukan di server.');
        }

        // 1. Jalankan SyncService ke inaproc_vendor_sync & LPSE transaksi
        $syncService = new \App\Services\SyncService();
        $res = $syncService->syncSikapCsvVendorDetails();

        // 2. Perkaya juga tabel `pengadaanpro_contractors` dari Hasil_Bot_SIKaP.csv
        $db = \Config\Database::connect();
        helper('ajip');

        $normalizeName = function($str) {
            $s = strtoupper(trim($str));
            $s = preg_replace('/^(PT|CV|UD|PO|KOPERASI|KOP|TOKO)\.?\s*/i', '', $s);
            $s = preg_replace('/[,.\s]+(PT|CV|UD|PO|KOPERASI|TBK|PERSERO)\.?$/i', '', $s);
            return strtolower(preg_replace('/[^A-Z0-9]/', '', $s));
        };

        $sikapToContractorsCount = 0;
        if ($db->tableExists('pengadaanpro_contractors') && ($h = fopen($targetFile, 'r')) !== false) {
            $header = fgetcsv($h, 0, ';');
            $hIdx = [];
            if ($header) {
                foreach ($header as $i => $col) {
                    $c = strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', $col)));
                    $hIdx[$c] = $i;
                }
            }

            while (($row = fgetcsv($h, 0, ';')) !== false) {
                if (count($row) < 2) continue;

                $getCol = function($name) use ($hIdx, $row) {
                    return isset($hIdx[$name]) && isset($row[$hIdx[$name]]) ? trim($row[$hIdx[$name]]) : '';
                };

                $nama = $getCol('nama perusahaan / kontraktor') ?: ($getCol('nama') ?: ($getCol('nama_penyedia') ?: ($getCol('nama badan usaha') ?: ($row[1] ?? ''))));
                $npwp = $getCol('npwp') ?: ($row[2] ?? '');
                if (empty($nama) && empty($npwp)) continue;

                $cleanNpwp = preg_replace('/\D/', '', $npwp);
                $norm = $normalizeName($nama);
                $alamat = $getCol('alamat');
                $kab = $getCol('kabupaten / kota') ?: ($getCol('kabupaten_kota') ?: ($getCol('kabupaten/kota') ?: ($row[3] ?? '')));
                $prov = $getCol('provinsi') ?: ($row[4] ?? 'Nusa Tenggara Barat');
                $detailUrl = $getCol('detail sbu url') ?: ($getCol('detail_url') ?: ($row[5] ?? ''));
                $email = $getCol('email');
                $telepon = $getCol('telepon') ?: ($getCol('telepon_seluler') ?: $getCol('no telepon'));
                $userId = $getCol('user_id') ?: ($getCol('user id') ?: ($row[0] ?? ''));

                if (empty($alamat) && !empty($kab)) {
                    $alamat = $kab . (!empty($prov) ? ', ' . $prov : '');
                }

                // Cek apakah ada data NPWP lengkap di lpse_tender jika NPWP masked
                if ((empty($cleanNpwp) || strlen($cleanNpwp) < 15) && !empty($nama)) {
                    $tMatch = $db->table('lpse_tender')
                        ->select('npwp_penyedia, bentuk_usaha')
                        ->where('UPPER(TRIM(nama_penyedia))', strtoupper(trim($nama)))
                        ->where('npwp_penyedia IS NOT NULL AND TRIM(npwp_penyedia) != ""')
                        ->limit(1)->get()->getRowArray();
                    if ($tMatch && !empty($tMatch['npwp_penyedia'])) {
                        $cleanNpwp = preg_replace('/\D/', '', $tMatch['npwp_penyedia']);
                    }
                }

                // Klasifikasi domisili & KPP (nama kota/kabupaten murni tanpa label Flag)
                $klas = classify_npwp_location($cleanNpwp, $alamat, $nama);
                $domisili = $klas['domisili'] ?? '';
                if (empty($domisili) || $domisili === '-') {
                    $domisili = !empty($kab) ? preg_replace('/\s*\(kab\.?\)|\s*\(kota\)/i', '', $kab) : $prov;
                }
                $kpp = substr($klas['kpp_code'] ?? '', 0, 20);
                $remoteId = 'sikap_' . ($userId ?: $norm);

                try {
                    $db->query("INSERT INTO `pengadaanpro_contractors`
                        (remote_id, contractor_name, contractor_name_normalized, npwp, kpp_code, domisili_kota, province, address, phone, email, detail_url)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                            contractor_name = VALUES(contractor_name),
                            contractor_name_normalized = VALUES(contractor_name_normalized),
                            npwp = IF(VALUES(npwp) != '' AND VALUES(npwp) IS NOT NULL, VALUES(npwp), npwp),
                            kpp_code = IF(VALUES(kpp_code) != '' AND VALUES(kpp_code) IS NOT NULL, VALUES(kpp_code), kpp_code),
                            domisili_kota = IF(VALUES(domisili_kota) != '' AND VALUES(domisili_kota) IS NOT NULL, VALUES(domisili_kota), domisili_kota),
                            province = IF(VALUES(province) != '' AND VALUES(province) IS NOT NULL, VALUES(province), province),
                            address = IF(VALUES(address) != '' AND VALUES(address) IS NOT NULL, VALUES(address), address),
                            phone = IF(VALUES(phone) != '' AND VALUES(phone) IS NOT NULL, VALUES(phone), phone),
                            email = IF(VALUES(email) != '' AND VALUES(email) IS NOT NULL, VALUES(email), email),
                            detail_url = IF(VALUES(detail_url) != '' AND VALUES(detail_url) IS NOT NULL, VALUES(detail_url), detail_url),
                            updated_at = CURRENT_TIMESTAMP",
                        [
                            $remoteId, $nama, $norm, $cleanNpwp, $kpp, $domisili, $prov,
                            $alamat ?: null,
                            ($telepon && $telepon !== '-') ? $telepon : null,
                            ($email && $email !== '-') ? $email : null,
                            $detailUrl ?: null
                        ]);
                    $sikapToContractorsCount++;
                } catch (\Throwable $e) {
                    // Ignore duplicate key or DB constraint errors
                }
            }
            fclose($h);
        }

        $msg = "Sinkronisasi bot SIKaP berhasil! {$res['updated']} data penyedia diperbarui, {$res['inserted']} data baru ditambahkan, {$res['enriched_transactions']} transaksi LPSE diperkaya, dan {$sikapToContractorsCount} profil referensi SIKaP diintegrasikan ke database kontraktor.";
        if ($uploaded) {
            $msg = "File CSV baru berhasil diunggah. " . $msg;
        }

        return redirect()->to(base_url('dashboard/scrape-perusahaan'))->with('success', $msg);
    }

    /**
     * Luncurkan Bot LPJK (Selenium) di background server lokal Windows
     */
    public function doLaunchBotLpjk()
    {
        if (session()->get('role') !== 'admin') {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Hanya administrator yang dapat meluncurkan Bot LPJK.'
            ])->setStatusCode(403);
        }

        $scriptPath = ROOTPATH . 'bot_lpjk_selenium.py';
        if (!is_file($scriptPath)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'File bot_lpjk_selenium.py tidak ditemukan di direktori sistem.'
            ]);
        }

        $logDir = WRITEPATH . 'logs/';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }

        $statusFile = $logDir . 'bot_lpjk_status.json';
        $logFile = $logDir . 'bot_lpjk.log';

        // Reset log file for fresh run
        file_put_contents($logFile, "[" . date('H:i:s') . "] Mempersiapkan peluncuran Bot LPJK...\n");
        file_put_contents($statusFile, json_encode([
            'status' => 'starting',
            'message' => 'Mempersiapkan peluncuran browser Chrome...',
            'count' => 0,
            'total' => 0,
            'updated_at' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE));

        // Detect Python executable
        $pythonCandidates = [
            'C:\\Users\\lobar\\AppData\\Local\\Python\\pythoncore-3.14-64\\python.exe',
            'python.exe',
            'python'
        ];
        $pythonExe = 'python';
        foreach ($pythonCandidates as $cand) {
            if (is_file($cand)) {
                $pythonExe = $cand;
                break;
            }
        }

        // Parameter CLI Bot LPJK
        $scope = $this->request->getPost('scope') === 'all' ? 'all' : 'ntb';
        $fetchDetailsParam = $this->request->getPost('fetch_details');
        $fetchDetails = ($fetchDetailsParam === '1' || $fetchDetailsParam === 'true' || $fetchDetailsParam === true || $fetchDetailsParam === null);
        $isDetailsOnly = ($this->request->getPost('details_only') === '1' || $this->request->getPost('mode') === 'details_only');
        
        $scopeArg = $scope === 'all' ? '--scope all' : '--scope ntb';
        $detailArg = $fetchDetails ? '--fetch-details' : '--no-fetch-details';
        $detailsOnlyArg = $isDetailsOnly ? '--details-only' : '';

        $batPath = ROOTPATH . 'run_bot_lpjk.bat';

        // Jalankan background process di Windows
        // Menggunakan start cmd /k agar jendela command prompt dan browser tetap terbuka tanpa tertutup otomatis
        $cmd = 'start "Bot LPJK PUPR" cmd /k ""' . $batPath . '" ' . $scopeArg . ' ' . $detailArg . ' ' . $detailsOnlyArg . '"';
        
        try {
            pclose(popen($cmd, "r"));
        } catch (\Throwable $e) {
            log_message('error', 'Gagal meluncurkan Bot LPJK: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Gagal mengeksekusi bot: ' . $e->getMessage()
            ]);
        }

        $modeLabel = $isDetailsOnly ? 'Ekstraksi Modal Detail Saja (Database)' : ($scope === 'all' ? 'Seluruh Indonesia' : 'Wilayah NTB');
        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Bot LPJK berhasil diluncurkan dengan mode ' . $modeLabel . ($fetchDetails ? ' + Ekstraksi Modal SBU Turbo' : '') . '! Browser Chrome akan segera terbuka di layar Anda.'
        ]);
    }

    /**
     * Ambil data lengkap profil LPJK (Legalitas, Pengurus, SBU) dari database
     */
    public function getContractorLpjkDetail($id)
    {
        if (session()->get('role') !== 'admin' && !has_permission('detail_penyedia')) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Akses ditolak.'
            ])->setStatusCode(403);
        }

        $db = \Config\Database::connect();
        $contractor = $db->table('pengadaanpro_contractors')->where('id', (int)$id)->get()->getRowArray();

        if (!$contractor) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Data kontraktor tidak ditemukan.'
            ])->setStatusCode(404);
        }

        $legalitas = !empty($contractor['legalitas_json']) ? json_decode($contractor['legalitas_json'], true) : [];
        $pengurus = !empty($contractor['pengurus_json']) ? json_decode($contractor['pengurus_json'], true) : [];
        $sbu = !empty($contractor['sbu_json']) ? json_decode($contractor['sbu_json'], true) : [];
        $pjsk = !empty($contractor['pjsk_json']) ? json_decode($contractor['pjsk_json'], true) : [];

        return $this->response->setJSON([
            'status' => 'success',
            'data' => [
                'id' => (int)$contractor['id'],
                'contractor_name' => $contractor['contractor_name'] ?? '-',
                'npwp' => $contractor['npwp'] ?? '',
                'nib' => $contractor['nib'] ?? null,
                'address' => $contractor['address'] ?? '',
                'domisili_kota' => $contractor['domisili_kota'] ?? '',
                'province' => $contractor['province'] ?? '',
                'phone' => $contractor['phone'] ?? '',
                'email' => $contractor['email'] ?? '',
                'direktur' => $contractor['direktur'] ?? '',
                'pjbu' => $contractor['pjbu'] ?? null,
                'pjtbu' => $contractor['pjtbu'] ?? null,
                'detail_url' => $contractor['detail_url'] ?? null,
                'detail_fetched_at' => $contractor['detail_fetched_at'] ?? null,
                'sbu_codes' => $contractor['sbu_codes'] ?? null,
                'remote_id' => $contractor['remote_id'] ?? '',
                'legalitas' => is_array($legalitas) ? $legalitas : [],
                'pengurus' => is_array($pengurus) ? $pengurus : [],
                'sbu' => is_array($sbu) ? $sbu : [],
                'pjsk' => is_array($pjsk) ? $pjsk : [],
                'updated_at' => $contractor['updated_at'] ?? null
            ]
        ]);
    }

    /**
     * Cek status dan log realtime Bot LPJK
     */
    public function getBotLpjkStatus()
    {
        $statusFile = WRITEPATH . 'logs/bot_lpjk_status.json';
        $logFile = WRITEPATH . 'logs/bot_lpjk.log';

        $statusData = [
            'status' => 'idle',
            'message' => 'Bot standby. Klik "Luncurkan Bot LPJK" untuk memulai.',
            'count' => 0,
            'total' => 0,
            'updated_at' => null
        ];

        if (is_file($statusFile)) {
            $json = json_decode(file_get_contents($statusFile), true);
            if (is_array($json)) {
                $statusData = array_merge($statusData, $json);
            }
        }

        // Ambil 50 baris terakhir log
        $logs = '';
        if (is_file($logFile)) {
            $lines = file($logFile);
            if ($lines !== false) {
                $lastLines = array_slice($lines, -50);
                $logs = implode("", $lastLines);
            }
        }

        $isRunning = in_array($statusData['status'], ['starting', 'running', 'waiting_captcha', 'extracting', 'extracting_details', 'saving']);

        return $this->response->setJSON([
            'status' => $statusData['status'],
            'message' => $statusData['message'],
            'count' => (int)($statusData['count'] ?? 0),
            'total' => (int)($statusData['total'] ?? 0),
            'updated_at' => $statusData['updated_at'],
            'is_running' => $isRunning,
            'logs' => $logs
        ]);
    }

    /**
     * Hentikan Bot LPJK jika sedang berjalan
     */
    public function stopBotLpjk()
    {
        if (session()->get('role') !== 'admin') {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Hanya administrator yang dapat menghentikan bot.'
            ])->setStatusCode(403);
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            @exec('taskkill /F /IM chromedriver.exe /T 2>&1');
        }

        $statusFile = WRITEPATH . 'logs/bot_lpjk_status.json';
        $logFile = WRITEPATH . 'logs/bot_lpjk.log';

        file_put_contents($statusFile, json_encode([
            'status' => 'stopped',
            'message' => 'Bot dihentikan oleh pengguna.',
            'count' => 0,
            'total' => 0,
            'updated_at' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE));

        if (is_file($logFile)) {
            @file_put_contents($logFile, "[" . date('H:i:s') . "] [!] Bot dihentikan secara paksa oleh pengguna melalui menu aplikasi.\n", FILE_APPEND);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Bot LPJK dan browser Chrome berhasil dihentikan.'
        ]);
    }

    /**
     * Luncurkan Bot SPSE Rekanan di background server lokal Windows
     */
    public function doLaunchBotSpse()
    {
        $role = session()->get('role');
        if ($role !== 'admin' && $role !== 'operator' && !has_permission('detail_penyedia')) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk meluncurkan Bot SPSE.'
            ])->setStatusCode(403);
        }

        $batPath = ROOTPATH . 'run_bot_spse_rekanan.bat';
        if (!is_file($batPath)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'File run_bot_spse_rekanan.bat tidak ditemukan di direktori sistem.'
            ]);
        }

        $mode = trim($this->request->getPost('mode') ?? 'enrich');
        if (!in_array($mode, ['enrich', 'scrape'])) {
            $mode = 'enrich';
        }

        $logDir = WRITEPATH . 'logs/';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }

        $statusFile = $logDir . 'bot_spse_status.json';
        $logFile = $logDir . 'bot_spse.log';

        $modeLabel = ($mode === 'enrich') ? 'Lengkapi Detail & Domisili (Kabupaten/Kota)' : 'Scraping Daftar Rekanan Baru';

        // Reset log file for fresh run
        file_put_contents($logFile, "[" . date('H:i:s') . "] Mempersiapkan Bot SPSE (Mode: {$modeLabel})...\n");
        file_put_contents($statusFile, json_encode([
            'status' => 'starting',
            'message' => "Mempersiapkan browser Google Chrome untuk {$modeLabel}...",
            'count' => 0,
            'total' => 0,
            'mode' => $mode,
            'updated_at' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE));

        // Jalankan background process di Windows dengan parameter --mode
        $cleanMode = in_array($mode, ['enrich', 'scrape']) ? $mode : 'enrich';
        $cmd = 'cmd /c start "" "' . $batPath . '" --mode=' . $cleanMode;
        try {
            pclose(popen($cmd, "r"));
        } catch (\Throwable $e) {
            log_message('error', 'Gagal meluncurkan Bot SPSE: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Gagal mengeksekusi bot: ' . $e->getMessage()
            ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => "Bot SPSE ({$modeLabel}) berhasil diluncurkan! Browser Chrome akan segera terbuka di layar Anda untuk login."
        ]);
    }

    /**
     * Cek status dan log realtime Bot SPSE
     */
    public function getBotSpseStatus()
    {
        $statusFile = WRITEPATH . 'logs/bot_spse_status.json';
        $logFile = WRITEPATH . 'logs/bot_spse.log';

        $statusData = [
            'status' => 'idle',
            'message' => 'Bot standby. Siap dijalankan.',
            'count' => 0,
            'total' => 0,
            'mode' => 'enrich',
            'updated_at' => null
        ];

        if (is_file($statusFile)) {
            $json = json_decode(file_get_contents($statusFile), true);
            if (is_array($json)) {
                $statusData = array_merge($statusData, $json);
            }
        }

        $logs = '';
        if (is_file($logFile)) {
            $lines = file($logFile);
            if ($lines !== false) {
                $lastLines = array_slice($lines, -60);
                $logs = implode("", $lastLines);
            }
        }

        $isRunning = in_array($statusData['status'], ['starting', 'waiting_login', 'scraping', 'saving', 'enriching']);

        // Database stats
        $db = \Config\Database::connect();
        $stats = [
            'total_spse' => 0,
            'domisili_filled' => 0,
            'domisili_pending' => 0
        ];
        if ($db->tableExists('pengadaanpro_contractors')) {
            $statRow = $db->query("
                SELECT 
                    COUNT(*) as total_spse,
                    SUM(CASE WHEN domisili_kota IS NOT NULL AND domisili_kota != '' THEN 1 ELSE 0 END) as domisili_filled,
                    SUM(CASE WHEN domisili_kota IS NULL OR domisili_kota = '' THEN 1 ELSE 0 END) as domisili_pending
                FROM pengadaanpro_contractors 
                WHERE remote_id LIKE 'spse_%'
            ")->getRowArray();
            if ($statRow) {
                $stats['total_spse'] = (int)($statRow['total_spse'] ?? 0);
                $stats['domisili_filled'] = (int)($statRow['domisili_filled'] ?? 0);
                $stats['domisili_pending'] = (int)($statRow['domisili_pending'] ?? 0);
            }
        }

        return $this->response->setJSON([
            'status' => $statusData['status'],
            'message' => $statusData['message'],
            'count' => (int)($statusData['count'] ?? 0),
            'total' => (int)($statusData['total'] ?? 0),
            'mode' => $statusData['mode'] ?? 'enrich',
            'updated_at' => $statusData['updated_at'],
            'is_running' => $isRunning,
            'logs' => $logs,
            'stats' => $stats
        ]);
    }

    /**
     * Hentikan Bot SPSE jika sedang berjalan
     */
    public function stopBotSpse()
    {
        $role = session()->get('role');
        if ($role !== 'admin' && $role !== 'operator' && !has_permission('detail_penyedia')) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk menghentikan bot.'
            ])->setStatusCode(403);
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            @exec('taskkill /F /IM chromedriver.exe /T 2>&1');
        }

        $statusFile = WRITEPATH . 'logs/bot_spse_status.json';
        $logFile = WRITEPATH . 'logs/bot_spse.log';

        file_put_contents($statusFile, json_encode([
            'status' => 'stopped',
            'message' => 'Bot dihentikan oleh pengguna.',
            'count' => 0,
            'total' => 0,
            'updated_at' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE));

        if (is_file($logFile)) {
            @file_put_contents($logFile, "[" . date('H:i:s') . "] [!] Bot SPSE dihentikan oleh pengguna melalui menu aplikasi.\n", FILE_APPEND);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Bot SPSE dan browser Chrome berhasil dihentikan.'
        ]);
    }

    public function scrapePerusahaanStatus()
    {
        return $this->getBotLpjkStatus();
    }

    /**
     * DataTables Server-Side Endpoint for Contractors Database
     */
    public function getScrapeContractorsDatatables()
    {
        if (session()->get('role') !== 'admin' && !has_permission('detail_penyedia')) {
            return $this->response->setJSON([
                'draw' => intval($this->request->getGet('draw') ?? 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Akses ditolak.'
            ])->setStatusCode(403);
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists('pengadaanpro_contractors')) {
            return $this->response->setJSON([
                'draw' => intval($this->request->getGet('draw') ?? 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ]);
        }

        $draw = (int) ($this->request->getGet('draw') ?? 1);
        $start = (int) ($this->request->getGet('start') ?? 0);
        $length = (int) ($this->request->getGet('length') ?? 25);
        $searchParam = $this->request->getGet('search');
        $searchValue = is_array($searchParam) ? trim($searchParam['value'] ?? '') : '';
        $sourceFilter = trim($this->request->getGet('source') ?? '');
        $orderParam = $this->request->getGet('order') ?? [];

        $totalRecords = $db->table('pengadaanpro_contractors')->countAllResults();

        $builder = $db->table('pengadaanpro_contractors');

        // Filter source
        if ($sourceFilter === 'spse') {
            $builder->like('remote_id', 'spse_', 'after');
        } elseif ($sourceFilter === 'sikap') {
            $builder->like('remote_id', 'sikap_', 'after');
        } elseif ($sourceFilter === 'lpjk') {
            $builder->like('remote_id', 'lpjk_', 'after');
        } elseif ($sourceFilter === 'pengadaanpro') {
            $builder->groupStart()
                    ->notLike('remote_id', 'spse_', 'after')
                    ->notLike('remote_id', 'sikap_', 'after')
                    ->notLike('remote_id', 'lpjk_', 'after')
                    ->orWhere('remote_id IS NULL')
                    ->groupEnd();
        }

        // Filter search keyword + filter lanjutan
        $this->_applyContractorFilters($builder, $searchValue);

        $filteredRecords = $builder->countAllResults(false);

        // Column ordering mapping
        $columns = [
            0 => 'id',
            1 => 'contractor_name',
            2 => 'npwp',
            3 => 'domisili_kota',
            4 => 'province',
            5 => 'address',
            6 => 'phone',
            7 => 'remote_id',
            8 => 'updated_at'
        ];

        if (!empty($orderParam) && isset($orderParam[0]['column']) && isset($columns[$orderParam[0]['column']])) {
            $colName = $columns[$orderParam[0]['column']];
            $colDir = strtolower($orderParam[0]['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
            $builder->orderBy($colName, $colDir);
        } else {
            $builder->orderBy('updated_at', 'DESC');
        }

        if ($length > 0) {
            $builder->limit($length, $start);
        }

        $rows = $builder->get()->getResultArray();

        $data = [];
        $no = $start + 1;
        foreach ($rows as $row) {
            $isSpse = (!empty($row['remote_id']) && strpos($row['remote_id'], 'spse_') === 0);
            $isSikap = (!empty($row['remote_id']) && strpos($row['remote_id'], 'sikap_') === 0);
            $isLpjk = (!empty($row['remote_id']) && strpos($row['remote_id'], 'lpjk_') === 0);

            if ($isSpse) {
                $sourceBadge = '<span class="badge bg-label-success small"><i class="bx bx-check-shield me-1"></i>SPSE Inaproc</span>';
                if (!empty($row['terdaftar_di'])) {
                    $sourceBadge .= '<small class="text-muted d-block mt-1 font-monospace" style="font-size:10px;">' . esc($row['terdaftar_di']) . '</small>';
                }
            } elseif ($isLpjk) {
                $sourceBadge = '<span class="badge bg-label-warning small"><i class="bx bx-building me-1"></i>LPJK PUPR</span>';
            } elseif ($isSikap) {
                $sourceBadge = '<span class="badge bg-label-info small"><i class="bx bx-bot me-1"></i>Bot SIKaP</span>';
            } else {
                $sourceBadge = '<span class="badge bg-label-primary small"><i class="bx bx-globe me-1"></i>PengadaanPro</span>';
            }

            $domisiliBadge = !empty($row['domisili_kota'])
                ? '<span class="badge bg-label-success fw-semibold small"><i class="bx bx-map-pin me-1"></i>' . esc($row['domisili_kota']) . '</span>'
                : '<span class="badge bg-label-secondary fw-normal text-muted small"><i class="bx bx-time-five me-1"></i>Belum terdata</span>';

            $contacts = [];
            if (!empty($row['phone'])) {
                $contacts[] = '<div><i class="bx bx-phone small text-muted me-1"></i><span class="font-monospace small">' . esc($row['phone']) . '</span></div>';
            }
            if (!empty($row['email'])) {
                $contacts[] = '<div class="text-truncate" style="max-width: 160px;" title="' . esc($row['email']) . '"><i class="bx bx-envelope small text-muted me-1"></i><span class="small">' . esc($row['email']) . '</span></div>';
            }
            $contactHtml = !empty($contacts) ? implode('', $contacts) : '<span class="text-muted small">-</span>';

            $addressHtml = !empty($row['address'])
                ? '<div class="small text-secondary" style="max-width: 280px; line-height: 1.35;">' . esc($row['address']) . '</div>'
                : '<span class="text-muted small">-</span>';

            $updatedAt = !empty($row['updated_at']) ? date('d/m/y H:i', strtotime($row['updated_at'])) : '-';

            $bentukHtml = !empty($row['bentuk_usaha']) ? '<small class="text-muted d-block font-monospace" style="font-size:10.5px;">' . esc($row['bentuk_usaha']) . '</small>' : '';
            $direkturHtml = !empty($row['direktur']) ? '<small class="text-muted d-block">Pimp: ' . esc($row['direktur']) . '</small>' : '';

            $data[] = [
                'no' => $no++,
                'contractor_name' => '<div class="fw-bold text-dark text-wrap" style="max-width: 250px;">' . esc($row['contractor_name'] ?? '-') . '</div>' . $bentukHtml . $direkturHtml,
                'npwp' => !empty($row['npwp']) ? '<span class="font-monospace fw-bold text-primary small bg-label-primary px-2 py-1 rounded d-inline-block">' . esc($row['npwp']) . '</span>' : '<span class="text-muted small">-</span>',
                'domisili_kota' => $domisiliBadge,
                'province' => '<span class="small fw-semibold text-secondary">' . esc($row['province'] ?? '-') . '</span>',
                'address' => $addressHtml,
                'contact' => $contactHtml,
                'source' => $sourceBadge,
                'updated_at' => '<span class="text-muted small font-monospace">' . $updatedAt . '</span>',
            ];
        }

        // Opsi dropdown filter (dari data SPSE)
        $optProv = [];
        $optKota = [];
        $optBentuk = [];
        try {
            foreach ($db->query("SELECT DISTINCT province FROM pengadaanpro_contractors WHERE remote_id LIKE 'spse\\_%' AND province IS NOT NULL AND province != '' ORDER BY province")->getResultArray() as $o) $optProv[] = $o['province'];
            foreach ($db->query("SELECT DISTINCT domisili_kota FROM pengadaanpro_contractors WHERE remote_id LIKE 'spse\\_%' AND domisili_kota IS NOT NULL AND domisili_kota != '' ORDER BY domisili_kota")->getResultArray() as $o) $optKota[] = $o['domisili_kota'];
            foreach ($db->query("SELECT DISTINCT bentuk_usaha FROM pengadaanpro_contractors WHERE remote_id LIKE 'spse\\_%' AND bentuk_usaha IS NOT NULL AND bentuk_usaha != '' ORDER BY bentuk_usaha")->getResultArray() as $o) $optBentuk[] = $o['bentuk_usaha'];
        } catch (\Throwable $e) {
            log_message('error', 'Error loading filter options: ' . $e->getMessage());
        }

        return $this->response->setJSON([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
            'filter_options' => ['province' => $optProv, 'kota' => $optKota, 'bentuk' => $optBentuk]
        ]);
    }

    /**
     * Terapkan pencarian & filter lanjutan (provinsi, kota, wilayah, kelengkapan data, tanggal update)
     */
    private function _applyContractorFilters($builder, $search)
    {
        $req = $this->request;

        if ($search !== '') {
            $builder->groupStart()
                    ->like('contractor_name', $search)
                    ->orLike('npwp', $search)
                    ->orLike('nib', $search)
                    ->orLike('domisili_kota', $search)
                    ->orLike('province', $search)
                    ->orLike('address', $search)
                    ->orLike('phone', $search)
                    ->orLike('email', $search)
                    ->orLike('direktur', $search)
                    ->orLike('pjbu', $search)
                    ->orLike('sbu_codes', $search)
                    ->orLike('bentuk_usaha', $search)
                    ->groupEnd();
        }

        $prov = trim($req->getGet('f_province') ?? '');
        if ($prov !== '') $builder->where('province', $prov);

        $kota = trim($req->getGet('f_kota') ?? '');
        if ($kota !== '') $builder->where('domisili_kota', $kota);

        $bentuk = trim($req->getGet('f_bentuk') ?? '');
        if ($bentuk !== '') $builder->where('bentuk_usaha', $bentuk);

        $wil = trim($req->getGet('f_wilayah') ?? '');
        if ($wil === 'lobar') {
            $builder->like('domisili_kota', 'Lombok Barat');
        } elseif ($wil === 'mataram') {
            $builder->like('domisili_kota', 'Mataram');
        } elseif ($wil === 'ntb' || $wil === 'ntb_lain') {
            $builder->groupStart()->like('province', 'Nusa Tenggara Barat')->orLike('province', 'NTB')->groupEnd();
            if ($wil === 'ntb_lain') {
                $builder->notLike('domisili_kota', 'Lombok Barat')->notLike('domisili_kota', 'Mataram');
            }
        } elseif ($wil === 'luar') {
            $builder->where("province IS NOT NULL AND province != '' AND province NOT LIKE '%Nusa Tenggara Barat%' AND province NOT LIKE '%NTB%'", null, false);
        }

        $alamat = trim($req->getGet('f_alamat') ?? '');
        if ($alamat === 'ada') $builder->where("address IS NOT NULL AND address != ''", null, false);
        elseif ($alamat === 'kosong') $builder->where("(address IS NULL OR address = '')", null, false);

        $dom = trim($req->getGet('f_domisili') ?? '');
        if ($dom === 'terdata') $builder->where("domisili_kota IS NOT NULL AND domisili_kota != ''", null, false);
        elseif ($dom === 'belum') $builder->where("(domisili_kota IS NULL OR domisili_kota = '')", null, false);

        $kontak = trim($req->getGet('f_kontak') ?? '');
        if ($kontak === 'ada') $builder->where("((phone IS NOT NULL AND phone != '') OR (email IS NOT NULL AND email != ''))", null, false);
        elseif ($kontak === 'kosong') $builder->where("((phone IS NULL OR phone = '') AND (email IS NULL OR email = ''))", null, false);

        $npwp = trim($req->getGet('f_npwp') ?? '');
        if ($npwp === 'ada') $builder->where("npwp IS NOT NULL AND npwp != ''", null, false);
        elseif ($npwp === 'kosong') $builder->where("(npwp IS NULL OR npwp = '')", null, false);

        $dFrom = trim($req->getGet('f_date_from') ?? '');
        if ($dFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dFrom)) $builder->where('updated_at >=', $dFrom . ' 00:00:00');
        $dTo = trim($req->getGet('f_date_to') ?? '');
        if ($dTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dTo)) $builder->where('updated_at <=', $dTo . ' 23:59:59');
    }

    /**
     * Export Contractors Database to Excel / CSV
     */
    public function exportContractors()
    {
        if (session()->get('role') !== 'admin' && !has_permission('detail_penyedia')) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Akses ditolak.');
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists('pengadaanpro_contractors')) {
            return redirect()->back()->with('error', 'Tabel kontraktor belum tersedia.');
        }

        $format = strtolower($this->request->getGet('format') ?? 'xlsx');
        $sourceFilter = trim($this->request->getGet('source') ?? '');
        $search = trim($this->request->getGet('search') ?? '');

        $builder = $db->table('pengadaanpro_contractors')
            ->select('contractor_name, npwp, nib, domisili_kota, province, address, phone, email, direktur, remote_id, updated_at');

        if ($sourceFilter === 'spse') {
            $builder->like('remote_id', 'spse_', 'after');
        } elseif ($sourceFilter === 'sikap') {
            $builder->like('remote_id', 'sikap_', 'after');
        } elseif ($sourceFilter === 'pengadaanpro') {
            $builder->groupStart()
                    ->notLike('remote_id', 'sikap_', 'after')
                    ->orWhere('remote_id IS NULL')
                    ->groupEnd();
        }

        $this->_applyContractorFilters($builder, $search);

        $rows = $builder->orderBy('updated_at', 'DESC')->get()->getResultArray();
        $timestamp = date('Ymd_His');

        if ($format === 'csv') {
            $filename = 'Database_Kontraktor_Referensi_' . $timestamp . '.csv';
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');
            // Write UTF-8 BOM so Excel opens it with correct character encoding
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($output, [
                'No',
                'Nama Perusahaan',
                'NPWP',
                'NIB',
                'Domisili Kota/Kabupaten',
                'Provinsi',
                'Alamat Lengkap',
                'Nomor Telepon',
                'Email',
                'Direktur / Pimpinan',
                'Sumber Data',
                'Terakhir Diperbarui'
            ], ';');

            $no = 1;
            foreach ($rows as $r) {
                $isSikap = (!empty($r['remote_id']) && strpos($r['remote_id'], 'sikap_') === 0);
                $sumber = $isSikap ? 'Bot SIKaP LKPP' : 'PengadaanPro.com';

                fputcsv($output, [
                    $no++,
                    $r['contractor_name'] ?? '',
                    $r['npwp'] ?? '',
                    $r['nib'] ?? '',
                    $r['domisili_kota'] ?? '',
                    $r['province'] ?? '',
                    $r['address'] ?? '',
                    $r['phone'] ?? '',
                    $r['email'] ?? '',
                    $r['direktur'] ?? '',
                    $sumber,
                    $r['updated_at'] ?? ''
                ], ';');
            }
            fclose($output);
            exit;
        }

        // Default: Export Excel (.xlsx) using PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Database Kontraktor');

        // Headers
        $headers = [
            'A1' => 'No',
            'B1' => 'Nama Perusahaan / Kontraktor',
            'C1' => 'NPWP',
            'D1' => 'NIB',
            'E1' => 'Domisili (Kota/Kab)',
            'F1' => 'Provinsi',
            'G1' => 'Alamat Lengkap',
            'H1' => 'No Telepon',
            'I1' => 'Email',
            'J1' => 'Direktur / Pimpinan',
            'K1' => 'Sumber Data',
            'L1' => 'Terakhir Update'
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        // Header Styling
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'fill' => ['type' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['argb' => 'FF1E2538']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ];
        $sheet->getStyle('A1:L1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $rowNum = 2;
        $no = 1;
        foreach ($rows as $r) {
            $isSikap = (!empty($r['remote_id']) && strpos($r['remote_id'], 'sikap_') === 0);
            $sumber = $isSikap ? 'Bot SIKaP LKPP' : 'PengadaanPro.com';

            $sheet->setCellValue('A' . $rowNum, $no++);
            $sheet->setCellValue('B' . $rowNum, $r['contractor_name'] ?? '-');

            // Force NPWP and NIB as string so leading zeros are preserved
            $sheet->setCellValueExplicit('C' . $rowNum, $r['npwp'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $rowNum, $r['nib'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

            $sheet->setCellValue('E' . $rowNum, $r['domisili_kota'] ?? '-');
            $sheet->setCellValue('F' . $rowNum, $r['province'] ?? '-');
            $sheet->setCellValue('G' . $rowNum, $r['address'] ?? '-');

            $sheet->setCellValueExplicit('H' . $rowNum, $r['phone'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('I' . $rowNum, $r['email'] ?? '');
            $sheet->setCellValue('J' . $rowNum, $r['direktur'] ?? '-');
            $sheet->setCellValue('K' . $rowNum, $sumber);
            $sheet->setCellValue('L' . $rowNum, !empty($r['updated_at']) ? date('d/m/Y H:i', strtotime($r['updated_at'])) : '-');

            $rowNum++;
        }

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(35);
        $sheet->getColumnDimension('C')->setWidth(24);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(22);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(40);
        $sheet->getColumnDimension('H')->setWidth(18);
        $sheet->getColumnDimension('I')->setWidth(25);
        $sheet->getColumnDimension('J')->setWidth(25);
        $sheet->getColumnDimension('K')->setWidth(18);
        $sheet->getColumnDimension('L')->setWidth(18);

        $filename = 'Database_Kontraktor_Referensi_' . $timestamp . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function _ensureDomisiliAiCache($db)
    {
        $db->query("CREATE TABLE IF NOT EXISTS domisili_ai_cache (
            nama_key VARCHAR(255) NOT NULL,
            alamat_hash CHAR(32) NOT NULL,
            kabupaten_kota VARCHAR(150) NOT NULL,
            provinsi VARCHAR(150) NULL,
            flag_lokasi VARCHAR(30) NOT NULL,
            keterangan TEXT NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (nama_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function getDetailPenyedia()
    {
        if (!has_permission('detail_penyedia') && !has_permission('penyedia-katalog') && session()->get('role') !== 'admin') {
            return $this->response->setJSON(['data' => []]);
        }

        $tahun = trim($this->request->getGet('tahun') ?? '');
        $lokasiFilter = trim($this->request->getGet('lokasi') ?? '');
        $search = trim($this->request->getGet('search') ?? '');

        $rawData = $this->_getDetailPenyediaRawData($tahun, $lokasiFilter, $search);

        $data = [];
        $no = 1;
        foreach ($rawData as $item) {
            $nama = $item['nama_penyedia'];
            $kode = $item['kode_penyedia'];
            $domisili = $item['domisili'];
            $lokasiHtml = $item['lokasi_html'];
            $alamat = $item['alamat'] ?? '';
            $npwp = $item['npwp'] ?? '';
            $kppDesc = $item['kpp_desc'] ?? '-';
            $kppShort = $item['kpp_short'] ?? $kppDesc;
            $kppWilayah = $item['kpp_wilayah'] ?? '-';
            $kppCode = $item['kpp'] ?? '';

            // Format Kolom Alamat (Tanpa simbol, warna teks default)
            if (!empty($alamat) && $alamat !== '-') {
                $cleanAlamat = preg_replace('/,?\s*(Kab\.|Kabupaten)?\s*Lombok\s*Barat\s*\(Kab\.\)/i', '', $alamat);
                $alamatHtml = '<div class="small" style="max-width: 260px; line-height: 1.35;" title="' . htmlspecialchars($cleanAlamat, ENT_QUOTES) . '">'
                    . htmlspecialchars($cleanAlamat) . 
                '</div>';
            } else {
                $alamatHtml = '<span class="text-muted small">-</span>';
            }

            // Format Kolom NPWP & KPP Pajak (Tanpa simbol, warna teks default)
            $npwpFmt = format_npwp($npwp);
            $hasNpwp = (!empty($npwp) && $npwp !== '-' && $npwpFmt !== '-');

            $npwpRow = $hasNpwp 
                ? '<div class="font-monospace small fw-semibold mb-1 user-select-all" title="Nomor Pokok Wajib Pajak (NPWP)">' . htmlspecialchars($npwpFmt) . '</div>'
                : '<div class="text-muted small font-monospace mb-1">-</div>';

            if (!empty($kppCode) && $kppCode !== '-') {
                $kppBadgeColor = 'bg-label-secondary';
                if ($kppCode === '914') $kppBadgeColor = 'bg-label-success';
                elseif ($kppCode === '911') $kppBadgeColor = 'bg-label-primary';
                elseif (in_array($kppCode, ['915', '912', '913'])) $kppBadgeColor = 'bg-label-info';
                elseif ($kppCode === '910') $kppBadgeColor = 'bg-label-warning';

                $kppRow = '<div class="d-flex align-items-center flex-wrap gap-1 mb-1">
                    <span class="badge ' . $kppBadgeColor . ' py-1 px-2" title="' . htmlspecialchars($kppDesc, ENT_QUOTES) . '">
                        KPP ' . htmlspecialchars($kppCode) . '
                    </span>
                    <span class="small fw-semibold" style="font-size:0.78rem;">' . htmlspecialchars($kppShort) . '</span>
                </div>';

                $wilayahRow = '<div class="text-muted small" style="font-size: 0.75rem; line-height: 1.25;">
                    Wilayah: ' . htmlspecialchars($kppWilayah) . '
                </div>';
            } else {
                $kppRow = '<div><span class="badge bg-label-secondary py-1 px-2">KPP: -</span></div>';
                $wilayahRow = '<div class="text-muted small mt-1" style="font-size: 0.75rem;">Wilayah: Belum terdata</div>';
            }

            $kppPajakHtml = '<div class="d-flex flex-column">' . $npwpRow . $kppRow . $wilayahRow . '</div>';

            $aksiHtml = '<div class="d-flex gap-1 flex-wrap">
                <button type="button" class="btn btn-sm btn-outline-primary btn-lihat-transaksi" data-nama="' . htmlspecialchars($nama, ENT_QUOTES) . '" data-kode="' . htmlspecialchars($kode, ENT_QUOTES) . '">
                    <i class="bx bx-list-ul me-1"></i> Transaksi
                </button>
                <button type="button" class="btn btn-sm btn-outline-info btn-lihat-detail" data-nama="' . htmlspecialchars($nama, ENT_QUOTES) . '" data-kode="' . htmlspecialchars($kode, ENT_QUOTES) . '" data-domisili="' . htmlspecialchars($domisili, ENT_QUOTES) . '" data-lokasi-badge="' . htmlspecialchars($lokasiHtml, ENT_QUOTES) . '" data-kpp="' . htmlspecialchars($kppDesc, ENT_QUOTES) . '" data-npwp="' . htmlspecialchars($npwpFmt, ENT_QUOTES) . '" data-alamat="' . htmlspecialchars($alamat, ENT_QUOTES) . '">
                    <i class="bx bx-id-card me-1"></i> Profil
                </button>
            </div>';

            $data[] = [
                'no' => $no++,
                'kode_penyedia' => $kode !== '' ? '<span class="font-monospace small">' . htmlspecialchars($kode) . '</span>' : '<span class="text-muted">-</span>',
                'nama_penyedia' => '<span class="fw-semibold text-dark">' . htmlspecialchars($nama) . '</span>',
                'lokasi' => $lokasiHtml,
                'alamat' => $alamatHtml,
                'needs_ai' => !empty($item['needs_ai']),
                'raw_nama' => $nama,
                'raw_kode' => $kode,
                'raw_npwp' => $npwp,
                'raw_alamat' => $item['alamat_asli'] ?? '',
                'kpp_pajak' => $kppPajakHtml,
                'npwp' => $npwpFmt,
                'domisili' => $domisili,
                'flag_lokasi' => $domisili,
                'sumber' => $item['sumber_badges'] ?: '<span class="text-muted">-</span>',
                'total_paket' => '<span class="badge bg-primary rounded-pill">' . number_format($item['total_paket'], 0, ',', '.') . ' Paket</span>',
                'total_transaksi' => '<span class="fw-bold text-success">Rp ' . number_format($item['total_transaksi'], 0, ',', '.') . '</span>',
                'aksi' => $aksiHtml
            ];
        }

        return $this->response->setJSON(['data' => $data]);
    }

    public function exportDetailPenyedia()
    {
        if (!has_permission('detail_penyedia') && !has_permission('penyedia-katalog') && session()->get('role') !== 'admin') {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses.');
        }

        $tahun = trim($this->request->getGet('tahun') ?? '');
        $lokasiFilter = trim($this->request->getGet('lokasi') ?? '');
        $search = trim($this->request->getGet('search') ?? '');

        $rawData = $this->_getDetailPenyediaRawData($tahun, $lokasiFilter, $search);

        $lokasiLabels = [
            'all' => 'Semua Domisili',
            'lobar' => 'Lombok Barat',
            'mataram' => 'Kota Mataram',
            'ntb_lain' => 'NTB Lainnya (Loteng, Lotim, Sumbawa, Bima, dll)',
            'luar' => 'Luar Daerah (Nasional)',
            '' => 'Semua Domisili'
        ];
        $labelLokasi = $lokasiLabels[$lokasiFilter] ?? 'Semua Domisili';
        $labelTahun = ($tahun !== '' && $tahun !== 'all') ? $tahun : 'Semua Tahun';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail Penyedia');

        // Document Properties
        $spreadsheet->getProperties()
            ->setCreator('InfoAJIP - Pengadaan Barang & Jasa')
            ->setTitle('Data Detail & Transaksi Penyedia')
            ->setSubject('Detail Penyedia Pengadaan');

        // Header Title Block
        $sheet->setCellValue('A1', 'DAFTAR DETAIL & TRANSAKSI PENYEDIA BARANG / JASA');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF1E2538'));
        $sheet->setCellValue('A2', 'Pemerintah Kabupaten Lombok Barat - Sistem Informasi Pengadaan (InfoAJIP)');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF6C757D'));

        $metaInfo = 'Tahun Anggaran: ' . $labelTahun . ' | Domisili: ' . $labelLokasi;
        if ($search !== '') {
            $metaInfo .= ' | Filter Pencarian: "' . $search . '"';
        }
        $metaInfo .= ' | Total: ' . number_format(count($rawData), 0, ',', '.') . ' Penyedia | Diunduh: ' . date('d/m/Y H:i') . ' WITA';
        $sheet->setCellValue('A3', $metaInfo);
        $sheet->getStyle('A3')->getFont()->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF495057'));

        // Table Column Headers (Row 5)
        $headers = [
            'A5' => 'No',
            'B5' => 'Kode Penyedia',
            'C5' => 'Nama Penyedia / Perusahaan',
            'D5' => 'Domisili Usaha',
            'E5' => 'Alamat Lengkap',
            'F5' => 'Nomor NPWP',
            'G5' => 'KPP Pajak Terdaftar',
            'H5' => 'Lokasi / Wilayah Kerja KPP',
            'I5' => 'Kategori Wilayah',
            'J5' => 'Jenis Pengadaan (Sumber)',
            'K5' => 'Total Paket',
            'L5' => 'Total Nilai Transaksi (Rp)'
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10],
            'fill' => ['type' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['argb' => 'FF1E2538']],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ],
        ];
        $sheet->getStyle('A5:L5')->applyFromArray($headerStyle);
        $sheet->getRowDimension(5)->setRowHeight(28);

        // Fill Data Rows
        $rowNum = 6;
        $no = 1;
        foreach ($rawData as $item) {
            $sheet->setCellValue('A' . $rowNum, $no++);
            
            // Kode Penyedia forced as STRING
            $sheet->setCellValueExplicit('B' . $rowNum, $item['kode_penyedia'] ?: '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            
            $sheet->setCellValue('C' . $rowNum, $item['nama_penyedia']);
            $sheet->setCellValue('D' . $rowNum, $item['domisili']);
            $sheet->setCellValue('E' . $rowNum, $item['alamat'] ?: '-');
            $sheet->setCellValueExplicit('F' . $rowNum, format_npwp($item['npwp'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('G' . $rowNum, $item['kpp_desc']);
            $sheet->setCellValue('H' . $rowNum, $item['kpp_wilayah'] ?? '-');
            $sheet->setCellValue('I' . $rowNum, $item['kategori_wilayah']);
            $sheet->setCellValue('J' . $rowNum, $item['sumber_list'] ?: '-');
            
            $sheet->setCellValue('K' . $rowNum, $item['total_paket']);
            $sheet->setCellValue('L' . $rowNum, $item['total_transaksi']);

            $sheet->getRowDimension($rowNum)->setRowHeight(20);
            $rowNum++;
        }

        $lastDataRow = $rowNum - 1;

        // Formatting columns
        if ($lastDataRow >= 6) {
            // Number formats
            $sheet->getStyle('K6:K' . $lastDataRow)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('L6:L' . $lastDataRow)->getNumberFormat()->setFormatCode('#,##0');

            // Alignments
            $sheet->getStyle('A6:A' . $lastDataRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B6:B' . $lastDataRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D6:D' . $lastDataRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('F6:F' . $lastDataRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H6:I' . $lastDataRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('K6:L' . $lastDataRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

            // Summary Row
            $sheet->mergeCells('A' . $rowNum . ':J' . $rowNum);
            $sheet->setCellValue('A' . $rowNum, 'TOTAL KESELURUHAN');
            $sheet->setCellValue('K' . $rowNum, '=SUM(K6:K' . $lastDataRow . ')');
            $sheet->setCellValue('L' . $rowNum, '=SUM(L6:L' . $lastDataRow . ')');

            $summaryStyle = [
                'font' => ['bold' => true, 'color' => ['argb' => 'FF1E2538'], 'size' => 11],
                'fill' => ['type' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['argb' => 'FFE9ECEF']],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ]
            ];
            $sheet->getStyle('A' . $rowNum . ':I' . $rowNum)->applyFromArray($summaryStyle);
            $sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H' . $rowNum . ':I' . $rowNum)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('H' . $rowNum)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('I' . $rowNum)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getRowDimension($rowNum)->setRowHeight(25);

            // Table Borders
            $borderStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => 'FFD1D5DB'],
                    ],
                ],
            ];
            $sheet->getStyle('A5:I' . $rowNum)->applyFromArray($borderStyle);
        }

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(38);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(34);
        $sheet->getColumnDimension('G')->setWidth(30);
        $sheet->getColumnDimension('H')->setWidth(14);
        $sheet->getColumnDimension('I')->setWidth(24);

        $safeTahun = ($tahun !== '' && $tahun !== 'all') ? $tahun : 'SemuaTahun';
        $safeLokasi = ($lokasiFilter !== '' && $lokasiFilter !== 'all') ? '_' . $lokasiFilter : '';
        $filename = 'Detail_Penyedia_' . $safeTahun . $safeLokasi . '_' . date('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function _getDetailPenyediaRawData($tahun = '', $lokasiFilter = '', $search = '')
    {
        $db = \Config\Database::connect();
        if ($tahun === 'all') {
            $tahun = '';
        }
        if ($lokasiFilter === 'all') {
            $lokasiFilter = '';
        }

        // Subqueries across all 4 transaction tables
        $subqueries = [];

        // 1. E-Purchasing (Katalog)
        $qEpur = "SELECT 
            TRIM(nama_penyedia) as nama_penyedia, 
            kode_penyedia as kd_penyedia, 
            'E-Katalog' as sumber, 
            total_harga as nilai, 
            id as paket_id 
            FROM epurchasing 
            WHERE nama_penyedia IS NOT NULL AND TRIM(nama_penyedia) != ''";
        if ($tahun !== '') {
            $qEpur .= " AND tahun = " . $db->escape($tahun);
        }
        $subqueries[] = $qEpur;

        // 2. LPSE Tender
        $qTender = "SELECT 
            TRIM(nama_penyedia) as nama_penyedia, 
            kd_penyedia, 
            'Tender' as sumber, 
            nilai_kontrak as nilai, 
            id as paket_id 
            FROM lpse_tender 
            WHERE nama_penyedia IS NOT NULL AND TRIM(nama_penyedia) != ''";
        if ($tahun !== '') {
            $qTender .= " AND tahun = " . $db->escape($tahun);
        }
        $subqueries[] = $qTender;

        // 3. LPSE Non-Tender
        $qNonTender = "SELECT 
            TRIM(nama_penyedia) as nama_penyedia, 
            kd_penyedia, 
            'Non-Tender' as sumber, 
            nilai_kontrak as nilai, 
            id as paket_id 
            FROM lpse_nontender 
            WHERE nama_penyedia IS NOT NULL AND TRIM(nama_penyedia) != ''";
        if ($tahun !== '') {
            $qNonTender .= " AND tahun = " . $db->escape($tahun);
        }
        $subqueries[] = $qNonTender;

        // 4. LPSE Pencatatan Non-Tender
        $qPct = "SELECT 
            TRIM(nama_penyedia) as nama_penyedia, 
            kd_penyedia, 
            'Pencatatan Non-Tender' as sumber, 
            realisasi as nilai, 
            id as paket_id 
            FROM lpse_pencatatan_nontender 
            WHERE nama_penyedia IS NOT NULL AND TRIM(nama_penyedia) != ''";
        if ($tahun !== '') {
            $qPct .= " AND tahun = " . $db->escape($tahun);
        }
        $subqueries[] = $qPct;

        $unionSql = implode(" UNION ALL ", $subqueries);

        $mainSql = "SELECT 
            UPPER(TRIM(nama_penyedia)) as normalized_nama,
            MAX(nama_penyedia) as nama_penyedia,
            MAX(kd_penyedia) as kode_penyedia,
            COUNT(paket_id) as total_paket,
            SUM(nilai) as total_transaksi,
            GROUP_CONCAT(DISTINCT sumber ORDER BY sumber SEPARATOR ', ') as sumber_list
            FROM ({$unionSql}) as all_transaksi
            GROUP BY UPPER(TRIM(nama_penyedia))
            ORDER BY total_transaksi DESC";

        $results = $db->query($mainSql)->getResultArray();

        helper('ajip');

        $normalizeName = function($str) {
            $s = strtoupper(trim($str));
            $s = preg_replace('/\s*[\(\/].*$/', '', $s);
            $s = preg_replace('/^(PT|CV|UD|PO|KOPERASI|TOKO)\.?\s*/i', '', $s);
            $s = preg_replace('/[,.\s]+(PT|CV|UD|PO|KOPERASI|TBK|PERSERO)\.?$/i', '', $s);
            $s = str_replace(['TEHNIK', 'KONSULTAN', 'GROUF', 'APOTIK'], ['TEKNIK', 'CONSULTANT', 'GROUP', 'APOTEK'], $s);
            return preg_replace('/[^A-Z0-9]/', '', $s);
        };

        $isValidNpwp = function($n) {
            if (empty($n)) return false;
            $digits = preg_replace('/[^0-9]/', '', $n);
            return (strlen($digits) >= 9 && $digits !== str_repeat('0', strlen($digits)));
        };

        // Cache location and flags from inaproc_vendor_sync, LPSE tables, and SIKaP
        $vendorFlags = [];
        $vfRows = $db->table('inaproc_vendor_sync')
            ->select('nama_penyedia, kode_penyedia, npwp_penyedia, flag_lokasi, lokasi_perusahaan, kpp_code, alamat_penyedia')
            ->get()->getResultArray();
        foreach ($vfRows as $vf) {
            $nUpper = strtoupper(trim($vf['nama_penyedia'] ?? ''));
            if ($nUpper !== '') {
                $vendorFlags[$nUpper] = $vf;
                $norm = $normalizeName($nUpper);
                if ($norm !== '') $vendorFlags['NORM_' . $norm] = $vf;
            }
            $kCode = trim($vf['kode_penyedia'] ?? '');
            if ($kCode !== '') {
                $vendorFlags['CODE_' . $kCode] = $vf;
            }
        }

        $mergeVendorFlag = function(&$target, $newItem) use ($isValidNpwp) {
            if (!$target) {
                $target = $newItem;
                return;
            }
            if ($isValidNpwp($newItem['npwp_penyedia'] ?? '') && !$isValidNpwp($target['npwp_penyedia'] ?? '')) {
                $target['npwp_penyedia'] = $newItem['npwp_penyedia'];
            }
            $curAddr = $target['alamat_penyedia'] ?? '';
            $newAddr = $newItem['alamat_penyedia'] ?? '';
            if (!empty($newAddr) && (empty($curAddr) || strpos($curAddr, '*****') !== false)) {
                $target['alamat_penyedia'] = $newAddr;
            }
            if (!empty($newItem['domisili_kota']) && (empty($target['domisili_kota']) || $target['domisili_kota'] === 'NTB Lainnya')) {
                $target['domisili_kota'] = $newItem['domisili_kota'];
            }
            if (!empty($newItem['province']) && empty($target['province'])) {
                $target['province'] = $newItem['province'];
            }
            if (!empty($newItem['lokasi_perusahaan']) && empty($target['lokasi_perusahaan'])) {
                $target['lokasi_perusahaan'] = $newItem['lokasi_perusahaan'];
            }
            if (!empty($newItem['flag_lokasi']) && empty($target['flag_lokasi'])) {
                $target['flag_lokasi'] = $newItem['flag_lokasi'];
            }
            if (!empty($newItem['kpp_code']) && empty($target['kpp_code'])) {
                $target['kpp_code'] = $newItem['kpp_code'];
            }
            if (!empty($newItem['kode_penyedia']) && empty($target['kode_penyedia'])) {
                $target['kode_penyedia'] = $newItem['kode_penyedia'];
            }
        };

        // Merge from LPSE Tender, Non-Tender, Pencatatan
        $lpseSources = [
            ['tbl' => 'lpse_tender', 'alamat' => 'alamat_penyedia'],
            ['tbl' => 'lpse_nontender', 'alamat' => 'alamat_penyedia'],
            ['tbl' => 'lpse_pencatatan_nontender', 'alamat' => '"" as alamat_penyedia']
        ];
        foreach ($lpseSources as $src) {
            $lRows = $db->query("SELECT nama_penyedia, kd_penyedia as kode_penyedia, npwp_penyedia, {$src['alamat']} FROM {$src['tbl']} WHERE npwp_penyedia IS NOT NULL AND TRIM(npwp_penyedia) != ''")->getResultArray();
            foreach ($lRows as $lr) {
                $nUpper = strtoupper(trim($lr['nama_penyedia'] ?? ''));
                $norm = $normalizeName($nUpper);
                $kCode = trim($lr['kode_penyedia'] ?? '');
                
                if ($nUpper !== '') {
                    if (!isset($vendorFlags[$nUpper])) $vendorFlags[$nUpper] = $lr;
                    else $mergeVendorFlag($vendorFlags[$nUpper], $lr);
                }
                if ($norm !== '') {
                    if (!isset($vendorFlags['NORM_' . $norm])) $vendorFlags['NORM_' . $norm] = $lr;
                    else $mergeVendorFlag($vendorFlags['NORM_' . $norm], $lr);
                }
                if ($kCode !== '') {
                    if (!isset($vendorFlags['CODE_' . $kCode])) $vendorFlags['CODE_' . $kCode] = $lr;
                    else $mergeVendorFlag($vendorFlags['CODE_' . $kCode], $lr);
                }
            }
        }

        // Merge from SIKaP CSV if available
        $csvPath = ROOTPATH . 'Hasil_Bot_SIKaP.csv';
        if (!is_file($csvPath)) $csvPath = FCPATH . 'Hasil_Bot_SIKaP.csv';
        if (is_file($csvPath) && ($h = fopen($csvPath, 'r')) !== false) {
            fgetcsv($h, 0, ';');
            while ($r = fgetcsv($h, 0, ';')) {
                $c_nama = trim($r[1] ?? '');
                $c_npwp = trim($r[2] ?? '');
                $c_kab = trim($r[3] ?? '');
                $c_prov = trim($r[4] ?? 'Nusa Tenggara Barat');
                $c_alamat = $c_kab . ($c_prov ? ', ' . $c_prov : '');
                if ($c_nama !== '') {
                    $nUpper = strtoupper($c_nama);
                    $norm = $normalizeName($nUpper);
                    $sItem = [
                        'nama_penyedia' => $c_nama,
                        'npwp_penyedia' => $c_npwp,
                        'alamat_penyedia' => $c_alamat,
                        'domisili_kota' => $c_kab,
                        'province' => $c_prov,
                        'kode_penyedia' => ''
                    ];
                    if (!isset($vendorFlags[$nUpper])) $vendorFlags[$nUpper] = $sItem;
                    else $mergeVendorFlag($vendorFlags[$nUpper], $sItem);

                    if ($norm !== '') {
                        if (!isset($vendorFlags['NORM_' . $norm])) $vendorFlags['NORM_' . $norm] = $sItem;
                        else $mergeVendorFlag($vendorFlags['NORM_' . $norm], $sItem);
                    }
                }
            }
            fclose($h);
        }

        // Merge from pengadaanpro_contractors (Database Kontraktor Nasional)
        try {
            $proAddr = [];
            if ($db->tableExists('pengadaanpro_contractors')) {
                $proRows = $db->table('pengadaanpro_contractors')
                    ->select('contractor_name, contractor_name_normalized, npwp, address, domisili_kota, province')
                    ->get()->getResultArray();
                $proAddr = [];
                foreach ($proRows as $pr) {
                    $prAddr = trim($pr['address'] ?? '');
                    if ($prAddr !== '' && strpos($prAddr, '*') === false) {
                        $prNpwp = preg_replace('/[^0-9]/', '', $pr['npwp'] ?? '');
                        if (strlen($prNpwp) >= 15) $proAddr['NPWP_' . $prNpwp] = $prAddr;
                        $prN = strtoupper(trim($pr['contractor_name'] ?? ''));
                        if ($prN !== '') $proAddr[$prN] = $prAddr;
                        $prNorm = $normalizeName($prN);
                        if ($prNorm !== '') $proAddr['NORM_' . $prNorm] = $prAddr;
                    }
                    $nUpper = strtoupper(trim($pr['contractor_name'] ?? ''));
                    $norm = $normalizeName($nUpper);
                    $normDb = strtoupper(trim($pr['contractor_name_normalized'] ?? ''));
                    
                    $pItem = [
                        'nama_penyedia' => $pr['contractor_name'],
                        'npwp_penyedia' => $pr['npwp'] ?? '',
                        'alamat_penyedia' => !empty($pr['address']) ? $pr['address'] : (!empty($pr['domisili_kota']) ? $pr['domisili_kota'] . ', ' . $pr['province'] : $pr['province']),
                        'kode_penyedia' => '',
                        'domisili_kota' => $pr['domisili_kota'] ?? '',
                        'province' => $pr['province'] ?? '',
                        'direct_loc' => true
                    ];
                    if (!isset($vendorFlags[$nUpper])) $vendorFlags[$nUpper] = $pItem;
                    else $mergeVendorFlag($vendorFlags[$nUpper], $pItem);

                    if ($norm !== '') {
                        if (!isset($vendorFlags['NORM_' . $norm])) $vendorFlags['NORM_' . $norm] = $pItem;
                        else $mergeVendorFlag($vendorFlags['NORM_' . $norm], $pItem);
                    }
                    if ($normDb !== '' && $normDb !== $norm) {
                        if (!isset($vendorFlags['NORM_' . $normDb])) $vendorFlags['NORM_' . $normDb] = $pItem;
                        else $mergeVendorFlag($vendorFlags['NORM_' . $normDb], $pItem);
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Error merging pengadaanpro_contractors: ' . $e->getMessage());
        }

        // Cache hasil deteksi domisili AI berdasarkan alamat (key: nama penyedia)
        $aiCache = [];
        try {
            $this->_ensureDomisiliAiCache($db);
            foreach ($db->table('domisili_ai_cache')->get()->getResultArray() as $ac) {
                $aiCache[$ac['nama_key']] = $ac;
            }
        } catch (\Throwable $e) {
            log_message('error', 'Error loading domisili_ai_cache: ' . $e->getMessage());
        }

        $cleanSearch = strtoupper(trim($search));

        $output = [];
        foreach ($results as $row) {
            $nama = trim($row['nama_penyedia']);
            $kode = trim($row['kode_penyedia'] ?? '');

            // Determine location and domisili
            $norm = $normalizeName($nama);
            $exactData = $vendorFlags[strtoupper($nama)] ?? null;
            $normData = ($norm !== '') ? ($vendorFlags['NORM_' . $norm] ?? null) : null;
            $codeData = ($kode !== '') ? ($vendorFlags['CODE_' . $kode] ?? null) : null;

            // Start with exactData, then enrich with normData or codeData if missing domisili_kota / unmasked address
            $flagData = $exactData ?? $normData ?? $codeData;
            if ($normData) {
                if (!$flagData) {
                    $flagData = $normData;
                } else {
                    if (empty($flagData['domisili_kota']) && !empty($normData['domisili_kota'])) {
                        $flagData['domisili_kota'] = $normData['domisili_kota'];
                    }
                    if (empty($flagData['province']) && !empty($normData['province'])) {
                        $flagData['province'] = $normData['province'];
                    }
                    if (empty($flagData['npwp_penyedia']) && !empty($normData['npwp_penyedia'])) {
                        $flagData['npwp_penyedia'] = $normData['npwp_penyedia'];
                    }
                    $curA = $flagData['alamat_penyedia'] ?? '';
                    $newA = $normData['alamat_penyedia'] ?? '';
                    if (!empty($newA) && (empty($curA) || strpos($curA, '*****') !== false)) {
                        $flagData['alamat_penyedia'] = $newA;
                    }
                }
            }
            if ($codeData) {
                if (!$flagData) {
                    $flagData = $codeData;
                } else {
                    if (empty($flagData['domisili_kota']) && !empty($codeData['domisili_kota'])) {
                        $flagData['domisili_kota'] = $codeData['domisili_kota'];
                    }
                    if (empty($flagData['province']) && !empty($codeData['province'])) {
                        $flagData['province'] = $codeData['province'];
                    }
                    if (empty($flagData['npwp_penyedia']) && !empty($codeData['npwp_penyedia'])) {
                        $flagData['npwp_penyedia'] = $codeData['npwp_penyedia'];
                    }
                    $curA = $flagData['alamat_penyedia'] ?? '';
                    $newA = $codeData['alamat_penyedia'] ?? '';
                    if (!empty($newA) && (empty($curA) || strpos($curA, '*****') !== false)) {
                        $flagData['alamat_penyedia'] = $newA;
                    }
                }
            }

            $domisili = '-';
            $flagCat = 'unknown';

            // Ekstraksi KPP Pajak secara mandiri dari NPWP
            $npwpVal = $flagData['npwp_penyedia'] ?? '';
            $alamatVal = $flagData['alamat_penyedia'] ?? '';
            $domKotaVal = $flagData['domisili_kota'] ?? '';
            $provVal = $flagData['province'] ?? '';

            // Referensi utama: alamat dari Hasil Scrape SPSE (pengadaanpro_contractors) bila tersedia & tidak tersensor
            $spseAddr = '';
            $npwpDigits = preg_replace('/[^0-9]/', '', $flagData['npwp_penyedia'] ?? '');
            if ($npwpDigits !== '' && isset($proAddr['NPWP_' . $npwpDigits])) {
                $spseAddr = $proAddr['NPWP_' . $npwpDigits];
            } elseif (isset($proAddr[strtoupper($nama)])) {
                $spseAddr = $proAddr[strtoupper($nama)];
            } elseif ($norm !== '' && isset($proAddr['NORM_' . $norm])) {
                $spseAddr = $proAddr['NORM_' . $norm];
            }
            if ($spseAddr !== '') {
                $alamatVal = $spseAddr;
            }

            // Alamat asli (sebelum fallback) = dasar utama penentuan domisili
            $alamatAsli = trim($alamatVal);

            // Jika alamat masih kosong, fallback ke domisili kota & provinsi
            if (empty($alamatVal) && !empty($domKotaVal)) {
                $alamatVal = $domKotaVal . (!empty($provVal) ? ', ' . $provVal : '');
            }

            // Jika alamat fisik tersedia, klasifikasi HANYA dari teks alamat (jangan dicampur domisili_kota/provinsi)
            $checkLoc = ($alamatAsli !== '') ? $alamatAsli : trim($alamatVal . ' ' . $domKotaVal . ' ' . $provVal);
            $locInfo = classify_npwp_location($npwpVal, $checkLoc, $provVal);

            // Ekstraksi KPP akurat
            $kppInfo = extract_kpp_from_npwp($npwpVal, $flagData['kpp_code'] ?? ($locInfo['kpp_code'] ?? ''));

            // PRIORITAS 1: hasil AI tersimpan untuk alamat yang sama
            // PRIORITAS 2: kata kunci wilayah pada teks alamat
            // PRIORITAS 3: data resmi (domisili_kota) / KPP / provinsi
            $needsAi = false;
            $aiHit = null;
            $aiKey = strtoupper(trim($nama));
            if ($alamatAsli !== '' && isset($aiCache[$aiKey]) && $aiCache[$aiKey]['alamat_hash'] === md5(strtolower($alamatAsli))) {
                $aiHit = $aiCache[$aiKey];
            }
            $addrHit = ($alamatAsli !== '' && strpos($locInfo['keterangan'] ?? '', 'Domisili di ') === 0);

            if ($aiHit) {
                $domisili = preg_replace('/^(KAB\.|KOTA)\s+/i', '', $aiHit['kabupaten_kota']);
                $flagCat = $aiHit['flag_lokasi'];
                if ($flagCat === 'lobar') $domisili = 'Lombok Barat';
                elseif ($flagCat === 'mataram') $domisili = 'Mataram';
                if (!in_array($flagCat, ['lobar', 'mataram', 'ntb_lain', 'luar'])) $flagCat = 'luar';
            } elseif ($addrHit) {
                $domisili = $locInfo['domisili'];
                $flagCat = $locInfo['flag_kategori'];
            } elseif (!empty($domKotaVal)) {
                $uKota = strtoupper($domKotaVal);
                $cleanKota = preg_replace('/^(KAB\.|KOTA)\s+/i', '', $domKotaVal);
                if (strpos($uKota, 'MATARAM') !== false) {
                    $domisili = 'Mataram';
                    $flagCat = 'mataram';
                } elseif (strpos($uKota, 'LOMBOK BARAT') !== false) {
                    $domisili = 'Lombok Barat';
                    $flagCat = 'lobar';
                } elseif (strpos($uKota, 'LOMBOK TENGAH') !== false) {
                    $domisili = 'Lombok Tengah';
                    $flagCat = 'ntb_lain';
                } elseif (strpos($uKota, 'LOMBOK TIMUR') !== false) {
                    $domisili = 'Lombok Timur';
                    $flagCat = 'ntb_lain';
                } elseif (strpos($uKota, 'LOMBOK UTARA') !== false) {
                    $domisili = 'Lombok Utara';
                    $flagCat = 'ntb_lain';
                } elseif (strpos($uKota, 'SUMBAWA BARAT') !== false) {
                    $domisili = 'Sumbawa Barat';
                    $flagCat = 'ntb_lain';
                } elseif (strpos($uKota, 'SUMBAWA') !== false) {
                    $domisili = 'Sumbawa';
                    $flagCat = 'ntb_lain';
                } elseif (strpos($uKota, 'BIMA') !== false) {
                    $domisili = 'Bima';
                    $flagCat = 'ntb_lain';
                } elseif (strpos($uKota, 'DOMPU') !== false) {
                    $domisili = 'Dompu';
                    $flagCat = 'ntb_lain';
                } else {
                    $domisili = $cleanKota;
                    $flagCat = ($provVal === 'Nusa Tenggara Barat') ? 'ntb_lain' : 'luar';
                }
            } elseif ($locInfo['flag_kategori'] !== 'unknown' && $locInfo['domisili'] !== '-') {
                $domisili = $locInfo['domisili'];
                $flagCat = $locInfo['flag_kategori'];
            } elseif (!empty($flagData['flag_lokasi']) && $flagData['flag_lokasi'] !== 'unknown') {
                $domisili = $flagData['lokasi_perusahaan'] ?: $flagData['flag_lokasi'];
                $uDom = strtoupper($domisili);
                $flagCat = 'luar';
                if (strpos($uDom, 'LOMBOK BARAT') !== false || strpos($uDom, 'GERUNG') !== false) $flagCat = 'lobar';
                elseif (strpos($uDom, 'MATARAM') !== false) $flagCat = 'mataram';
                elseif (strpos($uDom, 'LOMBOK') !== false || strpos($uDom, 'BIMA') !== false || strpos($uDom, 'SUMBAWA') !== false || strpos($uDom, 'DOMPU') !== false || strpos($uDom, 'NTB') !== false) $flagCat = 'ntb_lain';
            } elseif (!empty($flagData['province'])) {
                $prov = trim($flagData['province']);
                $uProv = strtoupper($prov);
                $domisili = $prov;
                $flagCat = ($uProv === 'NUSA TENGGARA BARAT') ? 'ntb_lain' : 'luar';
            }

            // Fallback for local OPD direct procurement and local merchants
            if ($domisili === '-' || $flagCat === 'unknown') {
                $uName = strtoupper($nama);
                if (strpos($uName, 'PLN') !== false || strpos($uName, 'LISTRIK NEGARA') !== false) {
                    $domisili = 'Jakarta'; $flagCat = 'luar';
                } elseif (strpos($uName, 'SPBU') !== false) {
                    $domisili = 'Lombok Barat'; $flagCat = 'lobar';
                } elseif (preg_match('/\b(CATERING|KATERING|MOTOR|BENGKEL|LOCKSMITH|AC|VARIASI|PRINT|OFFSET|TAILOR|JAHIT)\b/i', $uName)) {
                    $domisili = 'Lombok Barat'; $flagCat = 'lobar';
                }
                // Tidak ada lagi default 'Lombok Barat': tanpa dasar alamat/data, domisili tetap '-'
            }

            // Alamat ada tetapi belum terpetakan oleh kata kunci / belum pernah dicek AI -> perlu AI
            if ($alamatAsli !== '' && !$aiHit && !$addrHit) {
                $needsAi = true;
            }

            // KPP and KPP Desc
            $kpp = $kppInfo['code'];
            $kppDesc = $kppInfo['name'];
            if ($kppDesc === '-' && !empty($locInfo['kpp']) && $locInfo['kpp'] !== '-') {
                $kppDesc = $locInfo['kpp'];
            }

            // Apply filter lokasi
            // Khusus penyedia luar daerah yang NPWP-nya terdaftar di KPP Lombok Barat (914):
            // Tetap masuk jika user memilih filter Lombok Barat ('lobar')
            if ($lokasiFilter !== '') {
                if ($lokasiFilter === 'lobar' && $flagCat !== 'lobar' && !$kppInfo['is_lobar']) continue;
                if ($lokasiFilter === 'mataram' && $flagCat !== 'mataram' && $kppInfo['code'] !== '911') continue;
                if ($lokasiFilter === 'ntb_lain' && $flagCat !== 'ntb_lain' && !in_array($kppInfo['code'], ['912', '913', '915'])) continue;
                if ($lokasiFilter === 'ntb' && !in_array($flagCat, ['lobar', 'mataram', 'ntb_lain']) && !$kppInfo['is_ntb']) continue;
                if ($lokasiFilter === 'luar' && $flagCat !== 'luar') continue;
            }

            // Apply filter search
            if ($cleanSearch !== '') {
                if (strpos(strtoupper($nama), $cleanSearch) === false &&
                    strpos(strtoupper($kode), $cleanSearch) === false &&
                    strpos(strtoupper($domisili), $cleanSearch) === false &&
                    strpos(strtoupper($alamatVal), $cleanSearch) === false &&
                    strpos(strtoupper($npwpVal), $cleanSearch) === false &&
                    strpos(strtoupper($kppInfo['code']), $cleanSearch) === false &&
                    strpos(strtoupper($kppInfo['name']), $cleanSearch) === false &&
                    strpos(strtoupper($kppInfo['wilayah'] ?? ''), $cleanSearch) === false) {
                    continue;
                }
            }

            // Kategori Wilayah Label
            $kategoriWilayah = ($flagCat === 'unknown') ? 'Belum Terdata' : 'Luar NTB';
            if ($flagCat === 'lobar') $kategoriWilayah = 'Lombok Barat';
            elseif ($flagCat === 'mataram') $kategoriWilayah = 'Kota Mataram';
            elseif ($flagCat === 'ntb_lain') $kategoriWilayah = 'NTB Lainnya';

            // Format Badge Domisili (Tanpa simbol/ikon)
            if ($domisili === '-' || $flagCat === 'unknown') {
                $lokasiHtml = '<span class="text-muted small">-</span>';
            } else {
                $badgeColor = 'bg-label-secondary';
                if ($flagCat === 'lobar') {
                    $badgeColor = 'bg-label-success';
                } elseif ($flagCat === 'mataram') {
                    $badgeColor = 'bg-label-primary';
                } elseif ($flagCat === 'ntb_lain') {
                    $badgeColor = 'bg-label-info';
                }

                $lokasiHtml = '<span class="badge ' . $badgeColor . ' py-1 px-2" title="' . htmlspecialchars($domisili) . '">' . htmlspecialchars($domisili) . '</span>';
            }

            // Badges for transaction sources
            $sumberParts = array_map('trim', explode(',', $row['sumber_list'] ?? ''));
            $sumberBadges = '';
            foreach ($sumberParts as $sp) {
                if ($sp === 'E-Katalog') {
                    $sumberBadges .= '<span class="badge bg-label-success me-1">Katalog</span>';
                } elseif ($sp === 'Tender') {
                    $sumberBadges .= '<span class="badge bg-label-primary me-1">Tender</span>';
                } elseif ($sp === 'Non-Tender') {
                    $sumberBadges .= '<span class="badge bg-label-warning me-1">Non-Tender</span>';
                } elseif ($sp === 'Pencatatan Non-Tender') {
                    $sumberBadges .= '<span class="badge bg-label-info me-1">Pencatatan</span>';
                }
            }

            $output[] = [
                'nama_penyedia' => $nama,
                'kode_penyedia' => $kode,
                'npwp' => $npwpVal,
                'alamat' => $alamatVal,
                'needs_ai' => $needsAi,
                'alamat_asli' => $alamatAsli,
                'domisili' => $domisili,
                'kategori_wilayah' => $kategoriWilayah,
                'flag_cat' => $flagCat,
                'kpp' => $kpp,
                'kpp_desc' => $kppDesc,
                'kpp_short' => $kppInfo['short'] ?? $kppDesc,
                'kpp_wilayah' => $kppInfo['wilayah'] ?? '-',
                'kpp_badge' => $kppInfo['badge'],
                'total_paket' => (int)($row['total_paket'] ?? 0),
                'total_transaksi' => (float)($row['total_transaksi'] ?? 0),
                'sumber_list' => $row['sumber_list'] ?? '',
                'lokasi_html' => $lokasiHtml,
                'sumber_badges' => $sumberBadges,
            ];
        }

        return $output;
    }

    public function getTransaksiPenyedia()
    {
        if (!has_permission('detail_penyedia') && !has_permission('penyedia-katalog')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $namaPenyedia = trim($this->request->getGet('nama_penyedia') ?? '');
        $kodePenyedia = trim($this->request->getGet('kode_penyedia') ?? '');
        $tahun = trim($this->request->getGet('tahun') ?? '');

        if ($namaPenyedia === '' && $kodePenyedia === '') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Nama atau Kode Penyedia diperlukan', 'data' => []]);
        }

        $db = \Config\Database::connect();
        $transactions = [];

        // 1. E-Purchasing
        $b1 = $db->table('epurchasing')
            ->select('kd_paket, nama_paket, nama_satker, tahun, total_harga as nilai, order_date as tanggal, status, "E-Katalog" as sumber, "E-Purchasing" as metode')
            ->groupStart();
        if ($namaPenyedia !== '') {
            $b1->where('UPPER(TRIM(nama_penyedia))', strtoupper($namaPenyedia));
        }
        if ($kodePenyedia !== '') {
            $b1->orWhere('kode_penyedia', $kodePenyedia);
        }
        $b1->groupEnd();
        if ($tahun !== '' && $tahun !== 'all') {
            $b1->where('tahun', $tahun);
        }
        $r1 = $b1->get()->getResultArray();
        foreach ($r1 as $row) {
            $transactions[] = $row;
        }

        // 2. LPSE Tender
        $b2 = $db->table('lpse_tender')
            ->select('kd_paket, nama_paket, nama_satker, tahun, nilai_kontrak as nilai, tgl_kontrak as tanggal, status, "Tender" as sumber, metode')
            ->groupStart();
        if ($namaPenyedia !== '') {
            $b2->where('UPPER(TRIM(nama_penyedia))', strtoupper($namaPenyedia));
        }
        if ($kodePenyedia !== '') {
            $b2->orWhere('kd_penyedia', $kodePenyedia);
        }
        $b2->groupEnd();
        if ($tahun !== '' && $tahun !== 'all') {
            $b2->where('tahun', $tahun);
        }
        $r2 = $b2->get()->getResultArray();
        foreach ($r2 as $row) {
            $transactions[] = $row;
        }

        // 3. LPSE Non-Tender
        $b3 = $db->table('lpse_nontender')
            ->select('kd_paket, nama_paket, nama_satker, tahun, nilai_kontrak as nilai, tgl_kontrak as tanggal, status, "Non-Tender" as sumber, metode')
            ->groupStart();
        if ($namaPenyedia !== '') {
            $b3->where('UPPER(TRIM(nama_penyedia))', strtoupper($namaPenyedia));
        }
        if ($kodePenyedia !== '') {
            $b3->orWhere('kd_penyedia', $kodePenyedia);
        }
        $b3->groupEnd();
        if ($tahun !== '' && $tahun !== 'all') {
            $b3->where('tahun', $tahun);
        }
        $r3 = $b3->get()->getResultArray();
        foreach ($r3 as $row) {
            $transactions[] = $row;
        }

        // 4. LPSE Pencatatan Non-Tender
        $b4 = $db->table('lpse_pencatatan_nontender')
            ->select('kd_paket, nama_paket, nama_satker, tahun, realisasi as nilai, created_at as tanggal, status, "Pencatatan Non-Tender" as sumber, jenis_realisasi as metode')
            ->groupStart();
        if ($namaPenyedia !== '') {
            $b4->where('UPPER(TRIM(nama_penyedia))', strtoupper($namaPenyedia));
        }
        if ($kodePenyedia !== '') {
            $b4->orWhere('kd_penyedia', $kodePenyedia);
        }
        $b4->groupEnd();
        if ($tahun !== '' && $tahun !== 'all') {
            $b4->where('tahun', $tahun);
        }
        $r4 = $b4->get()->getResultArray();
        foreach ($r4 as $row) {
            $transactions[] = $row;
        }

        // Sort by tahun DESC, nilai DESC
        usort($transactions, function($a, $b) {
            if ($a['tahun'] == $b['tahun']) {
                return ($b['nilai'] <=> $a['nilai']);
            }
            return ($b['tahun'] <=> $a['tahun']);
        });

        $totalNilai = 0;
        $bySumber = ['E-Katalog' => 0, 'Tender' => 0, 'Non-Tender' => 0, 'Pencatatan Non-Tender' => 0];
        $formatted = [];
        $no = 1;
        foreach ($transactions as $t) {
            $totalNilai += (float) ($t['nilai'] ?? 0);
            if (isset($bySumber[$t['sumber']])) {
                $bySumber[$t['sumber']]++;
            }

            $badgeClass = 'bg-secondary';
            if ($t['sumber'] === 'E-Katalog') $badgeClass = 'bg-success';
            elseif ($t['sumber'] === 'Tender') $badgeClass = 'bg-primary';
            elseif ($t['sumber'] === 'Non-Tender') $badgeClass = 'bg-warning text-dark';
            elseif ($t['sumber'] === 'Pencatatan Non-Tender') $badgeClass = 'bg-info';

            $formatted[] = [
                'no' => $no++,
                'sumber' => '<span class="badge ' . $badgeClass . '">' . $t['sumber'] . '</span>',
                'kd_paket' => htmlspecialchars($t['kd_paket'] ?? '-'),
                'nama_paket' => htmlspecialchars($t['nama_paket'] ?? '-'),
                'nama_satker' => htmlspecialchars($t['nama_satker'] ?? '-'),
                'tahun' => $t['tahun'] ?? '-',
                'nilai' => (float) ($t['nilai'] ?? 0),
                'nilai_formatted' => 'Rp ' . number_format((float) ($t['nilai'] ?? 0), 0, ',', '.'),
                'status' => htmlspecialchars($t['status'] ?? '-'),
                'metode' => htmlspecialchars($t['metode'] ?? '-')
            ];
        }

        return $this->response->setJSON([
            'status' => 'success',
            'summary' => [
                'total_paket' => count($transactions),
                'total_nilai' => $totalNilai,
                'total_nilai_formatted' => 'Rp ' . number_format($totalNilai, 0, ',', '.'),
                'by_sumber' => $bySumber
            ],
            'data' => $formatted
        ]);
    }

    public function getProfilPenyedia()
    {
        if (!has_permission('detail_penyedia') && !has_permission('penyedia-katalog')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $namaPenyedia = trim($this->request->getGet('nama_penyedia') ?? '');
        $kodePenyedia = trim($this->request->getGet('kode_penyedia') ?? '');

        $db = \Config\Database::connect();
        $profile = [
            'nama_penyedia' => $namaPenyedia,
            'kode_penyedia' => $kodePenyedia,
            'bentuk_usaha' => null,
            'jenis_perusahaan' => null,
            'npwp_penyedia' => null,
            'alamat_penyedia' => null,
            'email' => null,
            'telepon' => null,
            'nib' => null,
            'status_aktif' => null,
            'status_umkk' => null,
            'kbli_name' => null,
            'kbli_id' => null,
            'sumber_data' => []
        ];

        // 1. Try Inaproc DB cache first
        $inaprocRow = null;
        if ($kodePenyedia !== '') {
            $inaprocRow = $db->table('inaproc_vendor_sync')
                ->where('kode_penyedia', $kodePenyedia)
                ->get()->getFirstRow('array');
        }
        if (!$inaprocRow && $namaPenyedia !== '') {
            $inaprocRow = $db->table('inaproc_vendor_sync')
                ->where('UPPER(TRIM(nama_penyedia))', strtoupper($namaPenyedia))
                ->get()->getFirstRow('array');
        }

        if ($inaprocRow) {
            $isSikap = (!empty($inaprocRow['last_sync_status']) && in_array($inaprocRow['last_sync_status'], ['sikap_sync', 'sikap_csv'])) ||
                       (isset($inaprocRow['kode_penyedia']) && strpos($inaprocRow['kode_penyedia'], 'SIKAP_') === 0);
            $profile['sumber_data'][] = $isSikap ? 'SIKaP LKPP' : 'Inaproc LKPP';
            foreach (['nama_penyedia', 'bentuk_usaha', 'jenis_perusahaan', 'npwp_penyedia', 'alamat_penyedia', 'email', 'telepon', 'nib', 'status_aktif', 'status_umkk', 'kbli_name', 'kbli_id'] as $f) {
                if (!empty($inaprocRow[$f])) {
                    $profile[$f] = $inaprocRow[$f];
                }
            }
            if (!empty($inaprocRow['kode_penyedia'])) {
                $profile['kode_penyedia'] = $inaprocRow['kode_penyedia'];
            }
            // Check raw_response if address/email/telepon are missing
            if ((empty($profile['alamat_penyedia']) || empty($profile['email'])) && !empty($inaprocRow['raw_response'])) {
                $raw = json_decode($inaprocRow['raw_response'], true);
                if (is_array($raw)) {
                    if (empty($profile['alamat_penyedia']) && !empty($raw['alamat_penyedia'])) $profile['alamat_penyedia'] = $raw['alamat_penyedia'];
                    if (empty($profile['email']) && !empty($raw['email'])) $profile['email'] = $raw['email'];
                    if (empty($profile['telepon']) && !empty($raw['telepon'])) $profile['telepon'] = $raw['telepon'];
                    if (empty($profile['nib']) && !empty($raw['nib'])) $profile['nib'] = $raw['nib'];
                }
            }
        } elseif ($kodePenyedia !== '' && strlen($kodePenyedia) > 10 && !is_numeric($kodePenyedia)) {
            // Try fetching from Inaproc API service
            $apiRes = $this->inaprocApiService->getVendorDetail($kodePenyedia);
            if (!empty($apiRes['ok']) && !empty($apiRes['data'])) {
                $profile['sumber_data'][] = 'Inaproc API';
                $apiData = $apiRes['data'];
                foreach (['nama_penyedia', 'bentuk_usaha', 'jenis_perusahaan', 'npwp_penyedia', 'alamat_penyedia', 'email', 'telepon', 'nib', 'status_aktif', 'status_umkk', 'kbli_name', 'kbli_id'] as $f) {
                    if (!empty($apiData[$f])) {
                        $profile[$f] = $apiData[$f];
                    }
                }
            }
        }

        // 2. Fallback / Merge from LPSE Tender, Non-Tender, Pencatatan
        $lpseRows = [];
        if ($namaPenyedia !== '') {
            // Tender
            $tRow = $db->table('lpse_tender')
                ->select('kd_penyedia, npwp_penyedia, alamat_penyedia, bentuk_usaha')
                ->where('UPPER(TRIM(nama_penyedia))', strtoupper($namaPenyedia))
                ->where('(npwp_penyedia IS NOT NULL OR alamat_penyedia IS NOT NULL)')
                ->limit(1)->get()->getFirstRow('array');
            if ($tRow) $lpseRows[] = $tRow;

            // Non-Tender
            $ntRow = $db->table('lpse_nontender')
                ->select('kd_penyedia, npwp_penyedia, alamat_penyedia, bentuk_usaha')
                ->where('UPPER(TRIM(nama_penyedia))', strtoupper($namaPenyedia))
                ->where('(npwp_penyedia IS NOT NULL OR alamat_penyedia IS NOT NULL)')
                ->limit(1)->get()->getFirstRow('array');
            if ($ntRow) $lpseRows[] = $ntRow;

            // Pencatatan Non-Tender
            $pctRow = $db->table('lpse_pencatatan_nontender')
                ->select('kd_penyedia, npwp_penyedia')
                ->where('UPPER(TRIM(nama_penyedia))', strtoupper($namaPenyedia))
                ->where('npwp_penyedia IS NOT NULL')
                ->limit(1)->get()->getFirstRow('array');
            if ($pctRow) $lpseRows[] = $pctRow;
        }

        foreach ($lpseRows as $lr) {
            $hasNewInfo = false;
            if (empty($profile['npwp_penyedia']) && !empty($lr['npwp_penyedia'])) {
                $profile['npwp_penyedia'] = $lr['npwp_penyedia'];
                $hasNewInfo = true;
            }
            if (empty($profile['alamat_penyedia']) && !empty($lr['alamat_penyedia'])) {
                $profile['alamat_penyedia'] = $lr['alamat_penyedia'];
                $hasNewInfo = true;
            }
            if (empty($profile['bentuk_usaha']) && !empty($lr['bentuk_usaha'])) {
                $profile['bentuk_usaha'] = $lr['bentuk_usaha'];
                $hasNewInfo = true;
            }
            if (empty($profile['kode_penyedia']) && !empty($lr['kd_penyedia'])) {
                $profile['kode_penyedia'] = $lr['kd_penyedia'];
                $hasNewInfo = true;
            }
            if ($hasNewInfo && !in_array('Data Kontrak LPSE', $profile['sumber_data'])) {
                $profile['sumber_data'][] = 'Data Kontrak LPSE';
            }
        }

        // 3. Fallback / Enrichment langsung dari referensi CSV SIKaP jika alamat masih kosong atau tersensor (*****)
        if (empty($profile['alamat_penyedia']) || strpos($profile['alamat_penyedia'], '*****') !== false || empty($profile['npwp_penyedia'])) {
            $csvPath = ROOTPATH . 'Hasil_Bot_SIKaP.csv';
            if (!is_file($csvPath)) $csvPath = FCPATH . 'Hasil_Bot_SIKaP.csv';
            if (is_file($csvPath) && ($handle = fopen($csvPath, 'r')) !== false) {
                $header = fgetcsv($handle, 0, ';');
                $hIdx = [];
                if ($header) {
                    foreach ($header as $i => $h) {
                        $h = strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', $h)));
                        $hIdx[$h] = $i;
                    }
                }
                $targetNama = strtoupper(trim($namaPenyedia));
                $normTarget = preg_replace('/[^A-Z0-9]/', '', preg_replace('/^(PT|CV|UD|PO|KOPERASI|TOKO)\.?\s+/i', '', $targetNama));
                $targetNpwp = preg_replace('/[^0-9]/', '', $profile['npwp_penyedia'] ?? '');

                while (($r = fgetcsv($handle, 0, ';')) !== false) {
                    $rowNama = trim($r[$hIdx['nama perusahaan / kontraktor'] ?? ($hIdx['nama'] ?? ($hIdx['nama_penyedia'] ?? 1))] ?? '');
                    $rowNpwp = trim($r[$hIdx['npwp'] ?? 2] ?? '');
                    $cleanRowNpwp = preg_replace('/[^0-9]/', '', $rowNpwp);
                    $rowUpper = strtoupper($rowNama);
                    $rowNorm = preg_replace('/[^A-Z0-9]/', '', preg_replace('/^(PT|CV|UD|PO|KOPERASI|TOKO)\.?\s+/i', '', $rowUpper));

                    $isMatch = false;
                    // 1. Prioritas utama: Cocokkan Nama Perusahaan / Kontraktor
                    if ($targetNama !== '' && ($targetNama === $rowUpper || ($normTarget !== '' && $normTarget === $rowNorm))) {
                        $isMatch = true;
                    } elseif ($targetNpwp !== '' && $cleanRowNpwp !== '' && !str_starts_with($cleanRowNpwp, '100000000') && !str_starts_with($targetNpwp, '100000000')) {
                        // Jangan pernah mencocokkan NPWP semata jika berawalan 100000000 (karena ratusan PT/CV baru berbagi prefix ini)
                        if (strlen($cleanRowNpwp) >= 9 && strpos($targetNpwp, substr($cleanRowNpwp, 0, 9)) === 0) {
                            $isMatch = true;
                        }
                    }

                    if ($isMatch) {
                        $sikapAlamat = trim($r[$hIdx['alamat'] ?? -1] ?? '');
                        $kab = trim($r[$hIdx['kabupaten / kota'] ?? ($hIdx['kabupaten_kota'] ?? ($hIdx['kabupaten/kota'] ?? 3))] ?? '');
                        $prov = trim($r[$hIdx['provinsi'] ?? 4] ?? 'Nusa Tenggara Barat');
                        $detailUrl = trim($r[$hIdx['detail sbu url'] ?? ($hIdx['detail_url'] ?? 5)] ?? '');
                        
                        if (empty($sikapAlamat) && !empty($kab)) {
                            $sikapAlamat = $kab . (!empty($prov) ? ', ' . $prov : '');
                        }
                        if ($sikapAlamat !== '') {
                            $profile['alamat_penyedia'] = $sikapAlamat;
                            $profile['domisili_kota'] = $kab;
                        }
                        if (empty($profile['npwp_penyedia']) && $rowNpwp !== '') $profile['npwp_penyedia'] = $rowNpwp;
                        if (empty($profile['bentuk_usaha'])) $profile['bentuk_usaha'] = trim($r[$hIdx['bentuk_usaha'] ?? -1] ?? '');
                        if (empty($profile['email'])) $profile['email'] = trim($r[$hIdx['email'] ?? -1] ?? '');
                        if (empty($profile['telepon'])) $profile['telepon'] = trim($r[$hIdx['telepon'] ?? -1] ?? '');
                        if (!in_array('LPJK PUPR / SIKaP LKPP', $profile['sumber_data'])) {
                            $profile['sumber_data'][] = 'LPJK PUPR / SIKaP LKPP';
                        }
                        if (!empty($detailUrl)) {
                            $profile['detail_url'] = $detailUrl;
                        }
                        break;
                    }
                }
                fclose($handle);
            }
        }

        // 4. Fallback / Enrichment dari pengadaanpro_contractors (Database Kontraktor Nasional)
        try {
            if ($db->tableExists('pengadaanpro_contractors')) {
                $clean = preg_replace('/^(PT|CV|UD|PO|KOPERASI|TOKO)\.?\s*/i', '', $targetNama);
                $clean = preg_replace('/[,.\s]+(PT|CV|UD|PO|KOPERASI|TBK|PERSERO)\.?$/i', '', $clean);
                $normTarget = preg_replace('/[^A-Z0-9]/', '', $clean);
                $targetNpwp = preg_replace('/[^0-9]/', '', $profile['npwp_penyedia'] ?? '');

                $proRow = null;
                if ($normTarget !== '') {
                    $proRow = $db->table('pengadaanpro_contractors')->where('contractor_name_normalized', strtolower($normTarget))->limit(1)->get()->getFirstRow('array');
                }
                if (!$proRow && $targetNama !== '') {
                    $proRow = $db->table('pengadaanpro_contractors')->where('UPPER(TRIM(contractor_name))', $targetNama)->limit(1)->get()->getFirstRow('array');
                }
                if (!$proRow && $targetNpwp !== '') {
                    $proRow = $db->table('pengadaanpro_contractors')->where('npwp', $targetNpwp)->limit(1)->get()->getFirstRow('array');
                }

                if ($proRow) {
                    if (empty($profile['npwp_penyedia']) && !empty($proRow['npwp'])) $profile['npwp_penyedia'] = $proRow['npwp'];
                    if (empty($profile['bentuk_usaha']) && !empty($proRow['bentuk_usaha'])) $profile['bentuk_usaha'] = $proRow['bentuk_usaha'];
                    if (empty($profile['nib']) && !empty($proRow['nib'])) $profile['nib'] = $proRow['nib'];
                    if (!empty($proRow['domisili_kota'])) $profile['domisili_kota'] = $proRow['domisili_kota'];
                    if ((empty($profile['alamat_penyedia']) || strpos($profile['alamat_penyedia'], '*****') !== false) && !empty($proRow['address'])) {
                        $profile['alamat_penyedia'] = $proRow['address'];
                    } elseif (empty($profile['alamat_penyedia']) && !empty($proRow['province'])) {
                        $profile['alamat_penyedia'] = (!empty($proRow['domisili_kota']) ? $proRow['domisili_kota'] . ', ' : '') . $proRow['province'];
                    }
                    if (empty($profile['telepon']) && !empty($proRow['phone'])) $profile['telepon'] = $proRow['phone'];
                    if (empty($profile['email']) && !empty($proRow['email'])) $profile['email'] = $proRow['email'];
                    if (!empty($proRow['tanggal_daftar'])) $profile['tanggal_daftar'] = $proRow['tanggal_daftar'];
                    if (!empty($proRow['sertifikat_elektronik'])) $profile['sertifikat_elektronik'] = $proRow['sertifikat_elektronik'];
                    if (!empty($proRow['direktur'])) $profile['direktur'] = $proRow['direktur'];
                    if (!empty($proRow['detail_url']) && empty($profile['detail_url'])) $profile['detail_url'] = $proRow['detail_url'];
                    if (!in_array('SPSE Lombok Barat (Verifikasi Rekanan)', $profile['sumber_data'])) {
                        $profile['sumber_data'][] = 'SPSE Lombok Barat (Verifikasi Rekanan)';
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Error in getProfilPenyedia pengadaanpro check: ' . $e->getMessage());
        }

        helper('ajip');
        $checkLocText = trim(($profile['alamat_penyedia'] ?? '') . ' ' . ($profile['domisili_kota'] ?? ''));
        $locInfo = classify_npwp_location($profile['npwp_penyedia'] ?? '', $checkLocText, $profile['provinsi'] ?? '');
        
        if (!empty($profile['domisili_kota'])) {
            $uDom = strtoupper($profile['domisili_kota']);
            $cleanDom = preg_replace('/^(KAB\.|KOTA)\s+/i', '', $profile['domisili_kota']);
            if (strpos($uDom, 'MATARAM') !== false) {
                $locInfo['lokasi'] = 'Mataram';
                $locInfo['flag'] = 'Mataram';
                $locInfo['flag_kategori'] = 'mataram';
                $locInfo['badge'] = '<span class="badge bg-label-primary"><i class="bx bx-buildings me-1"></i>Mataram</span>';
            } elseif (strpos($uDom, 'LOMBOK BARAT') !== false) {
                $locInfo['lokasi'] = 'Lombok Barat';
                $locInfo['flag'] = 'Lombok Barat';
                $locInfo['flag_kategori'] = 'lobar';
                $locInfo['badge'] = '<span class="badge bg-label-success"><i class="bx bx-map-pin me-1"></i>Lombok Barat</span>';
            } elseif (strpos($uDom, 'LOMBOK TENGAH') !== false) {
                $locInfo['lokasi'] = 'Lombok Tengah';
                $locInfo['flag'] = 'Lombok Tengah';
                $locInfo['flag_kategori'] = 'ntb_lain';
                $locInfo['badge'] = '<span class="badge bg-label-info"><i class="bx bx-map-pin me-1"></i>Lombok Tengah</span>';
            } elseif (strpos($uDom, 'LOMBOK TIMUR') !== false) {
                $locInfo['lokasi'] = 'Lombok Timur';
                $locInfo['flag'] = 'Lombok Timur';
                $locInfo['flag_kategori'] = 'ntb_lain';
                $locInfo['badge'] = '<span class="badge bg-label-info"><i class="bx bx-map-pin me-1"></i>Lombok Timur</span>';
            } elseif (strpos($uDom, 'LOMBOK UTARA') !== false) {
                $locInfo['lokasi'] = 'Lombok Utara';
                $locInfo['flag'] = 'Lombok Utara';
                $locInfo['flag_kategori'] = 'ntb_lain';
                $locInfo['badge'] = '<span class="badge bg-label-info"><i class="bx bx-map-pin me-1"></i>Lombok Utara</span>';
            } elseif (strpos($uDom, 'SUMBAWA BARAT') !== false) {
                $locInfo['lokasi'] = 'Sumbawa Barat';
                $locInfo['flag'] = 'Sumbawa Barat';
                $locInfo['flag_kategori'] = 'ntb_lain';
                $locInfo['badge'] = '<span class="badge bg-label-info"><i class="bx bx-map-pin me-1"></i>Sumbawa Barat</span>';
            } elseif (strpos($uDom, 'SUMBAWA') !== false) {
                $locInfo['lokasi'] = 'Sumbawa';
                $locInfo['flag'] = 'Sumbawa';
                $locInfo['flag_kategori'] = 'ntb_lain';
                $locInfo['badge'] = '<span class="badge bg-label-info"><i class="bx bx-map-pin me-1"></i>Sumbawa</span>';
            } elseif (strpos($uDom, 'BIMA') !== false) {
                $locInfo['lokasi'] = 'Bima';
                $locInfo['flag'] = 'Bima';
                $locInfo['flag_kategori'] = 'ntb_lain';
                $locInfo['badge'] = '<span class="badge bg-label-info"><i class="bx bx-map-pin me-1"></i>Bima</span>';
            } elseif (strpos($uDom, 'DOMPU') !== false) {
                $locInfo['lokasi'] = 'Dompu';
                $locInfo['flag'] = 'Dompu';
                $locInfo['flag_kategori'] = 'ntb_lain';
                $locInfo['badge'] = '<span class="badge bg-label-info"><i class="bx bx-map-pin me-1"></i>Dompu</span>';
            } else {
                $locInfo['lokasi'] = $cleanDom;
                $locInfo['flag'] = $cleanDom;
                $locInfo['flag_kategori'] = ($profile['provinsi'] ?? '') === 'Nusa Tenggara Barat' ? 'ntb_lain' : 'luar';
                $locInfo['badge'] = '<span class="badge bg-label-secondary"><i class="bx bx-map-pin me-1"></i>' . htmlspecialchars($cleanDom) . '</span>';
            }
            if (empty($locInfo['kpp']) || $locInfo['kpp'] === '-') {
                $locInfo['kpp'] = 'Alamat Fisik (' . $cleanDom . ')';
                $locInfo['keterangan'] = 'Domisili di ' . $cleanDom;
            }
        }

        $profile['kpp_code'] = $locInfo['kpp_code'];
        $profile['kpp_nama'] = $locInfo['kpp'];
        $profile['lokasi_perusahaan'] = $locInfo['lokasi'];
        $profile['flag_lokasi'] = $locInfo['flag'];
        $profile['lokasi_badge'] = $locInfo['badge'];
        $profile['lokasi_keterangan'] = $locInfo['keterangan'];

        $profile['sumber_data_str'] = !empty($profile['sumber_data']) ? implode(' & ', $profile['sumber_data']) : 'Data Transaksi Lokal';

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $profile
        ]);
    }

    /**
     * AI-Assisted Domicile Detection Endpoint
     * Detects regency/city and province from company address using configured LLM
     */
    public function detectDomisiliAi()
    {
        if (!has_permission('detail_penyedia') && !has_permission('penyedia-katalog') && session()->get('role') !== 'admin') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Akses ditolak (Unauthorized).'])->setStatusCode(403);
        }

        $nama = trim($this->request->getPost('nama_penyedia') ?? $this->request->getGet('nama_penyedia') ?? '');
        $kode = trim($this->request->getPost('kode_penyedia') ?? $this->request->getGet('kode_penyedia') ?? '');
        $npwp = trim($this->request->getPost('npwp') ?? $this->request->getGet('npwp') ?? '');
        $alamat = trim($this->request->getPost('alamat_penyedia') ?? $this->request->getGet('alamat_penyedia') ?? '');

        if (empty($alamat) && empty($nama)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Alamat atau nama penyedia tidak boleh kosong.']);
        }

        $settingsModel = new \App\Models\SettingsModel();
        $apiKey = $settingsModel->getSetting('chatbot_api_key', env('OPENAI_API_KEY'));
        $model = $settingsModel->getSetting('chatbot_model', 'qwen-plus-character');
        $apiUrl = $settingsModel->getSetting('chatbot_api_url', 'https://api.openai.com/v1/chat/completions');

        if (empty($apiKey)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'API Key AI belum dikonfigurasi pada menu Chatbot / Settings.']);
        }

        // Auto-format API URL to chat endpoint
        $apiUrl = rtrim($apiUrl, '/');
        if (!preg_match('/\/chat\/completions$/i', $apiUrl)) {
            $apiUrl .= '/chat/completions';
        }

        $systemPrompt = "Anda adalah asisten data geografi Indonesia untuk sistem pengadaan barang dan jasa pemerintah (LPSE / Inaproc / InfoAJIP Lombok Barat).
Tugas Anda: Menganalisis nama perusahaan dan teks alamat yang diberikan, lalu mengekstrak secara presisi Kabupaten/Kota dan Provinsi resmi di Indonesia.

PANDUAN PENTING & AKURASI:
1. Abaikan teks akhiran template unit kerja LPSE seperti ', Lombok Barat (Kab.), Nusa Tenggara Barat' atau 'Lombok Barat' jika alamat jalan/kelurahan/kecamatan/kota sebenarnya berada di wilayah lain (misal: Cakranegara Mataram, Pancoran Jakarta Selatan, Selong Lombok Timur, Surabaya, dll).
2. IDENTIFIKASI LANDMARK, GEDUNG PERKANTORAN, & SENSOR BINTANG SECARA AKURAT:
   - Jika alamat menyebut nama gedung perkantoran, tower, komplek bisnis, atau landmark terkenal di Indonesia, petakan secara tepat sesuai lokasi nyata gedung tersebut di Indonesia.
   - Contoh nyata: 'L\'Avenue' / 'L\'Avenue Office Tower' / 'L\'Avenue Residences' / 'L\'avenue o*****' berada di Jl. Raya Pasar Minggu Kav. 16, Pancoran, Kota Jakarta Selatan, DKI Jakarta (JANGAN berhalusinasi mengaitkan ke BSD City atau wilayah lain yang tidak nyata).
   - Jika teks alamat tersensor bintang (misal 'L\'avenue o*****'), rekonstruksi kata tersebut berdasarkan landmark nyata dan nama perusahaannya. Perusahaan seperti Standard Biosensor Healthcare / Indonesia berkantor pusat di L'Avenue Office Tower, Pancoran, Kota Jakarta Selatan.
3. Tentukan 'flag_lokasi' dengan aturan persis berikut:
   - 'lobar'      : Jika berada di Kabupaten Lombok Barat (Gerung, Kuripan, Labuapi, Kediri, Narmada, Lingsar, Gunungsari, Batulayar, Lembar, Sekotong, dll).
   - 'mataram'    : Jika berada di Kota Mataram (Ampenan, Mataram, Cakranegara, Sekarbela, Selaparang, Sandubaya, dll).
   - 'ntb_lain'   : Jika berada di wilayah NTB lainnya (Lombok Tengah, Lombok Timur, Lombok Utara, Sumbawa, Sumbawa Barat, Dompu, Bima).
   - 'luar'       : Jika berada di luar provinsi Nusa Tenggara Barat (Jakarta, Tangerang, Surabaya, Bandung, Bali, dll).
4. Format output WAJIB HANYA berupa JSON murni tanpa markdown, tanpa backtick ```json, dengan struktur:
{
  \"kabupaten_kota\": \"Kab. Lombok Barat / Kota Mataram / Kota Jakarta Selatan / dll\",
  \"provinsi\": \"Nusa Tenggara Barat / DKI Jakarta / dll\",
  \"flag_lokasi\": \"lobar / mataram / ntb_lain / luar\",
  \"keterangan\": \"Penjelasan singkat mengapa dipilih wilayah tersebut\"
}";

        $userPrompt = "Nama Perusahaan: {$nama}\nNPWP: {$npwp}\nAlamat Tertera: {$alamat}\n\nTentukan Kabupaten/Kota, Provinsi, dan flag_lokasi yang tepat dalam format JSON.";

        $postData = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ],
            'temperature' => 0.1,
            'max_tokens' => 250,
            'enable_search' => true
        ];

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
            'api-key: ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menghubungi server AI: ' . $curlError]);
        }

        $resArr = json_decode($response, true);
        $content = $resArr['choices'][0]['message']['content'] ?? '';

        // Clean any markdown formatting
        $content = preg_replace('/^```(?:json)?\s*/i', '', trim($content));
        $content = preg_replace('/\s*```$/', '', $content);

        $parsed = json_decode($content, true);
        if (!$parsed || empty($parsed['kabupaten_kota'])) {
            return $this->response->setJSON([
                'status' => 'error', 
                'message' => 'AI tidak dapat mengenali pola alamat.',
                'raw_ai' => $content
            ]);
        }

        $kabKota = trim($parsed['kabupaten_kota']);
        $provinsi = trim($parsed['provinsi'] ?? '');
        $flagLokasi = trim($parsed['flag_lokasi'] ?? 'unknown');
        $keterangan = trim($parsed['keterangan'] ?? '');

        // Badge styling (Tanpa ikon/simbol)
        $badgeClass = 'bg-label-secondary';
        $displayDom = preg_replace('/^(KAB\.|KOTA)\s+/i', '', $kabKota);

        if ($flagLokasi === 'lobar') {
            $badgeClass = 'bg-label-success';
            $displayDom = 'Lombok Barat';
        } elseif ($flagLokasi === 'mataram') {
            $badgeClass = 'bg-label-primary';
            $displayDom = 'Mataram';
        } elseif ($flagLokasi === 'ntb_lain') {
            $badgeClass = 'bg-label-info';
            if (strpos(strtoupper($kabKota), 'LOMBOK TIMUR') !== false) $displayDom = 'Lombok Timur';
            elseif (strpos(strtoupper($kabKota), 'LOMBOK TENGAH') !== false) $displayDom = 'Lombok Tengah';
            elseif (strpos(strtoupper($kabKota), 'LOMBOK UTARA') !== false) $displayDom = 'Lombok Utara';
            elseif (strpos(strtoupper($kabKota), 'SUMBAWA') !== false) $displayDom = 'Sumbawa';
            elseif (strpos(strtoupper($kabKota), 'BIMA') !== false) $displayDom = 'Bima';
            elseif (strpos(strtoupper($kabKota), 'DOMPU') !== false) $displayDom = 'Dompu';
        }

        $badgeHtml = '<span class="badge ' . $badgeClass . ' py-1 px-2">' . htmlspecialchars($displayDom) . '</span>';

        // Update database: inaproc_vendor_sync
        $db = \Config\Database::connect();
        try {
            if ($db->tableExists('inaproc_vendor_sync')) {
                $bInaproc = $db->table('inaproc_vendor_sync');
                if (!empty($kode)) {
                    $bInaproc->groupStart()->where('kode_penyedia', $kode);
                    if (!empty($npwp)) $bInaproc->orWhere('npwp_penyedia', $npwp);
                    $bInaproc->groupEnd();
                } elseif (!empty($npwp)) {
                    $bInaproc->where('npwp_penyedia', $npwp);
                } elseif (!empty($nama)) {
                    $bInaproc->where('UPPER(TRIM(nama_penyedia))', strtoupper($nama));
                }
                $bInaproc->update([
                    'lokasi_perusahaan' => $kabKota,
                    'flag_lokasi' => $flagLokasi,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }

            if ($db->tableExists('pengadaanpro_contractors')) {
                $bCont = $db->table('pengadaanpro_contractors');
                if (!empty($npwp)) {
                    $bCont->where('npwp', $npwp);
                } elseif (!empty($nama)) {
                    $bCont->where('UPPER(TRIM(contractor_name))', strtoupper($nama));
                }
                $bCont->update([
                    'domisili_kota' => $kabKota,
                    'province' => $provinsi,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Error updating domisili AI to DB: ' . $e->getMessage());
        }

        // Simpan hasil AI ke cache agar tabel Detail Penyedia memakainya (berdasarkan alamat)
        try {
            $this->_ensureDomisiliAiCache($db);
            $cacheRow = [
                'nama_key' => strtoupper(trim($nama)),
                'alamat_hash' => md5(strtolower($alamat)),
                'kabupaten_kota' => $kabKota,
                'provinsi' => $provinsi,
                'flag_lokasi' => $flagLokasi,
                'keterangan' => $keterangan,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $db->table('domisili_ai_cache')->replace($cacheRow);
        } catch (\Throwable $e) {
            log_message('error', 'Error saving domisili_ai_cache: ' . $e->getMessage());
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Domisili berhasil disinkronkan oleh AI!',
            'data' => [
                'kabupaten_kota' => $kabKota,
                'provinsi' => $provinsi,
                'flag_lokasi' => $flagLokasi,
                'domisili' => $displayDom,
                'badge' => $badgeHtml,
                'keterangan' => $keterangan
            ]
        ]);
    }

    public function syncVendorDetails()
    {
        if (!has_permission('penyedia-katalog')) {
            return $this->response->setJSON(['error' => 'Unauthorized']);
        }

        $kode = trim($this->request->getGet('kode_penyedia') ?? '');
        $service = new InaprocApiService();

        if ($kode !== '') {
            $result = $service->syncVendorDetail($kode, true);
            return $this->response->setJSON([
                'ok' => $result['ok'],
                'data' => $result['data'] ?? null,
                'error' => $result['error'] ?? null,
                'source' => $result['source'] ?? 'api',
            ]);
        }

        $db = \Config\Database::connect();
        $rows = $db->table('epurchasing')
            ->select('DISTINCT kode_penyedia')
            ->where('kode_penyedia IS NOT NULL')
            ->where('kode_penyedia !=', '')
            ->get()
            ->getResultArray();

        $processed = [];
        foreach ($rows as $row) {
            $code = trim($row['kode_penyedia']);
            if ($code === '') {
                continue;
            }
            $processed[] = [
                'kode_penyedia' => $code,
                'result' => $service->syncVendorDetail($code, true),
            ];
        }

        return $this->response->setJSON([
            'ok' => true,
            'count' => count($processed),
            'data' => $processed,
        ]);
    }

    public function fetchApiPenyedia($kode_penyedia)
    {
        if (!has_permission('detail_penyedia') && !has_permission('penyedia-katalog')) {
            return $this->response->setJSON(['error' => 'Unauthorized']);
        }

        // Delegate to unified getProfilPenyedia
        $nama = trim($this->request->getGet('nama_penyedia') ?? '');
        $res = $this->inaprocApiService->getVendorDetail($kode_penyedia);

        if (!empty($res['ok'])) {
            return $this->response->setJSON($res['data']);
        }

        // Fallback to getProfilPenyedia logic
        $db = \Config\Database::connect();
        $profile = [
            'kode_penyedia' => $kode_penyedia,
            'nama_penyedia' => $nama,
            'bentuk_usaha' => null,
            'npwp_penyedia' => null,
            'alamat_penyedia' => null
        ];

        if ($nama !== '') {
            $row = $db->table('lpse_nontender')
                ->select('kd_penyedia, npwp_penyedia, alamat_penyedia, bentuk_usaha')
                ->where('UPPER(TRIM(nama_penyedia))', strtoupper($nama))
                ->limit(1)->get()->getFirstRow('array');
            if (!$row) {
                $row = $db->table('lpse_tender')
                    ->select('kd_penyedia, npwp_penyedia, alamat_penyedia, bentuk_usaha')
                    ->where('UPPER(TRIM(nama_penyedia))', strtoupper($nama))
                    ->limit(1)->get()->getFirstRow('array');
            }
            if ($row) {
                $profile = array_merge($profile, array_filter($row));
                return $this->response->setJSON($profile);
            }
        }

        return $this->response->setJSON([
            'error' => $res['error'] ?? 'API unavailable',
            'detail' => $res['detail'] ?? null,
            'source' => $res['source'] ?? 'api',
        ]);
    }

    public function exportPenyediaKatalog()
    {
        if (!has_permission('penyedia-katalog')) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses.');
        }

        $tahun = $this->request->getGet('tahun') ?: date('Y');
        $db = \Config\Database::connect();

        $builder = $db->table('epurchasing')
            ->select('nama_penyedia, GROUP_CONCAT(DISTINCT COALESCE(NULLIF(nama_produk, ""), nama_paket) SEPARATOR " | ") as produk, SUM(total_harga) as total_transaksi, COUNT(id) as total_paket')
            ->where('tahun', $tahun)
            ->where('nama_penyedia IS NOT NULL')
            ->where('nama_penyedia !=', '')
            ->groupBy('nama_penyedia')
            ->orderBy('total_transaksi', 'DESC');

        $results = $builder->get()->getResultArray();

        // Group data in PHP to handle case sensitivity and spaces differences
        $groupedData = [];
        foreach ($results as $row) {
            $namaPenyedia = strtoupper(trim($row['nama_penyedia']));
            if (!isset($groupedData[$namaPenyedia])) {
                $groupedData[$namaPenyedia] = [
                    'nama_penyedia' => $namaPenyedia,
                    'produk' => $row['produk'],
                    'total_transaksi' => $row['total_transaksi'],
                    'total_paket' => $row['total_paket']
                ];
            } else {
                $groupedData[$namaPenyedia]['produk'] .= ' | ' . $row['produk'];
                $groupedData[$namaPenyedia]['total_transaksi'] += $row['total_transaksi'];
                $groupedData[$namaPenyedia]['total_paket'] += $row['total_paket'];
            }
        }

        // Sort by total_transaksi descending
        usort($groupedData, function ($a, $b) {
            return $b['total_transaksi'] <=> $a['total_transaksi'];
        });

        // Panggil AI untuk kategorisasi
        $aiCategories = $this->_categorizeWithAI($groupedData, ' | ');

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Penyedia Katalog ' . $tahun);

        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Nama Penyedia');
        $sheet->setCellValue('C1', 'Kategori Prediksi');
        $sheet->setCellValue('D1', 'Total Paket');
        $sheet->setCellValue('E1', 'Total Transaksi (Rp)');
        $sheet->setCellValue('F1', 'Daftar Produk / Pekerjaan');

        // Header styling
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['type' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['argb' => 'FF4F81BD']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ];
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

        $rowNum = 2;
        $no = 1;
        foreach ($groupedData as $dataItem) {
            $namaPenyedia = $dataItem['nama_penyedia'];
            $produkRaw = explode(' | ', $dataItem['produk']);
            $produkRaw = array_unique(array_filter(array_map('trim', $produkRaw)));

            // Cek apakah AI memberikan kategori yang valid
            $kategoriUtama = $aiCategories[$namaPenyedia] ?? null;
            $validKategori = ['Makanan & Minuman', 'Elektronik & IT', 'Alat Tulis Kantor (ATK)', 'Internet & Jaringan', 'Kesehatan & Medis', 'Konstruksi & Material', 'Kendaraan & Transportasi', 'Mebel & Furniture', 'Jasa', 'Lainnya'];

            if (!$kategoriUtama || !in_array($kategoriUtama, $validKategori)) {
                // Kategori logic fallback
                $kategoriCounter = [
                    'Makanan & Minuman' => 0,
                    'Elektronik & IT' => 0,
                    'Alat Tulis Kantor (ATK)' => 0,
                    'Internet & Jaringan' => 0,
                    'Kesehatan & Medis' => 0,
                    'Konstruksi & Material' => 0,
                    'Kendaraan & Transportasi' => 0,
                    'Mebel & Furniture' => 0,
                    'Jasa' => 0,
                    'Lainnya' => 0
                ];
                foreach ($produkRaw as $p) {
                    $pLower = strtolower($p);
                    if (preg_match('/(makan|minum|katering|nasi|snack|kopi|kue|catering)/', $pLower))
                        $kategoriCounter['Makanan & Minuman']++;
                    elseif (preg_match('/(kesehatan|obat|medis|alkes|masker|vaksin|oksigen|klinik|rsud|puskesmas|pasien|dokter)/', $pLower))
                        $kategoriCounter['Kesehatan & Medis']++;
                    elseif (preg_match('/(internet|bandwidth|wifi|fiber|kabel data|jaringan|server|hosting|domain|cloud)/', $pLower))
                        $kategoriCounter['Internet & Jaringan']++;
                    elseif (preg_match('/(laptop|komputer|pc|printer|scanner|mouse|keyboard|flashdisk|proyektor|cctv|tv|televisi|kamera|elektronik|vidiotron|videotron)/', $pLower))
                        $kategoriCounter['Elektronik & IT']++;
                    elseif (preg_match('/(kertas|tinta|pulpen|spidol|buku|map|toner|atk|fotokopi|cetak|penggandaan)/', $pLower))
                        $kategoriCounter['Alat Tulis Kantor (ATK)']++;
                    elseif (preg_match('/(semen|besi|pasir|batu|cat|paku|pipa|beton|aspal|konstruksi|bangunan|rehabilitasi|pemeliharaan)/', $pLower))
                        $kategoriCounter['Konstruksi & Material']++;
                    elseif (preg_match('/(motor|mobil|sepeda|ban|oli|bensin|bbm|kendaraan|suku cadang)/', $pLower))
                        $kategoriCounter['Kendaraan & Transportasi']++;
                    elseif (preg_match('/(meja|kursi|lemari|rak|sofa|mebel|furniture|pendingin|ac |air conditioner)/', $pLower))
                        $kategoriCounter['Mebel & Furniture']++;
                    elseif (preg_match('/(jasa|sewa|pemeliharaan|perbaikan|langganan|servis|tenaga|outsourcing|keamanan|kebersihan|cleaning)/', $pLower))
                        $kategoriCounter['Jasa']++;
                    else
                        $kategoriCounter['Lainnya']++;
                }
                arsort($kategoriCounter);
                $kategoriUtama = array_key_first($kategoriCounter);
                if ($kategoriCounter[$kategoriUtama] == 0)
                    $kategoriUtama = 'Lainnya';
            }

            $sheet->setCellValue('A' . $rowNum, $no++);
            $sheet->setCellValue('B' . $rowNum, $namaPenyedia);
            $sheet->setCellValue('C' . $rowNum, $kategoriUtama);
            $sheet->setCellValue('D' . $rowNum, $dataItem['total_paket']);
            $sheet->setCellValue('E' . $rowNum, $dataItem['total_transaksi']);
            $sheet->setCellValue('F' . $rowNum, implode("\n", $produkRaw));

            $sheet->getStyle('F' . $rowNum)->getAlignment()->setWrapText(true);
            $rowNum++;
        }

        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setWidth(60);

        $sheet->getStyle('E2:E' . ($rowNum - 1))->getNumberFormat()->setFormatCode('#,##0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'Penyedia_Katalog_' . $tahun . '_' . date('YmdHis') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    public function alterDb()
    {
        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();

        $columns = [
            'lokasi'                  => ['type' => 'TEXT', 'null' => true, 'after' => 'nama_proyek'],
            'ppk'                     => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true, 'after' => 'konsultan_pengawas'],
            'sumber_dana'             => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true, 'after' => 'ppk'],
            'jenis_pekerjaan'         => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true, 'after' => 'sumber_dana'],
            'metode_kontrak'          => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true, 'after' => 'jenis_pekerjaan'],
            'gps_kordinat'            => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true, 'after' => 'metode_kontrak'],
            'polygon_lokasi'          => ['type' => 'TEXT', 'null' => true, 'after' => 'gps_kordinat'],
            'target_progres_mingguan' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true, 'after' => 'polygon_lokasi'],
            'file_kontrak'            => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true, 'after' => 'target_progres_mingguan'],
            'dokumen_mc'              => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true, 'after' => 'file_kontrak'],
        ];

        try {
            $added = [];
            foreach ($columns as $columnName => $definition) {
                if (!$db->fieldExists($columnName, 'monev_proyek')) {
                    $forge->addColumn('monev_proyek', [$columnName => $definition]);
                    $added[] = $columnName;
                }
            }
            return "DB schema updated successfully. Added columns: " . (empty($added) ? 'none (all exist)' : implode(', ', $added));
        } catch (\Exception $e) {
            return "Error updating schema: " . $e->getMessage();
        }
    }

    private function _categorizeWithAI($groupedData, $separator = '||')
    {
        $settingsModel = new \App\Models\SettingsModel();
        $apiKey = $settingsModel->getSetting('chatbot_api_key', env('OPENAI_API_KEY'));
        $model = $settingsModel->getSetting('chatbot_model', 'gpt-4o-mini');
        $apiUrl = $settingsModel->getSetting('chatbot_api_url', 'https://api.openai.com/v1/chat/completions');

        if (empty($apiKey))
            return [];

        $aiInput = [];
        foreach ($groupedData as $row) {
            $nama = $row['nama_penyedia'];
            $produkList = array_unique(array_filter(array_map('trim', explode($separator, $row['produk']))));
            $aiInput[$nama] = substr(implode(', ', $produkList), 0, 300);
        }

        if (empty($aiInput))
            return [];

        $prompt = "Kamu adalah AI Pengkategorian Pengadaan. Kategorikan penyedia berikut berdasarkan produk yang mereka jual.
Kategori yang tersedia: Makanan & Minuman, Elektronik & IT, Alat Tulis Kantor (ATK), Internet & Jaringan, Kesehatan & Medis, Konstruksi & Material, Kendaraan & Transportasi, Mebel & Furniture, Jasa, Lainnya.

Berikut data JSON { \"Nama Penyedia\": \"Daftar Produk\" }:
" . json_encode($aiInput) . "

Balas HANYA dengan JSON murni dengan format { \"Nama Penyedia\": \"Nama Kategori\" }. Pastikan nama kategori sama persis dengan opsi di atas.";

        $data = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You only output pure JSON, no markdown formatting.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.1,
        ];

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            $result = json_decode($response, true);
            if (isset($result['choices'][0]['message']['content'])) {
                $content = $result['choices'][0]['message']['content'];
                $content = preg_replace('/```json|```/', '', $content);
                return json_decode(trim($content), true) ?: [];
            }
        }
        return [];
    }

    /**
     * Monev OPD - Monitoring Realisasi per OPD
     * Render halaman view
     */
    public function monevOpd()
    {
        $months = [
            'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
            'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
            'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
            'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
        ];
        $tglUpdateStr = strtr(date('d F Y'), $months);

        return view('dashboard/monev_opd', [
            'title' => 'Monev OPD - Monitoring Realisasi & Progres SPSE',
            'user' => session()->get(),
            'update_data' => $tglUpdateStr,
            'update_rup' => $tglUpdateStr,
        ]);
    }

    /**
     * Monev Progres SPSE - API endpoint
     * Mengambil data monitoring progres SPSE / E-Kontrak per OPD (sesuai format laporan PDF)
     */
    public function getMonevProgresData()
    {
        $year = $this->request->getGet('year') ?? date('Y');
        $kategori = $this->request->getGet('kategori') ?? 'all';

        $db = \Config\Database::connect();

        if (!$db->tableExists('monev_progres_spse')) {
            try {
                $migration = new \App\Database\Migrations\CreateMonevProgresSpseTable();
                $migration->up();
                $syncService = new \App\Services\SyncService();
                $syncService->syncMonevProgres($year);
            } catch (\Throwable $mEx) {
                log_message('error', 'Auto-create monev_progres_spse in getMonevProgresData failed: ' . $mEx->getMessage());
            }
        }

        if (!$db->tableExists('monev_progres_spse')) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Tabel monev_progres_spse belum ditemukan pada database. Silakan jalankan migrasi database atau import SQL monev_progres_spse.sql.',
                'data' => [],
                'summary' => [
                    'total_satker' => 0,
                    'rup_penyedia' => 0,
                    'rup_swakelola' => 0,
                    'rup_pl' => 0,
                    'rup_juksung' => 0,
                    'rup_epurchasing' => 0,
                    'rup_dikecualikan' => 0,
                    'rup_tender' => 0,
                    'epl_dilaksanakan' => 0,
                    'epl_belum_input' => 0,
                    'epl_sudah_input' => 0,
                    'epl_pct' => 0,
                    'tender_dilaksanakan' => 0,
                    'tender_belum_input' => 0,
                    'tender_sudah_input' => 0,
                    'tender_pct' => 0,
                    'non_tender_belum' => 0,
                    'non_tender_sudah' => 0,
                    'non_tender_pct' => 0,
                    'swakelola_belum' => 0,
                    'swakelola_sudah' => 0,
                    'swakelola_pct' => 0,
                    'epurchasing_jml' => 0,
                    'epurchasing_belum' => 0,
                    'epurchasing_sudah' => 0,
                    'epurchasing_pct' => 0,
                    'total_rup' => 0,
                ],
                'update_data' => '-',
                'update_rup' => '-',
            ]);
        }

        try {
            $builder = $db->table('monev_progres_spse');

            // Filter year if exists in DB, otherwise get default
            $countYear = (clone $builder)->where('tahun', $year)->countAllResults();
            if ($countYear > 0) {
                $builder->where('tahun', $year);
            }

            if ($kategori !== 'all' && !empty($kategori)) {
                $builder->where('kategori', $kategori);
            }

            $data = $builder->orderBy('no', 'ASC')->get()->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', 'getMonevProgresData error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Gagal mengambil data progres SPSE: ' . $e->getMessage(),
                'data' => [],
            ]);
        }

        // Calculate summary totals
        $summary = [
            'total_satker' => count($data),
            'rup_penyedia' => 0,
            'rup_swakelola' => 0,
            'rup_pl' => 0,
            'rup_juksung' => 0,
            'rup_epurchasing' => 0,
            'rup_dikecualikan' => 0,
            'rup_tender' => 0,
            'epl_dilaksanakan' => 0,
            'epl_belum_input' => 0,
            'epl_sudah_input' => 0,
            'epl_pct' => 0,
            'tender_dilaksanakan' => 0,
            'tender_belum_input' => 0,
            'tender_sudah_input' => 0,
            'tender_pct' => 0,
            'non_tender_belum' => 0,
            'non_tender_sudah' => 0,
            'non_tender_pct' => 0,
            'swakelola_belum' => 0,
            'swakelola_sudah' => 0,
            'swakelola_pct' => 0,
            'epurchasing_jml' => 0,
            'epurchasing_belum' => 0,
            'epurchasing_sudah' => 0,
            'epurchasing_pct' => 0,
        ];

        foreach ($data as $r) {
            $summary['rup_penyedia'] += (int) ($r['rup_penyedia'] ?? 0);
            $summary['rup_swakelola'] += (int) ($r['rup_swakelola'] ?? 0);
            $summary['rup_pl'] += (int) ($r['rup_pl'] ?? 0);
            $summary['rup_juksung'] += (int) ($r['rup_juksung'] ?? 0);
            $summary['rup_epurchasing'] += (int) ($r['rup_epurchasing'] ?? 0);
            $summary['rup_dikecualikan'] += (int) ($r['rup_dikecualikan'] ?? 0);
            $summary['rup_tender'] += (int) ($r['rup_tender'] ?? 0);

            $summary['epl_dilaksanakan'] += (int) ($r['epl_dilaksanakan'] ?? 0);
            $summary['epl_belum_input'] += (int) ($r['epl_belum_input'] ?? 0);
            $summary['epl_sudah_input'] += (int) ($r['epl_sudah_input'] ?? 0);

            $summary['tender_dilaksanakan'] += (int) ($r['tender_dilaksanakan'] ?? 0);
            $summary['tender_belum_input'] += (int) ($r['tender_belum_input'] ?? 0);
            $summary['tender_sudah_input'] += (int) ($r['tender_sudah_input'] ?? 0);

            $summary['non_tender_belum'] += (int) ($r['non_tender_belum'] ?? 0);
            $summary['non_tender_sudah'] += (int) ($r['non_tender_sudah'] ?? 0);

            $summary['swakelola_belum'] += (int) ($r['swakelola_belum'] ?? 0);
            $summary['swakelola_sudah'] += (int) ($r['swakelola_sudah'] ?? 0);

            $summary['epurchasing_jml'] += (int) ($r['epurchasing_jml'] ?? 0);
            $summary['epurchasing_belum'] += (int) ($r['epurchasing_belum'] ?? 0);
            $summary['epurchasing_sudah'] += (int) ($r['epurchasing_sudah'] ?? 0);
        }

        $summary['total_rup'] = $summary['rup_penyedia'] + $summary['rup_swakelola'];
        $summary['epl_pct'] = $summary['epl_dilaksanakan'] > 0 ? round(($summary['epl_sudah_input'] / $summary['epl_dilaksanakan']) * 100, 2) : 0;
        $summary['tender_pct'] = $summary['tender_dilaksanakan'] > 0 ? round(($summary['tender_sudah_input'] / $summary['tender_dilaksanakan']) * 100, 2) : 0;
        $totNonTender = $summary['non_tender_belum'] + $summary['non_tender_sudah'];
        $summary['non_tender_pct'] = $totNonTender > 0 ? round(($summary['non_tender_sudah'] / $totNonTender) * 100, 2) : 0;
        $totSwakelola = $summary['swakelola_belum'] + $summary['swakelola_sudah'];
        $summary['swakelola_pct'] = $totSwakelola > 0 ? round(($summary['swakelola_sudah'] / $totSwakelola) * 100, 2) : 0;
        $summary['epurchasing_pct'] = $summary['epurchasing_jml'] > 0 ? round(($summary['epurchasing_sudah'] / $summary['epurchasing_jml']) * 100, 2) : 0;

        // Tanggal update data terkini
        $maxTglUpdate = date('d F Y');
        $months = [
            'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
            'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
            'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
            'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
        ];
        $formattedDateNow = strtr(date('d F Y'), $months);

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $data,
            'summary' => $summary,
            'update_data' => $formattedDateNow,
            'update_rup' => $formattedDateNow,
        ]);
    }

    /**
     * Export Monev Progres SPSE to Excel (.xlsx)
     */
    public function exportMonevProgresSpse()
    {
        $year = $this->request->getGet('year') ?? date('Y');
        $kategori = $this->request->getGet('kategori') ?? 'all';

        $db = \Config\Database::connect();
        if (!$db->tableExists('monev_progres_spse')) {
            return redirect()->back()->with('error', 'Tabel monev_progres_spse belum ditemukan di database. Silakan jalankan migrasi database.');
        }
        $builder = $db->table('monev_progres_spse');
        $countYear = (clone $builder)->where('tahun', $year)->countAllResults();
        if ($countYear > 0) {
            $builder->where('tahun', $year);
        }
        if ($kategori !== 'all' && !empty($kategori)) {
            $builder->where('kategori', $kategori);
        }
        $rows = $builder->orderBy('no', 'ASC')->get()->getResultArray();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Progres SPSE ' . $year);

        // Judul Laporan
        $sheet->setCellValue('A1', 'LAPORAN MONITORING PROGRES SPSE (E-KONTRAK) OPD KABUPATEN LOMBOK BARAT');
        $sheet->setCellValue('A2', 'TAHUN ANGGARAN ' . $year);
        $months = [
            'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
            'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
            'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
            'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
        ];
        $tglUpdateStr = strtr(date('d F Y'), $months);
        $sheet->setCellValue('A3', 'Data per Tanggal: ' . $tglUpdateStr);

        $sheet->mergeCells('A1:AB1');
        $sheet->mergeCells('A2:AB2');
        $sheet->mergeCells('A3:AB3');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF1E293B'));
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF334155'));
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF64748B'));
        $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Header Baris 5: Grup Utama
        $sheet->setCellValue('A5', 'NO');
        $sheet->setCellValue('B5', 'SATKER / OPD');
        $sheet->setCellValue('C5', 'RUP');
        $sheet->setCellValue('J5', 'E-PL');
        $sheet->setCellValue('N5', 'TENDER');
        $sheet->setCellValue('R5', 'NON TENDER');
        $sheet->setCellValue('U5', 'SWAKELOLA');
        $sheet->setCellValue('X5', 'E-PURCHASING');
        $sheet->setCellValue('AB5', 'KETERANGAN');

        $sheet->mergeCells('A5:A7');
        $sheet->mergeCells('B5:B7');
        $sheet->mergeCells('C5:I5');
        $sheet->mergeCells('J5:M5');
        $sheet->mergeCells('N5:Q5');
        $sheet->mergeCells('R5:T5');
        $sheet->mergeCells('U5:W5');
        $sheet->mergeCells('X5:AA5');
        $sheet->mergeCells('AB5:AB7');

        // Header Baris 6: Sub Grup
        $sheet->setCellValue('C6', "Penyedia");
        $sheet->setCellValue('D6', "Swakelola");
        $sheet->setCellValue('E6', "Pengadaan\nLangsung");
        $sheet->setCellValue('F6', "Penunjukan\nLangsung");
        $sheet->setCellValue('G6', "E-Purchasing");
        $sheet->setCellValue('H6', "Dikecualikan");
        $sheet->setCellValue('I6', "Tender");

        $sheet->mergeCells('C6:C7');
        $sheet->mergeCells('D6:D7');
        $sheet->mergeCells('E6:E7');
        $sheet->mergeCells('F6:F7');
        $sheet->mergeCells('G6:G7');
        $sheet->mergeCells('H6:H7');
        $sheet->mergeCells('I6:I7');

        $sheet->setCellValue('J6', 'Dilaksanakan');
        $sheet->setCellValue('K6', 'Input E-Kontrak');
        $sheet->mergeCells('J6:J7');
        $sheet->mergeCells('K6:M6');

        $sheet->setCellValue('N6', 'Dilaksanakan');
        $sheet->setCellValue('O6', 'Input E-Kontrak');
        $sheet->mergeCells('N6:N7');
        $sheet->mergeCells('O6:Q6');

        $sheet->setCellValue('R6', 'Input E-Kontrak');
        $sheet->mergeCells('R6:T6');

        $sheet->setCellValue('U6', 'Input E-Kontrak');
        $sheet->mergeCells('U6:W6');

        $sheet->setCellValue('X6', 'Jumlah');
        $sheet->setCellValue('Y6', 'Input E-Kontrak');
        $sheet->mergeCells('X6:X7');
        $sheet->mergeCells('Y6:AA6');

        // Header Baris 7: Detail Input E-Kontrak
        $sheet->setCellValue('K7', 'Belum');
        $sheet->setCellValue('L7', 'Sudah');
        $sheet->setCellValue('M7', '%');

        $sheet->setCellValue('O7', 'Belum');
        $sheet->setCellValue('P7', 'Sudah');
        $sheet->setCellValue('Q7', '%');

        $sheet->setCellValue('R7', 'Belum');
        $sheet->setCellValue('S7', 'Sudah');
        $sheet->setCellValue('T7', '%');

        $sheet->setCellValue('U7', 'Belum');
        $sheet->setCellValue('V7', 'Sudah');
        $sheet->setCellValue('W7', '%');

        $sheet->setCellValue('Y7', 'Belum');
        $sheet->setCellValue('Z7', 'Sudah');
        $sheet->setCellValue('AA7', '%');

        // Styling Header (Soft/Kalem Colors Sesuai Web)
        $styleHeaderBase = [
            'font' => ['bold' => true, 'size' => 9],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FFCBD5E1']
                ]
            ]
        ];

        $sheet->getStyle('A5:AB7')->applyFromArray($styleHeaderBase);

        // Warna per grup kolom header
        $colorSatker     = ['type' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['argb' => 'FFF1F5F9']];
        $colorRup        = ['type' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['argb' => 'FFDBEAFE']];
        $colorEpl        = ['type' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['argb' => 'FFCCFBF1']];
        $colorTender     = ['type' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['argb' => 'FFFFE4E6']];
        $colorNonTender  = ['type' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['argb' => 'FFFEF9C3']];
        $colorSwakelola  = ['type' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['argb' => 'FFDCFCE7']];
        $colorEpurchasing = ['type' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['argb' => 'FFF3E8FF']];

        $sheet->getStyle('A5:B7')->getFill()->applyFromArray($colorSatker);
        $sheet->getStyle('AB5:AB7')->getFill()->applyFromArray($colorSatker);
        $sheet->getStyle('C5:I7')->getFill()->applyFromArray($colorRup);
        $sheet->getStyle('J5:M7')->getFill()->applyFromArray($colorEpl);
        $sheet->getStyle('N5:Q7')->getFill()->applyFromArray($colorTender);
        $sheet->getStyle('R5:T7')->getFill()->applyFromArray($colorNonTender);
        $sheet->getStyle('U5:W7')->getFill()->applyFromArray($colorSwakelola);
        $sheet->getStyle('X5:AA7')->getFill()->applyFromArray($colorEpurchasing);

        // Header font color groups
        $sheet->getStyle('C5:I7')->getFont()->getColor()->setARGB('FF1E40AF');
        $sheet->getStyle('J5:M7')->getFont()->getColor()->setARGB('FF0F766E');
        $sheet->getStyle('N5:Q7')->getFont()->getColor()->setARGB('FF9F1239');
        $sheet->getStyle('R5:T7')->getFont()->getColor()->setARGB('FF854D0E');
        $sheet->getStyle('U5:W7')->getFont()->getColor()->setARGB('FF166534');
        $sheet->getStyle('X5:AA7')->getFont()->getColor()->setARGB('FF6B21A8');

        // Populate Data Rows
        $rowNum = 8;
        $totalSatker = count($rows);

        foreach ($rows as $r) {
            $sheet->setCellValue('A' . $rowNum, $r['no']);
            $sheet->setCellValue('B' . $rowNum, $r['satker']);
            $sheet->setCellValue('C' . $rowNum, (int) ($r['rup_penyedia'] ?? 0));
            $sheet->setCellValue('D' . $rowNum, (int) ($r['rup_swakelola'] ?? 0));
            $sheet->setCellValue('E' . $rowNum, (int) ($r['rup_pl'] ?? 0));
            $sheet->setCellValue('F' . $rowNum, (int) ($r['rup_juksung'] ?? 0));
            $sheet->setCellValue('G' . $rowNum, (int) ($r['rup_epurchasing'] ?? 0));
            $sheet->setCellValue('H' . $rowNum, (int) ($r['rup_dikecualikan'] ?? 0));
            $sheet->setCellValue('I' . $rowNum, (int) ($r['rup_tender'] ?? 0));

            $sheet->setCellValue('J' . $rowNum, (int) ($r['epl_dilaksanakan'] ?? 0));
            $sheet->setCellValue('K' . $rowNum, (int) ($r['epl_belum_input'] ?? 0));
            $sheet->setCellValue('L' . $rowNum, (int) ($r['epl_sudah_input'] ?? 0));
            $sheet->setCellValue('M' . $rowNum, ((float) ($r['epl_pct'] ?? 0)) / 100);

            $sheet->setCellValue('N' . $rowNum, (int) ($r['tender_dilaksanakan'] ?? 0));
            $sheet->setCellValue('O' . $rowNum, (int) ($r['tender_belum_input'] ?? 0));
            $sheet->setCellValue('P' . $rowNum, (int) ($r['tender_sudah_input'] ?? 0));
            $sheet->setCellValue('Q' . $rowNum, ((float) ($r['tender_pct'] ?? 0)) / 100);

            $sheet->setCellValue('R' . $rowNum, (int) ($r['non_tender_belum'] ?? 0));
            $sheet->setCellValue('S' . $rowNum, (int) ($r['non_tender_sudah'] ?? 0));
            $sheet->setCellValue('T' . $rowNum, ((float) ($r['non_tender_pct'] ?? 0)) / 100);

            $sheet->setCellValue('U' . $rowNum, (int) ($r['swakelola_belum'] ?? 0));
            $sheet->setCellValue('V' . $rowNum, (int) ($r['swakelola_sudah'] ?? 0));
            $sheet->setCellValue('W' . $rowNum, ((float) ($r['swakelola_pct'] ?? 0)) / 100);

            $sheet->setCellValue('X' . $rowNum, (int) ($r['epurchasing_jml'] ?? 0));
            $sheet->setCellValue('Y' . $rowNum, (int) ($r['epurchasing_belum'] ?? 0));
            $sheet->setCellValue('Z' . $rowNum, (int) ($r['epurchasing_sudah'] ?? 0));
            $sheet->setCellValue('AA' . $rowNum, ((float) ($r['epurchasing_pct'] ?? 0)) / 100);

            $sheet->setCellValue('AB' . $rowNum, $r['keterangan'] ?? '');

            // Format baris selang-seling halus (zebra)
            if ($rowNum % 2 === 1) {
                $sheet->getStyle('A' . $rowNum . ':AB' . $rowNum)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFF8FAFC');
            }

            $rowNum++;
        }

        $lastDataRow = $rowNum - 1;

        // Grand Total Row
        $sheet->setCellValue('A' . $rowNum, 'GRAND TOTAL');
        $sheet->mergeCells('A' . $rowNum . ':B' . $rowNum);

        $colsSum = ['C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'N', 'O', 'P', 'R', 'S', 'U', 'V', 'X', 'Y', 'Z'];
        foreach ($colsSum as $c) {
            $sheet->setCellValue($c . $rowNum, "=SUM({$c}8:{$c}{$lastDataRow})");
        }

        // Formula Persentase Grand Total
        $sheet->setCellValue('M' . $rowNum, "=IF(J{$rowNum}>0, L{$rowNum}/J{$rowNum}, 0)");
        $sheet->setCellValue('Q' . $rowNum, "=IF(N{$rowNum}>0, P{$rowNum}/N{$rowNum}, 0)");
        $sheet->setCellValue('T' . $rowNum, "=IF((R{$rowNum}+S{$rowNum})>0, S{$rowNum}/(R{$rowNum}+S{$rowNum}), 0)");
        $sheet->setCellValue('W' . $rowNum, "=IF((U{$rowNum}+V{$rowNum})>0, V{$rowNum}/(U{$rowNum}+V{$rowNum}), 0)");
        $sheet->setCellValue('AA' . $rowNum, "=IF(X{$rowNum}>0, Z{$rowNum}/X{$rowNum}, 0)");
        $sheet->setCellValue('AB' . $rowNum, '');

        // Styling Grand Total
        $styleGrandTotal = [
            'font' => ['bold' => true, 'size' => 10],
            'fill' => [
                'type' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['argb' => 'FFE2E8F0']
            ],
            'borders' => [
                'top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE, 'color' => ['argb' => 'FF475569']],
                'bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE, 'color' => ['argb' => 'FF475569']],
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FF94A3B8']]
            ]
        ];
        $sheet->getStyle('A' . $rowNum . ':AB' . $rowNum)->applyFromArray($styleGrandTotal);

        // Alignment & Borders Data
        $sheet->getStyle('A8:AB' . $lastDataRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFE2E8F0');
        $sheet->getStyle('A8:A' . $lastDataRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B8:B' . $lastDataRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('C8:AA' . $rowNum)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('AB8:AB' . $lastDataRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Number Formatting
        $sheet->getStyle('C8:L' . $rowNum)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('N8:P' . $rowNum)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('R8:S' . $rowNum)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('U8:V' . $rowNum)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('X8:Z' . $rowNum)->getNumberFormat()->setFormatCode('#,##0');

        $sheet->getStyle('M8:M' . $rowNum)->getNumberFormat()->setFormatCode('0.00%');
        $sheet->getStyle('Q8:Q' . $rowNum)->getNumberFormat()->setFormatCode('0.00%');
        $sheet->getStyle('T8:T' . $rowNum)->getNumberFormat()->setFormatCode('0.00%');
        $sheet->getStyle('W8:W' . $rowNum)->getNumberFormat()->setFormatCode('0.00%');
        $sheet->getStyle('AA8:AA' . $rowNum)->getNumberFormat()->setFormatCode('0.00%');

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(38);
        foreach (range('C', 'I') as $col) {
            $sheet->getColumnDimension($col)->setWidth(12);
        }
        foreach (['J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA'] as $col) {
            $sheet->getColumnDimension($col)->setWidth(11);
        }
        $sheet->getColumnDimension('AB')->setWidth(18);

        // Freeze Panes agar header dan satker tetap terlihat saat di-scroll
        $sheet->freezePane('C8');

        $filename = 'Laporan_Monev_Progres_SPSE_' . $year . '_' . date('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Monev OPD - API endpoint
     * Mengambil data paket RUP per OPD dan padankan realisasi dari semua tabel
     */
    public function getMonevOpdData()
    {
        $year = $this->request->getGet('year') ?? date('Y');
        $cacheKey = "monev_opd_v4_{$year}";

        if (!$result = cache($cacheKey)) {
            // Models
            $rupPenyediaModel = new RupPenyediaModel();
            $rupSwakelolaModel = new RupSwakelolaModel();
            $tenderModel = new LpseTenderModel();
            $nontenderModel = new LpseNontenderModel();
            $epurchasingModel = new EpurchasingModel();
            $pencatatanNontenderModel = new LpsePencatatanNontenderModel();
            $pencatatanSwakelolaModel = new LpsePencatatanSwakelolaModel();

            // 1. Get all RUP Penyedia & Swakelola
            $rupP = $rupPenyediaModel->where('tahun', $year)->findAll();
            $rupS = $rupSwakelolaModel->where('tahun', $year)->findAll();

            // 2. Build realization map by kd_rup with source tracking
            $realizationMap = [];   // kd_rup => total value
            $sourceMap = [];        // kd_rup => source label array
            $namaPaketMap = [];     // kd_rup => nama paket realisasi

            // Tender
            $tenders = $tenderModel->where('tahun', $year)->findAll();
            foreach ($tenders as $t) {
                $kd = trim($t['kd_rup'] ?? '');
                if ($kd) {
                    $realizationMap[$kd] = ($realizationMap[$kd] ?? 0) + (float) ($t['nilai_kontrak'] ?? 0);
                    $sourceMap[$kd][] = 'Tender';
                    if (!isset($namaPaketMap[$kd]) && !empty(trim($t['nama_paket'] ?? '')))
                        $namaPaketMap[$kd] = trim($t['nama_paket']);
                }
            }

            // Non Tender
            $nontenders = $nontenderModel->where('tahun', $year)->findAll();
            foreach ($nontenders as $nt) {
                $kd = trim($nt['kd_rup'] ?? '');
                if ($kd) {
                    $realizationMap[$kd] = ($realizationMap[$kd] ?? 0) + (float) ($nt['nilai_kontrak'] ?? 0);
                    $sourceMap[$kd][] = 'Non Tender';
                    if (!isset($namaPaketMap[$kd]) && !empty(trim($nt['nama_paket'] ?? '')))
                        $namaPaketMap[$kd] = trim($nt['nama_paket']);
                }
            }

            // E-Purchasing (uses rup_code instead of kd_rup)
            $epurchasing = $epurchasingModel->where('tahun', $year)->findAll();
            foreach ($epurchasing as $ep) {
                $kd = trim($ep['rup_code'] ?? '');
                if ($kd) {
                    $realizationMap[$kd] = ($realizationMap[$kd] ?? 0) + (float) ($ep['total_harga'] ?? 0);
                    $sourceMap[$kd][] = 'E-Purchasing';
                    if (!isset($namaPaketMap[$kd]) && !empty(trim($ep['nama_paket'] ?? '')))
                        $namaPaketMap[$kd] = trim($ep['nama_paket']);
                }
            }

            // Pencatatan Non Tender
            $pnt = $pencatatanNontenderModel->where('tahun', $year)->findAll();
            foreach ($pnt as $p) {
                $kd = trim($p['kd_rup'] ?? '');
                if ($kd) {
                    $realizationMap[$kd] = ($realizationMap[$kd] ?? 0) + (float) ($p['realisasi'] ?? 0);
                    $sourceMap[$kd][] = 'Pencatatan Non Tender';
                    if (!isset($namaPaketMap[$kd]) && !empty(trim($p['nama_paket'] ?? '')))
                        $namaPaketMap[$kd] = trim($p['nama_paket']);
                }
            }

            // Pencatatan Swakelola
            $psw = $pencatatanSwakelolaModel->where('tahun', $year)->findAll();
            foreach ($psw as $p) {
                $kd = trim($p['kd_rup'] ?? '');
                if ($kd) {
                    $realizationMap[$kd] = ($realizationMap[$kd] ?? 0) + (float) ($p['realisasi'] ?? 0);
                    $sourceMap[$kd][] = 'Pencatatan Swakelola';
                    if (!isset($namaPaketMap[$kd]) && !empty(trim($p['nama_paket'] ?? '')))
                        $namaPaketMap[$kd] = trim($p['nama_paket']);
                }
            }

            // Deduplicate sources per kd_rup
            foreach ($sourceMap as $kd => &$sources) {
                $sources = array_values(array_unique($sources));
            }

            // 3. Build OPD data with packages
            $opdData = [];

            // Helper function to initialize OPD structure
            $initOpd = function($kdSatker, $namaSatker) {
                return [
                    'kd_satker' => $kdSatker,
                    'nama_satker' => $namaSatker,
                    // Keuangan (Anggaran)
                    'pagu_penyedia' => 0,
                    'realisasi_penyedia' => 0,
                    'persen_keuangan_penyedia' => 0,
                    'pagu_swakelola' => 0,
                    'realisasi_swakelola' => 0,
                    'persen_keuangan_swakelola' => 0,
                    'total_pagu' => 0,
                    'total_realisasi' => 0,
                    'persentase_keuangan' => 0,
                    'persentase' => 0, // compatibility alias
                    // Paket (Fisik)
                    'paket_penyedia' => 0,
                    'paket_terealisasi_penyedia' => 0,
                    'persen_paket_penyedia' => 0,
                    'paket_swakelola' => 0,
                    'paket_terealisasi_swakelola' => 0,
                    'persen_paket_swakelola' => 0,
                    'total_paket' => 0,
                    'paket_terealisasi' => 0,
                    'persentase_paket' => 0,
                    'packages' => [],
                ];
            };

            // Process Penyedia packages
            foreach ($rupP as $item) {
                $kdSatker = $item['kd_satker'] ?: '0';
                $kdRup = trim($item['kd_rup'] ?? '');

                if (!isset($opdData[$kdSatker])) {
                    $opdData[$kdSatker] = $initOpd($kdSatker, $item['nama_satker']);
                }

                $realisasi = 0;
                $sumberArr = [];
                $sumber = '-';
                $namaPaketReal = '-';
                if ($kdRup && isset($realizationMap[$kdRup])) {
                    $realisasi = $realizationMap[$kdRup];
                    $sumberArr = $sourceMap[$kdRup] ?? [];
                    $sumber = implode(', ', $sumberArr);
                    $namaPaketReal = $namaPaketMap[$kdRup] ?? '-';
                }

                $pagu = (float) ($item['pagu'] ?? 0);
                $opdData[$kdSatker]['pagu_penyedia'] += $pagu;
                $opdData[$kdSatker]['realisasi_penyedia'] += $realisasi;
                $opdData[$kdSatker]['paket_penyedia'] += 1;
                if ($realisasi > 0) {
                    $opdData[$kdSatker]['paket_terealisasi_penyedia'] += 1;
                }

                $opdData[$kdSatker]['total_pagu'] += $pagu;
                $opdData[$kdSatker]['total_realisasi'] += $realisasi;
                $opdData[$kdSatker]['total_paket'] += 1;
                if ($realisasi > 0) {
                    $opdData[$kdSatker]['paket_terealisasi'] += 1;
                }

                $pctPaket = $pagu > 0 ? round(($realisasi / $pagu) * 100, 2) : 0;

                $opdData[$kdSatker]['packages'][] = [
                    'kd_rup' => $kdRup ?: '-',
                    'nama_paket' => $item['nama_paket'] ?? '-',
                    'nama_paket_realisasi' => $namaPaketReal,
                    'pagu' => $pagu,
                    'jenis' => $item['jenis_pengadaan'] ?: 'Penyedia',
                    'metode' => $item['metode_pengadaan'] ?: '-',
                    'realisasi' => $realisasi,
                    'persentase' => $pctPaket,
                    'sumber' => $sumber,
                    'sources' => $sumberArr,
                    'tipe' => 'Penyedia',
                ];
            }

            // Process Swakelola packages
            foreach ($rupS as $item) {
                $kdSatker = $item['kd_satker'] ?: '0';
                $kdRup = trim($item['kd_rup'] ?? '');

                if (!isset($opdData[$kdSatker])) {
                    $opdData[$kdSatker] = $initOpd($kdSatker, $item['nama_satker']);
                }

                $realisasi = 0;
                $sumberArr = [];
                $sumber = '-';
                $namaPaketReal = '-';
                if ($kdRup && isset($realizationMap[$kdRup])) {
                    $realisasi = $realizationMap[$kdRup];
                    $sumberArr = $sourceMap[$kdRup] ?? [];
                    $sumber = implode(', ', $sumberArr);
                    $namaPaketReal = $namaPaketMap[$kdRup] ?? '-';
                }

                $pagu = (float) ($item['pagu'] ?? 0);
                $opdData[$kdSatker]['pagu_swakelola'] += $pagu;
                $opdData[$kdSatker]['realisasi_swakelola'] += $realisasi;
                $opdData[$kdSatker]['paket_swakelola'] += 1;
                if ($realisasi > 0) {
                    $opdData[$kdSatker]['paket_terealisasi_swakelola'] += 1;
                }

                $opdData[$kdSatker]['total_pagu'] += $pagu;
                $opdData[$kdSatker]['total_realisasi'] += $realisasi;
                $opdData[$kdSatker]['total_paket'] += 1;
                if ($realisasi > 0) {
                    $opdData[$kdSatker]['paket_terealisasi'] += 1;
                }

                $pctPaket = $pagu > 0 ? round(($realisasi / $pagu) * 100, 2) : 0;
                
                $rawTipeS = trim((string)($item['tipe_swakelola'] ?? ''));
                $metodeSwakelola = match ($rawTipeS) {
                    '1', 'Tipe 1', 'Tipe I' => 'Swakelola Tipe I',
                    '2', 'Tipe 2', 'Tipe II' => 'Swakelola Tipe II',
                    '3', 'Tipe 3', 'Tipe III' => 'Swakelola Tipe III',
                    '4', 'Tipe 4', 'Tipe IV' => 'Swakelola Tipe IV',
                    '' => 'Swakelola',
                    default => (str_starts_with(strtolower($rawTipeS), 'swakelola') ? $rawTipeS : 'Swakelola ' . $rawTipeS)
                };

                $opdData[$kdSatker]['packages'][] = [
                    'kd_rup' => $kdRup ?: '-',
                    'nama_paket' => $item['nama_paket'] ?? '-',
                    'nama_paket_realisasi' => $namaPaketReal,
                    'pagu' => $pagu,
                    'jenis' => 'Swakelola',
                    'metode' => $metodeSwakelola,
                    'realisasi' => $realisasi,
                    'persentase' => $pctPaket,
                    'sumber' => $sumber,
                    'sources' => $sumberArr,
                    'tipe' => 'Swakelola',
                ];
            }

            // 4. Calculate percentages and sort
            foreach ($opdData as &$opd) {
                // Keuangan
                $opd['persen_keuangan_penyedia'] = $opd['pagu_penyedia'] > 0
                    ? round(($opd['realisasi_penyedia'] / $opd['pagu_penyedia']) * 100, 2) : 0;
                $opd['persen_keuangan_swakelola'] = $opd['pagu_swakelola'] > 0
                    ? round(($opd['realisasi_swakelola'] / $opd['pagu_swakelola']) * 100, 2) : 0;
                $opd['persentase_keuangan'] = $opd['total_pagu'] > 0
                    ? round(($opd['total_realisasi'] / $opd['total_pagu']) * 100, 2) : 0;
                $opd['persentase'] = $opd['persentase_keuangan']; // compatibility alias

                // Paket
                $opd['persen_paket_penyedia'] = $opd['paket_penyedia'] > 0
                    ? round(($opd['paket_terealisasi_penyedia'] / $opd['paket_penyedia']) * 100, 2) : 0;
                $opd['persen_paket_swakelola'] = $opd['paket_swakelola'] > 0
                    ? round(($opd['paket_terealisasi_swakelola'] / $opd['paket_swakelola']) * 100, 2) : 0;
                $opd['persentase_paket'] = $opd['total_paket'] > 0
                    ? round(($opd['paket_terealisasi'] / $opd['total_paket']) * 100, 2) : 0;
            }

            // Sort by nama_satker
            usort($opdData, function ($a, $b) {
                return strcmp($a['nama_satker'] ?? '', $b['nama_satker'] ?? '');
            });

            $result = array_values($opdData);
            cache()->save($cacheKey, $result, 3600);
        }

        // Role-based filtering
        $userRole = trim(strtolower(session()->get('role') ?? ''));
        $userSatker = trim(session()->get('kd_satker') ?? '');

        if (in_array($userRole, ['satker', 'opd']) && $userSatker) {
            $result = array_values(array_filter($result, function ($s) use ($userSatker) {
                return $s['kd_satker'] == $userSatker;
            }));
        } elseif ($userRole === 'ppk') {
            $ppkRupCodes = $this->_getPpkRupCodes();
            $result = array_values(array_filter($result, function ($opd) use ($ppkRupCodes) {
                // Keep OPD if any of its packages match PPK's rup codes
                foreach ($opd['packages'] as $pkg) {
                    if (in_array($pkg['kd_rup'], $ppkRupCodes))
                        return true;
                }
                return false;
            }));
        }

        return $this->response->setJSON($result);
    }

    private function _enrichProyekWithTender($proyek)
    {
        if (empty($proyek)) {
            return $proyek;
        }

        // Store original contract dates
        $proyek['tgl_mulai_kontrak'] = $proyek['tgl_mulai'];
        $proyek['tgl_selesai_kontrak'] = $proyek['tgl_selesai'];
        $proyek['tgl_mulai_pekerjaan'] = null;
        $proyek['tgl_selesai_pekerjaan'] = null;
        $proyek['waktu_penyelesaian'] = null;
        $proyek['no_spmk_spp'] = null;

        if (!empty($proyek['kd_rup'])) {
            $db = \Config\Database::connect();
            $tender = $db->table('lpse_tender')->where('kd_rup', $proyek['kd_rup'])->get()->getRowArray();
            if (!$tender) {
                $tender = $db->table('lpse_nontender')->where('kd_rup', $proyek['kd_rup'])->get()->getRowArray();
            }
            if ($tender) {
                $proyek['tgl_mulai_pekerjaan'] = $tender['tgl_mulai_pekerjaan'] ?? null;
                $proyek['tgl_selesai_pekerjaan'] = $tender['tgl_selesai_pekerjaan'] ?? null;
                $proyek['waktu_penyelesaian'] = $tender['waktu_penyelesaian'] ?? null;
                $proyek['no_spmk_spp'] = $tender['no_spmk_spp'] ?? null;

                // Override project dates with actual work dates if set
                if (!empty($tender['tgl_mulai_pekerjaan'])) {
                    $proyek['tgl_mulai'] = $tender['tgl_mulai_pekerjaan'];
                }
                if (!empty($tender['tgl_selesai_pekerjaan'])) {
                    $proyek['tgl_selesai'] = $tender['tgl_selesai_pekerjaan'];
                }
            }
        }

        return $proyek;
    }
}
