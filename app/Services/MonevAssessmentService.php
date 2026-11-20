<?php

namespace App\Services;

use App\Models\MonevAssessmentTemplateModel;
use App\Models\MonevAssessmentAspectModel;
use App\Models\MonevAssessmentIndicatorModel;
use InvalidArgumentException;

class MonevAssessmentService
{
    public const RATING_TIDAK_BAIK = 'TIDAK_BAIK';
    public const RATING_BAIK = 'BAIK';
    public const RATING_SANGAT_BAIK = 'SANGAT_BAIK';

    public const RATING_SCORE_MAP = [
        self::RATING_TIDAK_BAIK   => 1,
        self::RATING_BAIK         => 2,
        self::RATING_SANGAT_BAIK  => 3,
    ];

    public const TOTAL_INDICATORS = 12;
    public const MIN_TOTAL_SCORE = 12; // 12 * 1
    public const MAX_TOTAL_SCORE = 36; // 12 * 3

    protected MonevAssessmentTemplateModel $templateModel;
    protected MonevAssessmentAspectModel $aspectModel;
    protected MonevAssessmentIndicatorModel $indicatorModel;

    public function __construct()
    {
        $this->templateModel = new MonevAssessmentTemplateModel();
        $this->aspectModel = new MonevAssessmentAspectModel();
        $this->indicatorModel = new MonevAssessmentIndicatorModel();
    }

    /**
     * Konversi rating ke skor integer (TIDAK_BAIK=1, BAIK=2, SANGAT_BAIK=3).
     */
    public function mapRatingToScore(string $rating): int
    {
        $normalized = strtoupper(trim($rating));
        if (!isset(self::RATING_SCORE_MAP[$normalized])) {
            throw new InvalidArgumentException("Rating tidak valid: '{$rating}'. Rating yang diperbolehkan: TIDAK_BAIK, BAIK, SANGAT_BAIK.");
        }
        return self::RATING_SCORE_MAP[$normalized];
    }

    /**
     * Menghitung total skor dan persentase capaian dari daftar item penilaian.
     * 
     * Formula:
     * - total_score = sum(score) [min: 12, max: 36]
     * - persentase = (total_score / maximum_score) * 100
     * 
     * CATATAN:
     * Kategori akhir (e.g. Baik/Sangat Baik) TIDAK dibuat secara otomatis
     * kecuali threshold resmi telah ditentukan dan aktif secara configurable.
     *
     * @param array $items Array penilaian per indikator [ ['rating' => 'BAIK', 'score' => 2], ... ]
     * @param array|null $thresholdConfig Konfigurasi threshold opsional
     * @return array [ 'total_score', 'maximum_score', 'minimum_score', 'persentase', 'kategori' ]
     */
    public function calculateScore(array $items, ?array $thresholdConfig = null): array
    {
        $totalScore = 0;
        $maxPossible = count($items) > 0 ? count($items) * 3 : self::MAX_TOTAL_SCORE;
        $minPossible = count($items) > 0 ? count($items) * 1 : self::MIN_TOTAL_SCORE;

        foreach ($items as $item) {
            $score = 0;
            if (isset($item['score']) && is_numeric($item['score'])) {
                $score = (int) $item['score'];
            } elseif (isset($item['rating'])) {
                $score = $this->mapRatingToScore($item['rating']);
            }

            if ($score < 1 || $score > 3) {
                throw new InvalidArgumentException("Skor indikator harus berada pada rentang 1 s/d 3. Diberikan: {$score}");
            }

            $totalScore += $score;
        }

        $percentage = $maxPossible > 0 ? round(($totalScore / $maxPossible) * 100, 2) : 0.00;

        // Evaluasi kategori HANYA jika threshold dikonfigurasi & aktif
        $kategori = $this->evaluateCategory($percentage, $thresholdConfig);

        return [
            'total_score'   => $totalScore,
            'minimum_score' => $minPossible,
            'maximum_score' => $maxPossible,
            'persentase'    => $percentage,
            'kategori'      => $kategori, // null jika threshold belum dikonfigurasi
        ];
    }

