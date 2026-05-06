<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class Chatbot extends ResourceController
{
    public function index()
    {
        return view('components/chatbot');
    }

    public function ask()
    {
        if (!$this->request->isAJAX()) {
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

        // --- Chatbot Memory (Session) ---
        $session = session();
        $history = $session->get('chat_history') ?? [];
        // Keep only last 10 messages to avoid token bloat
        if (count($history) > 10) $history = array_slice($history, -10);

        if (empty($apiKey)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'API Key tidak dikonfigurasi.'
            ], 500);
        }

        $response = $this->callOpenAI($message, $apiKey, $model, $apiUrl, $history);

        if (isset($response['error'])) {
            $errorMessage = is_array($response['error']) 
                ? ($response['error']['message'] ?? json_encode($response['error'])) 
                : $response['error'];
            
            return $this->respond([
                'status' => 'error',
                'message' => 'Gagal terhubung ke AI. ' . $errorMessage
            ], 500);
        }

        $reply = $response['choices'][0]['message']['content'] ?? 'Maaf, saya tidak mengerti.';

        // Save to History
        $history[] = ['role' => 'user', 'content' => $message];
        $history[] = ['role' => 'assistant', 'content' => $reply];
        $session->set('chat_history', $history);

        return $this->respond([
            'status' => 'success',
            'reply' => $reply
        ]);
    }

    private function getRupData($type)
    {
        $cacheKey = "rup_data_{$type}";
        if (!$data = cache($cacheKey)) {
            $url = ($type === 'swakelola') ? 'https://ajip-api.cloud/api/data/paket-swakelola' : 'https://ajip-api.cloud/api/data/paket-penyedia';
            $client = \Config\Services::curlrequest();
            try {
                $response = $client->get($url, ['timeout' => 5]);
                $data = json_decode($response->getBody(), true);
                if (is_array($data)) {
                    cache()->save($cacheKey, $data, 300);
                } else {
                    $data = [];
                }
            } catch (\Exception $e) {
                $data = [];
            }
        }
        
        $userSatker = session()->get('kd_satker');
        if ($userSatker && is_array($data)) {
            $data = array_filter($data, function($item) use ($userSatker) {
                return ($item['kd_satker'] ?? '') == $userSatker;
            });
        }
        
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
        // Reuse Dashboard logic but simplified for AI
        $dashboard = new \App\Controllers\Dashboard();
        $res = $dashboard->getEpurchasingDashboardData();
        $data = json_decode($res->getBody(), true);
        
        if (!$data || !isset($data['summary'])) {
            return json_encode(['error' => 'Data E-Purchasing tidak tersedia saat ini.']);
        }

        // Prepare a concise version for AI
        $summary = [
            'tahun_berjalan' => $data['summary']['year_active'] ?? date('Y'),
            'total_realisasi_rupiah' => $data['summary']['total_realisasi'],
            'total_paket' => $data['summary']['total_packages'],
            'top_satker_saat_ini' => $data['report']['top_performer'] ?? '-',
            'top_vendor_saat_ini' => $data['report']['top_vendor_name'] ?? '-',
            'analisis_pimpinan' => $data['report']['narrative'] ?? '-'
        ];

        return json_encode($summary);
    }

    private function getLpseRealisasiStats($argsStr)
    {
        $args = json_decode($argsStr, true);
        $type = $args['tipe'] ?? 'tender'; // tender, nontender, swakelola
        $year = $args['tahun'] ?? date('Y');

        $dashboard = new \App\Controllers\Dashboard();
        $res = $dashboard->getLpseDatatables($type === 'swakelola' ? 'pencatatan-swakelola' : ($type === 'nontender' ? 'nontender' : 'tender'));
        $body = json_decode($res->getBody(), true);
        $rows = $body['data'] ?? [];

        $totalPagu = 0;
        $totalReal = 0;
        $count = count($rows);

        foreach ($rows as $row) {
            $totalPagu += (float)($row['pagu'] ?? 0);
            $totalReal += (float)($row['realisasi'] ?? ($row['nilai_realisasi'] ?? 0));
        }

        return json_encode([
            'tipe_pengadaan' => $type,
            'tahun' => $year,
            'jumlah_paket' => $count,
            'total_pagu_rupiah' => $totalPagu,
            'total_realisasi_rupiah' => $totalReal,
            'persentase_realisasi' => $totalPagu > 0 ? round(($totalReal / $totalPagu) * 100, 2) . '%' : '0%'
        ]);
    }

    private function callOpenAI($message, $apiKey, $model, $url, $history = [])
    {
        $systemPrompt = "Kamu adalah asisten virtual untuk aplikasi SIDP (Sistem Informasi Data Pengadaan). 
        Kamu memiliki akses ke data REALISASI nyata (E-Purchasing dan LPSE), bukan hanya rencana (RUP).
        Tugasmu membantu user terkait statistik data pengadaan secara akurat dan profesional.
        Ingatlah untuk selalu memformat angka rupiah (contoh: Rp 10.000.000) agar mudah dibaca.
        Gunakan data terbaru dari tool yang tersedia jika ditanya tentang realisasi atau tren.";

        $messages = [['role' => 'system', 'content' => $systemPrompt]];
        
        foreach ($history as $h) {
            $messages[] = $h;
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        // Definisi alat (Tools/Functions) untuk AI
        $tools = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_pagu_summary',
                    'description' => 'Mendapatkan ringkasan statistik (total pagu dan jumlah paket) dengan filter tipe, metode, jenis pengadaan, atau OPD tertentu.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'tipe' => [
                                'type' => 'string',
                                'enum' => ['penyedia', 'swakelola'],
                                'description' => 'Wajib diisi. Tipe pengadaan.'
                            ],
                            'metode' => [
                                'type' => 'string',
                                'description' => 'Filter metode pengadaan (misal: "e-purchasing", "tender", "seleksi", "pengadaan langsung").'
                            ],
                            'opd' => [
                                'type' => 'string',
                                'description' => 'Filter nama OPD/Dinas tertentu.'
                            ],
                            'jenis_pengadaan' => [
                                'type' => 'string',
                                'description' => 'Filter jenis pengadaan (Barang, Jasa Lainnya, dll). Hanya untuk tipe penyedia.'
                            ]
                        ],
                        'required' => ['tipe']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_opd_pagu_ranking',
                    'description' => 'Jawaban terbaik untuk: "OPD mana dengan total pagu terbesar/terkecil" atau "Bagaimana ranking OPD". Ini menjumlahkan pagu per OPD.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'tipe' => [
                                'type' => 'string',
                                'enum' => ['penyedia', 'swakelola'],
                                'description' => 'Tipe pengadaan yang ingin dirangking pagunya per OPD.'
                            ],
                            'sort' => [
                                'type' => 'string',
                                'enum' => ['terbesar', 'terkecil'],
                                'description' => 'Urutkan dari yang terbesar (tertinggi) atau terkecil (terendah).'
                            ]
                        ],
                        'required' => ['tipe']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_paket_detail',
                    'description' => 'Mencari dan membaca detail daftar paket pengadaan individual (seperti rincian nilai pagu per paket, jenis pengadaan, metode, mengandung kata spesifik di nama paket atau spesifikasi).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => [
                                'type' => 'string',
                                'description' => 'Kata kunci untuk mencari nama paket atau spesifikasi, misal "sekolah", "laptop", "internet", atau "pekerjaan Alun", kosongkan jika tidak butuh.'
                            ],
                            'opd' => [
                                'type' => 'string',
                                'description' => 'Nama OPD atau Dinas atau Satker spesifik.'
                            ],
                            'metode' => [
                                'type' => 'string',
                                'description' => 'Metode pengadaan spesifik (misal: "e-purchasing", "tender", "pengadaan langsung", "dikecualikan", dsb).'
                            ],
                            'jenis_pengadaan' => [
                                'type' => 'string',
                                'description' => 'Filter jenis pengadaan (Hanya untuk tipe Penyedia). Gunakan salah satu: "Barang", "Pekerjaan Konstruksi", "Jasa Konsultansi", "Jasa Lainnya". JANGAN masukkan keyword pencarian di sini.'
                            ],
                            'tipe' => [
                                'type' => 'string',
                                'enum' => ['penyedia', 'swakelola'],
                                'description' => 'Tipe pengadaan yang ingin dicari rinciannya.'
                            ],
                            'sort_pagu' => [
                                'type' => 'string',
                                'enum' => ['terbesar', 'terkecil'],
                                'description' => 'Urutkan nilai paket terbesar/termahal atau terkecil/termurah.'
                            ]
                        ],
                        'required' => ['tipe']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_epurchasing_stats',
                    'description' => 'Mendapatkan statistik REALISASI E-Purchasing terbaru tahun berjalan (Total nilai, jumlah paket, top vendor, dan analisis tren). Gunakan ini jika user bertanya tentang E-Katalog atau E-Purchasing.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => []
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_lpse_realisasi_summary',
                    'description' => 'Mendapatkan ringkasan realisasi nyata dari LPSE (Tender, Non-Tender, atau Swakelola).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'tipe' => [
                                'type' => 'string',
                                'enum' => ['tender', 'nontender', 'swakelola'],
                                'description' => 'Tipe pengadaan yang ingin dilihat realisasinya.'
                            ],
                            'tahun' => [
                                'type' => 'string',
                                'description' => 'Tahun anggaran, default tahun berjalan.'
                            ]
                        ],
                        'required' => ['tipe']
                    ]
                ]
            ]
        ];

        // Loop untuk handle Tool Calling secara rekursif (maksimal 3 pemanggilan)
        for ($i = 0; $i < 4; $i++) {
            $data = [
                'model' => $model,
                'messages' => $messages,
                'tools' => $tools,
                'max_tokens' => 1000,
                'temperature' => 0.7
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ]);

            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return ['error' => $error];
            }

            $responseArray = json_decode($response, true);
            if (isset($responseArray['error'])) {
                return $responseArray;
            }

            $responseMessage = $responseArray['choices'][0]['message'] ?? null;
            if (!$responseMessage) {
                 return ['error' => 'Invalid response from AI'];
            }

            // Tambahkan balasan AI (Termasuk jika dia merespons dengan panggilan fungsi)
            $messages[] = $responseMessage;

            // Cek apakah AI ingin kita menjalankan sebuah fungsi lokal (Tool Call)
            if (isset($responseMessage['tool_calls']) && !empty($responseMessage['tool_calls'])) {
                foreach ($responseMessage['tool_calls'] as $toolCall) {
                    $functionName = $toolCall['function']['name'];
                    $functionArgs = $toolCall['function']['arguments'];
                    $functionResponse = '';

                    if ($functionName === 'get_pagu_summary') {
                        $functionResponse = $this->getPaguSummary($functionArgs);
                    } else if ($functionName === 'get_opd_pagu_ranking') {
                        $functionResponse = $this->getOpdPaguRanking($functionArgs);
                    } else if ($functionName === 'search_paket_detail') {
                        $functionResponse = $this->searchPaket($functionArgs);
                    } else if ($functionName === 'get_epurchasing_stats') {
                        $functionResponse = $this->getEpurchasingStats();
                    } else if ($functionName === 'get_lpse_realisasi_summary') {
                        $functionResponse = $this->getLpseRealisasiStats($functionArgs);
                    } else {
                        $functionResponse = json_encode(['error' => 'Fungsi tidak terdaftar']);
                    }

                    // Masukkan hasil fungsi ke dalam antrean chat
                    $messages[] = [
                        'tool_call_id' => $toolCall['id'],
                        'role' => 'tool',
                        'name' => $functionName,
                        'content' => $functionResponse,
                    ];
                }
                
                // Teruskan loop untuk memanggil AI lagi dengan menyertakan hasil tool
                continue;
            } else {
                // Jika AI merespons teks biasa, return hasil akhir
                return $responseArray;
            }
        }
        
        return ['error' => 'Terlalu banyak permintaan berantai (infinite loop tool calls).'];
    }
}
