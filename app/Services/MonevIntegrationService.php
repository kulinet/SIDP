<?php

namespace App\Services;

use App\Models\MonevProyekModel;
use App\Models\MonevJadwalModel;
use App\Models\MonevHarianModel;
use App\Models\MonevDokumenModel;
use App\Models\MonevEvidenceModel;
use InvalidArgumentException;

class MonevIntegrationService
{
    protected MonevProyekModel $proyekModel;
    protected MonevJadwalModel $jadwalModel;
    protected MonevHarianModel $harianModel;
    protected MonevDokumenModel $dokumenModel;
    protected MonevEvidenceModel $evidenceModel;

    public function __construct()
    {
        $this->proyekModel   = new MonevProyekModel();
        $this->jadwalModel   = new MonevJadwalModel();
        $this->harianModel   = new MonevHarianModel();
        $this->dokumenModel  = new MonevDokumenModel();
        $this->evidenceModel = new MonevEvidenceModel();
    }

    /**
     * Mengambil konteks lengkap proyek terintegrasi dari data existing
     * tanpa duplikasi input data kontrak, mengunci official progress hanya pada data yang verified,
     * serta menyajikan system-derived information & insight untuk Indikator 3 & 4.
     *
     * @param int $proyekId
     * @param string|null $asOfDate Tanggal evaluasi (default: hari ini)
     * @return array
     */
    public function getProjectContext(int $proyekId, ?string $asOfDate = null): array
    {
        $evaluationDate = $asOfDate ? date('Y-m-d', strtotime($asOfDate)) : date('Y-m-d');
        $db = \Config\Database::connect();

        // 1. Data Kontrak Existing (tanpa duplicate input)
        $proyek = $this->proyekModel->select('monev_proyek.*, users.nama_lengkap as ppk_nama_lengkap, users.email as ppk_email, users.nomor_hp as ppk_no_hp')
            ->join('users', 'users.id = monev_proyek.user_id', 'left')
            ->where('monev_proyek.id', $proyekId)
            ->first();

        if (!$proyek) {
            throw new InvalidArgumentException("Proyek dengan ID {$proyekId} tidak ditemukan pada data existing.");
        }

        // Ambil info SPMK dan paket dari tender/nontender jika ada RUP
        $tenderInfo = null;
        if (!empty($proyek['kd_rup'])) {
            $tenderInfo = $db->table('lpse_tender')->where('kd_rup', $proyek['kd_rup'])->get()->getRowArray();
            if (!$tenderInfo) {
                $tenderInfo = $db->table('lpse_nontender')->where('kd_rup', $proyek['kd_rup'])->get()->getRowArray();
            }
        }

        $noSpmk = $tenderInfo['no_spmk_spp'] ?? ($proyek['no_spmk'] ?? '-');
        $waktuPenyelesaian = $tenderInfo['waktu_penyelesaian'] ?? null;

        // 2. Kalkulasi Umur Kontrak & Sisa Waktu
        $tglMulai = !empty($proyek['tgl_mulai']) ? $proyek['tgl_mulai'] : $evaluationDate;
        $tglSelesai = !empty($proyek['tgl_selesai']) ? $proyek['tgl_selesai'] : $tglMulai;

        $startTs = strtotime($tglMulai);
        $endTs = strtotime($tglSelesai);
        $evalTs = strtotime($evaluationDate);

        $totalDurationDays = max(1, (int) round(($endTs - $startTs) / 86400) + 1);
        $elapsedDays = max(0, (int) round(($evalTs - $startTs) / 86400));
        $remainingDays = (int) round(($endTs - $evalTs) / 86400);
        $isOverdue = ($remainingDays < 0);
        $timePercentage = round(min(100.0, max(0.0, ($elapsedDays / $totalDurationDays) * 100)), 2);

        // 3. Progress Verification Rule: Hanya data monev_harian yang verified yang boleh menjadi official progress
        $verifiedLogs = $this->harianModel->where('proyek_id', $proyekId)
            ->where('is_verified', 1)
            ->orderBy('tanggal', 'ASC')
            ->findAll();

        $unverifiedCount = $this->harianModel->where('proyek_id', $proyekId)
            ->where('is_verified', 0)
            ->countAllResults();

        // 4. Hitung Kurva S & Progress Rencana vs Aktual Verified
        $jadwalItems = $this->jadwalModel->where('proyek_id', $proyekId)->findAll();
        $scurve = $this->calculateVerifiedScurve($proyek, $jadwalItems, $verifiedLogs, $evaluationDate);

        $progressRencana = $scurve['current_plan'];
        $progressAktualVerified = $scurve['current_actual'];
        $deviasi = round($progressAktualVerified - $progressRencana, 2);

        // Status Deviasi / Proyek
        if ($deviasi >= 0) {
            $statusProyek = 'Sesuai Jadwal (Deviasi Positif)';
        } elseif ($deviasi >= -5.0) {
            $statusProyek = 'Terlambat Ringan (Deviasi Minor)';
        } elseif ($deviasi >= -10.0) {
            $statusProyek = 'Terlambat Sedang (Perlu Perhatian)';
        } else {
            $statusProyek = 'Kritis / Terlambat Berat (Potensi Kontrak Kritis / SCM)';
        }

        // 5. Source / Reference Documents (monev_dokumen + file kontrak proyek)
        $docList = $this->dokumenModel->where('proyek_id', $proyekId)->orderBy('uploaded_at', 'DESC')->findAll();
        $referenceDocuments = [];
        if (!empty($proyek['file_kontrak'])) {
            $referenceDocuments[] = [
                'nama_dokumen' => 'Dokumen Kontrak Utama',
                'kategori'     => 'Kontrak',
                'file_path'    => $proyek['file_kontrak'],
                'uploaded_at'  => $proyek['created_at'],
                'source'       => 'monev_proyek',
            ];
        }
        if (!empty($proyek['dokumen_mc'])) {
            $referenceDocuments[] = [
                'nama_dokumen' => 'Dokumen Mutual Check (MC)',
                'kategori'     => 'Teknis',
                'file_path'    => $proyek['dokumen_mc'],
                'uploaded_at'  => $proyek['created_at'],
                'source'       => 'monev_proyek',
            ];
        }
        foreach ($docList as $d) {
            $referenceDocuments[] = [
                'id'           => $d['id'],
                'nama_dokumen' => $d['nama_dokumen'],
                'kategori'     => $d['kategori'],
                'file_path'    => $d['file_path'],
                'uploaded_at'  => $d['uploaded_at'],
                'source'       => 'monev_dokumen',
            ];
        }

        // 6. Progress Evidence (foto dari monev_harian yang verified)
        $progressEvidences = [];
        foreach ($verifiedLogs as $v) {
            if (!empty($v['foto_awal'])) {
                $progressEvidences[] = [
                    'jenis'       => 'Foto Awal',
                    'tanggal'     => $v['tanggal'],
                    'file_path'   => $v['foto_awal'],
                    'keterangan'  => $v['keterangan'] ?? '-',
                    'is_verified' => 1,
                    'source'      => 'monev_harian',
                ];
            }
            if (!empty($v['foto_proses'])) {
                $progressEvidences[] = [
                    'jenis'       => 'Foto Proses',
                    'tanggal'     => $v['tanggal'],
                    'file_path'   => $v['foto_proses'],
                    'keterangan'  => $v['keterangan'] ?? '-',
                    'is_verified' => 1,
                    'source'      => 'monev_harian',
                ];
            }
            if (!empty($v['foto_hasil'])) {
                $progressEvidences[] = [
                    'jenis'       => 'Foto Hasil',
                    'tanggal'     => $v['tanggal'],
                    'file_path'   => $v['foto_hasil'],
                    'keterangan'  => $v['keterangan'] ?? '-',
                    'is_verified' => 1,
                    'source'      => 'monev_harian',
                ];
            }
        }

        // 7. System Insights & Derived Information untuk Indikator 3 & 4
        // INDIKATOR 3: Pelaksanaan pekerjaan sesuai jangka waktu pelaksanaan kontrak
        $timeInsight = $this->buildTimeScheduleInsight($totalDurationDays, $elapsedDays, $remainingDays, $isOverdue, $timePercentage);
        
        // INDIKATOR 4: Progress/prestasi pekerjaan sesuai jadwal Kurva S
        $scheduleInsight = $this->buildProgressScheduleInsight($progressRencana, $progressAktualVerified, $deviasi, $statusProyek);

        return [
            'contract_data' => [
                'proyek_id'          => (int) $proyek['id'],
                'nama_proyek'        => $proyek['nama_proyek'],
                'no_kontrak'         => $proyek['no_kontrak'],
                'nomor_kontrak'      => $proyek['no_kontrak'],
                'nilai_kontrak'      => (float) $proyek['nilai_kontrak'],
                'tgl_mulai'          => $tglMulai,
                'tgl_selesai'        => $tglSelesai,
                'no_spmk'            => $noSpmk,
                'waktu_penyelesaian' => $waktuPenyelesaian,
                'penyedia'           => $proyek['nama_penyedia'],
                'konsultan_pengawas' => $proyek['konsultan_pengawas'] ?? '-',
                'direksi_teknis'     => $proyek['direksi_teknis'] ?? '-',
                'pphp'               => $proyek['pphp'] ?? '-',
                'ppk'                => [
                    'user_id'      => $proyek['user_id'],
                    'nama_lengkap' => $proyek['ppk_nama_lengkap'] ?? ($proyek['ppk'] ?? '-'),
                    'email'        => $proyek['ppk_email'] ?? '-',
                    'no_hp'        => $proyek['ppk_no_hp'] ?? '-',
                ],
                'kd_rup'             => $proyek['kd_rup'],
                'lokasi'             => $proyek['lokasi'] ?? '-',
                'sumber_dana'        => $proyek['sumber_dana'] ?? '-',
            ],
            'timeline_metrics' => [
                'total_days'     => $totalDurationDays,
                'elapsed_days'   => $elapsedDays,
                'remaining_days' => $remainingDays,
                'is_overdue'     => $isOverdue,
                'time_pct'       => $timePercentage,
            ],
            'progress_metrics' => [
                'progress_rencana'         => $progressRencana,
                'progress_aktual_verified' => $progressAktualVerified,
                'deviasi'                  => $deviasi,
                'status_proyek'            => $statusProyek,
                'verified_logs_count'      => count($verifiedLogs),
                'unverified_logs_count'    => $unverifiedCount,
                'scurve'                   => $scurve,
            ],
            'reference_documents' => $referenceDocuments,
            'progress_evidences'  => $progressEvidences,
            'system_insights'     => [
                'indicator_3_time_schedule' => $timeInsight,
                'indicator_4_kurva_s'       => $scheduleInsight,
            ],
        ];
    }

