<?= $this->extend('layout/main') ?><?= $this->section('content') ?>

<!-- DataTables CSS CDN -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<style>
    .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 0 !important; }
    div.dataTables_wrapper div.dataTables_info { padding-top: 0.5rem; }
    .dt-buttons .btn { margin-right: 5px; }

    /* Table Wrapping & Sizing */
    #table-epurchasing { width: 100% !important; border-collapse: collapse !important; }
    #table-epurchasing th, #table-epurchasing td { white-space: normal !important; vertical-align: top !important; }
    .col-ref { width: 120px !important; }
    .col-paket { min-width: 250px !important; }
    .col-produk { min-width: 180px !important; }
    .col-satker { min-width: 200px !important; }
    .col-harga { width: 140px !important; }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="fw-bold py-3 mb-0">
            <span class="text-muted fw-light">Data Pengadaan /</span> Data E-Purchasing
        </h4>
        <?php if(in_array(session()->get('role'), ['admin', 'operator'])): ?>
        <a href="<?= base_url('dashboard/clear-cache') ?>" class="btn btn-outline-danger btn-sm" data-confirm="Apakah Anda yakin ingin menghapus seluruh cache data? Hal ini akan memaksa sistem menarik ulang data terbaru dari API.">
            <i class="bx bx-trash me-1"></i> Clear Cache
        </a>
        <?php endif; ?>
    </div>

    <!-- Filter Card -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="mb-0">
                        <label class="form-label text-uppercase fw-bold small text-muted">Pencarian</label>
                        <input type="text" id="custom-search" placeholder="Cari Paket / Satker / Produk..." class="form-control">
                    </div>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="mb-0">
                        <label class="form-label text-uppercase fw-bold small text-muted">Filter Status</label>
                        <select id="filter-status" class="form-select">
                            <option value="">Semua Status</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2 mb-3 mb-md-0">
                    <div class="mb-0">
                        <label class="form-label text-uppercase fw-bold small text-muted">Tahun Anggaran</label>
                        <select id="filter-year" class="form-select">
                            <?php 
                            $curYear = date('Y');
                            for($i = $curYear; $i >= 2023; $i--): 
                            ?>
                            <option value="<?= $i ?>" <?= $i == $curYear ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-0">
                        <label class="form-label text-uppercase fw-bold small text-muted">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button id="btn-apply-filter" class="btn btn-primary w-100">
                                <i class="bx bx-filter-alt me-1"></i> Terapkan Filter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table id="table-epurchasing" class="table table-hover border-top">
                    <thead>
                        <tr>
                            <th class="col-ref">No Referensi</th>
                            <th class="col-paket">Nama Paket</th>
                            <th class="col-produk">Nama Program</th>
                            <th>Produk</th>
                            <th class="col-satker">Satker</th>
                            <th>Penyedia</th>
                            <th class="col-harga">Total Harga</th>
                            <th>Jumlah</th>
                            <th>Satuan</th>
                            <th>Tanggal Order</th>
                            <th>Tahun</th>
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
    let dataTable;
    let activeStatus = '';

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

    $(document).ready(function() {
        dataTable = $('#table-epurchasing').DataTable({
            processing: true,
            serverSide: true,
            searchDelay: 500,
            order: [[9, 'desc']], // Sort by Date DESC by default
            ajax: {
                url: '<?= base_url('dashboard/getEpurchasingDatatables') ?>',
                data: function(d) {
                    d.status_filter = activeStatus;
                    d.year = $('#filter-year').val() || '<?= date('Y') ?>';
                }
            },
            columns: [
                { data: 'no_referensi', defaultContent: '-' },
                { data: 'nama_paket', defaultContent: '-' },
                { 
                    data: 'nama_program', 
                    defaultContent: '-',
                    render: function(data) {
                        return `<small class="text-muted d-block">${data || '-'}</small>`;
                    }
                },
                { 
                    data: 'nama_produk', 
                    defaultContent: '-',
                    render: function(data) {
                        return `<div style="max-width: 300px; white-space: normal; font-size: 0.85rem; line-height: 1.4; color: #566a7f;">
                            ${data || '-'}
                        </div>`;
                    }
                },
                { 
                    data: 'nama_satker', 
                    defaultContent: '-',
                    render: function(data) { return `<small class="text-muted d-block" style="font-size:0.75rem">${data || '-'}</small>`; }
                },
                { 
                    data: 'nama_penyedia', 
                    defaultContent: '-',
                    render: function(data) { return `<span class="fw-bold text-primary">${data || '-'}</span>`; }
                },
                { 
                    data: 'pagu', 
                    defaultContent: 0,
                    render: function(data) { return '<strong>' + formatIDR(data) + '</strong>'; }
                },
                { data: 'jumlah', defaultContent: 0 },
                { data: 'satuan', defaultContent: 'Unit' },
                { 
                    data: 'tanggal', 
                    defaultContent: '-',
                    render: function(data) { return formatDate(data); }
                },
                { data: 'fiscal_year', defaultContent: '-' },
                { 
                    data: 'status', 
                    defaultContent: '-',
                    render: function(data) {
                        let s = (data || '').toUpperCase().replace(/_/g, ' ');
                        let cls = 'bg-label-secondary';
                        if (s === 'COMPLETED') cls = 'bg-label-success';
                        else if (s === 'ON PROCESS') cls = 'bg-label-info';
                        else if (s === 'ON ADDENDUM') cls = 'bg-label-warning';
                        else if (s === 'PAYMENT OUTSIDE SYSTEM') cls = 'bg-label-primary';
                        return `<span class="badge ${cls}">${s || '-'}</span>`;
                    }
                }
            ],
            dom: '<"d-flex justify-content-between align-items-center mb-3"Bl>rtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="far fa-file-excel mr-1"></i> Export Excel',
                    title: 'Data E-Purchasing - <?= date("Y") ?>',
                    className: 'btn btn-outline-success btn-sm me-2',
                    action: function(e, dt, node, config) {
                        runSmartExport.call(this, e, dt, node, config);
                    }
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="far fa-file-pdf mr-1"></i> Export PDF',
                    title: 'Data E-Purchasing - <?= date("Y") ?>',
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

        // Populate status filter from data
        populateFilters();

        // Filter: status dropdown change
        $('#filter-status').on('change', function() {
            activeStatus = this.value;
            dataTable.ajax.reload();
        });

        // Filter: year dropdown change
        $('#filter-year').on('change', function() {
            dataTable.ajax.reload();
            populateFilters(); // Re-populate statuses for new year
        });

        // Apply filter button
        $('#btn-apply-filter').on('click', function() {
            activeStatus = $('#filter-status').val();
            dataTable.ajax.reload();
        });
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
                }

                dt.one('preXhr', function(e, s, data) {
                    settings._iDisplayStart = oldStart;
                    settings._iDisplayLength = oldLength;
                    data.start = oldStart;
                    data.length = oldLength;
                });

                setTimeout(function() {
                    dt.ajax.reload(null, false);
                    button.removeClass('disabled').html(originalText);
                }, 1000);
            }, 500);
        });

        dt.ajax.reload();
    }
    // ─── Populate status filter from actual data ───
    async function populateFilters() {
        try {
            const year = $('#filter-year').val() || '<?= date('Y') ?>';
            // Request minimal data (length=1) just to get the unique_statuses metadata from the server
            const res = await fetch('<?= base_url('dashboard/getEpurchasingDatatables') ?>?start=0&length=1&draw=1&status_filter=&year=' + year);
            const json = await res.json();
            const statusSet = json.unique_statuses || [];

            const statusSelect = $('#filter-status');
            statusSelect.find('option:not(:first)').remove(); // Clear existing options except "All"
            statusSet.sort().forEach(s => statusSelect.append(new Option(s, s)));
        } catch(e) {
            console.warn('Filter population failed:', e);
        }
    }
</script>
<?= $this->endSection() ?>
