<?= $this->extend('layout/main') ?><?= $this->section('content') ?>

<!-- DataTables CSS CDN -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<style>
    .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 0 !important; }
    div.dataTables_wrapper div.dataTables_info { padding-top: 0.5rem; }
    .dt-buttons .btn { margin-right: 5px; }
    
    /* Table Wrapping & Sizing */
    #data-table { width: 100% !important; border-collapse: collapse !important; }
    #data-table th, #data-table td { white-space: normal !important; vertical-align: top !important; }
    .col-rup { width: 100px !important; }
    .col-paket { min-width: 250px !important; }
    .col-satker { min-width: 200px !important; }
    .col-pagu { width: 130px !important; }
    .col-uraian { min-width: 300px !important; }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="fw-bold py-3 mb-0">
            <span class="text-muted fw-light">Data RUP /</span> <?= ucfirst($type) ?>
        </h4>
        <?php if(in_array(session()->get('role'), ['admin', 'operator'])): ?>
        <a href="<?= base_url('dashboard/clear-cache') ?>" class="btn btn-outline-danger btn-sm" data-confirm="Apakah Anda yakin ingin menghapus seluruh cache data? Hal ini akan memaksa sistem menarik ulang data terbaru dari API.">
            <i class="bx bx-trash me-1"></i> Clear Cache
        </a>
        <?php endif; ?>
    </div>

    <div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-end">
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="mb-0">
                    <label class="form-label text-uppercase fw-bold small text-muted">Pencarian</label>
                    <input type="text" id="custom-search" placeholder="Cari Paket..." class="form-control">
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="mb-0">
                    <label class="form-label text-uppercase fw-bold small text-muted">Filter OPD</label>
                    <select id="filter-opd" class="form-select">
                        <option value="all">Semua OPD</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0" <?= $type == 'swakelola' ? 'style="display:none"' : '' ?>>
                <div class="mb-0">
                    <label class="form-label text-uppercase fw-bold small text-muted">Filter Jenis</label>
                    <select id="filter-jenis" class="form-select">
                        <option value="all">Semua Jenis</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-0">
                    <label class="form-label text-uppercase fw-bold small text-muted">Filter <?= $type == 'penyedia' ? 'Metode' : 'Tipe' ?></label>
                    <select id="filter-metode" class="form-select">
                        <option value="all">Semua <?= $type == 'penyedia' ? 'Metode' : 'Tipe' ?></option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table id="data-table" class="table table-hover border-top">
                <thead>
                    <tr>
                        <th class="col-rup">Kode RUP</th>
                        <th class="col-paket">Nama Paket</th>
                        <th class="col-satker">Satker</th>
                        <th class="col-pagu">Pagu</th>
                        <?php if ($type != 'swakelola'): ?><th>Jenis</th><?php endif; ?>
                        <th><?= $type == 'penyedia' ? 'Metode' : 'Tipe' ?></th>
                        
                        <th>Volume</th>
                        <th class="col-uraian">Uraian</th>
                        <th>Mulai</th>
                        <th>Selesai</th>
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
    const dataType = '<?= $type ?>';
    const userSatker = '<?= session()->get('kd_satker') ?>';
    const userRole = '<?= session()->get('role') ?>';
    let dataTable;

    $(document).ready(function() {
        populateFilters();

        dataTable = $('#data-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: `<?= base_url('dashboard/getDatatables') ?>/${dataType}`,
                data: function(d) {
                    d.opd = $('#filter-opd').val();
                    d.jenis = $('#filter-jenis').val();
                    d.metode = $('#filter-metode').val();
                }
            },
            columns: [
                { data: 'kd_rup' },
                { data: 'nama_paket' },
                { 
                    data: 'nama_satker',
                    render: function(data) { return `<small class="text-muted d-block">${data || '-'}</small>`; }
                },
                { 
                    data: 'pagu',
                    render: function(data) { return '<strong>' + formatIDR(data) + '</strong>'; }
                },
                <?php if ($type != 'swakelola'): ?>
                { 
                    data: 'jenis_pengadaan',
                    defaultContent: '-'
                },
                <?php endif; ?>
                { 
                    data: dataType == 'penyedia' ? 'metode_pengadaan' : 'tipe_swakelola',
                    render: function(data) {
                        if (dataType === 'swakelola' && data) {
                            return swakelolaTipeMapping[data] || data;
                        }
                        return data || '-';
                    },
                    defaultContent: '-'
                },
                { data: 'volume_pekerjaan', defaultContent: '-' },
                { 
                    data: 'urarian_pekerjaan', 
                    render: function(data) { return data || '-'; }
                },
                { 
                    data: 'tgl_awal_pemilihan', 
                    render: function(data) { return data || '-'; }
                },
                { 
                    data: 'tgl_akhir_pemilihan', 
                    render: function(data) { return data || '-'; }
                },
                { 
                    data: 'status_umumkan_rup',
                    render: function(data) {
                        const status = data || 'Terumumkan';
                        return `<span class="badge bg-label-success">${status}</span>`;
                    }
                }
            ],
            dom: '<"d-flex justify-content-between align-items-center mb-3"Bl>rtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="far fa-file-excel mr-1"></i> Export Excel',
                    title: `Data RUP ${dataType == 'penyedia' ? 'Penyedia' : 'Swakelola'}`,
                    className: 'btn btn-outline-success btn-sm me-2',
                    exportOptions: {
                        columns: dataType === 'swakelola' ? [0, 1, 2, 3, 4, 5, 6, 7, 8, 9] : [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
                    },
                    action: function(e, dt, node, config) {
                        runSmartExport.call(this, e, dt, node, config);
                    }
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="far fa-file-pdf mr-1"></i> Export PDF',
                    title: `Data RUP ${dataType == 'penyedia' ? 'Penyedia' : 'Swakelola'}`,
                    orientation: 'landscape',
                    pageSize: 'A3', // Change to A3 for more columns
                    className: 'btn btn-outline-danger btn-sm',
                    exportOptions: {
                        columns: dataType === 'swakelola' ? [0, 1, 2, 3, 4, 5, 6, 7, 8, 9] : [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
                    },
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

        $('#filter-opd, #filter-jenis, #filter-metode').on('change', function() {
            dataTable.ajax.reload();
        });
    });

    function runSmartExport(e, dt, node, config) {
        const self = this;
        const button = $(node);
        const originalText = button.html();
        const settings = dt.settings()[0];
        const oldStart = settings._iDisplayStart;
        const oldLength = settings._iDisplayLength;

        // Visual feedback
        button.addClass('disabled').html('<span class="spinner-border spinner-border-sm mr-1"></span> Mengolah...');

        // Step 1: Force all records
        dt.one('preXhr', function(e, s, data) {
            data.start = 0;
            data.length = -1;
        });

        // Step 2: Once records are loaded, trigger original DT export action
        dt.one('draw', function() {
            setTimeout(function() {
                try {
                    // Determine action name with fallbacks
                    let actionName = (config && config.extend) ? config.extend : null;
                    
                    if (!actionName) {
                        // Fallback: Infer from button class or text
                        if (button.hasClass('btn-outline-success') || button.html().toLowerCase().includes('excel')) {
                            actionName = 'excelHtml5';
                        } else if (button.hasClass('btn-outline-danger') || button.html().toLowerCase().includes('pdf')) {
                            actionName = 'pdfHtml5';
                        }
                    }

                    if (!actionName) throw new Error('Could not determine export type');

                    // Direct registry lookup with fallback
                    let btnAction = $.fn.dataTable.ext.buttons[actionName];
                    if (!btnAction && actionName && actionName.indexOf('Html5') !== -1) {
                        btnAction = $.fn.dataTable.ext.buttons[actionName.replace('Html5', '')];
                    }

                    if (btnAction && typeof btnAction.action === 'function') {
                        btnAction.action.call(self, e, dt, node, config);
                    } else {
                        throw new Error('DataTables action not found for ' + actionName);
                    }
                } catch (err) {
                    console.error('Export Error:', err);
                    showAlert('Export Error', 'Gagal mengekspor data: ' + err.message, 'error');
                }

                // Step 3: Restore previous state
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

    const swakelolaTipeMapping = {
        'Tipe I': 'Swakelola Tipe I - Direncanakan, dilaksanakan, dan diawasi oleh K/L/PD sendiri',
        'Tipe II': 'Swakelola Tipe II - Direncanakan dan diawasi oleh K/L/PD penanggung jawab, dilaksanakan oleh K/L/PD lain',
        'Tipe III': 'Swakelola Tipe III - Direncanakan dan diawasi oleh K/L/PD penanggung jawab, dilaksanakan oleh Ormas',
        'Tipe IV': 'Swakelola Tipe IV - Direncanakan dan diawasi oleh K/L/PD penanggung jawab/Pokmas, dilaksanakan oleh Pokmas',
        'Tipe 1': 'Swakelola Tipe I - Direncanakan, dilaksanakan, dan diawasi oleh K/L/PD sendiri',
        'Tipe 2': 'Swakelola Tipe II - Direncanakan dan diawasi oleh K/L/PD penanggung jawab, dilaksanakan oleh K/L/PD lain',
        'Tipe 3': 'Swakelola Tipe III - Direncanakan dan diawasi oleh K/L/PD penanggung jawab, dilaksanakan oleh Ormas',
        'Tipe 4': 'Swakelola Tipe IV - Direncanakan dan diawasi oleh K/L/PD penanggung jawab/Pokmas, dilaksanakan oleh Pokmas',
        '1': 'Swakelola Tipe I - Direncanakan, dilaksanakan, dan diawasi oleh K/L/PD sendiri',
        '2': 'Swakelola Tipe II - Direncanakan dan diawasi oleh K/L/PD penanggung jawab, dilaksanakan oleh K/L/PD lain',
        '3': 'Swakelola Tipe III - Direncanakan dan diawasi oleh K/L/PD penanggung jawab, dilaksanakan oleh Ormas',
        '4': 'Swakelola Tipe IV - Direncanakan dan diawasi oleh K/L/PD penanggung jawab/Pokmas, dilaksanakan oleh Pokmas'
    };

    async function populateFilters() {
        const res = await fetch(`<?= base_url('dashboard/getData') ?>/${dataType}`);
        const data = await res.json();
        
        const opdSelect = $('#filter-opd');
        const jenisSelect = $('#filter-jenis');
        const metodeSelect = $('#filter-metode');

        const opds = [...new Set(data.map(i => i.nama_satker))].filter(Boolean).sort();
        const jenisList = [...new Set(data.map(i => i.jenis_pengadaan))].filter(Boolean).sort();
        const metodeList = [...new Set(data.map(i => dataType == 'penyedia' ? i.metode_pengadaan : i.tipe_swakelola))].filter(Boolean).sort();
        
        opds.forEach(o => opdSelect.append(new Option(o, o)));
        jenisList.forEach(j => jenisSelect.append(new Option(j, j)));
        metodeList.forEach(m => {
            const label = (dataType === 'swakelola' && swakelolaTipeMapping[m]) ? swakelolaTipeMapping[m] : m;
            metodeSelect.append(new Option(label, m));
        });

        // Only lock filter for 'satker' role
        if (userRole === 'satker' && userSatker) {
            const userOpd = data.find(i => i.kd_satker == userSatker)?.nama_satker;
            if (userOpd) {
                opdSelect.val(userOpd).prop('disabled', true);
            }
        } else {
            opdSelect.prop('disabled', false);
        }
    }

    function formatIDR(v) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v);
    }
</script>
<?= $this->endSection() ?>