    /**
     * Menghitung Kurva S berbasis HANYA data monev_harian yang verified (is_verified = 1).
     */
    protected function calculateVerifiedScurve(array $proyek, array $jadwalItems, array $verifiedLogs, string $evaluationDate): array
    {
        $tglMulai = strtotime($proyek['tgl_mulai'] ?? date('Y-m-d'));
        $tglSelesai = strtotime($proyek['tgl_selesai'] ?? date('Y-m-d'));
        if ($tglSelesai < $tglMulai) {
            $tglSelesai = $tglMulai + (30 * 86400);
        }

        $durationDays = max(1, ($tglSelesai - $tglMulai) / 86400 + 1);
        $totalWeeks = max(1, (int) ceil($durationDays / 7));

        // Kelompokkan verified logs per item_id
        $verifiedByItem = [];
        foreach ($verifiedLogs as $h) {
            $verifiedByItem[$h['item_id']][] = $h;
        }

        $labels = [];
        $planWeights = [];
        $actualWeights = [];
        $deviasiList = [];

        $evalTs = strtotime($evaluationDate);
        $currentPlan = 0.0;
        $currentActual = 0.0;

        for ($w = 1; $w <= $totalWeeks; $w++) {
            $weekEndTimestamp = min($tglSelesai, $tglMulai + ($w * 7 - 1) * 86400);
            $weekEndDate = date('Y-m-d', $weekEndTimestamp);
            $labels[] = "M{$w} (" . date('d/m', $weekEndTimestamp) . ")";

            // 1. Plan Weight
            $weekPlanSum = 0;
            foreach ($jadwalItems as $item) {
                $bobot = (float) ($item['bobot'] ?? 0);
                $targets = json_decode($item['target_mingguan'] ?? '[]', true);
                if (!is_array($targets)) $targets = [];

                $idx = $w - 1;
                $targetVal = $idx < count($targets) ? (float) $targets[$idx] : (!empty($targets) ? (float) end($targets) : 100.0);
                $weekPlanSum += $bobot * ($targetVal / 100);
            }
            $planVal = round($weekPlanSum, 2);
            $planWeights[] = $planVal;

            // 2. Actual Verified Weight
            $weekActualSum = 0;
            foreach ($jadwalItems as $item) {
                $bobot = (float) ($item['bobot'] ?? 0);
                $itemId = $item['id'];
                $highestVerified = 0.0;

                if (isset($verifiedByItem[$itemId])) {
                    foreach ($verifiedByItem[$itemId] as $h) {
                        if ($h['tanggal'] <= $weekEndDate) {
                            if ((float) $h['progres_persen'] > $highestVerified) {
                                $highestVerified = (float) $h['progres_persen'];
                            }
                        }
                    }
                }
                $weekActualSum += $bobot * ($highestVerified / 100);
            }
            $actVal = round($weekActualSum, 2);
            $actualWeights[] = $actVal;

            // 3. Deviasi
            $devVal = round($actVal - $planVal, 2);
            $deviasiList[] = $devVal;
        }

        // Hitung nilai current plan & current verified actual tepat pada evaluation date
        if ($evalTs >= $tglSelesai) {
            $currentPlan = !empty($planWeights) ? end($planWeights) : 100.0;
        } elseif ($evalTs < $tglMulai) {
            $currentPlan = 0.0;
        } else {
            // Temukan minggu yang mencakup evaluation date
            $currentPlan = !empty($planWeights) ? $planWeights[0] : 0.0;
            for ($w = 1; $w <= $totalWeeks; $w++) {
                $weekStartTimestamp = $tglMulai + (($w - 1) * 7 * 86400);
                $weekEndTimestamp = min($tglSelesai, $tglMulai + ($w * 7 - 1) * 86400);
                if ($evalTs >= $weekStartTimestamp && $evalTs <= $weekEndTimestamp) {
                    $currentPlan = $planWeights[$w - 1] ?? 0.0;
                    break;
                }
            }
        }

        // Current actual verified strictly takes highest verified log on or before evaluation date
        $currentActual = 0.0;
        foreach ($jadwalItems as $item) {
            $bobot = (float) ($item['bobot'] ?? 0);
            $itemId = $item['id'];
            $highestVerified = 0.0;

            if (isset($verifiedByItem[$itemId])) {
                foreach ($verifiedByItem[$itemId] as $h) {
                    if ($h['tanggal'] <= $evaluationDate) {
                        if ((float) $h['progres_persen'] > $highestVerified) {
                            $highestVerified = (float) $h['progres_persen'];
                        }
                    }
                }
            }
            $currentActual += $bobot * ($highestVerified / 100);
        }
        $currentActual = round($currentActual, 2);

        return [
            'labels'         => $labels,
            'plan'           => $planWeights,
            'actual'         => $actualWeights,
            'deviasi'        => $deviasiList,
            'current_plan'   => $currentPlan,
            'current_actual' => $currentActual,
        ];
    }

