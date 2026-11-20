<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?><?= $this->section('content') ?>

<!-- DataTables CSS CDN -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<style>
    .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 0 !important; }
    div.dataTables_wrapper div.dataTables_info { padding-top: 0.5rem; }
    .dt-buttons .btn { margin-right: 5px; }

    /* Table Wrapping & Column Constraints */
    #table-itkp { width: 100% !important; border-collapse: collapse !important; }
    #table-itkp th, #table-itkp td { white-space: normal !important; vertical-align: top !important; font-size: 0.84rem; }
    
    .col-instansi { min-width: 140px !important; }
    .col-satker { min-width: 180px !important; }
    .col-kode { width: 100px !important; font-family: monospace; }
    .col-tahun { width: 70px !important; text-align: center; }
    .col-sumber { min-width: 120px !important; }
    .col-dana { width: 90px !important; }
    .col-penyedia { min-width: 160px !important; }
    .col-metode { min-width: 130px !important; }
    .col-jenis { min-width: 140px !important; }
    .col-paket { min-width: 240px !important; }
    .col-status { min-width: 110px !important; }
    .col-nilai { min-width: 120px !important; text-align: right; font-weight: 600; }
    .col-pagu { min-width: 130px !important; text-align: right; font-weight: 600; }
    .col-efisiensi { min-width: 130px !important; text-align: right; font-weight: 600; }
    .col-alamat { min-width: 220px !important; }
    .col-tgl { min-width: 110px !important; text-align: center; }

    /* Card Metrics Styling */
    .stat-card {
        border-radius: 12px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    }
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    /* Mobile datatable adjustments */
    @media (max-width: 768px) {
        .dt-buttons {
            display: flex;
            width: 100%;
            gap: 8px;
        }
        .dt-buttons .btn {
            flex: 1;
            margin-right: 0 !important;
            padding: 8px 5px !important;
        }
    }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
        <div>
            <h4 class="fw-bold py-1 mb-1">
                <span class="text-muted fw-light">Data Pengadaan /</span> Data ITKP
            </h4>
            <p class="text-muted small mb-0">Konsolidasi seluruh transaksi pengadaan (Tender, Non Tender, E-Purchasing, Pencatatan Non Tender, dan Swakelola)</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <?php 
            $isRestrictedRole = in_array(session()->get('role'), ['opd', 'satker']);
            $userNamaSatker = session()->get('nama_satker') ?: session()->get('nama_lengkap');
            ?>
            <?php if($isRestrictedRole): ?>
            <div class="d-flex align-items-center gap-2 bg-white px-3 py-2 rounded border">
                <i class="bx bx-shield-quarter text-warning fs-5"></i>
                <small class="text-muted">Data difilter:</small>
                <span class="badge bg-label-warning fw-bold"><?= esc($userNamaSatker ?: 'Satker Anda') ?></span>
            </div>
            <?php endif; ?>
            <a href="javascript:void(0);" id="btn-export-excel" class="btn btn-success btn-sm">
                <i class="bx bx-spreadsheet me-1"></i> Export Excel
            </a>
            <?php if(in_array(session()->get('role'), ['admin', 'operator'])): ?>
            <a href="<?= base_url('dashboard/clear-cache') ?>" class="btn btn-outline-danger btn-sm" data-confirm="Apakah Anda yakin ingin menghapus seluruh cache data?">
                <i class="bx bx-trash me-1"></i> Clear Cache
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="row mb-4 g-3">
        <div class="col-6 col-md-3">
            <div class="card stat-card shadow-sm border-0 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-label-primary text-primary">
                            <i class="bx bx-layer"></i>
                        </div>
                        <div>
                            <small class="text-muted text-uppercase fw-semibold d-block">Total Paket</small>
                            <h5 class="fw-bold mb-0 text-primary" id="summary-total-paket">-</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card shadow-sm border-0 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-label-success text-success">
                            <i class="bx bx-wallet"></i>
                        </div>
                        <div>
                            <small class="text-muted text-uppercase fw-semibold d-block">Total Nilai</small>
                            <h6 class="fw-bold mb-0 text-success" id="summary-total-nilai">-</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card shadow-sm border-0 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-label-info text-info">
                            <i class="bx bx-check-shield"></i>
                        </div>
                        <div>
                            <small class="text-muted text-uppercase fw-semibold d-block">Nilai PDN</small>
                            <h6 class="fw-bold mb-0 text-info" id="summary-total-pdn">-</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card shadow-sm border-0 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-label-warning text-warning">
                            <i class="bx bx-store"></i>
                        </div>
                        <div>
                            <small class="text-muted text-uppercase fw-semibold d-block">Nilai UMK</small>
                            <h6 class="fw-bold mb-0 text-warning" id="summary-total-umk">-</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-header pb-2 pt-3 border-bottom d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-bold"><i class="bx bx-filter-alt me-1 text-primary"></i> Filter & Parameter Pencarian</h6>
            <button id="btn-reset" class="btn btn-link btn-sm text-decoration-none p-0 text-muted">
                <i class="bx bx-reset me-1"></i> Reset Filter
            </button>
        </div>
        <div class="card-body pt-3">
            <div class="row align-items-end g-3">
                <div class="col-md-3">
                    <label class="form-label text-uppercase fw-bold small text-muted">Pencarian</label>
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" id="custom-search" placeholder="Cari Paket, RUP, Satker, Penyedia..." class="form-control">
                    </div>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label text-uppercase fw-bold small text-muted">Tahun Anggaran</label>
                    <select id="filter-year" class="form-select">
                        <option value="all">Semua Tahun</option>
                        <?php 
                        $curYear = date('Y');
                        if (isset($availableYears) && !empty($availableYears)) {
                            foreach($availableYears as $yr): 
                        ?>
                        <option value="<?= $yr ?>" <?= $yr == $curYear ? 'selected' : '' ?>><?= $yr ?></option>
                        <?php 
                            endforeach; 
                        }
                        ?>
                    </select>
                </div>

                <?php if(!$isRestrictedRole): ?>
                <div class="col-md-3">
                    <label class="form-label text-uppercase fw-bold small text-muted">Filter OPD (Satker)</label>
                    <select id="filter-opd" class="form-select select2">
                        <option value="all">Semua OPD / Satker</option>
                        <?php if(isset($availableSatkers) && !empty($availableSatkers)): ?>
                            <?php foreach($availableSatkers as $satker): ?>
                                <option value="<?= esc($satker) ?>"><?= esc($satker) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <?php else: ?>
                <input type="hidden" id="filter-opd" value="all">
                <?php endif; ?>

                <div class="col-md-2">
                    <label class="form-label text-uppercase fw-bold small text-muted">Sumber Transaksi</label>
                    <select id="filter-sumber" class="form-select">
                        <option value="all">Semua Sumber</option>
                        <option value="Tender">Tender</option>
                        <option value="Non Tender">Non Tender</option>
                        <option value="E-Purchasing">E-Purchasing</option>
                        <option value="Pencatatan Non Tender">Pencatatan Non Tender</option>
                        <option value="Swakelola">Swakelola</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <button id="btn-apply-filter" class="btn btn-primary w-100">
                        <i class="bx bx-check-circle me-1"></i> Terapkan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="d-md-none text-muted mb-2 d-flex align-items-center" style="font-size: 11.5px; gap: 5px;">
                <i class='bx bx-move-horizontal text-primary'></i>
                <span>Geser tabel ke samping untuk melihat detail kolom</span>
            </div>
            <div class="table-responsive">
                <table id="table-itkp" class="table table-hover border-top">
                    <thead class="table-light">
                        <tr>
                            <th class="col-instansi">Nama Instansi</th>
                            <th class="col-satker">Nama Satuan Kerja</th>
                            <th class="col-kode">Kode Paket</th>
                            <th class="col-kode">Kode Rup</th>
                            <th class="col-tahun">Tahun Anggaran</th>
                            <th class="col-sumber">Sumber Transaksi</th>
                            <th class="col-dana">Sumber Dana</th>
                            <th class="col-penyedia">Nama Penyedia</th>
                            <th class="col-metode">Metode Pengadaan</th>
                            <th class="col-jenis">Jenis Pengadaan</th>
                            <th class="col-paket">Nama Paket</th>
                            <th class="col-status">Status Paket</th>
                            <th class="col-nilai">Total Nilai</th>
                            <th class="col-nilai">Nilai PDN</th>
                            <th class="col-nilai">Nilai UMK</th>
                            <th class="col-pagu">Total Pagu</th>
                            <th class="col-efisiensi">Efisiensi</th>
                            <th class="col-alamat">Alamat Penyedia</th>
                            <th class="col-tgl">Tanggal Mulai Pekerjaan</th>
                            <th class="col-tgl">Tanggal Selesai Pekerjaan</th>
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

