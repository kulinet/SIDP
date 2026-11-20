<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?><?= $this->section('content') ?>

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
            <span class="text-muted fw-light">Data Pengadaan /</span> Data E-Purchasing
        </h4>
        <?php if(in_array(session()->get('role'), ['admin', 'operator'])): ?>
        <a href="<?= base_url('dashboard/clear-cache') ?>" class="btn btn-outline-danger btn-sm" data-confirm="Apakah Anda yakin ingin menghapus seluruh cache data? Hal ini akan memaksa sistem menarik ulang data terbaru dari API.">
            <i class="bx bx-trash me-1"></i> Clear Cache
        </a>
        <?php endif; ?>
        <?php 
        $isRestrictedRole = in_array(session()->get('role'), ['opd', 'satker']);
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
            <div class="row align-items-end">
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="mb-0">
                        <label class="form-label text-uppercase fw-bold small text-muted">Pencarian</label>
                        <input type="text" id="custom-search" placeholder="Cari Paket / Satker / Produk..." class="form-control">
                    </div>
                </div>
                <?php if(!$isRestrictedRole): ?>
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="mb-0">
                        <label class="form-label text-uppercase fw-bold small text-muted">Filter Satker (OPD)</label>
                        <select id="filter-satker" class="form-select select2">
                            <option value="">Semua Satker</option>
                        </select>
                    </div>
                </div>
                <?php else: ?>
                <input type="hidden" id="filter-satker" value="">
                <?php endif; ?>
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
                            <th>Kode RUP</th>
                            <th class="col-paket">Nama Paket</th>
                            <th class="col-produk">Nama Program</th>
                            <th class="col-produk">Produk &amp; Rincian</th>
                            <th class="col-satker">Satker</th>
                            <th>Penyedia</th>
                            <th class="col-harga">Pagu RUP</th>
                            <th class="col-harga">Total Harga</th>
                            <th>Flag Minikom</th>
                            <th>Jumlah</th>
                            <th>Satuan</th>
                            <th>Tanggal Order</th>
                            <th>Tahun</th>
                            <th>Asal Dana</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Modal Rincian Produk (products_json) & Ongkos Kirim -->
