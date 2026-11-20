<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?>

<?= $this->section('content') ?>
<!-- ApexCharts CSS & JS -->
<link rel="stylesheet" href="<?= base_url('assets/vendor/libs/apex-charts/apex-charts.css') ?>" />
<script src="<?= base_url('assets/vendor/libs/apex-charts/apexcharts.js') ?>"></script>

<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Breadcrumb & Top Bar -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-3 gap-2">
        <div>
            <h4 class="fw-bold py-1 mb-0">
                <span class="text-muted fw-light">
                    <a href="<?= base_url('dashboard/monev') ?>" class="text-muted">Monev</a> /
                </span> 
                Workspace Proyek
            </h4>
            <p class="text-muted small mb-0"><?= esc($proyek['nama_proyek']) ?> &bull; No: <span class="font-monospace fw-semibold"><?= esc($proyek['no_kontrak']) ?></span></p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <?php if ($activePeriod): ?>
                <span class="badge bg-label-primary fs-6 font-monospace">
                    <?= esc($activePeriod['nomor_monev']) ?> (Periode #<?= $activePeriod['periode_ke'] ?>)
                </span>
                <?php if ((int) $activePeriod['is_locked'] === 1): ?>
                    <span class="badge bg-success d-flex align-items-center gap-1">
                        <i class="bx bx-lock-alt"></i> IMMUTABLE (LOCKED)
                    </span>
                    <?php if (session()->get('role') === 'admin'): ?>
                        <button type="button" class="btn btn-xs btn-outline-warning d-flex align-items-center gap-1 shadow-sm fw-bold" onclick="reopenForRevision(<?= $activePeriod['id'] ?>)">
                            <i class="bx bx-revision"></i> Buka Kunci / Revisi Monev
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="badge bg-label-warning d-flex align-items-center gap-1">
                        <i class="bx bx-edit"></i> <?= $activePeriod['status'] ?>
                    </span>
                <?php endif; ?>
            <?php else: ?>
                <span class="badge bg-label-secondary">Belum Ada Sesi Monev</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Project Header Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-md-8">
                    <div class="d-flex align-items-start gap-3">
                        <div class="avatar avatar-lg flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="bx bx-buildings fs-2"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1"><?= esc($proyek['nama_proyek']) ?></h5>
                            <div class="d-flex flex-wrap gap-2 text-muted small">
                                <span><i class="bx bx-user me-1"></i>Penyedia: <strong class="text-dark"><?= esc($proyek['nama_penyedia'] ?? '-') ?></strong></span>
                                <span>&bull;</span>
                                <span><i class="bx bx-money me-1"></i>Nilai: <strong class="text-dark">Rp <?= number_format((float) $proyek['nilai_kontrak'], 0, ',', '.') ?></strong></span>
                                <span>&bull;</span>
                                <span><i class="bx bx-calendar me-1"></i>Waktu: <strong class="text-dark"><?= date('d/m/Y', strtotime($proyek['tgl_mulai'])) ?> s/d <?= date('d/m/Y', strtotime($proyek['tgl_selesai'])) ?></strong></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-flex flex-column align-items-md-end gap-1">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown">
                                <i class="bx bx-history"></i> Pilih Periode Sesi: 
                                <strong><?= $activePeriod ? "Periode #{$activePeriod['periode_ke']}" : 'Pilih' ?></strong>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <?php if (!empty($periods)): ?>
                                    <?php foreach ($periods as $per): ?>
                                        <li>
                                            <a class="dropdown-item <?= ($activePeriod && $activePeriod['id'] == $per['id']) ? 'active' : '' ?>" href="<?= base_url('dashboard/monev/project/' . $proyek['id'] . '/' . $per['id']) ?>">
                                                Periode Ke-<?= $per['periode_ke'] ?> (<?= esc($per['nomor_monev']) ?>) &bull; <?= $per['status'] ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <?php if (in_array(session()->get('role'), ['admin', 'operator'])): ?>
                                    <li>
                                        <a class="dropdown-item text-primary fw-bold" href="javascript:void(0)" onclick="startNewPeriod(<?= $proyek['id'] ?>)">
                                            <i class="bx bx-plus-circle me-1"></i> Mulai Periode Baru
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigasi 9 Tabs Sneat -->
    <div class="nav-align-top mb-4">
        <ul class="nav nav-pills flex-column flex-md-row gap-2 bg-white p-2 rounded shadow-sm border" role="tablist">
            <li class="nav-item">
                <button type="button" class="nav-link <?= $activeTab === 'overview' ? 'active' : '' ?>" role="tab" data-bs-toggle="tab" data-bs-target="#tab-overview">
                    <i class="bx bx-tachometer me-1"></i> Overview
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link <?= $activeTab === 'kontrak' ? 'active' : '' ?>" role="tab" data-bs-toggle="tab" data-bs-target="#tab-kontrak">
                    <i class="bx bx-file-blank me-1"></i> Kontrak
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link <?= $activeTab === 'scurve' ? 'active' : '' ?>" role="tab" data-bs-toggle="tab" data-bs-target="#tab-scurve">
                    <i class="bx bx-line-chart me-1"></i> Progress & Kurva S
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link <?= $activeTab === 'monev' ? 'active' : '' ?>" role="tab" data-bs-toggle="tab" data-bs-target="#tab-monev">
                    <i class="bx bx-calendar-event me-1"></i> Monev
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link <?= $activeTab === 'assessment' ? 'active' : '' ?>" role="tab" data-bs-toggle="tab" data-bs-target="#tab-assessment">
                    <i class="bx bx-edit me-1"></i> Assessment
                    <?php if ($ratedCount < 12): ?>
                        <span class="badge rounded-pill bg-warning text-dark ms-1" style="font-size: 0.7rem;"><?= $ratedCount ?>/12</span>
                    <?php else: ?>
                        <span class="badge rounded-pill bg-success ms-1" style="font-size: 0.7rem;">12/12</span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link <?= $activeTab === 'findings' ? 'active' : '' ?>" role="tab" data-bs-toggle="tab" data-bs-target="#tab-findings">
                    <i class="bx bx-error-alt me-1"></i> Findings
                    <?php if (!empty($findingsWithDetails)): ?>
                        <span class="badge rounded-pill bg-danger ms-1" style="font-size: 0.7rem;"><?= count($findingsWithDetails) ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link <?= $activeTab === 'evidence' ? 'active' : '' ?>" role="tab" data-bs-toggle="tab" data-bs-target="#tab-evidence">
                    <i class="bx bx-images me-1"></i> Evidence
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link <?= $activeTab === 'documents' ? 'active' : '' ?>" role="tab" data-bs-toggle="tab" data-bs-target="#tab-documents">
                    <i class="bx bx-folder me-1"></i> Documents
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link <?= $activeTab === 'history' ? 'active' : '' ?>" role="tab" data-bs-toggle="tab" data-bs-target="#tab-history">
                    <i class="bx bx-history me-1"></i> History
                </button>
            </li>
        </ul>

        <!-- Tab Content Panes -->
        <div class="tab-content p-0 border-0 shadow-none">
            
            <!-- ═════════════════════════════════════════════════════════════ -->
            <!-- TAB 1: OVERVIEW -->
            <!-- ═════════════════════════════════════════════════════════════ -->
            <div class="tab-pane fade <?= $activeTab === 'overview' ? 'show active' : '' ?>" id="tab-overview" role="tabpanel">
                <div class="row g-3 mb-4">
                    <!-- KPI Cards -->
                    <div class="col-6 col-lg-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <span class="text-muted small d-block mb-1">Skor Kinerja Penilaian</span>
                                <?php if ($activePeriod && !empty($activePeriod['skor_kinerja_total'])): ?>
                                    <h3 class="fw-bold text-primary mb-0"><?= number_format((float) $activePeriod['skor_kinerja_total'], 1) ?> <span class="fs-6 text-muted">/ 36</span></h3>
                                    <small class="text-success fw-semibold"><?= number_format(((float) $activePeriod['skor_kinerja_total'] / 36) * 100, 1) ?>% dari skor maksimal</small>
                                <?php else: ?>
                                    <h3 class="fw-bold text-muted mb-0">-</h3>
                                    <small class="text-muted">Belum ada evaluasi</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <span class="text-muted small d-block mb-1">Progres Aktual Verified</span>
                                <h3 class="fw-bold text-dark mb-0"><?= number_format((float) $context['progress_metrics']['progress_aktual_verified'], 2) ?>%</h3>
                                <small class="text-muted">Rencana: <?= number_format((float) $context['progress_metrics']['progress_rencana'], 2) ?>%</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <span class="text-muted small d-block mb-1">Deviasi Kurva S</span>
                                <?php $dev = (float) $context['progress_metrics']['deviasi']; ?>
                                <h3 class="fw-bold mb-0 <?= $dev >= 0 ? 'text-success' : ($dev >= -5.0 ? 'text-warning' : 'text-danger') ?>">
                                    <?= ($dev >= 0 ? '+' : '') . number_format($dev, 2) ?>%
                                </h3>
                                <small class="badge bg-label-<?= $dev >= 0 ? 'success' : ($dev >= -5.0 ? 'warning' : 'danger') ?>">
                                    <?= esc($context['progress_metrics']['status_proyek']) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <span class="text-muted small d-block mb-1">Umur & Sisa Waktu Kontrak</span>
                                <?php $tl = $context['timeline_metrics']; ?>
                                <h3 class="fw-bold mb-0 <?= $tl['is_overdue'] ? 'text-danger' : 'text-dark' ?>">
                                    <?= $tl['is_overdue'] ? 'Lewat ' . abs($tl['remaining_days']) . ' hr' : $tl['remaining_days'] . ' Hari' ?>
                                </h3>
                                <small class="text-muted">Terpakai: <?= $tl['elapsed_days'] ?> dari <?= $tl['total_days'] ?> hari (<?= $tl['time_pct'] ?>%)</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ringkasan & Kesimpulan Evaluasi -->
                <div class="row g-3">
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-white border-bottom py-3">
                                <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                    <i class="bx bx-notepad text-primary"></i> Kesimpulan & Rekomendasi Hasil Monev
                                </h6>
                            </div>
                            <div class="card-body p-4">
                                <?php if ($activePeriod && !empty($activePeriod['kesimpulan_rekomendasi'])): ?>
                                    <div class="p-3 bg-light rounded border mb-3">
                                        <h6 class="fw-bold text-dark mb-1">Kesimpulan Assessor:</h6>
                                        <p class="mb-0 text-dark"><?= nl2br(esc($activePeriod['kesimpulan_rekomendasi'])) ?></p>
                                    </div>
                                    <?php if (!empty($activePeriod['ringkasan_hasil'])): ?>
                                        <div class="p-3 bg-light rounded border">
                                            <h6 class="fw-bold text-dark mb-1">Catatan Ringkasan:</h6>
                                            <p class="mb-0 text-muted"><?= nl2br(esc($activePeriod['ringkasan_hasil'])) ?></p>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="text-center py-4 text-muted">
                                        <i class="bx bx-comment-detail fs-1 opacity-50 mb-2"></i>
                                        <p class="mb-0">Belum ada kesimpulan hasil Monev yang diinput. Silakan isi pada tab <strong>Assessment</strong>.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Final Documents Section -->
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-white border-bottom py-3">
                                <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                    <i class="bx bxs-file-pdf text-danger"></i> Dokumen Final Laporan Monev
                                </h6>
                            </div>
                            <div class="card-body p-4">
                                <?php if (empty($finalDocuments)): ?>
                                    <div class="text-center py-4 text-muted">
                                        <i class="bx bx-file-blank fs-1 opacity-50 mb-2"></i>
                                        <p class="mb-0">Belum ada dokumen final yang digenerate untuk sesi ini.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Versi</th>
                                                    <th>Nomor Laporan</th>
                                                    <th>Waktu Finalisasi</th>
                                                    <th class="text-center">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($finalDocuments as $fd): ?>
                                                    <tr>
                                                        <td>
                                                            <span class="badge bg-<?= $fd['version'] === count($finalDocuments) ? 'success' : 'secondary' ?>">
                                                                V<?= $fd['version'] ?> <?= $fd['version'] === count($finalDocuments) ? '(Terbaru)' : '' ?>
                                                            </span>
                                                        </td>
                                                        <td class="font-monospace fw-semibold text-primary"><?= esc($fd['document_number']) ?></td>
                                                        <td><?= date('d/m/Y H:i', strtotime($fd['created_at'])) ?></td>
                                                        <td class="text-center">
                                                            <a href="<?= base_url($fd['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                                                <i class="bx bxs-file-pdf me-1"></i> Buka
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-white border-bottom py-3">
                                <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                    <i class="bx bx-shield-quarter text-info"></i> Status & Alur Monev
                                </h6>
                            </div>
                            <div class="card-body p-4">
                                <?php if ($activePeriod): ?>
                                    <div class="mb-3">
                                        <label class="text-muted small d-block">Status Periode Ini:</label>
                                        <span class="badge bg-primary fs-6 mt-1"><?= $activePeriod['status'] ?></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="text-muted small d-block">Petugas Pelaksana (Assessor):</label>
                                        <strong class="text-dark"><?= esc($activePeriod['nama_assessor'] ?? '-') ?></strong>
                                    </div>
                                    <div class="mb-3">
                                        <label class="text-muted small d-block">Tanggal Rencana Monev:</label>
                                        <strong class="text-dark"><?= date('d F Y', strtotime($activePeriod['tgl_rencana_monev'])) ?></strong>
                                    </div>
                                    <div class="mb-3">
                                        <label class="text-muted small d-block">Kesiapan Finalisasi (Readiness):</label>
                                        <?php if ($readiness && $readiness['is_ready']): ?>
                                            <span class="badge bg-label-success mt-1"><i class="bx bx-check"></i> Siap Difinalisasi</span>
                                        <?php else: ?>
                                            <span class="badge bg-label-warning mt-1"><i class="bx bx-error"></i> Belum Siap Finalisasi</span>
                                            <?php if ($readiness && !empty($readiness['blockers'])): ?>
                                                <ul class="text-danger small ps-3 mt-2 mb-0">
                                                    <?php foreach ($readiness['blockers'] as $blk): ?>
                                                        <li><?= esc($blk) ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($activePeriod['status'] === 'APPROVED' && session()->get('role') === 'admin'): ?>
                                        <button class="btn btn-success btn-sm w-100 mt-2" onclick="finalizeMonev(<?= $activePeriod['id'] ?>)">
                                            <i class="bx bx-lock-alt me-1"></i> Finalisasi Monev (Kunci Permanen)
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═════════════════════════════════════════════════════════════ -->
            <!-- TAB 2: KONTRAK (Zero Duplicate Input) -->
            <!-- ═════════════════════════════════════════════════════════════ -->
            <div class="tab-pane fade <?= $activeTab === 'kontrak' ? 'show active' : '' ?>" id="tab-kontrak" role="tabpanel">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="bx bx-file text-primary"></i> Data Kontrak & Stakeholder (Existing Integration)
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <h6 class="text-primary fw-bold border-bottom pb-2 mb-3">Informasi Utama Kontrak</h6>
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td class="text-muted" style="width: 180px;">Nama Proyek</td>
                                        <td class="fw-bold text-dark">: <?= esc($context['contract_data']['nama_proyek']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Nomor Kontrak</td>
                                        <td class="fw-bold font-monospace text-dark">: <?= esc($context['contract_data']['no_kontrak']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Nilai Kontrak</td>
                                        <td class="fw-bold text-success">: Rp <?= number_format((float) $context['contract_data']['nilai_kontrak'], 0, ',', '.') ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Kode RUP</td>
                                        <td class="font-monospace text-dark">: <?= esc($context['contract_data']['kd_rup'] ?? '-') ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">No. SPMK / SPP</td>
                                        <td class="font-monospace text-dark">: <?= esc($context['contract_data']['no_spmk'] ?? '-') ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Tanggal Mulai</td>
                                        <td class="text-dark">: <?= date('d F Y', strtotime($context['contract_data']['tgl_mulai'])) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Tanggal Selesai</td>
                                        <td class="text-dark">: <?= date('d F Y', strtotime($context['contract_data']['tgl_selesai'])) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Sumber Dana & Lokasi</td>
                                        <td class="text-dark">: <?= esc($context['contract_data']['sumber_dana'] ?? '-') ?> &bull; <?= esc($context['contract_data']['lokasi'] ?? '-') ?></td>
                                    </tr>
                                </table>
                            </div>

                            <div class="col-md-6">
                                <h6 class="text-primary fw-bold border-bottom pb-2 mb-3">Pihak & Stakeholder Terlibat</h6>
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td class="text-muted" style="width: 180px;">Penyedia Jasa (Kontraktor)</td>
                                        <td class="fw-bold text-dark">: <?= esc($context['contract_data']['penyedia']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">PPK (Pejabat Pembuat Komitmen)</td>
                                        <td class="text-dark">: 
                                            <strong><?= esc($context['contract_data']['ppk']['nama_lengkap'] ?? '-') ?></strong>
                                            <?php if (!empty($context['contract_data']['ppk']['email'])): ?>
                                                <br><small class="text-muted ms-2">(<?= esc($context['contract_data']['ppk']['email']) ?>)</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Konsultan Pengawas / Supervisi</td>
                                        <td class="text-dark">: <?= esc($context['contract_data']['konsultan_pengawas'] ?? '-') ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Direksi Teknis</td>
                                        <td class="text-dark">: <?= esc($context['contract_data']['direksi_teknis'] ?? '-') ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Tim PPHP</td>
                                        <td class="text-dark">: <?= esc($context['contract_data']['pphp'] ?? '-') ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═════════════════════════════════════════════════════════════ -->
            <!-- TAB 3: PROGRESS & KURVA S -->
            <!-- ═════════════════════════════════════════════════════════════ -->
            <div class="tab-pane fade <?= $activeTab === 'scurve' ? 'show active' : '' ?>" id="tab-scurve" role="tabpanel">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="bx bx-line-chart text-primary"></i> Kurva S: Progres Rencana vs Aktual Verified
                        </h5>
                        <span class="badge bg-label-primary">
                            Hanya Log Harian Verified (is_verified = 1)
                        </span>
                    </div>
                    <div class="card-body p-4">
                        <div id="scurveChart" style="min-height: 350px;"></div>
                    </div>
                </div>

                <!-- Tabel Breakdown Mingguan -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="fw-bold text-dark mb-0">Tabel Rincian Kumulatif Kurva S Mingguan</h6>
                    </div>
                    <div class="table-responsive text-nowrap">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Minggu Evaluasi</th>
                                    <th class="text-center">Target Rencana (%)</th>
                                    <th class="text-center">Aktual Verified (%)</th>
                                    <th class="text-center">Deviasi (%)</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sc = $context['progress_metrics']['scurve'];
                                if (!empty($sc['labels'])):
                                    foreach ($sc['labels'] as $idx => $lbl):
                                        $pVal = $sc['plan'][$idx] ?? 0;
                                        $aVal = $sc['actual'][$idx] ?? 0;
                                        $dVal = $sc['deviasi'][$idx] ?? 0;
                                ?>
                                        <tr>
                                            <td class="fw-semibold text-dark"><?= esc($lbl) ?></td>
                                            <td class="text-center"><?= number_format((float) $pVal, 2) ?>%</td>
                                            <td class="text-center fw-bold text-primary"><?= number_format((float) $aVal, 2) ?>%</td>
                                            <td class="text-center">
                                                <span class="badge bg-label-<?= $dVal >= 0 ? 'success' : ($dVal >= -5 ? 'warning' : 'danger') ?>">
                                                    <?= ($dVal >= 0 ? '+' : '') . number_format((float) $dVal, 2) ?>%
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?= $dVal >= 0 ? '🟢 Sesuai' : ($dVal >= -5 ? '🟡 Deviasi Ringan' : '🔴 Kritis') ?>
                                            </td>
                                        </tr>
                                <?php
                                    endforeach;
                                endif;
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ═════════════════════════════════════════════════════════════ -->
            <!-- TAB 4: MONEV (Periode & Sesi) -->
            <!-- ═════════════════════════════════════════════════════════════ -->
            <div class="tab-pane fade <?= $activeTab === 'monev' ? 'show active' : '' ?>" id="tab-monev" role="tabpanel">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="bx bx-calendar-check text-primary"></i> Daftar Periode Monev Proyek Ini
                        </h5>
                        <?php if (in_array(session()->get('role'), ['admin', 'operator'])): ?>
                            <button class="btn btn-sm btn-primary d-flex align-items-center gap-1" onclick="startNewPeriod(<?= $proyek['id'] ?>)">
                                <i class="bx bx-plus-circle"></i> Mulai Periode Baru
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="table-responsive text-nowrap">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Periode Ke</th>
                                    <th>Nomor Monev</th>
                                    <th>Tgl. Rencana</th>
                                    <th>Assessor</th>
                                    <th>Status</th>
                                    <th>Skor Kinerja</th>
                                    <th>Kunci (Immutable)</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($periods)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">Belum ada sesi Monev untuk proyek ini.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($periods as $per): ?>
                                        <tr class="<?= ($activePeriod && $activePeriod['id'] == $per['id']) ? 'table-active' : '' ?>">
                                            <td class="fw-bold text-dark">Periode #<?= $per['periode_ke'] ?></td>
                                            <td class="font-monospace text-primary"><?= esc($per['nomor_monev']) ?></td>
                                            <td><?= date('d/m/Y', strtotime($per['tgl_rencana_monev'])) ?></td>
                                            <td><?= esc($per['nama_assessor'] ?? '-') ?></td>
                                            <td><span class="badge bg-label-primary"><?= $per['status'] ?></span></td>
                                            <td class="fw-bold"><?= !empty($per['skor_kinerja_total']) ? number_format((float) $per['skor_kinerja_total'], 1) . ' / 36' : '-' ?></td>
                                            <td>
                                                <?= (int) $per['is_locked'] === 1 ? '<span class="badge bg-success">Terkunci (Final)</span>' : '<span class="badge bg-label-secondary">Terbuka</span>' ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="<?= base_url('dashboard/monev/project/' . $proyek['id'] . '/' . $per['id']) ?>" class="btn btn-sm btn-outline-primary">
                                                    Pilih Sesi
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ═════════════════════════════════════════════════════════════ -->
            <!-- TAB 5: ASSESSMENT (6 Aspek, 12 Indikator, Accordion & Rating) -->
            <!-- ═════════════════════════════════════════════════════════════ -->
            <div class="tab-pane fade <?= $activeTab === 'assessment' ? 'show active' : '' ?>" id="tab-assessment" role="tabpanel">
                <?php if (!$activePeriod): ?>
                    <div class="card border-0 shadow-sm py-5 text-center">
                        <div class="card-body">
                            <i class="bx bx-calendar-exclamation text-warning mb-3" style="font-size: 4rem;"></i>
                            <h5 class="fw-bold text-dark">Belum Ada Sesi Monev Aktif</h5>
                            <p class="text-muted small mb-3">Silakan mulai sesi Monev terlebih dahulu untuk mengisi penilaian 6 Aspek & 12 Indikator.</p>
                            <button class="btn btn-primary" onclick="startNewPeriod(<?= $proyek['id'] ?>)">Mulai Monev Sekarang</button>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Progress Bar & Indicator Status Header -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-2">
                                <div>
                                    <h6 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                        <i class="bx bx-check-circle text-primary"></i> Progress Pengisian Evaluasi: 
                                        <span class="text-primary font-monospace"><?= $ratedCount ?> / 12 Selesai</span>
                                    </h6>
                                    <p class="text-muted small mb-0">Evaluasi standar kinerja penyedia jasa konstruksi berdasarkan 6 aspek teknis dan manajerial.</p>
                                </div>
                                <div>
                                    <?php if ($ratedCount >= 12): ?>
                                        <span class="badge bg-success py-2 px-3 fs-6 d-flex align-items-center gap-1">
                                            <i class="bx bx-badge-check"></i> Siap Disubmit
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-label-warning py-2 px-3 fs-6 d-flex align-items-center gap-1">
                                            <i class="bx bx-time"></i> <?= 12 - $ratedCount ?> Indikator Belum Dinilai
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar <?= $ratedCount >= 12 ? 'bg-success' : 'bg-primary' ?> progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?= ($ratedCount / 12) * 100 ?>%"></div>
                            </div>

                            <!-- Warning banner jika ada indikator yang belum dinilai -->
                            <?php if (!empty($unratedIndicators)): ?>
                                <div class="alert alert-warning border-0 mt-3 mb-0 py-2 px-3 small d-flex align-items-start gap-2">
                                    <i class="bx bx-error fs-5 flex-shrink-0 mt-1"></i>
                                    <div>
                                        <strong>Indikator Mandatory yang Belum Lengkap:</strong>
                                        <ul class="mb-0 ps-3 mt-1">
                                            <?php foreach ($unratedIndicators as $un): ?>
                                                <li>[<?= esc($un['kode']) ?>] <?= esc($un['nama']) ?> (<em><?= esc($un['aspek']) ?></em>)</li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Form Penilaian 6 Aspek -->
                    <form id="formAssessment" method="POST">
                        <input type="hidden" name="period_id" value="<?= $activePeriod['id'] ?>">

                        <!-- 6 Accordion Aspek -->
                        <div class="accordion mb-4" id="accordionAspects">
                            <?php
                            $aspectList = $template['aspects'] ?? [];
                            $savedItemsByInd = [];
                            if (!empty($activePeriod['assessment']['items'])) {
                                foreach ($activePeriod['assessment']['items'] as $it) {
                                    $savedItemsByInd[$it['indicator_id']] = $it;
                                }
                            }

                            foreach ($aspectList as $aspIdx => $asp):
                                $aspNum = $aspIdx + 1;
                            ?>
                                <div class="card accordion-item border-0 shadow-sm mb-3">
                                    <h2 class="accordion-header" id="headingAsp<?= $aspNum ?>">
                                        <button type="button" class="accordion-button fw-bold text-dark py-3 <?= $aspIdx > 0 ? 'collapsed' : '' ?>" data-bs-toggle="collapse" data-bs-target="#collapseAsp<?= $aspNum ?>" aria-expanded="<?= $aspIdx === 0 ? 'true' : 'false' ?>">
                                            <span class="badge bg-label-primary me-2">ASPEK <?= $aspNum ?></span>
                                            <?= esc($asp['nama_aspek']) ?> 
                                            <span class="text-muted small ms-2">(Bobot: <?= $asp['bobot_persen'] ?>%)</span>
                                        </button>
                                    </h2>
                                    <div id="collapseAsp<?= $aspNum ?>" class="accordion-collapse collapse <?= $aspIdx === 0 ? 'show' : '' ?>" data-bs-parent="#accordionAspects">
                                        <div class="accordion-body p-4 bg-white">
                                            
                                            <?php foreach ($asp['indicators'] as $indIdx => $ind): 
                                                $saved = $savedItemsByInd[$ind['id']] ?? null;
                                                $currentRating = $saved['rating'] ?? '';
                                                $currentScore = $saved['score'] ?? 0;
                                                $currentNote = $saved['catatan_penilai'] ?? '';
                                                $indCode = $ind['kode_indikator'];
                                            ?>
                                                <div class="p-3 mb-3 rounded border <?= empty($currentRating) ? 'border-warning bg-light' : 'border-light bg-white' ?>">
                                                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2 mb-2">
                                                        <div>
                                                            <span class="badge bg-secondary font-monospace"><?= esc($indCode) ?></span>
                                                            <strong class="text-dark ms-1"><?= esc($ind['nama_indikator']) ?></strong>
                                                        </div>
                                                    </div>

                                                    <!-- System Insight untuk Indikator 3 & 4 -->
                                                    <?php if ($indCode === 'IND-03' && !empty($context['system_insights']['indicator_3_time_schedule'])): 
                                                        $ins3 = $context['system_insights']['indicator_3_time_schedule'];
                                                    ?>
                                                        <div class="alert alert-info py-2 px-3 mb-2 small border-0 d-flex align-items-start gap-2">
                                                            <i class="bx bx-bulb fs-5 text-primary flex-shrink-0 mt-1"></i>
                                                            <div>
                                                                <strong>System Insight (Jangka Waktu Kontrak):</strong> <?= esc($ins3['system_insight']) ?>
                                                                <br><em class="text-muted small"><?= esc($ins3['disclaimer']) ?></em>
                                                            </div>
                                                        </div>
                                                    <?php elseif ($indCode === 'IND-04' && !empty($context['system_insights']['indicator_4_kurva_s'])): 
                                                        $ins4 = $context['system_insights']['indicator_4_kurva_s'];
                                                    ?>
                                                        <div class="alert alert-info py-2 px-3 mb-2 small border-0 d-flex align-items-start gap-2">
                                                            <i class="bx bx-bulb fs-5 text-primary flex-shrink-0 mt-1"></i>
                                                            <div>
                                                                <strong>System Insight (Kurva S):</strong> <?= esc($ins4['system_insight']) ?>
                                                                <br><em class="text-muted small"><?= esc($ins4['disclaimer']) ?></em>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Rating Buttons -->
                                                    <div class="mb-3 mt-2">
                                                        <label class="form-label small fw-semibold text-muted d-block">Pilihan Penilaian Resmi:</label>
                                                        <div class="btn-group w-100 w-md-auto" role="group">
                                                            <input type="radio" class="btn-check rating-radio" name="rating_<?= $ind['id'] ?>" id="rating_<?= $ind['id'] ?>_1" value="TIDAK_BAIK" data-score="1" data-indicator="<?= $ind['id'] ?>" <?= $currentRating === 'TIDAK_BAIK' ? 'checked' : '' ?> <?= (int) $activePeriod['is_locked'] === 1 ? 'disabled' : '' ?>>
                                                            <label class="btn btn-outline-danger btn-sm px-3" for="rating_<?= $ind['id'] ?>_1">
                                                                <i class="bx bx-dislike"></i> Tidak Baik (1)
                                                            </label>

                                                            <input type="radio" class="btn-check rating-radio" name="rating_<?= $ind['id'] ?>" id="rating_<?= $ind['id'] ?>_2" value="BAIK" data-score="2" data-indicator="<?= $ind['id'] ?>" <?= $currentRating === 'BAIK' ? 'checked' : '' ?> <?= (int) $activePeriod['is_locked'] === 1 ? 'disabled' : '' ?>>
                                                            <label class="btn btn-outline-warning btn-sm px-3" for="rating_<?= $ind['id'] ?>_2">
                                                                <i class="bx bx-check"></i> Baik (2)
                                                            </label>

                                                            <input type="radio" class="btn-check rating-radio" name="rating_<?= $ind['id'] ?>" id="rating_<?= $ind['id'] ?>_3" value="SANGAT_BAIK" data-score="3" data-indicator="<?= $ind['id'] ?>" <?= $currentRating === 'SANGAT_BAIK' ? 'checked' : '' ?> <?= (int) $activePeriod['is_locked'] === 1 ? 'disabled' : '' ?>>
                                                            <label class="btn btn-outline-success btn-sm px-3" for="rating_<?= $ind['id'] ?>_3">
                                                                <i class="bx bx-like"></i> Sangat Baik (3)
                                                            </label>
                                                        </div>
                                                    </div>

                                                    <!-- Keterangan Textarea -->
                                                    <div class="mb-2">
                                                        <textarea name="note_<?= $ind['id'] ?>" class="form-control form-control-sm" rows="2" placeholder="Tuliskan catatan hasil pemeriksaan lapangan atau justifikasi nilai..." <?= (int) $activePeriod['is_locked'] === 1 ? 'disabled' : '' ?>><?= esc($currentNote) ?></textarea>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>

                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Ringkasan Hasil & Kesimpulan Penilaian -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom py-3">
                                <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                    <i class="bx bx-message-square-check text-primary"></i> Ringkasan & Kesimpulan Akhir Assessor
                                </h6>
                            </div>
                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-dark">Ringkasan Hasil Evaluasi</label>
                                    <textarea name="ringkasan_hasil" id="ringkasan_hasil" class="form-control" rows="2" placeholder="Uraikan gambaran umum kinerja pelaksanaan..." <?= (int) $activePeriod['is_locked'] === 1 ? 'disabled' : '' ?>><?= esc($activePeriod['ringkasan_hasil'] ?? '') ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-dark">Kesimpulan & Rekomendasi Resmi <span class="text-danger">*</span></label>
                                    <textarea name="kesimpulan_rekomendasi" id="kesimpulan_rekomendasi" class="form-control" rows="3" placeholder="Tuliskan kesimpulan resmi dan instruksi rekomendasi perbaikan bagi penyedia..." required <?= (int) $activePeriod['is_locked'] === 1 ? 'disabled' : '' ?>><?= esc($activePeriod['kesimpulan_rekomendasi'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <?php if ((int) $activePeriod['is_locked'] === 0): ?>
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 bg-white rounded shadow-sm border mb-4">
                                <button type="button" id="btnSaveDraftAssessment" class="btn btn-outline-primary d-flex align-items-center gap-1">
                                    <i class="bx bx-save"></i> Simpan Draf Nilai
                                </button>

                                <div class="d-flex gap-2">
                                    <button type="button" id="btnSubmitMonev" class="btn btn-primary d-flex align-items-center gap-1" <?= $ratedCount < 12 ? 'disabled' : '' ?>>
                                        <i class="bx bx-send"></i> Submit Hasil Monev (Review)
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning border d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 p-3 rounded shadow-sm mb-4">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bx bx-lock-alt text-warning fs-2"></i>
                                    <div>
                                        <strong class="text-dark d-block">Sesi Monev Ini Berstatus Terkunci (<?= $activePeriod['status'] ?>)</strong>
                                        <span class="text-muted small">Seluruh penilaian dan catatan telah dikunci secara resmi. Jika perlu perbaikan/koreksi, Administrator dapat membuka kunci untuk melakukan revisi.</span>
                                    </div>
                                </div>
                                <?php if (session()->get('role') === 'admin'): ?>
                                    <button type="button" class="btn btn-warning d-flex align-items-center gap-1 fw-bold shadow-sm" onclick="reopenForRevision(<?= $activePeriod['id'] ?>)">
                                        <i class="bx bx-revision"></i> Buka Kunci / Revisi Monev
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </div>

            <!-- ═════════════════════════════════════════════════════════════ -->
            <!-- TAB 6: FINDINGS & ACTION ITEMS -->
            <!-- ═════════════════════════════════════════════════════════════ -->
            <div class="tab-pane fade <?= $activeTab === 'findings' ? 'show active' : '' ?>" id="tab-findings" role="tabpanel">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="bx bx-error text-danger"></i> Daftar Temuan Lapangan & Tindak Lanjut (Action Items)
                        </h5>
                        <?php if ($activePeriod && (int) $activePeriod['is_locked'] === 0 && in_array(session()->get('role'), ['admin', 'operator'])): ?>
                            <button type="button" class="btn btn-danger btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalAddFinding">
                                <i class="bx bx-plus"></i> Catat Temuan Baru
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-4">
                        <?php if (empty($findingsWithDetails)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="bx bx-check-double text-success mb-2" style="font-size: 3.5rem;"></i>
                                <h6 class="fw-bold text-dark">Tidak Ada Temuan Masalah</h6>
                                <p class="mb-0 small">Belum ada temuan penyimpangan teknis atau administratif yang dicatat pada sesi Monev ini.</p>
                            </div>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($findingsWithDetails as $f): 
                                    $sevBadge = [
                                        'LOW'      => 'success',
                                        'MEDIUM'   => 'warning',
                                        'HIGH'     => 'danger',
                                        'CRITICAL' => 'dark bg-danger',
                                    ][$f['severity']] ?? 'secondary';

                                    $stBadge = [
                                        'OPEN'        => 'danger',
                                        'IN_PROGRESS' => 'warning',
                                        'RESOLVED'    => 'info',
                                        'VERIFIED'    => 'success',
                                        'CLOSED'      => 'secondary',
                                    ][$f['status']] ?? 'secondary';
                                ?>
                                    <div class="col-12">
                                        <div class="card border shadow-sm">
                                            <div class="card-header bg-light py-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-<?= $sevBadge ?> fw-bold"><?= $f['severity'] ?></span>
                                                    <span class="font-monospace fw-bold text-primary"><?= esc($f['kode_temuan']) ?></span>
                                                    <span class="fw-bold text-dark"><?= esc($f['title'] ?? $f['deskripsi_temuan']) ?></span>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-label-<?= $stBadge ?> fw-bold">Status: <?= $f['status'] ?></span>
                                                    <?php if ($activePeriod && (int) ($activePeriod['is_locked'] ?? 0) === 0): ?>
                                                        <button class="btn btn-xs btn-outline-primary" onclick="openFollowupModal(<?= $f['id'] ?>, '<?= esc($f['kode_temuan']) ?>')">
                                                            <i class="bx bx-plus"></i> Tindak Lanjut
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="card-body p-3">
                                                <p class="text-dark small mb-2"><?= nl2br(esc($f['deskripsi_temuan'])) ?></p>
                                                <?php if (!empty($f['rekomendasi_tindakan']) && $f['rekomendasi_tindakan'] !== '-'): ?>
                                                    <div class="p-2 bg-light rounded small mb-2 text-muted">
                                                        <strong class="text-dark">Rekomendasi:</strong> <?= esc($f['rekomendasi_tindakan']) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="d-flex flex-wrap gap-3 text-muted small">
                                                    <span><i class="bx bx-user me-1"></i>PJ: <strong><?= esc($f['responsible_party'] ?? 'Penyedia') ?></strong></span>
                                                    <span>&bull;</span>
                                                    <span><i class="bx bx-calendar me-1"></i>Batas Waktu: <strong><?= !empty($f['due_date']) ? date('d/m/Y', strtotime($f['due_date'])) : (!empty($f['batas_waktu_penyelesaian']) ? date('d/m/Y', strtotime($f['batas_waktu_penyelesaian'])) : '-') ?></strong></span>
                                                </div>

                                                <!-- Follow-up Items Section -->
                                                <?php if (!empty($f['followups'])): ?>
                                                    <div class="mt-3 pt-2 border-top">
                                                        <h6 class="small fw-bold text-dark mb-2 d-flex align-items-center gap-1">
                                                            <i class="bx bx-wrench text-warning"></i> Tindakan Perbaikan (Follow-up Actions):
                                                        </h6>
                                                        <div class="table-responsive">
                                                            <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 0.8rem;">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th>Aksi Perbaikan</th>
                                                                        <th>PIC</th>
                                                                        <th>Deadline</th>
                                                                        <th>Status</th>
                                                                        <th>Verifikasi Reviewer</th>
                                                                        <th class="text-center">Aksi</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($f['followups'] as $fup): ?>
                                                                        <tr>
                                                                            <td><?= esc($fup['action'] ?? $fup['deskripsi_perbaikan']) ?></td>
                                                                            <td><?= esc($fup['pic'] ?? '-') ?></td>
                                                                            <td><?= !empty($fup['deadline']) ? date('d/m/Y', strtotime($fup['deadline'])) : (!empty($fup['tgl_tindak_lanjut']) ? date('d/m/Y', strtotime($fup['tgl_tindak_lanjut'])) : '-') ?></td>
                                                                            <td><span class="badge bg-label-info"><?= $fup['status'] ?></span></td>
                                                                            <td>
                                                                                <?php if ($fup['status'] === 'VERIFIED'): ?>
                                                                                    <span class="badge bg-success"><i class="bx bx-check"></i> Diterima</span>
                                                                                    <small class="text-muted d-block"><?= esc($fup['verification_notes'] ?? '') ?></small>
                                                                                <?php else: ?>
                                                                                    <span class="badge bg-label-secondary">Belum Diverifikasi</span>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td class="text-center">
                                                                                <?php if ($fup['status'] !== 'VERIFIED' && in_array(session()->get('role'), ['admin', 'operator'])): ?>
                                                                                    <button class="btn btn-xs btn-success" onclick="openVerifyModal(<?= $fup['id'] ?>)">
                                                                                        <i class="bx bx-badge-check"></i> Verifikasi
                                                                                    </button>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ═════════════════════════════════════════════════════════════ -->
            <!-- TAB 7: EVIDENCE (Galeri Terpartisi & Geotagging) -->
            <!-- ═════════════════════════════════════════════════════════════ -->
            <div class="tab-pane fade <?= $activeTab === 'evidence' ? 'show active' : '' ?>" id="tab-evidence" role="tabpanel">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="bx bx-images text-primary"></i> Galeri Bukti Terpartisi (Evidence Partition)
                        </h5>
                        <?php if ($activePeriod && (int)($activePeriod['is_locked'] ?? 0) === 0 && in_array(session()->get('role'), ['admin', 'operator'])): ?>
                            <button type="button" class="btn btn-sm btn-primary d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalUploadEvidence">
                                <i class="bx bx-upload"></i> Unggah Bukti Lapangan
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-4">
                        <!-- Sub-Kategori 1: Bukti Pemeriksaan Lapangan Monev V2 -->
                        <h6 class="fw-bold text-primary mb-3"><i class="bx bx-camera me-1"></i> Bukti Kunjungan & Temuan Monev V2</h6>
                        <div class="row g-3 mb-4">
                            <?php 
                            $monevEvidences = $activePeriod['evidences'] ?? [];
                            if (empty($monevEvidences)): 
                            ?>
                                <div class="col-12 text-muted small fst-italic">Belum ada lampiran bukti khusus Monev V2.</div>
                            <?php else: ?>
                                <?php foreach ($monevEvidences as $me): ?>
                                    <div class="col-6 col-md-3">
                                        <div class="card border h-100 position-relative shadow-none">
                                            <?php if ($activePeriod && (int)($activePeriod['is_locked'] ?? 0) === 0 && in_array(session()->get('role'), ['admin', 'operator'])): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2 rounded-circle p-0 d-flex align-items-center justify-content-center shadow" 
                                                        style="width: 28px; height: 28px; z-index: 5;" 
                                                        title="Hapus Bukti Foto" 
                                                        onclick="deleteEvidence(<?= (int)$me['id'] ?>)">
                                                    <i class="bx bx-x fs-4"></i>
                                                </button>
                                            <?php endif; ?>
                                            <img src="<?= base_url($me['file_path']) ?>" class="card-img-top" style="height: 140px; object-fit: cover;" onerror="this.src='<?= base_url('assets/img/elements/1.jpg') ?>'">
                                            <div class="card-body p-2">
                                                <span class="badge bg-label-primary mb-1" style="font-size: 0.65rem;"><?= $me['jenis_eviden'] ?></span>
                                                <p class="card-text small text-dark mb-1"><?= esc($me['keterangan'] ?? 'Bukti Monev') ?></p>
                                                <?php if (!empty($me['gps_lat'])): ?>
                                                    <small class="text-muted d-block font-monospace" style="font-size: 0.65rem;">
                                                        <i class="bx bx-map-pin text-danger"></i> <?= $me['gps_lat'] ?>, <?= $me['gps_lng'] ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Sub-Kategori 2: Bukti Fisik Log Harian Verified -->
                        <h6 class="fw-bold text-success mb-3"><i class="bx bx-check-shield me-1"></i> Bukti Fisik Progres Harian Terverifikasi (monev_harian)</h6>
                        <div class="row g-3">
                            <?php 
                            $progEvs = $context['progress_evidences'] ?? [];
                            if (empty($progEvs)): 
                            ?>
                                <div class="col-12 text-muted small fst-italic">Belum ada foto progres fisik terverifikasi.</div>
                            <?php else: ?>
                                <?php foreach ($progEvs as $pe): ?>
                                    <div class="col-6 col-md-3">
                                        <div class="card border h-100">
                                            <img src="<?= base_url($pe['file_path']) ?>" class="card-img-top" style="height: 140px; object-fit: cover;" onerror="this.src='<?= base_url('assets/img/elements/2.jpg') ?>'">
                                            <div class="card-body p-2">
                                                <span class="badge bg-label-success mb-1" style="font-size: 0.65rem;"><?= $pe['jenis'] ?> &bull; <?= date('d/m/Y', strtotime($pe['tanggal'])) ?></span>
                                                <p class="card-text small text-dark mb-0"><?= esc($pe['keterangan'] ?? '-') ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═════════════════════════════════════════════════════════════ -->
            <!-- TAB 8: DOCUMENTS (Referensi Proyek) -->
            <!-- ═════════════════════════════════════════════════════════════ -->
            <div class="tab-pane fade <?= $activeTab === 'documents' ? 'show active' : '' ?>" id="tab-documents" role="tabpanel">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="bx bx-folder text-primary"></i> Dokumen Master & Referensi Proyek (monev_dokumen)
                        </h5>
                    </div>
                    <div class="table-responsive text-nowrap">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Dokumen Referensi</th>
                                    <th>Kategori</th>
                                    <th>Sumber Data</th>
                                    <th>Tanggal Unggah</th>
                                    <th class="text-center">Unduh / Lihat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $refDocs = $context['reference_documents'] ?? [];
                                if (empty($refDocs)): 
                                ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Belum ada dokumen referensi proyek.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($refDocs as $rd): ?>
                                        <tr>
                                            <td class="fw-bold text-dark">
                                                <i class="bx bxs-file-pdf text-danger me-1"></i> <?= esc($rd['nama_dokumen']) ?>
                                            </td>
                                            <td><span class="badge bg-label-info"><?= esc($rd['kategori']) ?></span></td>
                                            <td><span class="badge bg-label-secondary font-monospace"><?= esc($rd['source']) ?></span></td>
                                            <td><?= !empty($rd['uploaded_at']) ? date('d/m/Y H:i', strtotime($rd['uploaded_at'])) : '-' ?></td>
                                            <td class="text-center">
                                                <a href="<?= base_url($rd['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="bx bx-download me-1"></i> Buka File
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ═════════════════════════════════════════════════════════════ -->
            <!-- TAB 9: HISTORY & AUDIT TRAIL -->
            <!-- ═════════════════════════════════════════════════════════════ -->
            <div class="tab-pane fade <?= $activeTab === 'history' ? 'show active' : '' ?>" id="tab-history" role="tabpanel">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="bx bx-history text-primary"></i> Riwayat Persetujuan & Audit Trail Transaksional
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <?php if (empty($auditLogs)): ?>
                            <div class="text-center py-4 text-muted">Belum ada catatan log aktivitas pada sesi ini.</div>
                        <?php else: ?>
                            <ul class="timeline mb-0">
                                <?php foreach ($auditLogs as $al): ?>
                                    <li class="timeline-item timeline-item-transparent ps-4 border-start mb-3 position-relative">
                                        <span class="timeline-point-wrapper position-absolute" style="left: -10px; top: 0;">
                                            <span class="timeline-point bg-primary" style="width: 18px; height: 18px; border-radius: 50%; display: inline-block;"></span>
                                        </span>
                                        <div class="timeline-event">
                                            <div class="timeline-header mb-1 d-flex justify-content-between">
                                                <h6 class="mb-0 fw-bold text-dark"><?= esc($al['action']) ?></h6>
                                                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($al['created_at'])) ?></small>
                                            </div>
                                            <p class="mb-1 text-muted small">
                                                Oleh: <strong class="text-dark"><?= esc($al['actor_nama'] ?? 'System') ?></strong> (Role: <?= esc($al['actor_role']) ?>)
                                                <?php if (!empty($al['old_status']) || !empty($al['new_status'])): ?>
                                                    &bull; Status: <span class="badge bg-label-secondary"><?= $al['old_status'] ?></span> &rarr; <span class="badge bg-label-primary"><?= $al['new_status'] ?></span>
                                                <?php endif; ?>
                                            </p>
                                            <?php if (!empty($al['notes'])): ?>
                                                <div class="p-2 bg-light rounded text-dark small"><?= esc($al['notes']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal Components -->
<?= $this->include('monev_v2/components/modal_finding') ?>
<?= $this->include('monev_v2/components/modal_followup') ?>
<?= $this->include('monev_v2/components/modal_evidence') ?>

<script>
// Global Safe POST Helper
const csrfHeader = '<?= config('Security')->headerName ?>';
const csrfHash = '<?= csrf_hash() ?>';

async function safePost(url, formData) {
    const res = await fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            [csrfHeader]: csrfHash
        }
    });
    const text = await res.text();
    let data;
    try {
        data = JSON.parse(text);
    } catch (e) {
        throw new Error(`Respon server tidak valid (${res.status}): ${text.substring(0, 100)}`);
    }
    if (!res.ok || data.status === 'error') {
        throw new Error(data.message || `Terjadi kesalahan sistem (Status: ${res.status})`);
    }
    return data;
}
window.safePost = safePost;

document.addEventListener('DOMContentLoaded', function() {
    // 1. Render ApexCharts Kurva S
    const scData = <?= json_encode($context['progress_metrics']['scurve'] ?? []) ?>;
    const chartEl = document.querySelector('#scurveChart');
    if (chartEl && scData && scData.labels && scData.labels.length > 0) {
        const options = {
            series: [
                {
                    name: 'Target Rencana (%)',
                    data: scData.plan.map(v => parseFloat(v))
                },
                {
                    name: 'Aktual Verified (%)',
                    data: scData.actual.map(v => parseFloat(v))
                }
            ],
            chart: {
                height: 350,
                type: 'line',
                toolbar: { show: true },
                zoom: { enabled: false }
            },
            colors: ['#8592a3', '#696cff'],
            dataLabels: { enabled: false },
            stroke: {
                curve: 'smooth',
                width: [3, 4],
                dashArray: [5, 0]
            },
            markers: {
                size: 4,
                hover: { size: 7 }
            },
            xaxis: {
                categories: scData.labels
            },
            yaxis: {
                min: 0,
                max: 100,
                title: { text: 'Progres Fisik Kumulatif (%)' }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val.toFixed(2) + " %";
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right'
            }
        };
        const chart = new ApexCharts(chartEl, options);
        chart.render();
    }

    // 2. Rating Change Detection & Progress Counter
    const ratingRadios = document.querySelectorAll('.rating-radio');
    function updateAssessmentProgress() {
        const uniqueIndicators = new Set();
        document.querySelectorAll('.rating-radio:checked').forEach(r => {
            uniqueIndicators.add(r.getAttribute('data-indicator'));
        });
        const count = uniqueIndicators.size;
        const btnSubmit = document.getElementById('btnSubmitMonev');
        if (btnSubmit) {
            if (count >= 12) {
                btnSubmit.disabled = false;
            } else {
                btnSubmit.disabled = true;
            }
        }
    }
    ratingRadios.forEach(r => {
        r.addEventListener('change', updateAssessmentProgress);
    });

    // 3. Simpan Draf Assessment
    const btnDraft = document.getElementById('btnSaveDraftAssessment');
    if (btnDraft) {
        btnDraft.addEventListener('click', function() {
            saveAssessmentForm(false);
        });
    }

    // 4. Submit Monev Resmi
    const btnSubmit = document.getElementById('btnSubmitMonev');
    if (btnSubmit) {
        btnSubmit.addEventListener('click', function() {
            const kesimpulan = document.getElementById('kesimpulan_rekomendasi').value.trim();
            if (!kesimpulan) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Kesimpulan Wajib Diisi',
                    text: 'Silakan isi kesimpulan dan rekomendasi akhir sebelum men-submit Monev.'
                });
                return;
            }

            Swal.fire({
                title: 'Konfirmasi Submit Monev',
                text: 'Hasil evaluasi akan dikirimkan ke Reviewer / Administrator untuk diperiksa.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Submit Sekarang',
                cancelButtonText: 'Batal'
            }).then(result => {
                if (result.isConfirmed) {
                    saveAssessmentForm(true);
                }
            });
        });
    }

    function saveAssessmentForm(doSubmitAfterSave) {
        const form = document.getElementById('formAssessment');
        if (!form) return;

        const periodId = form.querySelector('input[name="period_id"]').value;
        const items = [];

        // Kumpulkan 12 indikator
        <?php if (!empty($template['aspects'])): ?>
            <?php foreach ($template['aspects'] as $asp): ?>
                <?php foreach ($asp['indicators'] as $ind): ?>
                    const selectedRadio_<?= $ind['id'] ?> = form.querySelector('input[name="rating_<?= $ind['id'] ?>"]:checked');
                    const note_<?= $ind['id'] ?> = form.querySelector('textarea[name="note_<?= $ind['id'] ?>"]');
                    if (selectedRadio_<?= $ind['id'] ?>) {
                        items.push({
                            indicator_id: <?= $ind['id'] ?>,
                            rating: selectedRadio_<?= $ind['id'] ?>.value,
                            note: note_<?= $ind['id'] ?> ? note_<?= $ind['id'] ?>.value : ''
                        });
                    }
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        const ringkasan = document.getElementById('ringkasan_hasil') ? document.getElementById('ringkasan_hasil').value : '';
        const kesimpulan = document.getElementById('kesimpulan_rekomendasi') ? document.getElementById('kesimpulan_rekomendasi').value : '';

        const payload = new FormData();
        payload.append('period_id', periodId);
        payload.append('items', JSON.stringify(items));
        payload.append('ringkasan_hasil', ringkasan);
        payload.append('kesimpulan_rekomendasi', kesimpulan);

        safePost('<?= base_url('dashboard/monev-v2/assessment/save') ?>', payload)
        .then(data => {
            if (doSubmitAfterSave) {
                const subData = new FormData();
                subData.append('period_id', periodId);
                subData.append('catatan', kesimpulan);
                return safePost('<?= base_url('dashboard/monev-v2/submit') ?>', subData)
                .then(subRes => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Disubmit!',
                        text: 'Laporan Monev telah diteruskan ke Reviewer.'
                    }).then(() => location.reload());
                });
            } else {
                Swal.fire({
                    icon: 'success',
                    title: 'Draf Tersimpan!',
                    text: 'Skor sementara: ' + data.total_score + ' / 36 (' + data.persentase + '%)',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => location.reload());
            }
        })
        .catch(err => {
            Swal.fire({ icon: 'error', title: 'Gagal Menyimpan Penilaian', text: err.message });
        });
    }

    // 5. Submit Finding Baru
    const formFinding = document.getElementById('formAddFinding');
    if (formFinding) {
        formFinding.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitFinding');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

            const formData = new FormData(this);
            safePost('<?= base_url('dashboard/monev-v2/finding/save') ?>', formData)
            .then(data => {
                Swal.fire({ icon: 'success', title: 'Temuan Dicatat!', timer: 1500, showConfirmButton: false })
                    .then(() => location.reload());
            })
            .catch(err => {
                Swal.fire({ icon: 'error', title: 'Gagal Mencatat Temuan', text: err.message });
                btn.disabled = false;
                btn.innerHTML = '<i class="bx bx-save"></i> Simpan Temuan';
            });
        });
    }

    // 6. Submit Follow-up Baru
    const formFollowup = document.getElementById('formAddFollowup');
    if (formFollowup) {
        formFollowup.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitFollowup');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
            }
            const formData = new FormData(this);
            safePost('<?= base_url('dashboard/monev-v2/followup/save') ?>', formData)
            .then(data => {
                Swal.fire({ icon: 'success', title: 'Tindak Lanjut Disimpan!', timer: 1500, showConfirmButton: false })
                    .then(() => location.reload());
            })
            .catch(err => {
                Swal.fire({ icon: 'error', title: 'Gagal Menyimpan Tindak Lanjut', text: err.message });
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bx bx-check-circle"></i> Simpan Tindak Lanjut';
                }
            });
        });
    }

    // 7. Submit Verifikasi Follow-up
    const formVerify = document.getElementById('formVerifyFollowup');
    if (formVerify) {
        formVerify.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitVerify');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';
            }
            const formData = new FormData(this);
            safePost('<?= base_url('dashboard/monev-v2/followup/verify') ?>', formData)
            .then(data => {
                Swal.fire({ icon: 'success', title: 'Verifikasi Disimpan!', timer: 1500, showConfirmButton: false })
                    .then(() => location.reload());
            })
            .catch(err => {
                Swal.fire({ icon: 'error', title: 'Gagal Verifikasi', text: err.message });
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bx bx-save"></i> Simpan Hasil Verifikasi';
                }
            });
        });
    }

    // 8. Submit Upload Evidence Baru
    const formUploadEvidence = document.getElementById('formUploadEvidence');
    if (formUploadEvidence) {
        formUploadEvidence.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitEvidence');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengunggah...';
            }
            const formData = new FormData(this);
            safePost('<?= base_url('dashboard/monev-v2/evidence/upload') ?>', formData)
            .then(data => {
                Swal.fire({ icon: 'success', title: 'Bukti Berhasil Diunggah!', timer: 1500, showConfirmButton: false })
                    .then(() => location.reload());
            })
            .catch(err => {
                Swal.fire({ icon: 'error', title: 'Gagal Mengunggah Bukti', text: err.message });
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bx bx-upload"></i> Unggah Bukti';
                }
            });
        });
    }

    // 9. Ambil Geo Tag (GPS) pada Modal Bukti Lapangan
    const btnGetEvidenceGps = document.getElementById('btnGetEvidenceGps');
    const inputLat = document.getElementById('evidence_lat');
    const inputLng = document.getElementById('evidence_lng');
    const gpsFeedback = document.getElementById('gpsStatusFeedback');

    if (btnGetEvidenceGps) {
        btnGetEvidenceGps.addEventListener('click', function() {
            if (!navigator.geolocation) {
                if (gpsFeedback) {
                    gpsFeedback.innerHTML = '<span class="text-danger"><i class="bx bx-error-circle"></i> Geolocation tidak didukung di browser ini.</span>';
                }
                Swal.fire({
                    icon: 'warning',
                    title: 'Tidak Didukung',
                    text: 'Perangkat / browser Anda tidak mendukung fitur Geolocation GPS.'
                });
                return;
            }

            const originalBtnHtml = btnGetEvidenceGps.innerHTML;
            btnGetEvidenceGps.disabled = true;
            btnGetEvidenceGps.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengambil GPS...';
            if (gpsFeedback) {
                gpsFeedback.innerHTML = '<span class="text-primary"><i class="bx bx-loader-alt bx-spin me-1"></i> Mengakses sensor GPS / lokasi perangkat...</span>';
            }

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude.toFixed(7);
                    const lng = position.coords.longitude.toFixed(7);
                    const acc = Math.round(position.coords.accuracy);

                    if (inputLat) inputLat.value = lat;
                    if (inputLng) inputLng.value = lng;

                    btnGetEvidenceGps.disabled = false;
                    btnGetEvidenceGps.innerHTML = '<i class="bx bx-check text-success"></i> Berhasil';
                    setTimeout(() => { btnGetEvidenceGps.innerHTML = originalBtnHtml; }, 3000);

                    if (gpsFeedback) {
                        gpsFeedback.innerHTML = `<span class="text-success fw-semibold"><i class="bx bx-check-circle"></i> Koordinat terisi (Akurasi: ±${acc}m): ${lat}, ${lng}</span>`;
                    }
                },
                function(error) {
                    btnGetEvidenceGps.disabled = false;
                    btnGetEvidenceGps.innerHTML = originalBtnHtml;

                    let errorMsg = 'Gagal mendeteksi lokasi.';
                    if (error.code === error.PERMISSION_DENIED) {
                        errorMsg = 'Akses lokasi ditolak. Silakan izinkan izin lokasi (GPS) pada browser Anda.';
                    } else if (error.code === error.POSITION_UNAVAILABLE) {
                        errorMsg = 'Sinyal GPS / lokasi tidak tersedia.';
                    } else if (error.code === error.TIMEOUT) {
                        errorMsg = 'Waktu permintaan lokasi habis (timeout). Coba lagi di tempat terbuka.';
                    }

                    if (gpsFeedback) {
                        gpsFeedback.innerHTML = `<span class="text-danger"><i class="bx bx-error-circle"></i> ${errorMsg}</span>`;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Mengambil Koordinat',
                        text: errorMsg
                    });
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        });
    }
});

