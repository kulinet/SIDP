<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Breadcrumb & Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold py-1 mb-1">
                <span class="text-muted fw-light">Database /</span> Scrape Penyedia SPSE Inaproc
            </h4>
            <p class="text-muted small mb-0">
                Bot otomatisasi pengambilan data profil &amp; legalitas penyedia resmi dari portal <strong>SPSE Inaproc Lombok Barat</strong>
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= base_url('dashboard/detail-penyedia') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bx bx-arrow-back me-1"></i> Kembali ke Detail Penyedia
            </a>
            <a href="<?= base_url('dashboard/exportContractors?format=xlsx') ?>" class="btn btn-success btn-sm shadow-sm">
                <i class="bx bxs-file-export me-1"></i> Export Excel (.xlsx)
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="bx bx-check-circle fs-4 me-2"></i>
                <div><?= session()->getFlashdata('success') ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="bx bx-error-circle fs-4 me-2"></i>
                <div><?= session()->getFlashdata('error') ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <!-- Total Data SPSE -->
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm border-start border-success border-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge bg-label-success"><i class="bx bx-check-shield me-1"></i>SPSE Inaproc</span>
                        <?php if ($total_contractors > 0): ?>
                            <span class="badge bg-success">Aktif</span>
                        <?php else: ?>
                            <span class="badge bg-warning">Siap Di-scrap</span>
                        <?php endif; ?>
                    </div>
                    <span class="text-muted small d-block">TOTAL PENYEDIA TERDATA</span>
                    <h3 class="mb-1 fw-bold text-success"><?= number_format($total_contractors, 0, ',', '.') ?></h3>
                    <div class="small text-muted">
                        <i class="bx bx-time-five me-1"></i> Update: <?= $last_scrape ?: 'Belum pernah' ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Penyedia NTB -->
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge bg-label-primary">Domisili NTB</span>
                        <span class="badge bg-label-secondary"><?= $total_provinces ?> Wilayah</span>
                    </div>
                    <span class="text-muted small d-block">PENYEDIA NTB / LOBAR</span>
                    <h3 class="mb-1 fw-bold text-primary"><?= number_format($ntb_count, 0, ',', '.') ?></h3>
                    <div class="small text-muted">
                        <i class="bx bx-map-pin me-1"></i> Lombok Barat, Mataram &amp; Sekitarnya
                    </div>
                </div>
            </div>
        </div>

        <!-- NPWP Valid -->
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge bg-label-info">Validasi NPWP</span>
                        <i class="bx bx-id-card text-info fs-5"></i>
                    </div>
                    <span class="text-muted small d-block">NPWP PENYEDIA RESMI</span>
                    <h3 class="mb-1 fw-bold text-info"><?= number_format($has_npwp, 0, ',', '.') ?></h3>
                    <div class="small text-muted">
                        <i class="bx bx-check-circle me-1"></i> 15/16 Digit KPP Terpetakan
                    </div>
                </div>
            </div>
        </div>

        <!-- Kelengkapan Kontak -->
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge bg-label-warning">Alamat &amp; Kontak</span>
                        <i class="bx bx-phone-call text-warning fs-5"></i>
                    </div>
                    <div class="d-flex justify-content-between small mb-1">
                        <span>Alamat Riil:</span>
                        <strong class="text-success"><?= number_format($has_address, 0, ',', '.') ?></strong>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span>No. Telepon:</span>
                        <strong class="text-primary"><?= number_format($has_phone, 0, ',', '.') ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SPSE Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md me-3 bg-label-primary rounded p-2 d-flex align-items-center justify-content-center">
                        <i class="bx bx-buildings fs-3 text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Rekanan SPSE</div>
                        <h5 class="mb-0 fw-bold" id="statTotalSpse">6,569</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md me-3 bg-label-success rounded p-2 d-flex align-items-center justify-content-center">
                        <i class="bx bx-map-pin fs-3 text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Domisili Terdata (Kab/Kota)</div>
                        <h5 class="mb-0 fw-bold text-success" id="statDomisiliFilled">-</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="avatar avatar-md me-3 bg-label-warning rounded p-2 d-flex align-items-center justify-content-center">
                        <i class="bx bx-time-five fs-3 text-warning"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Perlu Dilengkapi Domisili</div>
                        <h5 class="mb-0 fw-bold text-warning" id="statDomisiliPending">-</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Panel: Bot SPSE Inaproc Verifikasi Rekanan -->
    <div class="card border-0 shadow-sm mb-4 border-start border-success border-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center">
                <div class="avatar avatar-md me-3 bg-label-success rounded p-2 d-flex align-items-center justify-content-center">
                    <i class="bx bx-bot fs-3 text-success"></i>
                </div>
                <div>
                    <h5 class="card-title mb-0 fw-bold">Bot SPSE Inaproc (Verifikasi Rekanan &amp; Domisili)</h5>
                    <small class="text-muted">
                        Target Portal: <a href="https://spse.inaproc.id/lombokbaratkab/verifikasi/rekanan" target="_blank" rel="noreferrer" class="text-decoration-underline text-success fw-semibold">https://spse.inaproc.id/lombokbaratkab/verifikasi/rekanan</a>
                    </small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span id="spseBotBadge" class="badge bg-label-secondary fs-7 py-2 px-3">
                    <i class="bx bx-circle me-1"></i> Standby (Idle)
                </span>
                <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm" id="btnLaunchEnrich" title="Buka link detail rekanan untuk mengambil Kabupaten/Kota, Provinsi, dan Terdaftar di secara presisi">
                    <i class="bx bx-map-pin me-1"></i> Lengkapi Domisili (Kab/Kota)
                </button>
                <button type="button" class="btn btn-outline-success btn-sm px-3 shadow-sm" id="btnLaunchScrape" title="Ambil daftar rekanan baru dari tabel SPSE">
                    <i class="bx bx-sync me-1"></i> Scrape Daftar Baru
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm px-3 d-none" id="btnStopSpse">
                    <i class="bx bx-stop-circle me-1"></i> Hentikan Bot
                </button>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="p-3 bg-light rounded border h-100">
                        <strong class="text-dark d-block mb-2">
                            <i class="bx bx-info-circle text-primary me-1"></i> Mode Pengambilan Data:
                        </strong>
                        <ul class="small text-muted ps-3 mb-3" style="line-height: 1.8;">
                            <li><strong class="text-primary">📍 Lengkapi Domisili (Kab/Kota):</strong> Mengunjungi URL link detail masing-masing penyedia (<code>verifikasi/view?id=...</code>) di latar belakang untuk mengekstrak data resmi <strong>Kabupaten/Kota</strong>, <strong>Provinsi</strong>, <strong>Terdaftar di</strong>, dan <strong>Alamat Lengkap</strong>. (Sangat cepat ~4-5 menit untuk ribuan data).</li>
                            <li><strong class="text-success">🔄 Scrape Daftar Baru:</strong> Membaca tabel daftar rekanan dari awal untuk mendeteksi penambahan vendor baru.</li>
                        </ul>
                        <div class="alert alert-primary py-2 px-3 small mb-0 d-flex align-items-center">
                            <i class="bx bx-check-shield fs-4 me-2 text-primary"></i>
                            <div>Login di Chrome hanya perlu dilakukan 1x. Sesi akan otomatis digunakan bot untuk mengambil detail resmi.</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong class="text-dark small"><i class="bx bx-terminal text-dark me-1"></i> Live Terminal &amp; Status Bot:</strong>
                        <div class="small text-muted" id="spseStatusText">Bot standby. Siap dijalankan.</div>
                    </div>

                    <!-- Progress Bar (shown when scraping / enriching / saving) -->
                    <div class="progress mb-2 d-none" id="spseProgressBarContainer" style="height: 12px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary font-monospace" id="spseProgressBar" role="progressbar" style="width: 0%; font-size: 10px;">0%</div>
                    </div>

                    <!-- Console Terminal Box -->
                    <div id="spseTerminalLog" class="p-3 rounded font-monospace small" style="height: 215px; overflow-y: auto; font-size: 11.5px; line-height: 1.6; white-space: pre-wrap; background-color: #1e1e2d !important; color: #50fa7b !important; border: 1px solid #323248;">[<?= date('H:i:s') ?>] Bot SPSE siap dijalankan. Klik "Lengkapi Domisili (Kab/Kota)" untuk menyempurnakan data Kabupaten/Kota seluruh penyedia.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Complete Database Penyedia Table with Paging, Search & Export -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="card-title mb-0 fw-bold d-flex align-items-center">
                    <i class="bx bx-table text-success me-2 fs-4"></i> Database Penyedia Hasil Scrape SPSE
                </h5>
                <small class="text-muted">Daftar lengkap rekanan terdaftar di SPSE Inaproc dengan pencarian, pagination, dan ekspor spreadsheet</small>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Export Dropdown -->
                <div class="btn-group btn-group-sm" role="group">
                    <button id="btnExportGroup" type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bx bx-download me-1"></i> Export Data
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="btnExportGroup">
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="<?= base_url('dashboard/exportContractors?format=xlsx') ?>">
                                <i class="bx bxs-file-excel text-success me-2 fs-5"></i>
                                <div>
                                    <div class="fw-semibold">Export Excel (.xlsx)</div>
                                    <small class="text-muted">Format spreadsheet Excel lengkap</small>
                                </div>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="<?= base_url('dashboard/exportContractors?format=csv') ?>">
                                <i class="bx bx-file text-primary me-2 fs-5"></i>
                                <div>
                                    <div class="fw-semibold">Export CSV (.csv)</div>
                                    <small class="text-muted">File CSV kompatibel UTF-8</small>
                                </div>
                            </a>
                        </li>
                    </ul>
                </div>

                <button type="button" class="btn btn-outline-primary btn-sm" id="btnRefreshContractorsTable" title="Muat Ulang Data">
                    <i class="bx bx-refresh fs-5"></i>
                </button>
            </div>
        </div>

        <!-- Filter & Pencarian -->
        <div class="card-body border-bottom pb-3 pt-3" style="background:#fafbfc;">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4 col-xl-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" for="fSearch">Pencarian</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="search" id="fSearch" class="form-control" placeholder="Nama, NPWP, NIB, alamat, kontak, direktur...">
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" for="fWilayah">Wilayah</label>
                    <select id="fWilayah" class="form-select form-select-sm">
                        <option value="">Semua Wilayah</option>
                        <option value="lobar">Lombok Barat</option>
                        <option value="mataram">Kota Mataram</option>
                        <option value="ntb">Seluruh NTB</option>
                        <option value="ntb_lain">NTB Lainnya</option>
                        <option value="luar">Luar NTB</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" for="fProvince">Provinsi</label>
                    <select id="fProvince" class="form-select form-select-sm"><option value="">Semua Provinsi</option></select>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" for="fKota">Kab/Kota</label>
                    <select id="fKota" class="form-select form-select-sm"><option value="">Semua Kab/Kota</option></select>
                </div>
                <div class="col-6 col-md-4 col-xl-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" for="fBentuk">Bentuk Usaha</label>
                    <select id="fBentuk" class="form-select form-select-sm"><option value="">Semua Bentuk Usaha</option></select>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" for="fAlamat">Alamat</label>
                    <select id="fAlamat" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="ada">Sudah ada alamat</option>
                        <option value="kosong">Alamat kosong</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" for="fDomisili">Status Domisili</label>
                    <select id="fDomisili" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="terdata">Terdata</option>
                        <option value="belum">Belum terdata</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" for="fKontak">Kontak</label>
                    <select id="fKontak" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="ada">Ada telp/email</option>
                        <option value="kosong">Tanpa kontak</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" for="fNpwp">NPWP</label>
                    <select id="fNpwp" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="ada">Ada NPWP</option>
                        <option value="kosong">NPWP kosong</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" for="fDateFrom">Update Dari</label>
                    <input type="date" id="fDateFrom" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" for="fDateTo">Update Sampai</label>
                    <input type="date" id="fDateTo" class="form-control form-control-sm">
                </div>
                <div class="col-12 col-md-6 col-xl-2 d-flex gap-2">
                    <button type="button" id="btnResetFilters" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bx bx-reset me-1"></i> Reset Filter
                    </button>
                </div>
            </div>
            <div class="small text-muted mt-2" id="filterSummary"></div>
        </div>

        <div class="card-body p-3">
            <div class="table-responsive">
                <table id="table-contractors" class="table table-hover table-striped align-middle mb-0" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 45px;" class="text-center">No</th>
                            <th style="min-width: 220px;">Nama Perusahaan</th>
                            <th style="width: 160px;">NPWP</th>
                            <th style="width: 140px;">Domisili</th>
                            <th style="width: 130px;">Provinsi</th>
                            <th style="min-width: 240px;">Alamat Lengkap</th>
                            <th style="width: 160px;">Kontak</th>
                            <th style="width: 120px;" class="text-center">Sumber</th>
                            <th style="width: 110px;" class="text-center">Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Diisi secara dinamis melalui server-side DataTables -->
                    </tbody>
                </table>
            </div>
        </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- DataTables JS CDN -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Parameter filter aktif
    function getFilterParams() {
        return {
            f_wilayah: $('#fWilayah').val(),
            f_province: $('#fProvince').val(),
            f_kota: $('#fKota').val(),
            f_bentuk: $('#fBentuk').val(),
            f_alamat: $('#fAlamat').val(),
            f_domisili: $('#fDomisili').val(),
            f_kontak: $('#fKontak').val(),
            f_npwp: $('#fNpwp').val(),
            f_date_from: $('#fDateFrom').val(),
            f_date_to: $('#fDateTo').val()
        };
    }

    var filterOptionsLoaded = false;
    function fillSelect(sel, items) {
        var $s = $(sel), cur = $s.val();
        $s.find('option:not(:first)').remove();
        (items || []).forEach(function(v) { $s.append($('<option>').val(v).text(v)); });
        $s.val(cur);
    }

    // 1. DataTables Server-Side untuk Database Kontraktor
    var dtContractors = $('#table-contractors').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "<?= base_url('dashboard/getScrapeContractorsDatatables') ?>",
            type: "GET",
            data: function(d) {
                // Semua sumber atau khusus spse
                d.source = 'spse';
                var f = getFilterParams();
                for (var k in f) { d[k] = f[k]; }
            },
            dataSrc: function(json) {
                if (json && json.filter_options && !filterOptionsLoaded) {
                    filterOptionsLoaded = true;
                    fillSelect('#fProvince', json.filter_options.province);
                    fillSelect('#fKota', json.filter_options.kota);
                    fillSelect('#fBentuk', json.filter_options.bentuk);
                }
                return json.data;
            },
            error: function(xhr, error, code) {
                console.error("DataTables Error: " + error + " (" + code + ")");
            }
        },
        columns: [
            { data: 'no', orderable: false, className: 'text-center' },
            { data: 'contractor_name' },
            { data: 'npwp' },
            { data: 'domisili_kota' },
            { data: 'province' },
            { data: 'address' },
            { data: 'contact' },
            { data: 'source', className: 'text-center' },
            { data: 'updated_at', className: 'text-center' }
        ],
        order: [[8, 'desc']],
        dom: '<"row mx-1 mb-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6">>rt<"row mt-2"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            emptyTable: "Belum ada data penyedia SPSE tersimpan",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 entri",
            infoFiltered: "(disaring dari _MAX_ total entri)",
            lengthMenu: "Tampilkan _MENU_ entri",
            loadingRecords: "Sedang memuat data...",
            processing: '<i class="bx bx-spin bx-loader-alt fs-3 text-success"></i> Memuat data penyedia...',
            search: "Cari:",
            zeroRecords: "Tidak ditemukan data yang sesuai",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        }
    });

    $('#btnRefreshContractorsTable').on('click', function() {
        dtContractors.ajax.reload(null, false);
    });


    // Ringkasan filter aktif & sinkronisasi link export
    function refreshFilterUi() {
        var labels = [];
        $('#fWilayah, #fProvince, #fKota, #fBentuk, #fAlamat, #fDomisili, #fKontak, #fNpwp').each(function() {
            if ($(this).val()) labels.push($(this).prev('label').text() + ': ' + $(this).find('option:selected').text());
        });
        if ($('#fDateFrom').val()) labels.push('Dari ' + $('#fDateFrom').val());
        if ($('#fDateTo').val()) labels.push('Sampai ' + $('#fDateTo').val());
        if ($('#fSearch').val().trim()) labels.push('Cari: "' + $('#fSearch').val().trim() + '"');
        $('#filterSummary').text(labels.length ? 'Filter aktif — ' + labels.join(' | ') : '');

        var qs = $.param($.extend({ source: 'spse', search: $('#fSearch').val().trim() }, getFilterParams()));
        $('a[href*="dashboard/exportContractors"]').each(function() {
            var base = this.href.split('?')[0];
            var fmt = /format=csv/.test(this.href) ? 'csv' : 'xlsx';
            this.href = base + '?format=' + fmt + '&' + qs;
        });
    }

    var searchTimer = null;
    $('#fSearch').on('input', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            dtContractors.search($('#fSearch').val().trim()).draw();
            refreshFilterUi();
        }, 350);
    });

    $('#fWilayah, #fProvince, #fKota, #fBentuk, #fAlamat, #fDomisili, #fKontak, #fNpwp, #fDateFrom, #fDateTo').on('change', function() {
        dtContractors.ajax.reload();
        refreshFilterUi();
    });

    $('#btnResetFilters').on('click', function() {
        $('#fSearch, #fDateFrom, #fDateTo').val('');
        $('#fWilayah, #fProvince, #fKota, #fBentuk, #fAlamat, #fDomisili, #fKontak, #fNpwp').val('');
        dtContractors.search('').ajax.reload();
        refreshFilterUi();
    });
    refreshFilterUi();

    // 2. CSRF Token Helper
    function getCsrfData() {
        let data = {};
        let csrfName = '<?= csrf_token() ?>';
        let csrfHash = '<?= csrf_hash() ?>';
        data[csrfName] = csrfHash;
        return data;
    }

    // 3. Bot SPSE Inaproc Controller & Realtime Polling
    var spsePollTimer = null;

    function updateSpseBadge(status, text) {
        let badge = $('#spseBotBadge');
        badge.removeClass('bg-label-secondary bg-label-warning bg-label-info bg-label-primary bg-label-success bg-label-danger');
        
        switch (status) {
            case 'starting':
                badge.addClass('bg-label-warning').html('<i class="bx bx-loader-alt bx-spin me-1"></i> ' + (text || 'Membuka Chrome...'));
                break;
            case 'waiting_login':
                badge.addClass('bg-label-info').html('<i class="bx bx-user-check bx-burst me-1"></i> ' + (text || 'Silakan Login di Chrome'));
                break;
            case 'enriching':
                badge.addClass('bg-label-primary').html('<i class="bx bx-map-pin bx-fade-down me-1"></i> ' + (text || 'Melengkapi Domisili...'));
                break;
            case 'scraping':
                badge.addClass('bg-label-primary').html('<i class="bx bx-download bx-fade-down me-1"></i> ' + (text || 'Sedang Menyedot Data...'));
                break;
            case 'saving':
                badge.addClass('bg-label-info').html('<i class="bx bx-data bx-spin me-1"></i> ' + (text || 'Menyimpan ke Database...'));
                break;
            case 'completed':
            case 'finished':
                badge.addClass('bg-label-success').html('<i class="bx bx-check-circle me-1"></i> ' + (text || 'Selesai'));
                break;
            case 'stopped':
                badge.addClass('bg-label-secondary').html('<i class="bx bx-stop-circle me-1"></i> ' + (text || 'Dihentikan'));
                break;
            case 'error':
                badge.addClass('bg-label-danger').html('<i class="bx bx-error-circle me-1"></i> ' + (text || 'Terjadi Kendala'));
                break;
            default:
                badge.addClass('bg-label-secondary').html('<i class="bx bx-circle me-1"></i> ' + (text || 'Standby (Idle)'));
                break;
        }
    }

    function pollSpseStatus() {
        $.ajax({
            url: "<?= base_url('dashboard/get-bot-spse-status') ?>",
            type: "GET",
            dataType: "json",
            success: function(res) {
                if (!res) return;
                
                updateSpseBadge(res.status);
                if (res.message) {
                    $('#spseStatusText').text(res.message);
                }

                if (res.logs) {
                    let term = $('#spseTerminalLog');
                    term.text(res.logs);
                    term.scrollTop(term[0].scrollHeight);
                }

                // Update Database Statistics Cards
                if (res.stats) {
                    if (res.stats.total_spse !== undefined) {
                        $('#statTotalSpse').text(res.stats.total_spse.toLocaleString('id-ID'));
                    }
                    if (res.stats.domisili_filled !== undefined) {
                        $('#statDomisiliFilled').text(res.stats.domisili_filled.toLocaleString('id-ID'));
                    }
                    if (res.stats.domisili_pending !== undefined) {
                        $('#statDomisiliPending').text(res.stats.domisili_pending.toLocaleString('id-ID'));
                    }
                }

                // Update Progress bar
                if (res.total > 0 && (res.status === 'scraping' || res.status === 'saving' || res.status === 'enriching' || res.status === 'completed')) {
                    $('#spseProgressBarContainer').removeClass('d-none');
                    let pct = Math.min(100, Math.round((res.count / res.total) * 100));
                    if (res.status === 'completed') pct = 100;
                    let label = (res.status === 'enriching') ? 'Detail Domisili: ' : 'Data: ';
                    $('#spseProgressBar').css('width', pct + '%').text(pct + '% (' + label + res.count.toLocaleString('id-ID') + ' / ' + res.total.toLocaleString('id-ID') + ')');
                } else if (res.status === 'completed') {
                    $('#spseProgressBarContainer').removeClass('d-none');
                    $('#spseProgressBar').css('width', '100%').text('100% Selesai');
                }

                // Control buttons
                if (res.is_running) {
                    $('#btnLaunchEnrich').prop('disabled', true);
                    $('#btnLaunchScrape').prop('disabled', true);
                    $('#btnStopSpse').removeClass('d-none');
                } else {
                    $('#btnLaunchEnrich').prop('disabled', false);
                    $('#btnLaunchScrape').prop('disabled', false);
                    $('#btnStopSpse').addClass('d-none');
                    
                    if (res.status === 'completed') {
                        if (spsePollTimer) {
                            clearInterval(spsePollTimer);
                            spsePollTimer = null;
                        }
                        dtContractors.ajax.reload(null, false);
                    } else if (res.status === 'stopped' || res.status === 'error' || res.status === 'idle') {
                        if (spsePollTimer) {
                            clearInterval(spsePollTimer);
                            spsePollTimer = null;
                        }
                    }
                }
            },
            error: function(err) {
                console.error("Error polling bot SPSE status:", err);
            }
        });
    }

    function triggerLaunch(mode) {
        let isEnrich = (mode === 'enrich');
        let btn = isEnrich ? $('#btnLaunchEnrich') : $('#btnLaunchScrape');
        let originalHtml = btn.html();

        $('#btnLaunchEnrich').prop('disabled', true);
        $('#btnLaunchScrape').prop('disabled', true);
        btn.html('<span class="spinner-border spinner-border-sm me-1"></span> Meluncurkan...');
        
        updateSpseBadge('starting', 'Mempersiapkan...');
        $('#spseStatusText').text('Membuka browser Google Chrome...');
        $('#spseProgressBarContainer').addClass('d-none');

        let data = getCsrfData();
        data.mode = mode;

        $.ajax({
            url: "<?= base_url('dashboard/do-launch-bot-spse') ?>",
            type: "POST",
            data: data,
            dataType: "json",
            success: function(resp) {
                btn.html(originalHtml);
                if (resp.status === 'success') {
                    $('#btnStopSpse').removeClass('d-none');
                    if (spsePollTimer) clearInterval(spsePollTimer);
                    spsePollTimer = setInterval(pollSpseStatus, 2000);
                    pollSpseStatus();
                } else {
                    $('#btnLaunchEnrich').prop('disabled', false);
                    $('#btnLaunchScrape').prop('disabled', false);
                    updateSpseBadge('error', 'Gagal');
                    alert(resp.message || 'Gagal meluncurkan bot.');
                }
            },
            error: function(xhr) {
                btn.html(originalHtml);
                $('#btnLaunchEnrich').prop('disabled', false);
                $('#btnLaunchScrape').prop('disabled', false);
                updateSpseBadge('error', 'Koneksi Gagal');
                alert('Terjadi kesalahan saat memicu peluncuran bot.');
            }
        });
    }

    // Tombol Lengkapi Domisili
    $('#btnLaunchEnrich').on('click', function() {
        triggerLaunch('enrich');
    });

    // Tombol Scrape Daftar Baru
    $('#btnLaunchScrape').on('click', function() {
        triggerLaunch('scrape');
    });

    // Tombol Hentikan Bot SPSE
    $('#btnStopSpse').on('click', function() {
        if (!confirm('Apakah Anda yakin ingin menghentikan Bot SPSE dan menutup browser Chrome?')) {
            return;
        }
        let btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menghentikan...');
        let data = getCsrfData();

        $.ajax({
            url: "<?= base_url('dashboard/stop-bot-spse') ?>",
            type: "POST",
            data: data,
            dataType: "json",
            success: function(resp) {
                btn.prop('disabled', false).addClass('d-none');
                $('#btnLaunchEnrich').prop('disabled', false);
                $('#btnLaunchScrape').prop('disabled', false);
                updateSpseBadge('stopped', 'Dihentikan');
                pollSpseStatus();
            },
            error: function() {
                btn.prop('disabled', false);
            }
        });
    });

    // Check status pertama kali saat halaman dimuat
    pollSpseStatus();
});
</script>
<?= $this->endSection() ?>
