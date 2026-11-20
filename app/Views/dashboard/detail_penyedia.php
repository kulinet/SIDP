<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?><?= $this->section('content') ?>

<!-- DataTables CSS CDN -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<style>
    .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 0 !important; }
    div.dataTables_wrapper div.dataTables_info { padding-top: 0.5rem; }
    #table-detail-penyedia { width: 100% !important; border-collapse: collapse !important; }
    #table-detail-penyedia th, #table-detail-penyedia td { white-space: normal !important; vertical-align: middle !important; }
    #table-transaksi-penyedia { width: 100% !important; border-collapse: collapse !important; }
    #table-transaksi-penyedia th, #table-transaksi-penyedia td { white-space: normal !important; vertical-align: middle !important; }
    .profile-label { font-weight: 600; color: #566a7f; width: 200px; }
    .bg-label-success { background-color: #e8fadf !important; color: #71dd37 !important; }
    .bg-label-primary { background-color: #e7e7ff !important; color: #696cff !important; }
    .bg-label-warning { background-color: #fff2d6 !important; color: #ffab00 !important; }
    .bg-label-info { background-color: #d7f5fc !important; color: #03c3ec !important; }
    .bg-label-secondary { background-color: #ebeef0 !important; color: #8592a3 !important; }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
        <div>
            <h4 class="fw-bold py-1 mb-1">
                <span class="text-muted fw-light">DATABASE /</span> Detail &amp; Transaksi Penyedia
            </h4>
            <p class="text-muted mb-0 small">
                Data penyedia terintegrasi dari seluruh jenis pengadaan: <strong>E-Katalog</strong>, <strong>Tender</strong>, <strong>Non-Tender</strong>, dan <strong>Pencatatan Non-Tender</strong>.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= base_url('dashboard/exportDetailPenyedia?tahun=' . date('Y')) ?>" id="btn-export-excel" class="btn btn-success shadow-sm">
                <i class="bx bxs-file-export me-1"></i> Export Excel
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <div class="row align-items-end g-3">
                <div class="col-md-5">
                    <label class="form-label text-uppercase fw-bold small text-muted">Pencarian Penyedia</label>
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" id="custom-search" placeholder="Ketik nama atau kode penyedia..." class="form-control">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-uppercase fw-bold small text-muted">Tahun Anggaran</label>
                    <select id="filter-tahun" class="form-select">
                        <option value="all">Semua Tahun</option>
                        <?php 
                        $currYear = date('Y');
                        for($y = $currYear; $y >= 2022; $y--): ?>
                            <option value="<?= $y ?>" <?= ($y == $currYear) ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-uppercase fw-bold small text-muted">Domisili Penyedia</label>
                    <select id="filter-lokasi" class="form-select">
                        <option value="all">Semua Domisili</option>
                        <option value="lobar">Lombok Barat</option>
                        <option value="mataram">Mataram</option>
                        <option value="ntb_lain">NTB Lainnya (Loteng, Lotim, Sumbawa, Bima)</option>
                        <option value="luar">Luar Daerah (Jakarta, Surabaya, dll.)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" id="btn-reset-filter" class="btn btn-outline-secondary w-100">
                        <i class="bx bx-reset me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center py-3 bg-white border-bottom">
            <h5 class="card-title mb-0 fw-bold"><i class="bx bx-list-check me-2 text-primary"></i>Daftar Penyedia Terintegrasi</h5>
            <div class="d-flex align-items-center gap-2 mt-2 mt-sm-0">
                <a href="<?= base_url('dashboard/exportDetailPenyedia?tahun=' . date('Y')) ?>" id="btn-export-excel-card" class="btn btn-sm btn-outline-success">
                    <i class="bx bxs-file-export me-1"></i> Export Excel (.xlsx)
                </a>
            </div>
        </div>
        <div class="card-datatable table-responsive pt-2">
            <table class="datatables-basic table table-hover table-striped border-top" id="table-detail-penyedia">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;">No</th>
                        <th style="width: 140px;">Kode Penyedia</th>
                        <th style="min-width: 220px;">Nama Penyedia</th>
                        <th style="width: 130px;">Domisili</th>
                        <th style="min-width: 220px;">Alamat</th>
                        <th style="min-width: 210px;">KPP Pajak &amp; NPWP</th>
                        <th style="width: 110px;">Total Paket</th>
                        <th style="width: 150px;">Total Nilai</th>
                        <th style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be populated by DataTables -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Daftar Transaksi Penyedia -->
<div class="modal fade" id="modalTransaksi" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white py-3">
        <div>
            <h5 class="modal-title text-white mb-0" id="modalTransaksiTitle">Daftar Transaksi Penyedia</h5>
            <small class="text-white-50" id="modalTransaksiSubtitle">Daftar seluruh paket pekerjaan &amp; pengadaan yang dikerjakan</small>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <!-- Summary Counters -->
        <div class="row g-3 mb-4" id="transaksiSummaryCards">
            <div class="col-md-4">
                <div class="p-3 bg-light rounded border text-center">
                    <span class="text-muted small d-block">TOTAL PAKET</span>
                    <h4 class="mb-0 fw-bold text-primary" id="sumTotalPaket">0 Paket</h4>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-light rounded border text-center">
                    <span class="text-muted small d-block">TOTAL NILAI TRANSAKSI</span>
                    <h4 class="mb-0 fw-bold text-success" id="sumTotalNilai">Rp 0</h4>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-light rounded border text-center">
                    <span class="text-muted small d-block">DISTRIBUSI SUMBER</span>
                    <div id="sumBadgesSumber" class="d-flex flex-wrap gap-1 justify-content-center mt-1">
                        -
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center py-5" id="loadingTransaksi">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Memuat daftar transaksi penyedia...</p>
        </div>

        <div id="contentTransaksi" style="display: none;">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="table-transaksi-penyedia">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;">No</th>
                            <th style="width: 100px;">Sumber</th>
                            <th style="width: 120px;">Kode Paket</th>
                            <th style="min-width: 250px;">Nama Paket</th>
                            <th style="min-width: 180px;">Satker / OPD</th>
                            <th style="width: 70px;">Tahun</th>
                            <th style="width: 140px;">Nilai</th>
                            <th style="width: 110px;">Status / Metode</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Populated by DataTables -->
                    </tbody>
                </table>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Detail Profil Penyedia -->
<div class="modal fade" id="modalDetail" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-info text-white py-3">
        <div>
            <h5 class="modal-title text-white mb-0" id="modalDetailTitle">Profil Lengkap Penyedia</h5>
            <small class="text-white-50" id="modalDetailSubtitle">Informasi identitas, legalitas, dan kualifikasi usaha</small>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4" id="modalDetailBody">
        <div class="text-center py-5" id="loadingDetail">
            <div class="spinner-border text-info" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Memuat data identitas penyedia dari Inaproc &amp; LPSE...</p>
        </div>

        <div id="contentDetail" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 fw-bold text-muted text-uppercase small">Data Identitas</h6>
                <span class="badge bg-label-info" id="dtl_sumber_badge">Inaproc LKPP</span>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <td class="profile-label">Nama Penyedia</td>
                            <td id="dtl_nama" class="fw-bold text-dark"></td>
                        </tr>
                        <tr>
                            <td class="profile-label">Kode Penyedia</td>
                            <td id="dtl_kode" class="font-monospace"></td>
                        </tr>
                        <tr>
                            <td class="profile-label">NPWP</td>
                            <td id="dtl_npwp" class="fw-bold text-primary font-monospace"></td>
                        </tr>
                        <tr>
                            <td class="profile-label">Domisili Usaha</td>
                            <td>
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <span id="dtl_lokasi"></span>
                                    <button type="button" class="btn btn-xs btn-outline-primary shadow-sm" id="btn-detect-domisili-ai" title="Koreksi / Deteksi Domisili Otomatis Berdasarkan Alamat Menggunakan AI">
                                        <i class="bx bx-bot me-1"></i> Deteksi AI
                                    </button>
                                </div>
                                <small id="dtl_ai_status" class="text-muted d-block mt-1" style="display:none;"></small>
                            </td>
                        </tr>
                        <tr>
                            <td class="profile-label">KPP Pajak Terdaftar</td>
                            <td id="dtl_kpp"></td>
                        </tr>
                        <tr>
                            <td class="profile-label">Bentuk Usaha</td>
                            <td id="dtl_bentuk"></td>
                        </tr>
                        <tr>
                            <td class="profile-label">Jenis Perusahaan</td>
                            <td id="dtl_jenis"></td>
                        </tr>
                        <tr>
                            <td class="profile-label">Alamat Lengkap</td>
                            <td id="dtl_alamat"></td>
                        </tr>
                        <tr>
                            <td class="profile-label">Email</td>
                            <td id="dtl_email"></td>
                        </tr>
                        <tr>
                            <td class="profile-label">Telepon</td>
                            <td id="dtl_telepon"></td>
                        </tr>
                        <tr>
                            <td class="profile-label">NIB</td>
                            <td id="dtl_nib"></td>
                        </tr>
                        <tr>
                            <td class="profile-label">Status Penyedia</td>
                            <td id="dtl_status"></td>
                        </tr>
                        <tr>
                            <td class="profile-label">Status UMKK</td>
                            <td id="dtl_umkk"></td>
                        </tr>
                        <tr>
                            <td class="profile-label align-top">KBLI (Bidang Usaha)</td>
                            <td id="dtl_kbli"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="errorDetail" class="alert alert-danger mt-3" style="display: none;">
            Gagal mengambil data dari server.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
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
    let dtTable = $('#table-detail-penyedia').DataTable({
        ajax: {
            url: "<?= base_url('dashboard/getDetailPenyedia') ?>",
            type: "GET",
            data: function(d) {
                d.tahun = $('#filter-tahun').val();
                d.lokasi = $('#filter-lokasi').val();
            }
        },
        columns: [
            { data: 'no' },
            { data: 'kode_penyedia' },
            { data: 'nama_penyedia' },
            { data: 'lokasi' },
            { data: 'alamat' },
            { data: 'kpp_pajak' },
            { data: 'total_paket' },
            { data: 'total_transaksi' },
            { data: 'aksi' }
        ],
        language: {
            emptyTable: "Tidak ada data penyedia ditemukan",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 entri",
            infoFiltered: "(disaring dari _MAX_ total entri)",
            lengthMenu: "Tampilkan _MENU_ entri",
            loadingRecords: "Sedang memuat data...",
            processing: '<i class="bx bx-spin bx-loader-alt fs-3 text-primary"></i> Memuat data penyedia...',
            search: "Cari:",
            zeroRecords: "Tidak ditemukan data yang sesuai",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        },
        processing: true,
        deferRender: true,
        pageLength: 25,
        dom: '<"row mx-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"p>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
    });

    // Auto-deteksi domisili via AI berdasarkan alamat (baris halaman aktif yang belum pasti)
    let aiQueueRunning = false;
    function runAiDomisiliQueue() {
        if (aiQueueRunning) return;
        let pending = [];
        dtTable.rows({ page: 'current' }).every(function() {
            let d = this.data();
            if (d && d.needs_ai && !d._ai_done) pending.push({ idx: this.index(), d: d });
        });
        if (!pending.length) return;
        aiQueueRunning = true;

        let i = 0;
        function next() {
            if (i >= pending.length) { aiQueueRunning = false; return; }
            let item = pending[i++];
            let node = $(dtTable.cell(item.idx, 3).node());
            node.html('<span class="badge bg-label-secondary py-1 px-2"><span class="spinner-border spinner-border-sm me-1" style="width:.7rem;height:.7rem;"></span>AI...</span>');
            $.ajax({
                url: "<?= base_url('dashboard/detectDomisiliAi') ?>",
                type: "POST",
                dataType: "json",
                data: {
                    nama_penyedia: item.d.raw_nama,
                    kode_penyedia: item.d.raw_kode,
                    npwp: item.d.raw_npwp,
                    alamat_penyedia: item.d.raw_alamat
                },
                success: function(res) {
                    item.d._ai_done = true;
                    if (res.status === 'success' && res.data) {
                        node.html(res.data.badge);
                        node.attr('title', res.data.keterangan || '');
                        next();
                    } else {
                        node.html('<span class="text-muted small">-</span>');
                        // Hentikan antrean bila AI belum dikonfigurasi
                        if (res.message && res.message.indexOf('API Key') !== -1) { aiQueueRunning = false; return; }
                        next();
                    }
                },
                error: function() {
                    item.d._ai_done = true;
                    node.html('<span class="text-muted small">-</span>');
                    next();
                }
            });
        }
        next();
    }
    dtTable.on('draw.dt', function() { setTimeout(runAiDomisiliQueue, 100); });

    // Synchronize Export Excel button URLs with current filters
    function updateExportUrl() {
        let tahun = $('#filter-tahun').val() || 'all';
        let lokasi = $('#filter-lokasi').val() || 'all';
        let search = $('#custom-search').val() || '';
        let url = "<?= base_url('dashboard/exportDetailPenyedia') ?>?tahun=" + encodeURIComponent(tahun) + "&lokasi=" + encodeURIComponent(lokasi);
        if (search.trim() !== '') {
            url += "&search=" + encodeURIComponent(search.trim());
        }
        $('#btn-export-excel, #btn-export-excel-card').attr('href', url);
    }
    updateExportUrl();

    // Setup Custom Search
    $('#custom-search').on('keyup', function () {
        dtTable.search(this.value).draw();
        updateExportUrl();
    });

    // Reload table on year change or location filter
    $('#filter-tahun, #filter-lokasi').on('change', function() {
        dtTable.ajax.reload();
        updateExportUrl();
    });

    // Reset Filter
    $('#btn-reset-filter').on('click', function() {
        $('#custom-search').val('');
        $('#filter-tahun').val('all');
        $('#filter-lokasi').val('all');
        dtTable.search('').draw();
        dtTable.ajax.reload();
        updateExportUrl();
    });
    
    // Modal instances
    const modalDetail = new bootstrap.Modal(document.getElementById('modalDetail'));
    const modalTransaksi = new bootstrap.Modal(document.getElementById('modalTransaksi'));
    let dtTransaksi = null;

    // Handler: Klik Tombol "Transaksi"
    $('#table-detail-penyedia').on('click', '.btn-lihat-transaksi', function() {
        const nama = $(this).data('nama');
        const kode = $(this).data('kode');
        const tahun = $('#filter-tahun').val();

        $('#modalTransaksiTitle').text('Daftar Transaksi: ' + nama);
        $('#modalTransaksiSubtitle').text('Kode Penyedia: ' + (kode || '-'));
        $('#loadingTransaksi').show();
        $('#contentTransaksi').hide();

        modalTransaksi.show();

        // Destroy previous sub-datatable if exists
        if (dtTransaksi) {
            dtTransaksi.destroy();
            $('#table-transaksi-penyedia tbody').empty();
        }

        $.ajax({
            url: "<?= base_url('dashboard/getTransaksiPenyedia') ?>",
            type: "GET",
            data: {
                nama_penyedia: nama,
                kode_penyedia: kode,
                tahun: tahun
            },
            dataType: "json",
            success: function(res) {
                $('#loadingTransaksi').hide();
                $('#contentTransaksi').show();

                if (res.summary) {
                    $('#sumTotalPaket').text((res.summary.total_paket || 0) + ' Paket');
                    $('#sumTotalNilai').text(res.summary.total_nilai_formatted || 'Rp 0');
                    
                    let badgesHtml = '';
                    if (res.summary.by_sumber) {
                        for (const [sumber, count] of Object.entries(res.summary.by_sumber)) {
                            if (count > 0) {
                                let bClass = 'bg-secondary';
                                if (sumber === 'E-Katalog') bClass = 'bg-success';
                                else if (sumber === 'Tender') bClass = 'bg-primary';
                                else if (sumber === 'Non-Tender') bClass = 'bg-warning text-dark';
                                else if (sumber === 'Pencatatan Non-Tender') bClass = 'bg-info';
                                badgesHtml += `<span class="badge ${bClass}">${sumber}: ${count}</span> `;
                            }
                        }
                    }
                    $('#sumBadgesSumber').html(badgesHtml || '-');
                }

                dtTransaksi = $('#table-transaksi-penyedia').DataTable({
                    data: res.data || [],
                    columns: [
                        { data: 'no' },
                        { data: 'sumber' },
                        { data: 'kd_paket' },
                        { data: 'nama_paket' },
                        { data: 'nama_satker' },
                        { data: 'tahun' },
                        { data: 'nilai_formatted' },
                        { data: 'status' }
                    ],
                    pageLength: 10,
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
                    }
                });
            },
            error: function(err) {
                $('#loadingTransaksi').hide();
                alert('Gagal mengambil daftar transaksi penyedia.');
            }
        });
    });

    // Handler: Klik Tombol "Detail Profil"
    $('#table-detail-penyedia').on('click', '.btn-lihat-detail', function() {
        const kode = $(this).data('kode');
        const nama = $(this).data('nama');
        const domisili = $(this).data('domisili');
        const lokasiBadge = $(this).data('lokasi-badge');
        const kppDesc = $(this).data('kpp');
        const npwp = $(this).data('npwp');
        const alamat = $(this).data('alamat');
        
        $('#modalDetailTitle').text('Profil Penyedia: ' + nama);
        $('#loadingDetail').show();
        $('#contentDetail').hide();
        $('#errorDetail').hide();
        
        if (lokasiBadge) {
            $('#dtl_lokasi').html(lokasiBadge);
        }
        if (kppDesc) {
            $('#dtl_kpp').html(kppDesc);
        }
        if (npwp) {
            $('#dtl_npwp').text(npwp);
        }
        if (alamat) {
            $('#dtl_alamat').text(alamat);
        }
        
        modalDetail.show();
        
        $.ajax({
            url: "<?= base_url('dashboard/getProfilPenyedia') ?>",
            type: "GET",
            data: {
                nama_penyedia: nama,
                kode_penyedia: kode
            },
            dataType: "json",
            success: function(res) {
                $('#loadingDetail').hide();
                let data = res.data || {};
                
                $('#dtl_sumber_badge').text(data.sumber_data_str || 'Data Lokal');
                $('#dtl_nama').text(data.nama_penyedia || nama || '-');
                $('#dtl_kode').text(data.kode_penyedia || kode || '-');
                $('#dtl_npwp').text(data.npwp_penyedia || '-');
                
                // Domisili & KPP (Gunakan badge hasil sinkronisasi atau fallback ke badge baris tabel)
                $('#dtl_lokasi').html(data.lokasi_badge || lokasiBadge || '-');
                $('#dtl_kpp').html((data.kpp_nama || kppDesc || '-') + (data.lokasi_keterangan ? ' <small class="text-muted d-block">' + data.lokasi_keterangan + '</small>' : ''));

                $('#dtl_bentuk').text(data.bentuk_usaha || '-');
                $('#dtl_jenis').text(data.jenis_perusahaan || '-');
                $('#dtl_alamat').text(data.alamat_penyedia || '-');
                $('#dtl_email').text(data.email || '-');
                $('#dtl_telepon').text(data.telepon || '-');
                $('#dtl_nib').text(data.nib || '-');
                
                // Status Aktif
                let statusBadge = '<span class="text-muted">-</span>';
                if (data.status_aktif !== null && data.status_aktif !== undefined) {
                    if (data.status_aktif === 'active' || data.status_aktif === 1) {
                        statusBadge = '<span class="badge bg-success">Aktif</span>';
                    } else {
                        statusBadge = '<span class="badge bg-danger">Non-Aktif / Blacklist</span>';
                    }
                }
                $('#dtl_status').html(statusBadge);
                
                // Status UMKK
                let umkkBadge = '<span class="text-muted">-</span>';
                if (data.status_umkk !== null && data.status_umkk !== undefined) {
                    if (data.status_umkk == 1) {
                        umkkBadge = '<span class="badge bg-info">UMKK (Usaha Mikro, Kecil, dan Koperasi)</span>';
                    } else {
                        umkkBadge = '<span class="badge bg-secondary">Non-UMKK</span>';
                    }
                }
                $('#dtl_umkk').html(umkkBadge);
                
                // KBLI
                let kbli_name = data.kbli_name ? data.kbli_name.split(',') : [];
                let kbli_id = data.kbli_id ? (String(data.kbli_id).split(',')) : [];
                
                let kbliHtml = '<ul class="mb-0 ps-3" style="max-height: 200px; overflow-y: auto;">';
                if (kbli_name.length > 0 && kbli_name[0].trim() !== '') {
                    let uniqueKbli = new Set();
                    kbli_name.forEach(function(name, idx) {
                        let k_name = name.trim();
                        let k_id = kbli_id[idx] ? kbli_id[idx].trim() : '';
                        let itemText = k_id ? `<strong>[${k_id}]</strong> ${k_name}` : k_name;
                        
                        if (!uniqueKbli.has(itemText)) {
                            uniqueKbli.add(itemText);
                            kbliHtml += `<li>${itemText}</li>`;
                        }
                    });
                } else {
                    kbliHtml += '<li><span class="text-muted">-</span></li>';
                }
                kbliHtml += '</ul>';
                $('#dtl_kbli').html(kbliHtml);

                $('#contentDetail').show();
            },
            error: function(err) {
                $('#loadingDetail').hide();
                $('#errorDetail').text('Gagal memuat profil penyedia.').show();
            }
        });
    });

    // Handler: Klik Tombol "Deteksi AI" pada Domisili Usaha di Modal
    $('#btn-detect-domisili-ai').on('click', function() {
        let btn = $(this);
        let origHtml = btn.html();
        
        let nama = $('#dtl_nama').text().trim();
        let kode = $('#dtl_kode').text().trim();
        let npwp = $('#dtl_npwp').text().trim();
        let alamat = $('#dtl_alamat').text().trim();

        if (!alamat || alamat === '-' || alamat.length < 3) {
            $('#dtl_ai_status').text('Tidak dapat mendeteksi: Teks alamat lengkap kosong atau belum tersedia.').addClass('text-danger').removeClass('text-success text-muted').show();
            return;
        }

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Menganalisis...');
        $('#dtl_ai_status').text('AI sedang menganalisis teks alamat & geografi...').addClass('text-muted').removeClass('text-danger text-success').show();

        $.ajax({
            url: "<?= base_url('dashboard/detectDomisiliAi') ?>",
            type: "POST",
            data: {
                nama_penyedia: nama,
                kode_penyedia: kode,
                npwp: npwp,
                alamat_penyedia: alamat
            },
            dataType: "json",
            success: function(res) {
                btn.prop('disabled', false).html(origHtml);
                if (res.status === 'success' && res.data) {
                    $('#dtl_lokasi').html(res.data.badge);
                    let infoText = 'Berhasil: ' + res.data.kabupaten_kota + (res.data.provinsi ? ' (' + res.data.provinsi + ')' : '');
                    if (res.data.keterangan) {
                        infoText += ' — ' + res.data.keterangan;
                    }
                    $('#dtl_ai_status').text(infoText).addClass('text-success').removeClass('text-danger text-muted').show();

                    // Sinkronkan tabel utama di latar belakang tanpa reload halaman
                    if (typeof dtTable !== 'undefined') {
                        dtTable.ajax.reload(null, false);
                    }
                } else {
                    $('#dtl_ai_status').text('Gagal: ' + (res.message || 'Respons tidak valid.')).addClass('text-danger').removeClass('text-success text-muted').show();
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(origHtml);
                let errDetail = 'Koneksi ke AI terputus atau server sedang sibuk.';
                try {
                    let errObj = JSON.parse(xhr.responseText);
                    if (errObj && errObj.message) errDetail = errObj.message;
                } catch(e) {}
                $('#dtl_ai_status').text('Gagal: ' + errDetail).addClass('text-danger').removeClass('text-success text-muted').show();
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
