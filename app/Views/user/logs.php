<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h4 class="fw-bold mb-0">
                <i class="bx bx-shield-quarter me-2 text-primary"></i><?= $title ?>
            </h4>
            <?php if(session()->get('role') === 'admin'): ?>
                <form id="clearLogsForm" action="<?= base_url('user/logs/clear') ?>" method="POST">
                    <?= csrf_field() ?>
                    <button type="button" class="btn btn-danger btn-sm rounded-pill" onclick="confirmClear()">
                        <i class="bx bx-trash me-1"></i> Bersihkan Log
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                <i class="bx bx-check-circle me-2"></i>
                <?= session()->getFlashdata('success') ?>
            </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                <i class="bx bx-error-circle me-2"></i>
                <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 fw-bold">Daftar Aktivitas Pengguna</h5>
                <small class="text-muted">History dari seluruh aktivitas mutating (tambah/ubah/hapus) data serta akses sistem.</small>
            </div>
            <div class="card-body">
                <div class="table-responsive text-nowrap">
                    <table class="table table-hover w-100" id="logsTable">
                        <thead class="bg-light">
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Action</th>
                                <th>IP Address</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#logsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= base_url('user/logs/getDatatables') ?>',
            type: 'GET'
        },
        columns: [
            { data: 'id', render: function(data, type, row, meta) {
                return meta.row + meta.settings._iDisplayStart + 1;
            }},
            { data: 'username', render: function(data, type, row) {
                let badge = row.role === 'admin' ? 'bg-label-danger' : (row.role === 'operator' ? 'bg-label-warning' : 'bg-label-primary');
                return `<span class="fw-semibold text-dark">${data}</span>`;
            }},
            { data: 'role', render: function(data) {
                let badge = data === 'admin' ? 'bg-label-danger' : (data === 'operator' ? 'bg-label-warning' : 'bg-label-primary');
                return `<span class="badge ${badge} text-uppercase">${data || 'GUEST'}</span>`;
            }},
            { data: 'action', render: function(data) {
                let textClass = '';
                if(data.includes('DELETE')) textClass = 'text-danger fw-semibold';
                else if(data.includes('PUT')) textClass = 'text-warning fw-semibold';
                else if(data.includes('POST')) textClass = 'text-success fw-semibold';
                else if(data.includes('failed')) textClass = 'text-danger fw-bold';
                
                return `<span class="${textClass}">${data}</span>`;
            }},
            { data: 'ip_address', render: function(data, type, row) {
                return `<code>${data}</code><br><small class="text-muted">${row.parsed_agent}</small>`;
            }},
            { data: 'created_at' }
        ],
        order: [[5, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json',
        }
    });
});

function confirmClear() {
    Swal.fire({
        title: 'Bersihkan Semua Log?',
        text: "Seluruh riwayat aktivitas akan dihapus dari database. Tindakan ini tidak bisa dibatalkan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#8592a3',
        confirmButtonText: 'Ya, Bersihkan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('clearLogsForm').submit();
        }
    });
}
</script>
<?= $this->endSection() ?>
