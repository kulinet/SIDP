<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h4 class="fw-bold mb-0">
                <i class="bx bx-package me-2 text-primary"></i><?= $title ?>
            </h4>
            <a href="<?= base_url('development/patch/generate') ?>" class="btn btn-success rounded-pill px-4 shadow-sm">
                <i class="bx bx-code-block me-1"></i> Generate Patch Baru
            </a>
        </div>

        <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible d-flex align-items-center mb-4" role="alert">
            <i class="bx bx-check-circle me-2 flex-shrink-0"></i>
            <div><?= session()->getFlashdata('success') ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible d-flex align-items-center mb-4" role="alert">
            <i class="bx bx-error-circle me-2 flex-shrink-0"></i>
            <div><?= session()->getFlashdata('error') ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Upload Card -->
        <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-header bg-white py-3 d-flex align-items-center">
                <i class="bx bx-cloud-upload bx-md text-primary me-3"></i>
                <div>
                    <h5 class="card-title mb-0 fw-bold">Upload Patch File</h5>
                    <small class="text-muted">Upload file ZIP untuk memperbarui sistem aplikasi</small>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-warning d-flex align-items-start mb-4" role="alert">
                    <i class="bx bx-error-circle bx-sm me-2 mt-1"></i>
                    <div>
                        <strong>Perhatian!</strong> Proses patching akan menimpa file yang sudah ada. Sistem akan otomatis membuat backup file lama sebelum ditimpa. Pastikan file ZIP Anda memiliki struktur direktori yang sesuai dengan aplikasi (misal: <code>app/Controllers/...</code>, <code>app/Views/...</code>).
                    </div>
                </div>

                <form id="patchForm" action="<?= base_url('development/patch/upload') ?>" method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div id="drop-zone" class="drop-zone mb-3">
                        <div class="drop-zone-content">
                            <i class="bx bx-cloud-upload bx-lg text-primary mb-2"></i>
                            <p class="mb-1 fw-semibold">Drag & Drop file ZIP di sini</p>
                            <p class="text-muted small mb-3">atau klik untuk memilih file</p>
                            <label class="btn btn-outline-primary btn-sm">
                                <i class="bx bx-folder-open me-1"></i> Pilih File
                                <input type="file" name="patch_file" id="patch_file" accept=".zip" hidden>
                            </label>
                        </div>
                        <div id="file-info" class="file-info d-none">
                            <i class="bx bx-file bx-md text-success me-2"></i>
                            <div>
                                <p class="mb-0 fw-semibold" id="file-name">-</p>
                                <small class="text-muted" id="file-size">-</small>
                            </div>
                            <button type="button" id="remove-file" class="btn btn-sm btn-icon btn-outline-danger ms-auto">
                                <i class="bx bx-x"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" id="btn-upload" class="btn btn-primary px-4 rounded-pill shadow-sm" disabled>
                            <i class="bx bx-upload me-1"></i> Terapkan Patch
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Patch History Card -->
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3 d-flex align-items-center">
                <i class="bx bx-history bx-md text-info me-3"></i>
                <div>
                    <h5 class="card-title mb-0 fw-bold">Riwayat Patching</h5>
                    <small class="text-muted">Daftar patch yang pernah diterapkan beserta opsi rollback</small>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($history)): ?>
                    <div class="text-center py-5">
                        <i class="bx bx-package bx-lg text-muted mb-3 d-block"></i>
                        <p class="text-muted mb-0">Belum ada riwayat patching.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th class="py-3 px-3" style="width: 50px;">#</th>
                                    <th class="py-3" style="width: 100px;">Versi</th>
                                    <th class="py-3">Nama Patch</th>
                                    <th class="py-3 text-center" style="width: 80px;">File</th>
                                    <th class="py-3">Diterapkan Oleh</th>
                                    <th class="py-3">Waktu</th>
                                    <th class="py-3 text-center" style="width: 200px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $idx => $h): ?>
                                <tr>
                                    <td class="px-3 text-muted"><?= $idx + 1 ?></td>
                                    <td><span class="badge bg-label-primary fw-bold"><?= esc($h['app_version'] ?? '-') ?></span></td>
                                    <td>
                                        <?php if (str_starts_with($h['patch_name'], '[ROLLBACK]')): ?>
                                            <span class="badge bg-label-warning me-1"><i class="bx bx-undo"></i> Rollback</span>
                                            <span class="fw-semibold"><?= esc(str_replace('[ROLLBACK] ', '', $h['patch_name'])) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-label-success me-1"><i class="bx bx-check-circle"></i> Patch</span>
                                            <span class="fw-semibold"><?= esc($h['patch_name']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-label-primary rounded-pill"><?= $h['file_count'] ?? '-' ?></span>
                                    </td>
                                    <td><?= esc($h['applied_by'] ?? '-') ?></td>
                                    <td>
                                        <small><?= $h['applied_at'] ?? '-' ?></small>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($h['backup_file'])): ?>
                                            <a href="<?= base_url('development/patch/rollback/' . $h['backup_file']) ?>" 
                                               class="btn btn-sm btn-outline-warning me-1 btn-rollback"
                                               data-name="<?= esc($h['patch_name']) ?>">
                                                <i class="bx bx-undo me-1"></i>Rollback
                                            </a>
                                            <a href="<?= base_url('development/patch/delete-backup/' . $h['backup_file']) ?>" 
                                               class="btn btn-sm btn-outline-danger btn-delete-backup"
                                               data-name="<?= esc($h['backup_file']) ?>">
                                                <i class="bx bx-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<style>
    .drop-zone {
        border: 2px dashed #c4cdd5;
        border-radius: 12px;
        padding: 40px 20px;
        text-align: center;
        background: #f8f9fa;
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
    }
    .drop-zone.drag-over {
        border-color: #696cff;
        background: rgba(105, 108, 255, 0.05);
        box-shadow: 0 0 0 4px rgba(105, 108, 255, 0.1);
    }
    .drop-zone .file-info {
        display: flex;
        align-items: center;
        text-align: left;
        padding: 16px 20px;
        background: #ffffff;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }
    .table thead th {
        vertical-align: middle;
        border-top: none;
        font-size: 0.85rem;
    }
</style>

<script>
$(document).ready(function() {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('patch_file');
    const fileInfo = document.getElementById('file-info');
    const dropContent = document.querySelector('.drop-zone-content');
    const fileNameEl = document.getElementById('file-name');
    const fileSizeEl = document.getElementById('file-size');
    const btnUpload = document.getElementById('btn-upload');
    const removeBtn = document.getElementById('remove-file');

    // Drag & Drop
    ['dragenter', 'dragover'].forEach(event => {
        dropZone.addEventListener(event, e => {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });
    });
    ['dragleave', 'drop'].forEach(event => {
        dropZone.addEventListener(event, e => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
        });
    });

    dropZone.addEventListener('drop', function(e) {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            showFileInfo(files[0]);
        }
    });

    dropZone.addEventListener('click', function(e) {
        if (e.target.closest('#remove-file') || e.target.closest('label')) return;
        fileInput.click();
    });

    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            showFileInfo(this.files[0]);
        }
    });

    removeBtn.addEventListener('click', function() {
        fileInput.value = '';
        fileInfo.classList.add('d-none');
        dropContent.classList.remove('d-none');
        btnUpload.disabled = true;
    });

    function showFileInfo(file) {
        if (!file.name.endsWith('.zip')) {
            Swal.fire({
                icon: 'error',
                title: 'Format Tidak Valid',
                text: 'Hanya file dengan format .zip yang diterima.',
                buttonsStyling: false,
                customClass: { confirmButton: 'btn btn-primary' }
            });
            fileInput.value = '';
            return;
        }
        fileNameEl.textContent = file.name;
        fileSizeEl.textContent = formatSize(file.size);
        dropContent.classList.add('d-none');
        fileInfo.classList.remove('d-none');
        btnUpload.disabled = false;
    }

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(2) + ' MB';
    }

    // Form submit confirmation
    $('#patchForm').on('submit', function(e) {
        e.preventDefault();
        const form = this;
        Swal.fire({
            title: 'Terapkan Patch?',
            html: 'Proses ini akan menimpa file yang sudah ada.<br><strong>Backup otomatis akan dibuat sebelum proses dimulai.</strong>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<i class="bx bx-check me-1"></i> Ya, Terapkan',
            cancelButtonText: 'Batal',
            buttonsStyling: false,
            customClass: {
                confirmButton: 'btn btn-primary me-3',
                cancelButton: 'btn btn-label-secondary',
                popup: 'rounded-3 shadow-lg'
            }
        }).then(result => {
            if (result.isConfirmed) {
                btnUpload.disabled = true;
                btnUpload.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';
                form.submit();
            }
        });
    });

    // Rollback confirmation
    $(document).on('click', '.btn-rollback', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        const name = $(this).data('name');
        Swal.fire({
            title: 'Rollback Patch?',
            html: `Anda akan mengembalikan file ke kondisi sebelum patch <strong>"${name}"</strong> diterapkan.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="bx bx-undo me-1"></i> Ya, Rollback',
            cancelButtonText: 'Batal',
            buttonsStyling: false,
            customClass: {
                confirmButton: 'btn btn-warning me-3',
                cancelButton: 'btn btn-label-secondary',
                popup: 'rounded-3 shadow-lg'
            }
        }).then(result => {
            if (result.isConfirmed) window.location.href = url;
        });
    });

    // Delete backup confirmation
    $(document).on('click', '.btn-delete-backup', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        const name = $(this).data('name');
        Swal.fire({
            title: 'Hapus Backup?',
            html: `File backup <strong>"${name}"</strong> akan dihapus permanen. Anda tidak bisa melakukan rollback setelah ini.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<i class="bx bx-trash me-1"></i> Ya, Hapus',
            cancelButtonText: 'Batal',
            buttonsStyling: false,
            customClass: {
                confirmButton: 'btn btn-danger me-3',
                cancelButton: 'btn btn-label-secondary',
                popup: 'rounded-3 shadow-lg'
            }
        }).then(result => {
            if (result.isConfirmed) window.location.href = url;
        });
    });
});
</script>
<?= $this->endSection() ?>