// Helper Functions
function deleteEvidence(evidenceId) {
    Swal.fire({
        title: 'Hapus Foto Bukti?',
        text: 'Foto bukti yang dihapus tidak dapat dipulihkan kembali.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bx bx-trash me-1"></i> Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Menghapus Foto...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            safePost('<?= base_url('dashboard/monev-v2/evidence/delete/') ?>' + evidenceId, new FormData())
            .then(data => {
                Swal.fire({
                    icon: 'success',
                    title: 'Terhapus!',
                    text: data.message || 'Foto bukti berhasil dihapus.',
                    timer: 1300,
                    showConfirmButton: false
                }).then(() => location.reload());
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Menghapus',
                    text: err.message || 'Terjadi kesalahan saat menghapus foto bukti.'
                });
            });
        }
    });
}
function openFollowupModal(findingId, kodeTemuan) {
    document.getElementById('followup_finding_id').value = findingId;
    document.getElementById('followup_finding_label').textContent = kodeTemuan;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAddFollowup')).show();
}

function openVerifyModal(followupId) {
    document.getElementById('verify_followup_id').value = followupId;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalVerifyFollowup')).show();
}

function startNewPeriod(proyekId) {
    Swal.fire({
        title: 'Mulai Periode Baru',
        text: 'Buat sesi Monev baru untuk proyek ini?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Mulai Sesi',
        cancelButtonText: 'Batal'
    }).then(result => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('proyek_id', proyekId);
            formData.append('jenis_monev', 'PERIODIC');
            safePost('<?= base_url('dashboard/monev-v2/start') ?>', formData)
            .then(data => {
                Swal.fire({ icon: 'success', title: 'Sesi Baru Dibuat!', timer: 1500, showConfirmButton: false })
                    .then(() => location.reload());
            })
            .catch(err => {
                Swal.fire({ icon: 'error', title: 'Gagal Memulai Sesi', text: err.message });
            });
        }
    });
}

