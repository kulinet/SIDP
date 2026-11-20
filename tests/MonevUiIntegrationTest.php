<?php

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use App\Controllers\MonevV2Controller;
use App\Models\MonevProyekModel;
use App\Models\MonevPeriodModel;
use App\Models\MonevAssessmentTemplateModel;
use App\Models\MonevFindingModel;
use App\Database\Seeds\MonevAssessmentTemplateSeeder;
use App\Services\MonevWorkflowService;
use App\Services\MonevIntegrationService;

class MonevUiIntegrationTest extends CIUnitTestCase
{
    protected MonevProyekModel $proyekModel;
    protected MonevPeriodModel $periodModel;
    protected MonevAssessmentTemplateModel $templateModel;
    protected MonevWorkflowService $workflowService;
    protected MonevIntegrationService $integrationService;
    protected MonevV2Controller $controller;

    protected int $testProyekId;
    protected int $testPeriodId;
    protected int $adminUserId = 1;
    protected int $operatorUserId = 2;

    protected int $startObLevel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->startObLevel = ob_get_level();

        // Set session admin/operator
        session()->set([
            'id'          => 1,
            'username'    => 'admin_test',
            'nama_lengkap'=> 'Administrator Monev',
            'role'        => 'admin',
            'logged_in'   => true,
        ]);

        $this->proyekModel        = new MonevProyekModel();
        $this->periodModel        = new MonevPeriodModel();
        $this->templateModel      = new MonevAssessmentTemplateModel();
        $this->workflowService    = new MonevWorkflowService();
        $this->integrationService = new MonevIntegrationService();
        $this->controller         = new MonevV2Controller();

        // Seed master template if needed
        $seeder = new MonevAssessmentTemplateSeeder(config('Database'));
        $seeder->run();

