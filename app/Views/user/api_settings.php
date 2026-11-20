<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?>

<?= $this->section('content') ?>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-1 mb-0">
            <span class="text-muted fw-light">Pengaturan /</span> AGI Integrator & API
        </h4>
        <div class="d-flex gap-2">
            <a href="<?= base_url('user/sync') ?>" class="btn btn-outline-primary btn-sm">
                <i class="bx bx-sync me-1"></i> Ke Menu Sinkronisasi
            </a>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success alert-dismissible shadow-sm fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="bx bx-check-circle fs-4 me-2"></i>
                <div><?= session()->getFlashdata('success') ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible shadow-sm fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="bx bx-error-circle fs-4 me-2"></i>
                <div><?= session()->getFlashdata('error') ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Row 1: Integrator Connection & Schema Config Import -->
    <div class="row g-4 mb-4">
        <!-- Card 1: Kredensial & URL AGI Integrator -->
        <div class="col-lg-7">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header d-flex justify-content-between align-items-center border-bottom pb-3">
                    <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                        <i class="bx bx-transfer-alt text-primary fs-4"></i>
                        <span>Koneksi Gateway AGI Integrator</span>
                    </h5>
                    <span class="badge bg-label-primary">Gateway Data Pengadaan</span>
                </div>
                <div class="card-body pt-4">
                    <form action="<?= base_url('user/api-settings/save-agi') ?>" method="POST" id="formAgiSettings">
                        <?= csrf_field() ?>
                        
                        <!-- Base URL -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Base URL AGI Integrator</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bx bx-globe"></i></span>
                                <input type="url" name="agi_api_base_url" id="agiBaseUrl" class="form-control" 
                                       value="<?= esc($agi_api_base_url) ?>" placeholder="http://localhost:3000" required>
                            </div>
                            <div class="form-text d-flex gap-2 align-items-center mt-1">
                                <span class="text-muted small">Preset Cepat:</span>
                                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" onclick="setPresetUrl('http://localhost:3000')">Localhost :3000</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" onclick="setPresetUrl('https://ajip-api.cloud')">Production (ajip-api.cloud)</button>
                            </div>
                        </div>

                        <!-- Switch Mode Tanpa Token -->
                        <div class="mb-3 p-3 bg-light rounded border">
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" type="checkbox" name="agi_no_token" id="agiNoToken" value="1" <?= (!empty($agi_no_token) && $agi_no_token === '1') || empty($agi_api_token) ? 'checked' : '' ?> onchange="toggleNoTokenMode()">
                                <label class="form-check-label fw-bold text-dark cursor-pointer" for="agiNoToken">
                                    Koneksi Tanpa Token <span class="badge bg-label-info ms-1">Public / No Auth</span>
                                </label>
                            </div>
                            <div class="form-text small text-muted mb-0">
                                Aktifkan jika server/gateway AGI Integrator dapat diakses publik tanpa perlu otentikasi Bearer Token / X-API-KEY.
                            </div>
                        </div>

                        <!-- API Token -->
                        <div class="mb-3" id="tokenContainer">
                            <label class="form-label fw-bold">Token API Personal (AGI Integrator)</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text bg-light"><i class="bx bx-key"></i></span>
                                <input type="password" name="agi_api_token" id="agiApiToken" class="form-control font-monospace" 
                                       value="<?= esc($agi_api_token) ?>" placeholder="agi_tok_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                                <span class="input-group-text cursor-pointer" onclick="toggleTokenVisibility()" title="Lihat/Sembunyikan Token">
                                    <i class="bx bx-hide" id="eyeIcon"></i>
                                </span>
                            </div>
                            <div class="form-text">
                                Token ini digunakan untuk mengautentikasi setiap permintaan sinkronisasi data dari AGI Integrator melalui header <code>Authorization: Bearer &lt;token&gt;</code>.
                            </div>
                        </div>

                        <!-- Test Connection Alert Result -->
                        <div id="testConnAlert" class="alert d-none mb-3 py-2 px-3 small" role="alert"></div>

                        <div class="d-flex justify-content-between align-items-center pt-2">
                            <button type="button" class="btn btn-outline-info" id="btnTestConn" onclick="testAgiConnection()">
                                <i class="bx bx-broadcast me-1" id="iconTestConn"></i> Test Koneksi & Token
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i> Simpan Pengaturan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Card 2: Import Config Schema -->
        <div class="col-lg-5">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header d-flex justify-content-between align-items-center border-bottom pb-3">
                    <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                        <i class="bx bx-import text-success fs-4"></i>
                        <span>Import Config Schema</span>
                    </h5>
                    <span class="badge bg-label-success">Schema JSON v<?= esc($agi_version) ?></span>
                </div>
                <div class="card-body pt-4 d-flex flex-column justify-content-between">
                    <div>
                        <p class="text-muted small mb-3">
                            Unggah file ekspor konfigurasi JSON schema dari aplikasi AGI Integrator untuk memperbarui daftar endpoint, parameter URL, dan jadwal sinkronisasi otomatis.
                        </p>

                        <!-- Upload JSON Form -->
                        <form action="<?= base_url('user/api-settings/import-config') ?>" method="POST" enctype="multipart/form-data" class="mb-3">
                            <?= csrf_field() ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Pilih File Schema (.json)</label>
                                <input type="file" name="config_file" class="form-control" accept=".json,application/json" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100 mb-2">
                                <i class="bx bx-upload me-1"></i> Upload & Terapkan Schema
                            </button>
                        </form>

                        <div class="text-center my-2">
                            <span class="badge bg-label-secondary text-uppercase px-3">atau</span>
                        </div>

                        <!-- Quick Load Default Button Form -->
                        <form action="<?= base_url('user/api-settings/import-config') ?>" method="POST" class="mb-3">
                            <?= csrf_field() ?>
                            <input type="hidden" name="use_default" value="1">
                            <button type="submit" class="btn btn-outline-secondary w-100 btn-sm" onclick="return confirm('Muat konfigurasi bawaan 27 endpoint AGI Integrator Lombok Barat?')">
                                <i class="bx bx-refresh me-1"></i> Muat Schema Default (27 Endpoint Lengkap)
                            </button>
                        </form>
                    </div>

                    <!-- Metadata Info Box -->
                    <div class="bg-light rounded p-3 border mt-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Total Endpoint Aktif:</span>
                            <span class="badge bg-primary fw-bold"><?= esc($total_sources) ?> Endpoint</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Versi Config Schema:</span>
                            <span class="fw-semibold small">v<?= esc($agi_version) ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Terakhir Diimpor:</span>
                            <span class="text-dark small fw-semibold">
                                <?= !empty($agi_last_import_at) ? date('d M Y, H:i', strtotime($agi_last_import_at)) : '<span class="text-muted">Bawaan Sistem</span>' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: List / Katalog Endpoint API AGI Integrator -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header border-bottom py-3">
            <div class="row align-items-center g-2">
                <div class="col-md-5">
                    <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                        <i class="bx bx-list-ul text-primary fs-4"></i>
                        <span>Katalog Endpoint API AGI Integrator yang Dipakai</span>
                    </h5>
                    <p class="text-muted small mb-0 mt-1">Daftar endpoint data pengadaan yang ditarik secara otomatis oleh InfoAJIP.</p>
                </div>
                <div class="col-md-7 d-flex justify-content-md-end gap-2 flex-wrap align-items-center">
                    <button type="button" class="btn btn-primary btn-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#modalAddEndpoint">
                        <i class="bx bx-plus-circle me-1"></i> Tambah Endpoint Manual
                    </button>
                    <div class="input-group input-group-sm" style="max-width: 220px;">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" id="filterEndpointInput" class="form-control" placeholder="Cari endpoint / nama..." onkeyup="filterEndpointsTable()">
                    </div>
                    <select id="filterIntervalSelect" class="form-select form-select-sm" style="max-width: 130px;" onchange="filterEndpointsTable()">
                        <option value="">Semua Interval</option>
                        <option value="daily">Harian (Daily)</option>
                        <option value="weekly">Mingguan</option>
                        <option value="none">Manual / None</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table table-hover table-striped align-middle mb-0" id="endpointsTable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Nama Sumber Data</th>
                        <th>Slug & Endpoint Path</th>
                        <th>Interval Sinkronisasi</th>
                        <th>Jadwal / Waktu</th>
                        <th>Record Terakhir</th>
                        <th class="text-center" style="width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    <?php if (empty($agi_sources)) : ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                Belum ada daftar endpoint API. Silakan klik tombol "Muat Schema Default" atau "Tambah Endpoint Manual" di atas.
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($agi_sources as $index => $source) : ?>
                            <?php 
                                $slug = esc($source['endpoint_slug'] ?? '-');
                                $fullPath = "/api/data/{$slug}";
                                $interval = strtolower($source['sync_interval'] ?? 'none');
                                $recordCount = number_format((int)($source['sync_record_count'] ?? 0));
                                $isManual = !empty($source['is_manual']);
                                
                                $badgeClass = 'bg-label-secondary';
                                if ($interval === 'daily') $badgeClass = 'bg-label-success';
                                elseif ($interval === 'weekly') $badgeClass = 'bg-label-info';
                                elseif ($interval === 'hourly') $badgeClass = 'bg-label-warning';
                            ?>
                            <tr class="endpoint-row" data-name="<?= strtolower(esc($source['name'] ?? '')) ?>" data-slug="<?= strtolower($slug) ?>" data-interval="<?= $interval ?>">
                                <td class="text-muted small"><?= $index + 1 ?></td>
                                <td>
                                    <div class="fw-bold text-dark d-flex align-items-center gap-1">
                                        <?= esc($source['name'] ?? 'Tanpa Nama') ?>
                                        <?php if ($isManual) : ?>
                                            <span class="badge bg-label-info py-0 px-1" style="font-size: 0.65rem;">Manual</span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">ID Integrator: #<?= esc($source['id'] ?? '-') ?></small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <code class="text-primary font-monospace bg-light px-2 py-0.5 rounded small"><?= $fullPath ?></code>
                                        <button type="button" class="btn btn-xs btn-link text-muted p-0" onclick="copyText('<?= $fullPath ?>')" title="Salin Path">
                                            <i class="bx bx-copy"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?= $badgeClass ?> text-uppercase"><?= $interval ?></span>
                                </td>
                                <td>
                                    <span class="small font-monospace text-muted">
                                        <?= !empty($source['sync_time']) ? esc($source['sync_time']) : '-' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-label-dark font-monospace"><?= $recordCount ?> baris</span>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-1">
                                        <button type="button" class="btn btn-outline-primary btn-xs py-1 px-2" onclick="previewEndpointData('<?= $slug ?>', '<?= esc(addslashes($source['name'])) ?>')" title="Lihat Contoh Data JSON">
                                            <i class="bx bx-show me-1"></i> Preview
                                        </button>
                                        <?php if ($isManual) : ?>
                                            <button type="button" class="btn btn-outline-danger btn-xs py-1 px-2" onclick="deleteEndpoint('<?= $slug ?>', '<?= esc(addslashes($source['name'])) ?>')" title="Hapus Endpoint Manual">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer py-2 text-muted small d-flex justify-content-between align-items-center">
            <span>Menampilkan <strong id="visibleRowsCount"><?= count($agi_sources) ?></strong> dari <?= count($agi_sources) ?> endpoint API</span>
            <span class="fst-italic">Endpoint dapat dipratinjau langsung dengan tombol Preview.</span>
        </div>
    </div>

    <!-- Row 3: Token Akses Internal Infoajip (Flutter App) -->
    <div class="accordion mb-4" id="accordionInternalToken">
        <div class="card accordion-item shadow-sm border-0">
            <h2 class="accordion-header" id="headingInternalToken">
                <button type="button" class="accordion-button collapsed fw-bold text-dark" data-bs-toggle="collapse" data-bs-target="#collapseInternalToken" aria-expanded="false">
                    <i class="bx bx-mobile-alt me-2 fs-4 text-warning"></i>
                    <span>Token API Internal InfoAJIP (Akses Aplikasi Flutter)</span>
                </button>
            </h2>
            <div id="collapseInternalToken" class="accordion-collapse collapse" data-bs-parent="#accordionInternalToken">
                <div class="accordion-body pt-3">
                    <div class="alert alert-warning mb-3 small" role="alert">
                        <strong>Informasi:</strong> Bagian ini adalah token lokal milik akun Anda di InfoAJIP untuk mengakses API internal aplikasi mobile Flutter. Token ini terpisah dari koneksi gateway AGI Integrator.
                    </div>
                    <?php 
                        $generatedToken = session()->getFlashdata('generated_token');
                        $hasToken = !empty($user['api_token']);
                        $displayValue = $generatedToken ? $generatedToken : ($hasToken ? '•••••••••••••••••••••••••••••••• (Tersimpan Aman / Ter-hash)' : 'Belum ada token');
                    ?>

                    <?php if ($generatedToken) : ?>
                        <div class="alert alert-danger mb-3 py-2 small" role="alert">
                            <i class="bx bx-error-circle me-1"></i> <strong>Penting:</strong> Salin token ini sekarang karena demi keamanan hanya ditampilkan sekali ini saja:
                            <code class="d-block mt-1 font-monospace p-2 bg-white rounded border select-all"><?= esc($generatedToken) ?></code>
                        </div>
                    <?php endif; ?>

                    <form action="<?= base_url('user/api-settings/generate') ?>" method="POST" class="d-flex align-items-center gap-3">
                        <?= csrf_field() ?>
                        <div class="input-group input-group-merge" style="max-width: 480px;">
                            <input type="text" class="form-control font-monospace" value="<?= esc($displayValue) ?>" readonly>
                            <span class="input-group-text cursor-pointer" onclick="copyText('<?= $generatedToken ? esc($generatedToken) : '' ?>')">
                                <i class="bx bx-copy"></i>
                            </span>
                        </div>
                        <button type="submit" class="btn btn-outline-warning btn-sm" onclick="return confirm('Generate token baru akan membatalkan token yang lama. Lanjutkan?')">
                            <i class="bx bx-refresh me-1"></i> Buat Token Baru
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Preview Data JSON Endpoint -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="badge bg-label-primary p-2"><i class="bx bx-code-alt fs-5"></i></div>
                    <div>
                        <h5 class="modal-title mb-0" id="previewModalTitle">Preview Data Endpoint</h5>
                        <small class="text-muted font-monospace" id="previewModalUrl">/api/data/...</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div id="previewLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Memuat data...</span>
                    </div>
                    <p class="text-muted small mt-2">Menghubungi AGI Integrator & memuat contoh data...</p>
                </div>
                <div id="previewError" class="alert alert-danger d-none" role="alert"></div>
                <div id="previewContent" class="d-none">
                    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                        <span class="badge bg-label-success" id="previewBadgeStatus">HTTP 200 OK</span>
                        <span class="text-muted small" id="previewMetaText">Menampilkan sampel 5 record</span>
                    </div>
                    <pre class="bg-dark text-success p-3 rounded font-monospace small mb-0" style="max-height: 420px; overflow-y: auto;" id="previewJsonCode"></pre>
                </div>
            </div>
            <div class="modal-footer py-2 border-top">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="copyPreviewJson()">
                    <i class="bx bx-copy me-1"></i> Salin JSON
                </button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Endpoint Manual -->
