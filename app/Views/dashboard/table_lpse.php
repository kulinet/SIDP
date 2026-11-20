<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?><?= $this->section('content') ?>

<!-- DataTables CSS CDN -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<style>
    .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 0 !important; }
    div.dataTables_wrapper div.dataTables_info { padding-top: 0.5rem; }
    .dt-buttons .btn { margin-right: 5px; }

    /* Table Wrapping & Sizing */
    #table-lpse { width: 100% !important; border-collapse: collapse !important; }
    #table-lpse th, #table-lpse td { white-space: normal !important; vertical-align: top !important; }
    .col-rup { width: 100px !important; }
    .col-paket { min-width: 250px !important; }
    .col-satker { min-width: 200px !important; }
    .col-pagu { width: 130px !important; }
    .col-detail { min-width: 200px !important; }
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
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
        <h4 class="fw-bold py-3 mb-0">
            <span class="text-muted fw-light">Data Pengadaan /</span> <?= $title ?>
        </h4>
        <?php if(in_array(session()->get('role'), ['admin', 'operator'])): ?>
        <a href="<?= base_url('dashboard/clear-cache') ?>" class="btn btn-outline-danger btn-sm" data-confirm="Apakah Anda yakin ingin menghapus seluruh cache data? Hal ini akan memaksa sistem menarik ulang data terbaru dari API.">
            <i class="bx bx-trash me-1"></i> Clear Cache
        </a>
        <?php endif; ?>
        <?php 
        $isRestrictedRole = in_array(session()->get('role'), ['opd', 'satker']);
        // Use nama_satker already resolved and stored in session at login time
        $userNamaSatker = session()->get('nama_satker') ?: session()->get('nama_lengkap');
        ?>
        <?php if($isRestrictedRole): ?>
        <div class="d-flex align-items-center gap-2">
            <i class="bx bx-shield-quarter text-warning"></i>
            <small class="text-muted">Data difilter untuk:</small>
            <span class="badge bg-label-warning fw-bold"><?= esc($userNamaSatker ?: 'Satker Anda') ?></span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Filter Card -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                    <label class="form-label text-uppercase fw-bold small text-muted">Pencarian</label>
                    <input type="text" id="custom-search" placeholder="Cari..." class="form-control">
                </div>
                <?php if(!$isRestrictedRole): ?>
                <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                    <label class="form-label text-uppercase fw-bold small text-muted">Filter OPD</label>
                    <select id="filter-opd" class="form-select">
                        <option value="all">Semua OPD</option>
                    </select>
                </div>
                <?php else: ?>
                <input type="hidden" id="filter-opd" value="all">
                <?php endif; ?>
                <?php if($type === 'tender' || $type === 'nontender' || $type === 'pencatatan-nontender'): ?>
                <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                    <label class="form-label text-uppercase fw-bold small text-muted">Filter Status</label>
                    <select id="filter-status" class="form-select">
                        <option value="all">Semua Status</option>
                    </select>
                </div>
                <?php endif; ?>
                <?php if($type === 'tender' || $type === 'nontender'): ?>
                <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                    <label class="form-label text-uppercase fw-bold small text-muted">Jenis Pengadaan</label>
                    <select id="filter-jenis-pengadaan" class="form-select">
                        <option value="all">Semua Jenis</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                    <label class="form-label text-uppercase fw-bold small text-muted">Metode Pemilihan</label>
                    <select id="filter-metode" class="form-select">
                        <option value="all">Semua Metode</option>
                    </select>
                </div>
                <?php elseif($type === 'pencatatan-nontender'): ?>
                <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                    <label class="form-label text-uppercase fw-bold small text-muted">Kategori Pengadaan</label>
                    <select id="filter-kategori-pengadaan" class="form-select">
                        <option value="all">Semua Kategori</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                    <label class="form-label text-uppercase fw-bold small text-muted">Metode Pemilihan</label>
                    <select id="filter-metode" class="form-select">
                        <option value="all">Semua Metode</option>
                    </select>
                </div>
                <?php else: ?>
                <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                    <label class="form-label text-uppercase fw-bold small text-muted">Filter Jenis</label>
                    <select id="filter-jenis" class="form-select">
                        <option value="all">Semua Jenis</option>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-12 col-sm-6 col-md-4 col-xl-2">
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
                        } else {
                            for($i = $curYear; $i >= 2023; $i--): 
                        ?>
                        <option value="<?= $i ?>" <?= $i == $curYear ? 'selected' : '' ?>><?= $i ?></option>
                        <?php 
                            endfor;
                        }
                        ?>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                    <label class="form-label text-uppercase fw-bold small text-muted d-none d-md-block">&nbsp;</label>
                    <button id="btn-reset" class="btn btn-outline-secondary w-100">
                        <i class="bx bx-reset me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table id="table-lpse" class="table table-hover border-top">
                    <thead>
                        <tr>
                            <th class="col-rup">Kode RUP</th>
                            <th>Tahun</th>
                            <th>ID Paket</th>
                            <th class="col-paket">Nama Paket</th>
                            <?php if($type === 'pencatatan-swakelola'): ?>
                            <th class="col-satker">Satuan Kerja</th>
                            <th class="col-satker">Pelaksana</th>
                            <?php else: ?>
                            <th class="col-satker">Satuan Kerja</th>
                            <?php endif; ?>
                            <th class="col-pagu">Pagu</th>

                            <?php if($type === 'tender'): ?>
                                <th class="col-pagu">HPS</th>
                                <th class="col-pagu">Penawaran</th>
                                <th class="col-pagu">Nilai Kontrak</th>
                                <th>Penyedia</th>
                                <th class="col-detail">Metode & Detail</th>
                                <th>Tgl Pemenang</th>
                                <th>Tgl Kontrak</th>
                                <th>No. Kontrak</th>
                                <th>No. SPMK</th>
                                <th>Waktu Penyelesaian</th>
                                <th>Mulai Kerja</th>
                                <th>Selesai Kerja</th>
                            <?php elseif($type === 'nontender'): ?>
                                <th class="col-pagu">HPS</th>
                                <th class="col-pagu">Nilai Kontrak</th>
                                <th class="col-pagu">Nilai Negosiasi</th>
                                <th class="col-pagu">PDN</th>
                                <th class="col-pagu">UMK</th>
                                <th>Penyedia</th>
                                <th class="col-detail">Metode & Detail</th>
                                <th>Jenis Pengadaan</th>
                                <th>Tgl Kontrak</th>
                                <th>No. Kontrak</th>
                                <th>No. SPMK</th>
                                <th>Waktu Penyelesaian</th>
                                <th>Mulai Kerja</th>
                                <th>Selesai Kerja</th>
                                <th>No. BAP</th>
                                <th>Tgl BAP</th>
                                <th>Progres</th>
                                <th>No. BAST</th>
                                <th>Tgl BAST</th>
                                <th class="col-pagu">Besar Pembayaran</th>
                            <?php elseif($type === 'pencatatan-nontender'): ?>
                                <th class="col-pagu">Realisasi</th>
                                <th>Penyedia</th>
                                <th>Jenis Realisasi</th>
                                <th>Kategori Pengadaan</th>
                                <th>Metode Pemilihan</th>
                                <th>Sumber Dana</th>
                                <th>Tgl Buat</th>
                                <th>Tgl Selesai</th>
                                <th>No Pembayaran</th>
                            <?php elseif($type === 'pencatatan-swakelola'): ?>
                                <th class="col-pagu">Realisasi</th>
                                <th>Jenis Realisasi</th>
                                <th>No Pembayaran</th>
                                <th>Keterangan</th>
                            <?php endif; ?>

                            <th>Status</th>
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

