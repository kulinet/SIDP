<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?>

<?= $this->section('content') ?>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Settings /</span> Sinkronisasi Data</h4>

    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <h5 class="card-header">Kontrol Sinkronisasi RUP</h5>
                <div class="card-body">
                    <div class="alert alert-info mb-4" role="alert">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="alert-heading fw-bold mb-1">Informasi Penggunaan</h6>
                                <span>Proses ini akan menarik data terbaru dari API Inaproc/SIRUP dan menyimpannya ke
                                    database lokal. Hal ini dilakukan untuk meningkatkan kecepatan dashboard secara
                                    signifikan.</span>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary" onclick="startSyncAll()">
                                    <i class="bx bx-sync me-1"></i> Sinkronkan Seluruh Data
                                </button>
                                <button type="button" class="btn btn-outline-secondary"
                                    onclick="startSync('vendor_details')">
                                    <i class="bx bx-id-card me-1"></i> Sinkronkan Detail Penyedia
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Pilih Tahun Data yang Ingin Diperbarui</label>
                            <select id="sync-year" class="form-select">
                                <?php
                                $currYear = date('Y');
                                for ($y = $currYear; $y >= 2022; $y--): ?>
                                    <option value="<?= $y ?>"><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                            <small class="text-muted">Proses sync hanya akan menambah/memperbarui data pada tahun yang
                                dipilih tanpa menghapus data tahun lainnya.</small>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="card border shadow-none h-100">
                                <div class="card-body text-center">
                                    <div class="avatar avatar-lg mx-auto mb-3">
                                        <span class="avatar-initial rounded bg-label-primary"><i
                                                class="bx bx-package fs-2"></i></span>
                                    </div>
                                    <h5 class="mb-1">RUP Penyedia</h5>
                                    <p class="text-muted small mb-1" style="font-size: 0.70rem; word-break: break-all;"
                                        title="API Endpoint">
                                        /api/data/perbandingan-rup-penyedia<br>
                                        /api/data/rup-paket-anggaran
                                    </p>
                                    <p class="text-muted small mb-3">Update: <span class="fw-bold"
                                            id="last-sync-penyedia"><?= $last_sync_penyedia ?></span></p>
                                    <button type="button" class="btn btn-primary w-100"
                                        onclick="startSync('penyedia')">Sinkronkan</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border shadow-none h-100">
                                <div class="card-body text-center">
                                    <div class="avatar avatar-lg mx-auto mb-3">
                                        <span class="avatar-initial rounded bg-label-secondary"><i
                                                class="bx bx-id-card fs-2"></i></span>
                                    </div>
                                    <h5 class="mb-1">Detail Penyedia</h5>
                                    <p class="text-muted small mb-1" style="font-size: 0.70rem; word-break: break-all;"
                                        title="Sync Task">
                                        Sinkronisasi referensi SIKaP Bot (CSV)<br>
                                        &amp; Inaproc ke `inaproc_vendor_sync`
                                    </p>
                                    <p class="text-muted small mb-3">Update: <span
                                            class="fw-bold"><?= $last_sync_vendor_details ?? 'Belum pernah' ?></span>
                                    </p>
                                    <button type="button" class="btn btn-secondary w-100"
                                        onclick="startSync('vendor_details')">Sinkronkan</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border shadow-none h-100">
                                <div class="card-body text-center">
                                    <div class="avatar avatar-lg mx-auto mb-3">
                                        <span class="avatar-initial rounded bg-label-success"><i
                                                class="bx bx-refresh fs-2"></i></span>
                                    </div>
                                    <h5 class="mb-1">RUP Swakelola</h5>
                                    <p class="text-muted small mb-1" style="font-size: 0.70rem; word-break: break-all;"
                                        title="API Endpoint">
                                        /api/data/perbandingan-swakelola<br>
                                        /api/data/rup-paket-swakelola
                                    </p>
                                    <p class="text-muted small mb-3">Update: <span class="fw-bold"
                                            id="last-sync-swakelola"><?= $last_sync_swakelola ?></span></p>
                                    <button type="button" class="btn btn-success w-100"
                                        onclick="startSync('swakelola')">Sinkronkan</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border shadow-none h-100">
                                <div class="card-body text-center">
                                    <div class="avatar avatar-lg mx-auto mb-3">
                                        <span class="avatar-initial rounded bg-label-warning"><i
                                                class="bx bx-file fs-2"></i></span>
                                    </div>
                                    <h5 class="mb-1">Data Tender</h5>
                                    <p class="text-muted small mb-1" style="font-size: 0.70rem; word-break: break-all;"
                                        title="API Endpoint">
                                        /api/data/tender-selesai<br>
                                        /api/data/list-tender-kontrak<br>
                                        /api/data/pengumuman-tender
                                    </p>
                                    <p class="text-muted small mb-3">Update: <span
                                            class="fw-bold"><?= $last_sync_tender ?? 'Belum' ?></span></p>
                                    <button type="button" class="btn btn-warning w-100 text-white"
                                        onclick="startSync('tender')">Sinkronkan</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border shadow-none h-100">
                                <div class="card-body text-center">
                                    <div class="avatar avatar-lg mx-auto mb-3">
                                        <span class="avatar-initial rounded bg-label-info"><i
                                                class="bx bx-file-blank fs-2"></i></span>
                                    </div>
                                    <h5 class="mb-1">Data Non-Tender</h5>
                                    <p class="text-muted small mb-1" style="font-size: 0.70rem; word-break: break-all;"
                                        title="API Endpoint">
                                        /api/data/nontender-selesai<br>
                                        /api/data/list-nontender-ekontrak
                                    </p>
                                    <p class="text-muted small mb-3">Update: <span
                                            class="fw-bold"><?= $last_sync_nontender ?? 'Belum' ?></span></p>
                                    <button type="button" class="btn btn-info w-100"
                                        onclick="startSync('nontender')">Sinkronkan</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border shadow-none h-100">
                                <div class="card-body text-center">
                                    <div class="avatar avatar-lg mx-auto mb-3">
                                        <span class="avatar-initial rounded bg-label-secondary"><i
                                                class="bx bx-cart fs-2"></i></span>
                                    </div>
                                    <h5 class="mb-1">E-Purchasing</h5>
                                    <p class="text-muted small mb-1" style="font-size: 0.70rem; word-break: break-all;"
                                        title="API Endpoint">
                                        /api/data/paket-epurchasing
                                    </p>
                                    <p class="text-muted small mb-3">Update: <span
                                            class="fw-bold"><?= $last_sync_epurchasing ?? 'Belum' ?></span></p>
                                    <button type="button" class="btn btn-secondary w-100"
                                        onclick="startSync('epurchasing')">Sinkronkan</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border shadow-none h-100">
                                <div class="card-body text-center">
                                    <div class="avatar avatar-lg mx-auto mb-3">
                                        <span class="avatar-initial rounded bg-label-danger"><i
                                                class="bx bx-cog fs-2"></i></span>
                                    </div>
                                    <h5 class="mb-1">Master Anggaran</h5>
                                    <p class="text-muted small mb-1" style="font-size: 0.70rem; word-break: break-all;"
                                        title="API Endpoint">
                                        /api/data/master-satker<br>
                                        /api/data/kegiatan-master
                                    </p>
                                    <p class="text-muted small mb-3">Update: <span
                                            class="fw-bold"><?= $last_sync_master ?? 'Belum' ?></span></p>
                                    <button type="button" class="btn btn-danger w-100"
                                        onclick="startSync('master')">Sinkronkan</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border shadow-none h-100">
                                <div class="card-body text-center">
                                    <div class="avatar avatar-lg mx-auto mb-3">
                                        <span class="avatar-initial rounded bg-label-dark"><i
                                                class="bx bx-edit fs-2"></i></span>
                                    </div>
                                    <h5 class="mb-1">Pencatatan Non-Tender</h5>
                                    <p class="text-muted small mb-1" style="font-size: 0.70rem; word-break: break-all;"
                                        title="API Endpoint">
                                        /api/data/pencatatan-nontender-realisasi
                                    </p>
                                    <p class="text-muted small mb-3">Update: <span
                                            class="fw-bold"><?= $last_sync_pct_nontender ?? 'Belum' ?></span></p>
                                    <button type="button" class="btn btn-dark w-100"
                                        onclick="startSync('pct_nontender')">Sinkronkan</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border shadow-none h-100">
                                <div class="card-body text-center">
                                    <div class="avatar avatar-lg mx-auto mb-3">
                                        <span class="avatar-initial rounded bg-label-dark"><i
                                                class="bx bx-spreadsheet fs-2"></i></span>
                                    </div>
                                    <h5 class="mb-1">Pencatatan Swakelola</h5>
                                    <p class="text-muted small mb-1" style="font-size: 0.70rem; word-break: break-all;"
                                        title="API Endpoint">
                                        /api/data/list-pencatatan-swakelola<br>
                                        /api/data/encatatan-swakelola-realisasi
                                    </p>
                                    <p class="text-muted small mb-3">Update: <span
                                            class="fw-bold"><?= $last_sync_pct_swakelola ?? 'Belum' ?></span></p>
                                    <button type="button" class="btn btn-dark w-100"
                                        onclick="startSync('pct_swakelola')">Sinkronkan</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border shadow-none h-100">
                                <div class="card-body text-center">
                                    <div class="avatar avatar-lg mx-auto mb-3">
                                        <span class="avatar-initial rounded bg-label-primary"><i
                                                class="bx bx-table fs-2"></i></span>
                                    </div>
                                    <h5 class="mb-1">Tabel Progres SPSE (Monev)</h5>
                                    <p class="text-muted small mb-1" style="font-size: 0.70rem; word-break: break-all;"
                                        title="Kalkulasi Otomatis">
                                        Agregasi RUP &amp; E-Kontrak per OPD<br>
                                        langsung dari database ke `monev_progres_spse`
                                    </p>
                                    <p class="text-muted small mb-3">Update: <span
                                            class="fw-bold" id="last-sync-monev-progres"><?= $last_sync_monev_progres ?? 'Belum' ?></span></p>
                                    <button type="button" class="btn btn-primary w-100"
                                        onclick="startSync('monev_progres')">Sinkronkan</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sync Progress Modal -->
            <div class="modal fade" id="syncModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalTitle">Sinkronisasi Sedang Berjalan...</h5>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-4">
                                <div class="spinner-border text-primary" role="status"
                                    style="width: 3rem; height: 3rem;">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-3" id="syncStatus">Menghubungkan ke API...</p>
                            </div>
                            <div class="progress" style="height: 20px;">
                                <div id="syncProgress"
                                    class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                    role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0"
                                    aria-valuemax="100"></div>
                            </div>
                            <p class="text-center small text-muted mt-2">Mohon jangan tutup halaman ini sampai proses
                                selesai.</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    async function startSyncAll() {
        const types = ['penyedia', 'swakelola', 'tender', 'nontender', 'epurchasing', 'master', 'pct_nontender', 'pct_swakelola', 'monev_progres'];

        const confirm = await showConfirm(
            'Apakah Anda yakin ingin mensinkronkan seluruh data? Proses ini mungkin memakan waktu beberapa menit tergantung pada volume data.',
            'Ya, Sinkronkan Semua',
            'Konfirmasi Sinkronisasi Total'
        );

        if (!confirm.isConfirmed) return;

        const modal = new bootstrap.Modal(document.getElementById('syncModal'));
        const progressBar = document.getElementById('syncProgress');
        const statusText = document.getElementById('syncStatus');
        const titleText = document.getElementById('modalTitle');

        modal.show();
        let successCount = 0;

        for (let i = 0; i < types.length; i++) {
            const type = types[i];
            const displayType = type.replace(/_/g, ' ').toUpperCase();
            const percent = Math.round(((i) / types.length) * 100);

            titleText.innerText = `Sinkronisasi Total (${i + 1}/${types.length})`;
            statusText.innerText = `Sedang memproses ${displayType}...`;
            progressBar.style.width = percent + '%';

            const selectedYear = document.getElementById('sync-year').value;

            try {
                const res = await fetch(`<?= base_url('user/do-sync') ?>/${type}?tahun=${selectedYear}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                const text = await res.text();
                let data = parseSyncJson(text, res);

                if (data.status === 'success') {
                    successCount++;
                } else {
                    console.error(`Gagal sync ${type}:`, data.message);
                }
            } catch (err) {
                console.error(`Error sync ${type}:`, err);
            }
        }

        progressBar.style.width = '100%';
        progressBar.classList.remove('progress-bar-animated', 'bg-primary');
        progressBar.classList.add('bg-success');
        statusText.innerText = `Selesai! Berhasil mensinkronkan ${successCount} dari ${types.length} kategori data.`;

        setTimeout(() => {
            modal.hide();
            showAlert('Sinkronisasi Selesai', statusText.innerText, 'success').then(() => {
                window.location.reload();
            });
        }, 2000);
    }

    function parseSyncJson(text, response) {
        const clean = (text || '').trim();
        if (!clean) {
            throw new Error(`Server tidak mengembalikan data / koneksi terputus (HTTP ${response ? response.status : 'error'}). Kemungkinan proses timeout di server.`);
        }
        try {
            return JSON.parse(clean);
        } catch (e) {
            // Coba ekstrak substring JSON jika ada whitespace atau header tercampur
            const firstBrace = clean.indexOf('{');
            const lastBrace = clean.lastIndexOf('}');
            if (firstBrace !== -1 && lastBrace !== -1 && lastBrace > firstBrace) {
                try {
                    return JSON.parse(clean.substring(firstBrace, lastBrace + 1));
                } catch (_) {}
            }

            if (clean.includes('<html') || clean.includes('<!DOCTYPE')) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(clean, 'text/html');
                const title = doc.querySelector('title')?.innerText || doc.querySelector('h1')?.innerText || '';
                throw new Error(title ? `Server error: ${title} (HTTP ${response ? response.status : 'error'})` : `Server merespon halaman web HTML, bukan JSON.`);
            }
            throw new Error(`Respon server tidak valid (${clean.substring(0, 150)}...)`);
        }
    }

    function startSync(type) {
        const modal = new bootstrap.Modal(document.getElementById('syncModal'));
        const progressBar = document.getElementById('syncProgress');
        const statusText = document.getElementById('syncStatus');
        const titleText = document.getElementById('modalTitle');

        const labelType = type.replace(/_/g, ' ');
        titleText.innerText = `Sinkronisasi ${labelType.charAt(0).toUpperCase() + labelType.slice(1)}...`;
        statusText.innerText = 'Mengirim permintaan ke server...';
        progressBar.style.width = '10%';
        modal.show();

        // Simulate progress since PHP execution is one-shot for this AJAX
        let progress = 10;
        const interval = setInterval(() => {
            if (progress < 90) {
                progress += Math.floor(Math.random() * 5);
                progressBar.style.width = progress + '%';
                if (progress > 30) statusText.innerText = 'Mengunduh data dari API...';
                if (progress > 60) statusText.innerText = 'Memproses dan menyimpan ke database...';
            }
        }, 1500);

        const selectedYear = document.getElementById('sync-year').value;

        fetch(`<?= base_url('user/do-sync') ?>/${type}?tahun=${selectedYear}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(async response => {
                const text = await response.text();
                const data = parseSyncJson(text, response);
                if (!response.ok && data.message) {
                    throw new Error(data.message);
                }
                return data;
            })
            .then(data => {
                clearInterval(interval);
                if (data.status === 'success') {
                    progressBar.style.width = '100%';
                    progressBar.classList.remove('progress-bar-animated', 'bg-primary');
                    progressBar.classList.add('bg-success');
                    statusText.innerText = data.message;

                    setTimeout(() => {
                        modal.hide();
                        showAlert('Berhasil!', data.message, 'success').then(() => {
                            window.location.reload();
                        });
                    }, 1500);
                } else {
                    throw new Error(data.message || 'Gagal sinkronisasi data.');
                }
            })
            .catch(error => {
                clearInterval(interval);
                modal.hide();
                showAlert('Gagal!', error.message, 'error');
            });
    }
</script>
<?= $this->endSection() ?>