<div class="modal fade" id="modalAddEndpoint" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= base_url('user/api-settings/add-endpoint') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header border-bottom">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <i class="bx bx-plus-circle text-primary"></i>
                        <span>Tambah Endpoint Data Manual</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Sumber Data / Endpoint <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="manualName" class="form-control" placeholder="Contoh: Paket Pengadaan Langsung 2026" required onkeyup="autoGenerateSlug()">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Endpoint Slug (Path URL) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light small">/api/data/</span>
                            <input type="text" name="slug" id="manualSlug" class="form-control font-monospace" placeholder="pengadaan-langsung-2026" required>
                        </div>
                        <div class="form-text small">Slug ini menjadi path akses: <code>/api/data/:slug</code>. Gunakan huruf kecil, angka, dan tanda hubung (-).</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">URL Sumber Asal (Upstream Inaproc)</label>
                        <input type="url" name="source_url" class="form-control" placeholder="https://data.inaproc.id/api/v1/...">
                        <div class="form-text small">URL API Inaproc/LPSE sumber data asli (opsional).</div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Interval Sinkronisasi</label>
                            <select name="sync_interval" class="form-select">
                                <option value="daily" selected>Harian (Daily)</option>
                                <option value="hourly">Setiap Jam (Hourly)</option>
                                <option value="weekly">Mingguan (Weekly)</option>
                                <option value="monthly">Bulanan (Monthly)</option>
                                <option value="none">Manual / Tidak Terjadwal</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Waktu Sync (WITA)</label>
                            <input type="time" name="sync_time" class="form-control" value="04:00">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold">Deskripsi / Catatan Tambahan</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Catatan format atau kebutuhan tabel data..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> Simpan Endpoint
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden Form Delete Endpoint -->
<form id="formDeleteEndpoint" action="<?= base_url('user/api-settings/delete-endpoint') ?>" method="POST" style="display: none;">
    <?= csrf_field() ?>
    <input type="hidden" name="slug" id="deleteEndpointSlug">
