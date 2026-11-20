<?php

namespace App\Controllers;

use App\Models\MasterAnggaranModel;
use App\Models\MasterAnggaranHistoryModel;
use App\Models\RolePermissionModel;

class MasterAnggaranController extends BaseController
{
    protected $masterModel;
    protected $historyModel;

    public function __construct()
    {
        $this->masterModel = new MasterAnggaranModel();
        $this->historyModel = new MasterAnggaranHistoryModel();
    }

    public function index()
    {
        $currentYear = $this->request->getGet('year') ?? date('Y');
        $selectedOpd = $this->request->getGet('opd') ?? 'all';
        $db = \Config\Database::connect();

        // 1. Available Years
        $yearsList = $db->table('master_anggaran')
            ->select('tahun')
            ->distinct()
            ->get()
            ->getResultArray();
        $availableYears = array_filter(array_column($yearsList, 'tahun'));
        rsort($availableYears);
        if (empty($availableYears)) {
            $availableYears = [date('Y')];
        }

        // 2. Fetch Master Anggaran for current year
        $builder = $this->masterModel->where('tahun', $currentYear);

        // RBAC filtering for Satker / OPD role
        $userRole = trim(strtolower(session()->get('role') ?? ''));
        $userSatker = trim(session()->get('kd_satker') ?? '');
        $userNamaSatker = trim(session()->get('nama_satker') ?? session()->get('nama_lengkap') ?? '');
        $isRestrictedRole = in_array($userRole, ['satker', 'opd']);

        if ($isRestrictedRole) {
            if ($userSatker) {
                $builder->where('kd_satker', $userSatker);
            } elseif ($userNamaSatker) {
                $builder->like('nama_satker', $userNamaSatker);
            }
        } elseif ($selectedOpd && $selectedOpd !== 'all') {
            $builder->where('nama_satker', $selectedOpd);
        }

        $allRows = $builder->orderBy('nama_satker', 'ASC')
            ->orderBy('kd_program_str', 'ASC')
            ->findAll();

        // 3. Group by Satker / OPD
        $opdGrouped = [];
        $availableOpds = [];

        $totalPaguAwalKab = 0;
        $totalPaguTerkiniKab = 0;
        $totalEditedPrograms = 0;

        foreach ($allRows as $r) {
            $satkerName = trim($r['nama_satker']) ?: 'Satker Tanpa Nama';
            $kdSatker = $r['kd_satker'] ?: '-';

            if (!isset($availableOpds[$satkerName])) {
                $availableOpds[$satkerName] = $satkerName;
            }

            if (!isset($opdGrouped[$satkerName])) {
                $opdGrouped[$satkerName] = [
                    'kd_satker'           => $kdSatker,
                    'nama_satker'         => $satkerName,
                    'jml_program'         => 0,
                    'total_pagu_awal'     => 0,
                    'total_pagu_terkini'  => 0,
                    'total_selisih'       => 0,
                    'total_edited'        => 0,
                    'programs'            => []
                ];
            }

            $paguAwal = $r['pagu_awal'] !== null ? (float)$r['pagu_awal'] : (float)$r['pagu_program'];
            $paguTerkini = (float)$r['pagu_program'];
            $selisih = $paguTerkini - $paguAwal;
            $isEdited = (int)($r['is_edited'] ?? 0);

            $opdGrouped[$satkerName]['jml_program'] += 1;
            $opdGrouped[$satkerName]['total_pagu_awal'] += $paguAwal;
            $opdGrouped[$satkerName]['total_pagu_terkini'] += $paguTerkini;
            $opdGrouped[$satkerName]['total_selisih'] += $selisih;
            if ($isEdited) {
                $opdGrouped[$satkerName]['total_edited'] += 1;
                $totalEditedPrograms++;
            }

            $totalPaguAwalKab += $paguAwal;
            $totalPaguTerkiniKab += $paguTerkini;

            $opdGrouped[$satkerName]['programs'][] = [
                'id'                   => $r['id'],
                'kd_program_str'       => $r['kd_program_str'],
                'nama_program'         => $r['nama_program'],
                'pagu_awal'            => $paguAwal,
                'pagu_program'         => $paguTerkini,
                'selisih'              => $selisih,
                'is_edited'            => $isEdited,
                'jenis_perubahan'      => $r['jenis_perubahan'],
                'keterangan_perubahan' => $r['keterangan_perubahan'],
                'updated_at'           => $r['updated_at']
            ];
        }

        // Determine if current user has edit permission
        $canEdit = in_array($userRole, ['admin', 'operator']) || (has_permission('master_anggaran') && !$isRestrictedRole);

        // Fetch recent changes (history log preview)
        $recentHistory = $this->historyModel
            ->where('tahun', $currentYear)
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->findAll();

        return view('master_anggaran/index', [
            'title'               => 'Master Anggaran OPD',
            'user'                => session()->get(),
            'currentYear'         => $currentYear,
            'selectedOpd'         => $selectedOpd,
            'availableYears'      => $availableYears,
            'availableOpds'       => array_values($availableOpds),
            'opdGrouped'          => array_values($opdGrouped),
            'recentHistory'       => $recentHistory,
            'canEdit'             => $canEdit,
            'isRestrictedRole'    => $isRestrictedRole,
            'kpi'                 => [
                'total_pagu_awal'    => $totalPaguAwalKab,
                'total_pagu_terkini' => $totalPaguTerkiniKab,
                'total_selisih'      => $totalPaguTerkiniKab - $totalPaguAwalKab,
                'total_opd'          => count($opdGrouped),
                'total_program'      => count($allRows),
                'total_edited'       => $totalEditedPrograms
            ]
        ]);
    }