    /**
     * Membangun contextual system information & insight untuk Indikator 3:
     * "Pelaksanaan pekerjaan sesuai jangka waktu pelaksanaan yang ditetapkan dalam kontrak"
     */
    protected function buildTimeScheduleInsight(int $totalDays, int $elapsedDays, int $remainingDays, bool $isOverdue, float $timePct): array
    {
        if ($isOverdue) {
            $absOverdue = abs($remainingDays);
            $insightText = "Pekerjaan telah melewati batas waktu kalender kontrak selama {$absOverdue} hari ({$timePct}% waktu terlampaui). Periksa adendum waktu atau surat teguran keterlambatan.";
            $suggestedRating = MonevAssessmentService::RATING_TIDAK_BAIK;
            $suggestedScore = 1;
        } elseif ($remainingDays <= 14 && $timePct >= 80) {
            $insightText = "Masa kontrak tersisa kritis: {$remainingDays} hari ({$timePct}% waktu telah berjalan). Penyedia harus memaksimalkan seluruh sumber daya untuk serah terima tepat waktu.";
            $suggestedRating = MonevAssessmentService::RATING_BAIK;
            $suggestedScore = 2;
        } else {
            $insightText = "Masa kontrak tersisa {$remainingDays} hari dari total {$totalDays} hari kalender ({$timePct}% waktu telah berjalan). Waktu kalender masih berada dalam koridor jadwal normal.";
            $suggestedRating = MonevAssessmentService::RATING_SANGAT_BAIK;
            $suggestedScore = 3;
        }

        return [
            'indicator_code'       => 'IND-03',
            'contextual_data'      => [
                'total_contract_days' => $totalDays,
                'elapsed_days'        => $elapsedDays,
                'remaining_days'      => $remainingDays,
                'is_overdue'          => $isOverdue,
                'time_percentage'     => $timePct,
            ],
            'system_insight'       => $insightText,
            'suggested_rating'     => $suggestedRating,
            'suggested_score'      => $suggestedScore,
            'is_rating_automatic'  => false, // Operator tetap yang berwenang memilih rating resmi
            'disclaimer'           => 'Rekomendasi sistem bersifat informasi pendukung. Keputusan rating resmi sepenuhnya berada pada Assessor/Operator.',
        ];
    }