</form>

<script>
function setPresetUrl(url) {
    document.getElementById('agiBaseUrl').value = url;
}

function toggleTokenVisibility() {
    const input = document.getElementById('agiApiToken');
    const icon = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bx-hide');
        icon.classList.add('bx-show');
    } else {
        input.type = 'password';
        icon.classList.remove('bx-show');
        icon.classList.add('bx-hide');
    }
}

function copyText(text) {
    if (!text || text.indexOf('••••') !== -1) {
        alert("Tidak ada teks yang dapat disalin.");
        return;
    }
    navigator.clipboard.writeText(text).then(() => {
        alert("Berhasil disalin ke clipboard!");
    });
}

function toggleNoTokenMode() {
    const noTokenCheckbox = document.getElementById('agiNoToken');
    if (!noTokenCheckbox) return;
    const isNoToken = noTokenCheckbox.checked;
    const tokenInput = document.getElementById('agiApiToken');
    const tokenContainer = document.getElementById('tokenContainer');

    if (tokenInput && tokenContainer) {
        if (isNoToken) {
            tokenInput.disabled = true;
            tokenContainer.style.opacity = '0.5';
        } else {
            tokenInput.disabled = false;
            tokenContainer.style.opacity = '1';
        }
    }
}
document.addEventListener('DOMContentLoaded', toggleNoTokenMode);

