<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?>
<?= $this->section('content') ?>

<!-- DataTables CSS CDN -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<style>
    .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 0 !important; }
    div.dataTables_wrapper div.dataTables_info { padding-top: 0.5rem; }
    .dt-buttons .btn { margin-right: 5px; }

    /* Modern Table Styling */
    #table-rup-itkp { width: 100% !important; border-collapse: collapse !important; }
    #table-rup-itkp th, #table-rup-itkp td { white-space: normal !important; vertical-align: middle !important; font-size: 0.84rem; }
    
    .col-no { width: 45px !important; text-align: center; }
    .col-instansi { min-width: 140px !important; }
    .col-satker { min-width: 180px !important; }
    .col-tahun { width: 75px !important; text-align: center; }
    .col-cara { width: 95px !important; text-align: center; }
    .col-metode { min-width: 140px !important; }
    .col-jenis { min-width: 130px !important; }
    .col-paket { min-width: 250px !important; }
    .col-kode { width: 95px !important; text-align: center; }
    .col-dana { width: 85px !important; text-align: center; }
    .col-pdn { width: 95px !important; text-align: center; }
    .col-nilai { min-width: 130px !important; text-align: right; }

    /* KPI Cards */
    .kpi-card {
        border-radius: 0.75rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1.25rem rgba(0, 0, 0, 0.08) !important;
    }
    .kpi-icon {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.5rem;
        font-size: 1.5rem;
    }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
        <div>
            <h4 class="fw-bold py-1 mb-1">
                <span class="text-muted fw-light">Data RUP /</span> Data ITKP
            </h4>
            <p class="text-muted small mb-0">Format Data Rencana Umum Pengadaan (SiRUP) Konsolidasi Penyedia & Swakelola untuk Penilaian ITKP</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <?php 
            $isRestrictedRole = in_array(session()->get('role'), ['opd', 'satker']);
            $userNamaSatker = session()->get('nama_satker') ?: session()->get('nama_lengkap');
            ?>
            <?php if($isRestrictedRole): ?>
            <div class="d-flex align-items-center gap-2 bg-white px-3 py-2 rounded border">
                <i class="bx bx-shield-quarter text-warning fs-5"></i>
                <small class="text-muted">Satker:</small>
                <span class="badge bg-label-warning fw-bold"><?= esc($userNamaSatker ?: 'Satker Anda') ?></span>
            </div>
            <?php endif; ?>
            <a href="javascript:void(0);" id="btn-export-excel" class="btn btn-success btn-sm shadow-sm">
                <i class="bx bx-spreadsheet me-1"></i> Export Excel
            </a>
            <?php if(in_array(session()->get('role'), ['admin', 'operator'])): ?>
            <a href="<?= base_url('dashboard/clear-cache') ?>" class="btn btn-outline-danger btn-sm" data-confirm="Apakah Anda yakin ingin menghapus seluruh cache data?">
                <i class="bx bx-trash me-1"></i> Clear Cache
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header pb-2 pt-3 border-bottom bg-transparent">
            <h6 class="mb-0 fw-bold text-primary d-flex align-items-center">
                <i class="bx bx-filter-alt me-2"></i> Filter & Kriteria Data SiRUP
            </h6>
        </div>
        <div class="card-body pt-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-2 col-6">
                    <label class="form-label small fw-bold text-uppercase text-muted">Tahun Anggaran</label>
                    <select id="filter-year" class="form-select">
                        <option value="all">Semua Tahun</option>
                        <?php 
                        $curYear = date('Y');
                        foreach($availableYears as $yr): 
                        ?>
                        <option value="<?= $yr ?>" <?= ($yr == $curYear || ($curYear > max($availableYears) && $yr == max($availableYears))) ? 'selected' : '' ?>>
                            <?= $yr ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 col-6">
                    <label class="form-label small fw-bold text-uppercase text-muted">Satuan Kerja (OPD)</label>
                    <select id="filter-opd" class="form-select" <?= ($isRestrictedRole && $userNamaSatker) ? 'disabled' : '' ?>>
                        <?php if(!$isRestrictedRole || !$userNamaSatker): ?>
                        <option value="all">-- Semua Satker / OPD --</option>
                        <?php endif; ?>
                        <?php foreach($availableSatkers as $s): ?>
                        <option value="<?= esc($s) ?>" <?= ($isRestrictedRole && $userNamaSatker == $s) ? 'selected' : '' ?>>
                            <?= esc($s) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2 col-6">
                    <label class="form-label small fw-bold text-uppercase text-muted">Cara Pengadaan</label>
                    <select id="filter-cara" class="form-select">
                        <option value="all">Semua Cara</option>
                        <option value="Penyedia">Penyedia</option>
                        <option value="Swakelola">Swakelola</option>
                    </select>
                </div>

                <div class="col-md-2 col-6">
                    <label class="form-label small fw-bold text-uppercase text-muted">Komitmen PDN</label>
                    <select id="filter-pdn" class="form-select">
                        <option value="all">Semua Status</option>
                        <option value="PDN">PDN (Produk Dalam Negeri)</option>
                        <option value="Non-PDN">Non-PDN / Lainnya</option>
                    </select>
                </div>

                <div class="col-md-3 col-12 d-flex gap-2">
                    <button id="btn-apply-filter" class="btn btn-primary w-100 shadow-sm">
                        <i class="bx bx-check-circle me-1"></i> Terapkan
                    </button>
                    <button id="btn-reset-filter" class="btn btn-outline-secondary w-auto px-3" title="Reset Filter">
                        <i class="bx bx-reset"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <!-- Total Pagu -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="card kpi-card shadow-sm border-0 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="kpi-icon bg-label-primary text-primary">
                            <i class="bx bx-wallet"></i>
                        </div>
                        <div class="overflow-hidden">
                            <small class="text-muted text-uppercase fw-semibold d-block">Total Pagu SiRUP</small>
                            <h5 class="fw-bold mb-0 text-primary text-truncate" id="kpi-total-pagu">Rp 0</h5>
                            <small class="text-muted" id="kpi-total-paket">0 Paket Terumumkan</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Penyedia -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="card kpi-card shadow-sm border-0 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="kpi-icon bg-label-info text-info">
                            <i class="bx bx-store-alt"></i>
                        </div>
                        <div class="overflow-hidden">
                            <small class="text-muted text-uppercase fw-semibold d-block">RUP Penyedia</small>
                            <h5 class="fw-bold mb-0 text-info text-truncate" id="kpi-penyedia-pagu">Rp 0</h5>
                            <small class="text-muted" id="kpi-penyedia-paket">0 Paket (<span id="kpi-penyedia-pct">0%</span>)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Swakelola -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="card kpi-card shadow-sm border-0 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="kpi-icon bg-label-warning text-warning">
                            <i class="bx bx-group"></i>
                        </div>
                        <div class="overflow-hidden">
                            <small class="text-muted text-uppercase fw-semibold d-block">RUP Swakelola</small>
                            <h5 class="fw-bold mb-0 text-warning text-truncate" id="kpi-swakelola-pagu">Rp 0</h5>
                            <small class="text-muted" id="kpi-swakelola-paket">0 Paket (<span id="kpi-swakelola-pct">0%</span>)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Komitmen PDN -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="card kpi-card shadow-sm border-0 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="kpi-icon bg-label-success text-success">
                            <i class="bx bx-badge-check"></i>
                        </div>
                        <div class="overflow-hidden">
                            <small class="text-muted text-uppercase fw-semibold d-block">Komitmen PDN</small>
                            <h5 class="fw-bold mb-0 text-success text-truncate" id="kpi-pdn-pagu">Rp 0</h5>
                            <small class="text-muted" id="kpi-pdn-paket">0 Paket (<span id="kpi-pdn-pct">0%</span> dari Penyedia)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row g-4 mb-4">
        <!-- Chart 1: Donut Cara Pengadaan -->
        <div class="col-lg-5 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header d-flex align-items-center justify-content-between pb-0">
                    <div class="card-title mb-0">
                        <h6 class="m-0 me-2 fw-bold text-dark">Komposisi Cara Pengadaan</h6>
                        <small class="text-muted">Perbandingan Pagu Penyedia vs Swakelola</small>
                    </div>
                </div>
                <div class="card-body d-flex flex-column justify-content-center pt-2">
                    <div id="chart-cara-pengadaan" style="min-height: 260px;"></div>
                </div>
            </div>
        </div>

        <!-- Chart 2: Pagu per Jenis Pengadaan -->
        <div class="col-lg-7 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header d-flex align-items-center justify-content-between pb-0">
                    <div class="card-title mb-0">
                        <h6 class="m-0 me-2 fw-bold text-dark">Pagu Berdasarkan Jenis Pengadaan</h6>
                        <small class="text-muted">Distribusi total pagu belanja RUP</small>
                    </div>
                </div>
                <div class="card-body pt-2">
                    <div id="chart-jenis-pengadaan" style="min-height: 260px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 border-bottom py-3">
            <div>
                <h5 class="card-title mb-0 fw-bold text-dark d-flex align-items-center">
                    <i class="bx bx-table text-primary me-2 fs-4"></i> Rincian Data SiRUP (Format ITKP)
                </h5>
                <small class="text-muted">Tabel menampilkan 12 kolom data sesuai format standar pelaporan SiRUP ITKP LKPP</small>
            </div>
            <div class="text-end">
                <span class="badge bg-label-primary px-3 py-2 fw-bold" id="badge-total-records">
                    <i class="bx bx-list-check me-1"></i> Memuat data...
                </span>
            </div>
        </div>
        <div class="card-body pt-3">
            <div class="table-responsive">
                <table id="table-rup-itkp" class="table table-hover table-striped border-top">
                    <thead class="table-light">
                        <tr>
                            <th class="col-no">No</th>
                            <th class="col-instansi">Nama Instansi</th>
                            <th class="col-satker">Nama Satuan Kerja</th>
                            <th class="col-tahun">Tahun Anggaran</th>
                            <th class="col-cara">Cara Pengadaan</th>
                            <th class="col-metode">Metode Pengadaan</th>
                            <th class="col-jenis">Jenis Pengadaan</th>
                            <th class="col-paket">Nama Paket</th>
                            <th class="col-kode">Kode Rup</th>
                            <th class="col-dana">Sumber Dana</th>
                            <th class="col-pdn">Produk Dalam Negeri</th>
                            <th class="col-nilai">Total Nilai</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- DataTables JS CDN -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- ApexCharts -->