    public function update()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Hanya menerima request AJAX.']);
        }

        $userRole = trim(strtolower(session()->get('role') ?? ''));
        $canEdit = in_array($userRole, ['admin', 'operator']) || has_permission('master_anggaran');

        if (!$canEdit) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk mengubah Master Anggaran.']);
        }

        $id = (int)$this->request->getPost('id');
        $paguBaruRaw = $this->request->getPost('pagu_baru');
        $jenisPerubahan = trim($this->request->getPost('jenis_perubahan') ?? '');
        $komentar = trim($this->request->getPost('komentar') ?? '');

        if (!$id) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'ID Program tidak valid.']);
        }

        // Clean currency format: "Rp 1.000.000,00" or "1000000"
        $paguBaruClean = str_replace(['Rp', ' ', '.'], '', $paguBaruRaw);
        $paguBaruClean = str_replace(',', '.', $paguBaruClean);
        $paguBaru = (float)$paguBaruClean;

        if ($paguBaru < 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Nilai Pagu Anggaran tidak boleh negatif.']);
        }

        if (empty($jenisPerubahan)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Pilih jenis perubahan anggaran (Pergeseran / APBD-P / dll).']);
        }

        if (empty($komentar)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Komentar / alasan perubahan anggaran wajib diisi untuk akuntabilitas.']);
        }

        $masterItem = $this->masterModel->find($id);
        if (!$masterItem) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data Program Master Anggaran tidak ditemukan.']);
        }

        $paguSebelum = (float)$masterItem['pagu_program'];
        $paguSesudah = $paguBaru;
        $selisih = $paguSesudah - $paguSebelum;

        $paguAwal = $masterItem['pagu_awal'] !== null ? (float)$masterItem['pagu_awal'] : $paguSebelum;

        // 1. Update Master Anggaran Table
        $this->masterModel->update($id, [
            'pagu_program'         => $paguSesudah,
            'pagu_awal'            => $paguAwal,
            'is_edited'            => 1,
            'jenis_perubahan'      => $jenisPerubahan,
            'keterangan_perubahan' => $komentar,
            'updated_at'           => date('Y-m-d H:i:s')
        ]);

        // 2. Insert into History Log Table
        $userId = session()->get('id') ?? session()->get('user_id');
        $userNama = session()->get('nama_lengkap') ?: (session()->get('username') ?: 'User');

        $this->historyModel->insert([
            'master_anggaran_id' => $id,
            'kd_satker'          => $masterItem['kd_satker'],
            'nama_satker'        => $masterItem['nama_satker'],
            'kd_program_str'     => $masterItem['kd_program_str'],
            'nama_program'       => $masterItem['nama_program'],
            'pagu_sebelum'       => $paguSebelum,
            'pagu_sesudah'       => $paguSesudah,
            'selisih'            => $selisih,
            'jenis_perubahan'    => $jenisPerubahan,
            'komentar'           => $komentar,
            'user_id'            => $userId,
            'user_nama'          => $userNama,
            'tahun'              => (int)$masterItem['tahun'],
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s')
        ]);

        // 3. Clear Caches to keep RUP, comparisons, and dashboards 100% in sync
        $year = $masterItem['tahun'];
        cache()->delete("rup_full_profile_and_analysis_v2_{$year}");
        cache()->delete("rup_rekap_profil_opd_v1_{$year}");
        cache()->delete("rup_opd_v15_full_db_{$year}");

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Perubahan anggaran berhasil disimpan dan tercatat di histori.',
            'data'    => [
                'id'                   => $id,
                'pagu_awal'            => $paguAwal,
                'pagu_program'         => $paguSesudah,
                'pagu_program_fmt'     => number_format($paguSesudah, 2, ',', '.'),
                'selisih'              => $selisih,
                'selisih_fmt'          => ($selisih > 0 ? '+' : '') . number_format($selisih, 2, ',', '.'),
                'jenis_perubahan'      => $jenisPerubahan,
                'keterangan_perubahan' => $komentar
            ]
        ]);
    }

    public function history($masterId)
    {
        $masterId = (int)$masterId;
        $history = $this->historyModel
            ->where('master_anggaran_id', $masterId)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $masterItem = $this->masterModel->find($masterId);

        return $this->response->setJSON([
            'status'  => 'success',
            'program' => $masterItem,
            'history' => $history
        ]);
    }

    public function export()
    {
        $currentYear = $this->request->getGet('year') ?? date('Y');
        $allRows = $this->masterModel
            ->where('tahun', $currentYear)
            ->orderBy('nama_satker', 'ASC')
            ->orderBy('kd_program_str', 'ASC')
            ->findAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="Master_Anggaran_' . $currentYear . '.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

        fputcsv($output, [
            'No',
            'Kode Satker',
            'Nama OPD / Satuan Kerja',
            'Kode Program',
            'Nama Program Kerja',
            'Pagu Awal DPA (Rp)',
            'Pagu Terkini (Rp)',
            'Selisih (Rp)',
            'Status Perubahan',
            'Jenis Perubahan',
            'Komentar / Alasan Perubahan',
            'Tahun Anggaran'
        ]);

        $no = 1;
        foreach ($allRows as $r) {
            $paguAwal = $r['pagu_awal'] !== null ? (float)$r['pagu_awal'] : (float)$r['pagu_program'];
            $paguTerkini = (float)$r['pagu_program'];
            $selisih = $paguTerkini - $paguAwal;
            $status = !empty($r['is_edited']) ? 'Ada Perubahan' : 'Original DPA';

            fputcsv($output, [
                $no++,
                $r['kd_satker'],
                $r['nama_satker'],
                $r['kd_program_str'],
                $r['nama_program'],
                $paguAwal,
                $paguTerkini,
                $selisih,
                $status,
                $r['jenis_perubahan'] ?? '-',
                $r['keterangan_perubahan'] ?? '-',
                $r['tahun']
            ]);
        }

        fclose($output);
        exit;
    }
}
