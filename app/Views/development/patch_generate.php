<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-12">

        <!-- Header -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold mb-0">
                    <i class="bx bx-code-block me-2 text-success"></i><?= $title ?>
                </h4>
                <small class="text-muted">Pilih file yang ingin dimasukkan ke dalam file patch ZIP</small>
            </div>
            <a href="<?= base_url('development/patch') ?>" class="btn btn-label-secondary rounded-pill">
                <i class="bx bx-arrow-back me-1"></i> Kembali ke Patcher
            </a>
        </div>

        <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
            <i class="bx bx-error-circle me-2"></i>
            <?= session()->getFlashdata('error') ?>
        </div>
        <?php endif; ?>

        <form id="generateForm" action="<?= base_url('development/patch/download') ?>" method="POST">
            <?= csrf_field() ?>

            <!-- Filter Bar -->
            <div class="card shadow-sm border-0 rounded-3 mb-4">
                <div class="card-body py-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold mb-1">
                                <i class="bx bx-calendar me-1 text-primary"></i> Tampilkan file dimodifikasi sejak:
                            </label>
                            <input type="date" id="since-filter" name="since" class="form-control"
                                   value="<?= esc($since) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold mb-1">
                                <i class="bx bx-tag me-1 text-primary"></i> Label Patch (nama file ZIP):
                            </label>
                            <div class="input-group">
                                <input type="text" name="patch_label" id="patch-label" class="form-control"
                                       placeholder="contoh: v2_0_hotfix" value="patch_<?= date('Ymd') ?>">
                                <span class="input-group-text text-muted">.zip</span>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="btn-filter" class="btn btn-primary w-100">
                                <i class="bx bx-search me-1"></i> Scan File
                            </button>
                        </div>
                        <div class="col-md-3 text-end">
                            <div class="d-flex gap-2 justify-content-end align-items-center">
                                <span class="text-muted small" id="selected-count">0 file dipilih</span>
                                <button type="submit" id="btn-download" class="btn btn-success rounded-pill px-4" disabled>
                                    <i class="bx bx-download me-1"></i> Generate & Download
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- File List Card -->
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <i class="bx bx-file bx-md text-success me-3"></i>
                        <div>
                            <h5 class="card-title mb-0 fw-bold">Daftar File yang Dimodifikasi</h5>
                            <small class="text-muted">
                                <?php if (!empty($modifiedFiles)): ?>
                                    Ditemukan <strong><?= count($modifiedFiles) ?></strong> file sejak <strong><?= $since ?></strong>
                                <?php else: ?>
                                    Tidak ada file yang dimodifikasi dalam rentang waktu tersebut
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                    <?php if (!empty($modifiedFiles)): ?>
                    <div class="d-flex gap-2">
                        <button type="button" id="btn-select-all" class="btn btn-sm btn-outline-primary rounded-pill">
                            <i class="bx bx-check-square me-1"></i> Pilih Semua
                        </button>
                        <button type="button" id="btn-deselect-all" class="btn btn-sm btn-outline-secondary rounded-pill">
                            <i class="bx bx-square me-1"></i> Batal Semua
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="card-body p-0">
                    <?php if (empty($modifiedFiles)): ?>
                        <div class="text-center py-5">
                            <i class="bx bx-folder-open bx-lg text-muted mb-3 d-block"></i>
                            <p class="text-muted mb-1 fw-semibold">Tidak ada file ditemukan</p>
                            <p class="text-muted small mb-3">Coba ubah tanggal filter ke periode yang lebih lama</p>
                            <a href="<?= base_url('development/patch/generate?since=' . date('Y-m-d', strtotime('-30 days'))) ?>"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bx bx-calendar me-1"></i> Scan 30 hari terakhir
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Group by directory -->
                        <?php
                            $grouped = [];
                            foreach ($modifiedFiles as $f) {
                                $parts = explode('/', $f['path']);
                                $topDir = $parts[0] ?? 'root';
                                $grouped[$topDir][] = $f;
                            }
                        ?>

                        <div id="file-list-container">
                        <?php foreach ($grouped as $dirName => $files): ?>
                            <div class="file-group">
                                <!-- Group Header -->
                                <div class="group-header d-flex align-items-center px-4 py-2 bg-light border-bottom border-top">
                                    <div class="form-check me-2 mb-0">
                                        <input class="form-check-input group-check" type="checkbox"
                                               id="group-<?= esc($dirName) ?>" data-group="<?= esc($dirName) ?>" checked>
                                    </div>
                                    <label class="form-check-label fw-bold mb-0 text-uppercase text-muted small"
                                           for="group-<?= esc($dirName) ?>">
                                        <i class="bx bx-folder-open me-1 text-warning"></i>
                                        <?= esc($dirName) ?>/
                                        <span class="badge bg-label-secondary ms-1"><?= count($files) ?> file</span>
                                    </label>
                                </div>

                                <!-- Files in group -->
                                <?php foreach ($files as $f): ?>
                                <div class="file-row d-flex align-items-center px-4 py-2 border-bottom file-item"
                                     data-group="<?= esc($dirName) ?>">
                                    <div class="form-check me-3 mb-0">
                                        <input class="form-check-input file-check" type="checkbox"
                                               name="files[]" value="<?= esc($f['path']) ?>"
                                               id="file-<?= md5($f['path']) ?>" checked>
                                    </div>
                                    <label class="form-check-label flex-grow-1 mb-0"
                                           for="file-<?= md5($f['path']) ?>" style="cursor:pointer;">
                                        <div class="d-flex align-items-center">
                                            <?php
                                                $ext = pathinfo($f['path'], PATHINFO_EXTENSION);
                                                $iconMap = [
                                                    'php'  => ['bx-code-alt', 'text-primary'],
                                                    'js'   => ['bx-code', 'text-warning'],
                                                    'css'  => ['bxl-css3', 'text-info'],
                                                    'json' => ['bx-code-block', 'text-success'],
                                                    'html' => ['bx-code-curly', 'text-danger'],
                                                    'sql'  => ['bx-data', 'text-purple'],
                                                    'env'  => ['bx-cog', 'text-secondary'],
                                                ];
                                                $iconClass = $iconMap[$ext] ?? ['bx-file', 'text-muted'];
                                            ?>
                                            <i class="bx <?= $iconClass[0] ?> <?= $iconClass[1] ?> me-2 fs-5"></i>
                                            <div>
                                                <code class="text-dark fw-semibold small"><?= esc($f['path']) ?></code>
                                                <div class="text-muted" style="font-size:0.75rem;">
                                                    <?= formatFileSize($f['size']) ?> &bull;
                                                    Dimodifikasi: <span class="text-primary"><?= $f['modified'] ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                    <div class="ms-auto ps-3 text-end">
                                        <span class="badge bg-label-<?= $ext === 'php' ? 'primary' : ($ext === 'js' ? 'warning' : ($ext === 'css' ? 'info' : 'secondary')) ?> text-uppercase">
                                            <?= esc($ext ?: 'file') ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                        </div>

                        <!-- Bottom summary bar -->
                        <div class="px-4 py-3 bg-light d-flex align-items-center justify-content-between border-top">
                            <span class="text-muted small">
                                Total: <strong><?= count($modifiedFiles) ?></strong> file &bull;
                                Dipilih: <strong id="selected-count-bottom">0</strong> file
                            </span>
                            <button type="submit" id="btn-download-bottom" class="btn btn-success rounded-pill px-4" disabled>
                                <i class="bx bx-download me-1"></i> Generate & Download ZIP
                            </button>
                        </div>

                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?php