    /**
     * Membangun contextual system information & insight untuk Indikator 4:
     * "Progress/prestasi pekerjaan sesuai jadwal dan tidak ada keterlambatan berdasarkan Time Schedule/Kurva S"
     */
    protected function buildProgressScheduleInsight(float $plan, float $actualVerified, float $deviasi, string $status): array
    {
        if ($deviasi >= 0) {
            $insightText = "Progres aktual tervalidasi ({$actualVerified}%) memenuhi atau melampaui target Kurva S ({$plan}%). Deviasi positif +{$deviasi}%. Pekerjaan berjalan lancar sesuai rencana.";
            $suggestedRating = MonevAssessmentService::RATING_SANGAT_BAIK;
            $suggestedScore = 3;
        } elseif ($deviasi >= -5.0) {
            $absDev = abs($deviasi);
            $insightText = "Terjadi deviasi keterlambatan minor sebesar -{$absDev}% (Rencana: {$plan}%, Aktual Verified: {$actualVerified}%). Masih dalam batas toleransi (0 s/d -5%), diperlukan akselerasi ringan.";
            $suggestedRating = MonevAssessmentService::RATING_BAIK;
            $suggestedScore = 2;
        } else {
            $absDev = abs($deviasi);
            $insightText = "PERHATIAN: Deviasi keterlambatan signifikan sebesar -{$absDev}% (Rencana: {$plan}%, Aktual Verified: {$actualVerified}%). Deviasi melebihi -5% berpotensi kontrak kritis (SCM). Disarankan mencatat temuan (Finding).";
            $suggestedRating = MonevAssessmentService::RATING_TIDAK_BAIK;
            $suggestedScore = 1;
        }

        return [
            'indicator_code'       => 'IND-04',
            'contextual_data'      => [
                'plan_percent'            => $plan,
                'actual_verified_percent' => $actualVerified,
                'deviasi'                 => $deviasi,
                'status_proyek'           => $status,
            ],
            'system_insight'       => $insightText,
            'suggested_rating'     => $suggestedRating,
            'suggested_score'      => $suggestedScore,
            'is_rating_automatic'  => false, // Operator tetap yang berwenang memilih rating resmi
            'disclaimer'           => 'Rekomendasi sistem bersifat informasi pendukung. Keputusan rating resmi sepenuhnya berada pada Assessor/Operator.',
        ];
    }
}