function finalizeMonev(periodId) {
    Swal.fire({
        title: 'Finalisasi Monev?',
        text: 'Setelah difinalisasi, seluruh data evaluasi akan DIKUNCI PERMANEN (Immutable). Tindakan ini tidak dapat dibatalkan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#71dd37',
        confirmButtonText: 'Ya, Finalisasi dan Kunci',
        cancelButtonText: 'Batal'
    }).then(result => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('period_id', periodId);
            formData.append('catatan', 'Monev resmi difinalisasi oleh Administrator.');
            safePost('<?= base_url('dashboard/monev-v2/finalize') ?>', formData)
            .then(data => {
                Swal.fire({ icon: 'success', title: 'Monev Berhasil Difinalisasi!', text: data.message })
                    .then(() => location.reload());
            })
            .catch(err => {
                Swal.fire({ icon: 'error', title: 'Finalisasi Ditolak', text: err.message });
            });
        }
    });
}

function reopenForRevision(periodId) {
    Swal.fire({
        title: 'Buka Kunci untuk Revisi?',
        text: 'Sesi Monev ini akan dibuka kembali (Status: REVISI / RETURNED) agar nilai indikator, temuan, dan dokumen dapat diperbaiki.',
        input: 'textarea',
        inputLabel: 'Alasan / Catatan Instruksi Revisi (Wajib):',
        inputPlaceholder: 'Contoh: Penyesuaian foto temuan dan klarifikasi justifikasi mutu agregat...',
        inputValidator: (value) => {
            if (!value) {
                return 'Alasan revisi wajib diisi!';
            }
        },
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Buka Kunci',
        confirmButtonColor: '#ffab00',
        cancelButtonText: 'Batal'
    }).then(result => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('period_id', periodId);
            formData.append('alasan_revisi', result.value);
            safePost('<?= base_url('dashboard/monev-v2/reopen') ?>', formData)
            .then(data => {
                Swal.fire({ icon: 'success', title: 'Sesi Siap Direvisi!', text: data.message })
                    .then(() => location.reload());
            })
            .catch(err => {
                Swal.fire({ icon: 'error', title: 'Gagal Membuka Kunci', text: err.message });
            });
        }
    });
}
</script>
<?= $this->endSection() ?>