function formatFileSize(int $bytes): string {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 2) . ' MB';
}
?>

<?= $this->section('scripts') ?>
<style>
.file-row {
    transition: background 0.15s ease;
}
.file-row:hover {
    background: rgba(105, 108, 255, 0.04);
}
.file-row:has(.file-check:checked) {
    background: rgba(105, 108, 255, 0.06);
}
.group-header {
    position: sticky;
    top: 0;
    z-index: 1;
}
.file-group:last-child .file-row:last-child {
    border-bottom: none !important;
}
code.text-dark {
    font-size: 0.82rem;
}
</style>

<script>
$(document).ready(function () {

    // --- Count selected & toggle download button ---
    function updateCount() {
        const total = $('.file-check:checked').length;
        $('#selected-count').text(total + ' file dipilih');
        $('#selected-count-bottom').text(total);
        const disabled = total === 0;
        $('#btn-download, #btn-download-bottom').prop('disabled', disabled);
    }

    // Init count
    updateCount();

    // File checkbox change
    $(document).on('change', '.file-check', function () {
        updateCount();

        // Sync group checkbox
        const group = $(this).closest('.file-item').data('group');
        const total = $(`.file-item[data-group="${group}"] .file-check`).length;
        const checked = $(`.file-item[data-group="${group}"] .file-check:checked`).length;
        $(`#group-${group}`).prop('indeterminate', checked > 0 && checked < total);
        $(`#group-${group}`).prop('checked', checked === total);
    });

    // Group checkbox: toggle all files in group
    $(document).on('change', '.group-check', function () {
        const group = $(this).data('group');
        const isChecked = $(this).is(':checked');
        $(`.file-item[data-group="${group}"] .file-check`).prop('checked', isChecked);
        updateCount();
    });

    // Select All
    $('#btn-select-all').on('click', function () {
        $('.file-check, .group-check').prop('checked', true).prop('indeterminate', false);
        updateCount();
    });

    // Deselect All
    $('#btn-deselect-all').on('click', function () {
        $('.file-check, .group-check').prop('checked', false).prop('indeterminate', false);
        updateCount();
    });

    // Filter / Scan button
    $('#btn-filter').on('click', function () {
        const since = $('#since-filter').val();
        window.location.href = '<?= base_url('development/patch/generate') ?>?since=' + since;
    });

    // Enter key on date input
    $('#since-filter').on('keydown', function (e) {
        if (e.key === 'Enter') $('#btn-filter').click();
    });

    // Submit confirmation
    $('#generateForm').on('submit', function (e) {
        e.preventDefault();
        const form = this;
        const count = $('.file-check:checked').length;
        const label = $('#patch-label').val() || 'patch';

        Swal.fire({
            title: 'Generate Patch File?',
            html: `Akan mengemas <strong>${count} file</strong> menjadi:<br><code>${label}_<?= date('Ymd_His') ?>.zip</code>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="bx bx-download me-1"></i> Ya, Download',
            cancelButtonText: 'Batal',
            buttonsStyling: false,
            customClass: {
                confirmButton: 'btn btn-success me-3',
                cancelButton: 'btn btn-label-secondary',
                popup: 'rounded-3 shadow-lg'
            }
        }).then(result => {
            if (result.isConfirmed) {
                $('#btn-download, #btn-download-bottom').prop('disabled', true)
                    .html('<span class="spinner-border spinner-border-sm me-1"></span> Memproses...');
                form.submit();
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