function testAgiConnection() {
    const baseUrl = document.getElementById('agiBaseUrl').value.trim();
    const isNoToken = document.getElementById('agiNoToken') ? document.getElementById('agiNoToken').checked : false;
    const apiToken = isNoToken ? '' : document.getElementById('agiApiToken').value.trim();
    const btn = document.getElementById('btnTestConn');
    const icon = document.getElementById('iconTestConn');
    const alertBox = document.getElementById('testConnAlert');

    if (!baseUrl) {
        alert("Masukkan Base URL AGI Integrator terlebih dahulu.");
        return;
    }

    btn.disabled = true;
    icon.className = 'bx bx-loader-alt bx-spin me-1';
    alertBox.classList.remove('d-none');
    alertBox.className = 'alert alert-info py-2 px-3 small';
    alertBox.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Menguji konektivitas ke ' + baseUrl + (isNoToken ? ' (Mode Tanpa Token)...' : ' (dengan Token)...');

    const csrfTokenName = '<?= csrf_token() ?>';
    const csrfHeaderName = '<?= config('Security')->headerName ?>';
    const csrfHash = document.querySelector('meta[name="' + csrfTokenName + '"]')?.getAttribute('content') 
                  || document.querySelector('input[name="' + csrfTokenName + '"]')?.value 
                  || '<?= csrf_hash() ?>';

    const formData = new FormData();
    formData.append('base_url', baseUrl);
    formData.append('api_token', apiToken);
    formData.append('no_token', isNoToken ? '1' : '0');
    formData.append(csrfTokenName, csrfHash);

    fetch('<?= base_url('user/api-settings/test-connection') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            [csrfHeaderName]: csrfHash
        },
        body: formData
    })
    .then(async res => {
        const isJson = res.headers.get('content-type')?.includes('application/json');
        const data = isJson ? await res.json() : null;
        if (!res.ok) {
            throw new Error((data && data.message) ? data.message : 'HTTP ' + res.status + ' (' + res.statusText + ')');
        }
        return data;
    })
    .then(data => {
        btn.disabled = false;
        icon.className = 'bx bx-broadcast me-1';

        if (data.status === 'success') {
            alertBox.className = 'alert alert-success py-2 px-3 small';
            alertBox.innerHTML = '<i class="bx bx-check-circle me-1"></i> <strong>' + data.message + '</strong>';
        } else if (data.status === 'warning') {
            alertBox.className = 'alert alert-warning py-2 px-3 small';
            alertBox.innerHTML = '<i class="bx bx-error-circle me-1"></i> ' + data.message;
        } else {
            alertBox.className = 'alert alert-danger py-2 px-3 small';
            alertBox.innerHTML = '<i class="bx bx-x-circle me-1"></i> ' + data.message;
        }
    })
    .catch(err => {
        btn.disabled = false;
        icon.className = 'bx bx-broadcast me-1';
        alertBox.className = 'alert alert-danger py-2 px-3 small';
        alertBox.innerHTML = '<i class="bx bx-x-circle me-1"></i> Gagal melakukan test koneksi: ' + err.message;
    });
}