    /**
     * Evaluasi kategori berdasarkan configurable threshold.
     * Jika threshold belum ditentukan / tidak aktif, return null.
     */
    public function evaluateCategory(float $percentage, ?array $thresholdConfig): ?string
    {
        if (empty($thresholdConfig) || empty($thresholdConfig['is_active']) || empty($thresholdConfig['tiers'])) {
            return null;
        }

        // Urutkan tiers berdasarkan min_pct menurun
        $tiers = $thresholdConfig['tiers'];
        usort($tiers, fn($a, $b) => ($b['min_pct'] ?? 0) <=> ($a['min_pct'] ?? 0));

        foreach ($tiers as $tier) {
            if ($percentage >= ($tier['min_pct'] ?? 0)) {
                return $tier['label'] ?? null;
            }
        }

        return null;
    }

    /**
     * Menyimpan atau memperbarui konfigurasi threshold pada template.
     */
    public function setThresholdConfig(int $templateId, array $config): bool
    {
        return $this->templateModel->update($templateId, [
            'threshold_config' => json_encode($config)
        ]);
    }

    /**
     * Membuat versi baru dari sebuah template (Versioning).
     *
     * @param int $sourceTemplateId ID template sumber
     * @param string $newVersionString Versi baru, e.g. "2.0"
     * @param string|null $newKodeTemplate Kode template baru opsional
     * @param bool $setActive Jadikan versi baru sebagai template aktif
     * @return int ID template baru yang dibuat
     */
    public function createTemplateVersion(int $sourceTemplateId, string $newVersionString, ?string $newKodeTemplate = null, bool $setActive = true): int
    {
        $source = $this->templateModel->find($sourceTemplateId);
        if (!$source) {
            throw new InvalidArgumentException("Template sumber dengan ID {$sourceTemplateId} tidak ditemukan.");
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // Siapkan kode unik untuk versi baru
        if (!$newKodeTemplate) {
            $baseCode = preg_replace('/-V[\d\.]+$/i', '', $source['kode_template']);
            $verSlug = str_replace('.', '_', $newVersionString);
            $newKodeTemplate = "{$baseCode}-V{$verSlug}";
        }

        // Nonaktifkan versi lama jika versi baru diaktifkan
        if ($setActive) {
            $this->templateModel->where('is_active', 1)->set(['is_active' => 0])->update();
        }

        // Insert Template Baru
        $newTemplateData = [
            'kode_template'    => $newKodeTemplate,
            'nama_template'    => $source['nama_template'],
            'versi'            => $newVersionString,
            'is_active'        => $setActive ? 1 : 0,
            'deskripsi'        => $source['deskripsi'],
            'threshold_config' => $source['threshold_config'] ?? null,
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ];
        $this->templateModel->insert($newTemplateData);
        $newTemplateId = (int) $this->templateModel->getInsertID();

        // Clone Aspects & Indicators
        $aspects = $this->aspectModel->where('template_id', $sourceTemplateId)->orderBy('urutan', 'ASC')->findAll();
        foreach ($aspects as $asp) {
            $indicators = $this->indicatorModel->where('aspect_id', $asp['id'])->orderBy('urutan', 'ASC')->findAll();

            $newAspectData = [
                'template_id'  => $newTemplateId,
                'urutan'       => $asp['urutan'],
                'kode_aspek'   => $asp['kode_aspek'],
                'nama_aspek'   => $asp['nama_aspek'],
                'bobot_persen' => $asp['bobot_persen'],
                'deskripsi'    => $asp['deskripsi'],
                'created_at'   => date('Y-m-d H:i:s'),
            ];
            $this->aspectModel->insert($newAspectData);
            $newAspectId = (int) $this->aspectModel->getInsertID();

            foreach ($indicators as $ind) {
                $newIndicatorData = [
                    'aspect_id'            => $newAspectId,
                    'urutan'               => $ind['urutan'],
                    'kode_indikator'       => $ind['kode_indikator'],
                    'nama_indikator'       => $ind['nama_indikator'],
                    'deskripsi_kriteria'   => $ind['deskripsi_kriteria'],
                    'panduan_nilai_1'      => $ind['panduan_nilai_1'],
                    'panduan_nilai_2'      => $ind['panduan_nilai_2'],
                    'panduan_nilai_3'      => $ind['panduan_nilai_3'],
                    'evidence_requirement' => $ind['evidence_requirement'] ?? null,
                    'created_at'           => date('Y-m-d H:i:s'),
                ];
                $this->indicatorModel->insert($newIndicatorData);
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new \RuntimeException("Gagal membuat versi template baru.");
        }

        return $newTemplateId;
    }
}
