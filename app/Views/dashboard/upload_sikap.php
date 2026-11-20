<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Breadcrumb & Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold py-1 mb-1">
                <span class="text-muted fw-light">Database /</span> Upload Referensi SIKaP
            </h4>
            <p class="text-muted small mb-0">Kelola dan perbarui data referensi penyedia hasil bot scraping SIKaP LKPP</p>
        </div>
        <div>
            <a href="<?= base_url('dashboard/detail-penyedia') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bx bx-arrow-back me-1"></i> Kembali ke Detail Penyedia
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="bx bx-check-circle fs-4 me-2"></i>
                <div><?= session()->getFlashdata('success') ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="bx bx-error-circle fs-4 me-2"></i>
                <div><?= session()->getFlashdata('error') ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Summary & Status Cards -->
    <div class="row g-4 mb-4">
        <!-- Status File SIKaP Aktif -->
        <div class="col-lg-6 col-md-12">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="card-title mb-0 fw-bold">
                            <i class="bx bx-file text-primary me-2 fs-4 align-middle"></i> Status File Referensi
                        </h5>
                        <?php if ($file_exists): ?>
                            <span class="badge bg-label-success">Tersedia di Server</span>
                        <?php else: ?>
                            <span class="badge bg-label-warning">Belum Ada File</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($file_exists): ?>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="p-3 bg-light rounded border">
                                    <span class="text-muted small d-block">TOTAL PENYEDIA</span>
                                    <h3 class="mb-0 fw-bold text-primary"><?= number_format($total_vendors, 0, ',', '.') ?></h3>
                                    <small class="text-muted">Entri unik dalam CSV</small>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-3 bg-light rounded border">
                                    <span class="text-muted small d-block">UKURAN FILE</span>
                                    <h3 class="mb-0 fw-bold text-success"><?= $file_size_formatted ?></h3>
                                    <small class="text-muted">Hasil scraping bot</small>
                                </div>
                            </div>
                        </div>

                        <ul class="list-group list-group-flush mt-3 small">
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Nama File</span>
                                <span class="fw-semibold font-monospace"><?= esc($file_name) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Terakhir Diperbarui</span>
                                <span class="fw-semibold"><?= esc($last_modified) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                <span class="text-muted">Penyedia Tersinkron di DB</span>
                                <span class="fw-bold text-info"><?= number_format($db_sikap_count, 0, ',', '.') ?> data</span>
                            </li>
                        </ul>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bx bx-cloud-upload display-4 text-muted mb-2"></i>
                            <p class="text-muted mb-0">Belum ada file <code>Hasil_Bot_SIKaP.csv</code> yang diunggah.</p>
                            <small class="text-muted">Silakan unggah file hasil scraping SIKaP melalui form di bawah.</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Panduan & Manfaat -->
        <div class="col-lg-6 col-md-12">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title fw-bold mb-3">
                        <i class="bx bx-info-circle text-info me-2 fs-4 align-middle"></i> Petunjuk &amp; Manfaat Referensi
                    </h5>
                    <p class="small text-muted mb-3">
                        Data yang diunggah digunakan sebagai <strong>referensi utama alamat &amp; NPWP</strong> pada modul Detail Penyedia dan profil transaksi LPSE/Katalog.
                    </p>
                    <div class="list-group list-group-flush small">
                        <div class="list-group-item px-0 py-2 border-0 d-flex">
                            <i class="bx bx-check text-success fs-5 me-2 flex-shrink-0"></i>
                            <div><strong>Alamat Riil Tanpa Sensor:</strong> Menimpa alamat yang sebelumnya tersensor bintang (<code>*****</code>) dari API publik.</div>
                        </div>
                        <div class="list-group-item px-0 py-2 border-0 d-flex">
                            <i class="bx bx-check text-success fs-5 me-2 flex-shrink-0"></i>
                            <div><strong>NPWP &amp; Bentuk Usaha Resmi:</strong> Menyediakan data legalitas penyedia resmi dari SIKaP LKPP.</div>
                        </div>
                        <div class="list-group-item px-0 py-2 border-0 d-flex">
                            <i class="bx bx-check text-success fs-5 me-2 flex-shrink-0"></i>
                            <div><strong>Format File Didukung:</strong> File <code>.csv</code> hasil bot SIKaP dengan pemisah titik koma (<code>;</code>) atau koma (<code>,</code>).</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Upload -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="card-title mb-0 fw-bold">
                <i class="bx bx-upload text-primary me-2"></i> Form Upload File CSV SIKaP
            </h5>
        </div>
        <div class="card-body p-4">
            <form action="<?= base_url('dashboard/do-upload-sikap') ?>" method="POST" enctype="multipart/form-data" id="formUploadSikap">
                <?= csrf_field() ?>

                <div class="mb-4">
                    <label for="file_csv" class="form-label fw-bold">Pilih File CSV Hasil Scraping SIKaP <span class="text-danger">*</span></label>
                    <input class="form-control form-control-lg" type="file" id="file_csv" name="file_csv" accept=".csv,text/csv,text/plain" required>
                    <div class="form-text">Contoh file: <code>Hasil_Bot_SIKaP.csv</code>. Maksimal ukuran file: 20MB.</div>
                </div>

                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" id="sync_now" name="sync_now" value="1" checked>
                    <label class="form-check-label fw-semibold" for="sync_now">
                        Langsung jalankan sinkronisasi ke database penyedia &amp; transaksi LPSE setelah upload
                    </label>
                    <small class="d-block text-muted">
                        Jika dicentang, sistem akan langsung memperbarui alamat dan NPWP vendor transaksi yang cocok.
                    </small>
                </div>

                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <button type="submit" class="btn btn-primary btn-lg" id="btnSubmitUpload">
                        <i class="bx bx-cloud-upload me-1"></i> Upload &amp; Proses Referensi
                    </button>
                    <?php if ($file_exists): ?>
                        <button type="submit" name="action" value="reprocess" class="btn btn-outline-info btn-lg" onclick="document.getElementById('file_csv').removeAttribute('required');">
                            <i class="bx bx-refresh me-1"></i> Sinkronkan Ulang dari File Yang Sudah Ada
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Preview Table -->
    <?php if ($file_exists && !empty($preview_rows)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="bx bx-table text-primary me-2"></i> Contoh / Preview Data Referensi SIKaP
                    </h5>
                    <small class="text-muted">Menampilkan 10 entri sampel dari file yang aktif di server</small>
                </div>
                <span class="badge bg-label-primary"><?= count($preview_rows) ?> Baris Ditampilkan</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;">No</th>
                            <th style="width: 180px;">NPWP</th>
                            <th style="min-width: 220px;">Nama Penyedia</th>
                            <th style="width: 160px;">Bentuk Usaha</th>
                            <th style="min-width: 280px;">Alamat</th>
                            <th style="width: 130px;">Kontak</th>
                            <th style="width: 100px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preview_rows as $idx => $row): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td><span class="font-monospace fw-bold text-primary small"><?= esc($row['npwp'] ?? '-') ?></span></td>
                                <td><span class="fw-semibold text-dark"><?= esc($row['nama'] ?? '-') ?></span></td>
                                <td><span class="badge bg-label-secondary small"><?= esc($row['bentuk'] ?? '-') ?></span></td>
                                <td class="small text-wrap"><?= esc($row['alamat'] ?? '-') ?></td>
                                <td class="small">
                                    <?php if (!empty($row['telepon'])): ?>
                                        <div><i class="bx bx-phone small me-1"></i> <?= esc($row['telepon']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($row['email'])): ?>
                                        <div class="text-truncate" style="max-width: 160px;" title="<?= esc($row['email']) ?>">
                                            <i class="bx bx-envelope small me-1"></i> <?= esc($row['email']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (empty($row['telepon']) && empty($row['email'])): ?>-<?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= ($row['status'] ?? '') === 'Baru' ? 'bg-label-warning' : 'bg-label-success' ?>">
                                        <?= esc($row['status'] ?? 'Terdaftar') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('formUploadSikap')?.addEventListener('submit', function(e) {
    const btn = document.getElementById('btnSubmitUpload');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Memproses file & sinkronisasi...';
    }
});
</script>
<?= $this->endSection() ?>
