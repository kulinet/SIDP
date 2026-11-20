<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?>

<?= $this->section('content') ?>
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
        <div>
            <h4 class="fw-bold py-1 mb-0">
                <span class="text-muted fw-light">MONEV V2 /</span> Dashboard & Early Warning
            </h4>
            <p class="text-muted small mb-0">Pemantauan kesehatan proyek secara terintegrasi berdasarkan progres, kinerja penyedia, dan temuan HSE/Administrasi.</p>
        </div>
        <div>
            <?php if (in_array(session()->get('role'), ['admin', 'operator'])): ?>
                <button type="button" class="btn btn-primary shadow-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalStartMonev">
                    <i class="bx bx-plus-circle fs-5"></i> Mulai Monev Baru
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- KPI Summary Cards - Top Row (Early Warning) -->
    <div class="row g-3 mb-4">
        <!-- Proyek Kritis (Merah) -->
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #ff3e1d !important;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="text-muted small d-block mb-1">Proyek Kritis (Deviasi ≤ -10%)</span>
                            <h3 class="fw-bold mb-0 text-danger"><?= $kpi['proyek_kritis'] ?? 0 ?></h3>
                        </div>
                        <div class="avatar avatar-sm">
                            <span class="avatar-initial rounded bg-label-danger"><i class="bx bx-error-alt"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Proyek Terlambat (Kuning) -->
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #ffab00 !important;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="text-muted small d-block mb-1">Proyek Terlambat (Deviasi < 0)</span>
                            <h3 class="fw-bold mb-0 text-warning"><?= $kpi['proyek_terlambat'] ?? 0 ?></h3>
                        </div>
                        <div class="avatar avatar-sm">
                            <span class="avatar-initial rounded bg-label-warning"><i class="bx bx-trending-down"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Temuan Overdue -->
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #ff3e1d !important;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="text-muted small d-block mb-1">Temuan Overdue</span>
                            <h3 class="fw-bold mb-0 text-danger"><?= $kpi['findings_overdue'] ?? 0 ?></h3>
                        </div>
                        <div class="avatar avatar-sm">
                            <span class="avatar-initial rounded bg-label-danger"><i class="bx bx-list-minus"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monev Overdue -->
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #8592a3 !important;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="text-muted small d-block mb-1">Monev Overdue (>30 Hari)</span>
                            <h3 class="fw-bold mb-0 text-secondary"><?= $kpi['monev_overdue'] ?? 0 ?></h3>
                        </div>
                        <div class="avatar avatar-sm">
                            <span class="avatar-initial rounded bg-label-secondary"><i class="bx bx-history"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="avatar avatar-md flex-shrink-0">
                        <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-buildings fs-4"></i></span>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Total Proyek (Aktif)</span>
                        <h4 class="fw-bold mb-0 text-dark"><?= $kpi['total_proyek'] ?? 0 ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="avatar avatar-md flex-shrink-0">
                        <span class="avatar-initial rounded bg-label-info"><i class="bx bx-star fs-4"></i></span>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Rata-rata Kinerja</span>
                        <h4 class="fw-bold mb-0 text-info"><?= $kpi['avg_performance'] ?? 0 ?> / 36</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="avatar avatar-md flex-shrink-0">
                        <span class="avatar-initial rounded bg-label-warning"><i class="bx bx-message-error fs-4"></i></span>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Temuan (Open)</span>
                        <h4 class="fw-bold mb-0 text-warning"><?= $kpi['findings_open'] ?? 0 ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="avatar avatar-md flex-shrink-0">
                        <span class="avatar-initial rounded bg-label-danger"><i class="bx bx-task-x fs-4"></i></span>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Follow-up Overdue</span>
                        <h4 class="fw-bold mb-0 text-danger"><?= $kpi['followups_overdue'] ?? 0 ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN DASHBOARD: PROJECT HEALTH MATRIX -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0"><i class="bx bx-heart-circle text-success me-2"></i>Project Health Matrix</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive text-nowrap">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Proyek & Penyedia</th>
                            <th class="text-center">Schedule<br><small class="text-muted">(Jadwal)</small></th>
                            <th class="text-center">Performance<br><small class="text-muted">(Skor Kinerja)</small></th>
                            <th class="text-center">Findings / HSE<br><small class="text-muted">(Temuan)</small></th>
                            <th>Info Terbaru</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($projectHealthList)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada proyek yang terdaftar.</td></tr>
                        <?php else: ?>
                            <?php foreach ($projectHealthList as $ph): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <strong class="text-dark"><?= esc($ph['nama_proyek']) ?></strong>
                                            <span class="text-muted small"><i class="bx bx-building-house me-1"></i><?= esc($ph['penyedia']) ?></span>
                                        </div>
                                    </td>
                                    
                                    <!-- Health Badges -->
                                    <td class="text-center">
                                        <?php if ($ph['health_schedule'] === 'GREEN'): ?>
                                            <span class="badge bg-success" title="Sesuai Jadwal (Deviasi: <?= $ph['deviasi'] ?>%)"><i class="bx bx-check-circle fs-5"></i></span>
                                        <?php elseif ($ph['health_schedule'] === 'YELLOW'): ?>
                                            <span class="badge bg-warning" title="Perhatian (Deviasi: <?= $ph['deviasi'] ?>%)"><i class="bx bx-error fs-5"></i></span>
                                        <?php elseif ($ph['health_schedule'] === 'RED'): ?>
                                            <span class="badge bg-danger" title="Kritis (Deviasi: <?= $ph['deviasi'] ?>%)"><i class="bx bx-error-alt fs-5"></i></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary" title="Belum Ada Data"><i class="bx bx-minus fs-5"></i></span>
                                        <?php endif; ?>
                                        <div class="small mt-1 text-muted"><?= $ph['deviasi'] ?>%</div>
                                    </td>
                                    
                                    <td class="text-center">
                                        <?php if ($ph['health_performance'] === 'GREEN'): ?>
                                            <span class="badge bg-success" title="Kinerja Baik (Skor: <?= $ph['skor'] ?>)"><i class="bx bx-check-circle fs-5"></i></span>
                                        <?php elseif ($ph['health_performance'] === 'YELLOW'): ?>
                                            <span class="badge bg-warning" title="Kinerja Cukup (Skor: <?= $ph['skor'] ?>)"><i class="bx bx-error fs-5"></i></span>
                                        <?php elseif ($ph['health_performance'] === 'RED'): ?>
                                            <span class="badge bg-danger" title="Kinerja Buruk (Skor: <?= $ph['skor'] ?>)"><i class="bx bx-error-alt fs-5"></i></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary" title="Belum Finalisasi"><i class="bx bx-minus fs-5"></i></span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="text-center">
                                        <?php if ($ph['health_findings'] === 'GREEN'): ?>
                                            <span class="badge bg-success" title="Tidak ada temuan kritikal"><i class="bx bx-check-circle fs-5"></i></span>
                                        <?php elseif ($ph['health_findings'] === 'YELLOW'): ?>
                                            <span class="badge bg-warning" title="Ada temuan High"><i class="bx bx-error fs-5"></i></span>
                                        <?php elseif ($ph['health_findings'] === 'RED'): ?>
                                            <span class="badge bg-danger" title="Ada temuan Kritikal!"><i class="bx bx-error-alt fs-5"></i></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary" title="Belum Ada Data"><i class="bx bx-minus fs-5"></i></span>
                                        <?php endif; ?>
                                        <?php if ($ph['open_findings'] > 0): ?>
                                            <div class="small mt-1 text-danger"><?= $ph['open_findings'] ?> Open</div>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <?php if ($ph['latest_monev_date']): ?>
                                            <div class="small text-dark">Terakhir: <?= date('d M Y', strtotime($ph['latest_monev_date'])) ?></div>
                                            <span class="badge bg-label-primary mt-1" style="font-size: 0.65rem;"><?= esc($ph['status_monev']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">Belum ada Monev</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="text-center">
                                        <a href="<?= base_url('dashboard/monev/project/' . $ph['id']) ?>" class="btn btn-sm btn-primary">
                                            <i class="bx bx-search me-1"></i> Drill-down
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

    <!-- Recent History (Bottom) -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="fw-bold text-dark mb-0"><i class="bx bx-history text-secondary me-2"></i>Riwayat Sesi Monev Terbaru</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive text-nowrap">
                <table id="tableMonevList" class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Proyek / Sesi Monev</th>
                            <th>Status & Progress</th>
                            <th>Temuan / Masalah</th>
                            <th>Petugas / Assessor</th>
                            <th>Opsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentPeriods)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada riwayat sesi Monev terbaru.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentPeriods as $p): ?>
                                <tr>
                                    <td>
                                        <strong class="text-dark"><?= esc($p['nama_proyek']) ?></strong><br>
                                        <span class="text-muted small">Sesi #<?= esc($p['periode_ke']) ?> (<?= date('d M Y', strtotime($p['tgl_rencana_monev'])) ?>)</span>
                                    </td>
                                    <td>
                                        <?php
                                        $bgStatus = match($p['status']) {
                                            'FINALIZED' => 'success',
                                            'APPROVED' => 'primary',
                                            'RETURNED' => 'danger',
                                            'SUBMITTED', 'IN_REVIEW' => 'warning',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-label-<?= $bgStatus ?> mb-1"><?= esc($p['status']) ?></span><br>
                                        <span class="text-muted small">Deviasi: 
                                            <strong class="<?= $p['deviasi'] < 0 ? 'text-danger' : 'text-success' ?>">
                                                <?= $p['deviasi'] > 0 ? '+' : '' ?><?= $p['deviasi'] ?>%
                                            </strong>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($p['total_findings'] > 0): ?>
                                            <span class="badge bg-label-danger"><?= $p['open_findings'] ?> Open</span>
                                            <span class="badge bg-label-secondary"><?= $p['total_findings'] ?> Total</span>
                                        <?php else: ?>
                                            <span class="badge bg-label-success">Tidak Ada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($p['nama_assessor']): ?>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm me-2">
                                                    <span class="avatar-initial rounded-circle bg-label-primary"><?= strtoupper(substr($p['nama_assessor'], 0, 1)) ?></span>
                                                </div>
                                                <div class="small">
                                                    <strong><?= esc($p['nama_assessor']) ?></strong><br>
                                                    <span class="text-muted"><?= esc($p['username_assessor']) ?></span>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('dashboard/monev/project/' . $p['proyek_id'] . '/' . $p['id']) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bx bx-right-arrow-alt me-1"></i> Buka Workspace
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
</div>

<!-- Modal Mulai Monev (Existing logic) -->
<div class="modal fade" id="modalStartMonev" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= base_url('dashboard/monev-v2/start') ?>" method="post">
            <?= csrf_field() ?>
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white border-0 py-3">
                    <h5 class="modal-title fw-bold text-white"><i class="bx bx-play-circle me-1"></i> Mulai Sesi Monev</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <p class="text-muted mb-4 small">Buat sesi Monitoring & Evaluasi baru untuk proyek berjalan. Sistem otomatis akan mengambil snapshot dari kurva S terkini yang sudah diverifikasi.</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih Proyek <span class="text-danger">*</span></label>
                        <select name="proyek_id" class="form-select shadow-sm" required>
                            <option value="">-- Pilih Proyek Terdaftar --</option>
                            <?php foreach ($proyekList as $pr): ?>
                                <option value="<?= $pr['id'] ?>"><?= esc($pr['nama_proyek']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if(empty($proyekList)): ?>
                            <small class="text-danger mt-1 d-block"><i class="bx bx-error-circle"></i> Anda belum mendaftarkan proyek ke dalam sistem Monev.</small>
                        <?php endif; ?>
                        <small class="text-muted mt-1 d-block">Proyek tidak ada di daftar? <a href="<?= base_url('dashboard/monev-proyek') ?>">Daftarkan Proyek Baru</a></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tanggal Rencana Monev <span class="text-danger">*</span></label>
                        <input type="date" name="tgl_rencana_monev" class="form-control shadow-sm" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jenis Monev</label>
                        <select name="jenis_monev" class="form-select shadow-sm">
                            <option value="PERIODIC">Berkala (Mingguan/Bulanan)</option>
                            <option value="INCIDENTAL">Insidental (Temuan Khusus)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top-0 pt-3">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4"><i class="bx bx-check-circle me-1"></i> Mulai Monev</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
