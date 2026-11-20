<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?>
<?= $this->section('content') ?>

<style>
    /* Styling Header Tabel Progres SPSE - Warna Kalem */
    .table-progres-spse {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 0.81rem;
    }
    .table-progres-spse th {
        vertical-align: middle !important;
        text-align: center !important;
        font-weight: 600 !important;
        border: 1px solid #e2e8f0 !important;
        padding: 7px 5px !important;
        color: #334155 !important;
    }
    .table-progres-spse td {
        vertical-align: middle !important;
        border: 1px solid #f1f5f9 !important;
        padding: 5px 6px !important;
        white-space: nowrap !important;
    }
    
    /* Header Color Groups - Kalem / Soft */
    .th-satker-group { background-color: #f8fafc !important; color: #1e293b !important; }
    .th-rup-group { background-color: #eff6ff !important; color: #1e40af !important; border-bottom: 2px solid #bfdbfe !important; }
    .th-epl-group { background-color: #f0fdfa !important; color: #0f766e !important; border-bottom: 2px solid #99f6e4 !important; }
    .th-tender-group { background-color: #fff1f2 !important; color: #9f1239 !important; border-bottom: 2px solid #fecdd3 !important; }
    .th-nontender-group { background-color: #fefce8 !important; color: #854d0e !important; border-bottom: 2px solid #fef08a !important; }
    .th-swakelola-group { background-color: #f0fdf4 !important; color: #166534 !important; border-bottom: 2px solid #bbf7d0 !important; }
    .th-epurchasing-group { background-color: #faf5ff !important; color: #6b21a8 !important; border-bottom: 2px solid #e9d5ff !important; }
    .th-ket-group { background-color: #f8fafc !important; color: #475569 !important; }
    
    .th-sub { background-color: #fafafa !important; font-weight: 500 !important; font-size: 0.76rem !important; }

    /* Footer Grand Total Kalem */
    .table-progres-spse tfoot th {
        background-color: #f8fafc !important;
        color: #0f172a !important;
        font-weight: 700 !important;
        font-size: 0.82rem !important;
        border-top: 2px solid #cbd5e1 !important;
    }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Header Page -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1">
                <span class="text-muted fw-light">Data Pengadaan /</span> Monev OPD
            </h4>
            <p class="text-muted mb-0 small">Monitoring Realisasi Fisik (Paket) & Keuangan (Anggaran) Berdasarkan RUP dan Progres SPSE</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="<?= base_url('dashboard/export-monev-progres') ?>" class="btn btn-outline-success btn-sm">
                <i class="bx bxs-file-export me-1"></i> Unduh Excel
            </a>
            <a href="<?= base_url('dashboard/clear-cache') ?>" class="btn btn-outline-danger btn-sm" data-confirm="Apakah Anda yakin ingin membersihkan seluruh cache data? Data akan ditarik ulang secara fresh.">
                <i class="bx bx-refresh me-1"></i> Clear Cache
            </a>
        </div>
    </div>

    <!-- Nav Tabs for Monev OPD -->
    <ul class="nav nav-pills mb-4 gap-2" id="monevMainTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-progres-spse" data-bs-toggle="pill" data-bs-target="#pane-progres-spse" type="button" role="tab" aria-controls="pane-progres-spse" aria-selected="true">
                <i class="bx bx-table me-1"></i> Tabel Progres SPSE
                <span class="badge bg-label-primary ms-1" id="badgeTotalSatker">60 OPD</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-realisasi-rup" data-bs-toggle="pill" data-bs-target="#pane-realisasi-rup" type="button" role="tab" aria-controls="pane-realisasi-rup" aria-selected="false">
                <i class="bx bx-bar-chart-alt-2 me-1"></i> Realisasi RUP & Keuangan
            </button>
        </li>
    </ul>

    <div class="tab-content p-0" id="monevTabsContent">
        <!-- TAB 1: TABEL PROGRES SPSE -->
        <div class="tab-pane fade show active" id="pane-progres-spse" role="tabpanel" aria-labelledby="tab-progres-spse">
            <!-- Executive KPI Cards -->
            <div class="row g-3 mb-4">
                <!-- Card 1: Total Satker & RUP -->
                <div class="col-12 col-sm-6 col-xl-2">
                    <div class="card h-100 border-0 shadow-sm card-border-shadow-primary">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="avatar-initial rounded bg-label-primary p-2"><i class="bx bx-buildings fs-5"></i></span>
                                <span class="badge bg-label-primary fw-bold" id="kpiTotalSatker">60 OPD</span>
                            </div>
                            <h5 class="mb-0 fw-bold" id="kpiTotalRup">10.212</h5>
                            <small class="text-muted d-block text-truncate">Total Paket RUP</small>
                            <div class="mt-1 small text-muted" style="font-size: 0.75rem;">
                                <span class="text-primary fw-semibold" id="kpiRupP">7.785</span> Penyedia | <span class="text-success fw-semibold" id="kpiRupS">2.427</span> Swakelola
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Card 2: E-PL -->
                <div class="col-12 col-sm-6 col-xl-2">
                    <div class="card h-100 border-0 shadow-sm card-border-shadow-info">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="avatar-initial rounded bg-label-info p-2"><i class="bx bx-purchase-tag fs-5"></i></span>
                                <span class="badge bg-label-info fw-bold" id="kpiEplPct">32.80%</span>
                            </div>
                            <h5 class="mb-0 fw-bold" id="kpiEplDilaksanakan">689</h5>
                            <small class="text-muted d-block text-truncate">E-PL Dilaksanakan</small>
                            <div class="mt-1 small text-muted" style="font-size: 0.75rem;">
                                <span class="text-success fw-semibold" id="kpiEplSudah">226</span> Input | <span class="text-danger fw-semibold" id="kpiEplBelum">463</span> Belum
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Card 3: Tender -->
                <div class="col-12 col-sm-6 col-xl-2">
                    <div class="card h-100 border-0 shadow-sm card-border-shadow-danger">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="avatar-initial rounded bg-label-danger p-2"><i class="bx bx-award fs-5"></i></span>
                                <span class="badge bg-label-danger fw-bold" id="kpiTenderPct">88.89%</span>
                            </div>
                            <h5 class="mb-0 fw-bold" id="kpiTenderDilaksanakan">27</h5>
                            <small class="text-muted d-block text-truncate">Tender Dilaksanakan</small>
                            <div class="mt-1 small text-muted" style="font-size: 0.75rem;">
                                <span class="text-success fw-semibold" id="kpiTenderSudah">24</span> Input | <span class="text-danger fw-semibold" id="kpiTenderBelum">3</span> Belum
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Card 4: Non Tender -->
                <div class="col-12 col-sm-6 col-xl-2">
                    <div class="card h-100 border-0 shadow-sm card-border-shadow-warning">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="avatar-initial rounded bg-label-warning p-2"><i class="bx bx-list-check fs-5"></i></span>
                                <span class="badge bg-label-warning fw-bold" id="kpiNonTenderPct">17.82%</span>
                            </div>
                            <h5 class="mb-0 fw-bold" id="kpiNonTenderSudah">1.520</h5>
                            <small class="text-muted d-block text-truncate">Non Tender Selesai</small>
                            <div class="mt-1 small text-muted" style="font-size: 0.75rem;">
                                <span class="text-danger fw-semibold" id="kpiNonTenderBelum">7.011</span> Belum Input
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Card 5: Swakelola -->
                <div class="col-12 col-sm-6 col-xl-2">
                    <div class="card h-100 border-0 shadow-sm card-border-shadow-success">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="avatar-initial rounded bg-label-success p-2"><i class="bx bx-user-check fs-5"></i></span>
                                <span class="badge bg-label-success fw-bold" id="kpiSwakelolaPct">8.31%</span>
                            </div>
                            <h5 class="mb-0 fw-bold" id="kpiSwakelolaSudah">220</h5>
                            <small class="text-muted d-block text-truncate">Swakelola Selesai</small>
                            <div class="mt-1 small text-muted" style="font-size: 0.75rem;">
                                <span class="text-danger fw-semibold" id="kpiSwakelolaBelum">2.427</span> Belum Input
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Card 6: E-Purchasing -->
                <div class="col-12 col-sm-6 col-xl-2">
                    <div class="card h-100 border-0 shadow-sm card-border-shadow-secondary">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="avatar-initial rounded bg-label-secondary p-2"><i class="bx bx-cart fs-5"></i></span>
                                <span class="badge bg-label-secondary fw-bold" id="kpiEpurchasingPct">28.51%</span>
                            </div>
                            <h5 class="mb-0 fw-bold" id="kpiEpurchasingJml">242</h5>
                            <small class="text-muted d-block text-truncate">E-Purchasing Selesai</small>
                            <div class="mt-1 small text-muted" style="font-size: 0.75rem;">
                                <span class="text-success fw-semibold" id="kpiEpurchasingSudah">69</span> Input | <span class="text-danger fw-semibold" id="kpiEpurchasingBelum">167</span> Belum
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-body p-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-3">
                            <label class="form-label small fw-bold mb-1 text-uppercase text-muted">Kategori Satker</label>
                            <select id="filterProgresKategori" class="form-select form-select-sm">
                                <option value="all">Semua Kategori (60 Satker)</option>
                                <option value="Dinas">Dinas</option>
                                <option value="Badan">Badan</option>
                                <option value="Sekretariat">Sekretariat</option>
                                <option value="Kecamatan">Kecamatan</option>
                                <option value="RSUD">RSUD</option>
                                <option value="Puskesmas">Puskesmas</option>
                                <option value="UPT">UPT</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label small fw-bold mb-1 text-uppercase text-muted">Status Input E-Kontrak</label>
                            <select id="filterProgresStatus" class="form-select form-select-sm">
                                <option value="all">Semua Status</option>
                                <option value="has_progress">Ada Input E-Kontrak (>0%)</option>
                                <option value="no_progress">Belum Ada Input (0%)</option>
                                <option value="marked_no_progress">Keterangan: Tidak Ada Progres</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-bold mb-1 text-uppercase text-muted">Cari Nama Satker / OPD</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="bx bx-search text-muted"></i></span>
                                <input type="text" id="searchProgresSatker" class="form-control" placeholder="Ketik nama Satker / Dinas / Puskesmas...">
                            </div>
                        </div>
                        <div class="col-12 col-md-2 d-flex gap-2">
                            <button id="btnResetProgresFilter" class="btn btn-sm btn-outline-secondary w-100">
                                <i class="bx bx-reset me-1"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Master Table Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header border-bottom py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                    <div>
                        <h5 class="card-title mb-0 fw-bold fs-6"><i class="bx bx-table me-2 text-primary"></i>Tabel Monitoring Progres SPSE OPD</h5>
                        <small class="text-muted">
                            <i class="bx bx-calendar me-1"></i>Update Data: <span id="labelUpdateData"><?= esc($update_data ?? 'Terbaru') ?></span> &bull; 
                            <i class="bx bx-time me-1 ms-1"></i>Update RUP: <span id="labelUpdateRup"><?= esc($update_rup ?? 'Terbaru') ?></span>
                        </small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <?php if (session()->get('role') === 'admin'): ?>
                        <button type="button" class="btn btn-sm btn-primary" id="btnSyncProgres" onclick="syncMonevProgresNow()">
                            <i class="bx bx-sync me-1"></i> Sinkronkan Progres
                        </button>
                        <?php endif; ?>
                        <a href="<?= base_url('dashboard/export-monev-progres') ?>" class="btn btn-sm btn-outline-success">
                            <i class="bx bxs-file-export me-1"></i> Unduh Excel
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="loadMonevProgresData()">
                            <i class="bx bx-refresh me-1"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 720px; overflow-y: auto;">
                        <table class="table table-hover align-middle w-100 table-progres-spse text-nowrap" id="table-progres-spse">
                            <thead class="sticky-top text-center" style="position: sticky; top: 0; z-index: 5;">
                                <tr>
                                    <th rowspan="3" class="th-satker-group" style="min-width: 45px;">NO</th>
                                    <th rowspan="3" class="th-satker-group text-start" style="min-width: 230px;">SATKER / OPD</th>
                                    <th colspan="7" class="th-rup-group py-2">RUP</th>
                                    <th colspan="4" class="th-epl-group py-2">E-PL</th>
                                    <th colspan="4" class="th-tender-group py-2">TENDER</th>
                                    <th colspan="3" class="th-nontender-group py-2">NON TENDER</th>
                                    <th colspan="3" class="th-swakelola-group py-2">SWAKELOLA</th>
                                    <th colspan="4" class="th-epurchasing-group py-2">E-PURCHASING</th>
                                    <th rowspan="3" class="th-ket-group" style="min-width: 130px;">KETERANGAN</th>
                                </tr>
                                <tr>
                                    <!-- Under RUP -->
                                    <th rowspan="2" class="th-rup-group">Penyedia</th>
                                    <th rowspan="2" class="th-rup-group">Swakelola</th>
                                    <th rowspan="2" class="th-rup-group">Pengadaan<br>Langsung</th>
                                    <th rowspan="2" class="th-rup-group">Penunjukan<br>Langsung</th>
                                    <th rowspan="2" class="th-rup-group">E-Purchasing</th>
                                    <th rowspan="2" class="th-rup-group">Dikecualikan</th>
                                    <th rowspan="2" class="th-rup-group">Tender</th>

                                    <!-- Under E-PL -->
                                    <th rowspan="2" class="th-epl-group">Dilaksanakan</th>
                                    <th colspan="3" class="th-epl-group">Input E-Kontrak</th>

                                    <!-- Under Tender -->
                                    <th rowspan="2" class="th-tender-group">Dilaksanakan</th>
                                    <th colspan="3" class="th-tender-group">Input E-Kontrak</th>

                                    <!-- Under Non Tender -->
                                    <th colspan="3" class="th-nontender-group">Input E-Kontrak</th>

                                    <!-- Under Swakelola -->
                                    <th colspan="3" class="th-swakelola-group">Input E-Kontrak</th>

                                    <!-- Under E-Purchasing -->
                                    <th rowspan="2" class="th-epurchasing-group">Jumlah</th>
                                    <th colspan="3" class="th-epurchasing-group">Input E-Kontrak</th>
                                </tr>
                                <tr>
                                    <!-- Subheaders: E-PL -->
                                    <th class="th-sub">Belum</th>
                                    <th class="th-sub">Sudah</th>
                                    <th class="th-sub fw-bold">%</th>

                                    <!-- Subheaders: Tender -->
                                    <th class="th-sub">Belum</th>
                                    <th class="th-sub">Sudah</th>
                                    <th class="th-sub fw-bold">%</th>

                                    <!-- Subheaders: Non Tender -->
                                    <th class="th-sub">Belum</th>
                                    <th class="th-sub">Sudah</th>
                                    <th class="th-sub fw-bold">%</th>

                                    <!-- Subheaders: Swakelola -->
                                    <th class="th-sub">Belum</th>
                                    <th class="th-sub">Sudah</th>
                                    <th class="th-sub fw-bold">%</th>

                                    <!-- Subheaders: E-Purchasing -->
                                    <th class="th-sub">Belum</th>
                                    <th class="th-sub">Sudah</th>
                                    <th class="th-sub fw-bold">%</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-progres-spse">
                                <tr>
                                    <td colspan="28" class="text-center py-4 text-muted">
                                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Memuat data progres SPSE...
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot id="tfoot-progres-spse" style="position: sticky; bottom: 0; z-index: 5;">
                                <tr>
                                    <th colspan="2" class="text-center">GRAND TOTAL</th>
                                    <th id="tfRupP">7.785</th>
                                    <th id="tfRupS">2.427</th>
                                    <th id="tfRupPl">6.868</th>
                                    <th id="tfRupJuksung">12</th>
                                    <th id="tfRupEpurchasing">578</th>
                                    <th id="tfRupDikecualikan">269</th>
                                    <th id="tfRupTender">58</th>
                                    <th id="tfEplDilaksanakan">689</th>
                                    <th id="tfEplBelum">463</th>
                                    <th id="tfEplSudah">226</th>
                                    <th id="tfEplPct">32.80%</th>
                                    <th id="tfTenderDilaksanakan">27</th>
                                    <th id="tfTenderBelum">3</th>
                                    <th id="tfTenderSudah">24</th>
                                    <th id="tfTenderPct">88.89%</th>
                                    <th id="tfNonTenderBelum">7.011</th>
                                    <th id="tfNonTenderSudah">1.520</th>
                                    <th id="tfNonTenderPct">17.82%</th>
                                    <th id="tfSwakelolaBelum">2.427</th>
                                    <th id="tfSwakelolaSudah">220</th>
                                    <th id="tfSwakelolaPct">8.31%</th>
                                    <th id="tfEpurchasingJml">242</th>
                                    <th id="tfEpurchasingBelum">167</th>
                                    <th id="tfEpurchasingSudah">69</th>
                                    <th id="tfEpurchasingPct">28.51%</th>
                                    <th>-</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: REALISASI RUP & KEUANGAN (EXISTING VIEW) -->
        <div class="tab-pane fade" id="pane-realisasi-rup" role="tabpanel" aria-labelledby="tab-realisasi-rup">
            <!-- Filter Card -->
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-body p-3">
            <div class="row g-3 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-bold mb-1 text-uppercase text-muted">Tahun Anggaran</label>
                    <select id="yearFilter" class="form-select form-select-sm">
                        <?php for($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-bold mb-1 text-uppercase text-muted">Tipe RUP</label>
                    <select id="tipeFilter" class="form-select form-select-sm">
                        <option value="all">Semua Tipe RUP</option>
                        <option value="Penyedia">Penyedia</option>
                        <option value="Swakelola">Swakelola</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-bold mb-1 text-uppercase text-muted">Metode Pengadaan (SiRUP)</label>
                    <select id="metodeFilter" class="form-select form-select-sm">
                        <option value="all">Semua Metode SiRUP</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-bold mb-1 text-uppercase text-muted">Status Realisasi</label>
                    <select id="statusFilter" class="form-select form-select-sm">
                        <option value="all">Semua Status</option>
                        <option value="realized">Sudah Realisasi (>0%)</option>
                        <option value="unrealized">Belum Realisasi (0%)</option>
                        <option value="completed">Realisasi 100%</option>
                    </select>
                </div>
                <div class="col-6 col-md-2 d-flex gap-2">
                    <button id="btnFilter" class="btn btn-sm btn-primary flex-fill">
                        <i class="bx bx-filter-alt me-1"></i> Filter
                    </button>
                    <button id="btnReset" class="btn btn-sm btn-outline-secondary" title="Reset Filter">
                        <i class="bx bx-reset"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 1: Realisasi Paket (Fisik) -->
    <div class="d-flex align-items-center mb-2">
        <span class="badge bg-label-info me-2 p-2"><i class="bx bx-package fs-5"></i></span>
        <div>
            <h6 class="fw-bold mb-0 text-uppercase tracking-wide" style="letter-spacing: 0.5px;">Ikhtisar Realisasi Paket (Fisik)</h6>
            <small class="text-muted">Perbandingan kuantitas jumlah paket RUP terisi dan terealisasi</small>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <!-- Card 1: Total Paket RUP -->
        <div class="col-6 col-lg-3">
            <div class="card card-border-shadow-info h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar avatar-sm me-2">
                            <span class="avatar-initial rounded bg-label-info"><i class="bx bx-layer"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0 fw-bold" id="valTotalPaket">0</h4>
                    </div>
                    <p class="mb-0 fw-semibold text-truncate">Total Paket RUP</p>
                    <small class="text-muted" id="valTotalOpd">0 OPD</small>
                </div>
            </div>
        </div>
        <!-- Card 2: Paket Penyedia -->
        <div class="col-6 col-lg-3">
            <div class="card card-border-shadow-primary h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2 pb-1">
                        <div class="avatar avatar-sm me-2">
                            <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-briefcase"></i></span>
                        </div>
                        <span class="badge bg-label-primary fw-bold" id="valPctPaketPenyedia">0%</span>
                    </div>
                    <h5 class="mb-0 fw-bold text-primary" id="valPaketPenyedia">0 / 0 pkt</h5>
                    <p class="mb-0 fw-semibold text-truncate">Paket RUP Penyedia</p>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar bg-primary" id="barPaketPenyedia" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Card 3: Paket Swakelola -->
        <div class="col-6 col-lg-3">
            <div class="card card-border-shadow-success h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2 pb-1">
                        <div class="avatar avatar-sm me-2">
                            <span class="avatar-initial rounded bg-label-success"><i class="bx bx-group"></i></span>
                        </div>
                        <span class="badge bg-label-success fw-bold" id="valPctPaketSwakelola">0%</span>
                    </div>
                    <h5 class="mb-0 fw-bold text-success" id="valPaketSwakelola">0 / 0 pkt</h5>
                    <p class="mb-0 fw-semibold text-truncate">Paket RUP Swakelola</p>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar bg-success" id="barPaketSwakelola" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Card 4: Total Realisasi Paket -->
        <div class="col-6 col-lg-3">
            <div class="card card-border-shadow-warning h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2 pb-1">
                        <div class="avatar avatar-sm me-2">
                            <span class="avatar-initial rounded bg-label-warning"><i class="bx bx-check-circle"></i></span>
                        </div>
                        <span class="badge bg-label-warning fw-bold fs-6" id="valPctTotalPaket">0%</span>
                    </div>
                    <h5 class="mb-0 fw-bold text-warning" id="valTotalPaketRealisasi">0 / 0 pkt</h5>
                    <p class="mb-0 fw-semibold text-truncate">Total Realisasi Paket</p>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar bg-warning" id="barTotalPaket" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 2: Realisasi Keuangan (Anggaran) -->
    <div class="d-flex align-items-center mb-2">
        <span class="badge bg-label-primary me-2 p-2"><i class="bx bx-wallet fs-5"></i></span>
        <div>
            <h6 class="fw-bold mb-0 text-uppercase tracking-wide" style="letter-spacing: 0.5px;">Ikhtisar Realisasi Keuangan (Anggaran)</h6>
            <small class="text-muted">Perbandingan nilai nominal pagu anggaran dengan realisasi transaksi</small>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <!-- Card 5: Total Pagu -->
        <div class="col-6 col-lg-3">
            <div class="card card-border-shadow-info h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar avatar-sm me-2">
                            <span class="avatar-initial rounded bg-label-info"><i class="bx bx-money"></i></span>
                        </div>
                        <h5 class="ms-1 mb-0 fw-bold" id="valTotalPagu">Rp 0</h5>
                    </div>
                    <p class="mb-0 fw-semibold text-truncate">Total Pagu RUP</p>
                    <small class="text-muted">Penyedia & Swakelola</small>
                </div>
            </div>
        </div>
        <!-- Card 6: Keuangan Penyedia -->
        <div class="col-6 col-lg-3">
            <div class="card card-border-shadow-primary h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2 pb-1">
                        <div class="avatar avatar-sm me-2">
                            <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-building"></i></span>
                        </div>
                        <span class="badge bg-label-primary fw-bold" id="valPctKeuanganPenyedia">0%</span>
                    </div>
                    <h6 class="mb-0 fw-bold text-primary text-truncate" id="valRealPenyedia">Rp 0</h6>
                    <small class="text-muted d-block text-truncate" id="valPaguPenyedia">Pagu: Rp 0</small>
                    <p class="mb-0 fw-semibold text-truncate mt-1">Keuangan Penyedia</p>
                </div>
            </div>
        </div>
        <!-- Card 7: Keuangan Swakelola -->
        <div class="col-6 col-lg-3">
            <div class="card card-border-shadow-success h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2 pb-1">
                        <div class="avatar avatar-sm me-2">
                            <span class="avatar-initial rounded bg-label-success"><i class="bx bx-user-pin"></i></span>
                        </div>
                        <span class="badge bg-label-success fw-bold" id="valPctKeuanganSwakelola">0%</span>
                    </div>
                    <h6 class="mb-0 fw-bold text-success text-truncate" id="valRealSwakelola">Rp 0</h6>
                    <small class="text-muted d-block text-truncate" id="valPaguSwakelola">Pagu: Rp 0</small>
                    <p class="mb-0 fw-semibold text-truncate mt-1">Keuangan Swakelola</p>
                </div>
            </div>
        </div>
        <!-- Card 8: Total Realisasi Keuangan -->
        <div class="col-6 col-lg-3">
            <div class="card card-border-shadow-warning h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2 pb-1">
                        <div class="avatar avatar-sm me-2">
                            <span class="avatar-initial rounded bg-label-warning"><i class="bx bx-chart"></i></span>
                        </div>
                        <span class="badge bg-label-warning fw-bold fs-6" id="valPctTotalKeuangan">0%</span>
                    </div>
                    <h6 class="mb-0 fw-bold text-warning text-truncate" id="valTotalRealisasi">Rp 0</h6>
                    <small class="text-muted d-block text-truncate" id="valTotalSisaPagu">Sisa: Rp 0</small>
                    <p class="mb-0 fw-semibold text-truncate mt-1">Total Realisasi Keuangan</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Card -->
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-header border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div>
                <h5 class="card-title mb-0 fw-bold" id="chartTitle">
                    <i class="bx bx-bar-chart-alt-2 me-2 text-primary"></i>Grafik Realisasi Paket per OPD
                </h5>
                <small class="text-muted" id="chartSubtitle">Menampilkan persentase paket RUP yang sudah terealisasi</small>
            </div>
            <!-- Chart Controls -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Limit Selector -->
                <div class="btn-group btn-group-sm" role="group" aria-label="Limit Display">
                    <button type="button" class="btn btn-outline-secondary active" id="btnLimit10" onclick="setChartLimit(10)">Top 10</button>
                    <button type="button" class="btn btn-outline-secondary" id="btnLimit20" onclick="setChartLimit(20)">Top 20</button>
                    <button type="button" class="btn btn-outline-secondary" id="btnLimitAll" onclick="setChartLimit('all')">Semua</button>
                </div>
                <!-- Chart Mode Switcher -->
                <div class="btn-group btn-group-sm" role="group" aria-label="Chart Display Mode">
                    <button type="button" class="btn btn-outline-primary active" id="btnChartModePctPaket" onclick="setChartMode('pct_paket')" title="% Realisasi Paket">
                        <i class="bx bx-pie-chart-alt me-1"></i> % Realisasi
                    </button>
                    <button type="button" class="btn btn-outline-primary" id="btnChartModeCountPaket" onclick="setChartMode('count_paket')" title="Jumlah Paket">
                        <i class="bx bx-bar-chart me-1"></i> Jml Paket
                    </button>
                    <button type="button" class="btn btn-outline-primary" id="btnChartModeTipePaket" onclick="setChartMode('tipe_paket')" title="Penyedia vs Swakelola">
                        <i class="bx bx-git-compare me-1"></i> Penyedia/Swakelola
                    </button>
                    <button type="button" class="btn btn-outline-primary" id="btnChartModeKeuangan" onclick="setChartMode('pct_keuangan')" title="% Keuangan">
                        <i class="bx bx-dollar-circle me-1"></i> % Keuangan
                    </button>
                </div>
                <!-- Collapse Button -->
                <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#collapseChartBody" aria-expanded="true" title="Minimize / Expand Grafik" id="btnToggleChartCollapse">
                    <i class="bx bx-chevron-up"></i>
                </button>
            </div>
        </div>
        <div class="collapse show" id="collapseChartBody">
            <div class="card-body p-3">
                <div id="chartScrollContainer" style="max-height: 480px; overflow-y: auto; overflow-x: hidden;">
                    <div id="opdChart"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card shadow-sm border-0">
        <div class="card-header border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div>
                <h5 class="card-title mb-0 fw-bold"><i class="bx bx-table me-2 text-primary"></i>Tabel Monitoring Realisasi OPD</h5>
                <small class="text-muted">Pemisahan Realisasi Paket (Fisik) dan Realisasi Keuangan (Anggaran)</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-label-info"><i class="bx bx-info-circle me-1"></i> Klik tombol <b>+</b> untuk rincian paket RUP</span>
            </div>
        </div>
        <div class="card-body pt-3">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle w-100" id="table-monev-opd">
                    <thead>
                        <tr class="table-light">
                            <th rowspan="2" class="text-center align-middle" width="30">#</th>
                            <th rowspan="2" class="align-middle">Nama OPD / Satker</th>
                            <th colspan="3" class="text-center bg-label-info fw-bold py-2">
                                <i class="bx bx-package me-1"></i> Realisasi Paket (Fisik)
                            </th>
                            <th colspan="3" class="text-center bg-label-primary fw-bold py-2">
                                <i class="bx bx-wallet me-1"></i> Realisasi Keuangan (Anggaran)
                            </th>
                        </tr>
                        <tr class="table-light">
                            <!-- Paket -->
                            <th class="text-center">RUP Penyedia</th>
                            <th class="text-center">RUP Swakelola</th>
                            <th class="text-center">Total Realisasi Paket</th>
                            <!-- Keuangan -->
                            <th class="text-center">RUP Penyedia</th>
                            <th class="text-center">RUP Swakelola</th>
                            <th class="text-center">Total Realisasi Keuangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>
        </div><!-- End pane-realisasi-rup -->
    </div><!-- End monevTabsContent -->
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets') ?>/vendor/libs/apex-charts/apexcharts.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

<script>
    let rawOpdData = [];
    let filteredOpdData = [];
    let currentChartMode = 'pct_paket';
    let opdChart = null;
    let dtTable = null;

    const dtLanguageId = {
        emptyTable: "Tidak ada data yang tersedia pada tabel ini",
        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
        infoEmpty: "Menampilkan 0 sampai 0 dari 0 entri",
        infoFiltered: "(disaring dari _MAX_ entri keseluruhan)",
        infoThousands: "'",
        lengthMenu: "Tampilkan _MENU_ entri",
        loadingRecords: "Sedang memuat...",
        processing: "Sedang memproses...",
        search: "Cari:",
        zeroRecords: "Tidak ditemukan data yang sesuai",
        paginate: {
            first: "Pertama",
            last: "Terakhir",
            next: "Selanjutnya",
            previous: "Sebelumnya"
        },
        aria: {
            sortAscending: ": aktifkan untuk mengurutkan kolom ke atas",
            sortDescending: ": aktifkan untuk mengurutkan kolom ke bawah"
        }
    };

    function formatIDR(amount) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount || 0);
    }

    function formatCompactIDR(amount) {
        if (!amount || amount === 0) return 'Rp 0';
        if (Math.abs(amount) >= 1e12) return 'Rp ' + (amount / 1e12).toFixed(2) + ' T';
        if (Math.abs(amount) >= 1e9) return 'Rp ' + (amount / 1e9).toFixed(2) + ' M';
        if (Math.abs(amount) >= 1e6) return 'Rp ' + (amount / 1e6).toFixed(2) + ' Jt';
        return formatIDR(amount);
    }

    function getExportTimestamp() {
        let now = new Date();
        let pad = (n) => n.toString().padStart(2, '0');
        let day = pad(now.getDate());
        let month = pad(now.getMonth() + 1);
        let year = now.getFullYear();
        let hours = pad(now.getHours());
        let minutes = pad(now.getMinutes());
        let seconds = pad(now.getSeconds());

        return {
            display: `${day}/${month}/${year} ${hours}:${minutes}:${seconds} WIB`,
            file: `${year}${month}${day}_${hours}${minutes}${seconds}`
        };
    }

    function escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    let currentChartLimit = 10;

    function setChartLimit(limit) {
        currentChartLimit = limit;
        $('#btnLimit10, #btnLimit20, #btnLimitAll').removeClass('active');
        if (limit === 10) $('#btnLimit10').addClass('active');
        else if (limit === 20) $('#btnLimit20').addClass('active');
        else $('#btnLimitAll').addClass('active');

        renderChart(filteredOpdData);
    }

    function setChartMode(mode) {
        currentChartMode = mode;
        $('#btnChartModePctPaket, #btnChartModeCountPaket, #btnChartModeTipePaket, #btnChartModeKeuangan').removeClass('active');
        if (mode === 'pct_paket') $('#btnChartModePctPaket').addClass('active');
        else if (mode === 'count_paket') $('#btnChartModeCountPaket').addClass('active');
        else if (mode === 'tipe_paket') $('#btnChartModeTipePaket').addClass('active');
        else if (mode === 'pct_keuangan') $('#btnChartModeKeuangan').addClass('active');

        renderChart(filteredOpdData);
    }

    function formatMetodeName(m) {
        if (!m || m === '-') return '-';
        let str = m.toString().trim();
        if (str === '1' || str === 'Tipe 1' || str === 'Tipe I') return 'Swakelola Tipe I';
        if (str === '2' || str === 'Tipe 2' || str === 'Tipe II') return 'Swakelola Tipe II';
        if (str === '3' || str === 'Tipe 3' || str === 'Tipe III') return 'Swakelola Tipe III';
        if (str === '4' || str === 'Tipe 4' || str === 'Tipe IV') return 'Swakelola Tipe IV';
        return str;
    }

    function populateMetodeDropdown(data) {
        let currentSelected = $('#metodeFilter').val();
        let currentTipe = $('#tipeFilter').val();
        
        let methodsSet = new Set();
        (data || []).forEach(opd => {
            (opd.packages || []).forEach(pkg => {
                if (currentTipe === 'all' || pkg.tipe === currentTipe) {
                    let formatted = formatMetodeName(pkg.metode);
                    if (formatted && formatted !== '-') {
                        methodsSet.add(formatted);
                    }
                }
            });
        });

        let sortedMethods = Array.from(methodsSet).sort((a, b) => a.localeCompare(b, 'id', { sensitivity: 'base' }));

        let html = '<option value="all">Semua Metode SiRUP</option>';
        sortedMethods.forEach(m => {
            html += `<option value="${escapeHtml(m)}">${escapeHtml(m)}</option>`;
        });

        $('#metodeFilter').html(html);

        if (currentSelected && methodsSet.has(currentSelected)) {
            $('#metodeFilter').val(currentSelected);
        } else {
            $('#metodeFilter').val('all');
        }
    }

    // Filter Logic
    function applyFilters() {
        let tipe = $('#tipeFilter').val();
        let metode = $('#metodeFilter').val();
        let status = $('#statusFilter').val();

        let processed = [];

        rawOpdData.forEach(opd => {
            let matchedPackages = (opd.packages || []).filter(pkg => {
                // Tipe RUP Filter
                if (tipe !== 'all' && pkg.tipe !== tipe) return false;

                // Metode Pengadaan SiRUP Filter
                if (metode !== 'all') {
                    let pkgMetode = formatMetodeName(pkg.metode);
                    if (pkgMetode.toLowerCase() !== metode.toLowerCase()) {
                        return false;
                    }
                }

                // Status Realisasi Filter
                if (status === 'realized' && pkg.realisasi <= 0) return false;
                if (status === 'unrealized' && pkg.realisasi > 0) return false;
                if (status === 'completed' && pkg.persentase < 100) return false;

                return true;
            });

            // Re-aggregate OPD data based on matched packages
            let paguPenyedia = 0, realPenyedia = 0, pktPenyedia = 0, pktRealPenyedia = 0;
            let paguSwakelola = 0, realSwakelola = 0, pktSwakelola = 0, pktRealSwakelola = 0;

            matchedPackages.forEach(pkg => {
                if (pkg.tipe === 'Penyedia') {
                    paguPenyedia += pkg.pagu;
                    realPenyedia += pkg.realisasi;
                    pktPenyedia += 1;
                    if (pkg.realisasi > 0) pktRealPenyedia += 1;
                } else {
                    paguSwakelola += pkg.pagu;
                    realSwakelola += pkg.realisasi;
                    pktSwakelola += 1;
                    if (pkg.realisasi > 0) pktRealSwakelola += 1;
                }
            });

            let totalPagu = paguPenyedia + paguSwakelola;
            let totalReal = realPenyedia + realSwakelola;
            let totalPkt = pktPenyedia + pktSwakelola;
            let totalPktReal = pktRealPenyedia + pktRealSwakelola;

            // Only include OPD if it has packages or if no filters are active
            if (matchedPackages.length > 0 || (tipe === 'all' && metode === 'all' && status === 'all')) {
                processed.push({
                    kd_satker: opd.kd_satker,
                    nama_satker: opd.nama_satker,
                    // Keuangan
                    pagu_penyedia: paguPenyedia,
                    realisasi_penyedia: realPenyedia,
                    persen_keuangan_penyedia: paguPenyedia > 0 ? Number(((realPenyedia / paguPenyedia) * 100).toFixed(2)) : 0,
                    pagu_swakelola: paguSwakelola,
                    realisasi_swakelola: realSwakelola,
                    persen_keuangan_swakelola: paguSwakelola > 0 ? Number(((realSwakelola / paguSwakelola) * 100).toFixed(2)) : 0,
                    total_pagu: totalPagu,
                    total_realisasi: totalReal,
                    persentase_keuangan: totalPagu > 0 ? Number(((totalReal / totalPagu) * 100).toFixed(2)) : 0,
                    // Paket
                    paket_penyedia: pktPenyedia,
                    paket_terealisasi_penyedia: pktRealPenyedia,
                    persen_paket_penyedia: pktPenyedia > 0 ? Number(((pktRealPenyedia / pktPenyedia) * 100).toFixed(2)) : 0,
                    paket_swakelola: pktSwakelola,
                    paket_terealisasi_swakelola: pktRealSwakelola,
                    persen_paket_swakelola: pktSwakelola > 0 ? Number(((pktRealSwakelola / pktSwakelola) * 100).toFixed(2)) : 0,
                    total_paket: totalPkt,
                    paket_terealisasi: totalPktReal,
                    persentase_paket: totalPkt > 0 ? Number(((totalPktReal / totalPkt) * 100).toFixed(2)) : 0,
                    packages: matchedPackages
                });
            }
        });

        filteredOpdData = processed;
        renderSummaryCards(filteredOpdData);
        renderChart(filteredOpdData);
        renderTable(filteredOpdData);
    }

    // Render Summary Cards
    function renderSummaryCards(data) {
        let totalOpd = data.length;
        let sumPktTotal = 0, sumPktReal = 0;
        let sumPktP = 0, sumPktRealP = 0;
        let sumPktS = 0, sumPktRealS = 0;

        let sumPaguTotal = 0, sumRealTotal = 0;
        let sumPaguP = 0, sumRealP = 0;
        let sumPaguS = 0, sumRealS = 0;

        data.forEach(d => {
            sumPktTotal += d.total_paket;
            sumPktReal += d.paket_terealisasi;
            sumPktP += d.paket_penyedia;
            sumPktRealP += d.paket_terealisasi_penyedia;
            sumPktS += d.paket_swakelola;
            sumPktRealS += d.paket_terealisasi_swakelola;

            sumPaguTotal += d.total_pagu;
            sumRealTotal += d.total_realisasi;
            sumPaguP += d.pagu_penyedia;
            sumRealP += d.realisasi_penyedia;
            sumPaguS += d.pagu_swakelola;
            sumRealS += d.realisasi_swakelola;
        });

        let pctTotalPkt = sumPktTotal > 0 ? ((sumPktReal / sumPktTotal) * 100).toFixed(2) : 0;
        let pctPktP = sumPktP > 0 ? ((sumPktRealP / sumPktP) * 100).toFixed(2) : 0;
        let pctPktS = sumPktS > 0 ? ((sumPktRealS / sumPktS) * 100).toFixed(2) : 0;

        let pctTotalKeuangan = sumPaguTotal > 0 ? ((sumRealTotal / sumPaguTotal) * 100).toFixed(2) : 0;
        let pctKeuanganP = sumPaguP > 0 ? ((sumRealP / sumPaguP) * 100).toFixed(2) : 0;
        let pctKeuanganS = sumPaguS > 0 ? ((sumRealS / sumPaguS) * 100).toFixed(2) : 0;

        // Block Paket
        $('#valTotalPaket').text(sumPktTotal.toLocaleString('id-ID'));
        $('#valTotalOpd').text(totalOpd + ' OPD / Satker');
        
        $('#valPaketPenyedia').text(sumPktRealP.toLocaleString('id-ID') + ' / ' + sumPktP.toLocaleString('id-ID') + ' pkt');
        $('#valPctPaketPenyedia').text(pctPktP + '%');
        $('#barPaketPenyedia').css('width', Math.min(100, pctPktP) + '%');

        $('#valPaketSwakelola').text(sumPktRealS.toLocaleString('id-ID') + ' / ' + sumPktS.toLocaleString('id-ID') + ' pkt');
        $('#valPctPaketSwakelola').text(pctPktS + '%');
        $('#barPaketSwakelola').css('width', Math.min(100, pctPktS) + '%');

        $('#valTotalPaketRealisasi').text(sumPktReal.toLocaleString('id-ID') + ' / ' + sumPktTotal.toLocaleString('id-ID') + ' pkt');
        $('#valPctTotalPaket').text(pctTotalPkt + '%');
        $('#barTotalPaket').css('width', Math.min(100, pctTotalPkt) + '%');

        // Block Keuangan
        $('#valTotalPagu').text(formatCompactIDR(sumPaguTotal));
        
        $('#valRealPenyedia').text(formatCompactIDR(sumRealP));
        $('#valPaguPenyedia').text('Pagu: ' + formatCompactIDR(sumPaguP));
        $('#valPctKeuanganPenyedia').text(pctKeuanganP + '%');

        $('#valRealSwakelola').text(formatCompactIDR(sumRealS));
        $('#valPaguSwakelola').text('Pagu: ' + formatCompactIDR(sumPaguS));
        $('#valPctKeuanganSwakelola').text(pctKeuanganS + '%');

        $('#valTotalRealisasi').text(formatCompactIDR(sumRealTotal));
        let sisaPagu = Math.max(0, sumPaguTotal - sumRealTotal);
        $('#valTotalSisaPagu').text('Sisa: ' + formatCompactIDR(sisaPagu));
        $('#valPctTotalKeuangan').text(pctTotalKeuangan + '%');
    }

    // Render ApexCharts
    function renderChart(data) {
        if (opdChart) {
            try { opdChart.destroy(); } catch(e) {}
            opdChart = null;
        }

        if (!data || data.length === 0) {
            $('#opdChart').html('<div class="text-center text-muted p-4">Tidak ada data OPD yang sesuai dengan filter.</div>');
            return;
        }

        let options = {};
        let limit = currentChartLimit;
        let limitText = limit === 'all' ? `Semua OPD (${data.length})` : `Top ${limit} OPD`;

        if (currentChartMode === 'pct_paket') {
            $('#chartTitle').html(`<i class="bx bx-bar-chart-alt-2 me-2 text-primary"></i>Persentase Realisasi Paket per OPD <span class="badge bg-label-primary fs-tiny fw-normal ms-1">${limitText}</span>`);
            $('#chartSubtitle').text('Rasio kuantitas paket RUP yang sudah memiliki realisasi fisik / transaksi');

            let sorted = [...data].sort((a, b) => b.persentase_paket - a.persentase_paket);
            let displayList = limit === 'all' ? sorted : sorted.slice(0, parseInt(limit));

            let categories = displayList.map(d => d.nama_satker);
            let seriesData = displayList.map(d => d.persentase_paket);
            let calcHeight = limit === 'all' ? Math.max(460, categories.length * 26) : (limit === 20 ? 460 : 360);

            options = {
                series: [{
                    name: 'Realisasi Paket (%)',
                    data: seriesData
                }],
                chart: {
                    type: 'bar',
                    height: calcHeight,
                    toolbar: { show: true },
                    animations: { enabled: true }
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 4,
                        dataLabels: { position: 'top' },
                        colors: {
                            ranges: [
                                { from: 80, to: 100, color: '#71dd37' },
                                { from: 50, to: 79.99, color: '#03c3ec' },
                                { from: 0, to: 49.99, color: '#ffab00' }
                            ]
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    offsetX: -6,
                    style: { fontSize: '11px', colors: ['#fff'] },
                    formatter: function (val) { return val + "%" }
                },
                xaxis: {
                    categories: categories,
                    max: 100,
                    labels: { formatter: function (val) { return val + "%" } }
                },
                tooltip: {
                    y: {
                        formatter: function (val, opts) {
                            let item = displayList[opts.dataPointIndex] || {};
                            return `${val}% (${item.paket_terealisasi || 0} dari ${item.total_paket || 0} paket) | Realisasi: ${formatIDR(item.total_realisasi)}`;
                        }
                    }
                }
            };
        } else if (currentChartMode === 'count_paket') {
            $('#chartTitle').html(`<i class="bx bx-bar-chart me-2 text-primary"></i>Jumlah Paket (Terealisasi vs Belum Terealisasi) <span class="badge bg-label-primary fs-tiny fw-normal ms-1">${limitText}</span>`);
            $('#chartSubtitle').text('Total jumlah paket terealisasi dibandingkan total paket RUP belum terealisasi');

            let sorted = [...data].sort((a, b) => b.total_paket - a.total_paket);
            let displayList = limit === 'all' ? sorted : sorted.slice(0, parseInt(limit));

            let categories = displayList.map(d => d.nama_satker);
            let realizedData = displayList.map(d => d.paket_terealisasi);
            let unrealizedData = displayList.map(d => Math.max(0, d.total_paket - d.paket_terealisasi));
            let calcHeight = limit === 'all' ? Math.max(460, categories.length * 26) : (limit === 20 ? 460 : 360);

            options = {
                series: [
                    { name: 'Paket Terealisasi', data: realizedData, color: '#71dd37' },
                    { name: 'Belum Terealisasi', data: unrealizedData, color: '#e0e0e0' }
                ],
                chart: {
                    type: 'bar',
                    stacked: true,
                    height: calcHeight,
                    toolbar: { show: true },
                    animations: { enabled: true }
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 3
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val) { return val > 0 ? val : ''; }
                },
                xaxis: {
                    categories: categories
                },
                tooltip: {
                    y: {
                        formatter: function (val, opts) {
                            let item = displayList[opts.dataPointIndex] || {};
                            return `${val} paket (Total: ${item.total_paket || 0} paket, Realisasi: ${item.persentase_paket || 0}%)`;
                        }
                    }
                }
            };
        } else if (currentChartMode === 'tipe_paket') {
            $('#chartTitle').html(`<i class="bx bx-git-compare me-2 text-primary"></i>Realisasi Paket: Penyedia vs Swakelola <span class="badge bg-label-primary fs-tiny fw-normal ms-1">${limitText}</span>`);
            $('#chartSubtitle').text('Perbandingan paket terealisasi antara metode RUP Penyedia dan Swakelola');

            let sorted = [...data].sort((a, b) => (b.paket_terealisasi_penyedia + b.paket_terealisasi_swakelola) - (a.paket_terealisasi_penyedia + a.paket_terealisasi_swakelola));
            let displayList = limit === 'all' ? sorted : sorted.slice(0, parseInt(limit));

            let categories = displayList.map(d => d.nama_satker);
            let penyediaData = displayList.map(d => d.paket_terealisasi_penyedia);
            let swakelolaData = displayList.map(d => d.paket_terealisasi_swakelola);
            let calcHeight = limit === 'all' ? Math.max(460, categories.length * 26) : (limit === 20 ? 460 : 360);

            options = {
                series: [
                    { name: 'Paket Penyedia Terealisasi', data: penyediaData, color: '#696cff' },
                    { name: 'Paket Swakelola Terealisasi', data: swakelolaData, color: '#71dd37' }
                ],
                chart: {
                    type: 'bar',
                    height: calcHeight,
                    toolbar: { show: true },
                    animations: { enabled: true }
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 4
                    }
                },
                dataLabels: { enabled: true },
                xaxis: { categories: categories },
                tooltip: {
                    y: {
                        formatter: function (val, opts) {
                            let item = displayList[opts.dataPointIndex] || {};
                            return `${val} paket terealisasi (Total: ${item.total_paket || 0} paket)`;
                        }
                    }
                }
            };
        } else if (currentChartMode === 'pct_keuangan') {
            $('#chartTitle').html(`<i class="bx bx-dollar-circle me-2 text-primary"></i>Persentase Realisasi Keuangan per OPD <span class="badge bg-label-primary fs-tiny fw-normal ms-1">${limitText}</span>`);
            $('#chartSubtitle').text('Persentase realisasi anggaran nominal (Rp) dari pagu RUP per OPD');

            let sorted = [...data].sort((a, b) => b.persentase_keuangan - a.persentase_keuangan);
            let displayList = limit === 'all' ? sorted : sorted.slice(0, parseInt(limit));

            let categories = displayList.map(d => d.nama_satker);
            let seriesData = displayList.map(d => d.persentase_keuangan);
            let calcHeight = limit === 'all' ? Math.max(460, categories.length * 26) : (limit === 20 ? 460 : 360);

            options = {
                series: [{
                    name: 'Realisasi Keuangan (%)',
                    data: seriesData
                }],
                chart: {
                    type: 'bar',
                    height: calcHeight,
                    toolbar: { show: true },
                    animations: { enabled: true }
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 4,
                        dataLabels: { position: 'top' },
                        colors: {
                            ranges: [
                                { from: 80, to: 100, color: '#71dd37' },
                                { from: 50, to: 79.99, color: '#03c3ec' },
                                { from: 0, to: 49.99, color: '#ffab00' }
                            ]
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    offsetX: -6,
                    style: { fontSize: '11px', colors: ['#fff'] },
                    formatter: function (val) { return val + "%" }
                },
                xaxis: {
                    categories: categories,
                    max: 100,
                    labels: { formatter: function (val) { return val + "%" } }
                },
                tooltip: {
                    y: {
                        formatter: function (val, opts) {
                            let item = displayList[opts.dataPointIndex] || {};
                            return `${val}% (${formatIDR(item.total_realisasi)} dari ${formatIDR(item.total_pagu)})`;
                        }
                    }
                }
            };
        }

        let chartEl = document.querySelector("#opdChart");
        if (chartEl) {
            chartEl.innerHTML = '';
            opdChart = new ApexCharts(chartEl, options);
            opdChart.render();
        }
    }

    // Render DataTable
    function renderTable(data) {
        if (dtTable) {
            dtTable.destroy();
            $('#table-monev-opd tbody').empty();
        }

        let tbody = '';
        data.forEach(opd => {
            let packagesJson = escapeHtml(JSON.stringify(opd.packages || []));

            let pctPktClass = opd.persentase_paket >= 80 ? 'bg-success' : (opd.persentase_paket >= 50 ? 'bg-info' : 'bg-warning');
            let pctKeuClass = opd.persentase_keuangan >= 80 ? 'bg-success' : (opd.persentase_keuangan >= 50 ? 'bg-info' : 'bg-warning');

            tbody += `
                <tr data-packages="${packagesJson}">
                    <td class="details-control text-center">
                        <button class="btn btn-sm btn-icon btn-outline-primary rounded-circle" title="Lihat Detail Paket RUP">
                            <i class="bx bx-plus"></i>
                        </button>
                    </td>
                    <td>
                        <span class="fw-bold text-dark">${escapeHtml(opd.nama_satker)}</span>
                        <div class="small text-muted d-block mt-1">Kode: ${escapeHtml(opd.kd_satker)}</div>
                    </td>

                    <!-- Realisasi Paket: Penyedia -->
                    <td class="text-center" data-order="${opd.persen_paket_penyedia}">
                        <div class="fw-semibold text-primary">${opd.paket_terealisasi_penyedia} / ${opd.paket_penyedia} pkt</div>
                        <span class="badge bg-label-primary font-monospace" style="font-size: 0.75rem;">${opd.persen_paket_penyedia}%</span>
                    </td>

                    <!-- Realisasi Paket: Swakelola -->
                    <td class="text-center" data-order="${opd.persen_paket_swakelola}">
                        <div class="fw-semibold text-success">${opd.paket_terealisasi_swakelola} / ${opd.paket_swakelola} pkt</div>
                        <span class="badge bg-label-success font-monospace" style="font-size: 0.75rem;">${opd.persen_paket_swakelola}%</span>
                    </td>

                    <!-- Realisasi Paket: Total -->
                    <td data-order="${opd.persentase_paket}">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold text-dark">${opd.paket_terealisasi} / ${opd.total_paket} pkt</span>
                            <span class="badge bg-label-info fw-bold">${opd.persentase_paket}%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar ${pctPktClass}" role="progressbar" style="width: ${Math.min(100, opd.persentase_paket)}%"></div>
                        </div>
                    </td>

                    <!-- Keuangan: Penyedia -->
                    <td data-order="${opd.persen_keuangan_penyedia}">
                        <div class="fw-semibold text-primary">${formatIDR(opd.realisasi_penyedia)}</div>
                        <div class="small text-muted">Pagu: ${formatIDR(opd.pagu_penyedia)}</div>
                        <span class="badge bg-label-primary font-monospace mt-1" style="font-size: 0.75rem;">${opd.persen_keuangan_penyedia}%</span>
                    </td>

                    <!-- Keuangan: Swakelola -->
                    <td data-order="${opd.persen_keuangan_swakelola}">
                        <div class="fw-semibold text-success">${formatIDR(opd.realisasi_swakelola)}</div>
                        <div class="small text-muted">Pagu: ${formatIDR(opd.pagu_swakelola)}</div>
                        <span class="badge bg-label-success font-monospace mt-1" style="font-size: 0.75rem;">${opd.persen_keuangan_swakelola}%</span>
                    </td>

                    <!-- Keuangan: Total -->
                    <td data-order="${opd.persentase_keuangan}">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div>
                                <span class="fw-bold text-success">${formatIDR(opd.total_realisasi)}</span>
                                <div class="small text-muted">Pagu: ${formatIDR(opd.total_pagu)}</div>
                            </div>
                            <span class="badge bg-label-warning fw-bold fs-6">${opd.persentase_keuangan}%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar ${pctKeuClass}" role="progressbar" style="width: ${Math.min(100, opd.persentase_keuangan)}%"></div>
                        </div>
                    </td>
                </tr>
            `;
        });

        $('#table-monev-opd tbody').html(tbody);

        dtTable = $('#table-monev-opd').DataTable({
            responsive: true,
            pageLength: 25,
            order: [[4, 'desc']], // Sort by Total Realisasi Paket descending by default
            columnDefs: [
                { orderable: false, targets: 0 }
            ],
            dom: '<"row align-items-center mb-3"<"col-md-4"l><"col-md-4 text-center"B><"col-md-4"f>>t<"row align-items-center mt-3"<"col-md-6"i><"col-md-6"p>>',
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="bx bx-export me-1"></i> Export Excel',
                    className: 'btn btn-success btn-sm',
                    filename: function() {
                        let ts = getExportTimestamp();
                        let yr = $('#yearFilter').val() || '';
                        return `Monev_Realisasi_OPD_${yr}_Export_${ts.file}`;
                    },
                    title: function() {
                        let yr = $('#yearFilter').val() || '';
                        return `Monev Realisasi Fisik (Paket) dan Keuangan OPD - Tahun Anggaran ${yr}`;
                    },
                    messageTop: function() {
                        let ts = getExportTimestamp();
                        let filterInfo = [];
                        let t = $('#tipeFilter').val();
                        let m = $('#metodeFilter').val();
                        let s = $('#statusFilter').val();
                        if (t !== 'all') filterInfo.push('Tipe: ' + $('#tipeFilter option:selected').text());
                        if (m !== 'all') filterInfo.push('Metode/Sumber: ' + $('#metodeFilter option:selected').text());
                        if (s !== 'all') filterInfo.push('Status: ' + $('#statusFilter option:selected').text());
                        let filterStr = filterInfo.length > 0 ? ' | Filter: ' + filterInfo.join(', ') : '';
                        return `Tanggal Export: ${ts.display}${filterStr}`;
                    },
                    messageBottom: function() {
                        let ts = getExportTimestamp();
                        return `Dokumen ini diekspor secara otomatis dari Sistem INFO AJIP pada: ${ts.display}`;
                    }
                }
            ],
            language: dtLanguageId
        });

        // Expand/Collapse Child Table Logic
        $('#table-monev-opd tbody').off('click', 'td.details-control').on('click', 'td.details-control', function () {
            let tr = $(this).closest('tr');
            let row = dtTable.row(tr);
            let btn = $(this).find('button i');

            if (row.child.isShown()) {
                row.child.hide();
                tr.removeClass('shown');
                btn.removeClass('bx-minus').addClass('bx-plus');
            } else {
                let packagesRaw = tr.attr('data-packages');
                let opdName = tr.find('td:eq(1) span').text();
                let packages = [];
                try {
                    let unescaped = $('<textarea/>').html(packagesRaw).text();
                    packages = JSON.parse(unescaped);
                } catch(e) {
                    console.error("Error parsing packages JSON", e);
                }

                let childId = 'child-table-' + Math.random().toString(36).substr(2, 9);
                row.child(formatChild(packages, childId, opdName)).show();
                tr.addClass('shown');
                btn.removeClass('bx-plus').addClass('bx-minus');

                $('#' + childId).DataTable({
                    dom: '<"d-flex justify-content-between align-items-center mb-2"fB>rt<"d-flex justify-content-between align-items-center mt-2"ip>',
                    pageLength: 10,
                    buttons: [
                        {
                            extend: 'excel',
                            text: '<i class="bx bx-export me-1"></i> Export Rincian RUP',
                            className: 'btn btn-success btn-sm',
                            filename: function() {
                                let ts = getExportTimestamp();
                                let safeName = opdName.replace(/[^a-zA-Z0-9]/g, '_');
                                return `Rincian_RUP_${safeName}_Export_${ts.file}`;
                            },
                            title: function() {
                                let yr = $('#yearFilter').val() || '';
                                return `Rincian Paket RUP - ${opdName} (Tahun Anggaran ${yr})`;
                            },
                            messageTop: function() {
                                let ts = getExportTimestamp();
                                return `Tanggal Export: ${ts.display} | OPD / Satker: ${opdName}`;
                            },
                            messageBottom: function() {
                                let ts = getExportTimestamp();
                                return `Dokumen ini diekspor secara otomatis dari Sistem INFO AJIP pada: ${ts.display}`;
                            }
                        }
                    ],
                    language: dtLanguageId
                });
            }
        });
    }

    // Format Child Table (Rincian Paket RUP)
    function formatChild(packages, tableId, opdName) {
        if (!packages || packages.length === 0) {
            return '<div class="text-center p-3 text-muted">Tidak ada paket RUP yang sesuai filter pada OPD ini.</div>';
        }

        let html = `
            <div class="p-3 bg-lighter rounded" style="border-left: 4px solid #696cff;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bx bx-package me-2 text-primary"></i>Rincian Paket RUP: <span class="text-primary">${escapeHtml(opdName)}</span> (${packages.length} Paket)
                    </h6>
                </div>
                <div class="table-responsive">
                    <table id="${tableId}" class="table table-sm table-bordered bg-white w-100 align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th width="30">No</th>
                                <th>Kode RUP</th>
                                <th>Tipe RUP</th>
                                <th>Nama Paket (RUP)</th>
                                <th>Nama Paket (Realisasi)</th>
                                <th>Jenis</th>
                                <th>Metode Pemilihan</th>
                                <th>Pagu (Rp)</th>
                                <th>Realisasi (Rp)</th>
                                <th>% Realisasi</th>
                                <th>Sumber Realisasi</th>
                            </tr>
                        </thead>
                        <tbody>
        `;

        packages.forEach((pkg, idx) => {
            let trClass = pkg.realisasi > 0 ? 'table-success' : '';
            let tipeBadge = pkg.tipe === 'Penyedia' ? '<span class="badge bg-label-primary">Penyedia</span>' : '<span class="badge bg-label-success">Swakelola</span>';
            
            // Format badges for sources
            let sumberBadges = '-';
            if (pkg.sources && pkg.sources.length > 0) {
                sumberBadges = pkg.sources.map(s => {
                    let bClass = 'bg-label-info';
                    if (s === 'Tender') bClass = 'bg-label-primary';
                    else if (s === 'Non Tender') bClass = 'bg-label-secondary';
                    else if (s === 'Pencatatan Non Tender') bClass = 'bg-label-warning';
                    else if (s === 'Pencatatan Swakelola') bClass = 'bg-label-success';
                    else if (s === 'E-Purchasing') bClass = 'bg-label-danger';
                    return `<span class="badge ${bClass} me-1">${escapeHtml(s)}</span>`;
                }).join(' ');
            } else if (pkg.sumber && pkg.sumber !== '-') {
                sumberBadges = `<span class="badge bg-label-info">${escapeHtml(pkg.sumber)}</span>`;
            } else {
                sumberBadges = '<span class="badge bg-label-secondary">Belum Realisasi</span>';
            }

            let pct = pkg.pagu > 0 ? ((pkg.realisasi / pkg.pagu) * 100).toFixed(2) : 0;
            let pctBadgeClass = pct >= 80 ? 'text-success' : (pct > 0 ? 'text-info' : 'text-muted');

            html += `
                <tr class="${trClass}">
                    <td class="text-center">${idx + 1}</td>
                    <td><span class="badge bg-label-secondary font-monospace">${escapeHtml(pkg.kd_rup)}</span></td>
                    <td class="text-center">${tipeBadge}</td>
                    <td class="fw-semibold">${escapeHtml(pkg.nama_paket)}</td>
                    <td class="text-muted small">${escapeHtml(pkg.nama_paket_realisasi || '-')}</td>
                    <td>${escapeHtml(pkg.jenis)}</td>
                    <td><span class="badge bg-label-dark">${escapeHtml(formatMetodeName(pkg.metode))}</span></td>
                    <td class="text-end">${formatIDR(pkg.pagu)}</td>
                    <td class="text-end fw-bold text-success">${formatIDR(pkg.realisasi)}</td>
                    <td class="text-center fw-bold ${pctBadgeClass}">${pct}%</td>
                    <td>${sumberBadges}</td>
                </tr>
            `;
        });

        html += `</tbody></table></div></div>`;
        return html;
    }

    // Load Data from Server
    function loadData() {
        let year = $('#yearFilter').val();

        Swal.fire({
            title: 'Memuat Data...',
            text: 'Mengambil data monitoring realisasi fisik paket & keuangan OPD',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: '<?= base_url('dashboard/getMonevOpdData') ?>',
            type: 'GET',
            data: { year: year },
            dataType: 'json',
            success: function(data) {
                Swal.close();
                rawOpdData = data || [];
                populateMetodeDropdown(rawOpdData);
                applyFilters();
            },
            error: function() {
                Swal.fire('Error', 'Gagal memuat data dari server.', 'error');
            }
        });
    }

    // ==========================================
    // TABEL PROGRES SPSE LOGIC
    // ==========================================
    let rawProgresData = [];
    let filteredProgresData = [];

    function formatNumber(num) {
        return new Intl.NumberFormat('id-ID').format(num || 0);
    }

    function renderPctBadge(pct) {
        let p = parseFloat(pct) || 0;
        if (p >= 100) {
            return `<span class="badge bg-label-success">${p.toFixed(2)}%</span>`;
        } else if (p > 0) {
            return `<span class="badge bg-label-primary">${p.toFixed(2)}%</span>`;
        } else {
            return `<span class="text-muted small">0%</span>`;
        }
    }

    function renderKategoriBadge(kat) {
        let colors = {
            'Dinas': 'bg-label-primary',
            'Badan': 'bg-label-info',
            'Sekretariat': 'bg-label-secondary',
            'Kecamatan': 'bg-label-secondary',
            'RSUD': 'bg-label-danger',
            'Puskesmas': 'bg-label-success',
            'UPT': 'bg-label-warning'
        };
        let c = colors[kat] || 'bg-label-secondary';
        return `<span class="badge ${c} rounded-pill" style="font-size: 0.7rem;">${kat}</span>`;
    }

    function renderKeteranganBadge(ket) {
        if (!ket || ket.trim() === '') return '<span class="text-muted">-</span>';
        if (ket.toLowerCase().includes('tidak ada progres')) {
            return `<span class="badge bg-label-secondary text-danger" style="font-size: 0.72rem;">${ket}</span>`;
        }
        return `<span class="badge bg-label-secondary" style="font-size: 0.72rem;">${ket}</span>`;
    }

    function loadMonevProgresData() {
        $('#tbody-progres-spse').html(`
            <tr>
                <td colspan="28" class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Memuat data progres SPSE...
                </td>
            </tr>
        `);

        $.ajax({
            url: '<?= base_url('dashboard/getMonevProgresData') ?>',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    rawProgresData = res.data || [];
                    if (res.update_data) $('#labelUpdateData').text(res.update_data);
                    if (res.update_rup) $('#labelUpdateRup').text(res.update_rup);
                    applyProgresFilter();
                } else {
                    let msg = res.message || 'Gagal memuat data.';
                    $('#tbody-progres-spse').html('<tr><td colspan="28" class="text-center text-danger py-3"><i class="bx bx-error-circle me-1"></i> ' + escapeHtml(msg) + '</td></tr>');
                }
            },
            error: function(xhr) {
                let msg = 'Terjadi kesalahan server saat mengambil data.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                $('#tbody-progres-spse').html('<tr><td colspan="28" class="text-center text-danger py-3"><i class="bx bx-error-circle me-1"></i> ' + escapeHtml(msg) + '</td></tr>');
            }
        });
    }

    function syncMonevProgresNow() {
        const btn = $('#btnSyncProgres');
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyinkronkan...');

        const currentYear = new Date().getFullYear();

        $.ajax({
            url: '<?= base_url('user/do-sync/monev_progres') ?>?tahun=' + currentYear,
            type: 'GET',
            dataType: 'text',
            success: function(rawText) {
                btn.prop('disabled', false).html(originalHtml);
                let res;
                try {
                    let clean = (rawText || '').trim();
                    const firstBrace = clean.indexOf('{');
                    const lastBrace = clean.lastIndexOf('}');
                    if (firstBrace !== -1 && lastBrace !== -1 && lastBrace >= firstBrace) {
                        clean = clean.substring(firstBrace, lastBrace + 1);
                    }
                    res = JSON.parse(clean);
                } catch (e) {
                    let msg = 'Server merespon format tidak valid.';
                    if (rawText && rawText.includes('<title>')) {
                        const m = rawText.match(/<title>(.*?)<\/title>/i);
                        if (m) msg = m[1];
                    }
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
                    } else {
                        alert(msg);
                    }
                    return;
                }

                if (res.status === 'success') {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sinkronisasi Berhasil',
                            text: res.message || 'Tabel progres SPSE berhasil disinkronkan langsung dari data transaksi lokal.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        alert(res.message || 'Tabel progres SPSE berhasil disinkronkan.');
                    }
                    loadMonevProgresData();
                } else {
                    let msg = res.message || 'Gagal sinkronisasi data progres.';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
                    } else {
                        alert(msg);
                    }
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(originalHtml);
                let msg = 'Terjadi kesalahan saat sinkronisasi.';
                try {
                    let parsed = JSON.parse(xhr.responseText);
                    if (parsed && parsed.message) msg = parsed.message;
                } catch (_) {
                    if (xhr.status === 403) msg = 'Akses ditolak. Anda tidak memiliki izin untuk sinkronisasi.';
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
                } else {
                    alert(msg);
                }
            }
        });
    }

    function applyProgresFilter() {
        let kategori = $('#filterProgresKategori').val();
        let status = $('#filterProgresStatus').val();
        let query = ($('#searchProgresSatker').val() || '').toLowerCase().trim();

        filteredProgresData = rawProgresData.filter(function(row) {
            // Filter Kategori
            if (kategori !== 'all' && row.kategori !== kategori) {
                return false;
            }

            // Filter Status
            if (status === 'has_progress') {
                let totalInput = (parseInt(row.epl_sudah_input) || 0) +
                                 (parseInt(row.tender_sudah_input) || 0) +
                                 (parseInt(row.non_tender_sudah) || 0) +
                                 (parseInt(row.swakelola_sudah) || 0) +
                                 (parseInt(row.epurchasing_sudah) || 0);
                if (totalInput <= 0) return false;
            } else if (status === 'no_progress') {
                let totalInput = (parseInt(row.epl_sudah_input) || 0) +
                                 (parseInt(row.tender_sudah_input) || 0) +
                                 (parseInt(row.non_tender_sudah) || 0) +
                                 (parseInt(row.swakelola_sudah) || 0) +
                                 (parseInt(row.epurchasing_sudah) || 0);
                if (totalInput > 0) return false;
            } else if (status === 'marked_no_progress') {
                if (!row.keterangan || !row.keterangan.toLowerCase().includes('tidak ada progres')) return false;
            }

            // Search query
            if (query !== '') {
                let satkerName = (row.satker || '').toLowerCase();
                if (!satkerName.includes(query)) return false;
            }

            return true;
        });

        renderProgresTable(filteredProgresData);
        updateProgresKpi(filteredProgresData);
    }

    function renderProgresTable(data) {
        if (!data || data.length === 0) {
            $('#tbody-progres-spse').html('<tr><td colspan="28" class="text-center py-4 text-muted">Tidak ada data OPD yang sesuai filter.</td></tr>');
            return;
        }

        let html = '';
        data.forEach(function(r) {
            html += `<tr>
                <td class="text-center fw-semibold text-muted">${r.no}</td>
                <td>
                    <div class="fw-semibold text-dark">${escapeHtml(r.satker)}</div>
                    <div class="mt-1">${renderKategoriBadge(r.kategori)}</div>
                </td>
                <td class="text-end fw-semibold text-primary">${formatNumber(r.rup_penyedia)}</td>
                <td class="text-end fw-semibold text-primary">${formatNumber(r.rup_swakelola)}</td>
                <td class="text-end">${formatNumber(r.rup_pl)}</td>
                <td class="text-end">${formatNumber(r.rup_juksung)}</td>
                <td class="text-end">${formatNumber(r.rup_epurchasing)}</td>
                <td class="text-end">${formatNumber(r.rup_dikecualikan)}</td>
                <td class="text-end">${formatNumber(r.rup_tender)}</td>

                <td class="text-end">${formatNumber(r.epl_dilaksanakan)}</td>
                <td class="text-end text-muted">${formatNumber(r.epl_belum_input)}</td>
                <td class="text-end text-success fw-semibold">${formatNumber(r.epl_sudah_input)}</td>
                <td class="text-center">${renderPctBadge(r.epl_pct)}</td>

                <td class="text-end">${formatNumber(r.tender_dilaksanakan)}</td>
                <td class="text-end text-muted">${formatNumber(r.tender_belum_input)}</td>
                <td class="text-end text-success fw-semibold">${formatNumber(r.tender_sudah_input)}</td>
                <td class="text-center">${renderPctBadge(r.tender_pct)}</td>

                <td class="text-end text-muted">${formatNumber(r.non_tender_belum)}</td>
                <td class="text-end text-success fw-semibold">${formatNumber(r.non_tender_sudah)}</td>
                <td class="text-center">${renderPctBadge(r.non_tender_pct)}</td>

                <td class="text-end text-muted">${formatNumber(r.swakelola_belum)}</td>
                <td class="text-end text-success fw-semibold">${formatNumber(r.swakelola_sudah)}</td>
                <td class="text-center">${renderPctBadge(r.swakelola_pct)}</td>

                <td class="text-end">${formatNumber(r.epurchasing_jml)}</td>
                <td class="text-end text-muted">${formatNumber(r.epurchasing_belum)}</td>
                <td class="text-end text-success fw-semibold">${formatNumber(r.epurchasing_sudah)}</td>
                <td class="text-center">${renderPctBadge(r.epurchasing_pct)}</td>

                <td class="text-center">${renderKeteranganBadge(r.keterangan)}</td>
            </tr>`;
        });
        $('#tbody-progres-spse').html(html);
    }

    function updateProgresKpi(data) {
        let totalSatker = data.length;
        let rupP = 0, rupS = 0, rupPl = 0, rupJuksung = 0, rupEpurchasing = 0, rupDikecualikan = 0, rupTender = 0;
        let eplDilaksanakan = 0, eplBelum = 0, eplSudah = 0;
        let tenderDilaksanakan = 0, tenderBelum = 0, tenderSudah = 0;
        let nonTenderBelum = 0, nonTenderSudah = 0;
        let swakelolaBelum = 0, swakelolaSudah = 0;
        let epurchasingJml = 0, epurchasingBelum = 0, epurchasingSudah = 0;

        data.forEach(function(r) {
            rupP += parseInt(r.rup_penyedia) || 0;
            rupS += parseInt(r.rup_swakelola) || 0;
            rupPl += parseInt(r.rup_pl) || 0;
            rupJuksung += parseInt(r.rup_juksung) || 0;
            rupEpurchasing += parseInt(r.rup_epurchasing) || 0;
            rupDikecualikan += parseInt(r.rup_dikecualikan) || 0;
            rupTender += parseInt(r.rup_tender) || 0;

            eplDilaksanakan += parseInt(r.epl_dilaksanakan) || 0;
            eplBelum += parseInt(r.epl_belum_input) || 0;
            eplSudah += parseInt(r.epl_sudah_input) || 0;

            tenderDilaksanakan += parseInt(r.tender_dilaksanakan) || 0;
            tenderBelum += parseInt(r.tender_belum_input) || 0;
            tenderSudah += parseInt(r.tender_sudah_input) || 0;

            nonTenderBelum += parseInt(r.non_tender_belum) || 0;
            nonTenderSudah += parseInt(r.non_tender_sudah) || 0;

            swakelolaBelum += parseInt(r.swakelola_belum) || 0;
            swakelolaSudah += parseInt(r.swakelola_sudah) || 0;

            epurchasingJml += parseInt(r.epurchasing_jml) || 0;
            epurchasingBelum += parseInt(r.epurchasing_belum) || 0;
            epurchasingSudah += parseInt(r.epurchasing_sudah) || 0;
        });

        let eplPct = eplDilaksanakan > 0 ? ((eplSudah / eplDilaksanakan) * 100).toFixed(2) : '0.00';
        let tenderPct = tenderDilaksanakan > 0 ? ((tenderSudah / tenderDilaksanakan) * 100).toFixed(2) : '0.00';
        let totNT = nonTenderBelum + nonTenderSudah;
        let nonTenderPct = totNT > 0 ? ((nonTenderSudah / totNT) * 100).toFixed(2) : '0.00';
        let totSwk = swakelolaBelum + swakelolaSudah;
        let swakelolaPct = totSwk > 0 ? ((swakelolaSudah / totSwk) * 100).toFixed(2) : '0.00';
        let epurchasingPct = epurchasingJml > 0 ? ((epurchasingSudah / epurchasingJml) * 100).toFixed(2) : '0.00';

        // Update Top Cards
        $('#badgeTotalSatker').text(totalSatker + ' OPD');
        $('#kpiTotalSatker').text(totalSatker + ' OPD');
        $('#kpiTotalRup').text(formatNumber(rupP + rupS));
        $('#kpiRupP').text(formatNumber(rupP));
        $('#kpiRupS').text(formatNumber(rupS));

        $('#kpiEplPct').text(eplPct + '%');
        $('#kpiEplDilaksanakan').text(formatNumber(eplDilaksanakan));
        $('#kpiEplSudah').text(formatNumber(eplSudah));
        $('#kpiEplBelum').text(formatNumber(eplBelum));

        $('#kpiTenderPct').text(tenderPct + '%');
        $('#kpiTenderDilaksanakan').text(formatNumber(tenderDilaksanakan));
        $('#kpiTenderSudah').text(formatNumber(tenderSudah));
        $('#kpiTenderBelum').text(formatNumber(tenderBelum));

        $('#kpiNonTenderPct').text(nonTenderPct + '%');
        $('#kpiNonTenderSudah').text(formatNumber(nonTenderSudah));
        $('#kpiNonTenderBelum').text(formatNumber(nonTenderBelum));

        $('#kpiSwakelolaPct').text(swakelolaPct + '%');
        $('#kpiSwakelolaSudah').text(formatNumber(swakelolaSudah));
        $('#kpiSwakelolaBelum').text(formatNumber(swakelolaBelum));

        $('#kpiEpurchasingPct').text(epurchasingPct + '%');
        $('#kpiEpurchasingJml').text(formatNumber(epurchasingJml));
        $('#kpiEpurchasingSudah').text(formatNumber(epurchasingSudah));
        $('#kpiEpurchasingBelum').text(formatNumber(epurchasingBelum));

        // Update Table Footers
        $('#tfRupP').text(formatNumber(rupP));
        $('#tfRupS').text(formatNumber(rupS));
        $('#tfRupPl').text(formatNumber(rupPl));
        $('#tfRupJuksung').text(formatNumber(rupJuksung));
        $('#tfRupEpurchasing').text(formatNumber(rupEpurchasing));
        $('#tfRupDikecualikan').text(formatNumber(rupDikecualikan));
        $('#tfRupTender').text(formatNumber(rupTender));

        $('#tfEplDilaksanakan').text(formatNumber(eplDilaksanakan));
        $('#tfEplBelum').text(formatNumber(eplBelum));
        $('#tfEplSudah').text(formatNumber(eplSudah));
        $('#tfEplPct').text(eplPct + '%');

        $('#tfTenderDilaksanakan').text(formatNumber(tenderDilaksanakan));
        $('#tfTenderBelum').text(formatNumber(tenderBelum));
        $('#tfTenderSudah').text(formatNumber(tenderSudah));
        $('#tfTenderPct').text(tenderPct + '%');

        $('#tfNonTenderBelum').text(formatNumber(nonTenderBelum));
        $('#tfNonTenderSudah').text(formatNumber(nonTenderSudah));
        $('#tfNonTenderPct').text(nonTenderPct + '%');

        $('#tfSwakelolaBelum').text(formatNumber(swakelolaBelum));
        $('#tfSwakelolaSudah').text(formatNumber(swakelolaSudah));
        $('#tfSwakelolaPct').text(swakelolaPct + '%');

        $('#tfEpurchasingJml').text(formatNumber(epurchasingJml));
        $('#tfEpurchasingBelum').text(formatNumber(epurchasingBelum));
        $('#tfEpurchasingSudah').text(formatNumber(epurchasingSudah));
        $('#tfEpurchasingPct').text(epurchasingPct + '%');
    }

    $(document).ready(function() {
        // Load both datasets
        loadMonevProgresData();
        loadData();

        // Progres SPSE event listeners
        $('#filterProgresKategori, #filterProgresStatus').change(function() {
            applyProgresFilter();
        });

        $('#searchProgresSatker').on('keyup input', function() {
            applyProgresFilter();
        });

        $('#btnResetProgresFilter').click(function() {
            $('#filterProgresKategori').val('all');
            $('#filterProgresStatus').val('all');
            $('#searchProgresSatker').val('');
            applyProgresFilter();
        });

        // Tab shown handler to resize charts and datatables
        $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function(e) {
            if (e.target.id === 'tab-realisasi-rup') {
                setTimeout(function() {
                    if (filteredOpdData && filteredOpdData.length > 0) {
                        renderChart(filteredOpdData);
                    }
                    if (dtTable) {
                        dtTable.columns.adjust().draw();
                    }
                    window.dispatchEvent(new Event('resize'));
                }, 100);
            }
        });

        // Chart Card Collapse button icon toggle
        $('#collapseChartBody').on('hidden.bs.collapse', function () {
            $('#btnToggleChartCollapse i').removeClass('bx-chevron-up').addClass('bx-chevron-down');
        }).on('shown.bs.collapse', function () {
            $('#btnToggleChartCollapse i').removeClass('bx-chevron-down').addClass('bx-chevron-up');
            setTimeout(function() {
                if (filteredOpdData && filteredOpdData.length > 0) {
                    renderChart(filteredOpdData);
                }
                window.dispatchEvent(new Event('resize'));
            }, 50);
        });

        // Existing event listeners for Realisasi RUP
        $('#btnFilter').click(function() {
            applyFilters();
        });

        $('#yearFilter').change(function() {
            loadData();
        });

        $('#tipeFilter').change(function() {
            populateMetodeDropdown(rawOpdData);
            applyFilters();
        });

        $('#metodeFilter, #statusFilter').change(function() {
            applyFilters();
        });

        $('#btnReset').click(function() {
            $('#tipeFilter').val('all');
            populateMetodeDropdown(rawOpdData);
            $('#metodeFilter').val('all');
            $('#statusFilter').val('all');
            applyFilters();
        });
    });
</script>
<?= $this->endSection() ?>
