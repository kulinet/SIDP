<?php

namespace App\Services;

/**
 * Verifikasi klasifikasi Pengadaan / Non-Pengadaan paket RUP menggunakan AI (LLM OpenAI-compatible).
 *
 * Hasil verifikasi disimpan di tabel `rup_klasifikasi_ai` sehingga AI hanya dipanggil sekali per paket
 * (selama nama paket & MAK tidak berubah). Dashboard cukup membaca tabel ini (cepat, tanpa memanggil AI).
 */
class NonPengadaanAiService
{
    public const TABLE = 'rup_klasifikasi_ai';

    protected $db;
    protected string $apiKey;
    protected string $apiUrl;
    protected string $model;

    public function __construct()
    {
        $this->db = \Config\Database::connect();

        $settings = new \App\Models\SettingsModel();
        $this->apiKey = (string) $settings->getSetting('chatbot_api_key', env('OPENAI_API_KEY'));
        $this->model = (string) $settings->getSetting('chatbot_model', 'gpt-4o-mini');
        $apiUrl = rtrim((string) $settings->getSetting('chatbot_api_url', 'https://api.openai.com/v1/chat/completions'), '/');
        if (!preg_match('/\/chat\/completions$/i', $apiUrl)) {
            $apiUrl .= '/chat/completions';
        }
        $this->apiUrl = $apiUrl;
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function ensureTable(): void
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . self::TABLE . "` (
                `kd_rup` VARCHAR(50) NOT NULL,
                `fingerprint` CHAR(32) NOT NULL,
                `is_non_pengadaan` TINYINT(1) NOT NULL DEFAULT 0,
                `kategori` VARCHAR(150) NULL,
                `alasan` TEXT NULL,
                `model` VARCHAR(100) NULL,
                `created_at` DATETIME NULL,
                PRIMARY KEY (`kd_rup`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    /** Fingerprint berubah jika nama paket / MAK diubah OPD, sehingga paket diverifikasi ulang. */
    public static function fingerprint(string $namaPaket, string $mak): string
    {
        return md5(mb_strtolower(trim($namaPaket)) . '|' . trim($mak));
    }

    /** @return array<string, array> kd_rup => row */
    public function loadVerdicts(): array
    {
        $this->ensureTable();
        $map = [];
        foreach ($this->db->table(self::TABLE)->get()->getResultArray() as $row) {
            $map[(string) $row['kd_rup']] = $row;
        }
        return $map;
    }

    /**
     * Klasifikasikan satu batch paket via AI lalu simpan hasilnya.
     *
     * @param array $items list of ['kd_rup','nama_paket','mak','akun','tipe']
     * @return int jumlah paket yang berhasil disimpan
     */
    public function classifyBatch(array $items): int
    {
        if (empty($items)) {
            return 0;
        }
        if (!$this->isConfigured()) {
            throw new \RuntimeException('API Key AI belum dikonfigurasi (menu Pengaturan Chatbot).');
        }

        $lines = [];
        foreach (array_values($items) as $i => $it) {
            $lines[] = ($i + 1) . '. ' . json_encode([
                'id' => (string) $it['kd_rup'],
                'nama' => (string) $it['nama_paket'],
                'akun_belanja' => $it['akun'] ?: '-',
                'tipe' => $it['tipe'],
            ], JSON_UNESCAPED_UNICODE);
        }

        $system = <<<TXT
Anda adalah auditor Pengadaan Barang/Jasa Pemerintah (PBJP) Indonesia. Tentukan apakah setiap paket RUP merupakan
objek PENGADAAN atau NON-PENGADAAN, mengacu pada Perpres 16/2018 jo. Perpres 12/2021 jo. Perpres 46/2025 dan Perlem LKPP 11/2021.

PENGADAAN = ada perolehan barang/jasa (dari penyedia maupun swakelola), misal: ATK, bahan, cetak, makan-minum rapat,
sewa, komputer, pemeliharaan, konstruksi, jasa pihak ketiga (internet, listrik, kebersihan, satpam, tenaga ahli/konsultan),
termasuk HIBAH/BANSOS DALAM BENTUK BARANG (mis. kursi roda, sembako, alat) karena barangnya harus diadakan.

NON-PENGADAAN (tidak boleh diumumkan di SiRUP):
- Gaji/jasa/penghasilan PPPK (Penuh Waktu maupun Paruh Waktu) dan belanja pegawai ASN (PPPK adalah ASN, UU 20/2023).
- Honorarium, tunjangan, insentif, uang lembur aparatur/tim internal.
- Perjalanan dinas (uang harian, transport, lumpsum) aparatur.
- Hibah/Bansos/bantuan keuangan dalam bentuk UANG (transfer kas).
- Pembayaran pajak, iuran BPJS/JKN, dan pembayaran wajib lainnya.

PENTING: kata "PPPK", "PNS", "honor", dsb. yang hanya menerangkan NAMA KEGIATAN (contoh: "Belanja Alat Tulis Kantor
(Pengadaan PNS dan PPPK)" atau "Makan Minum Rapat Seleksi PPPK") TIDAK membuat paket menjadi non-pengadaan; yang
dinilai adalah OBJEK BELANJA-nya. Gunakan akun_belanja sebagai petunjuk: 5.1.01=Belanja Pegawai, 5.1.02.01=Barang
pakai habis, 5.1.02.02=Jasa, 5.1.02.03=Pemeliharaan, 5.1.02.04=Perjalanan Dinas, 5.1.05=Hibah, 5.1.06=Bansos, 5.2=Modal.
Jika ragu, pilih PENGADAAN.

Jawab HANYA dengan JSON array tanpa teks lain, format:
[{"id":"<id>","non_pengadaan":true|false,"kategori":"<kategori singkat>","alasan":"<alasan maks 25 kata>"}]
Kategori non-pengadaan gunakan salah satu: "Gaji / Jasa PPPK (Non-PBJP)", "Honorarium & Belanja Pegawai",
"Perjalanan Dinas Rutin", "Hibah / Bansos Uang", "Iuran / Pajak / Non-PBJP". Untuk pengadaan gunakan "Pengadaan Normal".
TXT;

        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => "Klasifikasikan paket berikut:\n" . implode("\n", $lines)],
            ],
            'temperature' => 0,
            'max_tokens' => 4000,
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
                'api-key: ' . $this->apiKey,
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('Koneksi ke AI gagal: ' . $error);
        }
        $decoded = json_decode((string) $response, true);
        $content = $decoded['choices'][0]['message']['content'] ?? '';
        if ($httpCode !== 200 || $content === '') {
            $msg = $decoded['error']['message'] ?? "HTTP {$httpCode}";
            throw new \RuntimeException('AI error: ' . $msg);
        }

        $results = $this->parseJsonArray($content);
        if ($results === null) {
            throw new \RuntimeException('Format jawaban AI tidak valid: ' . mb_substr($content, 0, 200));
        }

        $byId = [];
        foreach ($items as $it) {
            $byId[(string) $it['kd_rup']] = $it;
        }

        $saved = 0;
        $now = date('Y-m-d H:i:s');
        foreach ($results as $res) {
            $id = (string) ($res['id'] ?? '');
            if (!isset($byId[$id])) {
                continue;
            }
            $it = $byId[$id];
            $isNon = filter_var($res['non_pengadaan'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $this->db->query(
                "REPLACE INTO `" . self::TABLE . "` (kd_rup, fingerprint, is_non_pengadaan, kategori, alasan, model, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $id,
                    self::fingerprint($it['nama_paket'], $it['mak']),
                    $isNon ? 1 : 0,
                    mb_substr((string) ($res['kategori'] ?? ($isNon ? 'Non-Pengadaan' : 'Pengadaan Normal')), 0, 150),
                    (string) ($res['alasan'] ?? ''),
                    $this->model,
                    $now,
                ]
            );
            $saved++;
        }

        return $saved;
    }

    protected function parseJsonArray(string $content): ?array
    {
        $content = trim($content);
        // Hapus blok <think>...</think> (model reasoning) dan code fence ```json
        $content = preg_replace('/<think>.*?<\/think>/is', '', $content);
        $content = preg_replace('/^```(?:json)?|```$/m', '', $content);
        $start = strpos($content, '[');
        $end = strrpos($content, ']');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }
        $arr = json_decode(substr($content, $start, $end - $start + 1), true);
        return is_array($arr) ? $arr : null;
    }
}
