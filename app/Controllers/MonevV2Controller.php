<?php

namespace App\Controllers;

use App\Services\MonevWorkflowService;
use App\Services\MonevIntegrationService;
use App\Models\MonevPeriodModel;
use Exception;

class MonevV2Controller extends BaseController
{
    protected MonevWorkflowService $workflowService;
    protected MonevIntegrationService $integrationService;
    protected MonevPeriodModel $periodModel;

    public function __construct()
    {
        $this->workflowService    = new MonevWorkflowService();
        $this->integrationService = new MonevIntegrationService();
        $this->periodModel        = new MonevPeriodModel();
    }

    /**
     * Memulai Monev resmi untuk sebuah proyek (Langkah 1 - 6).
     */
    public function start()
    {
        $userId   = (int) session()->get('id');
        $userRole = (string) session()->get('role');

        $proyekId = (int) $this->request->getPost('proyek_id');
        if (!$proyekId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'ID Proyek wajib disertakan.'])->setStatusCode(400);
        }

        try {
            $options = [
                'jenis_monev'       => $this->request->getPost('jenis_monev') ?? 'PERIODIC',
                'tgl_rencana_monev' => $this->request->getPost('tgl_rencana_monev') ?? date('Y-m-d'),
            ];

            $result = $this->workflowService->startMonev($proyekId, $userId, $userRole, $options);

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => "Monev Period #{$result['nomor_monev']} berhasil dimulai.",
                'data'    => $result,
            ]);
        } catch (Exception $e) {
            $code = str_contains($e->getMessage(), 'tidak memiliki wewenang') || str_contains($e->getMessage(), 'tidak diizinkan') ? 403 : 400;
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()])->setStatusCode($code);
        }
    }

    /**
     * Mengambil seluruh detail periode Monev.
     */
    public function periodDetail($periodId)
    {
        $details = $this->periodModel->getFullPeriodDetails((int) $periodId);
        if (!$details) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Monev Period tidak ditemukan.'])->setStatusCode(404);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $details,
        ]);
    }

    /**
     * Menyimpan data kunjungan lapangan (Langkah 8).
     */
    public function saveVisit()
    {
        $userId   = (int) session()->get('id');
        $userRole = (string) session()->get('role');

        $periodId = (int) $this->request->getPost('period_id');
        if (!$periodId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'ID Period wajib disertakan.'])->setStatusCode(400);
        }

        $visitData = $this->request->getPost();

        try {
            $result = $this->workflowService->saveVisit($periodId, $userId, $userRole, $visitData);
            return $this->response->setJSON(['status' => 'success', 'message' => 'Data kunjungan berhasil disimpan.', 'data' => $result]);
        } catch (Exception $e) {
            $code = str_contains($e->getMessage(), 'Ditolak') || str_contains($e->getMessage(), 'wewenang') ? 403 : 400;
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()])->setStatusCode($code);
        }
    }

    /**
     * Menyimpan penilaian 6 aspek 12 indikator (Langkah 9, 13, 14).
     */
    public function saveAssessment()
    {
        $userId   = (int) session()->get('id');
        $userRole = (string) session()->get('role');

        $periodId = (int) $this->request->getPost('period_id');
        if (!$periodId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'ID Period wajib disertakan.'])->setStatusCode(400);
        }

        $itemsJson = $this->request->getPost('items');
        $items = is_string($itemsJson) ? json_decode($itemsJson, true) : ($this->request->getPost('items') ?? []);
        if (!is_array($items)) {
            $items = [];
        }

        $ringkasan = $this->request->getPost('ringkasan_hasil');
        $kesimpulan = $this->request->getPost('kesimpulan_rekomendasi');

        try {
            $result = $this->workflowService->saveAssessment($periodId, $userId, $userRole, $items, $ringkasan, $kesimpulan);
            return $this->response->setJSON(['status' => 'success', 'message' => 'Penilaian kinerja berhasil disimpan.', 'data' => $result]);
        } catch (Exception $e) {
            $code = str_contains($e->getMessage(), 'Ditolak') || str_contains($e->getMessage(), 'wewenang') ? 403 : 400;
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()])->setStatusCode($code);
        }
    }

    /**
     * Menambahkan temuan masalah di lapangan (Langkah 11).
     */
    public function addFinding()
    {
        $userId   = (int) session()->get('id');
        $userRole = (string) session()->get('role');

        $periodId = (int) $this->request->getPost('period_id');
        if (!$periodId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'ID Period wajib disertakan.'])->setStatusCode(400);
        }

        $findingData = $this->request->getPost();

        // Handle evidence file if uploaded
        $file = $this->request->getFile('file_evidence');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $uploadErr = '';
            if (!is_secure_uploaded_file($file, ['image', 'document'], 10485760, $uploadErr)) {
                return $this->response->setJSON(['status' => 'error', 'message' => $uploadErr])->setStatusCode(400);
            }

            $uploadPath = FCPATH . 'uploads/monev_v2';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            $newName = $file->getRandomName();
            $file->move($uploadPath, $newName);

            $findingData['evidence'] = [
                'jenis_eviden'    => 'FOTO_LAPANGAN',
                'file_path'       => 'uploads/monev_v2/' . $newName,
                'file_name'       => $file->getClientName(),
                'file_size'       => $file->getSize(),
                'mime_type'       => $file->getClientMimeType(),
                'file_hash'       => hash_file('sha256', $uploadPath . '/' . $newName),
                'keterangan'      => $findingData['title'] ?? 'Bukti Temuan Lapangan',
                'tgl_pengambilan' => date('Y-m-d H:i:s'),
            ];
        }

        try {
            $result = $this->workflowService->addFinding($periodId, $userId, $userRole, $findingData);
            return $this->response->setJSON(['status' => 'success', 'message' => 'Temuan berhasil dicatat.', 'data' => $result]);
        } catch (Exception $e) {
            $code = str_contains($e->getMessage(), 'Ditolak') || str_contains($e->getMessage(), 'wewenang') || str_contains($e->getMessage(), 'diizinkan') ? 403 : 400;
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()])->setStatusCode($code);
        }
    }

    /**
     * Submit hasil Monev oleh Operator (Langkah 15).
     */
    public function submit()
    {
        $userId   = (int) session()->get('id');
        $userRole = (string) session()->get('role');

        $periodId = (int) $this->request->getPost('period_id');
        $catatan  = $this->request->getPost('catatan');

        try {
            $result = $this->workflowService->submitMonev($periodId, $userId, $userRole, $catatan);
            return $this->response->setJSON(['status' => 'success', 'message' => 'Hasil Monev berhasil disubmit untuk direview.', 'data' => $result]);
        } catch (Exception $e) {
            $code = str_contains($e->getMessage(), 'wewenang') || str_contains($e->getMessage(), 'tidak diizinkan') ? 403 : 400;
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()])->setStatusCode($code);
        }
    }

    /**
     * Review Monev oleh Admin / Reviewer (Langkah 16, 17, 18).
     */
    public function review()
    {
        $userId   = (int) session()->get('id');
        $userRole = (string) session()->get('role');

        $periodId = (int) $this->request->getPost('period_id');
        $decision = (string) $this->request->getPost('decision'); // APPROVE / RETURN
        $catatan  = $this->request->getPost('catatan');

        try {
            $result = $this->workflowService->reviewMonev($periodId, $userId, $userRole, $decision, $catatan);
            $actionMsg = $decision === 'APPROVE' ? 'disetujui' : 'dikembalikan untuk perbaikan';
            return $this->response->setJSON(['status' => 'success', 'message' => "Monev berhasil {$actionMsg}.", 'data' => $result]);
        } catch (Exception $e) {
            $code = str_contains($e->getMessage(), 'wewenang') || str_contains($e->getMessage(), 'otorisasi') ? 403 : 400;
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()])->setStatusCode($code);
        }
    }

    /**
     * Finalisasi Monev oleh Admin (Langkah 19, 20).
     */
    public function finalize()
    {
        $userId   = (int) session()->get('id');
        $userRole = (string) session()->get('role');

        $periodId = (int) $this->request->getPost('period_id');
        $catatan  = $this->request->getPost('catatan');

        try {
            // Check readiness (if not checked by workflow)
            $readiness = $this->workflowService->checkFinalizationReadiness($periodId);
            if (!$readiness['is_ready']) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Belum memenuhi syarat finalisasi.'])->setStatusCode(400);
            }

            // Get Period Data before finalizing (to pass to PDF)
            $period = $this->periodModel->getFullPeriodDetails($periodId);
            if (!$period) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Periode tidak ditemukan.'])->setStatusCode(404);
            }
            $context = $this->integrationService->getProjectContext((int) $period['proyek_id']);
            
            // Get Assessment Template and Findings
            $templateModel = new \App\Models\MonevAssessmentTemplateModel();
            $assessment = $templateModel->getActiveTemplateWithStructure();
            $assessment['items'] = $period['assessment']['items'] ?? [];
            
            $findingModel = new \App\Models\MonevFindingModel();
            $rawFindings = $findingModel->where('monev_period_id', $periodId)->orderBy('id', 'ASC')->findAll();
            $findings = [];
            foreach ($rawFindings as $rf) {
                $findings[] = $findingModel->getFindingWithDetails((int) $rf['id']);
            }

            // Execute Workflow (sets status to FINALIZED, is_locked = 1)
            $result = $this->workflowService->finalizeMonev($periodId, $userId, $userRole, $catatan);
            
            // Re-fetch Period to get the updated status and dates
            $period = $this->periodModel->getFullPeriodDetails($periodId);
            
            // Generate PDF Hash and Document Number
            $pdfGen = new \App\Libraries\MonevPdfGenerator();
            $hash = $pdfGen->generateDocumentHash($period);
            
            // Get version
            $db = \Config\Database::connect();
            $existingDocs = $db->table('monev_period_documents')->where('monev_period_id', $periodId)->countAllResults();
            $version = $existingDocs + 1;
            
            $docNumber = "MONEV/".date('Y/m')."/PRJ-".$period['proyek_id']."/V".$version;
            
            // Generate QR SVG
            $qrData = base_url("verify/document?hash=" . $hash);
            $qrSvg = $pdfGen->generateQrCodeSvg($qrData);
            
            // Prepare PDF HTML
            $html = view('monev_v2/pdf_template', [
                'doc_number' => $docNumber,
                'finalized_at' => $period['updated_at'],
                'contract' => $context['contract_data'],
                'period' => $period,
                'progress' => $context['progress_metrics'],
                'assessment' => $assessment,
                'findings' => $findings,
                'hash' => $hash,
                'qr_svg' => $qrSvg
            ]);
            
            $fileName = 'MONEV_FINAL_P' . $periodId . '_V' . $version . '_' . time() . '.pdf';
            $saveDir = FCPATH . 'uploads/monev_documents';
            if (!is_dir($saveDir)) {
                mkdir($saveDir, 0777, true);
            }
            $savePath = $saveDir . '/' . $fileName;
            
            $pdfGen->generateAndSave($html, $savePath);
            $publicPath = 'uploads/monev_documents/' . $fileName;
            
            // Insert tracking record
            $db->table('monev_period_documents')->insert([
                'monev_period_id' => $periodId,
                'version' => $version,
                'document_number' => $docNumber,
                'hash_signature' => $hash,
                'file_path' => $publicPath,
                'created_by' => $userId,
            ]);
            
            // Update period final document path
            $this->periodModel->update($periodId, ['final_document_path' => $publicPath]);

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Monev resmi telah difinalisasi. Dokumen PDF V'.$version.' berhasil di-generate.',
                'data'    => $result,
            ]);
        } catch (Exception $e) {
            $code = str_contains($e->getMessage(), 'wewenang') || str_contains($e->getMessage(), 'Administrator') ? 403 : 400;
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()])->setStatusCode($code);
        }
    }

    /**
     * Membuka kunci sesi Monev untuk revisi oleh Administrator.
     */
    public function reopen()
    {
        $userId   = (int) session()->get('id');
        $userRole = (string) session()->get('role');

        $periodId     = (int) $this->request->getPost('period_id');
        $alasanRevisi = (string) $this->request->getPost('alasan_revisi');

        try {
            $result = $this->workflowService->reopenMonev($periodId, $userId, $userRole, $alasanRevisi);
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Kunci sesi Monev berhasil dibuka. Anda sekarang dapat merevisi penilaian, temuan, dan data lapangan.',
                'data'    => $result,
            ]);
        } catch (Exception $e) {
            $code = str_contains($e->getMessage(), 'wewenang') || str_contains($e->getMessage(), 'Administrator') ? 403 : 400;
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()])->setStatusCode($code);
        }
    }

    /**
     * Riwayat Monev per Proyek.
     */
    public function getProjectPeriods($proyekId)
    {
        $periods = $this->periodModel->where('proyek_id', (int) $proyekId)
            ->orderBy('periode_ke', 'DESC')
            ->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $periods,
        ]);
    }

    /**
     * Mengambil konteks data integrasi proyek existing (tanpa duplicate input, Kurva S verified, insight Indikator 3 & 4).
     */
    public function projectContext($proyekId)
    {
        try {
            $asOfDate = $this->request->getGet('as_of_date');
            $data = $this->integrationService->getProjectContext((int) $proyekId, $asOfDate);

            return $this->response->setJSON([
                'status' => 'success',
                'data'   => $data,
            ]);
        } catch (Exception $e) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ])->setStatusCode(404);
        }
    }

    /**
     * Memperbarui status Finding / Temuan.
     */
    public function updateFindingStatus()
    {
        $userId   = (int) session()->get('id');
        $userRole = (string) session()->get('role');

        $findingId = (int) $this->request->getPost('finding_id');
        $status    = (string) $this->request->getPost('status');
        $notes     = $this->request->getPost('notes');

        try {
            $result = $this->workflowService->updateFindingStatus($findingId, $userId, $userRole, $status, $notes);
            return $this->response->setJSON(['status' => 'success', 'message' => 'Status temuan berhasil diperbarui.', 'data' => $result]);
        } catch (Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()])->setStatusCode(400);
        }
    }

    /**
     * Menambahkan Tindakan Perbaikan (Follow-up / Action Item) pada temuan.
     */
    public function saveFollowup()
    {
        $userId   = (int) session()->get('id');
        $userRole = (string) session()->get('role');

        $findingId = (int) $this->request->getPost('finding_id');
        $data      = $this->request->getPost();

        // Handle evidence file if uploaded
        $file = $this->request->getFile('file_evidence');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $uploadErr = '';
            if (!is_secure_uploaded_file($file, ['image', 'document'], 10485760, $uploadErr)) {
                return $this->response->setJSON(['status' => 'error', 'message' => $uploadErr])->setStatusCode(400);
            }

            $uploadPath = FCPATH . 'uploads/monev_v2';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            $newName = $file->getRandomName();
            $file->move($uploadPath, $newName);

            $data['evidence'] = [
                'jenis_eviden'    => 'BUKTI_PERBAIKAN',
                'file_path'       => 'uploads/monev_v2/' . $newName,
                'file_name'       => $file->getClientName(),
                'file_size'       => $file->getSize(),
                'mime_type'       => $file->getClientMimeType(),
                'file_hash'       => hash_file('sha256', $uploadPath . '/' . $newName),
                'keterangan'      => 'Bukti Perbaikan: ' . ($data['action'] ?? 'Follow-up'),
                'tgl_pengambilan' => date('Y-m-d H:i:s'),
            ];
        }

        try {
            $result = $this->workflowService->addFollowup($findingId, $userId, $userRole, $data);
            return $this->response->setJSON(['status' => 'success', 'message' => 'Tindakan perbaikan berhasil dicatat.', 'data' => $result]);
        } catch (Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()])->setStatusCode(400);
        }
    }

    /**
     * Memverifikasi Tindakan Perbaikan (Follow-up).
     */
    public function verifyFollowup()
    {
        $userId   = (int) session()->get('id');
        $userRole = (string) session()->get('role');

        $followupId = (int) $this->request->getPost('followup_id');
        $decision   = (string) $this->request->getPost('decision'); // APPROVE / REJECT
        $notes      = $this->request->getPost('notes');

        try {
            $result = $this->workflowService->verifyFollowup($followupId, $userId, $userRole, $decision, $notes);
            return $this->response->setJSON(['status' => 'success', 'message' => 'Verifikasi follow-up berhasil disimpan.', 'data' => $result]);
        } catch (Exception $e) {
            $code = str_contains($e->getMessage(), 'wewenang') ? 403 : 400;
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()])->setStatusCode($code);
        }
    }

    /**
     * Cek kesiapan finalisasi Monev (Readiness Check).
     */
    public function finalizationReadiness($periodId)
    {
        try {
            $readiness = $this->workflowService->checkFinalizationReadiness((int) $periodId);
            return $this->response->setJSON(['status' => 'success', 'data' => $readiness]);
        } catch (Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()])->setStatusCode(400);
        }
    }

    /**
     * Upload berkas bukti (Evidence) untuk Finding, Followup, atau Visit.
     */
    public function uploadEvidence()
    {
        $userId   = (int) session()->get('id');
        $userRole = (string) session()->get('role');

        $entityType = (string) $this->request->getPost('entity_type');
        $entityId   = (int) $this->request->getPost('entity_id');
        $keterangan = $this->request->getPost('keterangan');
        $jenisEviden = $this->request->getPost('jenis_eviden') ?? 'FOTO_LAPANGAN';
        $gpsLat = $this->request->getPost('gps_lat');
        $gpsLng = $this->request->getPost('gps_lng');

        $file = $this->request->getFile('file_evidence');
        $uploadErr = '';
        if (!is_secure_uploaded_file($file, ['image', 'document'], 10485760, $uploadErr)) {
            return $this->response->setJSON(['status' => 'error', 'message' => $uploadErr])->setStatusCode(400);
        }

        $uploadPath = FCPATH . 'uploads/monev_v2';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $newName = $file->getRandomName();
        $file->move($uploadPath, $newName);

        $evidenceModel = new \App\Models\MonevEvidenceModel();
        $evidenceModel->insert([
            'entity_type'     => $entityType,
            'entity_id'       => $entityId,
            'jenis_eviden'    => $jenisEviden,
            'file_path'       => 'uploads/monev_v2/' . $newName,
            'file_name'       => $file->getClientName(),
            'file_size'       => $file->getSize(),
            'mime_type'       => $file->getClientMimeType(),
            'gps_lat'         => $gpsLat,
            'gps_lng'         => $gpsLng,
            'tgl_pengambilan' => date('Y-m-d H:i:s'),
            'keterangan'      => $keterangan,
            'uploaded_by'     => $userId,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'status'    => 'success',
            'message'   => 'Bukti berhasil diunggah.',
            'file_path' => 'uploads/monev_v2/' . $newName,
        ]);
    }

    /**
     * Hapus berkas bukti (Evidence) Monev V2.
     */
    public function deleteEvidence($id)
    {
        $userId   = (int) session()->get('id');
        $userRole = (string) session()->get('role');

        if (!in_array($userRole, ['admin', 'operator'])) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Anda tidak memiliki hak akses untuk menghapus bukti ini.'
            ])->setStatusCode(403);
        }

        $evidenceModel = new \App\Models\MonevEvidenceModel();
        $evidence = $evidenceModel->find((int) $id);
        if (!$evidence) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Data bukti tidak ditemukan.'
            ])->setStatusCode(404);
        }

        // Jika bukti terikat pada periode Monev, cek apakah periode terkunci
        if ($evidence['entity_type'] === 'MONEV_PERIOD') {
            $period = $this->periodModel->find((int) $evidence['entity_id']);
            if ($period && (int)($period['is_locked'] ?? 0) === 1) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'Sesi Monev ini telah dikunci (Final). Foto tidak dapat dihapus.'
                ])->setStatusCode(400);
            }
        }

        // Hapus file fisik dari storage jika ada
        if (!empty($evidence['file_path'])) {
            $fullPath = FCPATH . ltrim($evidence['file_path'], '/\\');
            if (file_exists($fullPath) && is_file($fullPath)) {
                @unlink($fullPath);
            }
        }

        $evidenceModel->delete((int) $id);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Foto bukti berhasil dihapus.'
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // UI VIEW METHODS (Step 7)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Halaman Daftar Monev V2 (/dashboard/monev atau /monev).
     * Menampilkan: proyek, penyedia, tanggal Monev, petugas, progress, deviasi, status proyek, status Monev, score kinerja, jumlah findings, action.
     */
    public function index()
    {
        $dashboardService = new \App\Services\MonevDashboardService();
        $kpi = $dashboardService->getAggregatedKpis();
        $projectHealthList = $dashboardService->getProjectHealthList();

        // Also fetch the recent history of Monev sessions for the bottom table
        $db = \Config\Database::connect();
        $recentPeriods = $this->periodModel->select('monev_period.*, monev_proyek.nama_proyek, monev_proyek.no_kontrak, monev_proyek.nama_penyedia, monev_proyek.nilai_kontrak, users.nama_lengkap as nama_assessor, users.username as username_assessor')
            ->join('monev_proyek', 'monev_proyek.id = monev_period.proyek_id', 'inner')
            ->join('users', 'users.id = monev_period.assessor_id', 'left')
            ->orderBy('monev_period.id', 'DESC')
            ->limit(20)
            ->findAll();
            
        foreach ($recentPeriods as &$p) {
            $periodId = (int) $p['id'];
            $p['total_findings'] = $db->table('monev_finding')->where('monev_period_id', $periodId)->countAllResults();
            $p['open_findings']  = $db->table('monev_finding')->where('monev_period_id', $periodId)->whereIn('status', ['OPEN', 'IN_PROGRESS'])->countAllResults();
            
            $progSnap = !empty($p['progress_snapshot']) && is_string($p['progress_snapshot']) ? json_decode($p['progress_snapshot'], true) : ($p['progress_snapshot'] ?? []);
            $p['progres_aktual'] = (float) ($progSnap['progress_aktual_verified'] ?? ($progSnap['progres_persen'] ?? 0));
            $p['progres_rencana'] = (float) ($progSnap['progress_rencana'] ?? 0);
            $p['deviasi'] = (float) ($progSnap['deviasi'] ?? round($p['progres_aktual'] - $p['progres_rencana'], 2));
            $p['status_proyek'] = $progSnap['status_proyek'] ?? ($p['deviasi'] >= 0 ? 'Sesuai Jadwal' : 'Terlambat');
        }

        $proyekList = (new \App\Models\MonevProyekModel())->orderBy('id', 'DESC')->findAll();

        return view('monev_v2/index', [
            'title'             => 'Dashboard & Early Warning (Monev V2)',
            'kpi'               => $kpi,
            'projectHealthList' => $projectHealthList,
            'recentPeriods'     => $recentPeriods,
            'proyekList'        => $proyekList,
        ]);
    }

    /**
     * Project Monev Workspace (/dashboard/monev/project/{id} atau /dashboard/monev/{id}).
     * Memuat 9 tab lengkap.
     */
    public function projectWorkspace($proyekId, $periodId = null, $activeTab = 'overview')
    {
        $proyekModel = new \App\Models\MonevProyekModel();
        $proyek = $proyekModel->find((int) $proyekId);
        if (!$proyek) {
            return redirect()->to(base_url('dashboard/monev'))->with('error', 'Proyek tidak ditemukan.');
        }

        $context = $this->integrationService->getProjectContext((int) $proyekId);

        $periods = $this->periodModel->where('proyek_id', (int) $proyekId)
            ->orderBy('periode_ke', 'DESC')
            ->findAll();

        $periodId = $periodId ?: $this->request->getGet('period_id');
        $activePeriod = null;
        if ($periodId) {
            $activePeriod = $this->periodModel->getFullPeriodDetails((int) $periodId);
        } elseif (!empty($periods)) {
            $activePeriod = $this->periodModel->getFullPeriodDetails((int) $periods[0]['id']);
        }

        $templateModel = new \App\Models\MonevAssessmentTemplateModel();
        $templateStructure = $templateModel->getActiveTemplateWithStructure();

        $findingsWithDetails = [];
        if ($activePeriod) {
            $findingModel = new \App\Models\MonevFindingModel();
            $rawFindings = $findingModel->where('monev_period_id', $activePeriod['id'])->orderBy('id', 'ASC')->findAll();
            foreach ($rawFindings as $rf) {
                $findingsWithDetails[] = $findingModel->getFindingWithDetails((int) $rf['id']);
            }
        }

        $readiness = null;
        if ($activePeriod) {
            $readiness = $this->workflowService->checkFinalizationReadiness((int) $activePeriod['id']);
        }

        $auditLogs = [];
        $finalDocuments = [];
        if ($activePeriod) {
            $db = \Config\Database::connect();
            $auditLogs = $db->table('monev_audit_log')
                ->select('monev_audit_log.*, users.nama_lengkap as actor_nama')
                ->join('users', 'users.id = monev_audit_log.actor_id', 'left')
                ->where('monev_period_id', $activePeriod['id'])
                ->orderBy('created_at', 'DESC')
                ->get()
                ->getResultArray();
                
            $finalDocuments = $db->table('monev_period_documents')
                ->select('monev_period_documents.*, users.nama_lengkap as creator_nama')
                ->join('users', 'users.id = monev_period_documents.created_by', 'left')
                ->where('monev_period_id', $activePeriod['id'])
                ->orderBy('version', 'DESC')
                ->get()
                ->getResultArray();
        }

        // Hitung progress kelengkapan assessment: X dari 12 selesai
        $ratedCount = 0;
        $unratedIndicators = [];
        $ratedItems = [];
        if ($activePeriod && !empty($activePeriod['assessment']['items'])) {
            foreach ($activePeriod['assessment']['items'] as $item) {
                if (!empty($item['score']) && (int) $item['score'] > 0) {
                    $ratedCount++;
                    $ratedItems[$item['indicator_id']] = true;
                }
            }
        }

        if (!empty($templateStructure['aspects'])) {
            foreach ($templateStructure['aspects'] as $asp) {
                foreach ($asp['indicators'] as $ind) {
                    if (!isset($ratedItems[$ind['id']])) {
                        $unratedIndicators[] = [
                            'id'     => $ind['id'],
                            'kode'   => $ind['kode_indikator'],
                            'nama'   => $ind['nama_indikator'],
                            'aspek'  => $asp['nama_aspek'],
                        ];
                    }
                }
            }
        }

        return view('monev_v2/workspace', [
            'title'               => 'Monev Workspace: ' . esc($proyek['nama_proyek']),
            'proyek'              => $proyek,
            'context'             => $context,
            'periods'             => $periods,
            'activePeriod'        => $activePeriod,
            'activeTab'           => $activeTab,
            'template'            => $templateStructure,
            'findingsWithDetails' => $findingsWithDetails,
            'readiness'           => $readiness,
            'auditLogs'           => $auditLogs,
            'finalDocuments'      => $finalDocuments,
            'ratedCount'          => $ratedCount,
            'unratedIndicators'   => $unratedIndicators,
        ]);
    }

    public function periodWorkspace($periodId)
    {
        $period = $this->periodModel->find((int) $periodId);
        if (!$period) {
            return redirect()->to(base_url('dashboard/monev'))->with('error', 'Sesi Monev tidak ditemukan.');
        }
        return $this->projectWorkspace($period['proyek_id'], (int) $periodId, 'overview');
    }

    public function assessmentView($periodId)
    {
        $period = $this->periodModel->find((int) $periodId);
        if (!$period) {
            return redirect()->to(base_url('dashboard/monev'))->with('error', 'Sesi Monev tidak ditemukan.');
        }
        return $this->projectWorkspace($period['proyek_id'], (int) $periodId, 'assessment');
    }

    public function findingsView($periodId)
    {
        $period = $this->periodModel->find((int) $periodId);
        if (!$period) {
            return redirect()->to(base_url('dashboard/monev'))->with('error', 'Sesi Monev tidak ditemukan.');
        }
        return $this->projectWorkspace($period['proyek_id'], (int) $periodId, 'findings');
    }

    public function evidenceView($periodId)
    {
        $period = $this->periodModel->find((int) $periodId);
        if (!$period) {
            return redirect()->to(base_url('dashboard/monev'))->with('error', 'Sesi Monev tidak ditemukan.');
        }
        return $this->projectWorkspace($period['proyek_id'], (int) $periodId, 'evidence');
    }

    public function reviewView($periodId)
    {
        $period = $this->periodModel->find((int) $periodId);
        if (!$period) {
            return redirect()->to(base_url('dashboard/monev'))->with('error', 'Sesi Monev tidak ditemukan.');
        }
        return $this->projectWorkspace($period['proyek_id'], (int) $periodId, 'review');
    }

    public function historyView($periodId)
    {
        $period = $this->periodModel->find((int) $periodId);
        if (!$period) {
            return redirect()->to(base_url('dashboard/monev'))->with('error', 'Sesi Monev tidak ditemukan.');
        }
        return $this->projectWorkspace($period['proyek_id'], (int) $periodId, 'history');
    }

    public function createPeriodForProject($proyekId)
    {
        return $this->projectWorkspace((int) $proyekId, null, 'monev');
    }
}