<script>
    const type = '<?= $type ?>';
    let dataTable;

    function formatIDR(v) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v || 0);
    }

    function formatDate(str) {
        if (!str || str === '-') return '-';
        try {
            const d = new Date(str);
            if (isNaN(d)) return str;
            return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
        } catch(e) { return str; }
    }

    function renderStatus(data) {
        const s = data || 'Selesai';
        const lower = s.toLowerCase();
        let cls = 'bg-label-secondary';

        if (lower.includes('selesai') || lower.includes('kontrak') || lower.includes('pemenang')) cls = 'bg-label-success';
        else if (lower.includes('proses') || lower.includes('berjalan') || lower.includes('evaluasi')) cls = 'bg-label-warning';
        else if (lower.includes('batal') || lower.includes('gagal')) cls = 'bg-label-danger';
        else if (lower.includes('pengumuman') || lower.includes('tender')) cls = 'bg-label-info';
        else if (lower.includes('penjelasan')) cls = 'bg-label-primary';

        return `<span class="badge ${cls}">${s}</span>`;
    }

    // ─── Build columns per type ───
    function buildColumns() {
        let cols = [
            { 
                data: 'kd_rup', 
                defaultContent: '-'
            },
            { 
                data: 'tahun', 
                defaultContent: '-'
            },
            { 
                data: 'kd_paket', 
                defaultContent: '-' 
            },
            { 
                data: 'nama_paket', 
                defaultContent: '-'
            },
            { 
                data: 'nama_satker', 
                defaultContent: '-',
                render: function(data) { return `<small class="text-muted d-block">${data || '-'}</small>`; }
            },
            { 
                data: 'pagu', 
                defaultContent: 0,
                render: function(data) { return '<strong>' + formatIDR(data) + '</strong>'; }
            },
        ];

        // Insert nama_pelaksana BEFORE pagu only for pencatatan-swakelola (matches thead)
        if (type === 'pencatatan-swakelola') {
            cols.splice(5, 0, { 
                data: 'nama_pelaksana', 
                defaultContent: '-',
                render: function(data) { return `<div class="fw-bold text-primary">${data || '-'}</div>`; }
            });
        }

        if (type === 'tender') {
            cols.push(
                { data: 'hps', defaultContent: 0, render: data => formatIDR(data) },
                { data: 'nilai_penawaran', defaultContent: 0, render: data => '<strong class="text-warning">' + formatIDR(data) + '</strong>' },
                { data: 'nilai_kontrak', defaultContent: 0, render: data => '<strong class="text-success">' + formatIDR(data) + '</strong>' },
                { 
                    data: 'nama_penyedia', defaultContent: '-',
                    render: data => data && data !== '-' ? `<div class="fw-bold text-primary">${data}</div>` : '-'
                },
                { 
                    data: 'metode', defaultContent: '-',
                    render: function(data, t, row) {
                        return `<div style="font-size: 0.8rem; line-height: 1.3;">
                            <div class="mb-1"><span class="badge bg-label-primary">${data || '-'}</span></div>
                            <div class="text-muted mt-1 fw-bold">Jenis: ${row.jenis_pengadaan || '-'}</div>
                            <div class="text-muted">Kategori: ${row.kgr_nama || '-'}</div>
                            <div class="text-muted">Evaluasi: ${row.mtd_evaluasi || '-'}</div>
                            <div class="text-muted">Kualifikasi: ${row.mtd_kualifikasi || '-'}</div>
                            <div class="text-muted">Kual. Paket: ${row.kualifikasi_paket || '-'}</div>
                            <div class="text-muted">Pembayaran: ${row.kontrak_pembayaran || '-'}</div>
                            ${row.status_kontrak && row.status_kontrak !== '-' ? `<div class="text-info extra-small mt-1 fw-bold">Status: ${row.status_kontrak}</div>` : ''}
                        </div>`;
                    }
                },
                { data: 'tgl_pemenang', defaultContent: '-', render: data => formatDate(data) },
                { data: 'tgl_kontrak', defaultContent: '-', render: data => formatDate(data) },
                { 
                    data: 'no_kontrak', 
                    defaultContent: '-',
                    render: data => data && data !== '-' ? `<span class="badge bg-label-success font-monospace" style="font-size: 0.75rem;">${data}</span>` : '-'
                },
                { 
                    data: 'no_spmk_spp', 
                    defaultContent: '-',
                    render: data => data && data !== '-' ? `<span class="badge bg-label-primary font-monospace" style="font-size: 0.75rem;">${data}</span>` : '-'
                },
                { 
                    data: 'waktu_penyelesaian', 
                    defaultContent: '-',
                    render: data => data && data !== null && data !== '-' ? `<strong>${data} Hari</strong>` : '-'
                },
                { data: 'tgl_mulai_pekerjaan', defaultContent: '-', render: data => formatDate(data) },
                { data: 'tgl_selesai_pekerjaan', defaultContent: '-', render: data => formatDate(data) }
            );
        } else if (type === 'nontender') {
            cols.push(
                { data: 'hps', defaultContent: 0, render: data => formatIDR(data) },
                { data: 'nilai_kontrak', defaultContent: 0, render: data => '<strong class="text-success">' + formatIDR(data) + '</strong>' },
                { data: 'nilai_negosiasi', defaultContent: 0, render: data => '<strong class="text-warning">' + formatIDR(data) + '</strong>' },
                { 
                    data: 'nilai_pdn_kontrak', 
                    defaultContent: 0,
                    render: function(data) {
                        const val = parseFloat(data) || 0;
                        return val > 0 
                            ? `<span class="badge bg-label-success fw-bold" style="font-size:0.8rem;">${formatIDR(val)}</span>`
                            : '<span class="text-muted">-</span>';
                    }
                },
                { 
                    data: 'nilai_umk_kontrak', 
                    defaultContent: 0,
                    render: function(data) {
                        const val = parseFloat(data) || 0;
                        return val > 0 
                            ? `<span class="badge bg-label-primary fw-bold" style="font-size:0.8rem;">${formatIDR(val)}</span>`
                            : '<span class="text-muted">-</span>';
                    }
                },
                { 
                    data: 'nama_penyedia', defaultContent: '-',
                    render: data => data && data !== '-' ? `<div class="fw-bold text-primary">${data}</div>` : '-'
                },
                { 
                    data: 'metode', defaultContent: '-',
                    render: function(data, t, row) {
                        return `<div style="font-size: 0.8rem; line-height: 1.3;">
                            <div class="mb-1"><span class="badge bg-label-info">${data || '-'}</span></div>
                            <div class="text-muted">Kualifikasi: ${row.kualifikasi_paket || '-'}</div>
                            <div class="text-muted">Pembayaran: ${row.kontrak_pembayaran || '-'}</div>
                        </div>`;
                    }
                },
                { data: 'jenis_pengadaan', defaultContent: '-' },
                { data: 'tgl_kontrak', defaultContent: '-', render: data => formatDate(data) },
                { 
                    data: 'no_kontrak', 
                    defaultContent: '-',
                    render: data => data && data !== '-' ? `<span class="badge bg-label-success font-monospace" style="font-size: 0.75rem;">${data}</span>` : '-'
                },
                { 
                    data: 'no_spmk_spp', 
                    defaultContent: '-',
                    render: data => data && data !== '-' ? `<span class="badge bg-label-primary font-monospace" style="font-size: 0.75rem;">${data}</span>` : '-'
                },
                { 
                    data: 'waktu_penyelesaian', 
                    defaultContent: '-',
                    render: data => data && data !== null && data !== '-' ? `<strong>${data} Hari</strong>` : '-'
                },
                { data: 'tgl_mulai_pekerjaan', defaultContent: '-', render: data => formatDate(data) },
                { data: 'tgl_selesai_pekerjaan', defaultContent: '-', render: data => formatDate(data) },
                { 
                    data: 'no_bap', 
                    defaultContent: '-',
                    render: data => data && data !== '-' ? `<span class="badge bg-label-info font-monospace" style="font-size: 0.75rem;">${data}</span>` : '-'
                },
                { data: 'tgl_bap', defaultContent: '-', render: data => formatDate(data) },
                { 
                    data: 'progres_pekerjaan', 
                    defaultContent: '-',
                    render: function(data) {
                        if (data === null || data === undefined || data === '' || data === '-') return '-';
                        const p = parseFloat(data);
                        if (isNaN(p)) return data;
                        let bg = 'bg-label-warning';
                        if (p >= 100) bg = 'bg-label-success';
                        else if (p > 50) bg = 'bg-label-primary';
                        else if (p > 0) bg = 'bg-label-info';
                        return `<span class="badge ${bg} fw-bold font-monospace">${p}%</span>`;
                    }
                },
                { 
                    data: 'no_bast', 
                    defaultContent: '-',
                    render: data => data && data !== '-' ? `<span class="badge bg-label-warning font-monospace text-dark" style="font-size: 0.75rem;">${data}</span>` : '-'
                },
                { data: 'tgl_bast', defaultContent: '-', render: data => formatDate(data) },
                { 
                    data: 'besar_pembayaran', 
                    defaultContent: 0, 
                    render: function(data) {
                        const val = parseFloat(data) || 0;
                        return val > 0 
                            ? `<strong class="text-success">${formatIDR(val)}</strong>`
                            : '<span class="text-muted">-</span>';
                    }
                }
            );
        } else if (type === 'pencatatan-nontender') {
            cols.push(
                { data: 'realisasi', defaultContent: 0, render: data => '<strong class="text-success">' + formatIDR(data) + '</strong>' },
                { 
                    data: 'nama_penyedia', defaultContent: '-',
                    render: data => data && data !== '-' ? `<div class="fw-bold text-primary">${data}</div>` : '-'
                },
                { data: 'jenis_realisasi', defaultContent: '-' },
                { 
                    data: 'kategori_pengadaan', defaultContent: '-',
                    render: data => data && data !== '-' ? `<span class="badge bg-label-info">${data}</span>` : '-'
                },
                { 
                    data: 'mtd_pemilihan', defaultContent: '-',
                    render: data => data && data !== '-' ? `<span class="badge bg-label-primary">${data}</span>` : '-'
                },
                { 
                    data: 'sumber_dana', defaultContent: '-',
                    render: data => data && data !== '-' ? `<span class="badge bg-label-success">${data}</span>` : '-'
                },
                { data: 'tgl_buat_paket', defaultContent: '-', render: data => formatDate(data) },
                { data: 'tgl_selesai_paket', defaultContent: '-', render: data => formatDate(data) },
                { 
                    data: 'no_pembayaran', defaultContent: '-',
                    render: data => data && data !== '-' ? `<span class="badge bg-label-secondary font-monospace">${data}</span>` : '-'
                }
            );
        } else if (type === 'pencatatan-swakelola') {
            cols.push(
                { data: 'realisasi', defaultContent: 0, render: data => '<strong class="text-success">' + formatIDR(data) + '</strong>' },
                { data: 'jenis_realisasi', defaultContent: '-' },
                { 
                    data: 'no_pembayaran', defaultContent: '-',
                    render: data => data && data !== '-' ? `<span class="badge bg-label-secondary font-monospace">${data}</span>` : '-'
                },
                { 
                    data: 'ket_realisasi', defaultContent: '-',
                    render: data => `<div style="max-width: 200px; white-space: normal; font-size: 0.85rem;">${data || '-'}</div>`
                }
            );
        }

        // Status column (always last)
        cols.push({ data: 'status', defaultContent: 'Selesai', render: data => renderStatus(data) });

        return cols;
    }

    $(document).ready(function() {
        populateFilters();

        dataTable = $('#table-lpse').DataTable({
            processing: true,
            serverSide: true,
            searchDelay: 500,
            ajax: {
                url: '<?= base_url('dashboard/getLpseDatatables') ?>/' + type,
                data: function(d) {
                    d.opd = $('#filter-opd').val();
                    
                    if (type === 'tender' || type === 'nontender' || type === 'pencatatan-nontender') {
                        d.status = $('#filter-status').val();
                    }
                    d.jenis_pengadaan = $('#filter-jenis-pengadaan').val();
                    d.kategori_pengadaan = $('#filter-kategori-pengadaan').val();
                    d.metode = $('#filter-metode').val();
                    d.jenis  = $('#filter-jenis').val();
                    d.year   = $('#filter-year').val() || 'all';
                }
            },
            columns: buildColumns(),
            dom: '<"d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2"Bl>rtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="far fa-file-excel mr-1"></i> Export Excel',
                    title: () => '<?= $title ?> - Tahun ' + ($('#filter-year').val() || 'Semua'),
                    messageTop: () => `Tanggal Export: ${new Date().toLocaleString('id-ID')}`,
                    className: 'btn btn-outline-success btn-sm me-2',
                    action: function(e, dt, node, config) {
                        runSmartExport.call(this, e, dt, node, config);
                    }
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="far fa-file-pdf mr-1"></i> Export PDF',
                    title: () => '<?= $title ?> - Tahun ' + ($('#filter-year').val() || 'Semua'),
                    messageTop: () => `Tanggal Export: ${new Date().toLocaleString('id-ID')}`,
                    orientation: 'landscape',
                    pageSize: 'A3',
                    className: 'btn btn-outline-danger btn-sm',
                    action: function(e, dt, node, config) {
                        runSmartExport.call(this, e, dt, node, config);
                    }
                }
            ],
            language: {
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                zeroRecords: "Tidak ada data yang ditemukan",
                paginate: { next: "Next", previous: "Prev" }
            }
        });

        $('#custom-search').on('keyup', function() {
            dataTable.search(this.value).draw();
        });

        $('#filter-status, #filter-opd, #filter-jenis-pengadaan, #filter-kategori-pengadaan, #filter-metode, #filter-jenis').on('change', function() {
            dataTable.ajax.reload();
        });

        $('#filter-year').on('change', function() {
            dataTable.ajax.reload();
            populateFilters();
        });

        const isRestrictedRole = <?= $isRestrictedRole ? 'true' : 'false' ?>;

        $('#btn-reset').on('click', function() {
            $('#custom-search').val('');
            // Don't reset OPD filter for restricted roles (opd/satker)
            if (!isRestrictedRole) {
                $('#filter-opd').val('all');
            }
            $('#filter-status, #filter-jenis-pengadaan, #filter-kategori-pengadaan, #filter-metode, #filter-jenis').val('all');
            $('#filter-year').val('all');
            dataTable.search('').draw();
            if (!isRestrictedRole) populateFilters();
        });

    // ─── Smart Export (fetch all rows first) ───
    function runSmartExport(e, dt, node, config) {
        const self = this;
        const button = $(node);
        const originalText = button.html();
        const settings = dt.settings()[0];
        const oldStart = settings._iDisplayStart;
        const oldLength = settings._iDisplayLength;

        button.addClass('disabled').html('<span class="spinner-border spinner-border-sm mr-1"></span> Mengolah...');

        dt.one('preXhr', function(e, s, data) {
            data.start = 0;
            data.length = -1;
        });

        dt.one('draw', function() {
            setTimeout(function() {
                try {
                    let actionName = (config && config.extend) ? config.extend : null;
                    if (!actionName) {
                        if (button.hasClass('btn-outline-success')) actionName = 'excelHtml5';
                        else if (button.hasClass('btn-outline-danger')) actionName = 'pdfHtml5';
                    }
                    if (!actionName) throw new Error('Could not determine export type');

                    let btnAction = $.fn.dataTable.ext.buttons[actionName];
                    if (!btnAction && actionName.indexOf('Html5') !== -1) {
                        btnAction = $.fn.dataTable.ext.buttons[actionName.replace('Html5', '')];
                    }

                    if (btnAction && typeof btnAction.action === 'function') {
                        btnAction.action.call(self, e, dt, node, config);
                    } else {
                        throw new Error('DataTables action not found for ' + actionName);
                    }
                } catch (err) {
                    console.error('Export Error:', err);
                } finally {
                    button.removeClass('disabled').html(originalText);
                    dt.one('preXhr', function(e, s, data) {
                        data.start = oldStart;
                        data.length = oldLength;
                    });
                    dt.draw(false);
                }
            }, 100);
        });
        dt.draw(false);
    }

    // ─── Populate Filters from existing data ───
    async function populateFilters() {
        try {
            const year = $('#filter-year').val() || 'all';
            const res = await fetch('<?= base_url('dashboard/getLpseDatatables') ?>/' + type + '?start=0&length=-1&draw=1&year=' + year);
            const json = await res.json();
            const data = json.data || [];

            // Tahun filter is already populated via PHP on page load

            // OPD filter — only populate for non-restricted roles
            if (!isRestrictedRole) {
                const opdSet = [...new Set(data.map(i => i.nama_satker).filter(Boolean))].sort();
                const opdSelect = $('#filter-opd');
                opdSelect.find('option:not(:first)').remove();
                opdSet.forEach(s => opdSelect.append(new Option(s, s)));
            }
            
            if (type === 'tender' || type === 'nontender') {
                // Jenis Pengadaan filter
                const jenisPengadaanSet = [...new Set(data.map(i => i.jenis_pengadaan).filter(Boolean))].sort();
                const jenisPengadaanSelect = $('#filter-jenis-pengadaan');
                jenisPengadaanSelect.find('option:not(:first)').remove();
                jenisPengadaanSet.forEach(m => jenisPengadaanSelect.append(new Option(m, m)));

                // Metode filter
                const metodeSet = [...new Set(data.map(i => i.metode).filter(Boolean))].sort();
                const metodeSelect = $('#filter-metode');
                metodeSelect.find('option:not(:first)').remove();
                metodeSet.forEach(m => metodeSelect.append(new Option(m, m)));
            } else if (type === 'pencatatan-nontender') {
                // Kategori Pengadaan filter
                const kategoriSet = [...new Set(data.map(i => i.kategori_pengadaan).filter(Boolean))].sort();
                const kategoriSelect = $('#filter-kategori-pengadaan');
                kategoriSelect.find('option:not(:first)').remove();
                kategoriSet.forEach(k => kategoriSelect.append(new Option(k, k)));

                // Metode filter (mtd_pemilihan)
                const metodeSet = [...new Set(data.map(i => i.mtd_pemilihan).filter(Boolean))].sort();
                const metodeSelect = $('#filter-metode');
                metodeSelect.find('option:not(:first)').remove();
                metodeSet.forEach(m => metodeSelect.append(new Option(m, m)));
            } else if (type === 'pencatatan-swakelola') {
                // Jenis Realisasi filter
                const jenisSet = [...new Set(data.map(i => i.jenis_realisasi).filter(Boolean))].sort();
                const jenisSelect = $('#filter-jenis');
                jenisSelect.find('option:not(:first)').remove();
                jenisSet.forEach(j => jenisSelect.append(new Option(j, j)));
            }
            
            // Status filter
            if (type === 'tender' || type === 'nontender' || type === 'pencatatan-nontender') {
                const statusSet = [...new Set(data.map(i => i.status).filter(Boolean))].sort();
                const statusSelect = $('#filter-status');
                statusSelect.find('option:not(:first)').remove(); // Clear existing except "All"
                statusSet.forEach(s => statusSelect.append(new Option(s, s)));

                // DEFAULT: Set status to "Selesai" for Non-Tender to match dashboard
                if (type === 'nontender') {
                    const hasSelesai = statusSet.some(s => s.toLowerCase().includes('selesai'));
                    if (hasSelesai && !$('#filter-status').val()) {
                        const exactSelesai = statusSet.find(s => s === 'Selesai') || statusSet.find(s => s.toLowerCase().includes('selesai'));
                        statusSelect.val(exactSelesai);
                        // Force reload data table with this default
                        dataTable.ajax.reload();
                    }
                }
            }
        } catch(e) {
            console.error('Filter population error:', e);
        }
    }
});
</script>
<?= $this->endSection() ?>
