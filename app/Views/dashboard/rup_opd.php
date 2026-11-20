<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?>
<?= $this->section('content') ?>

<!-- DataTables CSS CDN -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<style>
    /* Styling Header Tabel Bertingkat Rekap RUP */
    .table-rekap-rup {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 0.82rem;
    }
    .table-rekap-rup th {
        vertical-align: middle !important;
        text-align: center !important;
        font-weight: 700 !important;
        border: 1px solid #c7d2fe !important;
        padding: 6px 4px !important;
        color: #1e293b !important;
    }
    .table-rekap-rup td {
        vertical-align: middle !important;
        border: 1px solid #e2e8f0 !important;
        padding: 6px 6px !important;
        white-space: nowrap !important;
    }
    .table-rekap-rup td.col-satker {
        white-space: normal !important;
        min-width: 220px !important;
    }
    
    /* Header Color Groups */
    .th-satker { background-color: #dcfce7 !important; color: #14532d !important; }
    .th-pagu { background-color: #d1fae5 !important; color: #065f46 !important; }
    .th-paket { background-color: #e0f2fe !important; color: #0369a1 !important; }
    .th-jenis { background-color: #fef08a !important; color: #713f12 !important; }
    .th-metode { background-color: #ffedd5 !important; color: #7c2d12 !important; }
    
    .th-sub-pagu { background-color: #ecfdf5 !important; color: #047857 !important; }
    .th-sub-paket { background-color: #f0f9ff !important; color: #0284c7 !important; }
    .th-sub-jenis { background-color: #fefce8 !important; color: #854d0e !important; }
    .th-sub-metode { background-color: #fff7ed !important; color: #9a3412 !important; }

    /* Footer Grand Total */
    .table-rekap-rup tfoot th {
        background-color: #f8fafc !important;
        font-weight: 800 !important;
        font-size: 0.85rem !important;
        border-top: 2px solid #64748b !important;
    }

    .nav-tabs .nav-link {
        font-weight: 600;
        font-size: 0.95rem;
        padding: 0.75rem 1.25rem;
        color: #64748b;
        border: none;
        border-bottom: 3px solid transparent;
        background: transparent;
    }
    .nav-tabs .nav-link.active {
        color: #4f46e5;
        border-bottom: 3px solid #4f46e5;
        background: #f8fafc;
    }

    .details-control { cursor: pointer; }
    .dataTables_filter input { border-radius: 5px; padding: 5px 10px; border: 1px solid #d9dee3; }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Header Title & Action Toolbar -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1">
                <span class="text-muted fw-light">Data RUP /</span> Rekapitulasi & Analisis RUP OPD
            </h4>
            <div class="text-muted small">
                Monitoring Rencana Umum Pengadaan (RUP) Pemerintah Kabupaten Lombok Barat Tahun Anggaran <?= esc($currentYear) ?>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="d-flex align-items-center bg-white rounded px-2 border shadow-sm">
                <span class="small fw-bold text-muted me-2"><i class="bx bx-calendar me-1"></i>Tahun:</span>
                <select id="select-global-year" class="form-select form-select-sm border-0 shadow-none font-monospace fw-bold text-primary" style="width: auto;">
                    <?php foreach($availableYears as $yr): ?>
                    <option value="<?= $yr ?>" <?= $yr == $currentYear ? 'selected' : '' ?>><?= $yr ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if(in_array(session()->get('role'), ['admin', 'operator'])): ?>
            <a href="<?= base_url('dashboard/clear-cache') ?>" class="btn btn-outline-danger btn-sm" data-confirm="Apakah Anda yakin ingin menghapus seluruh cache data? Hal ini akan memaksa sistem menarik ulang data terbaru dari API.">
                <i class="bx bx-trash me-1"></i> Clear Cache
            </a>
            <?php endif; ?>
            <a href="<?= base_url('dashboard/export-non-pengadaan?year=' . $currentYear . ($selectedOpd !== 'all' && !empty($selectedOpd) ? '&opd=' . urlencode($selectedOpd) : '')) ?>" class="btn btn-sm btn-danger shadow-sm" id="btn-export-non-pengadaan" title="Unduh CSV data paket anomali non-pengadaan untuk diberikan ke OPD agar segera dibatalkan/dihapus">
                <i class="bx bxs-file-export me-1"></i> Ekspor Temuan Non-Pengadaan
            </a>
            <a href="<?= base_url('dashboard/export-rup-opd?year=' . $currentYear) ?>" class="btn btn-sm btn-success shadow-sm">
                <i class="bx bx-download me-1"></i> Unduh Rekap CSV
            </a>
            <a href="<?= base_url('dashboard/export-rup-detail?year=' . $currentYear . ($selectedOpd !== 'all' && !empty($selectedOpd) ? '&opd=' . urlencode($selectedOpd) : '')) ?>" class="btn btn-sm btn-primary shadow-sm" title="Unduh data detail RUP Penyedia & Swakelola dalam satu file Excel">
                <i class="bx bx-spreadsheet me-1"></i> Unduh Detail RUP (Penyedia + Swakelola)
            </a>
        </div>
    </div>

    <!-- Executive KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-lg">
            <div class="card card-border-shadow-secondary h-100 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar avatar-sm me-2">
                            <span class="avatar-initial rounded bg-label-secondary"><i class="bx bx-money"></i></span>
                        </div>
                        <span class="fw-semibold text-muted small text-uppercase">Pagu Master</span>
                    </div>
                    <h5 class="mb-1 fw-bold text-dark">Rp <?= number_format($totalsAnalisis['total_master'] ?? 0, 0, ',', '.') ?></h5>
                    <small class="text-muted">Pagu DPA / Kegiatan</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg">
            <div class="card card-border-shadow-primary h-100 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar avatar-sm me-2">
                            <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-briefcase"></i></span>
                        </div>
                        <span class="fw-semibold text-muted small text-uppercase">Pagu Penyedia</span>
                    </div>
                    <h5 class="mb-1 fw-bold text-primary">Rp <?= number_format($totalsRekap['pagu_penyedia'] ?? 0, 0, ',', '.') ?></h5>
                    <small class="text-muted"><?= number_format($totalsRekap['paket_penyedia'] ?? 0, 0, ',', '.') ?> Paket (<?= $totalsRekap['pct_pagu_penyedia'] ?? 0 ?>%)</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg">
            <div class="card card-border-shadow-success h-100 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar avatar-sm me-2">
                            <span class="avatar-initial rounded bg-label-success"><i class="bx bx-group"></i></span>
                        </div>
                        <span class="fw-semibold text-muted small text-uppercase">Pagu Swakelola</span>
                    </div>
                    <h5 class="mb-1 fw-bold text-success">Rp <?= number_format($totalsRekap['pagu_swakelola'] ?? 0, 0, ',', '.') ?></h5>
                    <small class="text-muted"><?= number_format($totalsRekap['paket_swakelola'] ?? 0, 0, ',', '.') ?> Paket (<?= $totalsRekap['pct_pagu_swakelola'] ?? 0 ?>%)</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg">
            <div class="card card-border-shadow-danger h-100 shadow-sm border-start border-4 border-danger">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-sm me-2">
                                <span class="avatar-initial rounded bg-label-danger"><i class="bx bx-shield-x"></i></span>
                            </div>
                            <span class="fw-semibold text-danger small text-uppercase">Non-Pengadaan</span>
                        </div>
                        <?php if(!empty($totalsAnalisis['total_non_pengadaan_paket'])): ?>
                        <span class="badge bg-danger animate__animated animate__pulse animate__infinite font-monospace" style="font-size: 0.65rem;">
                            <?= number_format($totalsAnalisis['total_non_pengadaan_paket'] ?? 0, 0, ',', '.') ?> Paket
                        </span>
                        <?php endif; ?>
                    </div>
                    <h5 class="mb-1 fw-bold text-danger">Rp <?= number_format($totalsAnalisis['total_non_pengadaan_pagu'] ?? 0, 0, ',', '.') ?></h5>
                    <small class="text-muted"><?= number_format($totalsAnalisis['opd_with_non_pengadaan_count'] ?? 0, 0, ',', '.') ?> OPD Perlu Dihapus</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg">
            <div class="card card-border-shadow-info h-100 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar avatar-sm me-2">
                            <span class="avatar-initial rounded bg-label-info"><i class="bx bx-wallet"></i></span>
                        </div>
                        <span class="fw-semibold text-muted small text-uppercase">Total Pagu RUP</span>
                    </div>
                    <h5 class="mb-1 fw-bold text-info">Rp <?= number_format($totalsRekap['total_pagu'] ?? 0, 0, ',', '.') ?></h5>
                    <small class="text-muted"><?= number_format($totalsRekap['total_paket'] ?? 0, 0, ',', '.') ?> Total Paket RUP</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg">
            <div class="card card-border-shadow-warning h-100 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-sm me-2">
                                <span class="avatar-initial rounded bg-label-warning"><i class="bx bx-bar-chart-alt-2"></i></span>
                            </div>
                            <span class="fw-semibold text-muted small text-uppercase">Keterumuman</span>
                        </div>
                        <?php if(!empty($totalsAnalisis['anomaly_count'])): ?>
                        <span class="badge bg-label-danger animate__animated animate__pulse animate__infinite" style="font-size: 0.65rem;">
                            <i class="bx bx-error-alt me-1"></i> <?= $totalsAnalisis['anomaly_count'] ?> Anomali
                        </span>
                        <?php endif; ?>
                    </div>
                    <h5 class="mb-1 fw-bold text-warning"><?= $totalsAnalisis['avg_progress'] ?? 0 ?>%</h5>
                    <div class="progress" style="height: 4px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?= min(100, $totalsAnalisis['avg_progress'] ?? 0) ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation Tabs -->
    <div class="card shadow-sm border-0">
        <div class="card-header border-bottom p-0 bg-white">
            <ul class="nav nav-tabs card-header-tabs m-0" id="rupTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-rekap-btn" data-bs-toggle="tab" data-bs-target="#tab-rekap" type="button" role="tab" aria-controls="tab-rekap" aria-selected="true">
                        <i class="bx bx-grid-alt me-2 text-success"></i> Rekapitulasi & Profil RUP OPD
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-analisis-btn" data-bs-toggle="tab" data-bs-target="#tab-analisis" type="button" role="tab" aria-controls="tab-analisis" aria-selected="false">
                        <i class="bx bx-analyse me-2 text-primary"></i> Analisis Keterumuman & Anomali
                        <?php if(!empty($totalsAnalisis['anomaly_count'])): ?>
                        <span class="badge bg-danger rounded-pill ms-2" style="font-size: 0.7rem;"><?= $totalsAnalisis['anomaly_count'] ?></span>
                        <?php endif; ?>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body pt-4">
            <div class="tab-content p-0" id="rupTabsContent">
                
                <!-- ==================== TAB 1: REKAPITULASI & PROFIL RUP ==================== -->
                <div class="tab-pane fade show active" id="tab-rekap" role="tabpanel" aria-labelledby="tab-rekap-btn">
                    <!-- Filter Toolbar Tab 1 -->
                    <div class="p-3 bg-light rounded-3 mb-4 border">
                        <div class="row align-items-end g-3">
                            <div class="col-md-4">
                                <label class="form-label text-uppercase fw-bold small text-muted">Pencarian Cepat</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bx bx-search"></i></span>
                                    <input type="text" id="custom-search-rekap" placeholder="Cari Satker / OPD..." class="form-control">
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label text-uppercase fw-bold small text-muted">Filter Satuan Kerja / OPD</label>
                                <?php if(!$isRestrictedRole): ?>
                                <select id="filter-opd-rekap" class="form-select">
                                    <option value="all" <?= ($selectedOpd === 'all' || empty($selectedOpd)) ? 'selected' : '' ?>>Semua OPD (<?= count($availableOpds) ?> Satker)</option>
                                    <?php foreach($availableOpds as $opd): ?>
                                    <option value="<?= esc($opd) ?>" <?= $opd === $selectedOpd ? 'selected' : '' ?>><?= esc($opd) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php else: ?>
                                <input type="text" class="form-control" value="<?= esc($summaryRekap[0]['nama_satker'] ?? 'Satker Anda') ?>" readonly>
                                <input type="hidden" id="filter-opd-rekap" value="all">
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3">
                                <button id="btn-reset-rekap" class="btn btn-outline-secondary w-100">
                                    <i class="bx bx-reset me-1"></i> Reset Filter
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Header Matrix Title -->
                    <div class="text-center py-2 mb-3">
                        <h5 class="fw-bold mb-1 text-uppercase text-dark">
                            REKAPITULASI DAN PROFIL RENCANA UMUM PENGADAAN (RUP) KABUPATEN LOMBOK BARAT
                        </h5>
                        <div class="text-primary fw-bold">TAHUN ANGGARAN <?= esc($currentYear) ?></div>
                    </div>

                    <div class="table-responsive">
                        <table id="table-rekap-rup" class="table table-hover table-rekap-rup w-100">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="th-satker" style="width: 35px;">No.</th>
                                    <th rowspan="2" class="th-satker col-satker">Satker</th>
                                    <th colspan="5" class="th-pagu">Pagu RUP Terumumkan</th>
                                    <th colspan="5" class="th-paket">Jumlah Paket RUP</th>
                                    <th colspan="4" class="th-jenis">Jenis Pengadaan</th>
                                    <th colspan="6" class="th-metode">Metode Pengadaan</th>
                                </tr>
                                <tr>
                                    <!-- Sub Header Pagu -->
                                    <th class="th-sub-pagu">Penyedia</th>
                                    <th class="th-sub-pagu">%</th>
                                    <th class="th-sub-pagu">Swakelola</th>
                                    <th class="th-sub-pagu">%</th>
                                    <th class="th-sub-pagu">Total Pagu</th>

                                    <!-- Sub Header Paket -->
                                    <th class="th-sub-paket">Penyedia</th>
                                    <th class="th-sub-paket">%</th>
                                    <th class="th-sub-paket">Swakelola</th>
                                    <th class="th-sub-paket">%</th>
                                    <th class="th-sub-paket">Jumlah Paket</th>

                                    <!-- Sub Header Jenis Pengadaan -->
                                    <th class="th-sub-jenis" title="Barang">B</th>
                                    <th class="th-sub-jenis" title="Jasa Lainnya">JL</th>
                                    <th class="th-sub-jenis" title="Pekerjaan Konstruksi">PK</th>
                                    <th class="th-sub-jenis" title="Jasa Konsultansi">JK</th>

                                    <!-- Sub Header Metode Pengadaan -->
                                    <th class="th-sub-metode" title="Pengadaan Langsung">PL</th>
                                    <th class="th-sub-metode" title="Penunjukan Langsung">Juksung</th>
                                    <th class="th-sub-metode" title="E-Purchasing">E-Purchasing</th>
                                    <th class="th-sub-metode" title="Seleksi">Seleksi</th>
                                    <th class="th-sub-metode" title="Tender">Tender</th>
                                    <th class="th-sub-metode" title="Dikecualikan">Dikecualikan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1; 
                                foreach($summaryRekap as $row): 
                                ?>
                                <tr data-satker="<?= esc($row['nama_satker']) ?>">
                                    <td class="text-center font-monospace text-muted"><?= $no++ ?></td>
                                    <td class="col-satker fw-bold text-dark">
                                        <div><?= esc($row['nama_satker']) ?></div>
                                        <?php if(!empty($row['kd_satker'])): ?>
                                        <span class="badge bg-label-secondary font-monospace" style="font-size: 0.7rem;"><?= esc($row['kd_satker']) ?></span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Pagu -->
                                    <td class="text-end font-monospace"><?= number_format($row['pagu_penyedia'], 2, ',', '.') ?></td>
                                    <td class="text-end font-monospace text-muted"><?= number_format($row['pct_pagu_penyedia'], 2, ',', '.') ?>%</td>
                                    <td class="text-end font-monospace"><?= number_format($row['pagu_swakelola'], 2, ',', '.') ?></td>
                                    <td class="text-end font-monospace text-muted"><?= number_format($row['pct_pagu_swakelola'], 2, ',', '.') ?>%</td>
                                    <td class="text-end font-monospace fw-bold text-success"><?= number_format($row['total_pagu'], 2, ',', '.') ?></td>

                                    <!-- Paket -->
                                    <td class="text-center font-monospace"><?= number_format($row['paket_penyedia'], 0, ',', '.') ?></td>
                                    <td class="text-end font-monospace text-muted"><?= number_format($row['pct_paket_penyedia'], 2, ',', '.') ?>%</td>
                                    <td class="text-center font-monospace"><?= number_format($row['paket_swakelola'], 0, ',', '.') ?></td>
                                    <td class="text-end font-monospace text-muted"><?= number_format($row['pct_paket_swakelola'], 2, ',', '.') ?>%</td>
                                    <td class="text-center font-monospace fw-bold text-primary"><?= number_format($row['total_paket'], 0, ',', '.') ?></td>

                                    <!-- Jenis Pengadaan -->
                                    <td class="text-center font-monospace <?= $row['b_cnt'] > 0 ? 'fw-bold text-dark' : 'text-muted' ?>"><?= $row['b_cnt'] > 0 ? $row['b_cnt'] : '-' ?></td>
                                    <td class="text-center font-monospace <?= $row['jl_cnt'] > 0 ? 'fw-bold text-dark' : 'text-muted' ?>"><?= $row['jl_cnt'] > 0 ? $row['jl_cnt'] : '-' ?></td>
                                    <td class="text-center font-monospace <?= $row['pk_cnt'] > 0 ? 'fw-bold text-dark' : 'text-muted' ?>"><?= $row['pk_cnt'] > 0 ? $row['pk_cnt'] : '-' ?></td>
                                    <td class="text-center font-monospace <?= $row['jk_cnt'] > 0 ? 'fw-bold text-dark' : 'text-muted' ?>"><?= $row['jk_cnt'] > 0 ? $row['jk_cnt'] : '-' ?></td>

                                    <!-- Metode Pengadaan -->
                                    <td class="text-center font-monospace <?= $row['pl_cnt'] > 0 ? 'fw-bold text-dark' : 'text-muted' ?>"><?= $row['pl_cnt'] > 0 ? $row['pl_cnt'] : '-' ?></td>
                                    <td class="text-center font-monospace <?= $row['juksung_cnt'] > 0 ? 'fw-bold text-dark' : 'text-muted' ?>"><?= $row['juksung_cnt'] > 0 ? $row['juksung_cnt'] : '-' ?></td>
                                    <td class="text-center font-monospace <?= $row['epurchasing_cnt'] > 0 ? 'fw-bold text-dark' : 'text-muted' ?>"><?= $row['epurchasing_cnt'] > 0 ? $row['epurchasing_cnt'] : '-' ?></td>
                                    <td class="text-center font-monospace <?= $row['seleksi_cnt'] > 0 ? 'fw-bold text-dark' : 'text-muted' ?>"><?= $row['seleksi_cnt'] > 0 ? $row['seleksi_cnt'] : '-' ?></td>
                                    <td class="text-center font-monospace <?= $row['tender_cnt'] > 0 ? 'fw-bold text-dark' : 'text-muted' ?>"><?= $row['tender_cnt'] > 0 ? $row['tender_cnt'] : '-' ?></td>
                                    <td class="text-center font-monospace <?= $row['dikecualikan_cnt'] > 0 ? 'fw-bold text-dark' : 'text-muted' ?>"><?= $row['dikecualikan_cnt'] > 0 ? $row['dikecualikan_cnt'] : '-' ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="2" class="text-center fw-bold">TOTAL KESELURUHAN</th>
                                    <th class="text-end font-monospace">Rp <?= number_format($totalsRekap['pagu_penyedia'] ?? 0, 2, ',', '.') ?></th>
                                    <th class="text-end font-monospace text-muted"><?= $totalsRekap['pct_pagu_penyedia'] ?? 0 ?>%</th>
                                    <th class="text-end font-monospace">Rp <?= number_format($totalsRekap['pagu_swakelola'] ?? 0, 2, ',', '.') ?></th>
                                    <th class="text-end font-monospace text-muted"><?= $totalsRekap['pct_pagu_swakelola'] ?? 0 ?>%</th>
                                    <th class="text-end font-monospace fw-bold text-success">Rp <?= number_format($totalsRekap['total_pagu'] ?? 0, 2, ',', '.') ?></th>

                                    <th class="text-center font-monospace"><?= number_format($totalsRekap['paket_penyedia'] ?? 0, 0, ',', '.') ?></th>
                                    <th class="text-end font-monospace text-muted"><?= $totalsRekap['pct_paket_penyedia'] ?? 0 ?>%</th>
                                    <th class="text-center font-monospace"><?= number_format($totalsRekap['paket_swakelola'] ?? 0, 0, ',', '.') ?></th>
                                    <th class="text-end font-monospace text-muted"><?= $totalsRekap['pct_paket_swakelola'] ?? 0 ?>%</th>
                                    <th class="text-center font-monospace fw-bold text-primary"><?= number_format($totalsRekap['total_paket'] ?? 0, 0, ',', '.') ?></th>

                                    <th class="text-center font-monospace"><?= number_format($totalsRekap['b_cnt'] ?? 0, 0, ',', '.') ?></th>
                                    <th class="text-center font-monospace"><?= number_format($totalsRekap['jl_cnt'] ?? 0, 0, ',', '.') ?></th>
                                    <th class="text-center font-monospace"><?= number_format($totalsRekap['pk_cnt'] ?? 0, 0, ',', '.') ?></th>
                                    <th class="text-center font-monospace"><?= number_format($totalsRekap['jk_cnt'] ?? 0, 0, ',', '.') ?></th>

                                    <th class="text-center font-monospace"><?= number_format($totalsRekap['pl_cnt'] ?? 0, 0, ',', '.') ?></th>
                                    <th class="text-center font-monospace"><?= number_format($totalsRekap['juksung_cnt'] ?? 0, 0, ',', '.') ?></th>
                                    <th class="text-center font-monospace"><?= number_format($totalsRekap['epurchasing_cnt'] ?? 0, 0, ',', '.') ?></th>
                                    <th class="text-center font-monospace"><?= number_format($totalsRekap['seleksi_cnt'] ?? 0, 0, ',', '.') ?></th>
                                    <th class="text-center font-monospace"><?= number_format($totalsRekap['tender_cnt'] ?? 0, 0, ',', '.') ?></th>
                                    <th class="text-center font-monospace"><?= number_format($totalsRekap['dikecualikan_cnt'] ?? 0, 0, ',', '.') ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- ==================== TAB 2: ANALISIS KETERUMUMAN & ANOMALI ==================== -->
                <div class="tab-pane fade" id="tab-analisis" role="tabpanel" aria-labelledby="tab-analisis-btn">
                    <!-- Filter Toolbar Tab 2 -->
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 p-3 bg-light rounded-3 border">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="small fw-bold text-muted"><i class="bx bx-buildings me-1"></i>OPD:</span>
                            <?php if(!$isRestrictedRole): ?>
                            <select id="filter-opd-analisis" class="form-select form-select-sm" style="width: auto; max-width: 260px;">
                                <option value="all" <?= ($selectedOpd === 'all' || empty($selectedOpd)) ? 'selected' : '' ?>>Semua OPD</option>
                                <?php foreach($availableOpds as $opd): ?>
                                <option value="<?= esc($opd) ?>" <?= $opd === $selectedOpd ? 'selected' : '' ?>><?= esc($opd) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php else: ?>
                            <input type="text" class="form-control form-control-sm" value="<?= esc($summaryRekap[0]['nama_satker'] ?? 'Satker Anda') ?>" readonly style="width: auto;">
                            <input type="hidden" id="filter-opd-analisis" value="all">
                            <?php endif; ?>

                            <span class="small fw-bold text-muted ms-md-2"><i class="bx bx-filter-alt me-1"></i>Status:</span>
                            <select id="filter-status-analisis" class="form-select form-select-sm" style="width: auto;">
                                <option value="">Semua Status</option>
                                <option value="non_pengadaan">⚠️ Hanya Temuan Non-Pengadaan (Harus Dihapus)</option>
                                <option value="anomali">Hanya OPD Anomali Pagu</option>
                                <option value="kritis">Kritis (< 50%)</option>
                                <option value="sedang">Sedang (50% - 99%)</option>
                                <option value="baik">Baik (≥ 100%)</option>
                            </select>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-expand-all-analisis">
                                    <i class="bx bx-expand-vertical me-1"></i> Expand All
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-collapse-all-analisis">
                                    <i class="bx bx-collapse-vertical me-1"></i> Collapse All
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover w-100" id="table-analisis-rup">
                            <thead class="table-light">
                                <tr>
                                    <th width="30"></th>
                                    <th>Nama OPD / Satker</th>
                                    <th>Pagu Master (Kegiatan)</th>
                                    <th>Pagu Terumumkan (SIRUP)</th>
                                    <th width="160">Persentase (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($summaryAnalisis as $s): ?>
                                <tr data-details='<?= htmlspecialchars(json_encode($s['details']), ENT_QUOTES, 'UTF-8') ?>' 
                                    data-kd-satker='<?= htmlspecialchars($s['kd_satker'], ENT_QUOTES) ?>'
                                    data-nama-satker='<?= htmlspecialchars($s['nama_satker'], ENT_QUOTES) ?>'
                                    data-percent='<?= $s['percent'] ?>'
                                    data-has-anomaly='<?= !empty($s['has_anomaly']) ? '1' : '0' ?>'
                                    data-has-non-pengadaan='<?= !empty($s['has_non_pengadaan']) ? '1' : '0' ?>'>
                                    <td class="details-control text-center align-middle">
                                        <button class="btn btn-sm btn-icon btn-outline-primary rounded-circle">
                                            <i class="bx bx-plus"></i>
                                        </button>
                                    </td>
                                    <td class="align-middle">
                                        <div class="fw-bold text-dark d-flex align-items-center gap-2 flex-wrap">
                                            <?= $s['nama_satker'] ?>
                                            <?php if(!empty($s['has_non_pengadaan'])): ?>
                                                <span class="badge bg-danger animate__animated animate__pulse animate__infinite" style="font-size: 0.65rem;" title="Teridentifikasi <?= $s['non_pengadaan_count'] ?> paket non-pengadaan senilai Rp <?= number_format($s['non_pengadaan_pagu'], 0, ',', '.') ?> di SiRUP yang harus dibatalkan/dihapus">
                                                    <i class="bx bx-shield-x me-1"></i> <?= $s['non_pengadaan_count'] ?> Temuan Non-Pengadaan
                                                </span>
                                            <?php elseif(!empty($s['has_anomaly'])): ?>
                                                <span class="badge bg-label-warning" style="font-size: 0.65rem;" title="Terdapat anomali distribusi pagu pada rincian program">
                                                    <i class="bx bx-error-alt me-1"></i> Anomali Pagu
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted font-monospace"><?= $s['kd_satker_str'] ?></small>
                                    </td>
                                    <td class="align-middle">
                                        <div class="fw-bold font-monospace"><?= number_format($s['master_pagu'], 0, ',', '.') ?></div>
                                    </td>
                                    <td class="align-middle">
                                        <div class="fw-bold text-primary font-monospace"><?= number_format($s['rup_pagu'], 0, ',', '.') ?></div>
                                        <small class="text-muted"><?= $s['rup_paket'] ?> Paket Terumumkan</small>
                                        <?php if(!empty($s['has_non_pengadaan'])): ?>
                                        <div class="text-danger extra-small fw-semibold mt-1" style="font-size: 0.72rem;">
                                            <i class="bx bx-error-circle me-1"></i>Non-Pengadaan: Rp <?= number_format($s['non_pengadaan_pagu'], 0, ',', '.') ?> (<?= $s['non_pengadaan_count'] ?> paket)
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="align-middle">
                                        <div class="d-flex align-items-center">
                                            <div class="progress w-100 me-2" style="height: 8px;">
                                                <div class="progress-bar <?= $s['percent'] >= 100 ? 'bg-success' : ($s['percent'] >= 50 ? 'bg-primary' : 'bg-danger') ?>" 
                                                     role="progressbar" style="width: <?= min(100, $s['percent']) ?>%"></div>
                                            </div>
                                            <small class="fw-bold font-monospace"><?= $s['percent'] ?>%</small>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Modal Data Hasil Kaji Ulang RUP -->
<div class="modal fade" id="modalHasilKajiUlang" tabindex="-1" aria-labelledby="modalHasilKajiUlangLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-label-warning py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-sm bg-warning rounded text-white d-flex align-items-center justify-content-center">
                        <i class="bx bx-revision fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="modalHasilKajiUlangLabel">
                            Data Hasil Kaji Ulang RUP
                        </h5>
                        <small class="text-muted">Riwayat perubahan & penyesuaian paket RUP (SiRUP LKPP)</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Package Summary Card -->
                <div class="card bg-lighter border-0 mb-4 shadow-none">
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-md-3 col-sm-6">
                                <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.7rem;">Kode RUP Terpilih</small>
                                <span class="fw-bold font-monospace text-primary fs-5" id="mku-kd-rup">-</span>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.7rem;">Status Kaji Ulang</small>
                                <div id="mku-status-badge" class="mt-1">-</div>
                            </div>
                            <div class="col-md-6 col-sm-12">
                                <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.7rem;">Satuan Kerja / OPD</small>
                                <span class="fw-semibold text-dark" id="mku-satker">-</span>
                            </div>
                            <div class="col-12">
                                <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.7rem;">Nama Paket</small>
                                <div class="fw-bold text-dark fs-6" id="mku-nama-paket">-</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table Header & Count -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bx bx-list-ul me-1 text-primary"></i> Tabel Riwayat Perubahan (<span id="mku-count">0</span> Catatan)
                    </h6>
                    <span class="badge bg-label-info font-monospace" style="font-size: 0.72rem;">Sumber: SiRUP LKPP</span>
                </div>

                <div class="table-responsive border rounded shadow-xs">
                    <table class="table table-sm table-hover table-striped mb-0" style="font-size: 0.83rem;">
                        <thead class="table-light">
                            <tr>
                                <th width="5%" class="text-center fw-bold">No</th>
                                <th width="18%" class="fw-bold">Tanggal Kaji Ulang</th>
                                <th width="15%" class="text-center fw-bold">Jenis Revisi</th>
                                <th width="15%" class="fw-bold">RUP Asal (Lama)</th>
                                <th width="15%" class="fw-bold">RUP Baru</th>
                                <th width="32%" class="fw-bold">Alasan Kaji Ulang</th>
                            </tr>
                        </thead>
                        <tbody id="mku-table-body">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- DataTables JS CDN -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- DataTables Buttons & Export -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
const BASE_URL = '<?= rtrim(base_url(), '/') ?>';
const CURRENT_YEAR = '<?= esc($currentYear) ?>';

// ==================== KAJI ULANG RUP DATA SERVICE ====================
let kajiUlangMap = {};
let kajiUlangList = [];
let isKajiUlangLoaded = false;

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatKajiUlangDate(dateStr) {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch(e) {
        return dateStr;
    }
}

function getRevisiBadge(jenis) {
    const j = String(jenis || '').toUpperCase().trim();
    if (j === 'SATUKESATU') {
        return '<span class="badge bg-warning text-dark"><i class="bx bx-transfer me-1"></i>Satu ke Satu (1:1)</span>';
    } else if (j === 'SATUKEBANYAK') {
        return '<span class="badge bg-info text-white"><i class="bx bx-git-repo-forked me-1"></i>Satu ke Banyak (1:N)</span>';
    } else if (j === 'PEMBATALAN') {
        return '<span class="badge bg-danger text-white"><i class="bx bx-x-circle me-1"></i>Pembatalan</span>';
    } else if (j === 'PENGAKTIFAN') {
        return '<span class="badge bg-success text-white"><i class="bx bx-check-shield me-1"></i>Pengaktifan</span>';
    }
    return `<span class="badge bg-label-secondary">${escapeHtml(jenis || '-')}</span>`;
}

function processKajiUlangData(data) {
    kajiUlangList = Array.isArray(data) ? data : [];
    kajiUlangMap = {};
    
    kajiUlangList.forEach(item => {
        const lama = item.kd_rup_lama ? String(item.kd_rup_lama).trim() : null;
        const baru = item.kd_rup_baru ? String(item.kd_rup_baru).trim() : null;
        
        if (lama) {
            if (!kajiUlangMap[lama]) kajiUlangMap[lama] = [];
            kajiUlangMap[lama].push(item);
        }
        if (baru && baru !== lama) {
            if (!kajiUlangMap[baru]) kajiUlangMap[baru] = [];
            // Prevent duplicate insertion if both match same item
            if (!kajiUlangMap[baru].includes(item)) {
                kajiUlangMap[baru].push(item);
            }
        }
    });
    
    isKajiUlangLoaded = true;
    updateRenderedStatusCells();
}

function loadKajiUlangData() {
    // 1. Try local cached route first
    $.ajax({
        url: '<?= base_url('dashboard/kajiulang-rup-data') ?>',
        type: 'GET',
        dataType: 'json',
        timeout: 15000
    }).done(function(data) {
        if (Array.isArray(data) && data.length > 0) {
            processKajiUlangData(data);
        } else {
            fallbackDirectKajiUlangApi();
        }
    }).fail(function() {
        fallbackDirectKajiUlangApi();
    });
}

function fallbackDirectKajiUlangApi() {
    // 2. Direct fallback to official API
    const fallbackBaseUrl = '<?= rtrim(env('AJIP_API_BASE_URL', 'https://ajip-api.cloud'), '/') ?>';
    $.ajax({
        url: fallbackBaseUrl + '/api/data/kajiulang-rup',
        type: 'GET',
        dataType: 'json',
        timeout: 20000
    }).done(function(data) {
        let list = Array.isArray(data) ? data : (data.data || []);
        processKajiUlangData(list);
    }).fail(function(err) {
        console.warn('Gagal memuat data kaji ulang RUP:', err);
        isKajiUlangLoaded = true;
        updateRenderedStatusCells();
    });
}

function updateRenderedStatusCells() {
    $('.kaji-status-cell').each(function() {
        const kd = $(this).data('kd-rup');
        const nama = $(this).data('pkg-nama') || '';
        const satker = $(this).data('satker') || '';
        $(this).html(renderStatusKajiUlang(kd, nama, satker));
    });
}

function renderStatusKajiUlang(kdRup, pkgNama, satkerName) {
    if (!kdRup || kdRup === '-') {
        return '<span class="badge bg-light text-muted border" style="font-size: 0.72rem;">-</span>';
    }
    
    const kdStr = String(kdRup).trim();
    if (!isKajiUlangLoaded) {
        return `<span class="badge bg-label-secondary" style="font-size: 0.72rem;"><span class="spinner-border spinner-border-sm me-1" style="width: 0.65rem; height: 0.65rem;"></span>Cek Status...</span>`;
    }

    const items = kajiUlangMap[kdStr];
    if (items && items.length > 0) {
        const count = items.length;
        const latest = items[0];
        const j = String(latest.jenis_revisi || '').toUpperCase().trim();
        
        let badgeClass = 'bg-warning text-dark';
        let badgeIcon = 'bx-revision';
        let badgeText = 'Ada Perubahan';
        
        if (j === 'SATUKESATU') {
            badgeText = count > 1 ? `Revisi (1:1) [${count}x]` : 'Revisi (1:1)';
        } else if (j === 'SATUKEBANYAK') {
            badgeClass = 'bg-info text-white';
            badgeIcon = 'bx-git-repo-forked';
            badgeText = count > 1 ? `Revisi (1:N) [${count}x]` : 'Revisi (1:N)';
        } else if (j === 'PEMBATALAN') {
            badgeClass = 'bg-danger text-white';
            badgeIcon = 'bx-x-circle';
            badgeText = 'Dibatalkan';
        } else if (j === 'PENGAKTIFAN') {
            badgeClass = 'bg-success text-white';
            badgeIcon = 'bx-check-shield';
            badgeText = 'Diaktifkan';
        }

        return `
            <div class="d-inline-flex align-items-center justify-content-center gap-1 flex-wrap">
                <span class="badge ${badgeClass} shadow-xs" style="font-size: 0.72rem;" title="Paket ini mengalami perubahan/kaji ulang pada SiRUP (${count} catatan)">
                    <i class="bx ${badgeIcon} me-1"></i>${badgeText}
                </span>
                <button type="button" 
                        class="btn btn-xs btn-outline-warning btn-view-kajiulang py-1 px-2 shadow-xs" 
                        data-kd-rup="${escapeHtml(kdStr)}" 
                        data-pkg-nama="${escapeHtml(pkgNama || '')}"
                        data-satker="${escapeHtml(satkerName || '')}"
                        title="Klik untuk melihat tabel rincian data hasil kaji ulang">
                    <i class="bx bx-history me-1"></i>Hasil Kaji Ulang
                </button>
            </div>
        `;
    }

    return `
        <span class="badge bg-label-success" style="font-size: 0.72rem;" title="RUP normal / tidak mengalami kaji ulang">
            <i class="bx bx-check me-1"></i>Normal
        </span>
    `;
}

function showKajiUlangModal(kdRup, pkgNama, satkerName) {
    const kdStr = String(kdRup).trim();
    const items = kajiUlangMap[kdStr] || [];
    
    $('#mku-kd-rup').text('#' + kdStr);
    $('#mku-nama-paket').text(pkgNama || '-');
    $('#mku-satker').text(satkerName || (items.length > 0 ? (items[0].nama_satker || '-') : '-'));
    $('#mku-count').text(items.length);
    
    if (items.length > 0) {
        const j = String(items[0].jenis_revisi || '').toUpperCase().trim();
        $('#mku-status-badge').html(getRevisiBadge(j));
    } else {
        $('#mku-status-badge').html('<span class="badge bg-label-secondary">Tidak Ada Riwayat</span>');
    }
    
    let rowsHtml = '';
    if (items.length === 0) {
        rowsHtml = `<tr><td colspan="6" class="text-center py-4 text-muted"><i class="bx bx-info-circle me-1"></i> Tidak ditemukan catatan kaji ulang untuk paket ini.</td></tr>`;
    } else {
        items.forEach((item, i) => {
            const no = i + 1;
            const tgl = formatKajiUlangDate(item.tgl_kaji_ulang);
            const revisiBadge = getRevisiBadge(item.jenis_revisi);
            const alasan = item.alasan_kajiulang ? escapeHtml(item.alasan_kajiulang) : '<em class="text-muted">Tidak ada keterangan</em>';
            
            const isLama = String(item.kd_rup_lama).trim() === kdStr;
            const isBaru = String(item.kd_rup_baru).trim() === kdStr;
            
            let lamaHtml = `<span class="font-monospace text-muted">#${item.kd_rup_lama || '-'}</span>`;
            if (isLama) {
                lamaHtml = `<span class="fw-bold text-primary font-monospace">#${item.kd_rup_lama}</span> <span class="badge bg-label-primary ms-1" style="font-size: 0.65rem;">Paket Ini</span>`;
            }
            
            let baruHtml = `<span class="font-monospace text-muted">#${item.kd_rup_baru || '-'}</span>`;
            if (isBaru) {
                baruHtml = `<span class="fw-bold text-success font-monospace">#${item.kd_rup_baru}</span> <span class="badge bg-label-success ms-1" style="font-size: 0.65rem;">Hasil Baru</span>`;
            }
            
            rowsHtml += `
                <tr>
                    <td class="text-center align-middle fw-semibold">${no}</td>
                    <td class="align-middle text-nowrap"><i class="bx bx-calendar me-1 text-muted"></i>${tgl}</td>
                    <td class="text-center align-middle">${revisiBadge}</td>
                    <td class="align-middle">${lamaHtml}</td>
                    <td class="align-middle">${baruHtml}</td>
                    <td class="align-middle" style="white-space: normal !important; word-wrap: break-word;">${alasan}</td>
                </tr>
            `;
        });
    }
    
    $('#mku-table-body').html(rowsHtml);
    const modalEl = document.getElementById('modalHasilKajiUlang');
    if (modalEl) {
        const modalObj = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modalObj.show();
    }
}

// Format child row for Analisis Tab
function formatAnalisisRow(d, rowId, satkerName) {
    let details = [];
    try {
        details = JSON.parse(d);
    } catch(e) {
        console.error("Failed to parse details:", e);
        return '<div class="alert alert-danger m-3">Gagal memuat rincian data.</div>';
    }
    
    let html = `
        <div class="p-3 bg-light rounded-3 ms-4 me-4 mb-3 border shadow-sm" id="prog-container-${rowId}">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2 border-bottom pb-2">
                <h6 class="fw-bold mb-0 text-dark"><i class="bx bx-subdirectory-right me-1 text-primary"></i> Rincian Program Kerja (${escapeHtml(satkerName)})</h6>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="${BASE_URL}/dashboard/export-non-pengadaan?year=${CURRENT_YEAR}&opd=${encodeURIComponent(satkerName)}" class="btn btn-sm btn-outline-danger shadow-sm" title="Unduh CSV daftar paket anomali non-pengadaan khusus ${escapeHtml(satkerName)}">
                        <i class="bx bxs-file-export me-1"></i>Ekspor Anomali OPD Ini
                    </a>
                    <button type="button" class="btn btn-sm btn-primary btn-analyze-all" data-container="prog-container-${rowId}">
                        <i class="bx bx-bot me-1"></i>Analisa Semua Anomali (AI)
                    </button>
                </div>
            </div>
            <table class="table table-sm table-bordered bg-white w-100">
                <thead class="table-dark">
                    <tr>
                        <th width="40" class="text-center">Paket</th>
                        <th>Nama Program Kerja</th>
                        <th class="text-end">Pagu Master</th>
                        <th class="text-end">Pagu RUP</th>
                        <th class="text-center" width="130">Keterumuman</th>
                    </tr>
                </thead>
                <tbody>`;
    
    details.forEach((p, idx) => {
        let statusClass = p.percent >= 100 ? 'text-success' : (p.percent > 0 ? 'text-primary' : 'text-danger');
        let icon = p.percent >= 100 ? 'bx-check-double' : (p.percent > 0 ? 'bx-check' : 'bx-x');
        
        let hasPackages = p.packages && p.packages.length > 0;
        let expandBtn = hasPackages ? `<button type="button" class="btn btn-xs btn-icon btn-outline-secondary rounded-circle btn-expand-prog" data-target="pkg-${rowId}-${idx}"><i class="bx bx-plus"></i></button>` : '';
        let isAnomaly = p.pagu_rup > p.pagu_master || (p.percent < 50 && p.pagu_master > 0);
        let anomalyBadge = p.pagu_rup > p.pagu_master ? ' <span class="badge bg-label-danger ms-1" style="font-size: 0.6rem;"><i class="bx bx-error-alt me-1"></i>Anomali</span>' : '';
        let nonPengadaanProgBadge = (p.non_pengadaan_count && p.non_pengadaan_count > 0) ? ` <span class="badge bg-danger ms-1 animate__animated animate__pulse animate__infinite" style="font-size: 0.65rem;" title="Teridentifikasi ${p.non_pengadaan_count} paket Non-Pengadaan senilai Rp ${new Intl.NumberFormat('id-ID').format(p.non_pengadaan_pagu)}"><i class="bx bx-shield-x me-1"></i>${p.non_pengadaan_count} Non-Pengadaan</span>` : '';
        let singleAiBtn = isAnomaly ? `<button type="button" class="btn btn-xs btn-outline-primary ms-2 btn-analyze-single" data-target="ai-res-${rowId}-${idx}" title="Analisa AI untuk program ini"><i class="bx bx-bot me-1"></i>Analisa AI</button>` : '';
        
        let topPkgs = (p.packages || []).slice().sort((a, b) => (parseFloat(b.pagu) || 0) - (parseFloat(a.pagu) || 0));
        let packagesSummary = topPkgs.slice(0, 8).map(pkg => ({
            kd_rup: pkg.kd_rup,
            nama_paket: pkg.nama_paket,
            pagu: pkg.pagu,
            tipe: pkg.tipe,
            mak: pkg.mak || '-'
        }));
        let packagesJson = JSON.stringify(packagesSummary);
        if (topPkgs.length > 8) packagesJson += ` (Total ${topPkgs.length} paket)`;
        
        html += `
            <tr>
                <td class="text-center align-middle">${expandBtn}</td>
                <td class="fw-bold text-dark align-middle">${p.name}${anomalyBadge}${nonPengadaanProgBadge}${singleAiBtn}</td>
                <td class="text-end align-middle font-monospace">${new Intl.NumberFormat('id-ID').format(p.pagu_master)}</td>
                <td class="text-end align-middle font-monospace fw-bold">${new Intl.NumberFormat('id-ID').format(p.pagu_rup)}</td>
                <td class="text-center align-middle">
                    <span class="badge bg-label-${p.percent >= 100 ? 'success' : (p.percent > 0 ? 'primary' : 'danger')} font-monospace">
                        <i class="bx ${icon} me-1"></i> ${p.percent}%
                    </span>
                </td>
            </tr>`;
            
        if (isAnomaly) {
            html += `<tr id="ai-res-${rowId}-${idx}" style="display: none;" class="bg-label-primary ai-anomaly-row"
                data-satker="${encodeURIComponent(satkerName)}" 
                data-program="${encodeURIComponent(p.name)}" 
                data-master="${p.pagu_master}" 
                data-rup="${p.pagu_rup}" 
                data-packages="${encodeURIComponent(packagesJson)}">
                <td class="border-0"></td>
                <td colspan="4" class="p-0 border-0">
                    <div class="p-3 border-start border-4 border-primary ms-4 mb-3 mt-2 bg-white rounded shadow-sm">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bx bx-bot text-primary fs-4 me-2"></i>
                            <h6 class="fw-bold text-primary mb-0">Analisis AI (AJIP)</h6>
                        </div>
                        <div class="ai-content text-dark" style="font-size: 0.9rem;">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Sedang menganalisa...
                        </div>
                    </div>
                </td>
            </tr>`;
        }
            
        if (hasPackages) {
            let nonPkgCount = (p.packages || []).filter(x => x.is_non_pengadaan).length;
            html += `<tr id="pkg-${rowId}-${idx}" style="display: none;" class="bg-label-secondary pkg-row">
                <td class="border-0"></td>
                <td colspan="4" class="p-0 border-0">
                    <div class="p-3 border-start border-4 border-info ms-4 mb-3 mt-2 bg-white rounded shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="small fw-bold text-info mb-0 text-uppercase"><i class="bx bx-layer me-2"></i>Rincian Paket RUP (${p.packages.length} Paket)</h6>
                            ${nonPkgCount > 0 ? `<span class="badge bg-label-danger"><i class="bx bx-error-circle me-1"></i>${nonPkgCount} Paket Terindikasi Non-Pengadaan</span>` : ''}
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-bordered mb-0 w-100" style="font-size: 0.85rem;">
                                <thead class="table-info">
                                    <tr>
                                        <th width="12%" class="text-dark fw-bold">Kode RUP</th>
                                        <th class="text-dark fw-bold">Nama Paket</th>
                                        <th width="8%" class="text-center text-dark fw-bold">Tipe</th>
                                        <th width="14%" class="text-end text-dark fw-bold">Pagu Paket</th>
                                        <th width="22%" class="text-center text-dark fw-bold">Temuan Non-Pengadaan</th>
                                        <th width="12%" class="text-center text-dark fw-bold">Status</th>
                                    </tr>
                                </thead>
                                <tbody>`;
            p.packages.forEach(pkg => {
                let tipeBadge = pkg.tipe === 'Penyedia' ? 'bg-label-primary' : (pkg.tipe === 'Swakelola' ? 'bg-label-warning' : 'bg-label-secondary');
                let statusHtml = renderStatusKajiUlang(pkg.kd_rup, pkg.nama_paket, satkerName);
                let isNonPengadaan = pkg.is_non_pengadaan === true || pkg.is_non_pengadaan === 1 || pkg.is_non_pengadaan === '1';
                let rowClass = isNonPengadaan ? 'table-danger bg-opacity-10' : '';
                let nonPengadaanCell = '';
                if (isNonPengadaan) {
                    let isAiVerified = (pkg.alasan_non_pengadaan || '').indexOf('[AI Terverifikasi]') !== -1;
                    nonPengadaanCell = `
                        <span class="badge bg-danger text-white px-2 py-1 mb-1" style="font-size: 0.68rem;">
                            <i class="bx bx-shield-x me-1"></i>NON-PENGADAAN
                        </span>
                        <div class="fw-bold text-danger" style="font-size: 0.72rem;" title="${escapeHtml(pkg.kategori_non_pengadaan || 'Non-Pengadaan')}">
                            ${escapeHtml(pkg.kategori_non_pengadaan || 'Non-Pengadaan')}
                        </div>
                        <div class="text-muted extra-small mt-1" style="font-size: 0.65rem;" title="${escapeHtml(pkg.alasan_non_pengadaan || 'Harus dibatalkan dari SiRUP')}">
                            ${escapeHtml(pkg.alasan_non_pengadaan || 'Harus dibatalkan dari SiRUP')}
                        </div>
                        <div class="mt-1 d-flex align-items-center justify-content-center gap-1">
                            <span class="badge bg-label-danger" style="font-size: 0.62rem;">Usul Hapus SiRUP</span>
                            ${isAiVerified ? `
                                <span class="badge bg-label-info" style="font-size: 0.60rem;" title="Sudah diverifikasi model AI"><i class="bx bx-bot"></i> AI</span>
                            ` : `
                                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1 btn-verify-pkg-ai" 
                                    style="font-size: 0.60rem; line-height: 1.2;"
                                    data-kd-rup="${escapeHtml(pkg.kd_rup)}"
                                    data-nama="${escapeHtml(pkg.nama_paket)}"
                                    data-mak="${escapeHtml(pkg.mak || '')}"
                                    data-tipe="${escapeHtml(pkg.tipe || 'Penyedia')}"
                                    title="Minta AI memverifikasi status paket ini">
                                    <i class="bx bx-bot"></i> Cek AI
                                </button>
                            `}
                        </div>
                    `;
                } else {
                    let isAiVerified = (pkg.alasan_non_pengadaan || '').indexOf('[AI Terverifikasi]') !== -1;
                    nonPengadaanCell = `
                        <span class="badge bg-label-success px-2 py-1" style="font-size: 0.68rem;">
                            <i class="bx bx-check-circle me-1"></i>PENGADAAN
                        </span>
                        <div class="text-muted extra-small mt-1" style="font-size: 0.68rem;">Normal (PBJP)</div>
                        ${isAiVerified ? `<div class="text-info extra-small mt-1" style="font-size: 0.62rem;"><i class="bx bx-bot me-1"></i>AI Verified</div>` : ''}
                    `;
                }

                html += `<tr class="${rowClass}">
                    <td class="align-middle font-monospace"><span class="text-muted"><i class="bx bx-hash"></i>${escapeHtml(pkg.kd_rup)}</span></td>
                    <td class="align-middle" style="min-width: 240px; white-space: normal !important; word-wrap: break-word;">
                        <div class="fw-semibold text-dark mb-1">${escapeHtml(pkg.nama_paket)}</div>
                        <div class="text-muted extra-small" style="font-size: 0.7rem;"><i class="bx bx-barcode-reader me-1"></i>MAK: ${escapeHtml(pkg.mak) || '-'}</div>
                    </td>
                    <td class="align-middle text-center"><span class="badge ${tipeBadge} px-2 py-1" style="font-size: 0.7rem;">${pkg.tipe}</span></td>
                    <td class="align-middle text-end font-monospace"><span class="fw-bold text-primary">Rp ${new Intl.NumberFormat('id-ID').format(pkg.pagu)}</span></td>
                    <td class="align-middle text-center">${nonPengadaanCell}</td>
                    <td class="align-middle text-center kaji-status-cell" 
                        data-kd-rup="${escapeHtml(pkg.kd_rup)}" 
                        data-pkg-nama="${escapeHtml(pkg.nama_paket)}" 
                        data-satker="${escapeHtml(satkerName)}">
                        ${statusHtml}
                    </td>
                </tr>`;
            });
            html += `           </tbody>
                            </table>
                        </div>
                    </div>
                </td>
            </tr>`;
        }
    });

    html += `</tbody></table></div>`;
    return html;
}

$(document).ready(function() {
    // Muat data kaji ulang RUP secara asinkron
    loadKajiUlangData();

    // Event Klik Lihat Detail Hasil Kaji Ulang
    $(document).on('click', '.btn-view-kajiulang', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const kdRup = $(this).data('kd-rup');
        const pkgNama = $(this).data('pkg-nama');
        const satker = $(this).data('satker');
        showKajiUlangModal(kdRup, pkgNama, satker);
    });

    // Global Year Change
    $('#select-global-year').on('change', function() {
        const opdVal = $('#filter-opd-rekap').val();
        let url = '<?= base_url('dashboard/rup-opd') ?>?year=' + $(this).val();
        if (opdVal && opdVal !== 'all') {
            url += '&opd=' + encodeURIComponent(opdVal);
        }
        window.location.href = url;
    });

    // 1. DATA TABLE TAB 1 (REKAPITULASI & PROFIL)
    const tableRekap = $('#table-rekap-rup').DataTable({
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
        dom: '<"d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2"Bl>rtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="far fa-file-excel mr-1"></i> Export Excel',
                title: 'REKAPITULASI DAN PROFIL RUP KABUPATEN LOMBOK BARAT TAHUN <?= $currentYear ?>',
                messageTop: 'Tanggal Unduh: ' + new Date().toLocaleString('id-ID'),
                className: 'btn btn-outline-success btn-sm me-2',
                footer: true
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="far fa-file-pdf mr-1"></i> Export PDF',
                title: 'REKAPITULASI DAN PROFIL RUP KABUPATEN LOMBOK BARAT TAHUN <?= $currentYear ?>',
                orientation: 'landscape',
                pageSize: 'A3',
                className: 'btn btn-outline-danger btn-sm me-2',
                footer: true
            },
            {
                extend: 'print',
                text: '<i class="bx bx-printer mr-1"></i> Cetak',
                title: 'REKAPITULASI DAN PROFIL RUP KABUPATEN LOMBOK BARAT TAHUN <?= $currentYear ?>',
                className: 'btn btn-outline-secondary btn-sm',
                footer: true
            }
        ],
        language: {
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ OPD",
            zeroRecords: "Tidak ada data Satker yang sesuai",
            paginate: { next: "Next", previous: "Prev" }
        },
        order: [[1, 'asc']],
        columnDefs: [
            { orderable: false, targets: [0] }
        ]
    });

    // Dinamis update nomor urut saat tabel rekap difilter / di-sort
    tableRekap.on('draw.dt', function () {
        var info = tableRekap.page.info();
        tableRekap.column(0, { search: 'applied', order: 'applied', page: 'current' }).nodes().each(function (cell, i) {
            cell.innerHTML = info.start + i + 1;
        });
    });

    // Custom Search Tab 1
    $('#custom-search-rekap').on('keyup', function() {
        tableRekap.search(this.value).draw();
    });

    // Custom Filter Tab 1 (Filter OPD)
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (settings.nTable.id !== 'table-rekap-rup') return true;
        
        const selected = $('#filter-opd-rekap').val();
        if (!selected || selected === 'all') return true;
        
        const node = tableRekap.row(dataIndex).node();
        if (!node) return true;
        
        const satker = $(node).attr('data-satker') || '';
        return satker.trim() === selected.trim();
    });

    // Filter OPD Tab 1
    $('#filter-opd-rekap').on('change', function() {
        const val = $(this).val();
        if ($('#filter-opd-analisis').length) {
            $('#filter-opd-analisis').val(val);
            tableAnalisis.draw();
        }
        tableRekap.draw();
    });

    // Reset Tab 1
    $('#btn-reset-rekap').on('click', function() {
        $('#custom-search-rekap').val('');
        $('#filter-opd-rekap').val('all');
        if ($('#filter-opd-analisis').length) {
            $('#filter-opd-analisis').val('all');
            tableAnalisis.draw();
        }
        tableRekap.search('').draw();
    });

    // 2. DATA TABLE TAB 2 (ANALISIS KETERUMUMAN & ANOMALI)
    const tableAnalisis = $('#table-analisis-rup').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[4, 'asc']], // Lowest percentage first
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Cari OPD...",
            lengthMenu: "_MENU_",
        },
        dom: '<"row"<"col-md-6"l><"col-md-6"f>>t<"row"<"col-md-6"i><"col-md-6"p>>'
    });

    // Custom Filter Tab 2
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (settings.nTable.id !== 'table-analisis-rup') return true;
        
        var node = tableAnalisis.row(dataIndex).node();
        if (!node) return true;

        var filterOpd = $('#filter-opd-analisis').val();
        if (filterOpd && filterOpd !== 'all') {
            var satker = $(node).attr('data-nama-satker') || '';
            if (satker.trim() !== filterOpd.trim()) {
                return false;
            }
        }
        
        var filter = $('#filter-status-analisis').val();
        if (!filter) return true;
        
        var percent = parseFloat($(node).attr('data-percent')) || 0;
        var hasAnomaly = $(node).attr('data-has-anomaly') === '1';
        var hasNonPengadaan = $(node).attr('data-has-non-pengadaan') === '1';
        
        if (filter === 'non_pengadaan') return hasNonPengadaan;
        if (filter === 'anomali') return hasAnomaly;
        if (filter === 'kritis') return percent < 50;
        if (filter === 'sedang') return percent >= 50 && percent < 100;
        if (filter === 'baik') return percent >= 100;
        return true;
    });

    $('#filter-status-analisis').on('change', function() {
        tableAnalisis.draw();
    });

    $('#filter-opd-analisis').on('change', function() {
        const val = $(this).val();
        $('#filter-opd-rekap').val(val);
        tableRekap.draw();
        tableAnalisis.draw();
    });

    // Child Row Toggle Tab 2
    $('#table-analisis-rup tbody').on('click', 'td.details-control', function () {
        var tr = $(this).closest('tr');
        var row = tableAnalisis.row(tr);
        var icon = $(this).find('i');
        var rowId = tr.attr('data-kd-satker') || row.index();
        rowId = String(rowId).replace(/[^a-zA-Z0-9_-]/g, '_');
        var satkerName = tr.attr('data-nama-satker') || 'Unknown';

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('shown');
            icon.removeClass('bx-minus').addClass('bx-plus');
        } else {
            row.child(formatAnalisisRow(tr.attr('data-details'), rowId, satkerName)).show();
            tr.addClass('shown');
            icon.removeClass('bx-plus').addClass('bx-minus');
        }
    });

    // Expand / Collapse All Tab 2
    $('#btn-expand-all-analisis').on('click', function() {
        $('#table-analisis-rup tbody tr:not(.shown)').each(function() {
            $(this).find('td.details-control').trigger('click');
        });
    });

    $('#btn-collapse-all-analisis').on('click', function() {
        $('#table-analisis-rup tbody tr.shown').each(function() {
            $(this).find('td.details-control').trigger('click');
        });
    });

    // Expand Sub-packages in Tab 2
    $(document).on('click', '.btn-expand-prog', function(e) {
        e.preventDefault();
        var targetId = $(this).data('target');
        var targetRow = document.getElementById(targetId);
        var icon = $(this).find('i');
        
        if (targetRow) {
            if (targetRow.style.display === 'none') {
                targetRow.style.display = 'table-row';
                icon.removeClass('bx-plus').addClass('bx-minus');
            } else {
                targetRow.style.display = 'none';
                icon.removeClass('bx-minus').addClass('bx-plus');
            }
        }
    });

    // AI Anomaly Analysis Event
    $(document).on('click', '.btn-analyze-all', function(e) {
        e.preventDefault();
        var btn = $(this);
        var container = $('#' + btn.data('container'));
        var anomalyRows = container.find('tr.ai-anomaly-row');
        
        if (anomalyRows.length === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Info', 'Semua program kerja pada OPD ini berstatus baik. Tidak ada anomali yang perlu dianalisa.', 'info');
            } else {
                alert('Semua program kerja pada OPD ini berstatus baik.');
            }
            return;
        }

        btn.html('<span class="spinner-border spinner-border-sm me-1"></span>Menganalisa AI...').prop('disabled', true);
        
        var requests = [];
        anomalyRows.each(function() {
            var row = $(this);
            if (row.data('loaded')) {
                row.show();
                return;
            }
            
            row.show();
            var aiContent = row.find('.ai-content');
            
            var req = $.ajax({
                url: '<?= base_url('dashboard/analyze-anomaly') ?>',
                type: 'POST',
                data: {
                    satker: decodeURIComponent(row.data('satker')),
                    program: decodeURIComponent(row.data('program')),
                    pagu_master: row.data('master'),
                    pagu_rup: row.data('rup'),
                    packages_info: decodeURIComponent(row.data('packages')),
                    "<?= csrf_token() ?>": "<?= csrf_hash() ?>"
                },
                dataType: 'json',
                success: function(res) {
                    row.data('loaded', true);
                    if (res.status === 'success') {
                        aiContent.html(res.analysis.replace(/\n/g, '<br>'));
                    } else {
                        aiContent.html('<span class="text-danger"><i class="bx bx-error-circle me-1"></i>' + res.message + '</span>');
                    }
                },
                error: function() {
                    aiContent.html('<span class="text-danger"><i class="bx bx-error-circle me-1"></i>Gagal menghubungi server AI.</span>');
                }
            });
            requests.push(req);
        });

        $.when.apply($, requests).always(function() {
            btn.html('<i class="bx bx-check-double me-1"></i>Analisa Selesai').prop('disabled', false);
        });
    });

    // Individual AI Anomaly Analysis Event
    $(document).on('click', '.btn-analyze-single', function(e) {
        e.preventDefault();
        var targetId = $(this).data('target');
        var row = $('#' + targetId);
        var btn = $(this);
        
        if (row.length === 0) return;
        
        if (row.is(':visible') && row.data('loaded')) {
            row.toggle();
            return;
        }
        
        row.show();
        if (row.data('loaded')) return;
        
        var aiContent = row.find('.ai-content');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Menganalisa...');
        
        $.ajax({
            url: '<?= base_url('dashboard/analyze-anomaly') ?>',
            type: 'POST',
            data: {
                satker: decodeURIComponent(row.data('satker')),
                program: decodeURIComponent(row.data('program')),
                pagu_master: row.data('master'),
                pagu_rup: row.data('rup'),
                packages_info: decodeURIComponent(row.data('packages')),
                "<?= csrf_token() ?>": "<?= csrf_hash() ?>"
            },
            dataType: 'json',
            success: function(res) {
                row.data('loaded', true);
                if (res.status === 'success') {
                    aiContent.html(res.analysis.replace(/\n/g, '<br>'));
                } else {
                    aiContent.html('<span class="text-danger"><i class="bx bx-error-circle me-1"></i>' + res.message + '</span>');
                }
            },
            error: function(xhr) {
                var msg = 'Gagal menghubungi server AI.';
                try {
                    var json = JSON.parse(xhr.responseText);
                    if (json && json.message) msg = json.message;
                } catch(err) {}
                aiContent.html('<span class="text-danger"><i class="bx bx-error-circle me-1"></i>' + msg + '</span>');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bx bx-bot me-1"></i>Analisa AI');
            }
        });
    });

    // Package-level AI Non-Pengadaan Verification
    $(document).on('click', '.btn-verify-pkg-ai', function(e) {
        e.preventDefault();
        var btn = $(this);
        var cell = btn.closest('td');
        var originalHtml = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" style="width:0.7rem;height:0.7rem;"></span>');

        $.ajax({
            url: '<?= base_url('dashboard/verify-non-pengadaan-ai') ?>',
            type: 'POST',
            data: {
                kd_rup: btn.data('kd-rup'),
                nama_paket: decodeURIComponent(btn.data('nama')),
                mak: btn.data('mak'),
                tipe: btn.data('tipe'),
                year: '<?= $currentYear ?>',
                "<?= csrf_token() ?>": "<?= csrf_hash() ?>"
            },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    if (res.result && !res.result.is_non_pengadaan) {
                        cell.html(`
                            <span class="badge bg-label-success px-2 py-1" style="font-size: 0.68rem;">
                                <i class="bx bx-check-circle me-1"></i>PENGADAAN
                            </span>
                            <div class="text-success extra-small mt-1" style="font-size: 0.68rem;">
                                <i class="bx bx-bot me-1"></i>Diverifikasi AI: Sah PBJP
                            </div>
                        `);
                        cell.closest('tr').removeClass('table-danger bg-opacity-10');
                    } else {
                        btn.removeClass('btn-outline-secondary').addClass('btn-label-danger').html('<i class="bx bx-check"></i> Non-PBJP');
                        alert('Hasil AI: Paket ini tetap tergolong Non-Pengadaan (' + (res.result ? res.result.kategori : '') + '). Alasan: ' + (res.result ? res.result.alasan : ''));
                    }
                } else {
                    alert('Gagal verifikasi AI: ' + res.message);
                    btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function() {
                alert('Gagal menghubungi endpoint verifikasi AI.');
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    // Re-adjust columns when tabs change
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    });
});
</script>
<?= $this->endSection() ?>
