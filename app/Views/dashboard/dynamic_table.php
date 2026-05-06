<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<!-- DataTables CSS CDN -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<?php if(empty($columns)): ?>
    <div class="alert alert-warning">
        No columns configured for this menu. Please go to <a href="<?= base_url('development/edit/'.$menu['id']) ?>">Development</a> to configure column mapping.
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-4">
                <div class="mb-0">
                    <label class="form-label text-uppercase fw-bold small text-muted">Pencarian</label>
                    <input type="text" id="custom-search" placeholder="Cari data..." class="form-control">
                </div>
            </div>
            <div class="col-md-8 text-md-end mt-3 mt-md-0">
                <span class="badge bg-label-secondary">Source: <?= $menu['api_url'] ?></span>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive text-nowrap">
            <table id="data-table" class="table table-hover">
                <thead>
                    <tr>
                        <?php foreach($columns as $col): ?>
                            <th><?= $col['label'] ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
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

    $(document).ready(function() {
        dataTable = $('#data-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: `<?= base_url('dynamic/getDatatables/'.$menu['slug']) ?>`,
            },
            columns: [
                <?php foreach($columns as $col): ?>
                { 
                    data: '<?= $col['field_key'] ?>',
                    render: function(data, type, row) {
                        const formatter = '<?= $col['formatter'] ?>';
                        if (!data) return '-';
                        
                        if (formatter === 'currency') {
                            return '<strong>' + formatIDR(data) + '</strong>';
                        }
                        if (formatter === 'badge') {
                            return `<span class="badge bg-label-info">${data}</span>`;
                        }
                        return data;
                    }
                },
                <?php endforeach; ?>
            ],
            dom: '<"d-flex justify-content-between align-items-center mb-3"Bl>rtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="far fa-file-excel mr-1"></i> Export Excel',
                    title: `Data <?= $menu['name'] ?>`,
                    className: 'btn btn-outline-success btn-sm me-2',
                    action: function(e, dt, node, config) {
                        runSmartExport.call(this, e, dt, node, config);
                    }
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="far fa-file-pdf mr-1"></i> Export PDF',
                    title: `Data <?= $menu['name'] ?>`,
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
    });

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
                        if (button.hasClass('btn-outline-success') || button.html().toLowerCase().includes('excel')) {
                            actionName = 'excelHtml5';
                        } else if (button.hasClass('btn-outline-danger') || button.html().toLowerCase().includes('pdf')) {
                            actionName = 'pdfHtml5';
                        }
                    }

                    let btnAction = $.fn.dataTable.ext.buttons[actionName];
                    if (!btnAction && actionName && actionName.indexOf('Html5') !== -1) {
                        btnAction = $.fn.dataTable.ext.buttons[actionName.replace('Html5', '')];
                    }

                    if (btnAction && typeof btnAction.action === 'function') {
                        btnAction.action.call(self, e, dt, node, config);
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

    function formatIDR(v) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v);
    }
</script>
<?= $this->endSection() ?>