function filterEndpointsTable() {
    const input = document.getElementById('filterEndpointInput').value.toLowerCase().trim();
    const intervalSelect = document.getElementById('filterIntervalSelect').value.toLowerCase().trim();
    const rows = document.querySelectorAll('.endpoint-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const name = row.getAttribute('data-name') || '';
        const slug = row.getAttribute('data-slug') || '';
        const interval = row.getAttribute('data-interval') || '';

        const matchText = !input || name.includes(input) || slug.includes(input);
        const matchInterval = !intervalSelect || interval === intervalSelect;

        if (matchText && matchInterval) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const countElem = document.getElementById('visibleRowsCount');
    if (countElem) countElem.innerText = visibleCount;
}

let currentPreviewDataString = '';

function previewEndpointData(slug, name) {
    const modalElem = document.getElementById('previewModal');
    const modal = new bootstrap.Modal(modalElem);
    
    document.getElementById('previewModalTitle').innerText = 'Preview: ' + name;
    document.getElementById('previewModalUrl').innerText = '/api/data/' + slug;
    document.getElementById('previewLoading').classList.remove('d-none');
    document.getElementById('previewError').classList.add('d-none');
    document.getElementById('previewContent').classList.add('d-none');
    currentPreviewDataString = '';

    modal.show();

    fetch('<?= base_url('user/api-settings/preview-endpoint') ?>/' + encodeURIComponent(slug))
        .then(res => res.json())
        .then(data => {
            document.getElementById('previewLoading').classList.add('d-none');
            if (data.status === 'success') {
                document.getElementById('previewContent').classList.remove('d-none');
                document.getElementById('previewBadgeStatus').innerText = 'HTTP ' + data.http_code + ' OK';
                document.getElementById('previewBadgeStatus').className = 'badge bg-label-success';
                document.getElementById('previewMetaText').innerText = 'Menampilkan sampel ' + (data.preview ? data.preview.length : 0) + ' record dari ' + data.url;

                currentPreviewDataString = JSON.stringify(data.preview, null, 2);
                document.getElementById('previewJsonCode').innerText = currentPreviewDataString;
            } else {
                const errBox = document.getElementById('previewError');
                errBox.classList.remove('d-none');
                errBox.innerHTML = '<i class="bx bx-error-circle me-1"></i> ' + (data.message || 'Gagal memuat preview data dari endpoint.');
            }
        })
        .catch(err => {
            document.getElementById('previewLoading').classList.add('d-none');
            const errBox = document.getElementById('previewError');
            errBox.classList.remove('d-none');
            errBox.innerHTML = '<i class="bx bx-error-circle me-1"></i> Gagal melakukan request: ' + err;
        });
}

function copyPreviewJson() {
    if (!currentPreviewDataString) {
        alert("Belum ada data untuk disalin.");
        return;
    }
    navigator.clipboard.writeText(currentPreviewDataString).then(() => {
        alert("Data JSON berhasil disalin!");
    });
}

function autoGenerateSlug() {
    const nameInput = document.getElementById('manualName');
    const slugInput = document.getElementById('manualSlug');
    if (!slugInput.dataset.manualEdited) {
        const slug = nameInput.value.toLowerCase()
            .trim()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
        slugInput.value = slug;
    }
}

document.getElementById('manualSlug')?.addEventListener('input', function() {
    this.dataset.manualEdited = "true";
});

function deleteEndpoint(slug, name) {
    if (confirm('Yakin ingin menghapus endpoint manual "' + name + '" (/api/data/' + slug + ') dari katalog?')) {
        document.getElementById('deleteEndpointSlug').value = slug;
        document.getElementById('formDeleteEndpoint').submit();
    }
}
</script>
<?= $this->endSection() ?>
