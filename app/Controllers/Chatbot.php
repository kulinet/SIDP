<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\EpurchasingModel;

class Chatbot extends ResourceController
{
    public function index()
    {
        return view('components/chatbot');
    }

    public function ask()
    {
        $senderId = $this->request->getPost('sender_id');
        $isApiCall = !empty($senderId);

        if (!$this->request->isAJAX() && !$isApiCall) {
            return $this->failForbidden('Hanya menerima request AJAX.');
        }

        $message = $this->request->getPost('message');
        $settingsModel = new \App\Models\SettingsModel();
        
        $apiKey = $settingsModel->getSetting('chatbot_api_key', env('OPENAI_API_KEY'));
        $model = $settingsModel->getSetting('chatbot_model', 'gpt-5.4-mini');
        $apiUrl = $settingsModel->getSetting('chatbot_api_url', 'https://api.ai.cc/v1/chat/completions');

        if (empty($message)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Pesan tidak boleh kosong.'
            ], 400);
        }

        // --- Chatbot Memory ( Session / Cache for API ) ---
        $session = session();
        $cache = \Config\Services::cache();
        if ($senderId) {
            $history = $cache->get('chat_history_' . $senderId) ?? [];
        } else {
            $history = $session->get('chat_history') ?? [];
        }

        // Keep only last 4 messages to reduce token load
        if (count($history) > 4) $history = array_slice($history, -4);

        // Check if user asks for e‑purchasing winner
        if (preg_match('/pemenang\s+paket\s+(.+)/i', $message, $m)) {
            $paketName = trim($m[1], " .?,!\"'");
            $winnerData = $this->findPaketWinner($paketName);
            if ($winnerData) {
                $nama = $winnerData['nama_penyedia'] ?? 'Tidak diketahui';
                $kode = $winnerData['kode_penyedia'] ?? '';
                $reply = "Pemenang paket **{$paketName}** adalah **{$nama}**";
                if ($kode) $reply .= " (Kode: {$kode})";
                
                // Save to history
                $history[] = ['role' => 'user', 'content' => $message];
                $history[] = ['role' => 'assistant', 'content' => $reply];
                if ($senderId) {
                    $cache->save('chat_history_' . $senderId, $history, 3600);
                } else {
                    $session->set('chat_history', $history);
                }

                return $this->respond([
                    'status' => 'success',
                    'reply' => $reply
                ]);
            } else {
                return $this->respond([
                    'status' => 'error',
                    'message' => "Tidak ditemukan pemenang untuk paket **{$paketName}**."
                ], 404);
            }
        }

        if (empty($apiKey)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'API Key tidak dikonfigurasi.'
            ], 500);
        }

        // Build smart context based on user's message keywords
        $smartContext = $this->_buildSmartContext($message);
        
        $response = $this->callOpenAI($message, $apiKey, $model, $apiUrl, $history, $smartContext);

        if (isset($response['error'])) {
            $errorMessage = is_array($response['error']) 
                ? ($response['error']['message'] ?? json_encode($response['error'])) 
                : $response['error'];

            // Log the error
            $logModel = new \App\Models\ChatbotLogModel();
            $logModel->insert([
                'user_id'    => session()->get('id'),
                'username'   => session()->get('username') ?? session()->get('nama') ?? ($senderId ?? 'API'),
                'ip_address' => $this->request->getIPAddress(),
                'question'   => $message,
                'response'   => 'ERROR: ' . $errorMessage,
                'model'      => $model,
                'status'     => 'error',
            ]);
            
            return $this->respond([
                'status' => 'error',
                'message' => 'Gagal terhubung ke AI. ' . $errorMessage
            ], 500);
        }

        $reply = $response['choices'][0]['message']['content'] ?? 'Maaf, saya tidak mengerti.';

        // Log the conversation
        $logModel = new \App\Models\ChatbotLogModel();
        $logModel->insert([
            'user_id'    => session()->get('id'),
            'username'   => session()->get('username') ?? session()->get('nama') ?? ($senderId ?? 'API'),
            'ip_address' => $this->request->getIPAddress(),
            'question'   => $message,
            'response'   => $reply,
            'model'      => $model,
            'status'     => 'success',
        ]);

        // Save to History
        $history[] = ['role' => 'user', 'content' => $message];
        $history[] = ['role' => 'assistant', 'content' => $reply];
        
        if ($senderId) {
            $cache->save('chat_history_' . $senderId, $history, 3600);
        } else {
            $session->set('chat_history', $history);
        }

        return $this->respond([
            'status' => 'success',
            'reply'  => $reply
        ]);
    }

    /**
     * Memastikan hanya paket yang RESMI TAYANG di sistem yang diproses.
     * Mengabaikan paket dibatalkan, paket dengan realisasi 0, atau status E-Purchasing yang belum sah.
     */
    public static function isValidPublishedPackage($row, $type)
    {
        $status = strtolower(trim((string) ($row['status'] ?? '')));
        if (str_contains($status, 'batal')) {
            return false;
        }

        if ($type === 'tender') {
            $realisasi = (float) ($row['nilai_kontrak'] ?? 0);
            return $realisasi > 0;
        }

        if ($type === 'nontender') {
            $realisasi = (float) ($row['nilai_kontrak'] ?? 0);
            if ($realisasi <= 0) {
                $realisasi = (float) ($row['nilai_negosiasi'] ?? 0);
            }
            return $realisasi > 0;
        }

        if ($type === 'epurchasing') {
            $allowed = ['COMPLETED', 'ON PROCESS', 'PAYMENT OUTSIDE SYSTEM', 'WAITING PPK REVIEW', 'ON NEGOTIATION', 'WAITING SELLER CONFIRMATION', 'ON ADDENDUM'];
            $epStatus = strtoupper(str_replace('_', ' ', trim((string) ($row['status'] ?? ''))));
            if (empty($epStatus) || $epStatus === '-' || !in_array($epStatus, $allowed)) {
                return false;
            }

            $orderDate = trim((string) ($row['order_date'] ?? ''));
            if ($orderDate === '' || $orderDate === '-' || $orderDate === '0000-00-00' || str_starts_with($orderDate, '0000-00-00')) {
                return false;
            }

            $realisasi = (float) ($row['total_harga'] ?? 0);
            return $realisasi > 0;
        }

        if ($type === 'pencatatan_nontender' || $type === 'pencatatan-nontender') {
            $realisasi = (float) ($row['realisasi'] ?? 0);
            return $realisasi > 0;
        }

        if ($type === 'swakelola' || $type === 'pencatatan_swakelola' || $type === 'pencatatan-swakelola') {
            $statusPctKet = strtolower(trim((string) ($row['status_swakelola_pct_ket'] ?? '')));
            $alasanBatal = trim((string) ($row['alasan_pembatalan'] ?? ''));
            if (str_contains($statusPctKet, 'batal') || $alasanBatal !== '') {
                return false;
            }
            $realisasi = (float) ($row['realisasi'] ?? 0);
            return $realisasi > 0;
        }

        return true;
    }

    private function getRupData($type)
    {
        $currentYear = date('Y');
        $model = ($type === 'swakelola') ? new \App\Models\RupSwakelolaModel() : new \App\Models\RupPenyediaModel();
        
        $builder = $model->where('tahun', $currentYear);
        
        $userRole = strtolower(session()->get('role') ?? '');
        $userSatker = session()->get('kd_satker');
        
        // Filter by OPD/Satker if user is a Satker
        if (in_array($userRole, ['satker', 'opd']) && $userSatker) {
            $builder->where('kd_satker', $userSatker);
        }
        
        $data = $builder->findAll();
        
        return is_array($data) ? $data : [];
    }

    private function getPaguSummary($argsStr)
    {
        $args = json_decode($argsStr, true);
        $tipe = $args['tipe'] ?? 'penyedia';
        $opd = $args['opd'] ?? '';
        $metode = $args['metode'] ?? '';
        $jenis = $args['jenis_pengadaan'] ?? '';
        
        $data = $this->getRupData($tipe);
        
        if ($opd) {
            $data = array_filter($data, function($item) use ($opd) {
                return stripos($item['nama_satker'] ?? '', $opd) !== false;
            });
        }
        
        if ($metode) {
            $data = array_filter($data, function($item) use ($metode, $tipe) {
                $m = $tipe === 'penyedia' ? ($item['metode_pengadaan'] ?? '') : ($item['tipe_swakelola'] ?? '');
                return stripos($m, $metode) !== false;
            });
        }
        
        if ($jenis && $tipe === 'penyedia') {
            $data = array_filter($data, function($item) use ($jenis) {
                return stripos($item['jenis_pengadaan'] ?? '', $jenis) !== false;
            });
        }
        
        $totalPagu = array_sum(array_column($data, 'pagu'));
        $totalPaket = count($data);
        $opdName = $opd ? ($data[array_key_first($data)]['nama_satker'] ?? $opd) : 'Seluruh OPD';

        return json_encode([
            'tipe' => $tipe,
            'opd' => $opdName,
            'metode' => $metode ?: 'Semua',
            'jenis_pengadaan' => $jenis ?: 'Semua',
            'total_paket' => $totalPaket,
            'total_pagu_rupiah' => $totalPagu
        ]);
    }

    private function searchPaket($argsStr)
    {
        $args = json_decode($argsStr, true);
        $query = $args['query'] ?? '';
        $opd = $args['opd'] ?? '';
        $metodeFilter = $args['metode'] ?? '';
        $jenisFilter = $args['jenis_pengadaan'] ?? '';
        $tipe = $args['tipe'] ?? 'penyedia';
        $sortPagu = $args['sort_pagu'] ?? ''; // 'terbesar' atau 'terkecil'
        
        $data = $this->getRupData($tipe);
        $results = [];
        $totalPaguAggregated = 0;

        foreach ($data as $item) {
            $match = true;

            // Search Keyword (Pencarian Luas - Multi word AND search)
            if ($query && $match) {
                // Menggabungkan berbagai field teks untuk pencarian yang lebih baik
                $searchContent = implode(' ', [
                    $item['nama_paket'] ?? '',
                    $item['uraian_pekerjaan'] ?? '',
                    $item['urarian_pekerjaan'] ?? '', // Typo handling untuk data penyedia
                    $item['spesifikasi_pekerjaan'] ?? '',
                    $item['kegiatan'] ?? '',
                    $item['nama_satker'] ?? '',
                    $item['metode_pengadaan'] ?? '',
                    $item['volume_pekerjaan'] ?? ''
                ]);

                $searchContentLower = strtolower($searchContent);
                $queryWords = explode(' ', strtolower($query));
                
                foreach ($queryWords as $word) {
                    $word = trim($word);
                    if ($word && strpos($searchContentLower, $word) === false) {
                        $match = false;
                        break;
                    }
                }
            }

            if ($opd && $match) {
                if (stripos($item['nama_satker'] ?? '', $opd) === false) {
                    $match = false;
                }
            }
            
            if ($metodeFilter && $match) {
                $m = $tipe === 'penyedia' ? ($item['metode_pengadaan'] ?? '') : ($item['tipe_swakelola'] ?? '');
                if (stripos($m, $metodeFilter) === false) {
                    $match = false;
                }
            }

            // Filter Jenis Pengadaan (Hanya berlaku untuk tipe Penyedia)
            if ($jenisFilter && $match && $tipe === 'penyedia') {
                if (stripos($item['jenis_pengadaan'] ?? '', $jenisFilter) === false) {
                    $match = false;
                }
            }

            if ($match) {
                $pagu = (float) ($item['pagu'] ?? 0);
                $totalPaguAggregated += $pagu;

                $results[] = [
                    'opd' => $item['nama_satker'] ?? '',
                    'nama_paket' => $item['nama_paket'] ?? '',
                    'pagu' => $pagu,
                    'jenis_pengadaan' => $item['jenis_pengadaan'] ?? '-',
                    'metode' => $tipe === 'penyedia' ? ($item['metode_pengadaan'] ?? '-') : ($item['tipe_swakelola'] ?? '-'),
                    'uraian' => $item['uraian_pekerjaan'] ?? ($item['urarian_pekerjaan'] ?? ($item['spesifikasi_pekerjaan'] ?? ($item['kegiatan'] ?? '-')))
                ];
            }
        }
        
        if ($sortPagu === 'terbesar') {
            usort($results, function($a, $b) {
                return $b['pagu'] <=> $a['pagu'];
            });
        } else if ($sortPagu === 'terkecil') {
            usort($results, function($a, $b) {
                return $a['pagu'] <=> $b['pagu'];
            });
        }

        $totalFound = count($results);
        // Limit the items inside PHP so OpenAI doesn't exceed max tokens
        $results = array_slice($results, 0, 15);

        if (empty($results)) {
            return json_encode(['message' => 'Tidak ditemukan data paket yang cocok.']);
        }

        return json_encode([
            'total_paket_ditemukan' => $totalFound,
            'total_pagu_seluruh_pencarian_rupiah' => $totalPaguAggregated,
            'keterangan' => $totalFound > 15 ? 'Menampilkan detail top 15 data saja.' : 'Menampilkan semua data detail.',
            'data_detail' => $results
        ]);
    }

    private function searchRealisasiPaket($argsStr)
    {
        $args = json_decode($argsStr, true);
        $query = $args['query'] ?? '';
        $opd = $args['opd'] ?? '';
        $type = $args['tipe'] ?? 'all'; // tender, nontender, pencatatan_nontender, swakelola, pencatatan_swakelola, epurchasing, all
        $year = $args['tahun'] ?? date('Y');
        $sortReal = $args['sort_realisasi'] ?? ''; // 'terbesar' atau 'terkecil'

        $db = \Config\Database::connect();
        
        $typesToSearch = [];
        if ($type === 'all') {
            $typesToSearch = ['tender', 'nontender', 'pencatatan_nontender', 'pencatatan_swakelola', 'epurchasing'];
        } else {
            $typesToSearch = [$type];
        }

        $results = [];
        $totalRealAggregated = 0;

        foreach ($typesToSearch as $t) {
            $builder = null;
            $realField = 'nilai_kontrak';
            
            if ($t === 'tender') {
                $builder = $db->table('lpse_tender');
                $realField = 'nilai_kontrak';
            } elseif ($t === 'nontender') {
                $builder = $db->table('lpse_nontender');
                $realField = 'nilai_kontrak';
            } elseif ($t === 'pencatatan_nontender') {
                $builder = $db->table('lpse_pencatatan_nontender');
                $realField = 'realisasi';
            } elseif ($t === 'swakelola' || $t === 'pencatatan_swakelola') {
                $builder = $db->table('lpse_pencatatan_swakelola');
                $realField = 'realisasi';
            } elseif ($t === 'epurchasing') {
                $builder = $db->table('epurchasing');
                $realField = 'total_harga';
            }

            if (!$builder) continue;

            // Strict Role/Satker checking
            $userRole = strtolower(session()->get('role') ?? '');
            $userSatker = session()->get('kd_satker');
            if (in_array($userRole, ['satker', 'opd']) && $userSatker) {
                $builder->where('kd_satker', $userSatker);
            }

            if ($year && $year !== 'all') {
                $builder->where('tahun', $year);
            }

            $data = $builder->get()->getResultArray();

            foreach ($data as $item) {
                // Pastikan hanya paket yang RESMI TAYANG (tidak batal, status valid, realisasi > 0)
                if (!self::isValidPublishedPackage($item, $t)) {
                    continue;
                }

                $match = true;

                // Search query against multiple text fields
                if ($query && $match) {
                    $searchContent = implode(' ', [
                        $item['nama_paket'] ?? '',
                        $item['nama_satker'] ?? '',
                        $item['nama_penyedia'] ?? '',
                        $item['nama_pelaksana'] ?? '',
                        $item['ket_realisasi'] ?? '',
                        $item['no_kontrak'] ?? '',
                        $item['no_pembayaran'] ?? ''
                    ]);

                    $searchContentLower = strtolower($searchContent);
                    $queryWords = explode(' ', strtolower($query));
                    
                    foreach ($queryWords as $word) {
                        $word = trim($word);
                        if ($word && strpos($searchContentLower, $word) === false) {
                            $match = false;
                            break;
                        }
                    }
                }

                if ($opd && $match) {
                    if (stripos($item['nama_satker'] ?? '', $opd) === false) {
                        $match = false;
                    }
                }

                if ($match) {
                    $realVal = (float)($item[$realField] ?? 0);
                    if ($t === 'nontender' && $realVal <= 0) {
                        $realVal = (float)($item['nilai_negosiasi'] ?? 0);
                    }
                    $paguVal = (float)($item['pagu'] ?? 0);
                    if ($paguVal < $realVal) {
                        $paguVal = $realVal;
                    }

                    $totalRealAggregated += $realVal;

                    $results[] = [
                        'tipe_realisasi' => $t,
                        'kd_rup' => $item['kd_rup'] ?? ($item['rup_code'] ?? '-'),
                        'nama_paket' => $item['nama_paket'] ?? '',
                        'opd' => $item['nama_satker'] ?? '',
                        'pagu' => $paguVal,
                        'realisasi' => $realVal,
                        'penyedia_atau_pelaksana' => $item['nama_penyedia'] ?? ($item['nama_pelaksana'] ?? '-'),
                        'keterangan' => $item['ket_realisasi'] ?? ($item['status'] ?? '-'),
                        'no_referensi' => $item['no_kontrak'] ?? ($item['no_pembayaran'] ?? '-')
                    ];
                }
            }
        }

        if ($sortReal === 'terbesar') {
            usort($results, function($a, $b) {
                return $b['realisasi'] <=> $a['realisasi'];
            });
        } else if ($sortReal === 'terkecil') {
            usort($results, function($a, $b) {
                return $a['realisasi'] <=> $b['realisasi'];
            });
        }

        $totalFound = count($results);
        $results = array_slice($results, 0, 15);

        if (empty($results)) {
            return json_encode(['message' => 'Tidak ditemukan data realisasi paket yang cocok dengan kriteria pencarian.']);
        }

        return json_encode([
            'total_paket_realisasi_ditemukan' => $totalFound,
            'total_realisasi_seluruh_pencarian_rupiah' => $totalRealAggregated,
            'keterangan' => $totalFound > 15 ? 'Menampilkan detail top 15 data realisasi saja.' : 'Menampilkan semua data detail realisasi.',
            'data_detail' => $results
        ]);
    }

    private function getOpdPaguRanking($argsStr)
    {
        $args = json_decode($argsStr, true);
        $tipe = $args['tipe'] ?? 'penyedia';
        $sort = $args['sort'] ?? 'terbesar'; // 'terbesar' atau 'terkecil'
        
        $data = $this->getRupData($tipe);
        
        // Group & sum by OPD
        $opdTotals = [];
        foreach ($data as $item) {
            $opd = $item['nama_satker'] ?? 'Tanpa Nama OPD';
            if (!isset($opdTotals[$opd])) {
                $opdTotals[$opd] = 0;
            }
            $opdTotals[$opd] += (float) ($item['pagu'] ?? 0);
        }
        
        if ($sort === 'terkecil') {
            asort($opdTotals);
        } else {
            arsort($opdTotals);
        }
        
        $results = [];
        $i = 1;
        foreach ($opdTotals as $opd => $totalPagu) {
            $results[] = [
                'peringkat' => $i,
                'opd' => $opd,
                'total_pagu_rupiah' => $totalPagu
            ];
            $i++;
            if ($i > 15) break; // Limit 15 OPD
        }

        $ket = $sort === 'terkecil' ? 'terkecil / terendah' : 'terbesar / tertinggi';

        return json_encode([
            'tipe' => $tipe,
            'keterangan' => "Menampilkan daftar Top 15 OPD dengan agregasi/akumulasi total pagu $ket.",
            'data' => $results
        ]);
    }

    private function getEpurchasingStats()
    {
        $year = date('Y');
        try {
            $dashboard = new \App\Controllers\Dashboard();
            $lpse = $dashboard->calculateDashboardLpseData($year, 'all');

            $pagu = $lpse['total_pagu']['E-Purchasing'] ?? 0;
            $realisasi = $lpse['total_realisasi']['E-Purchasing'] ?? 0;
            $count = $lpse['by_year']['E-Purchasing'][$year]['count'] ?? 0;
            $efisiensi = max(0, $pagu - $realisasi);

            // Cari Top Satker E-Purchasing yang tayang
            $db = \Config\Database::connect();
            $allowed = ['COMPLETED', 'ON PROCESS', 'PAYMENT OUTSIDE SYSTEM', 'WAITING PPK REVIEW', 'ON NEGOTIATION', 'WAITING SELLER CONFIRMATION', 'ON ADDENDUM'];
            $topRow = $db->table('epurchasing')
                ->where('tahun', $year)
                ->whereIn('status', $allowed)
                ->where("order_date IS NOT NULL AND order_date != '' AND order_date != '-' AND order_date != '0000-00-00' AND total_harga > 0")
                ->select('nama_satker, SUM(total_harga) as total')
                ->groupBy('nama_satker')
                ->orderBy('total', 'DESC')
                ->get()->getRowArray();
            $topSatker = $topRow ? $topRow['nama_satker'] . ' (Rp ' . number_format((float)$topRow['total'], 0, ',', '.') . ')' : '-';

            return json_encode([
                'keterangan' => 'Statistik E-Purchasing yang resmi tayang (status terverifikasi dan pesanan aktif)',
                'tahun_berjalan' => $year,
                'total_paket' => $count,
                'total_pagu_rupiah' => $pagu,
                'total_realisasi_rupiah' => $realisasi,
                'total_efisiensi_rupiah' => $efisiensi,
                'top_satker_saat_ini' => $topSatker
            ]);
        } catch (\Exception $e) {
            return json_encode(['error' => 'Gagal mengambil statistik E-Purchasing: ' . $e->getMessage()]);
        }
    }

    private function getLpseRealisasiStats($argsStr)
    {
        $args = json_decode($argsStr, true);
        $type = strtolower($args['tipe'] ?? 'all');
        $year = $args['tahun'] ?? date('Y');
        $quarter = $args['triwulan'] ?? 'all';

        try {
            $dashboard = new \App\Controllers\Dashboard();
            $lpse = $dashboard->calculateDashboardLpseData($year, $quarter);

            $labelMap = [
                'tender' => 'Tender',
                'nontender' => 'Non Tender',
                'non_tender' => 'Non Tender',
                'epurchasing' => 'E-Purchasing',
                'e_purchasing' => 'E-Purchasing',
                'pencatatan_nontender' => 'Pencatatan Non Tender',
                'pencatatan-nontender' => 'Pencatatan Non Tender',
                'swakelola' => 'Pencatatan Swakelola',
                'pencatatan_swakelola' => 'Pencatatan Swakelola',
                'pencatatan-swakelola' => 'Pencatatan Swakelola',
            ];

            if ($type === 'all' || !isset($labelMap[$type])) {
                $totalPagu = 0;
                $totalReal = 0;
                $totalPaket = 0;
                foreach ($lpse['labels'] as $lbl) {
                    $totalPagu += $lpse['total_pagu'][$lbl] ?? 0;
                    $totalReal += $lpse['total_realisasi'][$lbl] ?? 0;
                    $totalPaket += $lpse['by_year'][$lbl][$year]['count'] ?? 0;
                }
                $efisiensi = max(0, $totalPagu - $totalReal);
                $efPct = $totalPagu > 0 ? round(($efisiensi / $totalPagu) * 100, 2) : 0;

                return json_encode([
                    'keterangan' => 'Statistik seluruh realisasi pengadaan yang tayang resmi di Dashboard LPSE',
                    'tahun' => $year,
                    'triwulan' => $quarter === 'all' ? 'Semua Triwulan' : "Triwulan {$quarter}",
                    'total_paket' => $totalPaket,
                    'total_pagu_rupiah' => $totalPagu,
                    'total_realisasi_rupiah' => $totalReal,
                    'total_efisiensi_rupiah' => $efisiensi,
                    'persentase_efisiensi' => $efPct . '%',
                    'persentase_realisasi' => $totalPagu > 0 ? round(($totalReal / $totalPagu) * 100, 2) . '%' : '0%'
                ]);
            }

            $label = $labelMap[$type];
            $pagu = $lpse['total_pagu'][$label] ?? 0;
            $realisasi = $lpse['total_realisasi'][$label] ?? 0;
            $count = $lpse['by_year'][$label][$year]['count'] ?? 0;
            $efisiensi = max(0, $pagu - $realisasi);
            $efPct = $pagu > 0 ? round(($efisiensi / $pagu) * 100, 2) : 0;

            return json_encode([
                'keterangan' => "Statistik realisasi {$label} yang tayang resmi di Dashboard LPSE (hanya paket aktif yang selesai/berkontrak)",
                'tipe_pengadaan' => $label,
                'tahun' => $year,
                'triwulan' => $quarter === 'all' ? 'Semua Triwulan' : "Triwulan {$quarter}",
                'jumlah_paket' => $count,
                'total_pagu_rupiah' => $pagu,
                'total_realisasi_rupiah' => $realisasi,
                'total_efisiensi_rupiah' => $efisiensi,
                'persentase_efisiensi' => $efPct . '%',
                'persentase_realisasi' => $pagu > 0 ? round(($realisasi / $pagu) * 100, 2) . '%' : '0%'
            ]);
        } catch (\Exception $e) {
            return json_encode(['error' => 'Gagal mengambil statistik LPSE: ' . $e->getMessage()]);
        }
    }
    private function getMonevSummary()
    {
        $proyekModel = new \App\Models\MonevProyekModel();
        $jadwalModel = new \App\Models\MonevJadwalModel();
        $harianModel = new \App\Models\MonevHarianModel();
        $dashboard = new \App\Controllers\Dashboard();
        $dashboard->initController($this->request, $this->response, $this->logger);

        $proyekList = $proyekModel->findAll();
        
        $summaryData = [];
        $totalProyek = count($proyekList);
        $totalKritis = 0;
        
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
                $statusIndikator = 'Merah (Kritis)';
                $totalKritis++;
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
                'nama_proyek' => $p['nama_proyek'],
                'opd' => $p['nama_satker'] ?? 'Dinas Terkait',
                'penyedia' => $p['nama_penyedia'],
                'realisasi_kumulatif' => $realisasiTerakhir . '%',
                'deviasi_kumulatif' => $deviasiTerakhir . '%',
                'deviasi_mingguan' => $deviasiMingguan . '%',
                'status' => $statusIndikator
            ];
        }

        return json_encode([
            'keterangan' => 'Ringkasan Monitoring dan Evaluasi Proyek Berjalan',
            'total_proyek' => $totalProyek,
            'total_proyek_kritis' => $totalKritis,
            'detail_proyek' => $summaryData
        ]);
    }

    private function getRealisasiPerOpd($argsStr)
    {
        $args = json_decode($argsStr, true);
        $type = $args['tipe'] ?? 'tender'; // tender, nontender, swakelola, epurchasing
        $year = $args['tahun'] ?? date('Y');

        $db = \Config\Database::connect();
        $builder = null;
        $sumField = 'nilai_kontrak';

        if ($type === 'tender') {
            $builder = $db->table('lpse_tender');
            $builder->where("status NOT LIKE '%batal%' AND nilai_kontrak > 0");
            $sumField = 'nilai_kontrak';
        } elseif ($type === 'nontender') {
            $builder = $db->table('lpse_nontender');
            $builder->where("status NOT LIKE '%batal%' AND (nilai_kontrak > 0 OR nilai_negosiasi > 0)");
            $sumField = 'IF(nilai_kontrak > 0, nilai_kontrak, nilai_negosiasi)';
        } elseif ($type === 'pencatatan_nontender' || $type === 'pencatatan-nontender') {
            $builder = $db->table('lpse_pencatatan_nontender');
            $builder->where("status NOT LIKE '%batal%' AND realisasi > 0");
            $sumField = 'realisasi';
        } elseif ($type === 'swakelola' || $type === 'pencatatan_swakelola' || $type === 'pencatatan-swakelola') {
            $builder = $db->table('lpse_pencatatan_swakelola');
            $builder->where("realisasi > 0");
            $builder->groupStart()
                ->where('status_swakelola_pct_ket IS NULL')
                ->orWhere("LOWER(status_swakelola_pct_ket) NOT LIKE '%batal%'")
                ->groupEnd();
            $builder->groupStart()
                ->where('alasan_pembatalan IS NULL')
                ->orWhere("TRIM(alasan_pembatalan) = ''")
                ->groupEnd();
            $sumField = 'realisasi';
        } elseif ($type === 'epurchasing') {
            $allowed = ['COMPLETED', 'ON PROCESS', 'PAYMENT OUTSIDE SYSTEM', 'WAITING PPK REVIEW', 'ON NEGOTIATION', 'WAITING SELLER CONFIRMATION', 'ON ADDENDUM'];
            $builder = $db->table('epurchasing');
            $builder->whereIn('status', $allowed)
                ->where("order_date IS NOT NULL AND order_date != '' AND order_date != '-' AND order_date != '0000-00-00' AND total_harga > 0");
            $sumField = 'total_harga';
        }

        if (!$builder) {
            return json_encode(['error' => 'Tipe pengadaan tidak valid.']);
        }

        $builder->select("nama_satker as opd, SUM($sumField) as total_realisasi, COUNT(*) as jumlah_paket");
        if ($year && $year !== 'all') {
            $builder->where('tahun', $year);
        }
        $builder->groupBy('nama_satker');
        $builder->orderBy('total_realisasi', 'DESC');
        $data = $builder->get()->getResultArray();

        $totalKeseluruhan = 0;
        foreach ($data as $k => $v) {
            $data[$k]['total_realisasi'] = (float) $v['total_realisasi'];
            $totalKeseluruhan += $data[$k]['total_realisasi'];
        }

        return json_encode([
            'tipe_pengadaan' => $type,
            'tahun' => $year,
            'total_realisasi_seluruh_opd' => $totalKeseluruhan,
            'data_per_opd' => $data
        ]);
    }

    private function findPaketWinner($query)
    {
        $model = new EpurchasingModel();
        // Try to find a winner entry first (status indicating winner), fallback to any matching paket
        $winner = $model->where('status', 'pemenang')->like('nama_paket', $query)->first();
        if ($winner) return $winner;
        return $model->like('nama_paket', $query)->first();
    }
    private function _buildDataContext()
    {
        $db   = \Config\Database::connect();
        $year = date('Y');
        $ctx  = "=== DATA RESMI PENGADAAN YANG TAYANG (Tahun {$year}) ===\n";

        // 1. RUP Penyedia & Swakelola (Terumumkan)
        try {
            $pagu = $db->table('rup_penyedia')->where('tahun', $year)->where('status_umumkan_rup', 'Terumumkan')->selectSum('pagu', 'total')->get()->getRow();
            $jml  = $db->table('rup_penyedia')->where('tahun', $year)->where('status_umumkan_rup', 'Terumumkan')->countAllResults();
            $ctx .= "RUP Penyedia (Terumumkan): Total Pagu = Rp " . number_format($pagu->total ?? 0, 0, ',', '.') . " ({$jml} paket)\n";
        } catch (\Exception $e) { $ctx .= "RUP Penyedia: Data tidak tersedia\n"; }

        try {
            $pagus = $db->table('rup_swakelola')->where('tahun', $year)->where('status_umumkan_rup', 'Terumumkan')->selectSum('pagu', 'total')->get()->getRow();
            $jmls  = $db->table('rup_swakelola')->where('tahun', $year)->where('status_umumkan_rup', 'Terumumkan')->countAllResults();
            $ctx .= "RUP Swakelola (Terumumkan): Total Pagu = Rp " . number_format($pagus->total ?? 0, 0, ',', '.') . " ({$jmls} paket)\n";
        } catch (\Exception $e) { $ctx .= "RUP Swakelola: Data tidak tersedia\n"; }

        // 2. Realisasi Pengadaan LPSE & E-Purchasing yang Tayang Resmi di Dashboard
        try {
            $dashboard = new \App\Controllers\Dashboard();
            $lpse = $dashboard->calculateDashboardLpseData($year, 'all');

            $allPagu = 0;
            $allReal = 0;
            foreach ($lpse['labels'] as $lbl) {
                $allPagu += $lpse['total_pagu'][$lbl] ?? 0;
                $allReal += $lpse['total_realisasi'][$lbl] ?? 0;
            }
            $allEf = max(0, $allPagu - $allReal);
            $allEfPct = $allPagu > 0 ? round(($allEf / $allPagu) * 100, 1) : 0;

            $ctx .= "\nIKHTISAR REALISASI RESMI TAYANG (LPSE & E-PURCHASING):\n";
            $ctx .= "- Total Pagu Paket Terealisasi: Rp " . number_format($allPagu, 0, ',', '.') . "\n";
            $ctx .= "- Total Realisasi Penyerapan: Rp " . number_format($allReal, 0, ',', '.') . "\n";
            $ctx .= "- Total Efisiensi Pengadaan: Rp " . number_format($allEf, 0, ',', '.') . " ({$allEfPct}%)\n";

            $ctx .= "\nRINCIAN REALISASI PER METODE PENGADAAN (RESMI TAYANG):\n";
            foreach ($lpse['labels'] as $lbl) {
                $paguM = $lpse['total_pagu'][$lbl] ?? 0;
                $realM = $lpse['total_realisasi'][$lbl] ?? 0;
                $efM = max(0, $paguM - $realM);
                $efPctM = $paguM > 0 ? round(($efM / $paguM) * 100, 1) : 0;
                $cntM = $lpse['by_year'][$lbl][$year]['count'] ?? 0;
                $ctx .= "  - {$lbl}: Realisasi = Rp " . number_format($realM, 0, ',', '.') . " | Pagu = Rp " . number_format($paguM, 0, ',', '.') . " | Efisiensi = Rp " . number_format($efM, 0, ',', '.') . " ({$efPctM}%) | Jumlah = {$cntM} paket\n";
            }

            if (!empty($lpse['by_quarter'])) {
                $ctx .= "\nTREN REALISASI PER TRIWULAN TAHUN {$year} (RESMI TAYANG):\n";
                $qNames = [1 => 'Triwulan I (Q1)', 2 => 'Triwulan II (Q2)', 3 => 'Triwulan III (Q3)', 4 => 'Triwulan IV (Q4)'];
                foreach ($qNames as $qNum => $qTitle) {
                    $qData = $lpse['by_quarter'][$qNum] ?? null;
                    if ($qData) {
                        $ctx .= "  - {$qTitle}: Rp " . number_format($qData['total'] ?? 0, 0, ',', '.') . " ({$qData['count']} paket)\n";
                    }
                }
            }
        } catch (\Exception $e) {
            $ctx .= "Data Realisasi LPSE: Gagal memuat data tayang\n";
        }

        // 3. Monev Proyek
        try {
            $totalProyek = $db->table('monev_proyek')->countAllResults();
            $statusMonev = $db->query(
                "SELECT status, COUNT(*) as jml FROM monev_proyek GROUP BY status ORDER BY jml DESC"
            )->getResultArray();
            $ctx .= "\nMonev Proyek: Total={$totalProyek} proyek\n";
            foreach ($statusMonev as $s) {
                $st = $s['status'] ?: '(tanpa status)';
                $ctx .= "  - Status '{$st}': {$s['jml']} proyek\n";
            }
        } catch (\Exception $e) { $ctx .= "Monev Proyek: Data tidak tersedia\n"; }

        $ctx .= "=== AKHIR DATA ===\n";
        return $ctx;
    }


    private function _buildSmartContext($message)
    {
        $db   = \Config\Database::connect();
        $year = date('Y');
        $ctx  = '';
        $msgLower = strtolower($message);

        // Helper: extract keyword by stripping common stopwords
        $stopwords = ['berapa', 'total', 'data', 'semua', 'seluruh', 'realisasi', 'pagu', 'nilai', 'kontrak',
                      'pengadaan', 'paket', 'tender', 'non-tender', 'epurchasing', 'e-purchasing', 'katalog',
                      'tampilkan', 'cari', 'ada', 'yang', 'dari', 'pada', 'di', 'ke', 'dan', 'atau',
                      'untuk', 'dengan', 'tahun', 'tahu', 'berjalan', 'ini'];
        $words = array_filter(explode(' ', preg_replace('/[^\w\s]/u', ' ', $msgLower)));
        $keywords = array_values(array_diff($words, $stopwords));
        $mainKeyword = count($keywords) > 0 ? $keywords[0] : '';

        // 1. Pencarian spesifik nama paket / barang di seluruh tabel realisasi
        if ($mainKeyword && strlen($mainKeyword) >= 3) {
            $ctx .= "\n=== HASIL PENCARIAN PAKET TERKAIT: '{$mainKeyword}' ===\n";
            $found = false;

            // E-Purchasing (Hanya paket sah tayang)
            try {
                $allowed = ['COMPLETED', 'ON PROCESS', 'PAYMENT OUTSIDE SYSTEM', 'WAITING PPK REVIEW', 'ON NEGOTIATION', 'WAITING SELLER CONFIRMATION', 'ON ADDENDUM'];
                $builder = $db->table('epurchasing')
                    ->where('tahun', $year)
                    ->whereIn('status', $allowed)
                    ->where("order_date IS NOT NULL AND order_date != '' AND order_date != '-' AND order_date != '0000-00-00' AND total_harga > 0")
                    ->groupStart()
                        ->like('nama_paket', $mainKeyword)
                        ->orLike('nama_penyedia', $mainKeyword)
                    ->groupEnd();
                
                $agg = clone $builder;
                $aggRes = $agg->select('SUM(total_harga) as total, COUNT(id) as jml')->get()->getRow();
                
                $epRows = $builder->select('nama_paket, nama_penyedia, nama_satker, total_harga')
                    ->orderBy('total_harga', 'DESC')->limit(5)->get()->getResultArray();

                if ($epRows || ($aggRes && $aggRes->jml > 0)) {
                    $found = true;
                    $ctx .= "[E-Purchasing]\n";
                    if ($aggRes && $aggRes->jml > 0) {
                        $ctx .= "  Total Transaksi '{$mainKeyword}': Rp " . number_format($aggRes->total ?? 0, 0, ',', '.') . " (dari {$aggRes->jml} paket)\n";
                    }
                    if ($epRows) {
                        $ctx .= "  Top 5 Paket Terbesar:\n";
                        foreach ($epRows as $r) {
                            $ctx .= "    - {$r['nama_paket']} | Penyedia: {$r['nama_penyedia']} | OPD: {$r['nama_satker']} | Nilai: Rp " . number_format($r['total_harga'],0,',','.') . "\n";
                        }
                    }
                }
            } catch (\Exception $e) {}

            // Tender (Hanya paket sah tayang)
            try {
                $builder = $db->table('lpse_tender')
                    ->where('tahun', $year)
                    ->where("status NOT LIKE '%batal%' AND nilai_kontrak > 0")
                    ->groupStart()
                        ->like('nama_paket', $mainKeyword)
                        ->orLike('pemenang', $mainKeyword)
                    ->groupEnd();
                
                $agg = clone $builder;
                $aggRes = $agg->select('SUM(nilai_kontrak) as total, COUNT(id) as jml')->get()->getRow();

                $tRows = $builder->select('nama_paket, pemenang, nama_satker, nilai_kontrak')
                    ->orderBy('nilai_kontrak', 'DESC')->limit(5)->get()->getResultArray();
                
                if ($tRows || ($aggRes && $aggRes->jml > 0)) {
                    $found = true;
                    $ctx .= "[Tender]\n";
                    if ($aggRes && $aggRes->jml > 0) {
                        $ctx .= "  Total Tender '{$mainKeyword}': Rp " . number_format($aggRes->total ?? 0, 0, ',', '.') . " (dari {$aggRes->jml} paket)\n";
                    }
                    if ($tRows) {
                        $ctx .= "  Top 5 Paket Terbesar:\n";
                        foreach ($tRows as $r) {
                            $ctx .= "    - {$r['nama_paket']} | Pemenang: {$r['pemenang']} | OPD: {$r['nama_satker']} | Nilai: Rp " . number_format($r['nilai_kontrak'],0,',','.') . "\n";
                        }
                    }
                }
            } catch (\Exception $e) {}

            // Non-Tender (Hanya paket sah tayang)
            try {
                $builder = $db->table('lpse_nontender')
                    ->where('tahun', $year)
                    ->where("status NOT LIKE '%batal%' AND (nilai_kontrak > 0 OR nilai_negosiasi > 0)")
                    ->groupStart()
                        ->like('nama_paket', $mainKeyword)
                        ->orLike('pemenang', $mainKeyword)
                    ->groupEnd();
                
                $agg = clone $builder;
                $aggRes = $agg->select('SUM(IF(nilai_kontrak > 0, nilai_kontrak, nilai_negosiasi)) as total, COUNT(id) as jml')->get()->getRow();

                $ntRows = $builder->select('nama_paket, pemenang, nama_satker, IF(nilai_kontrak > 0, nilai_kontrak, nilai_negosiasi) as nilai_kontrak')
                    ->orderBy('nilai_kontrak', 'DESC')->limit(5)->get()->getResultArray();
                
                if ($ntRows || ($aggRes && $aggRes->jml > 0)) {
                    $found = true;
                    $ctx .= "[Non-Tender]\n";
                    if ($aggRes && $aggRes->jml > 0) {
                        $ctx .= "  Total Non-Tender '{$mainKeyword}': Rp " . number_format($aggRes->total ?? 0, 0, ',', '.') . " (dari {$aggRes->jml} paket)\n";
                    }
                    if ($ntRows) {
                        $ctx .= "  Top 5 Paket Terbesar:\n";
                        foreach ($ntRows as $r) {
                            $ctx .= "    - {$r['nama_paket']} | Pemenang: {$r['pemenang']} | OPD: {$r['nama_satker']} | Nilai: Rp " . number_format($r['nilai_kontrak'],0,',','.') . "\n";
                        }
                    }
                }
            } catch (\Exception $e) {}

            // Pencatatan Non-Tender (Hanya paket sah tayang)
            try {
                $builder = $db->table('lpse_pencatatan_nontender')
                    ->where('tahun', $year)
                    ->where("status NOT LIKE '%batal%' AND realisasi > 0")
                    ->groupStart()
                        ->like('nama_paket', $mainKeyword)
                        ->orLike('nama_penyedia', $mainKeyword)
                    ->groupEnd();
                
                $agg = clone $builder;
                $aggRes = $agg->select('SUM(realisasi) as total, COUNT(id) as jml')->get()->getRow();

                $pntRows = $builder->select('nama_paket, nama_penyedia, nama_satker, realisasi')
                    ->orderBy('realisasi', 'DESC')->limit(5)->get()->getResultArray();

                if ($pntRows || ($aggRes && $aggRes->jml > 0)) {
                    $found = true;
                    $ctx .= "[Pencatatan Non-Tender]\n";
                    if ($aggRes && $aggRes->jml > 0) {
                        $ctx .= "  Total Realisasi '{$mainKeyword}': Rp " . number_format($aggRes->total ?? 0, 0, ',', '.') . " (dari {$aggRes->jml} paket)\n";
                    }
                    if ($pntRows) {
                        $ctx .= "  Top 5 Paket Terbesar:\n";
                        foreach ($pntRows as $r) {
                            $ctx .= "    - {$r['nama_paket']} | Penyedia: {$r['nama_penyedia']} | OPD: {$r['nama_satker']} | Realisasi: Rp " . number_format($r['realisasi'],0,',','.') . "\n";
                        }
                    }
                }
            } catch (\Exception $e) {}

            // RUP Penyedia
            try {
                $builder = $db->table('rup_penyedia')
                    ->where('tahun', $year)
                    ->groupStart()
                        ->like('nama_paket', $mainKeyword)
                        ->orLike('uraian_pekerjaan', $mainKeyword)
                        ->orLike('spesifikasi_pekerjaan', $mainKeyword)
                    ->groupEnd();
                
                $agg = clone $builder;
                $aggRes = $agg->select('SUM(pagu) as total, COUNT(id) as jml')->get()->getRow();

                $rupRows = $builder->select('nama_paket, nama_satker, pagu, metode_pengadaan')
                    ->orderBy('pagu', 'DESC')->limit(5)->get()->getResultArray();

                if ($rupRows || ($aggRes && $aggRes->jml > 0)) {
                    $found = true;
                    $ctx .= "[RUP Penyedia]\n";
                    if ($aggRes && $aggRes->jml > 0) {
                        $ctx .= "  Total Pagu '{$mainKeyword}': Rp " . number_format($aggRes->total ?? 0, 0, ',', '.') . " (dari {$aggRes->jml} paket)\n";
                    }
                    if ($rupRows) {
                        $ctx .= "  Top 5 Paket Terbesar:\n";
                        foreach ($rupRows as $r) {
                            $ctx .= "    - {$r['nama_paket']} | OPD: {$r['nama_satker']} | Pagu: Rp " . number_format($r['pagu'],0,',','.') . " | Metode: {$r['metode_pengadaan']}\n";
                        }
                    }
                }
            } catch (\Exception $e) {}

            if (!$found) {
                $ctx .= "  (Tidak ditemukan paket terkait kata kunci '{$mainKeyword}' di tahun {$year})\n";
            }
            $ctx .= "=== AKHIR HASIL PENCARIAN ===\n";
        }

        // 2. Jika ada keyword OPD / Satker, tampilkan realisasi per OPD tersebut
        $opdKeywords = ['dinas', 'badan', 'kantor', 'kecamatan', 'sekretariat', 'rsud', 'uptd', 'opd', 'satker'];
        foreach ($opdKeywords as $ok) {
            if (strpos($msgLower, $ok) !== false) {
                // Cari nama OPD dari pesan
                if (preg_match('/' . $ok . '[\s]+([\w\s]+)/iu', $message, $match)) {
                    $opdName = trim($match[1]);
                    if (strlen($opdName) >= 3) {
                        $ctx .= "\n=== DATA OPD: '{$opdName}' ===\n";
                        try {
                            $allowed = ['COMPLETED', 'ON PROCESS', 'PAYMENT OUTSIDE SYSTEM', 'WAITING PPK REVIEW', 'ON NEGOTIATION', 'WAITING SELLER CONFIRMATION', 'ON ADDENDUM'];
                            $tRes = $db->table('lpse_tender')->where('tahun', $year)->where("status NOT LIKE '%batal%' AND nilai_kontrak > 0")->like('nama_satker', $opdName)->selectSum('nilai_kontrak','total')->get()->getRow();
                            $ntRes = $db->table('lpse_nontender')->where('tahun', $year)->where("status NOT LIKE '%batal%' AND (nilai_kontrak > 0 OR nilai_negosiasi > 0)")->like('nama_satker', $opdName)->selectSum('IF(nilai_kontrak > 0, nilai_kontrak, nilai_negosiasi)','total')->get()->getRow();
                            $epRes = $db->table('epurchasing')->where('tahun', $year)->whereIn('status', $allowed)->where("order_date IS NOT NULL AND order_date != '' AND order_date != '-' AND order_date != '0000-00-00' AND total_harga > 0")->like('nama_satker', $opdName)->selectSum('total_harga','total')->get()->getRow();
                            $pntRes = $db->table('lpse_pencatatan_nontender')->where('tahun', $year)->where("status NOT LIKE '%batal%' AND realisasi > 0")->like('nama_satker', $opdName)->selectSum('realisasi','total')->get()->getRow();
                            $pswRes = $db->table('lpse_pencatatan_swakelola')->where('tahun', $year)->where("realisasi > 0 AND (status_swakelola_pct_ket NOT LIKE '%batal%' OR status_swakelola_pct_ket IS NULL) AND (alasan_pembatalan IS NULL OR TRIM(alasan_pembatalan) = '')")->like('nama_satker', $opdName)->selectSum('realisasi','total')->get()->getRow();
                            
                            $ctx .= "  Realisasi Tender (Sah): Rp " . number_format($tRes->total ?? 0, 0, ',', '.') . "\n";
                            $ctx .= "  Realisasi Non-Tender (Sah): Rp " . number_format($ntRes->total ?? 0, 0, ',', '.') . "\n";
                            $ctx .= "  Realisasi E-Purchasing (Sah): Rp " . number_format($epRes->total ?? 0, 0, ',', '.') . "\n";
                            $ctx .= "  Pencatatan Non-Tender (Sah): Rp " . number_format($pntRes->total ?? 0, 0, ',', '.') . "\n";
                            $ctx .= "  Pencatatan Swakelola (Sah): Rp " . number_format($pswRes->total ?? 0, 0, ',', '.') . "\n";
                        } catch (\Exception $e) {}
                        $ctx .= "=== AKHIR DATA OPD ===\n";
                    }
                }
                break;
            }
        }

        // 3. Jika ada keyword penyedia / vendor
        if (preg_match('/(?:penyedia|vendor|rekanan|cv\.?|pt\.?)\s+([\w\s]+)/iu', $message, $vmatch)) {
            $vendorName = trim($vmatch[1]);
            if (strlen($vendorName) >= 3) {
                $ctx .= "\n=== DATA PENYEDIA (RESMI TAYANG): '{$vendorName}' ===\n";
                try {
                    $allowed = ['COMPLETED', 'ON PROCESS', 'PAYMENT OUTSIDE SYSTEM', 'WAITING PPK REVIEW', 'ON NEGOTIATION', 'WAITING SELLER CONFIRMATION', 'ON ADDENDUM'];
                    $tVal = $db->table('lpse_tender')->like('pemenang', $vendorName)->where('tahun', $year)->where("status NOT LIKE '%batal%' AND nilai_kontrak > 0")->selectSum('nilai_kontrak','total')->get()->getRow();
                    $tVend = $db->table('lpse_tender')->like('pemenang', $vendorName)->where('tahun', $year)->where("status NOT LIKE '%batal%' AND nilai_kontrak > 0")->countAllResults();
                    $epVend = $db->table('epurchasing')->like('nama_penyedia', $vendorName)->where('tahun', $year)->whereIn('status', $allowed)->where("order_date IS NOT NULL AND order_date != '' AND order_date != '-' AND order_date != '0000-00-00' AND total_harga > 0")->selectSum('total_harga','total')->get()->getRow();
                    $ctx .= "  Tender (Sah): {$tVend} paket, Total: Rp " . number_format($tVal->total ?? 0, 0, ',', '.') . "\n";
                    $ctx .= "  E-Purchasing (Sah): Rp " . number_format($epVend->total ?? 0, 0, ',', '.') . "\n";
                } catch (\Exception $e) {}
            }
        }

        // --- KNOWLEDGE BASE RAG SEARCH ---
        // Cari referensi dari library kepintaran AI untuk setiap pertanyaan
        try {
            $kbJson = $this->searchKnowledgeBase(json_encode(['query' => $message]));
            $kbParsed = json_decode($kbJson, true);
            if (isset($kbParsed['top_relevant_rules']) && is_array($kbParsed['top_relevant_rules'])) {
                $hasKb = false;
                $kbCtx = "\n=== REFERENSI DARI PUSTAKA / KNOWLEDGE BASE ===\n";
                foreach ($kbParsed['top_relevant_rules'] as $kbItem) {
                    // Hanya ambil jika kemiripan (similarity) cukup baik (misal > 0.20)
                    if (($kbItem['similarity'] ?? 0) > 0.20) {
                        $hasKb = true;
                        $kbCtx .= "- Sumber: " . ($kbItem['document'] ?? 'Pustaka') . "\n";
                        $kbCtx .= "  Isi: " . ($kbItem['text'] ?? '') . "\n\n";
                    }
                }
                if ($hasKb) {
                    $kbCtx .= "INSTRUKSI PENTING: Anda WAJIB memprioritaskan dan menggunakan informasi pada REFERENSI DARI PUSTAKA di atas untuk menjawab pertanyaan user secara akurat.\n";
                    $ctx .= $kbCtx;
                }
            }
        } catch (\Exception $e) {}

        return $ctx;
    }
    private function callOpenAI($message, $apiKey, $model, $url, $history = [], $smartContext = '')
    {
        // Auto-format API URL to ensure it points to the chat endpoint
        $url = rtrim($url, '/');
        if (!preg_match('/\/chat\/completions$/i', $url)) {
            $url .= '/chat/completions';
        }

        // Build data context
        $dataContext = $this->_buildDataContext();
        // Merge smart context (specific search results)
        if ($smartContext) {
            $dataContext .= "\n" . $smartContext;
        }

        $systemPrompt = "1. IDENTITAS & PERAN
Anda adalah Asisten Pengadaan — asisten AI internal yang membantu tim pengadaan (PPK, Pokja Pemilihan, PP, PPTK, staf procurement, dan pengguna terkait) dalam merencanakan, menyusun, memeriksa, dan menelusuri proses pengadaan barang/jasa.

Anda terhubung ke dua sumber data:

Database Pengadaan: data transaksional — paket pengadaan, status tender/seleksi, RUP (Rencana Umum Pengadaan), kontrak, vendor/penyedia, HPS, anggaran, jadwal, dokumen pemilihan.
Library Pengadaan: basis pengetahuan — regulasi (Perpres, Perlem LKPP, Permen terkait), SOP internal, template dokumen (KAK, HPS, RKS/SDP, kontrak), pedoman teknis, dan preseden/kasus sebelumnya.

Tujuan Anda: membuat proses pengadaan lebih cepat, akurat, sesuai regulasi, dan terdokumentasi dengan baik, tanpa menggantikan keputusan resmi yang menjadi kewenangan pejabat berwenang (PPK/Pokja/KPA/PA).

KEMAMPUAN UTAMA (CAPABILITIES) & ALAT BANTU (TOOLS)
Anda memiliki akses ke berbagai fungsi/tool untuk mencari data langsung di Database Pengadaan dan Library Kepintaran (Knowledge Base). Gunakan fungsi/tool berikut secara dinamis untuk menjawab pertanyaan spesifik atau melakukan rangkuman data:
- `searchPaket`: Mencari data RUP Penyedia/Swakelola.
- `searchRealisasiPaket`: Mencari data realisasi paket pengadaan (tender, non-tender, e-purchasing, swakelola).
- `searchKnowledgeBase`: Mencari aturan pengadaan & SOP (RAG).
- `getVendorPerformance`: Menganalisis riwayat kinerja vendor.
- `analyzeMonevProject`: Menganalisis proyek berjalan dan deviasi S-Curve.
- `getPaguSummary`: Ringkasan total pagu RUP.
- `getOpdPaguRanking`: Peringkat OPD berdasarkan pagu.
- `getEpurchasingStats`: Statistik transaksi E-Purchasing.
- `getLpseRealisasiStats`: Statistik realisasi paket LPSE.
- `getMonevSummary`: Ringkasan monitoring evaluasi proyek kritis.
- `getRealisasiPerOpd`: Realisasi pengadaan per OPD.

Aturan Penting:
1. Jika pertanyaan pengguna memerlukan data detail dari database (misal status paket tertentu, kinerja vendor, atau analisis kendala proyek), gunakan tool yang sesuai terlebih dahulu. Jangan mengarang data!
2. Data realisasi dan pagu yang Anda rujuk dan sampaikan kepada pengguna WAJIB merupakan DATA YANG RESMI TAYANG di aplikasi InfoAJIP / Dashboard LPSE yang telah melewati filter validasi:
   - Paket yang dibatalkan TIDAK BOLEH dihitung atau ditayangkan sebagai realisasi.
   - Paket dengan nilai realisasi 0 TIDAK BOLEH dihitung sebagai penyerapan anggaran.
   - Paket E-Purchasing yang ditayangkan hanyalah paket berstatus resmi terverifikasi dengan tanggal pesanan yang sah.
   - Jika pengguna menanyakan total realisasi, per metode, atau per triwulan, SELALU gunakan angka resmi pada konteks DATA RESMI PENGADAAN YANG TAYANG atau hasil dari fungsi `getLpseRealisasiStats`.
3. Jika database dan library memberikan informasi yang bertentangan atau ambigu, tampilkan keduanya secara transparan dan sarankan konfirmasi ke pejabat berwenang, jangan menebak.
4. Anda dapat melakukan pemanggilan beberapa tool secara berurutan untuk menyelesaikan tugas yang kompleks (misal mengambil daftar proyek kritis kemudian mencari aturan penanganan keterlambatan proyek).
5. Untuk perangkuman, ambil datanya menggunakan tool database terlebih dahulu, lalu lakukan perangkuman berbasis data yang didapat.

GAYA KOMUNIKASI
Gunakan Bahasa Indonesia formal-profesional, jelas, dan ringkas. Boleh menyesuaikan ke istilah teknis pengadaan (PPK, Pokja, HPS, RUP, dst) tanpa perlu menjelaskan ulang kecuali diminta.
Untuk jawaban faktual/data: langsung ke inti, gunakan tabel/daftar bila membantu keterbacaan.
Di akhir setiap jawabanmu, SELALU berikan daftar pilihan lanjutan menggunakan nomor urut (1, 2, 3) agar user bisa membalas dengan nomor untuk pertanyaan selanjutnya. Pastikan daftar pilihan relevan dengan konteks pembahasan saat ini.

{$dataContext}";

        $settingsModel = new \App\Models\SettingsModel();
        $customPrompt = $settingsModel->getSetting('chatbot_system_prompt', '');
        if (!empty(trim($customPrompt))) {
            $systemPrompt .= "\n\n=== KEMAMPUAN / INSTRUKSI TAMBAHAN (DARI PENGATURAN) ===\n" . trim($customPrompt);
        }

        $messages = [];
        $isOpenSource = preg_match('/gemma|llama|mistral|mixtral|qwen|deepseek/i', $model);

        $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        
        foreach ($history as $h) {
            if (isset($h['role']) && in_array($h['role'], ['user', 'assistant'])) {
                $filteredMsg = ['role' => $h['role'], 'content' => $h['content'] ?? ''];
                if (isset($h['tool_calls'])) {
                    $filteredMsg['tool_calls'] = $h['tool_calls'];
                }
                if (!empty($filteredMsg['content']) || !empty($filteredMsg['tool_calls'])) {
                    $messages[] = $filteredMsg;
                }
            } elseif (isset($h['role']) && $h['role'] === 'tool') {
                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $h['tool_call_id'] ?? '',
                    'name' => $h['name'] ?? '',
                    'content' => $h['content'] ?? ''
                ];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        $tools = $this->getChatbotTools();
        
        $loopCount = 0;
        $maxLoops = 5;
        $useTools = true;

        while ($loopCount < $maxLoops) {
            $data = [
                'model'       => $model,
                'messages'    => $messages,
                'max_tokens'  => 2000,
                'temperature' => 0.7
            ];
            
            if ($useTools && !empty($tools)) {
                $data['tools'] = $tools;
            }

            $retryCount = 0;
            $maxRetries = 3;
            $delay = 1000000;
            $response = '';
            $error = '';
            $responseArray = [];

            do {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey,
                    'api-key: ' . $apiKey
                ]);

                $response = curl_exec($ch);
                $error    = curl_error($ch);
                curl_close($ch);

                // Overwrite on first loop, append on later loops
                if ($loopCount === 0 && $retryCount === 0) {
                    file_put_contents(WRITEPATH . 'logs/debug_chat.log', "=== NEW CONVERSATION ===\n");
                }
                file_put_contents(WRITEPATH . 'logs/debug_chat.log', "Loop: {$loopCount} | Payload: " . json_encode($data) . "\nResponse: " . $response . "\nCurlError: " . $error . "\n", FILE_APPEND);

                if ($error) break;

                $responseArray = json_decode($response, true);
                if (isset($responseArray['error'])) {
                    $errCode = $responseArray['error']['code'] ?? '';
                    $errMsg  = $responseArray['error']['message'] ?? '';
                    if ($errCode == '429' || strpos(strtolower($errMsg), 'too many requests') !== false || strpos(strtolower($errMsg), 'rate limit') !== false) {
                        $retryCount++;
                        if ($retryCount <= $maxRetries) {
                            usleep($delay);
                            $delay *= 2;
                            continue;
                        }
                    }
                }
                break;
            } while ($retryCount <= $maxRetries);

            if ($error) {
                return ['error' => $error];
            }

            if (isset($responseArray['error'])) {
                $errMsg = strtolower($responseArray['error']['message'] ?? '');
                if ($useTools && (
                    strpos($errMsg, 'tool') !== false || 
                    strpos($errMsg, 'parameter') !== false || 
                    strpos($errMsg, 'invalid') !== false || 
                    strpos($errMsg, 'unsupported') !== false
                )) {
                    $useTools = false;
                    file_put_contents(WRITEPATH . 'logs/debug_chat.log', "Error detected on tools parameter. Retrying without tools. Error: " . json_encode($responseArray['error']) . "\n", FILE_APPEND);
                    
                    if ($isOpenSource) {
                        $messages = [];
                        $combinedMessage = "=== INSTRUKSI SISTEM & DATA (BACA INI DULU) ===\n" . $systemPrompt;
                        
                        foreach ($history as $h) {
                            if (isset($h['role']) && in_array($h['role'], ['user', 'assistant'])) {
                                $filteredMsg = ['role' => $h['role'], 'content' => $h['content'] ?? ''];
                                if (!empty($filteredMsg['content'])) {
                                    $messages[] = $filteredMsg;
                                }
                            }
                        }
                        
                        $combinedMessage .= "\n\n=== PERTANYAAN USER ===\n" . $message;
                        $messages[] = ['role' => 'user', 'content' => $combinedMessage];
                    } else {
                        $messages = [];
                        $messages[] = ['role' => 'system', 'content' => $systemPrompt];
                        foreach ($history as $h) {
                            if (isset($h['role']) && in_array($h['role'], ['user', 'assistant'])) {
                                $filteredMsg = ['role' => $h['role'], 'content' => $h['content'] ?? ''];
                                if (!empty($filteredMsg['content'])) {
                                    $messages[] = $filteredMsg;
                                }
                            }
                        }
                        $messages[] = ['role' => 'user', 'content' => $message];
                    }
                    
                    $loopCount = 0;
                    continue;
                }
                
                return $responseArray;
            }

            $choiceMessage = $responseArray['choices'][0]['message'] ?? null;
            if (!$choiceMessage) {
                return ['error' => 'Invalid response from AI'];
            }

            if (!empty($choiceMessage['tool_calls'])) {
                $messages[] = $choiceMessage;

                foreach ($choiceMessage['tool_calls'] as $toolCall) {
                    $funcName = $toolCall['function']['name'];
                    $argsStr = $toolCall['function']['arguments'];

                    file_put_contents(WRITEPATH . 'logs/debug_chat.log', "Executing Tool: {$funcName} | Args: {$argsStr}\n", FILE_APPEND);

                    $toolResult = '';
                    if (method_exists($this, $funcName)) {
                        try {
                            if (in_array($funcName, ['getEpurchasingStats', 'getMonevSummary'])) {
                                $toolResult = $this->$funcName();
                            } else {
                                $toolResult = $this->$funcName($argsStr);
                            }
                        } catch (\Exception $ex) {
                            $toolResult = json_encode(['error' => 'Gagal mengeksekusi tool: ' . $ex->getMessage()]);
                        }
                    } else {
                        $toolResult = json_encode(['error' => "Fungsi tool '{$funcName}' tidak ditemukan."]);
                    }

                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall['id'],
                        'name' => $funcName,
                        'content' => $toolResult
                    ];
                }

                $loopCount++;
                continue;
            }

            return $responseArray;
        }

        return $responseArray;
    }

    private function getChatbotTools()
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'searchPaket',
                    'description' => 'Mencari data Rencana Umum Pengadaan (RUP) berdasarkan kata kunci, OPD, metode, jenis pengadaan, tipe (penyedia/swakelola), dan mengurutkan pagu.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => ['type' => 'string', 'description' => 'Kata kunci nama paket atau uraian pekerjaan'],
                            'opd' => ['type' => 'string', 'description' => 'Filter nama OPD / Satker'],
                            'metode' => ['type' => 'string', 'description' => 'Metode pengadaan (misal: tender, pengadaan langsung, e-purchasing)'],
                            'jenis_pengadaan' => ['type' => 'string', 'description' => 'Jenis pengadaan (misal: barang, pekerjaan konstruksi, jasa konsultansi)'],
                            'tipe' => ['type' => 'string', 'enum' => ['penyedia', 'swakelola'], 'description' => 'Tipe pengadaan, default: penyedia'],
                            'sort_pagu' => ['type' => 'string', 'enum' => ['terbesar', 'terkecil'], 'description' => 'Urutan pagu']
                        ]
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'searchRealisasiPaket',
                    'description' => 'Mencari data realisasi paket pengadaan di database (tender, non-tender, e-purchasing, swakelola) berdasarkan kata kunci, OPD, tipe, tahun, dan mengurutkan nilai realisasi.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => ['type' => 'string', 'description' => 'Kata kunci nama paket, penyedia, atau nomor kontrak'],
                            'opd' => ['type' => 'string', 'description' => 'Filter nama OPD / Satker'],
                            'tipe' => ['type' => 'string', 'enum' => ['tender', 'nontender', 'pencatatan_nontender', 'swakelola', 'pencatatan_swakelola', 'epurchasing', 'all'], 'description' => 'Tipe realisasi, default: all'],
                            'tahun' => ['type' => 'string', 'description' => 'Tahun realisasi (contoh: 2026, 2025)'],
                            'sort_realisasi' => ['type' => 'string', 'enum' => ['terbesar', 'terkecil'], 'description' => 'Urutan realisasi']
                        ]
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'searchKnowledgeBase',
                    'description' => 'Mencari referensi aturan hukum pengadaan, SOP internal, template dokumen, dan pedoman teknis dari Library Kepintaran / Knowledge Base menggunakan pencarian semantik (RAG).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => ['type' => 'string', 'description' => 'Pertanyaan atau kata kunci regulasi/pedoman yang dicari']
                        ],
                        'required' => ['query']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'getVendorPerformance',
                    'description' => 'Mendapatkan rekam jejak, riwayat paket/kontrak, dan kinerja proyek dari penyedia/vendor tertentu di database.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'nama_penyedia' => ['type' => 'string', 'description' => 'Nama penyedia atau vendor (misal: PT Maju Jaya, CV Prima)']
                        ],
                        'required' => ['nama_penyedia']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'analyzeMonevProject',
                    'description' => 'Menganalisis status proyek berjalan, deviasi S-Curve, kendala lapangan, serta rekomendasi penanganan proyek kritis.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'nama_proyek' => ['type' => 'string', 'description' => 'Nama proyek spesifik, atau gunakan kata "kritis" untuk mencari semua proyek kritis (deviasi <= -10%)']
                        ]
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'getPaguSummary',
                    'description' => 'Mendapatkan ringkasan total pagu dan jumlah paket RUP (Rencana Umum Pengadaan) berdasarkan tipe, OPD, metode, dan jenis pengadaan.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'tipe' => ['type' => 'string', 'enum' => ['penyedia', 'swakelola'], 'description' => 'Tipe RUP, default: penyedia'],
                            'opd' => ['type' => 'string', 'description' => 'Filter nama OPD'],
                            'metode' => ['type' => 'string', 'description' => 'Filter metode pengadaan'],
                            'jenis_pengadaan' => ['type' => 'string', 'description' => 'Filter jenis pengadaan']
                        ]
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'getOpdPaguRanking',
                    'description' => 'Mendapatkan peringkat OPD dengan total pagu RUP terbesar atau terkecil.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'tipe' => ['type' => 'string', 'enum' => ['penyedia', 'swakelola'], 'description' => 'Tipe RUP, default: penyedia'],
                            'sort' => ['type' => 'string', 'enum' => ['terbesar', 'terkecil'], 'description' => 'Urutkan dari terbesar atau terkecil, default: terbesar']
                        ]
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'getEpurchasingStats',
                    'description' => 'Mendapatkan statistik realisasi transaksi E-Purchasing tahun berjalan (total realisasi, total transaksi, top satker).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => []
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'getLpseRealisasiStats',
                    'description' => 'Mendapatkan ringkasan statistik realisasi LPSE dan E-Purchasing yang RESMI TAYANG di dashboard (jumlah paket, total pagu, total realisasi, efisiensi, persentase) per tipe pengadaan, tahun, dan triwulan.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'tipe' => ['type' => 'string', 'enum' => ['all', 'tender', 'nontender', 'pencatatan_nontender', 'swakelola', 'pencatatan_swakelola', 'epurchasing'], 'description' => 'Tipe pengadaan, default: all'],
                            'tahun' => ['type' => 'string', 'description' => 'Tahun realisasi (contoh: 2026, 2025, default: tahun berjalan)'],
                            'triwulan' => ['type' => 'string', 'enum' => ['1', '2', '3', '4', 'all'], 'description' => 'Filter triwulan (1, 2, 3, 4, atau all). Default: all']
                        ]
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'getMonevSummary',
                    'description' => 'Mendapatkan ringkasan status seluruh proyek monitoring dan evaluasi (Monev) yang sedang berjalan beserta deviasi dan status kritis.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => []
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'getRealisasiPerOpd',
                    'description' => 'Mendapatkan daftar total realisasi pengadaan per OPD untuk tipe pengadaan tertentu.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'tipe' => ['type' => 'string', 'enum' => ['tender', 'nontender', 'swakelola', 'epurchasing'], 'description' => 'Tipe pengadaan'],
                            'tahun' => ['type' => 'string', 'description' => 'Tahun, default: tahun berjalan']
                        ]
                    ]
                ]
            ]
        ];
    }


    public function searchKnowledgeBase($argsStr)
    {
        $settingsModel = new \App\Models\SettingsModel();
        $apiKey = $settingsModel->getSetting('embedding_api_key', env('OPENAI_API_KEY'));
        $url = $settingsModel->getSetting('embedding_api_url', 'https://api.openai.com/v1/embeddings');
        $modelName = $settingsModel->getSetting('embedding_model', 'text-embedding-3-small');

        $args = json_decode($argsStr, true);
        $query = $args['query'] ?? '';
        if (!$query) return json_encode(['error' => 'Query kosong']);

        $queryEmbedding = null;

        // Coba peroleh embedding jika API key & URL tersedia
        if (!empty($apiKey) && !empty($url)) {
            $data = [
                'model' => $modelName,
                'input' => $query
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 6);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
                'api-key: ' . $apiKey
            ]);

            $response = curl_exec($ch);
            curl_close($ch);
            $resObj = json_decode($response, true);
            if (isset($resObj['data'][0]['embedding'])) {
                $queryEmbedding = $resObj['data'][0]['embedding'];
            }
        }

        // Fallback otomatis ke Keyword Search jika API Embedding gagal/kuota habis/tidak diset
        if (!$queryEmbedding) {
            return $this->keywordSearchKnowledgeBase($query);
        }

        // Ambil data dari DB
        $db = \Config\Database::connect();
        try {
            $chunks = $db->table('chatbot_knowledge')->get()->getResultArray();
        } catch (\Exception $e) {
            return json_encode(['error' => 'Belum ada library knowledge yang diunggah']);
        }

        if (empty($chunks)) {
            return json_encode(['error' => 'Belum ada library knowledge yang diunggah']);
        }

        $results = [];
        foreach ($chunks as $chunk) {
            $dbVec = json_decode($chunk['embedding_vector'], true);
            if (!$dbVec) continue;

            $similarity = $this->cosineSimilarity($queryEmbedding, $dbVec);
            $results[] = [
                'document' => $chunk['document_title'],
                'text' => $chunk['chunk_text'],
                'similarity' => $similarity
            ];
        }

        // Urutkan berdasarkan similarity tertinggi
        usort($results, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        // Ambil top 4
        $top = array_slice($results, 0, 4);

        // Jika hasil semantik kurang memuaskan, lengkapi dengan keyword search
        if (empty($top) || ($top[0]['similarity'] ?? 0) < 0.20) {
            $kwRes = json_decode($this->keywordSearchKnowledgeBase($query), true);
            if (!empty($kwRes['top_relevant_rules'])) {
                return json_encode($kwRes);
            }
        }

        return json_encode(['top_relevant_rules' => $top]);
    }

    /**
     * Pencarian cadangan berbasis kata kunci (Keyword Fallback Search)
     * Bekerja secara lokal tanpa ketergantungan API Embedding eksternal.
     */
    public function keywordSearchKnowledgeBase($query)
    {
        $db = \Config\Database::connect();
        try {
            $table = $db->table('chatbot_knowledge');
        } catch (\Exception $e) {
            return json_encode(['error' => 'Belum ada library knowledge yang diunggah']);
        }

        $queryClean = trim($query);
        if (empty($queryClean)) {
            return json_encode(['error' => 'Query kosong']);
        }

        // Stopwords bahasa Indonesia yang tidak signifikan untuk pencarian
        $stopwords = [
            'apa', 'apakah', 'bagaimana', 'kenapa', 'mengapa', 'kapan', 'dimana', 'siapa',
            'yang', 'dan', 'di', 'ke', 'dari', 'untuk', 'pada', 'dengan', 'adalah', 'yaitu',
            'ini', 'itu', 'atau', 'dalam', 'secara', 'tersebut', 'bisa', 'dapat', 'tolong',
            'tentang', 'mengenai', 'konteks', 'seperti', 'oleh', 'karena', 'jika', 'bila',
            'sebutkan', 'jelaskan', 'berikan', 'definisi', 'pengertian', 'maksud', 'arti',
            'aturan', 'regulasi'
        ];

        $words = preg_split('/[\s,\.\?\!\:\;\"\']+/u', mb_strtolower($queryClean));
        $keywords = [];
        foreach ($words as $w) {
            $w = trim($w);
            if (mb_strlen($w) >= 3 && !in_array($w, $stopwords)) {
                $keywords[] = $w;
            }
        }
        $keywords = array_values(array_unique($keywords));
        if (empty($keywords)) {
            foreach ($words as $w) {
                $w = trim($w);
                if (mb_strlen($w) >= 2) {
                    $keywords[] = $w;
                }
            }
            $keywords = array_values(array_unique($keywords));
        }

        try {
            $chunks = $table->select('id, document_title, chunk_text')->get()->getResultArray();
        } catch (\Exception $e) {
            return json_encode(['error' => 'Gagal membaca data library']);
        }

        if (empty($chunks)) {
            return json_encode(['error' => 'Belum ada library knowledge yang diunggah']);
        }

        $results = [];
        $totalKw = max(1, count($keywords));

        foreach ($chunks as $chunk) {
            $textLower = mb_strtolower($chunk['chunk_text']);

            $matchedKw = 0;
            $totalOccurrences = 0;
            foreach ($keywords as $kw) {
                $c = substr_count($textLower, $kw);
                if ($c > 0) {
                    $matchedKw++;
                    $totalOccurrences += min($c, 5);
                }
            }

            if ($matchedKw === 0) continue;

            // Skor dasar: proporsi kecocokan keyword (0.0 - 0.70)
            $ratio = $matchedKw / $totalKw;
            $score = $ratio * 0.70;

            // Bonus jika ada frasa berurutan yang cocok persis (misal: "mini kompetisi")
            for ($len = min(4, $totalKw); $len >= 2; $len--) {
                for ($i = 0; $i <= $totalKw - $len; $i++) {
                    $subPhrase = implode(' ', array_slice($keywords, $i, $len));
                    if (strpos($textLower, $subPhrase) !== false) {
                        $score += 0.20 * ($len / 2);
                        break 2;
                    }
                }
            }

            // Bonus frekuensi kemunculan (maksimal 0.10)
            $score += min(0.10, $totalOccurrences * 0.02);

            if ($score >= 0.25) {
                $results[] = [
                    'document' => $chunk['document_title'],
                    'text' => $chunk['chunk_text'],
                    'similarity' => round(min(0.99, $score), 4)
                ];
            }
        }

        usort($results, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        $top = array_slice($results, 0, 4);
        return json_encode([
            'top_relevant_rules' => $top,
            'source_mode' => 'keyword_fallback'
        ]);
    }

    private function cosineSimilarity($vec1, $vec2) {
        $dotProduct = 0;
        $normA = 0;
        $normB = 0;
        foreach ($vec1 as $i => $v) {
            $dotProduct += $v * $vec2[$i];
            $normA += pow($v, 2);
            $normB += pow($vec2[$i], 2);
        }
        if ($normA == 0 || $normB == 0) return 0;
        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }

    private function getVendorPerformance($argsStr)
    {
        $args = json_decode($argsStr, true);
        $vendor = $args['nama_penyedia'] ?? '';
        if (!$vendor) return json_encode(['error' => 'Nama penyedia kosong']);

        $db = \Config\Database::connect();
        $summary = [];

        // LPSE Tender
        try {
            $tender = $db->table('lpse_tender')->like('pemenang', $vendor)->selectSum('nilai_penawaran', 'total')->countAllResults(false);
            $tenderVal = $db->table('lpse_tender')->like('pemenang', $vendor)->selectSum('nilai_penawaran', 'total')->get()->getRow()->total ?? 0;
            $tenderDetail = $db->table('lpse_tender')->like('pemenang', $vendor)->select('nama_paket, nilai_penawaran, tahun')->limit(5)->get()->getResultArray();
            $summary['Tender'] = ['jumlah_paket' => $tender, 'total_nilai' => $tenderVal, 'contoh_paket' => $tenderDetail];
        } catch (\Exception $e) {}

        // LPSE Non Tender
        try {
            $nontender = $db->table('lpse_nontender')->like('pemenang', $vendor)->selectSum('nilai_penawaran', 'total')->countAllResults(false);
            $ntVal = $db->table('lpse_nontender')->like('pemenang', $vendor)->selectSum('nilai_penawaran', 'total')->get()->getRow()->total ?? 0;
            $ntDetail = $db->table('lpse_nontender')->like('pemenang', $vendor)->select('nama_paket, nilai_penawaran, tahun')->limit(5)->get()->getResultArray();
            $summary['Non_Tender'] = ['jumlah_paket' => $nontender, 'total_nilai' => $ntVal, 'contoh_paket' => $ntDetail];
        } catch (\Exception $e) {}

        // LPSE Pencatatan Non Tender
        try {
            $pencatatanNontender = $db->table('lpse_pencatatan_nontender')->like('nama_penyedia', $vendor)->selectSum('realisasi', 'total')->countAllResults(false);
            $pntVal = $db->table('lpse_pencatatan_nontender')->like('nama_penyedia', $vendor)->selectSum('realisasi', 'total')->get()->getRow()->total ?? 0;
            $pntDetail = $db->table('lpse_pencatatan_nontender')->like('nama_penyedia', $vendor)->select('nama_paket, realisasi as nilai_penawaran, tahun')->limit(5)->get()->getResultArray();
            $summary['Pencatatan_Non_Tender'] = ['jumlah_paket' => $pencatatanNontender, 'total_nilai' => $pntVal, 'contoh_paket' => $pntDetail];
        } catch (\Exception $e) {}

        // EPurchasing
        try {
            $epur = $db->table('epurchasing')->like('nama_penyedia', $vendor)->selectSum('total_harga', 'total')->countAllResults(false);
            $epurVal = $db->table('epurchasing')->like('nama_penyedia', $vendor)->selectSum('total_harga', 'total')->get()->getRow()->total ?? 0;
            $epurDetail = $db->table('epurchasing')->like('nama_penyedia', $vendor)->select('nama_paket, total_harga, tahun')->limit(5)->get()->getResultArray();
            $summary['E_Purchasing'] = ['jumlah_paket' => $epur, 'total_nilai' => $epurVal, 'contoh_paket' => $epurDetail];
        } catch (\Exception $e) {}

        // Monev
        try {
            $monevBuilder = $db->table('monev_proyek')->like('nama_penyedia', $vendor);
            $monevTotal = $monevBuilder->countAllResults(false);
            $monevTerlambat = $db->table('monev_proyek')->like('nama_penyedia', $vendor)->where('status', 'Terlambat')->countAllResults();
            $monevSelesai = $db->table('monev_proyek')->like('nama_penyedia', $vendor)->where('status', 'Selesai')->countAllResults();
            
            $monevDetail = $db->table('monev_proyek')
                              ->like('nama_penyedia', $vendor)
                              ->select('nama_proyek, nilai_kontrak, status, tgl_mulai, tgl_selesai')
                              ->orderBy('tgl_mulai', 'DESC')
                              ->limit(10)
                              ->get()
                              ->getResultArray();

            $summary['Monev_Proyek'] = [
                'total_proyek_dimonev' => $monevTotal, 
                'selesai' => $monevSelesai, 
                'terlambat' => $monevTerlambat,
                'detail_proyek' => $monevDetail
            ];
        } catch (\Exception $e) {}

        return json_encode([
            'nama_penyedia_dicari' => $vendor,
            'summary_kinerja' => $summary
        ]);
    }



    private function analyzeMonevProject($argsStr)
    {
        $args = json_decode($argsStr, true);
        $namaProyek = $args['nama_proyek'] ?? '';
        
        $proyekModel = new \App\Models\MonevProyekModel();
        $jadwalModel = new \App\Models\MonevJadwalModel();
        $harianModel = new \App\Models\MonevHarianModel();
        
        $builder = $proyekModel->builder();
        
        if (strtolower($namaProyek) === 'kritis') {
            // Find all projects, we will compute them below
            $proyekList = $proyekModel->findAll();
        } else if (!empty($namaProyek)) {
            $builder->like('nama_proyek', $namaProyek);
            $proyekList = $proyekModel->findAll();
        } else {
            $proyekList = $proyekModel->findAll();
        }

        if (empty($proyekList)) {
            return json_encode(['error' => 'Proyek tidak ditemukan.']);
        }

        $dashboard = new \App\Controllers\Dashboard();
        $dashboard->initController($this->request, $this->response, $this->logger);

        $results = [];
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
            
            // Extract latest "Kendala" and "Keterangan"
            $laporanPenting = [];
            foreach (array_reverse($harianItems) as $hari) {
                if (!empty($hari['kendala']) || !empty($hari['keterangan'])) {
                    $laporanPenting[] = [
                        'tanggal' => $hari['tanggal'],
                        'kendala' => $hari['kendala'],
                        'keterangan' => $hari['keterangan'],
                        'progres_hari_itu' => $hari['progres_persen'] . '%'
                    ];
                }
                if (count($laporanPenting) >= 5) break; // Limit top 5 recent issues
            }

            $isKritis = ($deviasiTerakhir <= -10);

            // Filter if user specifically asked for "kritis"
            if (strtolower($namaProyek) === 'kritis' && !$isKritis) {
                continue;
            }

            // KNOWLEDGE BASE RAG SEMANTIC ANALYSIS:
            // Cari referensi regulasi/SOP penanganan kendala proyek dari pustaka kecerdasan jika proyek tidak aman / kritis
            $pustakaKecerdasan = [];
            if ($deviasiTerakhir < 0 || !empty($laporanPenting)) {
                // Gunakan jenis pekerjaan dan kendala utama sebagai query RAG
                $mainKendala = !empty($laporanPenting) ? ($laporanPenting[0]['kendala'] ?? '') : '';
                $searchQuery = ($p['jenis_pekerjaan'] ?? '') . ' ' . $mainKendala . ' keterlambatan penanganan denda';
                $searchQuery = trim(preg_replace('/\s+/', ' ', $searchQuery));

                if ($searchQuery) {
                    try {
                        $kbJson = $this->searchKnowledgeBase(json_encode(['query' => $searchQuery]));
                        $kbParsed = json_decode($kbJson, true);
                        if (isset($kbParsed['top_relevant_rules']) && is_array($kbParsed['top_relevant_rules'])) {
                            foreach (array_slice($kbParsed['top_relevant_rules'], 0, 3) as $kbItem) {
                                // Hanya ambil jika kemiripan (similarity) cukup baik (misal > 0.3)
                                if (($kbItem['similarity'] ?? 0) > 0.3) {
                                    $pustakaKecerdasan[] = [
                                        'sumber_panduan' => $kbItem['document'] ?? 'Pustaka Pengadaan',
                                        'klausul_referensi' => $kbItem['text'] ?? '',
                                        'tingkat_relevansi' => round(($kbItem['similarity'] ?? 0) * 100, 1) . '%'
                                    ];
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        // Silent fail if KB search has errors, to ensure chatbot keeps running smoothly
                    }
                }
            }

            $results[] = [
                'nama_proyek' => $p['nama_proyek'],
                'penyedia' => $p['nama_penyedia'],
                'tgl_mulai' => $p['tgl_mulai'],
                'tgl_selesai' => $p['tgl_selesai'],
                'nilai_kontrak' => (float)$p['nilai_kontrak'],
                'status_proyek' => $p['status'],
                'deviasi_terakhir' => $deviasiTerakhir . '%',
                'status_indikator' => $isKritis ? 'Kritis' : ($deviasiTerakhir <= -5 ? 'Perhatian' : 'Aman'),
                'pejabat_ppk' => $p['ppk'] ?? '-',
                'lokasi_spesifik' => $p['lokasi'] ?? '-',
                'sumber_dana' => $p['sumber_dana'] ?? '-',
                'jenis_pekerjaan' => $p['jenis_pekerjaan'] ?? '-',
                'metode_kontrak' => $p['metode_kontrak'] ?? '-',
                'gps_koordinat' => $p['gps_kordinat'] ?? '-',
                'polygon_lokasi_geojson' => $p['polygon_lokasi'] ?? '-',
                '5_laporan_kendala_terakhir' => $laporanPenting,
                'rujukan_regulasi_dan_sop_penanganan_rag' => $pustakaKecerdasan // Expose RAG matches directly to AI context!
            ];
            
            if (count($results) >= 5) break; // Limit output size to prevent token limits
        }

        if (empty($results)) {
            return json_encode(['message' => 'Tidak ada proyek kritis atau data yang sesuai kriteria.']);
        }

        return json_encode([
            'catatan' => 'Data deviasi didapat dari perhitungan kumulatif aktual dikurangi rencana S-Curve. Jika deviasi minus, proyek terlambat.',
            'data' => $results
        ]);
    }
}
