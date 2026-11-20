<?php

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\MonevAssessmentTemplateModel;
use App\Models\MonevAssessmentAspectModel;
use App\Models\MonevAssessmentIndicatorModel;
use App\Services\MonevAssessmentService;
use App\Database\Seeds\MonevAssessmentTemplateSeeder;
use InvalidArgumentException;

class MonevAssessmentTemplateTest extends CIUnitTestCase
{
    protected MonevAssessmentTemplateModel $templateModel;
    protected MonevAssessmentAspectModel $aspectModel;
    protected MonevAssessmentIndicatorModel $indicatorModel;
    protected MonevAssessmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templateModel = new MonevAssessmentTemplateModel();
        $this->aspectModel = new MonevAssessmentAspectModel();
        $this->indicatorModel = new MonevAssessmentIndicatorModel();
        $this->service = new MonevAssessmentService();

        // Pastikan seed dijalankan sebelum pengujian
        $seeder = new MonevAssessmentTemplateSeeder(config('Database'));
        $seeder->run();
    }

    /**
     * 1. Memastikan Template Utama Kinerja Penyedia v1.0 tersedia dan aktif.
     */
    public function testTemplateHeaderAndActiveStatus(): void
    {
        $template = $this->templateModel->getActiveTemplateWithStructure();
        
        $this->assertNotNull($template, "Template aktif tidak ditemukan.");
        $this->assertSame('Kinerja Penyedia Jasa Konstruksi', $template['nama_template']);
        $this->assertSame('TMPL-KINERJA-PENYEDIA-V1', $template['kode_template']);
        $this->assertSame('1.0', (string) $template['versi']);
        $this->assertSame('1', (string) $template['is_active']);
    }

    /**
     * 2. Memastikan SEMUA 6 ASPEK tersedia dengan benar.
     */
    public function testAllSixAspectsAvailable(): void
    {
        $template = $this->templateModel->getActiveTemplateWithStructure();
        $this->assertNotNull($template);

        $aspects = $template['aspects'];
        $this->assertCount(6, $aspects, "Jumlah aspek harus tepat 6.");

        $expectedAspects = [
            1 => ['kode' => 'ASP-01', 'nama' => 'ADMINISTRASI'],
            2 => ['kode' => 'ASP-02', 'nama' => 'JADWAL DAN WAKTU'],
            3 => ['kode' => 'ASP-03', 'nama' => 'MATERIAL'],
            4 => ['kode' => 'ASP-04', 'nama' => 'TENAGA KERJA DAN PERALATAN'],
            5 => ['kode' => 'ASP-05', 'nama' => 'KESELAMATAN DAN KESEHATAN KERJA'],
            6 => ['kode' => 'ASP-06', 'nama' => 'LINGKUNGAN'],
        ];

        foreach ($aspects as $asp) {
            $urutan = (int) $asp['urutan'];
            $this->assertArrayHasKey($urutan, $expectedAspects, "Urutan aspek tidak valid: {$urutan}");
            $this->assertSame($expectedAspects[$urutan]['kode'], $asp['kode_aspek']);
            $this->assertSame($expectedAspects[$urutan]['nama'], $asp['nama_aspek']);
        }
    }

    /**
     * 3. Memastikan SEMUA 12 INDIKATOR tersedia dengan pembagian per aspek yang tepat.
     */
    public function testAllTwelveIndicatorsAvailable(): void
    {
        $template = $this->templateModel->getActiveTemplateWithStructure();
        $this->assertNotNull($template);

        $totalIndicators = 0;
        $allCodes = [];

        // Ekspektasi jumlah indikator per aspek: 2, 2, 2, 3, 1, 2 = 12
        $expectedCountPerAspect = [
            'ASP-01' => 2,
            'ASP-02' => 2,
            'ASP-03' => 2,
            'ASP-04' => 3,
            'ASP-05' => 1,
            'ASP-06' => 2,
        ];

        foreach ($template['aspects'] as $asp) {
            $aspectCode = $asp['kode_aspek'];
            $indCount = count($asp['indicators']);
            $this->assertSame(
                $expectedCountPerAspect[$aspectCode],
                $indCount,
                "Jumlah indikator untuk {$aspectCode} ({$asp['nama_aspek']}) harus {$expectedCountPerAspect[$aspectCode]}, ditemukan {$indCount}."
            );

            foreach ($asp['indicators'] as $ind) {
                $totalIndicators++;
                $allCodes[] = $ind['kode_indikator'];

                $this->assertNotEmpty($ind['nama_indikator'], "Nama indikator tidak boleh kosong.");
                $this->assertNotEmpty($ind['panduan_nilai_1'], "Panduan nilai 1 harus ada.");
                $this->assertNotEmpty($ind['panduan_nilai_2'], "Panduan nilai 2 harus ada.");
                $this->assertNotEmpty($ind['panduan_nilai_3'], "Panduan nilai 3 harus ada.");
                $this->assertNotEmpty($ind['evidence_requirement'], "Evidence requirement harus ada.");
            }
        }

        $this->assertSame(12, $totalIndicators, "Total indikator harus tepat 12.");

        // Pastikan kode IND-01 sampai IND-12 lengkap
        for ($i = 1; $i <= 12; $i++) {
            $code = sprintf('IND-%02d', $i);
            $this->assertContains($code, $allCodes, "Indikator {$code} tidak ditemukan.");
        }
    }

    /**
     * 4. Memastikan score mapping benar: TIDAK_BAIK=1, BAIK=2, SANGAT_BAIK=3.
     */
    public function testScoreMappingCorrectness(): void
    {
        $this->assertSame(1, $this->service->mapRatingToScore('TIDAK_BAIK'));
        $this->assertSame(2, $this->service->mapRatingToScore('BAIK'));
        $this->assertSame(3, $this->service->mapRatingToScore('SANGAT_BAIK'));

        // Case-insensitive check
        $this->assertSame(1, $this->service->mapRatingToScore('tidak_baik'));
        $this->assertSame(2, $this->service->mapRatingToScore('Baik'));
        $this->assertSame(3, $this->service->mapRatingToScore('Sangat_Baik'));

        // Invalid rating throws exception
        $this->expectException(InvalidArgumentException::class);
        $this->service->mapRatingToScore('LUAR_BIASA');
    }

    /**
     * 5. Memastikan total minimum = 12, total maximum = 36, dan kalkulasi persentase benar.
     */
    public function testTotalScoreRangeMinTwelveMaxThirtySix(): void
    {
        // Kondisi 1: Semua Tidak Baik (12 item x 1 = 12)
        $minItems = array_fill(0, 12, ['rating' => 'TIDAK_BAIK', 'score' => 1]);
        $resMin = $this->service->calculateScore($minItems);
        $this->assertSame(12, $resMin['total_score']);
        $this->assertSame(12, $resMin['minimum_score']);
        $this->assertSame(36, $resMin['maximum_score']);
        $this->assertSame(33.33, $resMin['persentase']);

        // Kondisi 2: Semua Baik (12 item x 2 = 24)
        $midItems = array_fill(0, 12, ['rating' => 'BAIK', 'score' => 2]);
        $resMid = $this->service->calculateScore($midItems);
        $this->assertSame(24, $resMid['total_score']);
        $this->assertSame(66.67, $resMid['persentase']);

        // Kondisi 3: Semua Sangat Baik (12 item x 3 = 36)
        $maxItems = array_fill(0, 12, ['rating' => 'SANGAT_BAIK', 'score' => 3]);
        $resMax = $this->service->calculateScore($maxItems);
        $this->assertSame(36, $resMax['total_score']);
        $this->assertSame(100.0, $resMax['persentase']);
    }

    /**
     * 6. Memastikan kategori akhir tetap NULL jika threshold belum ditentukan / tidak aktif.
     */
    public function testCategoryRemainsNullWhenThresholdNotConfigured(): void
    {
        $maxItems = array_fill(0, 12, ['rating' => 'SANGAT_BAIK', 'score' => 3]);
        $res = $this->service->calculateScore($maxItems, null);

        $this->assertSame(36, $res['total_score']);
        $this->assertSame(100.0, $res['persentase']);
        // Wajib NULL, tidak boleh ada hardcoded 'Sangat Baik'
        $this->assertNull($res['kategori'], "Kategori akhir harus null sampai threshold resmi ditentukan.");
    }

    /**
     * 7. Memastikan threshold bersifat configurable saat diaktifkan.
     */
    public function testConfigurableThresholdEvaluation(): void
    {
        $customConfig = [
            'is_active' => true,
            'tiers' => [
                ['label' => 'SANGAT_MEMUASKAN', 'min_pct' => 85.0],
                ['label' => 'MEMUASKAN',        'min_pct' => 70.0],
                ['label' => 'CUKUP_BAIK',       'min_pct' => 50.0],
                ['label' => 'PERLU_PERBAIKAN',  'min_pct' => 0.0],
            ]
        ];

        // 100% -> SANGAT_MEMUASKAN
        $this->assertSame('SANGAT_MEMUASKAN', $this->service->evaluateCategory(100.0, $customConfig));
        // 75% -> MEMUASKAN
        $this->assertSame('MEMUASKAN', $this->service->evaluateCategory(75.0, $customConfig));
        // 60% -> CUKUP_BAIK
        $this->assertSame('CUKUP_BAIK', $this->service->evaluateCategory(60.0, $customConfig));
        // 40% -> PERLU_PERBAIKAN
        $this->assertSame('PERLU_PERBAIKAN', $this->service->evaluateCategory(40.0, $customConfig));

        // Jika is_active = false, kategori kembali null
        $inactiveConfig = $customConfig;
        $inactiveConfig['is_active'] = false;
        $this->assertNull($this->service->evaluateCategory(100.0, $inactiveConfig));
    }

    /**
     * 8. Memastikan template dapat di-versioning dengan lengkap.
     */
    public function testTemplateCanBeVersioned(): void
    {
        $template = $this->templateModel->where('kode_template', 'TMPL-KINERJA-PENYEDIA-V1')->first();
        $this->assertNotNull($template);

        // Buat versi baru 2.0
        $newTemplateId = $this->service->createTemplateVersion((int) $template['id'], '2.0', 'TMPL-KINERJA-PENYEDIA-V2', true);
        $this->assertGreaterThan(0, $newTemplateId);

        // Verifikasi template baru
        $newTemplate = $this->templateModel->getActiveTemplateWithStructure($newTemplateId);
        $this->assertNotNull($newTemplate);
        $this->assertSame('2.0', (string) $newTemplate['versi']);
        $this->assertSame('TMPL-KINERJA-PENYEDIA-V2', $newTemplate['kode_template']);
        $this->assertSame('1', (string) $newTemplate['is_active']);

        // Verifikasi semua 6 aspek dan 12 indikator terkloning sempurna pada versi baru
        $this->assertCount(6, $newTemplate['aspects']);
        $clonedIndicatorCount = 0;
        foreach ($newTemplate['aspects'] as $asp) {
            $clonedIndicatorCount += count($asp['indicators']);
        }
        $this->assertSame(12, $clonedIndicatorCount);

        // Verifikasi versi 1.0 lama dinonaktifkan
        $oldTemplate = $this->templateModel->find($template['id']);
        $this->assertSame('0', (string) $oldTemplate['is_active']);

        // Kembalikan ke versi 1.0 aktif agar test suite tetap konsisten
        $this->templateModel->update($template['id'], ['is_active' => 1]);
        $this->templateModel->delete($newTemplateId);
    }

    /**
     * 9. Memastikan Seeder Idempotent (dijalankan berulang kali tidak menduplikasi data).
     */
    public function testSeederIsIdempotent(): void
    {
        $seeder = new MonevAssessmentTemplateSeeder(config('Database'));

        // Jalankan 2 kali lagi
        $seeder->run();
        $seeder->run();

        $activeTemplate = $this->templateModel->getActiveTemplateWithStructure();
        $this->assertNotNull($activeTemplate);

        $this->assertCount(6, $activeTemplate['aspects'], "Jumlah aspek tidak boleh bertambah (harus tetap 6).");

        $totalInd = 0;
        foreach ($activeTemplate['aspects'] as $asp) {
            $totalInd += count($asp['indicators']);
        }
        $this->assertSame(12, $totalInd, "Jumlah indikator tidak boleh bertambah (harus tetap 12).");
    }
}
