<?php

namespace App\Controllers;

use App\Models\RupPenyediaModel;
use App\Models\RupSwakelolaModel;
use App\Models\LpseTenderModel;
use App\Models\LpseNontenderModel;
use App\Models\EpurchasingModel;
use App\Models\LpsePencatatanNontenderModel;
use App\Models\LpsePencatatanSwakelolaModel;
use App\Models\MasterAnggaranModel;

class Report extends BaseController
{
    public function index()
    {
        return view('report/index', [
            'title' => 'Summary Laporan Realisasi',
            'user'  => session()->get(),
        ]);
    }

    public function getSummaryData()
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $year = (int) ($this->request->getGet('year') ?? date('Y'));
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }

        $cacheKey = "report_summary_v2_{$year}";

        $result = null;
        try {
            $cached = cache($cacheKey);
            if (is_array($cached)) {
                $result = $cached;
            }
        } catch (\Throwable $e) {
            log_message('error', 'Cache get error: ' . $e->getMessage());
        }

        if ($result === null) {
            try {
                $db = \Config\Database::connect();

                // 1. Build Realization Map by kd_rup efficiently
                // Only select needed columns: kd_rup and amount
                $realizationMap = [];

                // Helper to populate realization map safely
                $addRealization = function(array $rows, string $kdField, string $valField) use (&$realizationMap) {
                    foreach ($rows as $r) {
                        $rawKd = (string)($r[$kdField] ?? '');
                        $val = (float)($r[$valField] ?? 0);
                        if ($val <= 0 || $rawKd === '') {
                            continue;
                        }

                        // Support semicolon/comma separated kd_rup
                        if (strpos($rawKd, ';') !== false || strpos($rawKd, ',') !== false) {
                            $parts = preg_split('/[;,]/', $rawKd);
                            foreach ($parts as $p) {
                                $p = trim($p);
                                if ($p !== '') {
                                    $realizationMap[$p] = ($realizationMap[$p] ?? 0) + $val;
                                }
                            }
                        } else {
                            $rawKd = trim($rawKd);
                            if ($rawKd !== '') {
                                $realizationMap[$rawKd] = ($realizationMap[$rawKd] ?? 0) + $val;
                            }
                        }
                    }
                };

                // Tender
                $tenders = $db->table('lpse_tender')
                    ->select('kd_rup, nilai_kontrak')
                    ->where('tahun', $year)
                    ->where('kd_rup IS NOT NULL')
                    ->where('kd_rup !=', '')
                    ->get()
                    ->getResultArray();
                $addRealization($tenders, 'kd_rup', 'nilai_kontrak');
                unset($tenders);

                // Non Tender
                $nontenders = $db->table('lpse_nontender')
                    ->select('kd_rup, nilai_kontrak')
                    ->where('tahun', $year)
                    ->where('kd_rup IS NOT NULL')
                    ->where('kd_rup !=', '')
                    ->get()
                    ->getResultArray();
                $addRealization($nontenders, 'kd_rup', 'nilai_kontrak');
                unset($nontenders);

                // E-Purchasing
                $epurchasing = $db->table('epurchasing')
                    ->select('rup_code, total_harga')
                    ->where('tahun', $year)
                    ->where('rup_code IS NOT NULL')
                    ->where('rup_code !=', '')
                    ->get()
                    ->getResultArray();
                $addRealization($epurchasing, 'rup_code', 'total_harga');
                unset($epurchasing);

                // Pencatatan Non Tender
                $pnt = $db->table('lpse_pencatatan_nontender')
                    ->select('kd_rup, realisasi')
                    ->where('tahun', $year)
                    ->where('kd_rup IS NOT NULL')
                    ->where('kd_rup !=', '')
                    ->get()
                    ->getResultArray();
                $addRealization($pnt, 'kd_rup', 'realisasi');
                unset($pnt);

                // Pencatatan Swakelola
                $psw = $db->table('lpse_pencatatan_swakelola')
                    ->select('kd_rup, realisasi')
                    ->where('tahun', $year)
                    ->where('kd_rup IS NOT NULL')
                    ->where('kd_rup !=', '')
                    ->get()
                    ->getResultArray();
                $addRealization($psw, 'kd_rup', 'realisasi');
                unset($psw);

                // 2. Aggregate by Satker
                $satkerSummary = [];

                // Query RUP Penyedia - ONLY fetch kd_rup, kd_satker, nama_satker, pagu
                $rupP = $db->table('rup_penyedia')
                    ->select('kd_rup, kd_satker, nama_satker, pagu')
                    ->where('tahun', $year)
                    ->get()
                    ->getResultArray();

                foreach ($rupP as $item) {
                    $kdSatker = (string)($item['kd_satker'] ?: '0');
                    if (!isset($satkerSummary[$kdSatker])) {
                        $satkerSummary[$kdSatker] = [
                            'nama_satker'        => $item['nama_satker'] ?: 'Satker Tanpa Nama',
                            'kd_satker'          => $kdSatker,
                            'pagu_penyedia'      => 0,
                            'total_paket_p'      => 0,
                            'realisasi_penyedia' => 0,
                            'paket_realisasi_p'  => 0,
                            'pagu_swakelola'     => 0,
                            'total_paket_s'      => 0,
                            'realisasi_swakelola'=> 0,
                            'paket_realisasi_s'  => 0,
                        ];
                    }
                    $satkerSummary[$kdSatker]['pagu_penyedia'] += (float)($item['pagu'] ?? 0);
                    $satkerSummary[$kdSatker]['total_paket_p'] += 1;

                    $kdRup = trim((string)($item['kd_rup'] ?? ''));
                    if ($kdRup !== '' && isset($realizationMap[$kdRup])) {
                        $satkerSummary[$kdSatker]['realisasi_penyedia'] += $realizationMap[$kdRup];
                        $satkerSummary[$kdSatker]['paket_realisasi_p'] += 1;
                    }
                }
                unset($rupP);

                // Query RUP Swakelola - ONLY fetch kd_rup, kd_satker, nama_satker, pagu
                $rupS = $db->table('rup_swakelola')
                    ->select('kd_rup, kd_satker, nama_satker, pagu')
                    ->where('tahun', $year)
                    ->get()
                    ->getResultArray();

                foreach ($rupS as $item) {
                    $kdSatker = (string)($item['kd_satker'] ?: '0');
                    if (!isset($satkerSummary[$kdSatker])) {
                        $satkerSummary[$kdSatker] = [
                            'nama_satker'        => $item['nama_satker'] ?: 'Satker Tanpa Nama',
                            'kd_satker'          => $kdSatker,
                            'pagu_penyedia'      => 0,
                            'total_paket_p'      => 0,
                            'realisasi_penyedia' => 0,
                            'paket_realisasi_p'  => 0,
                            'pagu_swakelola'     => 0,
                            'total_paket_s'      => 0,
                            'realisasi_swakelola'=> 0,
                            'paket_realisasi_s'  => 0,
                        ];
                    }
                    $satkerSummary[$kdSatker]['pagu_swakelola'] += (float)($item['pagu'] ?? 0);
                    $satkerSummary[$kdSatker]['total_paket_s'] += 1;

                    $kdRup = trim((string)($item['kd_rup'] ?? ''));
                    if ($kdRup !== '' && isset($realizationMap[$kdRup])) {
                        $satkerSummary[$kdSatker]['realisasi_swakelola'] += $realizationMap[$kdRup];
                        $satkerSummary[$kdSatker]['paket_realisasi_s'] += 1;
                    }
                }
                unset($rupS);
                unset($realizationMap);

                // 3. Sort by nama_satker
                usort($satkerSummary, function($a, $b) {
                    return strcasecmp($a['nama_satker'] ?? '', $b['nama_satker'] ?? '');
                });

                $result = array_values($satkerSummary);

                try {
                    cache()->save($cacheKey, $result, 3600);
                } catch (\Throwable $e) {
                    log_message('error', 'Cache save error: ' . $e->getMessage());
                }

            } catch (\Throwable $e) {
                log_message('error', 'Report::getSummaryData error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
                return $this->response->setStatusCode(500)->setJSON([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan saat memproses laporan: ' . $e->getMessage()
                ]);
            }
        }

        // RBAC Filter: If user is opd or satker, filter the result by nama_satker / kd_satker
        $userRole = session()->get('role');
        $isRestricted = in_array($userRole, ['satker', 'opd']);

        if ($isRestricted && is_array($result)) {
            $userNamaSatker = session()->get('nama_satker');
            $userKdSatker = session()->get('kd_satker');

            if (!$userNamaSatker && $userKdSatker) {
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

            if ($userKdSatker || $userNamaSatker) {
                $result = array_values(array_filter($result, function($item) use ($userNamaSatker, $userKdSatker) {
                    if ($userKdSatker && !empty($item['kd_satker']) && (string)$item['kd_satker'] === (string)$userKdSatker) {
                        return true;
                    }
                    if ($userNamaSatker && !empty($item['nama_satker']) && strcasecmp(trim($item['nama_satker']), trim($userNamaSatker)) === 0) {
                        return true;
                    }
                    return false;
                }));
            }
        }

        return $this->response->setJSON($result ?? []);
    }
}
