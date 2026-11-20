<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?><?= $this->section('content') ?>

<!-- DataTables CSS CDN -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<style>
    .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 0 !important; }
    div.dataTables_wrapper div.dataTables_info { padding-top: 0.5rem; }
    #table-penyedia { width: 100% !important; border-collapse: collapse !important; }
    #table-penyedia th, #table-penyedia td { white-space: normal !important; vertical-align: top !important; }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
        <h4 class="fw-bold py-3 mb-0">
            <span class="text-muted fw-light">DATABASE /</span> Penyedia Katalog
        </h4>
        
        <div class="d-flex gap-2">
            <a href="<?= base_url('dashboard/exportPenyediaKatalog?tahun=') ?>" id="btn-export-excel" class="btn btn-success btn-sm d-flex align-items-center">
                <i class="bx bx-export me-1"></i> Export Excel
            </a>

        </div>
    </div>

    <!-- Filter Card -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="mb-0">
                        <label class="form-label text-uppercase fw-bold small text-muted">Pencarian Penyedia</label>
                        <input type="text" id="custom-search" placeholder="Ketik nama penyedia atau produk..." class="form-control">
                    </div>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="mb-0">
                        <label class="form-label text-uppercase fw-bold small text-muted">Kategori Produk</label>
                        <select id="filter-kategori" class="form-select">
                            <option value="">-- Semua Kategori --</option>
                            <option value="Makanan & Minuman">Makanan & Minuman</option>
                            <option value="Elektronik & IT">Elektronik & IT</option>
                            <option value="Alat Tulis Kantor (ATK)">Alat Tulis Kantor (ATK)</option>
                            <option value="Internet & Jaringan">Internet & Jaringan</option>
                            <option value="Kesehatan & Medis">Kesehatan & Medis</option>
                            <option value="Konstruksi & Material">Konstruksi & Material</option>
                            <option value="Kendaraan & Transportasi">Kendaraan & Transportasi</option>
                            <option value="Mebel & Furniture">Mebel & Furniture</option>
                            <option value="Jasa">Jasa</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="mb-0">
                        <label class="form-label text-uppercase fw-bold small text-muted">Tahun</label>
                        <select id="filter-tahun" class="form-select">
                            <?php 
                            $currYear = date('Y');
                            for($y = $currYear; $y >= 2022; $y--): ?>
                                <option value="<?= $y ?>"><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
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
    <div class="card">
        <div class="card-datatable table-responsive pt-0">
            <table class="datatables-basic table table-hover table-striped border-top" id="table-penyedia">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th style="min-width: 250px;">Nama Penyedia</th>
                        <th style="min-width: 150px;">Kategori Produk</th>
                        <th style="width: 100px;">Total Paket</th>
                        <th style="width: 150px;">Total Transaksi</th>
                        <th style="min-width: 300px;">Daftar Produk / Pekerjaan</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be populated by DataTables -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- DataTables JS CDN -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    let dtTable = $('#table-penyedia').DataTable({
        ajax: {
            url: "<?= base_url('dashboard/getPenyediaKatalog') ?>",
            type: "GET",
            data: function(d) {
                d.tahun = $('#filter-tahun').val();
            }
        },
        columns: [
            { data: 'no' },
            { data: 'nama_penyedia' },
            { data: 'kategori' },
            { data: 'total_paket' },
            { data: 'total_transaksi' },
            { data: 'daftar_produk' }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json',
            processing: '<i class="bx bx-spin bx-loader-alt fs-3 text-primary"></i> Memuat data...'
        },
        processing: true,
        deferRender: true,
        pageLength: 25,
        dom: '<"row mx-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"p>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        initComplete: function () {
            // Apply custom filters on initialization
            dtTable.draw();
        }
    });

    // Setup Custom Search
    $('#custom-search').on('keyup', function () {
        dtTable.search(this.value).draw();
    });

    // Custom Category Filter
    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            var selectedKategori = $('#filter-kategori').val();
            var rowKategori = data[2] || ""; // Kolom index 2 adalah kategori
            
            // Check if selected category is empty or if it matches the row's category text (ignoring HTML tags inside badge)
            if (selectedKategori === "") {
                return true;
            }
            if (rowKategori.indexOf(selectedKategori) !== -1) {
                return true;
            }
            return false;
        }
    );

    $('#filter-kategori').on('change', function() {
        dtTable.draw();
    });

    // Reload table on year change
    $('#filter-tahun').on('change', function() {
        // Update export URL
        let btnExport = document.getElementById('btn-export-excel');
        btnExport.href = "<?= base_url('dashboard/exportPenyediaKatalog?tahun=') ?>" + this.value;
        
        // Reload data only if DataTable is already initialized completely
        if ($.fn.DataTable.isDataTable('#table-penyedia')) {
            dtTable.ajax.reload();
        }
    });

    // Set initial export URL without triggering ajax reload (since dtTable does it automatically on init)
    let initialTahun = $('#filter-tahun').val();
    let btnExport = document.getElementById('btn-export-excel');
    btnExport.href = "<?= base_url('dashboard/exportPenyediaKatalog?tahun=') ?>" + initialTahun;

    // Reset Filter
    $('#btn-reset-filter').on('click', function() {
        $('#custom-search').val('');
        $('#filter-kategori').val('');
        let currYear = new Date().getFullYear();
        $('#filter-tahun').val(currYear);
        dtTable.search('').draw();
        $('#filter-tahun').trigger('change');
    });
});
</script>
<?= $this->endSection() ?>