        // Set up test project
        $this->setupTestProject();
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > $this->startObLevel) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    protected function setupTestProject(): void
    {
        $db = \Config\Database::connect();

        // Create or get PPK and Operator users
        foreach ([
            ['id' => 1, 'username' => 'admin_test', 'nama_lengkap' => 'Administrator Monev', 'role' => 'admin'],
            ['id' => 2, 'username' => 'operator_test', 'nama_lengkap' => 'Petugas Lapangan', 'role' => 'operator'],
            ['id' => 3, 'username' => 'ppk_test', 'nama_lengkap' => 'Pejabat Pembuat Komitmen', 'role' => 'ppk'],
        ] as $u) {
            $user = $db->table('users')->where('id', $u['id'])->get()->getRowArray();
            if (!$user) {
                $db->table('users')->insert(array_merge($u, [
                    'password' => password_hash('password123', PASSWORD_DEFAULT),
                    'active'   => 1,
                ]));
            }
        }

        $existing = $this->proyekModel->where('nama_proyek', 'Proyek Pembangunan Jembatan UI Test')->first();
        if (!$existing) {
            $this->testProyekId = (int) $this->proyekModel->insert([
                'user_id'            => 3,
                'nama_proyek'        => 'Proyek Pembangunan Jembatan UI Test',
                'no_kontrak'         => '600/UI/MONEV/2026',
                'nomor_kontrak'      => '600/UI/MONEV/2026',
                'nama_paket'         => 'Paket Jembatan UI Test V2',
                'nama_penyedia'      => 'PT. Konstruksi Megah Nusantara',
                'penyedia'           => 'PT. Konstruksi Megah Nusantara',
                'nilai_kontrak'      => 3250000000.00,
                'tgl_mulai'          => '2026-02-01',
                'tgl_selesai'        => '2026-11-30',
                'tgl_spmk'           => '2026-02-05',
                'konsultan_pengawas' => 'PT. Konsultan Handal Utama',
                'direksi_teknis'     => 'Dinas PUTR Kab. Lombok Barat',
                'status'             => 'Berjalan',
                'created_at'         => date('Y-m-d H:i:s'),
            ]);
        } else {
            $this->testProyekId = (int) $existing['id'];
        }

        // Create an active Monev Period for testing UI
        $period = $this->periodModel->where('proyek_id', $this->testProyekId)->first();
        if (!$period) {
            $startRes = $this->workflowService->startMonev($this->testProyekId, $this->operatorUserId, 'operator', [
                'tgl_rencana_monev' => date('Y-m-d'),
                'jenis_monev'       => 'PERIODIC',
            ]);
            $this->testPeriodId = (int) $startRes['period_id'];
        } else {
            $this->testPeriodId = (int) $period['id'];
        }
    }

    /**
     * Test Monev V2 Index View rendering and required data points.
     */
    public function testIndexViewRendersCorrectly(): void
    {
        $viewOutput = view('monev_v2/index', [
            'title'      => 'Daftar Monitoring & Evaluasi Proyek (Monev V2)',
            'periods'    => $this->periodModel->where('proyek_id', $this->testProyekId)->findAll(),
            'kpi'        => [
                'total_monev'     => 1,
                'total_draft'     => 1,
                'total_approved'  => 0,
                'total_finalized' => 0,
            ],
            'proyekList' => $this->proyekModel->findAll(),
        ]);

        $this->assertStringContainsString('Monev V2', $viewOutput);
        $this->assertStringContainsString('Mulai Monev Baru', $viewOutput);
        $this->assertStringContainsString('modalStartMonev', $viewOutput);
        $this->assertStringContainsString('tableMonevList', $viewOutput);
        $this->assertStringContainsString('Proyek', $viewOutput);
        $this->assertStringContainsString('Penyedia', $viewOutput);
        $this->assertStringContainsString('Petugas', $viewOutput);
        $this->assertStringContainsString('Deviasi', $viewOutput);
        $this->assertStringContainsString('Skor', $viewOutput);
        $this->assertStringContainsString('Temuan', $viewOutput);
    }

    /**
     * Test Project Workspace renders all 9 requested tabs.
     */
    public function testWorkspaceRendersAllNineTabs(): void
    {
        $context           = $this->integrationService->getProjectContext($this->testProyekId);
        $proyek            = $this->proyekModel->find($this->testProyekId);
        $periods           = $this->periodModel->where('proyek_id', $this->testProyekId)->findAll();
        $activePeriod      = $this->periodModel->getFullPeriodDetails($this->testPeriodId);
        $templateStructure = $this->templateModel->getActiveTemplateWithStructure();
        $readiness         = $this->workflowService->checkFinalizationReadiness($this->testPeriodId);

        $viewOutput = view('monev_v2/workspace', [
            'title'               => 'Monev Workspace: ' . esc($proyek['nama_proyek']),
            'proyek'              => $proyek,
            'context'             => $context,
            'periods'             => $periods,
            'activePeriod'        => $activePeriod,
            'activeTab'           => 'overview',
            'template'            => $templateStructure,
            'findingsWithDetails' => [],
            'readiness'           => $readiness,
            'auditLogs'           => [],
            'ratedCount'          => 0,
            'unratedIndicators'   => [],
        ]);

        // Verifikasi 9 Tabs
        $this->assertStringContainsString('#tab-overview', $viewOutput, 'Tab Overview must exist');
        $this->assertStringContainsString('#tab-kontrak', $viewOutput, 'Tab Kontrak must exist');
        $this->assertStringContainsString('#tab-scurve', $viewOutput, 'Tab Progress & Kurva S must exist');
        $this->assertStringContainsString('#tab-monev', $viewOutput, 'Tab Monev must exist');
        $this->assertStringContainsString('#tab-assessment', $viewOutput, 'Tab Assessment must exist');
        $this->assertStringContainsString('#tab-findings', $viewOutput, 'Tab Findings must exist');
        $this->assertStringContainsString('#tab-evidence', $viewOutput, 'Tab Evidence must exist');
        $this->assertStringContainsString('#tab-documents', $viewOutput, 'Tab Documents must exist');
        $this->assertStringContainsString('#tab-history', $viewOutput, 'Tab History must exist');
    }

    /**
     * Test Assessment UI contains 6 aspects, 12 indicators, and 3-choice rating buttons.
     */
    public function testAssessmentUiStructureAndIndicators(): void
    {
        $context           = $this->integrationService->getProjectContext($this->testProyekId);
        $proyek            = $this->proyekModel->find($this->testProyekId);
        $periods           = $this->periodModel->where('proyek_id', $this->testProyekId)->findAll();
        $activePeriod      = $this->periodModel->getFullPeriodDetails($this->testPeriodId);
        $templateStructure = $this->templateModel->getActiveTemplateWithStructure();
        $readiness         = $this->workflowService->checkFinalizationReadiness($this->testPeriodId);

        // Simulasi 5 unrated
        $unratedSample = [
            ['id' => 1, 'kode' => 'IND-01', 'nama' => 'Administrasi Kontrak', 'aspek' => 'ADMINISTRASI'],
        ];

        $viewOutput = view('monev_v2/workspace', [
            'title'               => 'Monev Workspace: ' . esc($proyek['nama_proyek']),
            'proyek'              => $proyek,
            'context'             => $context,
            'periods'             => $periods,
            'activePeriod'        => $activePeriod,
            'activeTab'           => 'assessment',
            'template'            => $templateStructure,
            'findingsWithDetails' => [],
            'readiness'           => $readiness,
            'auditLogs'           => [],
            'ratedCount'          => 7,
            'unratedIndicators'   => $unratedSample,
        ]);

        // Verifikasi 6 Aspek tercantum di accordion
        $this->assertStringContainsString('ADMINISTRASI', $viewOutput);
        $this->assertStringContainsString('JADWAL DAN WAKTU', $viewOutput);
        $this->assertStringContainsString('MATERIAL', $viewOutput);
        $this->assertStringContainsString('TENAGA KERJA DAN PERALATAN', $viewOutput);
        $this->assertStringContainsString('KESELAMATAN DAN KESEHATAN KERJA', $viewOutput);
        $this->assertStringContainsString('LINGKUNGAN', $viewOutput);

        // Verifikasi Pilihan Rating 3 Skala
        $this->assertStringContainsString('Tidak Baik (1)', $viewOutput);
        $this->assertStringContainsString('Baik (2)', $viewOutput);
        $this->assertStringContainsString('Sangat Baik (3)', $viewOutput);

        // Verifikasi Progress Bar dan Warning Banner
        $this->assertStringContainsString('7 / 12 Selesai', $viewOutput);
        $this->assertStringContainsString('Indikator Mandatory yang Belum Lengkap', $viewOutput);
        $this->assertStringContainsString('IND-01', $viewOutput);

        // Verifikasi Tombol Simpan Draft dan Submit
        $this->assertStringContainsString('Simpan Draf Nilai', $viewOutput);
        $this->assertStringContainsString('btnSubmitMonev', $viewOutput);
    }

    /**
     * Test Evidence Tab partitions Monev V2 evidence, verified daily logs, and reference documents.
     */
    public function testEvidencePartitioningInWorkspace(): void
    {
        $context           = $this->integrationService->getProjectContext($this->testProyekId);
        $proyek            = $this->proyekModel->find($this->testProyekId);
        $periods           = $this->periodModel->where('proyek_id', $this->testProyekId)->findAll();
        $activePeriod      = $this->periodModel->getFullPeriodDetails($this->testPeriodId);
        $templateStructure = $this->templateModel->getActiveTemplateWithStructure();
        $readiness         = $this->workflowService->checkFinalizationReadiness($this->testPeriodId);

        $viewOutput = view('monev_v2/workspace', [
            'title'               => 'Monev Workspace: ' . esc($proyek['nama_proyek']),
            'proyek'              => $proyek,
            'context'             => $context,
            'periods'             => $periods,
            'activePeriod'        => $activePeriod,
            'activeTab'           => 'evidence',
            'template'            => $templateStructure,
            'findingsWithDetails' => [],
            'readiness'           => $readiness,
            'auditLogs'           => [],
            'ratedCount'          => 12,
            'unratedIndicators'   => [],
        ]);

        // Verifikasi 3 Partisi Terpisah
        $this->assertStringContainsString('Bukti Kunjungan & Temuan Monev V2', $viewOutput);
        $this->assertStringContainsString('Bukti Fisik Progres Harian Terverifikasi (monev_harian)', $viewOutput);
        $this->assertStringContainsString('Dokumen Master & Referensi Proyek (monev_dokumen)', $viewOutput);
        $this->assertStringContainsString('modalUploadEvidence', $viewOutput);
    }

    /**
     * Test Finding and Follow-up Modals are rendered and include necessary fields.
     */
    public function testFindingAndFollowupModals(): void
    {
        $modalFinding = view('monev_v2/components/modal_finding', [
            'activePeriod' => ['id' => $this->testPeriodId],
        ]);

        $this->assertStringContainsString('modalAddFinding', $modalFinding);
        $this->assertStringContainsString('CRITICAL', $modalFinding);
        $this->assertStringContainsString('HIGH', $modalFinding);
        $this->assertStringContainsString('MEDIUM', $modalFinding);
        $this->assertStringContainsString('LOW', $modalFinding);
        $this->assertStringContainsString('responsible_party', $modalFinding);
        $this->assertStringContainsString('due_date', $modalFinding);

        $modalFollowup = view('monev_v2/components/modal_followup', [
            'activePeriod' => ['id' => $this->testPeriodId],
        ]);

        $this->assertStringContainsString('modalAddFollowup', $modalFollowup);
        $this->assertStringContainsString('modalVerifyFollowup', $modalFollowup);
        $this->assertStringContainsString('Rencana / Tindakan Perbaikan', $modalFollowup);
        $this->assertStringContainsString('APPROVE', $modalFollowup);
        $this->assertStringContainsString('REJECT', $modalFollowup);
    }

    /**
     * Test Kurva S ApexCharts container and series rendering.
     */
    public function testKurvaSChartIntegration(): void
    {
        $context           = $this->integrationService->getProjectContext($this->testProyekId);
        $proyek            = $this->proyekModel->find($this->testProyekId);
        $periods           = $this->periodModel->where('proyek_id', $this->testProyekId)->findAll();
        $activePeriod      = $this->periodModel->getFullPeriodDetails($this->testPeriodId);
        $templateStructure = $this->templateModel->getActiveTemplateWithStructure();
        $readiness         = $this->workflowService->checkFinalizationReadiness($this->testPeriodId);

        $viewOutput = view('monev_v2/workspace', [
            'title'               => 'Monev Workspace: ' . esc($proyek['nama_proyek']),
            'proyek'              => $proyek,
            'context'             => $context,
            'periods'             => $periods,
            'activePeriod'        => $activePeriod,
            'activeTab'           => 'scurve',
            'template'            => $templateStructure,
            'findingsWithDetails' => [],
            'readiness'           => $readiness,
            'auditLogs'           => [],
            'ratedCount'          => 12,
            'unratedIndicators'   => [],
        ]);

        $this->assertStringContainsString('scurveChart', $viewOutput);
        $this->assertStringContainsString('ApexCharts', $viewOutput);
        $this->assertStringContainsString('Target Rencana (%)', $viewOutput);
        $this->assertStringContainsString('Aktual Verified (%)', $viewOutput);
    }
}