<script src="<?= base_url('assets/vendor/libs/apex-charts/apexcharts.js') ?>"></script>

<script>
    let dataTable;
    let chartCara, chartJenis;

    const formatRupiah = (val) => {
        return 'Rp ' + Number(val || 0).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    };

    const formatNumber = (val) => {
        return Number(val || 0).toLocaleString('id-ID');
    };

    // Initialize ApexCharts
    function initCharts() {
        // Chart 1: Donut Cara Pengadaan
        const caraEl = document.querySelector('#chart-cara-pengadaan');
        const caraOpts = {
            chart: {
                type: 'donut',
                height: 260,
                fontFamily: 'Inter, sans-serif'
            },
            series: [0, 0],
            labels: ['Penyedia', 'Swakelola'],
            colors: ['#696cff', '#ffab00'],
            legend: {
                position: 'bottom',
                markers: { radius: 12 }
            },
            dataLabels: {
                enabled: true,
                formatter: (val) => Math.round(val) + '%'
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total Pagu',
                                formatter: function (w) {
                                    const sum = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    if (sum >= 1e12) return 'Rp ' + (sum / 1e12).toFixed(1) + ' T';
                                    if (sum >= 1e9) return 'Rp ' + (sum / 1e9).toFixed(1) + ' M';
                                    if (sum >= 1e6) return 'Rp ' + (sum / 1e6).toFixed(1) + ' Jt';
                                    return formatRupiah(sum);
                                }
                            }
                        }
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: (val) => formatRupiah(val)
                }
            }
        };
        chartCara = new ApexCharts(caraEl, caraOpts);
        chartCara.render();

        // Chart 2: Bar Jenis Pengadaan
        const jenisEl = document.querySelector('#chart-jenis-pengadaan');
        const jenisOpts = {
            chart: {
                type: 'bar',
                height: 260,
                toolbar: { show: false },
                fontFamily: 'Inter, sans-serif'
            },
            series: [{
                name: 'Total Pagu',
                data: []
            }],
            xaxis: {
                categories: [],
                labels: {
                    rotate: -20,
                    style: { fontSize: '11px' }
                }
            },
            yaxis: {
                labels: {
                    formatter: function(val) {
                        if (val >= 1e12) return (val / 1e12).toFixed(1) + ' T';
                        if (val >= 1e9) return (val / 1e9).toFixed(1) + ' M';
                        if (val >= 1e6) return (val / 1e6).toFixed(1) + ' Jt';
                        return val;
                    }
                }
            },
            colors: ['#03c3ec'],
            plotOptions: {
                bar: {
                    borderRadius: 6,
                    columnWidth: '45%',
                    distributed: false
                }
            },
            dataLabels: { enabled: false },
            tooltip: {
                y: {
                    formatter: (val) => formatRupiah(val)
                }
            }
        };
        chartJenis = new ApexCharts(jenisEl, jenisOpts);
        chartJenis.render();
    }

    // Load Dashboard KPI & Charts
    function loadDashboardData() {
        const year = $('#filter-year').val();
        const opd = $('#filter-opd').val();
        const cara = $('#filter-cara').val();
        const pdn = $('#filter-pdn').val();

        $.ajax({
            url: "<?= base_url('dashboard/getRupItkpDashboardData') ?>",
            type: "GET",
            data: {
                year: year,
                opd: opd,
                cara_pengadaan: cara,
                status_pdn: pdn
            },
            success: function(res) {
                if (res && res.kpi) {
                    const kpi = res.kpi;
                    $('#kpi-total-pagu').text(formatRupiah(kpi.total_pagu));
                    $('#kpi-total-paket').text(formatNumber(kpi.total_paket) + ' Paket Terumumkan');

                    $('#kpi-penyedia-pagu').text(formatRupiah(kpi.penyedia_pagu));
                    $('#kpi-penyedia-paket').html(formatNumber(kpi.penyedia_paket) + ' Paket (<span class="fw-bold text-info">' + kpi.pct_penyedia + '%</span>)');

                    $('#kpi-swakelola-pagu').text(formatRupiah(kpi.swakelola_pagu));
                    $('#kpi-swakelola-paket').html(formatNumber(kpi.swakelola_paket) + ' Paket (<span class="fw-bold text-warning">' + kpi.pct_swakelola + '%</span>)');

                    $('#kpi-pdn-pagu').text(formatRupiah(kpi.pdn_pagu));
                    $('#kpi-pdn-paket').html(formatNumber(kpi.pdn_paket) + ' Paket (<span class="fw-bold text-success">' + kpi.pct_pdn + '%</span> dari Penyedia)');
                }

                if (res && res.charts) {
                    // Update Donut
                    if (chartCara && res.charts.cara_pengadaan) {
                        chartCara.updateSeries(res.charts.cara_pengadaan.series || [0, 0]);
                    }

                    // Update Jenis Bar
                    if (chartJenis && res.charts.jenis_pengadaan) {
                        chartJenis.updateOptions({
                            xaxis: { categories: res.charts.jenis_pengadaan.categories || [] }
                        });
                        chartJenis.updateSeries([{
                            name: 'Total Pagu',
                            data: res.charts.jenis_pengadaan.series || []
                        }]);
                    }
                }
            }
        });
    }

    $(document).ready(function() {
        initCharts();
        loadDashboardData();

        // Initialize DataTable
        dataTable = $('#table-rup-itkp').DataTable({
            processing: true,
            serverSide: true,
            autoWidth: false,
            responsive: false,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            ajax: {
                url: "<?= base_url('dashboard/getRupItkpDatatables') ?>",
                type: "GET",
                data: function(d) {
                    d.year = $('#filter-year').val();
                    d.opd = $('#filter-opd').val();
                    d.cara_pengadaan = $('#filter-cara').val();
                    d.status_pdn = $('#filter-pdn').val();
                },
                dataSrc: function(json) {
                    $('#badge-total-records').html('<i class="bx bx-check-double me-1"></i> ' + formatNumber(json.recordsFiltered) + ' Paket Ditemukan');
                    return json.data;
                }
            },
            columns: [
                {
                    data: null,
                    className: "col-no",
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                {
                    data: "nama_instansi",
                    className: "col-instansi",
                    render: function(data) {
                        return '<span class="fw-semibold text-dark">' + (data || 'Pemerintah Daerah Kabupaten Lombok Barat') + '</span>';
                    }
                },
                {
                    data: "nama_satker",
                    className: "col-satker",
                    render: function(data) {
                        return data ? '<span class="text-truncate d-inline-block" style="max-width: 200px;" title="' + data + '">' + data + '</span>' : '-';
                    }
                },
                {
                    data: "tahun",
                    className: "col-tahun",
                    render: function(data) {
                        return '<span class="badge bg-label-dark fw-bold">' + (data || '-') + '</span>';
                    }
                },
                {
                    data: "cara_pengadaan",
                    className: "col-cara",
                    render: function(data) {
                        if (data === 'Penyedia') {
                            return '<span class="badge bg-label-primary fw-bold">Penyedia</span>';
                        }
                        return '<span class="badge bg-label-info fw-bold">Swakelola</span>';
                    }
                },
                {
                    data: "metode_pengadaan",
                    className: "col-metode",
                    render: function(data) {
                        if (!data || data === '-') return '<span class="text-muted">-</span>';
                        if (data.includes('Swakelola')) {
                            return '<span class="badge bg-label-info fw-bold">' + data + '</span>';
                        }
                        return '<span class="badge bg-label-secondary">' + data + '</span>';
                    }
                },
                {
                    data: "jenis_pengadaan",
                    className: "col-jenis",
                    render: function(data) {
                        return data ? '<span class="fw-semibold">' + data + '</span>' : '';
                    }
                },
                {
                    data: "nama_paket",
                    className: "col-paket",
                    render: function(data) {
                        return '<span class="fw-bold text-dark">' + (data || '-') + '</span>';
                    }
                },
                {
                    data: "kd_rup",
                    className: "col-kode",
                    render: function(data) {
                        return '<span class="badge bg-label-secondary font-monospace">' + (data || '-') + '</span>';
                    }
                },
                {
                    data: "sumber_dana",
                    className: "col-dana",
                    render: function(data) {
                        return '<span class="badge bg-label-light text-dark border">' + (data || 'APBD') + '</span>';
                    }
                },
                {
                    data: "status_pdn",
                    className: "col-pdn",
                    render: function(data) {
                        const val = (data || '').toLowerCase();
                        if (val.includes('pdn') && !val.includes('non') && !val.includes('bukan')) {
                            return '<span class="badge bg-label-success fw-bold"><i class="bx bx-check me-1"></i>PDN</span>';
                        } else if (val.includes('non') || val.includes('bukan')) {
                            return '<span class="badge bg-label-warning fw-bold">Non-PDN</span>';
                        }
                        return '<span class="text-muted">-</span>';
                    }
                },
                {
                    data: "total_nilai",
                    className: "col-nilai",
                    render: function(data) {
                        return '<span class="fw-bold text-primary">' + formatRupiah(data) + '</span>';
                    }
                }
            ],
            order: [[11, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Cari paket, OPD, kode RUP...",
                lengthMenu: "Tampilkan _MENU_",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ paket",
                infoEmpty: "Menampilkan 0 data",
                infoFiltered: "(disaring dari _MAX_ total paket)",
                zeroRecords: "Tidak ada data SiRUP yang sesuai dengan kriteria filter",
                processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"></div> Memuat Data SiRUP ITKP...',
                paginate: {
                    first: '<i class="bx bx-chevrons-left"></i>',
                    previous: '<i class="bx bx-chevron-left"></i>',
                    next: '<i class="bx bx-chevron-right"></i>',
                    last: '<i class="bx bx-chevrons-right"></i>'
                }
            }
        });

        // Filter button event
        $('#btn-apply-filter').on('click', function() {
            loadDashboardData();
            dataTable.ajax.reload();
        });

        // Reset filter
        $('#btn-reset-filter').on('click', function() {
            $('#filter-year').val('<?= date('Y') ?>');
            <?php if(!$isRestrictedRole || !$userNamaSatker): ?>
            $('#filter-opd').val('all');
            <?php endif; ?>
            $('#filter-cara').val('all');
            $('#filter-pdn').val('all');
            loadDashboardData();
            dataTable.ajax.reload();
        });

        // Year quick change
        $('#filter-year').on('change', function() {
            loadDashboardData();
            dataTable.ajax.reload();
        });

        // Export Excel
        $('#btn-export-excel').on('click', function() {
            const year = $('#filter-year').val();
            const opd = $('#filter-opd').val();
            const cara = $('#filter-cara').val();
            const pdn = $('#filter-pdn').val();
            const search = dataTable.search();

            const exportUrl = `<?= base_url('dashboard/export-rup-itkp') ?>?year=${encodeURIComponent(year)}&opd=${encodeURIComponent(opd)}&cara_pengadaan=${encodeURIComponent(cara)}&status_pdn=${encodeURIComponent(pdn)}&search=${encodeURIComponent(search)}`;
            window.location.href = exportUrl;
        });
    });
</script>
<?= $this->endSection() ?>