<div class="modal fade" id="tableProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #696cff 0%, #3f4191 100%); color: white;">
                <div class="d-flex align-items-center">
                    <div class="d-flex align-items-center justify-content-center bg-white text-primary me-3" style="width: 38px; height: 38px; border-radius: 10px;">
                        <i class="bx bx-purchase-tag-alt fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="tblProdModalTitle">Rincian Item Produk &amp; Harga</h5>
                        <p class="text-white-50 small mb-0" id="tblProdModalSubtitle">Detail komoditas dari data produk E-Purchasing beserta Ongkos Kirim</p>
                    </div>
                </div>
                <button type="button" class="btn-close" style="filter: brightness(0) invert(1);" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3 p-3 rounded-3" style="background: #f8f9fa; border: 1px solid #e9ecef;" id="tblProdModalMeta"></div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 45px;">#</th>
                                <th>Nama Produk &amp; Product ID</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Harga Awal / Unit (Origin)</th>
                                <th class="text-end">Harga Akhir / Unit (Final)</th>
                                <th class="text-end">Subtotal Harga Awal</th>
                                <th class="text-end">Subtotal Harga Akhir</th>
                            </tr>
                        </thead>
                        <tbody id="tblProdModalBody"></tbody>
                        <tfoot id="tblProdModalFoot" class="table-light fw-bold"></tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <small class="text-muted">
                    <i class="bx bx-info-circle me-1"></i> Rincian produk berasal dari data item E-Katalog (<code>products_json</code>) beserta Ongkos Kirim (<code>shipping_fee</code>).
                </small>
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

    function escapeHTML(str) {
        if (!str || typeof str !== 'string') return str || '';
        return str.replace(/&/g, "&amp;")
                  .replace(/</g, "&lt;")
                  .replace(/>/g, "&gt;")
                  .replace(/"/g, "&quot;")
                  .replace(/'/g, "&#039;");
    }

    $(document).ready(function() {
        dataTable = $('#table-epurchasing').DataTable({
            processing: true,
            serverSide: true,
            searchDelay: 500,
            order: [[12, 'desc']], // Sort by Tanggal Order DESC by default
            ajax: {
                url: '<?= base_url('dashboard/getEpurchasingDatatables') ?>',
                data: function(d) {
                    d.status_filter = $('#filter-status').val();
                    d.satker_filter = $('#filter-satker').val();
                    d.year = $('#filter-year').val() || '<?= date('Y') ?>';
                }
            },
            columns: [
                { data: 'no_referensi', defaultContent: '-' },
                { 
                    data: 'rup_code', 
                    defaultContent: '-',
                    render: function(data) {
                        return `<span class="fw-semibold text-dark">${escapeHTML(String(data || '-'))}</span>`;
                    }
                },
                { data: 'nama_paket', defaultContent: '-' },
                { 
                    data: 'nama_program', 
                    defaultContent: '-',
                    render: function(data) {
                        return `<small class="text-muted d-block">${escapeHTML(String(data || '-'))}</small>`;
                    }
                },
                { 
                    data: 'nama_produk', 
                    defaultContent: '-',
                    render: function(data, type, row) {
                        if (type === 'export' || type === 'filter' || type === 'sort') {
                            return data || '-';
                        }
                        const items = row.products_list || [];
                        const itemCount = items.length || row.count_product || 0;
                        const btnHtml = items.length > 0
                            ? `<div class="mt-1">
                                <button type="button" class="btn btn-xs btn-outline-primary rounded-pill py-0 px-2 btn-view-products" style="font-size: 0.72rem;">
                                    <i class="bx bx-search-alt me-1"></i>Rincian Produk (${itemCount} Item)
                                </button>
                               </div>`
                            : '';
                        return `<div style="max-width: 300px; white-space: normal; font-size: 0.85rem; line-height: 1.4; color: #566a7f;">
                            <div>${escapeHTML(String(data || '-'))}</div>
                            ${btnHtml}
                        </div>`;
                    }
                },
                { 
                    data: 'nama_satker', 
                    defaultContent: '-',
                    render: function(data) { return `<small class="text-muted d-block" style="font-size:0.75rem">${escapeHTML(String(data || '-'))}</small>`; }
                },
                { 
                    data: 'nama_penyedia', 
                    defaultContent: '-',
                    render: function(data) { return `<span class="fw-bold text-primary">${escapeHTML(String(data || '-'))}</span>`; }
                },
                { 
                    data: 'pagu_rup', 
                    defaultContent: 0,
                    render: function(data, type, row) {
                        if (type === 'export' || type === 'sort') return data || 0;
                        if (!data || Number(data) === 0) return '<span class="text-muted">-</span>';
                        let txBadge = '';
                        if (row && row.rup_tx_count && row.rup_tx_count > 1) {
                            txBadge = `<small class="text-muted d-block" style="font-size:0.68rem;" title="Pagu RUP ini digunakan bersama oleh ${row.rup_tx_count} transaksi pesanan"><i class="bx bx-layer me-1 text-primary"></i>${row.rup_tx_count}x transaksi</small>`;
                        }
                        return `<span class="fw-semibold text-dark">${formatIDR(data)}</span>${txBadge}`;
                    }
                },
                { 
                    data: 'total_harga', 
                    defaultContent: 0,
                    render: function(data, type, row) {
                        if (type === 'export' || type === 'sort') return data || row.pagu || 0;
                        const val = data || row.pagu || 0;
                        return `<strong class="text-primary">${formatIDR(val)}</strong>`;
                    }
                },
                {
                    data: 'flag_minikom',
                    defaultContent: 'Non Minikom',
                    render: function(data, type) {
                        const val = String(data || 'Non Minikom');
                        if (type === 'export' || type === 'sort') return val;
                        const isMini = val.toLowerCase().indexOf('non') === -1 && val.toLowerCase().indexOf('mini') !== -1;
                        const cls = isMini ? 'bg-label-warning' : 'bg-label-secondary';
                        return `<span class="badge ${cls}">${escapeHTML(val)}</span>`;
                    }
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
                    data: 'funding_source', 
                    defaultContent: '-',
                    render: function(data, type, row) {
                        const src = data || row.sumber_dana || '-';
                        if (type === 'export' || type === 'sort') return src;
                        if (!src || src === '-') return '<span class="text-muted">-</span>';
                        let cls = 'bg-label-secondary';
                        let s = String(src).toUpperCase();
                        if (s === 'APBD') cls = 'bg-label-primary';
                        else if (s === 'APBN' || s === 'BLUD') cls = 'bg-label-info';
                        return `<span class="badge ${cls}">${escapeHTML(s)}</span>`;
                    }
                },
                { 
                    data: 'status', 
                    defaultContent: '-',
                    render: function(data) {
                        let s = (data || '').toUpperCase().replace(/_/g, ' ');
                        let cls = 'bg-label-secondary';
                        if (s === 'COMPLETED') cls = 'bg-label-success';
                        else if (s === 'ON PROCESS') cls = 'bg-label-info';
                        else if (s === 'PAYMENT OUTSIDE SYSTEM') cls = 'bg-label-primary';
                        else if (s === 'ON NEGOTIATION') cls = 'bg-label-info';
                        else if (s === 'WAITING PPK REVIEW' || s === 'WAITING SELLER CONFIRMATION') cls = 'bg-label-warning';
                        return `<span class="badge ${cls}">${s || '-'}</span>`;
                    }
                }
            ],
            dom: '<"d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2"Bl>rtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="far fa-file-excel mr-1"></i> Export Excel',
                    title: () => 'Data E-Purchasing - Tahun ' + ($('#filter-year').val() || 'Semua'),
                    messageTop: () => `Tanggal Export: ${new Date().toLocaleString('id-ID')}`,
                    className: 'btn btn-outline-success btn-sm me-2',
                    action: function(e, dt, node, config) {
                        runSmartExport.call(this, e, dt, node, config);
                    }
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="far fa-file-pdf mr-1"></i> Export PDF',
                    title: () => 'Data E-Purchasing - Tahun ' + ($('#filter-year').val() || 'Semua'),
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

        // Populate status filter from data
        populateFilters();

        // Filter: status dropdown change
        $('#filter-status').on('change', function() {
            dataTable.ajax.reload();
        });

        // Filter: satker dropdown change
        $('#filter-satker').on('change', function() {
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

        // Delegate click on "Rincian Produk" button inside table
        $('#table-epurchasing tbody').on('click', '.btn-view-products', function() {
            const tr = $(this).closest('tr');
            const rowData = dataTable.row(tr).data();
            if (rowData) {
                openTableProductModal(rowData);
            }
        });
    });

    function openTableProductModal(row) {
        $('#tblProdModalTitle').text(row.nama_paket || 'Rincian Item Produk & Harga');
        $('#tblProdModalSubtitle').text('No. Referensi: ' + (row.no_referensi || '-') + ' | Kode RUP: ' + (row.rup_code || '-'));

        const flagMini = row.flag_minikom || 'Non Minikom';
        const fund = row.funding_source || row.sumber_dana || '-';
        const shipFee = Number(row.shipping_fee || 0);

        $('#tblProdModalMeta').html(`
            <div class="col-md-3">
                <div class="small text-muted text-uppercase fw-bold" style="font-size:0.7rem;">Satker Pelaksana</div>
                <div class="small fw-bold text-dark">${escapeHTML(String(row.nama_satker || '-'))}</div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted text-uppercase fw-bold" style="font-size:0.7rem;">Penyedia E-Katalog</div>
                <div class="small fw-bold text-primary">${escapeHTML(String(row.nama_penyedia || '-'))}</div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted text-uppercase fw-bold" style="font-size:0.7rem;">Pagu RUP Paket</div>
                <div class="small fw-bold text-dark">${Number(row.pagu_rup || 0) > 0 ? formatIDR(row.pagu_rup) : '-'}</div>
                ${row.rup_tx_count > 1 ? `<div class="text-muted" style="font-size:0.68rem;"><i class="bx bx-layer me-1 text-primary"></i>Digunakan oleh ${row.rup_tx_count} transaksi</div>` : ''}
            </div>
            <div class="col-md-3">
                <div class="small text-muted text-uppercase fw-bold" style="font-size:0.7rem;">Metode, Asal Dana &amp; Ongkir</div>
                <div class="d-flex flex-wrap gap-1 mt-1">
                    <span class="badge bg-label-warning">${escapeHTML(String(flagMini))}</span>
                    <span class="badge bg-label-primary">Dana: ${escapeHTML(String(fund))}</span>
                    <span class="badge bg-label-info">Ongkir: ${formatIDR(shipFee)}</span>
                </div>
            </div>
        `);

        const items = row.products_list || [];
        const tbody = $('#tblProdModalBody');
        const tfoot = $('#tblProdModalFoot');

        if (items.length === 0) {
            tbody.html('<tr><td colspan="7" class="text-center py-4 text-muted">Data rincian item produk tidak tersedia.</td></tr>');
            tfoot.html('');
        } else {
            const rowsHtml = items.map((it, idx) => {
                return `
                    <tr>
                        <td class="text-center">${idx + 1}</td>
                        <td>
                            <div class="fw-bold text-dark small">${escapeHTML(String(it.product_name || '-'))}</div>
                            <div class="text-muted" style="font-size:0.72rem;">ID: <code>${escapeHTML(String(it.product_id || '-'))}</code></div>
                        </td>
                        <td class="text-center fw-bold">${Number(it.qty || 0).toLocaleString('id-ID')}</td>
                        <td class="text-end text-muted small">${formatIDR(it.origin_price || 0)}</td>
                        <td class="text-end fw-semibold text-dark small">${formatIDR(it.final_price || 0)}</td>
                        <td class="text-end text-muted small">${formatIDR(it.subtotal_origin || 0)}</td>
                        <td class="text-end fw-bold text-primary small">${formatIDR(it.subtotal_final || 0)}</td>
                    </tr>
                `;
            }).join('');
            tbody.html(rowsHtml);

            tfoot.html(`
                <tr>
                    <td colspan="5" class="text-end text-uppercase small">Subtotal Harga Produk:</td>
                    <td class="text-end text-muted">${formatIDR(row.total_origin || 0)}</td>
                    <td class="text-end text-primary">${formatIDR(row.total_final_produk || 0)}</td>
                </tr>
                <tr>
                    <td colspan="6" class="text-end text-uppercase small">Ongkos Kirim (Shipping Fee):</td>
                    <td class="text-end text-info">${formatIDR(shipFee)}</td>
                </tr>
                <tr style="background: #eef0ff;">
                    <td colspan="6" class="text-end text-uppercase small text-dark">Total Harga Kontrak / Realisasi Paket:</td>
                    <td class="text-end text-primary fs-6">${formatIDR(row.total_harga || row.pagu || 0)}</td>
                </tr>
            `);
        }

        $('#tableProductModal').modal('show');
    }

    const isRestrictedRole = <?= $isRestrictedRole ? 'true' : 'false' ?>;

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

            // Also populate Satker if metadata available and role is not restricted
            if (!isRestrictedRole) {
                const resSatker = await fetch('<?= base_url('dashboard/getFilterOptions/epurchasing') ?>?year=' + year);
                const satkerList = await resSatker.json();
                const satkerSelect = $('#filter-satker');
                satkerSelect.find('option:not(:first)').remove();
                satkerList.forEach(s => satkerSelect.append(new Option(s, s)));
            }
        } catch(e) {
            console.warn('Filter population failed:', e);
        }
    }
</script>
<?= $this->endSection() ?>