<!-- DataTables Buttons & Export -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
    let dataTable;

    function formatIDR(v) {
        return new Intl.NumberFormat('id-ID', { 
            style: 'currency', 
            currency: 'IDR', 
            maximumFractionDigits: 0 
        }).format(v || 0);
    }

    function renderSumberBadge(sumber) {
        if (!sumber) return '-';
        const s = String(sumber).toLowerCase();
        let badgeClass = 'bg-label-secondary';
        let icon = 'bx-layer';

        if (s.includes('tender') && !s.includes('non') && !s.includes('pencatatan')) {
            badgeClass = 'bg-label-primary';
            icon = 'bx-award';
        } else if (s.includes('non tender') || s.includes('nontender')) {
            badgeClass = 'bg-label-info';
            icon = 'bx-file-blank';
        } else if (s.includes('purchasing')) {
            badgeClass = 'bg-label-success';
            icon = 'bx-cart';
        } else if (s.includes('swakelola')) {
            badgeClass = 'bg-label-warning';
            icon = 'bx-group';
        } else if (s.includes('pencatatan')) {
            badgeClass = 'bg-label-dark';
            icon = 'bx-edit';
        }

        return `<span class="badge ${badgeClass} text-nowrap"><i class="bx ${icon} me-1"></i>${escapeHtml(sumber)}</span>`;
    }

    function renderStatusBadge(status) {
        if (!status) return '-';
        const st = String(status).toLowerCase();
        let badgeClass = 'bg-label-secondary';

        if (st.includes('selesai') || st.includes('completed') || st.includes('terverifikasi')) {
            badgeClass = 'bg-label-success';
        } else if (st.includes('proses') || st.includes('berlangsung') || st.includes('on process') || st.includes('review')) {
            badgeClass = 'bg-label-warning';
        } else if (st.includes('batal') || st.includes('gagal') || st.includes('reject')) {
            badgeClass = 'bg-label-danger';
        } else {
            badgeClass = 'bg-label-info';
        }

        return `<span class="badge ${badgeClass} text-nowrap">${escapeHtml(status)}</span>`;
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    $(document).ready(function() {
        dataTable = $('#table-itkp').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, 250], [10, 25, 50, 100, 250]],
            ajax: {
                url: "<?= base_url('dashboard/getItkpDatatables') ?>",
                data: function(d) {
                    d.year = $('#filter-year').val();
                    d.opd = $('#filter-opd').val();
                    d.sumber_transaksi = $('#filter-sumber').val();
                    d.search.value = $('#custom-search').val();
                },
                dataSrc: function(json) {
                    // Update Summary Metric Cards
                    if (json.summary) {
                        $('#summary-total-paket').text(Number(json.summary.total_paket || 0).toLocaleString('id-ID'));
                        $('#summary-total-nilai').text(formatIDR(json.summary.total_nilai));
                        $('#summary-total-pdn').text(formatIDR(json.summary.total_pdn));
                        $('#summary-total-umk').text(formatIDR(json.summary.total_umk));
                    }
                    return json.data;
                }
            },
            columns: [
                { 
                    data: 'nama_instansi',
                    render: function(data) { return escapeHtml(data || 'Pemerintah Daerah'); }
                },
                { 
                    data: 'nama_satker',
                    render: function(data) { return `<span class="fw-semibold text-dark">${escapeHtml(data || '-')}</span>`; }
                },
                { 
                    data: 'kd_paket',
                    render: function(data) { return `<span class="badge bg-light text-dark font-monospace">${escapeHtml(data || '-')}</span>`; }
                },
                { 
                    data: 'kd_rup',
                    render: function(data) { return `<span class="badge bg-light text-dark font-monospace">${escapeHtml(data || '-')}</span>`; }
                },
                { 
                    data: 'tahun',
                    render: function(data) { return `<span class="badge bg-label-secondary fw-bold">${escapeHtml(data || '-')}</span>`; }
                },
                { 
                    data: 'sumber_transaksi',
                    render: function(data) { return renderSumberBadge(data); }
                },
                { 
                    data: 'sumber_dana',
                    render: function(data) { return `<span class="badge bg-label-info">${escapeHtml(data || 'APBD')}</span>`; }
                },
                { 
                    data: 'nama_penyedia',
                    render: function(data) { return `<span class="text-dark">${escapeHtml(data || '-')}</span>`; }
                },
                { 
                    data: 'metode_pengadaan',
                    render: function(data) { return escapeHtml(data || '-'); }
                },
                { 
                    data: 'jenis_pengadaan',
                    render: function(data) { return escapeHtml(data || '-'); }
                },
                { 
                    data: 'nama_paket',
                    render: function(data) { return `<span class="fw-medium text-dark">${escapeHtml(data || '-')}</span>`; }
                },
                { 
                    data: 'status_paket',
                    render: function(data) { return renderStatusBadge(data); }
                },
                { 
                    data: 'total_nilai',
                    render: function(data) { return `<span class="text-primary fw-bold text-nowrap">${formatIDR(data)}</span>`; }
                },
                { 
                    data: 'nilai_pdn',
                    render: function(data) { return `<span class="text-success fw-semibold text-nowrap">${formatIDR(data)}</span>`; }
                },
                { 
                    data: 'nilai_umk',
                    render: function(data) { return `<span class="text-warning fw-semibold text-nowrap">${formatIDR(data)}</span>`; }
                },
                { 
                    data: 'total_pagu',
                    render: function(data) { return `<span class="text-dark fw-bold text-nowrap">${formatIDR(data)}</span>`; }
                },
                { 
                    data: 'efisiensi',
                    render: function(data) { 
                        const val = Number(data || 0);
                        const colorClass = val > 0 ? 'text-success' : 'text-muted';
                        return `<span class="${colorClass} fw-bold text-nowrap">${formatIDR(val)}</span>`; 
                    }
                },
                { 
                    data: 'alamat_penyedia',
                    render: function(data) { return `<small class="text-muted d-block text-wrap" style="max-width: 260px;">${escapeHtml(data || '-')}</small>`; }
                },
                { 
                    data: 'tgl_mulai_pekerjaan',
                    render: function(data) { 
                        return data && data !== '-' ? `<span class="badge bg-label-secondary font-monospace">${escapeHtml(data)}</span>` : '<span class="text-muted">-</span>'; 
                    }
                },
                { 
                    data: 'tgl_selesai_pekerjaan',
                    render: function(data) { 
                        return data && data !== '-' ? `<span class="badge bg-label-secondary font-monospace">${escapeHtml(data)}</span>` : '<span class="text-muted">-</span>'; 
                    }
                }
            ],
            order: [[1, 'asc'], [4, 'desc'], [12, 'desc']],
            dom: '<"row mb-3"<"col-md-6 d-flex align-items-center"l><"col-md-6 d-flex justify-content-end"B>>rt<"row"<"col-md-6"i><"col-md-6 d-flex justify-content-end"p>>',
            buttons: [
                {
                    extend: 'copy',
                    className: 'btn btn-outline-secondary btn-sm',
                    text: '<i class="bx bx-copy me-1"></i> Copy'
                },
                {
                    extend: 'csv',
                    className: 'btn btn-outline-secondary btn-sm',
                    text: '<i class="bx bx-file me-1"></i> CSV'
                },
                {
                    extend: 'print',
                    className: 'btn btn-outline-secondary btn-sm',
                    text: '<i class="bx bx-printer me-1"></i> Print'
                }
            ],
            language: {
                processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div> Memuat Data ITKP...',
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                infoEmpty: "Menampilkan 0 data",
                infoFiltered: "(disaring dari _MAX_ total data)",
                zeroRecords: "Tidak ada data ITKP yang sesuai dengan kriteria filter",
                paginate: {
                    first: "Pertama",
                    last: "Terakhir",
                    next: '<i class="bx bx-chevron-right"></i>',
                    previous: '<i class="bx bx-chevron-left"></i>'
                }
            }
        });

        // Trigger filters
        $('#btn-apply-filter').on('click', function() {
            dataTable.draw();
        });

        $('#filter-year, #filter-opd, #filter-sumber').on('change', function() {
            dataTable.draw();
        });

        let searchTimer;
        $('#custom-search').on('keyup', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                dataTable.draw();
            }, 400);
        });

        $('#btn-reset').on('click', function() {
            $('#custom-search').val('');
            $('#filter-year').val('<?= date('Y') ?>');
            $('#filter-opd').val('all');
            $('#filter-sumber').val('all');
            dataTable.draw();
        });

        // Export Excel button with active filters
        $('#btn-export-excel').on('click', function() {
            const year = $('#filter-year').val();
            const opd = $('#filter-opd').val();
            const sumber = $('#filter-sumber').val();
            const search = $('#custom-search').val();

            const exportUrl = `<?= base_url('dashboard/export-itkp') ?>?year=${encodeURIComponent(year)}&opd=${encodeURIComponent(opd)}&sumber_transaksi=${encodeURIComponent(sumber)}&search=${encodeURIComponent(search)}`;
            window.location.href = exportUrl;
        });
    });
</script>
<?= $this->endSection() ?>
