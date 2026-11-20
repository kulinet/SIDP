<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?>

<?= $this->section('content') ?>
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<!-- Leaflet Geocoder CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
<!-- Leaflet Draw CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css"/>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
        <div>
            <h4 class="fw-bold py-1 mb-0">
                <span class="text-muted fw-light">MONEV /</span> Monitoring Proyek PPK
            </h4>
            <p class="text-muted small mb-0">Kelola kontrak, jadwal Kurva S, pelaporan fisik harian, dan verifikasi progres konstruksi.</p>
        </div>
        
        <!-- Project Switcher & Actions -->
        <div class="d-flex flex-column flex-md-row gap-2 align-items-stretch align-items-md-center w-100 w-md-auto">
            <?php if (!empty($proyekList)): ?>
                <form method="GET" action="" class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-1 w-100">
                    <label for="proyek_id_select" class="text-nowrap me-md-2 mb-1 mb-md-0 fw-semibold text-muted small">Pilih Proyek:</label>
                    <select name="proyek_id" id="proyek_id_select" class="form-select form-select-sm border-primary shadow-sm w-100" onchange="this.form.submit()" style="min-width: 0; max-width: 100%;">
                        <?php foreach ($proyekList as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= (isset($activeProyek) && $activeProyek['id'] == $p['id']) ? 'selected' : '' ?>>
                                <?= esc($p['nama_proyek']) ?> (<?= esc($p['no_kontrak']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>
            
            <div class="d-flex gap-2 w-100 justify-content-md-end mt-2 mt-md-0">
                <?php if (in_array(session()->get('role'), ['ppk', 'admin', 'operator'])): ?>
                    <button class="btn btn-sm btn-primary shadow-sm flex-fill flex-md-grow-0 text-nowrap" onclick="showSetupModal()">
                        <i class="bx bx-plus me-1"></i> Proyek Baru
                    </button>
                <?php endif; ?>
                <?php if (isset($activeProyek)): ?>
                    <button class="btn btn-sm btn-secondary shadow-sm flex-fill flex-md-grow-0 text-nowrap" onclick="printLaporanProyek()">
                        <i class="bx bx-printer me-1"></i> Cetak Laporan
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Alert / Status Indicator Banners -->
    <?php if (isset($activeProyek)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm overflow-hidden" style="border-left: 5px solid <?= $statusIndikator === 'Red' || $statusIndikator === 'Merah' ? '#ff3e1d' : ($statusIndikator === 'Kuning' ? '#ffab00' : '#71dd37') ?>;">
                    <div class="card-body py-3 d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar avatar-md flex-shrink-0">
                                <span class="avatar-initial rounded bg-label-<?= $statusIndikator === 'Red' || $statusIndikator === 'Merah' ? 'danger' : ($statusIndikator === 'Kuning' ? 'warning' : 'success') ?>">
                                    <i class="bx <?= $statusIndikator === 'Red' || $statusIndikator === 'Merah' ? 'bx-error-circle' : ($statusIndikator === 'Kuning' ? 'bx-time-five' : 'bx-check-double') ?> fs-4"></i>
                                </span>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">
                                    Status Proyek: 
                                    <span class="badge bg-<?= $statusIndikator === 'Red' || $statusIndikator === 'Merah' ? 'danger' : ($statusIndikator === 'Kuning' ? 'warning' : 'success') ?> ms-1">
                                        <?= $statusIndikator === 'Red' || $statusIndikator === 'Merah' ? 'MERAH (Terlambat / Kritis)' : ($statusIndikator === 'Kuning' ? 'KUNING (Deviasi Sedang)' : 'HIJAU (Sesuai Rencana)') ?>
                                    </span>
                                </h6>
                                <p class="text-muted small mb-0 mt-1">
                                    Deviasi Mingguan Terakhir: <strong class="<?= $deviasiMingguan < 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($deviasiMingguan, 2) ?>%</strong> dari target rencana minggu ini.
                                </p>
                            </div>
                        </div>
                        
                        <?php if ($warningKpa): ?>
                            <div class="alert alert-danger mb-0 py-2 px-3 d-flex align-items-center gap-2 border-0 shadow-sm">
                                <i class="bx bxs-bell bx-tada text-danger"></i>
                                <span class="small fw-semibold text-danger">PERINGATAN: Deviasi kritis > 10% / Terlambat > 14 hari. Notifikasi eskalasi KPA/PA telah diaktifkan!</span>
                            </div>
                        <?php elseif ($deviasiMingguan <= -5.0): ?>
                            <div class="alert alert-warning mb-0 py-2 px-3 d-flex align-items-center gap-2 border-0 shadow-sm">
                                <i class="bx bx-info-circle text-warning"></i>
                                <span class="small fw-semibold text-warning">Perhatian: Terjadi keterlambatan kinerja minggu ini (deviasi minus > 5%). PPK wajib mengarahkan tindak lanjut percepatan fisik.</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Empty State -->
    <?php if (empty($proyekList)): ?>
        <div class="card border-0 shadow-sm py-5 text-center">
            <div class="card-body">
                <i class="bx bx-folder-open text-primary mb-3" style="font-size: 5.5rem; opacity: 0.85;"></i>
                <h4 class="fw-bold text-dark">Belum Ada Proyek yang Dimonitor</h4>
                <p class="text-muted small mx-auto mb-4" style="max-width: 550px;">
                    Anda belum membuat atau mengelola paket proyek konstruksi/pengadaan apa pun. Silakan tambahkan proyek pertama Anda secara manual atau klaim kode RUP Anda terlebih dahulu untuk mempermudah pre-filling data kontrak.
                </p>
                <?php if (in_array(session()->get('role'), ['ppk', 'admin', 'operator'])): ?>
                    <button class="btn btn-primary" onclick="showSetupModal()">
                        <i class="bx bx-plus me-1"></i> Buat Proyek Pertama
                    </button>
                <?php else: ?>
                    <div class="alert alert-info d-inline-block border-0 px-4">
                        <i class="bx bx-info-circle me-1"></i> Login sebagai role <strong>PPK</strong> atau <strong>Administrator</strong> untuk mulai mengelola proyek konstruksi Anda sendiri.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <!-- Main Interactive SPA Welcome Wizard Card -->
        <div id="welcome-action-banner" class="mb-4">
            <div class="mb-3">
                <h5 class="fw-bold text-dark mb-1">Menu Layanan Monev</h5>
                <p class="text-muted small mb-0">Pilih menu di bawah untuk mengelola data proyek konstruksi.</p>
            </div>
            
            <!-- 5 Interactive Clean Cards (MyASN Style) -->
            <div class="row g-3">
                <!-- Card B (Dashboard) -->
                <div class="col-6 col-md-4 col-lg">
                    <div class="card border border-light shadow-sm hover-scale-card cursor-pointer h-100 text-center" style="border-radius: 16px;" onclick="switchSPATab('dashboard-tab')">
                        <div class="card-body p-3">
                            <div class="avatar avatar-md mx-auto mb-2" style="width: 50px; height: 50px;">
                                <span class="avatar-initial rounded-circle bg-label-info w-100 h-100 position-relative">
                                    <i class="bx bx-show-alt fs-3"></i>
                                    <span class="position-absolute top-0 start-100 translate-middle badge border border-light rounded-circle bg-success p-1"><span class="visually-hidden">unread messages</span></span>
                                </span>
                            </div>
                            <h6 class="fw-bold mb-1 text-dark small" style="font-size: 0.8rem;">Dashboard Proyek</h6>
                            <p class="text-muted small mb-0" style="font-size: 0.65rem;">Kurva S & Realisasi</p>
                        </div>
                    </div>
                </div>
                <!-- Card A (Harian) -->
                <div class="col-6 col-md-4 col-lg">
                    <div class="card border border-light shadow-sm hover-scale-card cursor-pointer h-100 text-center" style="border-radius: 16px;" onclick="switchSPATab('harian-tab')">
                        <div class="card-body p-3">
                            <div class="avatar avatar-md mx-auto mb-2" style="width: 50px; height: 50px;">
                                <span class="avatar-initial rounded-circle bg-label-success w-100 h-100 position-relative">
                                    <i class="bx bx-calendar-edit fs-3"></i>
                                    <span class="position-absolute top-0 start-100 translate-middle badge border border-light rounded-circle bg-success p-1"><span class="visually-hidden">unread messages</span></span>
                                </span>
                            </div>
                            <h6 class="fw-bold mb-1 text-dark small" style="font-size: 0.8rem;">Progres Hari Ini</h6>
                            <p class="text-muted small mb-0" style="font-size: 0.65rem;">Laporan & Foto</p>
                        </div>
                    </div>
                </div>
                <!-- Card C (Rekap) -->
                <div class="col-6 col-md-4 col-lg">
                    <div class="card border border-light shadow-sm hover-scale-card cursor-pointer h-100 text-center" style="border-radius: 16px;" onclick="switchSPATab('rekap-tab')">
                        <div class="card-body p-3">
                            <div class="avatar avatar-md mx-auto mb-2" style="width: 50px; height: 50px;">
                                <span class="avatar-initial rounded-circle bg-label-primary w-100 h-100 position-relative">
                                    <i class="bx bx-file-find fs-3"></i>
                                </span>
                            </div>
                            <h6 class="fw-bold mb-1 text-dark small" style="font-size: 0.8rem;">Laporan Rekap</h6>
                            <p class="text-muted small mb-0" style="font-size: 0.65rem;">Mingguan & Bulanan</p>
                        </div>
                    </div>
                </div>
                <!-- Card D (Jadwal) -->
                <div class="col-6 col-md-4 col-lg">
                    <div class="card border border-light shadow-sm hover-scale-card cursor-pointer h-100 text-center" style="border-radius: 16px;" onclick="switchSPATab('jadwal-tab')">
                        <div class="card-body p-3">
                            <div class="avatar avatar-md mx-auto mb-2" style="width: 50px; height: 50px;">
                                <span class="avatar-initial rounded-circle bg-label-warning w-100 h-100 position-relative">
                                    <i class="bx bx-slider-alt fs-3"></i>
                                </span>
                            </div>
                            <h6 class="fw-bold mb-1 text-dark small" style="font-size: 0.8rem;">Setup Jadwal</h6>
                            <p class="text-muted small mb-0" style="font-size: 0.65rem;">Baseline Kurva S</p>
                        </div>
                    </div>
                </div>
                <!-- Card E (Dokumen) -->
                <div class="col-6 col-md-4 col-lg">
                    <div class="card border border-light shadow-sm hover-scale-card cursor-pointer h-100 text-center" style="border-radius: 16px;" onclick="switchSPATab('dokumen-tab')">
                        <div class="card-body p-3">
                            <div class="avatar avatar-md mx-auto mb-2" style="width: 50px; height: 50px;">
                                <span class="avatar-initial rounded-circle bg-label-secondary w-100 h-100 position-relative">
                                    <i class="bx bx-cloud-upload fs-3"></i>
                                </span>
                            </div>
                            <h6 class="fw-bold mb-1 text-dark small" style="font-size: 0.8rem;">Upload Dokumen</h6>
                            <p class="text-muted small mb-0" style="font-size: 0.65rem;">Dokumen & File MC</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabbed Navigation Panels -->
        <div class="nav-align-top mb-4">
            <ul class="nav nav-tabs nav-fill border-0 shadow-sm rounded bg-white p-1 mb-3 d-none" role="tablist">
                <li class="nav-item">
                    <button type="button" class="nav-tab-btn nav-link active py-3 fw-bold" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#panel-dashboard" role="tab" aria-selected="true">
                        <i class="bx bx-desktop me-1"></i>Lihat dashboard proyek saya
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-tab-btn nav-link py-3 fw-bold" id="harian-tab" data-bs-toggle="tab" data-bs-target="#panel-harian" role="tab" aria-selected="false">
                        <i class="bx bx-calendar-edit me-1"></i> Input progres hari ini
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-tab-btn nav-link py-3 fw-bold" id="rekap-tab" data-bs-toggle="tab" data-bs-target="#panel-rekap" role="tab" aria-selected="false">
                        <i class="bx bx-bar-chart-alt-2 me-1"></i> Buat laporan mingguan / bulanan
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-tab-btn nav-link py-3 fw-bold" id="jadwal-tab" data-bs-toggle="tab" data-bs-target="#panel-jadwal" role="tab" aria-selected="false">
                        <i class="bx bx-grid-alt me-1"></i>Input atau ubah jadwal proyek
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-tab-btn nav-link py-3 fw-bold" id="dokumen-tab" data-bs-toggle="tab" data-bs-target="#panel-dokumen" role="tab" aria-selected="false">
                        <i class="bx bx-folder me-1"></i>Upload dokumen pendukung
                    </button>
                </li>
            </ul>
            
            <div class="tab-content border-0 shadow-sm rounded bg-white p-4">
                <!-- PANEL B: DASHBOARD & S-CURVE -->
                <div class="tab-pane fade show active" id="panel-dashboard" role="tabpanel">
                    <div class="row g-4">
                        <!-- Contract Summary Metrics -->
                        <div class="col-lg-4">
                            <div class="card border border-light mb-3">
                                <div class="card-header bg-light py-3">
                                    <h6 class="fw-bold mb-0 text-dark"><i class="bx bx-briefcase me-1"></i> Detail Kontrak Kerja</h6>
                                </div>
                                <div class="card-body py-3">
                                    <table class="table table-borderless table-sm small mb-0">
                                        <tr>
                                            <td class="fw-semibold text-muted py-1" style="width: 38%;">Nama Proyek</td>
                                            <td class="text-dark py-1">: <?= esc($activeProyek['nama_proyek']) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted py-1">No. Kontrak</td>
                                            <td class="text-dark py-1">: <span class="badge bg-label-primary"><?= esc($activeProyek['no_kontrak']) ?></span></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted py-1">Nilai Kontrak</td>
                                            <td class="text-dark py-1">: <strong>Rp <?= number_format($activeProyek['nilai_kontrak'], 0, ',', '.') ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted py-1">Penyedia</td>
                                            <td class="text-dark py-1">: <?= esc($activeProyek['nama_penyedia']) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted py-1">Masa Kontrak</td>
                                            <td class="text-dark py-1">: <?= date('d M Y', strtotime($activeProyek['tgl_mulai_kontrak'])) ?> s/d <?= date('d M Y', strtotime($activeProyek['tgl_selesai_kontrak'])) ?>
                                                <?php
                                                    $diffKontrak = strtotime($activeProyek['tgl_selesai_kontrak']) - strtotime($activeProyek['tgl_mulai_kontrak']);
                                                    $daysKontrak = round($diffKontrak / (60 * 60 * 24));
                                                    echo "<span class='badge bg-label-secondary ms-1'>{$daysKontrak} Hari</span>";
                                                ?>
                                            </td>
                                        </tr>
                                        <?php if (!empty($activeProyek['kd_rup'])): ?>
                                        <tr>
                                            <td class="fw-semibold text-muted py-1">No. SPMK / SPP</td>
                                            <td class="text-dark py-1">: <?= esc($activeProyek['no_spmk_spp'] ?: '-') ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted py-1">Masa Pelaksanaan (SPMK)</td>
                                            <td class="text-dark py-1">: 
                                                <?php if (!empty($activeProyek['tgl_mulai_pekerjaan']) && !empty($activeProyek['tgl_selesai_pekerjaan'])): ?>
                                                    <?= date('d M Y', strtotime($activeProyek['tgl_mulai_pekerjaan'])) ?> s/d <?= date('d M Y', strtotime($activeProyek['tgl_selesai_pekerjaan'])) ?>
                                                    <?php if (!empty($activeProyek['waktu_penyelesaian'])): ?>
                                                        <span class="badge bg-label-info ms-1"><?= esc($activeProyek['waktu_penyelesaian']) ?> Hari</span>
                                                    <?php else: ?>
                                                        <?php
                                                            $diffKerja = strtotime($activeProyek['tgl_selesai_pekerjaan']) - strtotime($activeProyek['tgl_mulai_pekerjaan']);
                                                            $daysKerja = round($diffKerja / (60 * 60 * 24));
                                                        ?>
                                                        <span class="badge bg-label-info ms-1"><?= $daysKerja ?> Hari</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted italic">Belum diatur</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                        <tr>
                                            <td class="fw-semibold text-muted py-1">Konsultan</td>
                                            <td class="text-dark py-1">: <?= esc($activeProyek['konsultan_pengawas'] ?: '-') ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted py-1">Direksi Teknis</td>
                                            <td class="text-dark py-1">: <?= esc($activeProyek['direksi_teknis'] ?: '-') ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted py-1">PPHP</td>
                                            <td class="text-dark py-1">: <?= esc($activeProyek['pphp'] ?: '-') ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted py-1">PPK</td>
                                            <td class="text-dark py-1">: <?= esc($activeProyek['ppk'] ?? '-') ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted py-1">Lokasi</td>
                                            <td class="text-dark py-1">: <?= esc($activeProyek['lokasi'] ?? '-') ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted py-1">Sumber Dana</td>
                                            <td class="text-dark py-1">: <?= esc($activeProyek['sumber_dana'] ?? '-') ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted py-1">Jenis Pekerjaan</td>
                                            <td class="text-dark py-1">: <?= esc($activeProyek['jenis_pekerjaan'] ?? '-') ?></td>
                                        </tr>
                                        <?php if(!empty($activeProyek['file_kontrak'])): ?>
                                        <tr>
                                            <td class="fw-semibold text-muted py-1">File Kontrak</td>
                                            <td class="text-dark py-1">: <a href="<?= base_url($activeProyek['file_kontrak']) ?>" target="_blank" class="btn btn-xs btn-outline-primary"><i class="bx bx-download"></i> Unduh File</a></td>
                                        </tr>
                                        <?php endif; ?>
                                    </table>
                                </div>
                            </div>

                            <!-- Map Widget Card -->
                            <div class="card border border-light shadow-sm">
                                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                    <h6 class="fw-bold mb-0 text-dark small"><i class="bx bx-map-alt me-1"></i> Peta Lokasi &amp; Batas Wilayah</h6>
                                    <span class="badge bg-label-info text-capitalize" style="font-size: 0.65rem;">Google Satellite</span>
                                </div>
                                <div class="card-body p-0 position-relative">
                                    <div id="dashboard_map" style="height: 230px; width: 100%; z-index: 1;"></div>
                                    <?php if(empty($activeProyek['gps_kordinat']) && empty($activeProyek['polygon_lokasi'])): ?>
                                        <div class="position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-95 d-flex align-items-center justify-content-center text-center p-3" style="z-index: 2;">
                                            <div class="text-muted small">
                                                <i class="bx bx-map-pin fs-2 mb-1 text-warning"></i>
                                                <p class="mb-0 fw-semibold">Lokasi spasial belum ditentukan.</p>
                                                <p class="mb-0 text-xs">PPK dapat mengaturnya di form Setup &amp; Baseline.</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Summary -->
                        <div class="col-lg-8">
                            <div class="card border border-light h-100">
                                <div class="card-header bg-light py-3">
                                    <h6 class="fw-bold mb-0 text-dark"><i class="bx bx-trending-up me-1"></i> Ringkasan Bobot Realisasi</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row align-items-center h-100">
                                        <div class="col-sm-6 text-center py-2 border-end">
                                            <div class="display-5 fw-bold text-primary mb-1">
                                                <?php 
                                                    $actWeight = 0;
                                                    if (!empty($scurve['actual'])) {
                                                        foreach (array_reverse($scurve['actual']) as $val) {
                                                            if ($val !== null) { $actWeight = $val; break; }
                                                        }
                                                    }
                                                    echo number_format($actWeight, 2);
                                                ?>%
                                            </div>
                                            <span class="text-muted small fw-semibold uppercase">Realisasi Fisik Kumulatif</span>
                                            <div class="progress progress-sm mx-auto mt-2" style="width: 80%; height: 6px;">
                                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $actWeight ?>%;" aria-valuenow="<?= $actWeight ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 text-center py-2">
                                            <div class="display-6 fw-semibold text-muted mb-1">
                                                <?php 
                                                    $planWeight = 0;
                                                    if (!empty($scurve['plan'])) {
                                                        // Let's get current target plan based on current week
                                                        $currentDate = date('Y-m-d');
                                                        $tglMulai = strtotime($activeProyek['tgl_mulai']);
                                                        $daysElapsed = floor((time() - $tglMulai) / 86400);
                                                        $currentWeek = max(1, ceil(($daysElapsed + 1) / 7));
                                                        $index = $currentWeek - 1;
                                                        if ($index < count($scurve['plan'])) {
                                                            $planWeight = $scurve['plan'][$index];
                                                        } else {
                                                            $planWeight = !empty($scurve['plan']) ? end($scurve['plan']) : 100.0;
                                                        }
                                                    }
                                                    echo number_format($planWeight, 2);
                                                ?>%
                                            </div>
                                            <span class="text-muted small fw-semibold uppercase">Rencana Target Kumulatif (M-<?= isset($currentWeek) ? $currentWeek : 1 ?>)</span>
                                            <div class="mt-3">
                                                <span class="small">Deviasi Kumulatif: <strong class="<?= $deviasiTerakhir < 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($deviasiTerakhir, 2) ?>%</strong></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Curve S: Tabel Rencana Waktu Pelaksanaan + Grafik -->
                        <div class="col-12 mt-4">
                            <div class="card border border-light">
                                <div class="card-header bg-light py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <h6 class="fw-bold mb-0 text-dark"><i class="bx bx-grid-alt me-1"></i> Rencana Waktu Pelaksanaan &amp; Kurva S</h6>
                                    <div class="d-flex gap-2 align-items-center">
                                        <span class="badge bg-warning text-dark small">— Rencana</span>
                                        <span class="badge bg-primary small">— Realisasi</span>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="toggleScurveView()" id="btnToggleScurve">
                                            <i class="bx bx-table me-1"></i> Tampilkan Tabel
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($jadwalItems)): ?>
                                        <div class="text-center py-5 text-muted">
                                            <i class="bx bx-calendar-x mb-2" style="font-size: 3rem;"></i>
                                            <p class="small mb-0">Jadwal baseline proyek belum diinput. Kurva S tidak dapat divisualisasikan.</p>
                                        </div>
                                    <?php else: ?>
                                        <!-- Chart View -->
                                        <div id="scurveChartView" class="p-3">
                                            <div style="height: 380px; position: relative;">
                                                <canvas id="scurveChartCanvas"></canvas>
                                            </div>
                                        </div>
                                        <!-- Table View (hidden by default) -->
                                        <div id="scurveTableView" style="display: none;" class="table-responsive">
                                            <table class="table table-bordered table-sm mb-0" id="scurveTable" style="font-size: 0.72rem; min-width: 900px;">
                                                <thead>
                                                    <tr class="text-center bg-dark text-white">
                                                        <th rowspan="2" class="align-middle" style="min-width:180px;">No &nbsp; Uraian Pekerjaan</th>
                                                        <th rowspan="2" class="align-middle" style="width:70px;">Bobot (%)</th>
                                                        <?php
                                                            $maxWeeks = 0;
                                                            foreach($jadwalItems as $ji) {
                                                                $wa = json_decode($ji['target_mingguan'] ?? '[]', true);
                                                                $maxWeeks = max($maxWeeks, is_array($wa) ? count($wa) : 0);
                                                            }
                                                            // Group by month (4 weeks per month)
                                                            $months = ceil($maxWeeks / 4);
                                                            for($m = 1; $m <= $months; $m++):
                                                                $wStart = ($m-1)*4+1;
                                                                $wEnd = min($m*4, $maxWeeks);
                                                                $colspan = $wEnd - $wStart + 1;
                                                        ?>
                                                        <th colspan="<?= $colspan ?>" class="text-center">Minggu ke-<?= $wStart ?><?= $colspan > 1 ? ' s/d '.$wEnd : '' ?></th>
                                                        <?php endfor; ?>
                                                    </tr>
                                                    <tr class="text-center bg-secondary text-white" style="font-size:0.68rem;">
                                                        <?php for($w = 1; $w <= $maxWeeks; $w++): ?>
                                                        <th style="width:50px;">M-<?= $w ?></th>
                                                        <?php endfor; ?>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($jadwalItems as $idx => $ji):
                                                        $weeklyArr = json_decode($ji['target_mingguan'] ?? '[]', true);
                                                        $weeklyArr = is_array($weeklyArr) ? $weeklyArr : [];
                                                    ?>
                                                    <tr>
                                                        <td class="fw-semibold"><?= ($idx+1) ?>. <?= esc($ji['item_pekerjaan']) ?></td>
                                                        <td class="text-center fw-bold"><?= number_format($ji['bobot'],2) ?></td>
                                                        <?php 
                                                            $prevCum = 0;
                                                            for($w = 0; $w < $maxWeeks; $w++):
                                                                $cumVal = isset($weeklyArr[$w]) ? (float)$weeklyArr[$w] : null;
                                                                $wVal = null;
                                                                if ($cumVal !== null && $cumVal > $prevCum) {
                                                                    $deltaPercent = $cumVal - $prevCum;
                                                                    $wVal = ($deltaPercent * $ji['bobot']) / 100;
                                                                    $prevCum = $cumVal;
                                                                }
                                                        ?>
                                                        <td class="text-center" style="background: <?= $wVal !== null && $wVal > 0 ? 'rgba(105,108,255,0.12)' : '' ?>">
                                                            <?php if($wVal !== null && $wVal > 0): ?>
                                                                <span style="font-size:0.65rem; font-weight:700; color:#696cff;"><?= number_format($wVal,2) ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <?php endfor; ?>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                    <!-- Row: Progress Rencana -->
                                                    <tr class="fw-bold bg-warning bg-opacity-25">
                                                        <td colspan="2" class="text-center fw-bold text-dark">Progress Rencana (%)</td>
                                                        <?php
                                                            $planLabels = isset($scurve['plan']) ? $scurve['plan'] : [];
                                                            $prevPlan = 0;
                                                            for($w = 0; $w < $maxWeeks; $w++):
                                                                $wPlan = isset($planLabels[$w]) ? $planLabels[$w] - $prevPlan : null;
                                                                $prevPlan = isset($planLabels[$w]) ? $planLabels[$w] : $prevPlan;
                                                        ?>
                                                        <td class="text-center" style="font-size:0.65rem;"><?= $wPlan !== null ? number_format($wPlan,2) : '' ?></td>
                                                        <?php endfor; ?>
                                                    </tr>
                                                    <!-- Row: Total Kumulatif Rencana -->
                                                    <tr class="fw-bold" style="background:#fff3cd;">
                                                        <td colspan="2" class="text-center fw-bold text-warning-emphasis">Total Kumulatif Rencana (%)</td>
                                                        <?php for($w = 0; $w < $maxWeeks; $w++): ?>
                                                        <td class="text-center fw-bold" style="font-size:0.65rem; color:#d97706;">
                                                            <?= isset($planLabels[$w]) ? number_format($planLabels[$w],2) : '' ?>
                                                        </td>
                                                        <?php endfor; ?>
                                                    </tr>
                                                    <!-- Row: Progress Aktual -->
                                                    <tr class="fw-bold">
                                                        <td colspan="2" class="text-center fw-bold text-primary">Progress Aktual (%)</td>
                                                        <?php
                                                            $actualLabels = isset($scurve['actual']) ? $scurve['actual'] : [];
                                                            $prevAct = 0;
                                                            for($w = 0; $w < $maxWeeks; $w++):
                                                                $wAct = isset($actualLabels[$w]) ? $actualLabels[$w] - $prevAct : null;
                                                                $prevAct = isset($actualLabels[$w]) ? $actualLabels[$w] : $prevAct;
                                                        ?>
                                                        <td class="text-center" style="font-size:0.65rem;">
                                                            <?= ($wAct !== null && $wAct != 0) ? number_format($wAct,2) : '' ?>
                                                        </td>
                                                        <?php endfor; ?>
                                                    </tr>
                                                    <!-- Row: Total Kumulatif Aktual -->
                                                    <tr class="fw-bold" style="background:#dbeafe;">
                                                        <td colspan="2" class="text-center fw-bold" style="color:#1d4ed8;">Total Kumulatif Aktual (%)</td>
                                                        <?php for($w = 0; $w < $maxWeeks; $w++): ?>
                                                        <td class="text-center fw-bold" style="font-size:0.65rem; color:#1d4ed8;">
                                                            <?= (isset($actualLabels[$w]) && $actualLabels[$w] != 0) ? number_format($actualLabels[$w],2) : '' ?>
                                                        </td>
                                                        <?php endfor; ?>
                                                    </tr>
                                                    <!-- Row: Deviasi -->
                                                    <tr class="fw-bold">
                                                        <td colspan="2" class="text-center fw-bold">Deviasi (+/-)</td>
                                                        <?php
                                                            $devLabels = isset($scurve['deviasi']) ? $scurve['deviasi'] : [];
                                                            for($w = 0; $w < $maxWeeks; $w++):
                                                                $dv = $devLabels[$w] ?? null;
                                                        ?>
                                                        <td class="text-center" style="font-size:0.65rem; color:<?= $dv !== null ? ($dv < 0 ? '#dc2626' : '#16a34a') : 'inherit' ?>;">
                                                            <?= $dv !== null ? number_format($dv,2) : '' ?>
                                                        </td>
                                                        <?php endfor; ?>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PANEL A: PELAPORAN HARIAN -->
                <div class="tab-pane fade" id="panel-harian" role="tabpanel">
                    <div class="row g-4">
                        <!-- Form Entry Harian -->
                        <div class="col-lg-5">
                            <div class="card border border-light">
                                <div class="card-header bg-light py-3">
                                    <h6 class="fw-bold mb-0 text-dark" id="form-harian-title"><i class="bx bx-edit me-1"></i> Form Laporan Progres Fisik Harian</h6>
                                </div>
                                <div class="card-body py-3">
                                    <?php if (empty($jadwalItems)): ?>
                                        <div class="alert alert-warning border-0 small mb-0 py-3">
                                            <i class="bx bx-info-circle me-1"></i> Anda harus mengisi baseline jadwal item pekerjaan di tab <strong>[D] Setup & Baseline</strong> terlebih dahulu sebelum melaporkan progres harian!
                                        </div>
                                    <?php else: ?>
                                        <?php if (in_array(session()->get('role'), ['ppk', 'admin', 'operator'])): ?>
                                        <form id="formHarianMonev" enctype="multipart/form-data">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="proyek_id" value="<?= $activeProyek['id'] ?>">
                                            <input type="hidden" name="id" id="harian_edit_id" value="">
                                            
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold" for="harian_item_id">Item Pekerjaan</label>
                                                <select name="item_id" id="harian_item_id" class="form-select border-primary" required>
                                                    <option value="" disabled selected>-- Pilih Item Pekerjaan --</option>
                                                    <?php foreach ($jadwalItems as $item): ?>
                                                        <option value="<?= $item['id'] ?>" data-bobot="<?= $item['bobot'] ?>">
                                                            <?= esc($item['item_pekerjaan']) ?> (Bobot: <?= $item['bobot'] ?>%)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            
                                            <div class="row g-2 mb-3">
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold" for="harian_tanggal">Tanggal Kerja</label>
                                                    <input type="date" name="tanggal" id="harian_tanggal" class="form-select" required value="<?= date('Y-m-d') ?>">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold" for="harian_progres">Progres Fisik Kumulatif (%)</label>
                                                    <div class="input-group">
                                                        <input type="number" step="0.01" name="progres_persen" id="harian_progres" class="form-control" placeholder="e.g. 15.5" required min="0" max="100">
                                                        <span class="input-group-text bg-light">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row g-2 mb-3">
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold" for="harian_volume">Volume Terpasang</label>
                                                    <input type="number" step="0.01" name="volume_selesai" id="harian_volume" class="form-control" placeholder="e.g. 50" required min="0">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold" for="harian_satuan">Satuan Kerja</label>
                                                    <input type="text" name="satuan" id="harian_satuan" class="form-control" placeholder="e.g. m3, m2, unit" required>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold" for="harian_lokasi">Lokasi Spesifik / STA</label>
                                                <input type="text" name="lokasi" id="harian_lokasi" class="form-control" placeholder="e.g. Lantai 1, STA 0+250" required>
                                            </div>
                                            
                                            <div class="row g-2 mb-3">
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold" for="harian_tenaga">Tenaga Kerja (Jumlah/Keahlian)</label>
                                                    <textarea name="tenaga_kerja" id="harian_tenaga" class="form-control" rows="2" placeholder="e.g. 5 Tukang Batu, 2 Pekerja"></textarea>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold" for="harian_peralatan">Peralatan yang Digunakan</label>
                                                    <textarea name="peralatan" id="harian_peralatan" class="form-control" rows="2" placeholder="e.g. 1 Excavator, 2 Molen"></textarea>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold" for="harian_kendala">Kendala / Hambatan Lapangan</label>
                                                <input type="text" name="kendala" id="harian_kendala" class="form-control" placeholder="e.g. Hujan lebat 3 jam, material terlambat">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold" for="harian_keterangan">Justifikasi / Keterangan Lonjakan (Wajib jika progres melebihi rencana target)</label>
                                                <textarea name="keterangan" id="harian_keterangan" class="form-control" rows="2" placeholder="e.g. Penambahan tenaga kerja 2 shift, cuaca sangat mendukung percepatan pekerjaan..."></textarea>
                                            </div>
                                            
                                            <!-- Mandatory Foto Upload Field Guard -->
                                            <div class="mb-3 p-2 bg-light rounded border border-light">
                                                <label class="form-label small fw-bold text-primary mb-1"><i class="bx bx-camera me-1"></i> Unggah Dokumentasi Lapangan (Wajib 3 Foto)</label>
                                                <div class="row g-1">
                                                    <div class="col-4">
                                                        <span class="d-block text-muted text-center" style="font-size: 0.65rem; margin-bottom: 2px;">1. Kondisi Awal</span>
                                                        <input type="file" name="foto_awal" class="form-control form-control-sm" accept="image/*">
                                                    </div>
                                                    <div class="col-4">
                                                        <span class="d-block text-muted text-center" style="font-size: 0.65rem; margin-bottom: 2px;">2. Proses</span>
                                                        <input type="file" name="foto_proses" class="form-control form-control-sm" accept="image/*">
                                                    </div>
                                                    <div class="col-4">
                                                        <span class="d-block text-muted text-center" style="font-size: 0.65rem; margin-bottom: 2px;">3. Hasil</span>
                                                        <input type="file" name="foto_hasil" class="form-control form-control-sm" accept="image/*">
                                                    </div>
                                                </div>
                                                <div class="mt-1" id="edit_photo_reminder" style="display: none;">
                                                    <span class="small text-muted text-center d-block" style="font-size: 0.7rem;">*) Kosongkan foto jika tidak ingin mengganti dokumentasi lama</span>
                                                </div>
                                            </div>
                                            
                                            <div id="harian-warning-alert" class="alert alert-warning py-2 mb-3 border-0 small" style="display: none;"></div>
                                            
                                            <div class="d-flex gap-2">
                                                <button type="submit" id="btnHarianSubmit" class="btn btn-sm btn-primary w-100">
                                                    <i class="bx bx-save me-1"></i> Simpan Laporan
                                                </button>
                                                <button type="button" id="btnHarianCancel" class="btn btn-sm btn-label-secondary" style="display: none;" onclick="resetHarianForm()">
                                                    Batal
                                                </button>
                                            </div>
                                        </form>
                                        <?php else: ?>
                                            <div class="alert alert-info border-0 shadow-sm mt-3">
                                                <i class="bx bx-info-circle me-1"></i> Hanya PPK yang berwenang mengisi Laporan Progres Fisik Harian.
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Data Table Log Harian -->
                        <div class="col-lg-7">
                            <div class="card border border-light">
                                <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="fw-bold mb-0 text-dark"><i class="bx bx-list-ul me-1"></i> Riwayat Progres Pekerjaan Lapangan</h6>
                                    <div class="d-flex gap-2">
                                        <select class="form-select form-select-sm" id="filterBulan" onchange="filterLaporan()">
                                            <option value="all">Semua Waktu</option>
                                            <option value="this_month">Bulan Ini</option>
                                            <option value="last_month">Bulan Lalu</option>
                                        </select>
                                        <button class="btn btn-sm btn-outline-primary" onclick="cetakLaporanEksekutif()"><i class="bx bx-printer me-1"></i> Cetak PDF</button>
                                    </div>
                                </div>
                                <div class="table-responsive" style="max-height: 580px;" id="areaLaporanCetak">
                                    <table class="table table-hover table-striped align-middle mb-0" style="font-size: 0.8rem;" id="tabelLogbook">
                                        <thead class="bg-light sticky-top">
                                            <tr>
                                                <th class="col-tanggal">Tanggal</th>
                                                <th>Item Pekerjaan</th>
                                                <th>Realisasi (%)</th>
                                                <th class="col-dokumentasi">Dokumentasi</th>
                                                <th class="no-print">Status</th>
                                                <th class="no-print">Verifikasi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($harianItems)): ?>
                                                <tr>
                                                    <td colspan="6" class="text-center py-4 text-muted">
                                                        Belum ada laporan progres lapangan yang diinput.
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($harianItems as $item): ?>
                                                    <tr class="log-row" data-tanggal="<?= $item['tanggal'] ?>">
                                                        <td class="col-tanggal"><strong><?= date('d/m/Y', strtotime($item['tanggal'])) ?></strong></td>
                                                        <td>
                                                            <strong><?= esc($item['item_pekerjaan']) ?></strong>
                                                            <div class="text-muted" style="font-size: 0.72rem;">
                                                                <i class="bx bx-map text-secondary me-1"></i>Lokasi: <?= esc($item['lokasi']) ?>
                                                                <?php if (!empty($item['kendala'])): ?>
                                                                    <br><span class="text-warning"><i class="bx bx-error me-1"></i>Hambatan: <?= esc($item['kendala']) ?></span>
                                                                <?php endif; ?>
                                                                <?php if (!empty($item['keterangan'])): ?>
                                                                    <br><span class="text-success"><i class="bx bx-check-circle me-1"></i>Justifikasi: <?= esc($item['keterangan']) ?></span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-label-primary fw-bold"><?= number_format($item['progres_persen'], 2) ?>%</span>
                                                            <div class="text-muted" style="font-size: 0.72rem;"><?= esc($item['volume_selesai']) ?> <?= esc($item['satuan']) ?></div>
                                                        </td>
                                                        <td class="col-dokumentasi">
                                                            <!-- Photo Thumbnail Gallery trigger -->
                                                            <div class="d-flex gap-1 print-photo-gallery">
                                                                <?php if ($item['foto_awal']): ?>
                                                                    <img src="<?= base_url($item['foto_awal']) ?>" class="rounded cursor-pointer border hover-zoom-img print-img" style="width: 25px; height: 25px; object-fit: cover;" onclick="viewFieldPhotos('<?= base_url($item['foto_awal']) ?>', '<?= base_url($item['foto_proses']) ?>', '<?= base_url($item['foto_hasil']) ?>')">
                                                                <?php endif; ?>
                                                                <?php if ($item['foto_proses']): ?>
                                                                    <img src="<?= base_url($item['foto_proses']) ?>" class="rounded cursor-pointer border hover-zoom-img print-img" style="width: 25px; height: 25px; object-fit: cover;" onclick="viewFieldPhotos('<?= base_url($item['foto_awal']) ?>', '<?= base_url($item['foto_proses']) ?>', '<?= base_url($item['foto_hasil']) ?>')">
                                                                <?php endif; ?>
                                                                <?php if ($item['foto_hasil']): ?>
                                                                    <img src="<?= base_url($item['foto_hasil']) ?>" class="rounded cursor-pointer border hover-zoom-img print-img" style="width: 25px; height: 25px; object-fit: cover;" onclick="viewFieldPhotos('<?= base_url($item['foto_awal']) ?>', '<?= base_url($item['foto_proses']) ?>', '<?= base_url($item['foto_hasil']) ?>')">
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                        <td class="no-print">
                                                            <span class="badge rounded-pill bg-<?= $item['is_verified'] == 1 ? 'success' : 'warning' ?> p-1 px-2" style="font-size: 0.65rem;">
                                                                <?= $item['is_verified'] == 1 ? 'Verified' : 'Pending' ?>
                                                            </span>
                                                        </td>
                                                        <td class="no-print">
                                                            <div class="d-flex gap-1">
                                                                <!-- Verify validation by Konsultan Pengawas toggle -->
                                                                <?php if (in_array(session()->get('role'), ['operator', 'admin'])): ?>
                                                                <button class="btn btn-xs btn-outline-success p-1 px-2" style="font-size: 0.65rem;" onclick="verifyHarian(<?= $item['id'] ?>)">
                                                                    <i class="bx bx-check-shield"></i> Validasi
                                                                </button>
                                                                <?php endif; ?>
                                                                
                                                                <?php if (in_array(session()->get('role'), ['ppk', 'admin', 'operator'])): ?>
                                                                    <button class="btn btn-xs btn-outline-secondary p-1 px-2" style="font-size: 0.65rem;" onclick="editHarian(<?= htmlspecialchars(json_encode($item)) ?>)">
                                                                        <i class="bx bx-edit-alt"></i>
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
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PANEL C: REKAPITULASI & MC -->
                <div class="tab-pane fade" id="panel-rekap" role="tabpanel">
                    <div class="card border border-light mb-4">
                        <div class="card-header bg-light py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <h6 class="fw-bold mb-0 text-dark"><i class="bx bx-spreadsheet me-1"></i> Rekapitulasi Deviasi Mingguan Rencana vs Realisasi</h6>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary" onclick="printBeritaAcara()"><i class="bx bx-printer me-1"></i> Cetak Berita Acara (BA)</button>
                                <button class="btn btn-sm btn-primary" onclick="openMonthlyMc()"><i class="bx bx-badge-check me-1"></i> Tagihan MC (Monthly Certificate)</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle mb-0" style="font-size: 0.82rem;">
                                <thead class="bg-light text-center">
                                    <tr>
                                        <th rowspan="2">Minggu Pekerjaan</th>
                                        <th colspan="2">Progres Kumulatif (%)</th>
                                        <th rowspan="2">Deviasi (%)</th>
                                        <th rowspan="2">Status Kinerja</th>
                                        <th rowspan="2" style="width: 35%;">Analisis Hambatan & Rencana Tindak Lanjut</th>
                                    </tr>
                                    <tr>
                                        <th>Rencana</th>
                                        <th>Realisasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($scurve['labels'])): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">Data rekapitulasi belum terhitung. Isi baseline Kurva S terlebih dahulu.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php 
                                            $savedWeeklyAi = [];
                                            if (!empty($activeProyek['weekly_ai_analysis'])) {
                                                $savedWeeklyAi = json_decode($activeProyek['weekly_ai_analysis'], true) ?: [];
                                            }
                                            
                                            for ($i = 0; $i < count($scurve['labels']); $i++): 
                                                $planVal = $scurve['plan'][$i];
                                                $actVal = isset($scurve['actual'][$i]) ? $scurve['actual'][$i] : null;
                                                $devVal = isset($scurve['deviasi'][$i]) ? $scurve['deviasi'][$i] : null;
                                                
                                                if ($actVal === null) continue; // Hide future weeks
                                                
                                                $weekNum = $i + 1;
                                                $hasSaved = isset($savedWeeklyAi[$weekNum]);
                                                $savedHambatan = $hasSaved ? ($savedWeeklyAi[$weekNum]['hambatan'] ?? '') : '';
                                                $savedTindakLanjut = $hasSaved ? ($savedWeeklyAi[$weekNum]['tindak_lanjut'] ?? '') : '';
                                        ?>
                                            <tr class="text-center">
                                                <td class="fw-bold text-dark">Minggu ke-<?= $i + 1 ?></td>
                                                <td><?= number_format($planVal, 2) ?>%</td>
                                                <td><span class="badge bg-label-primary fw-bold"><?= number_format($actVal, 2) ?>%</span></td>
                                                <td class="fw-bold <?= $devVal < 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($devVal, 2) ?>%</td>
                                                <td>
                                                    <?php if ($devVal <= -10.0): ?>
                                                        <span class="badge bg-danger rounded-pill">Kritis (Merah)</span>
                                                    <?php elseif ($devVal <= -2.0): ?>
                                                        <span class="badge bg-warning rounded-pill">Lambat (Kuning)</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success rounded-pill">Normal (Hijau)</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-start" id="ai_analysis_td_<?= $i + 1 ?>">
                                                    <?php if ($devVal <= -2.0): ?>
                                                        <div class="small mb-1" id="analysis_content_<?= $i + 1 ?>">
                                                            <strong class="text-danger">Analisis Hambatan:</strong> 
                                                            <span class="text-muted" id="ai_hambatan_<?= $i + 1 ?>"><?= $hasSaved ? esc($savedHambatan) : 'Pekerjaan terganggu kendala lapangan/tenaga kerja kurang.' ?></span><br>
                                                            <strong class="text-primary">Rencana Tindak Lanjut:</strong> 
                                                            <span class="text-muted" id="ai_tindak_lanjut_<?= $i + 1 ?>"><?= $hasSaved ? esc($savedTindakLanjut) : 'Mobilisasi tenaga kerja tambahan &amp; material cadangan dipercepat.' ?></span>
                                                        </div>
                                                        <?php if (in_array(session()->get('role'), ['ppk', 'admin', 'operator'])): ?>
                                                            <?php if ($hasSaved): ?>
                                                                <button class="btn btn-xs btn-label-success py-1 px-2 mt-1 shadow-sm" type="button" onclick="generateWeeklyAiAnalysis(<?= $activeProyek['id'] ?>, <?= $i + 1 ?>, <?= $devVal ?>)" id="btn_ai_<?= $i + 1 ?>">
                                                                    <i class="bx bx-check-circle me-1"></i> Teroptimalkan AI AJIP (Picu Ulang)
                                                                </button>
                                                            <?php else: ?>
                                                                <button class="btn btn-xs btn-label-primary py-1 px-2 mt-1 shadow-sm" type="button" onclick="generateWeeklyAiAnalysis(<?= $activeProyek['id'] ?>, <?= $i + 1 ?>, <?= $devVal ?>)" id="btn_ai_<?= $i + 1 ?>">
                                                                    <i class="bx bx-bot me-1"></i> Optimalkan dengan AI AJIP
                                                                </button>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <?php if ($hasSaved): ?>
                                                                <span class="badge bg-label-success py-1 px-2 mt-1"><i class="bx bx-check-circle me-1"></i> Dioptimalkan AI AJIP</span>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-success small"><i class="bx bx-check-circle me-1"></i> Progres aman dan sesuai baseline rencana kerja.</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endfor; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- PANEL D: SETUP & BASELINE (KURVA S) - Per-Week Column Grid -->
                <div class="tab-pane fade" id="panel-jadwal" role="tabpanel">
                    <div class="row g-4">
                        <!-- Project Details Info Form -->
                        <div class="col-lg-12">
                            <div class="card border border-light">
                                <div class="card-header bg-light py-3">
                                    <h6 class="fw-bold mb-0 text-dark"><i class="bx bx-slider me-1"></i> Edit Informasi Kontrak Proyek</h6>
                                </div>
                                <div class="card-body py-3">
                                    <form id="formEditProyekMonev">
                                        <?= csrf_field() ?>
                                        <fieldset <?= !in_array(session()->get('role'), ['ppk', 'admin', 'operator']) ? 'disabled' : '' ?>>
                                        <input type="hidden" name="id" value="<?= $activeProyek['id'] ?>">
                                        <input type="hidden" name="kd_rup" value="<?= $activeProyek['kd_rup'] ?>">
                                        <input type="hidden" name="status" value="<?= $activeProyek['status'] ?>">
                                        
                                        <div class="row g-3">
                                            <div class="col-md-6 col-lg-3">
                                                <label class="form-label small fw-bold">Nama Proyek</label>
                                                <input type="text" name="nama_proyek" class="form-control form-control-sm" value="<?= esc($activeProyek['nama_proyek']) ?>" required>
                                            </div>
                                            <div class="col-md-6 col-lg-3">
                                                <label class="form-label small fw-bold">Nomor Kontrak</label>
                                                <input type="text" name="no_kontrak" class="form-control form-control-sm" value="<?= esc($activeProyek['no_kontrak']) ?>" required>
                                            </div>
                                            <div class="col-md-6 col-lg-2">
                                                <label class="form-label small fw-bold">Nilai Kontrak (Rp)</label>
                                                <input type="number" name="nilai_kontrak" class="form-control form-control-sm" value="<?= $activeProyek['nilai_kontrak'] ?>" required>
                                            </div>
                                            <div class="col-md-3 col-lg-2">
                                                <label class="form-label small fw-bold">Tanggal Mulai</label>
                                                <input type="date" name="tgl_mulai" id="edit_tgl_mulai" class="form-control form-control-sm" value="<?= $activeProyek['tgl_mulai_kontrak'] ?>" required>
                                            </div>
                                            <div class="col-md-3 col-lg-2">
                                                <label class="form-label small fw-bold">Tanggal Selesai</label>
                                                <input type="date" name="tgl_selesai" id="edit_tgl_selesai" class="form-control form-control-sm" value="<?= $activeProyek['tgl_selesai_kontrak'] ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="row g-3 mt-1">
                                            <div class="col-md-3">
                                                <label class="form-label small fw-bold">Nama Penyedia</label>
                                                <input type="text" name="nama_penyedia" class="form-control form-control-sm" value="<?= esc($activeProyek['nama_penyedia']) ?>" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small fw-bold">Konsultan Pengawas</label>
                                                <input type="text" name="konsultan_pengawas" class="form-control form-control-sm" value="<?= esc($activeProyek['konsultan_pengawas']) ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small fw-bold">PPHP</label>
                                                <input type="text" name="pphp" class="form-control form-control-sm" value="<?= esc($activeProyek['pphp']) ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small fw-bold">Direksi Teknis</label>
                                                <input type="text" name="direksi_teknis" class="form-control form-control-sm" value="<?= esc($activeProyek['direksi_teknis']) ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="row g-3 mt-1">
                                            <div class="col-md-4 col-lg-3">
                                                <label class="form-label small fw-bold">PPK (Pejabat Pembuat Komitmen)</label>
                                                <input type="text" name="ppk" class="form-control form-control-sm" value="<?= esc($activeProyek['ppk'] ?? '') ?>" placeholder="e.g. Budi Santoso, ST.">
                                            </div>
                                            <div class="col-md-4 col-lg-3">
                                                <label class="form-label small fw-bold">Lokasi Proyek</label>
                                                <input type="text" name="lokasi" class="form-control form-control-sm" value="<?= esc($activeProyek['lokasi'] ?? '') ?>" placeholder="e.g. Kecamatan X, Kabupaten Y">
                                            </div>
                                            <div class="col-md-4 col-lg-2">
                                                <label class="form-label small fw-bold">Sumber Dana</label>
                                                <input type="text" name="sumber_dana" class="form-control form-control-sm" value="<?= esc($activeProyek['sumber_dana'] ?? '') ?>" placeholder="e.g. APBD / DAK 2026">
                                            </div>
                                            <div class="col-md-6 col-lg-2">
                                                <label class="form-label small fw-bold">Jenis Pekerjaan</label>
                                                <input type="text" name="jenis_pekerjaan" class="form-control form-control-sm" value="<?= esc($activeProyek['jenis_pekerjaan'] ?? '') ?>" placeholder="e.g. Konstruksi Jalan">
                                            </div>
                                            <div class="col-md-6 col-lg-2">
                                                <label class="form-label small fw-bold">Metode Kontrak</label>
                                                <input type="text" name="metode_kontrak" class="form-control form-control-sm" value="<?= esc($activeProyek['metode_kontrak'] ?? '') ?>" placeholder="e.g. Lumpsum / Harga Satuan">
                                            </div>
                                        </div>

                                        <?php if (!empty($activeProyek['kd_rup'])): ?>
                                        <div class="row g-3 mt-1 border-top pt-3 tender-edit-section">
                                            <div class="col-md-6 col-lg-3">
                                                <label class="form-label small fw-bold text-primary">Nomor SPMK / SPP</label>
                                                <input type="text" name="no_spmk_spp" class="form-control form-control-sm border-primary text-primary fw-bold" value="<?= esc($activeProyek['no_spmk_spp'] ?? '') ?>" placeholder="e.g. Nomor SPMK">
                                            </div>
                                            <div class="col-md-6 col-lg-3">
                                                <label class="form-label small fw-bold text-primary">Waktu Penyelesaian (Hari)</label>
                                                <input type="number" name="waktu_penyelesaian" id="edit_waktu_penyelesaian" class="form-control form-control-sm border-primary text-primary fw-bold" value="<?= esc($activeProyek['waktu_penyelesaian'] ?? '') ?>" placeholder="e.g. 120">
                                            </div>
                                            <div class="col-md-6 col-lg-3">
                                                <label class="form-label small fw-bold text-primary">Tanggal Mulai Pekerjaan (SPMK)</label>
                                                <input type="date" name="tgl_mulai_pekerjaan" id="edit_tgl_mulai_pekerjaan" class="form-control form-control-sm border-primary text-primary fw-bold" value="<?= esc($activeProyek['tgl_mulai_pekerjaan'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-6 col-lg-3">
                                                <label class="form-label small fw-bold text-primary">Tanggal Selesai Pekerjaan (SPMK)</label>
                                                <input type="date" name="tgl_selesai_pekerjaan" id="edit_tgl_selesai_pekerjaan" class="form-control form-control-sm border-primary text-primary fw-bold" value="<?= esc($activeProyek['tgl_selesai_pekerjaan'] ?? '') ?>">
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <div class="row g-3 mt-1">
                                            <div class="col-md-6">
                                                <label class="form-label small fw-bold">GPS Koordinat</label>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="gps_kordinat" id="edit_gps" class="form-control form-control-sm" value="<?= esc($activeProyek['gps_kordinat'] ?? '') ?>" placeholder="-8.1234, 116.1234">
                                                    <button class="btn btn-outline-primary btn-sm" type="button" id="btn_pick_edit_map" onclick="toggleEditMapPicker()"><i class="bx bx-map"></i> Buka Peta &amp; Gambar</button>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                    <label class="form-label small fw-bold mb-0">Polygon Lokasi (GeoJSON) <span id="edit_polygon_measure_badge" class="badge bg-label-dark ms-2" style="display:none;"></span></label>
                                                    <div>
                                                        <input type="file" id="import_edit_geojson_file" class="d-none" accept=".geojson,.json" onchange="handleEditGeoJsonImport(event)">
                                                        <button class="btn btn-xs btn-outline-primary" type="button" onclick="document.getElementById('import_edit_geojson_file').click()"><i class="bx bx-upload me-1"></i> Unggah File GeoJSON</button>
                                                    </div>
                                                </div>
                                                <textarea name="polygon_lokasi" id="edit_polygon" class="form-control form-control-sm" rows="1" placeholder='{"type": "Feature", "geometry": {"type": "Polygon", "coordinates": [...]}}' oninput="syncEditPolygonFromText()"><?= esc($activeProyek['polygon_lokasi'] ?? '') ?></textarea>
                                            </div>
                                        </div>

                                        <div id="edit_map_picker_container" style="display:none; margin-top: 1rem; margin-bottom: 1rem;">
                                            <div class="border rounded p-2 bg-light">
                                                <div id="edit_map_picker" style="height: 350px; width: 100%; border-radius: 4px; border: 1px solid #ddd; z-index:1;"></div>
                                                <small class="text-muted mt-2 d-block text-center fw-semibold"><i class="bx bx-info-circle"></i> Gunakan alat gambar di kiri peta untuk menggambar polygon/polyline wilayah proyek, atau klik titik koordinat.</small>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3 text-end">
                                            <?php if (in_array(session()->get('role'), ['ppk', 'admin', 'operator'])): ?>
                                            <button type="submit" class="btn btn-sm btn-primary px-4 shadow-sm"><i class="bx bx-save me-1"></i> Perbarui Kontrak</button>
                                            <?php endif; ?>
                                        </div>
                                        </fieldset>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Schedule Baseline: Per-Week Grid Table (like Kurva S image) -->
                        <div class="col-lg-12">
                            <div class="card border border-light">
                                <div class="card-header bg-light py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark"><i class="bx bx-grid me-1"></i> Setup Baseline Kurva S — Rencana Waktu Pelaksanaan</h6>
                                        <small class="text-muted">Isi bobot tiap pekerjaan per minggu (nilai progress per minggu itu, bukan kumulatif). Kolom kosong = tidak ada pekerjaan di minggu tersebut.</small>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <!-- Total Bobot Indicator -->
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="small fw-semibold text-muted">Bobot:</span>
                                            <span id="jadwal-bobot-indicator" class="badge bg-danger p-2 shadow-sm">0.00%</span>
                                        </div>
                                        <div class="vr d-none d-md-block" style="height:24px;"></div>
                                        <?php if (in_array(session()->get('role'), ['ppk', 'admin', 'operator'])): ?>
                                        <!-- Download Template -->
                                        <button type="button" class="btn btn-sm btn-outline-success" onclick="downloadJadwalTemplate()" title="Download template CSV untuk diisi offline">
                                            <i class="bx bx-download me-1"></i> Download Template
                                        </button>
                                        <!-- Upload Template -->
                                        <div class="position-relative">
                                            <input type="file" id="jadwal_csv_upload" accept=".csv" class="d-none" onchange="uploadJadwalTemplate(this)">
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('jadwal_csv_upload').click()">
                                                <i class="bx bx-upload me-1"></i> Tarik dari CSV Template
                                            </button>
                                        </div>
                                        <?php endif; ?>
                                        <div class="vr d-none d-md-block" style="height:24px;"></div>
                                        <!-- Add Row -->
                                        <?php if (in_array(session()->get('role'), ['ppk', 'admin', 'operator'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addJadwalRow()">
                                                <i class="bx bx-plus me-1"></i> Tambah Item Pekerjaan
                                            </button>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-sm btn-outline-info d-md-none" onclick="toggleFullscreenTable()">
                                            <i class="bx bx-fullscreen me-1"></i> Lihat Tabel Jadwal
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body py-3 px-2">
                                    <?php
                                        // Calculate total weeks from contract dates
                                        $tglMulaiKontrak = strtotime($activeProyek['tgl_mulai']);
                                        $tglSelesaiKontrak = strtotime($activeProyek['tgl_selesai']);
                                        $durasiHari = max(1, ($tglSelesaiKontrak - $tglMulaiKontrak) / 86400 + 1);
                                        $totalMingguKontrak = max(1, ceil($durasiHari / 7));
                                        
                                        // Build month grouping headers
                                        $monthGroups = [];
                                        for ($w = 1; $w <= $totalMingguKontrak; $w++) {
                                            $weekStart = $tglMulaiKontrak + ($w - 1) * 7 * 86400;
                                            $bulan = date('M\'y', $weekStart);
                                            if (!isset($monthGroups[$bulan])) {
                                                $monthGroups[$bulan] = ['start' => $w, 'count' => 0];
                                            }
                                            $monthGroups[$bulan]['count']++;
                                        }
                                    ?>
                                    <form id="formJadwalMonev">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="proyek_id" value="<?= $activeProyek['id'] ?>">
                                        <input type="hidden" name="total_minggu" value="<?= $totalMingguKontrak ?>">
                                        
                                        <style>
                                            @media (max-width: 768px) {
                                                .mobile-static { position: static !important; }
                                                .mobile-min-w { min-width: 150px !important; }
                                                .week-input { min-width: 45px !important; }
                                            }
                                        </style>
                                        <div class="table-responsive d-none d-md-block" id="jadwalTableContainer">
                                            <table class="table table-bordered align-middle mb-0" id="tableJadwalMonev" style="font-size: 0.73rem; min-width: <?= max(800, 220 + $totalMingguKontrak * 52) ?>px;">
                                                <thead>
                                                    <!-- Row 1: Month headers -->
                                                    <tr class="text-center" style="background:#1e293b; color:#fff;">
                                                        <th rowspan="2" class="align-middle text-start mobile-min-w" style="min-width:180px; position:sticky; left:0; background:#1e293b; z-index:2;">No &nbsp; Uraian Pekerjaan</th>
                                                        <th rowspan="2" class="align-middle mobile-static" style="width:110px; min-width:110px; position:sticky; left:180px; background:#1e293b; z-index:2;">Bobot (%)</th>
                                                        <?php foreach ($monthGroups as $bulan => $grp): ?>
                                                        <th colspan="<?= $grp['count'] ?>" style="border-left: 2px solid #4a5568; font-size:0.75rem; letter-spacing:0.5px;"><?= $bulan ?></th>
                                                        <?php endforeach; ?>
                                                        <th rowspan="2" class="align-middle mobile-static" style="width:55px; position:sticky; right:0; background:#1e293b; z-index:2;">Aksi</th>
                                                    </tr>
                                                    <!-- Row 2: Week numbers -->
                                                    <tr class="text-center" style="background:#334155; color:#e2e8f0; font-size:0.68rem;">
                                                        <?php for ($w = 1; $w <= $totalMingguKontrak; $w++): 
                                                            $isMonthBorder = false;
                                                            $cumCount = 0;
                                                            foreach ($monthGroups as $grp2) {
                                                                $cumCount += $grp2['count'];
                                                                if ($cumCount == $w - 1) { $isMonthBorder = true; break; }
                                                            }
                                                        ?>
                                                        <th style="width:52px; min-width:52px; <?= $isMonthBorder ? 'border-left: 2px solid #4a5568;' : '' ?>">W<?= $w ?></th>
                                                        <?php endfor; ?>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($jadwalItems)): ?>
                                                        <!-- Empty initial row -->
                                                        <tr class="jadwal-row" data-row="0">
                                                            <td class="mobile-min-w" style="position:sticky; left:0; background:#fff; z-index:1;">
                                                                <div class="d-flex align-items-center gap-1">
                                                                    <span class="text-muted row-num small fw-bold">1.</span>
                                                                    <input type="text" name="item_pekerjaan[]" class="form-control form-control-sm border-0 shadow-none" style="min-width: 120px;" placeholder="e.g. Pekerjaan Persiapan" required>
                                                                </div>
                                                            </td>
                                                            <td class="mobile-static" style="position:sticky; left:180px; background:#fff; z-index:1;">
                                                                <input type="number" step="0.01" name="bobot[]" class="form-control form-control-sm jadwal-bobot-input text-center border-primary" placeholder="0.00" required min="0.01" max="100">
                                                            </td>
                                                            <?php for ($w = 0; $w < $totalMingguKontrak; $w++): ?>
                                                            <td class="p-1 text-center week-cell">
                                                                <input type="number" step="0.01" 
                                                                       name="week_val[0][<?= $w ?>]" 
                                                                       class="form-control form-control-sm text-center week-input p-0 border-0 shadow-none" 
                                                                       placeholder="" min="0" max="100"
                                                                       style="width:48px; font-size:0.68rem; background:transparent;"
                                                                       oninput="onWeekInputChange(this)">
                                                            </td>
                                                            <?php endfor; ?>
                                                            <td class="text-center mobile-static" style="position:sticky; right:0; background:#fff; z-index:1;">
                                                                <button type="button" class="btn btn-xs btn-outline-danger" onclick="deleteJadwalRow(this)"><i class="bx bx-trash"></i></button>
                                                            </td>
                                                        </tr>
                                                    <?php else: ?>
                                                        <?php foreach ($jadwalItems as $rowIdx => $j): 
                                                            $existingWeeks = json_decode($j['target_mingguan'] ?? '[]', true);
                                                            if (!is_array($existingWeeks)) $existingWeeks = [];
                                                            // Convert cumulative item percentage (0-100) to per-week delta bobot for display
                                                            $perWeekVals = [];
                                                            $prevCum = 0;
                                                            foreach ($existingWeeks as $cumVal) {
                                                                $deltaPercent = $cumVal - $prevCum;
                                                                $weightedVal = ($deltaPercent * $j['bobot']) / 100;
                                                                // To prevent floating point issues like 0.00 showing up
                                                                $perWeekVals[] = $weightedVal > 0.005 ? round($weightedVal, 2) : 0;
                                                                $prevCum = $cumVal;
                                                            }
                                                        ?>
                                                        <tr class="jadwal-row" data-row="<?= $rowIdx ?>">
                                                            <td class="mobile-min-w" style="position:sticky; left:0; background:#fff; z-index:1;">
                                                                <div class="d-flex align-items-center gap-1">
                                                                    <span class="text-muted row-num small fw-bold"><?= $rowIdx + 1 ?>.</span>
                                                                    <input type="text" name="item_pekerjaan[]" class="form-control form-control-sm border-0 shadow-none" style="min-width: 120px;" value="<?= esc($j['item_pekerjaan']) ?>" required>
                                                                </div>
                                                            </td>
                                                            <td class="mobile-static" style="position:sticky; left:180px; background:#fff; z-index:1;">
                                                                <input type="number" step="0.01" name="bobot[]" class="form-control form-control-sm jadwal-bobot-input text-center border-primary" value="<?= esc($j['bobot']) ?>" required min="0.01" max="100">
                                                            </td>
                                                            <?php for ($w = 0; $w < $totalMingguKontrak; $w++): 
                                                                $wVal = isset($perWeekVals[$w]) && $perWeekVals[$w] > 0 ? $perWeekVals[$w] : '';
                                                            ?>
                                                            <td class="p-1 text-center week-cell" style="<?= $wVal !== '' ? 'background:rgba(99,102,241,0.08);' : '' ?>">
                                                                <input type="number" step="0.01"
                                                                       name="week_val[<?= $rowIdx ?>][<?= $w ?>]"
                                                                       class="form-control form-control-sm text-center week-input p-0 border-0 shadow-none"
                                                                       value="<?= $wVal ?>"
                                                                       placeholder="" min="0" max="100"
                                                                       style="width:48px; font-size:0.68rem; background:transparent; color:<?= $wVal !== '' ? '#4338ca' : 'inherit' ?>; font-weight:<?= $wVal !== '' ? '700' : '400' ?>;"
                                                                       oninput="onWeekInputChange(this)">
                                                            </td>
                                                            <?php endfor; ?>
                                                            <?php if (in_array(session()->get('role'), ['ppk', 'admin', 'operator'])): ?>
                                                            <td class="text-center mobile-static" style="position:sticky; right:0; background:#fff; z-index:1;">
                                                                <button type="button" class="btn btn-xs btn-outline-danger" onclick="deleteJadwalRow(this)"><i class="bx bx-trash"></i></button>
                                                            </td>
                                                            <?php endif; ?>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                                <!-- Footer: Row summary per week -->
                                                <tfoot>
                                                    <tr class="text-center fw-bold" style="background:#fef9c3; font-size:0.68rem;">
                                                        <td colspan="2" style="position:sticky; left:0; background:#fef9c3; z-index:1; text-align:center; color:#92400e;">Progress Rencana (%)</td>
                                                        <?php for ($w = 0; $w < $totalMingguKontrak; $w++): ?>
                                                        <td id="foot-week-<?= $w ?>" class="text-center" style="color:#d97706;">—</td>
                                                        <?php endfor; ?>
                                                        <td style="position:sticky; right:0; background:#fef9c3; z-index:1;"></td>
                                                    </tr>
                                                    <tr class="text-center fw-bold" style="background:#fffbeb; font-size:0.68rem;">
                                                        <td colspan="2" style="position:sticky; left:0; background:#fffbeb; z-index:1; text-align:center; color:#92400e;">Total Kumulatif Rencana (%)</td>
                                                        <?php for ($w = 0; $w < $totalMingguKontrak; $w++): ?>
                                                        <td id="foot-cum-<?= $w ?>" class="text-center fw-bold" style="color:#b45309;">—</td>
                                                        <?php endfor; ?>
                                                        <td style="position:sticky; right:0; background:#fffbeb; z-index:1;"></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                        
                                        <?php if (in_array(session()->get('role'), ['ppk', 'admin', 'operator'])): ?>
                                        <div class="d-flex justify-content-end mt-3 gap-2">
                                            <button type="submit" id="btnJadwalSave" class="btn btn-sm btn-success px-4" disabled>
                                                <i class="bx bx-check me-1"></i> Simpan Baseline Rencana
                                            </button>
                                        </div>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PANEL E: DOKUMEN & ARSIP -->
                <div class="tab-pane fade" id="panel-dokumen" role="tabpanel">
                    <div class="row g-4">
                        <!-- Upload Form -->
                        <div class="col-lg-5">
                            <div class="card border border-light">
                                <div class="card-header bg-light py-3">
                                    <h6 class="fw-bold mb-0 text-dark"><i class="bx bx-cloud-upload me-1"></i> Upload Dokumen Proyek Baru</h6>
                                </div>
                                <div class="card-body pt-3">
                                    <?php if (in_array(session()->get('role'), ['ppk', 'admin', 'operator'])): ?>
                                    <form action="<?= base_url('dashboard/monev/upload-dokumen') ?>" method="POST" enctype="multipart/form-data">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="proyek_id" value="<?= $activeProyek['id'] ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Nama / Keterangan Dokumen</label>
                                            <input type="text" name="nama_dokumen" class="form-control" placeholder="e.g. Dokumen Kontrak Perjanjian Addendum I" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Kategori Dokumen</label>
                                            <select name="kategori" class="form-select" required>
                                                <option value="Kontrak">Kontrak Kerja & SPMK</option>
                                                <option value="MC">Monthly Certificate (MC) / Tagihan</option>
                                                <option value="Laporan Lapangan">Laporan Bulanan & Gambar (As Built)</option>
                                                <option value="Lainnya">Dokumen Lainnya</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Pilih File Dokumen (PDF, Excel, Zip)</label>
                                            <input type="file" name="file_dokumen" class="form-control" required accept=".pdf,.doc,.docx,.xls,.xlsx,.zip,.rar">
                                        </div>
                                        
                                        <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bx bx-upload me-1"></i> Upload Arsip</button>
                                    </form>
                                    <?php else: ?>
                                        <div class="alert alert-info border-0 shadow-sm">
                                            <i class="bx bx-info-circle me-1"></i> Hanya PPK yang berwenang mengupload dokumen proyek.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Document List -->
                        <div class="col-lg-7">
                            <div class="card border border-light">
                                <div class="card-header bg-light py-3">
                                    <h6 class="fw-bold mb-0 text-dark"><i class="bx bx-cabinet me-1"></i> Arsip Dokumen Digital Proyek</h6>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped align-middle mb-0" style="font-size: 0.8rem;">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Nama Dokumen</th>
                                                <th>Kategori</th>
                                                <th>Tanggal Unggah</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($dokumenItems)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center py-4 text-muted">Belum ada file dokumen pendukung yang diunggah.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($dokumenItems as $doc): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="fw-bold text-dark"><i class="bx bxs-file-pdf me-1 text-danger"></i> <?= esc($doc['nama_dokumen']) ?></div>
                                                        </td>
                                                        <td><span class="badge bg-label-secondary"><?= esc($doc['kategori']) ?></span></td>
                                                        <td><?= date('d M Y H:i', strtotime($doc['uploaded_at'])) ?></td>
                                                        <td class="text-center">
                                                            <a href="<?= base_url($doc['file_path']) ?>" class="btn btn-xs btn-outline-primary" download target="_blank">
                                                                <i class="bx bx-download"></i> Unduh
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- MODAL: SETUP NEW PROJECT (Claim from RUP / Manual) -->
<div class="modal fade" id="setupProyekModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title text-white fw-bold"><i class="bx bx-briefcase me-1"></i> Setup Paket Proyek PPK Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <form id="formSetupProyek" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="setup_proyek_id" value="">
                    
                    <!-- Pre-filling Claimed RUP selection -->
                    <div class="mb-3 p-3 bg-light rounded border shadow-sm">
                        <label class="form-label small fw-bold text-primary mb-2"><i class="bx bx-git-pull-request me-1"></i> Integrasi Paket SPSE (Pra-isi Otomatis)</label>
                        
                        <div class="mb-2">
                            <select id="select_rup_prefill" class="form-select border-primary" onchange="prefillPaketDetails(this.value)">
                                <option value="">-- Pilih dari Paket SPSE yang Telah Anda Klaim --</option>
                                <?php foreach ($rupPackages as $pkg): ?>
                                    <option value="<?= $pkg['kd_paket'] ?>">
                                        <?= esc($pkg['kd_paket']) ?> - <?= esc($pkg['nama_paket']) ?> (Rp <?= number_format($pkg['nilai_kontrak'], 0, ',', '.') ?>) [<?= esc($pkg['type']) ?>]
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="input-group input-group-sm mt-3 pt-2 border-top">
                            <span class="input-group-text bg-primary text-white border-primary" id="claim-addon"><i class="bx bx-plus-circle me-1"></i> Klaim Kode Paket SPSE</span>
                            <input type="text" class="form-control border-primary" id="input_claim_paket" placeholder="Masukkan Kode Paket SPSE (e.g. 10103894000)" aria-describedby="claim-addon">
                            <button class="btn btn-primary" type="button" id="btn_claim_paket" onclick="claimPaketNew()"><i class="bx bx-check-circle me-1"></i> Klaim & Muat</button>
                        </div>
                        
                        <span class="small text-muted d-block mt-2" style="font-size: 0.72rem;">*) Memilih salah satu akan mengimpor nama paket, pagu/nilai kontrak, dan nama penyedia secara instan. Jika Kode Paket belum diklaim, masukkan kode Paket SPSE Anda di atas lalu klik Klaim.</span>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Nama Proyek / Paket</label>
                            <input type="text" name="nama_proyek" id="setup_nama_proyek" class="form-control" required placeholder="e.g. Pembangunan Jembatan Gantung Desa A">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Kode RUP (Opsional)</label>
                            <input type="text" name="kd_rup" id="setup_kd_rup" class="form-control" placeholder="e.g. 3524673">
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nomor Surat Kontrak</label>
                            <input type="text" name="no_kontrak" id="setup_no_kontrak" class="form-control" required placeholder="e.g. 602.1/35/KONTRAK/DPU/2026">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nilai Kontrak Kerja (Rp)</label>
                            <input type="number" name="nilai_kontrak" id="setup_nilai_kontrak" class="form-control" required placeholder="e.g. 750000000">
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tanggal Mulai Kontrak</label>
                            <input type="date" name="tgl_mulai" id="setup_tgl_mulai" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tanggal Selesai Kontrak</label>
                            <input type="date" name="tgl_selesai" id="setup_tgl_selesai" class="form-control" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3 tender-only-fields" style="display: none;">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-primary">Nomor SPMK / SPP</label>
                            <input type="text" name="no_spmk_spp" id="setup_no_spmk_spp" class="form-control border-primary text-primary fw-bold" placeholder="e.g. 602.1/35/SPMK/DPU/2026">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-primary">Waktu Penyelesaian (Hari Kalender)</label>
                            <input type="number" name="waktu_penyelesaian" id="setup_waktu_penyelesaian" class="form-control border-primary text-primary fw-bold" placeholder="e.g. 120">
                        </div>
                    </div>

                    <div class="row g-3 mb-3 tender-only-fields" style="display: none;">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-primary">Tanggal Mulai Pekerjaan (SPMK)</label>
                            <input type="date" name="tgl_mulai_pekerjaan" id="setup_tgl_mulai_pekerjaan" class="form-control border-primary text-primary fw-bold">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-primary">Tanggal Selesai Pekerjaan (SPMK)</label>
                            <input type="date" name="tgl_selesai_pekerjaan" id="setup_tgl_selesai_pekerjaan" class="form-control border-primary text-primary fw-bold">
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nama Penyedia / Kontraktor</label>
                            <input type="text" name="nama_penyedia" id="setup_nama_penyedia" class="form-control" required placeholder="e.g. PT. Bangun Jaya Konstruksi">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Konsultan Pengawas</label>
                            <input type="text" name="konsultan_pengawas" id="setup_konsultan" class="form-control" placeholder="e.g. CV. Rekayasa Konsultan">
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Panitia PPHP</label>
                            <input type="text" name="pphp" id="setup_pphp" class="form-control" placeholder="e.g. Tim PPHP Satker DPU">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Direksi Teknis</label>
                            <input type="text" name="direksi_teknis" id="setup_direksi" class="form-control" placeholder="e.g. Bidang Bina Marga">
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">PPK (Pejabat Pembuat Komitmen)</label>
                            <input type="text" name="ppk" id="setup_ppk" class="form-control" placeholder="e.g. Budi Santoso, ST.">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Lokasi Proyek</label>
                            <input type="text" name="lokasi" id="setup_lokasi" class="form-control" placeholder="e.g. Kecamatan X, Kabupaten Y">
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Sumber Dana</label>
                            <input type="text" name="sumber_dana" id="setup_sumber_dana" class="form-control" placeholder="e.g. APBD / DAK 2026">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Jenis Pekerjaan</label>
                            <input type="text" name="jenis_pekerjaan" id="setup_jenis_pekerjaan" class="form-control" placeholder="e.g. Konstruksi Jalan">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Metode Kontrak</label>
                            <input type="text" name="metode_kontrak" id="setup_metode_kontrak" class="form-control" placeholder="e.g. Lumpsum / Harga Satuan">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">GPS Koordinat</label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="gps_kordinat" id="setup_gps" class="form-control" placeholder="-8.1234, 116.1234">
                                <button class="btn btn-outline-primary" type="button" id="btn_pick_map" title="Pilih dari Peta" onclick="toggleMapPicker()"><i class="bx bx-map"></i> Buka Peta Pencarian</button>
                            </div>
                        </div>
                    </div>

                    <div id="map_picker_container" style="display:none; margin-bottom: 1rem;">
                        <div class="border rounded p-2 bg-light">
                            <div id="map_picker" style="height: 350px; width: 100%; border-radius: 4px; border: 1px solid #ddd; z-index:1;"></div>
                            <small class="text-muted mt-2 d-block text-center fw-semibold"><i class="bx bx-info-circle"></i> Geser peta, cari lokasi, atau klik langsung pada area proyek untuk mengambil titik koordinat otomatis.</small>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label class="form-label small fw-bold mb-0">Polygon Lokasi (GeoJSON / Batas Wilayah) - Opsional <span id="polygon_measure_badge" class="badge bg-label-dark ms-2" style="display:none;"></span></label>
                                <div>
                                    <input type="file" id="import_geojson_file" class="d-none" accept=".geojson,.json" onchange="handleGeoJsonImport(event)">
                                    <button class="btn btn-xs btn-outline-primary" type="button" onclick="document.getElementById('import_geojson_file').click()"><i class="bx bx-upload me-1"></i> Unggah File GeoJSON</button>
                                </div>
                            </div>
                            <textarea name="polygon_lokasi" id="setup_polygon" class="form-control" rows="2" placeholder='{"type": "Feature", "geometry": {"type": "Polygon", "coordinates": [...]}}' oninput="syncPolygonFromText()"></textarea>
                            <small class="text-muted" style="font-size: 0.72rem;">*) Anda dapat menggambar batas wilayah (polygon/jalur jalan) langsung di peta satelit di atas setelah membuka peta pencarian, atau mengunggah file GeoJSON (.geojson / .json).</small>
                        </div>
                    </div>

                    <div class="row g-3 mb-4 p-3 bg-light rounded border shadow-sm mx-0">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-primary"><i class="bx bxs-file-pdf"></i> Upload File Kontrak (Opsional)</label>
                            <input type="file" name="file_kontrak" id="setup_file_kontrak" class="form-control" accept=".pdf,.doc,.docx,.zip">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-primary"><i class="bx bx-file"></i> Upload Dokumen MC (Opsional)</label>
                            <input type="file" name="dokumen_mc" id="setup_dokumen_mc" class="form-control" accept=".pdf,.doc,.docx,.zip,.xls,.xlsx">
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="button" class="btn btn-label-secondary me-2" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4"><i class="bx bx-check me-1"></i> Simpan Proyek</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: FIELD PHOTOS VIEW -->
<div class="modal fade" id="photosViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark"><i class="bx bx-image me-1"></i> Dokumentasi Lapangan Progres Fisik</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <!-- Carousel slides of photos -->
                <div class="row g-3 text-center">
                    <div class="col-md-4">
                        <h6 class="fw-bold mb-1 small text-muted">1. Kondisi Awal (0%)</h6>
                        <div class="border rounded overflow-hidden shadow-sm" style="height: 200px; background: #eee;">
                            <img id="photo_view_awal" src="" class="img-fluid w-100 h-100" style="object-fit: cover;">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <h6 class="fw-bold mb-1 small text-muted">2. Proses Pengerjaan</h6>
                        <div class="border rounded overflow-hidden shadow-sm" style="height: 200px; background: #eee;">
                            <img id="photo_view_proses" src="" class="img-fluid w-100 h-100" style="object-fit: cover;">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <h6 class="fw-bold mb-1 small text-muted">3. Hasil / Capaian</h6>
                        <div class="border rounded overflow-hidden shadow-sm" style="height: 200px; background: #eee;">
                            <img id="photo_view_hasil" src="" class="img-fluid w-100 h-100" style="object-fit: cover;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: RINGKASAN & KONFIRMASI LAPORAN HARIAN -->
<div class="modal fade" id="confirmHarianModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white py-3">
                <h5 class="modal-title text-white fw-bold"><i class="bx bx-check-double me-1"></i> Ringkasan & Konfirmasi Laporan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <p class="text-muted small mb-3">Mohon periksa kembali ringkasan progres harian Anda sebelum dikirimkan ke sistem:</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered small">
                        <tbody>
                            <tr>
                                <th class="bg-light" style="width: 40%;">Item Pekerjaan</th>
                                <td id="summary_harian_item" class="text-dark fw-bold"></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Tanggal Kerja</th>
                                <td id="summary_harian_tanggal" class="text-dark"></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Progres Kumulatif</th>
                                <td id="summary_harian_progres" class="text-dark fw-bold text-success"></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Volume Realisasi</th>
                                <td id="summary_harian_volume" class="text-dark"></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Lokasi Pekerjaan</th>
                                <td id="summary_harian_lokasi" class="text-dark"></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Tenaga Kerja</th>
                                <td id="summary_harian_tenaga" class="text-dark"></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Peralatan</th>
                                <td id="summary_harian_peralatan" class="text-dark"></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Hambatan/Kendala</th>
                                <td id="summary_harian_kendala" class="text-warning"></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Justifikasi Lonjakan</th>
                                <td id="summary_harian_justifikasi" class="text-success fw-semibold"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">Kembali & Ubah</button>
                <button type="button" id="btnHarianFinalSubmit" class="btn btn-success btn-sm"><i class="bx bx-send me-1"></i> Ya, Kirim Laporan</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: RINGKASAN & KONFIRMASI BASELINE SCHEDULE -->
<div class="modal fade" id="confirmJadwalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark py-3">
                <h5 class="modal-title fw-bold text-dark"><i class="bx bx-list-check me-1"></i> Ringkasan Baseline Kurva S</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <p class="text-muted small mb-3">Berikut adalah ringkasan pembobotan pekerjaan yang akan disimpan. Harap pastikan pembagian bobot tepat 100%:</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered small" id="summary_jadwal_table">
                        <thead>
                            <tr class="bg-light">
                                <th>Item Pekerjaan</th>
                                <th class="text-center" style="width: 20%;">Bobot (%)</th>
                                <th>Target Mingguan (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Filled dynamically -->
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold bg-light">
                                <td>Total Bobot Kerja</td>
                                <td class="text-center text-success" id="summary_jadwal_total_bobot">100.00%</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">Kembali & Edit</button>
                <button type="button" id="btnJadwalFinalSubmit" class="btn btn-warning btn-sm text-dark"><i class="bx bx-save me-1"></i> Ya, Simpan Baseline</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: RINGKASAN & KONFIRMASI SETUP PROYEK -->
<div class="modal fade" id="confirmProyekModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title text-white fw-bold"><i class="bx bx-briefcase me-1"></i> Konfirmasi Setup Kontrak Proyek</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <p class="text-muted small mb-3">Mohon periksa kembali detail kontrak proyek konstruksi yang akan dibuat:</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered small">
                        <tbody>
                            <tr>
                                <th class="bg-light" style="width: 40%;">Nama Proyek</th>
                                <td id="summary_proyek_nama" class="text-dark fw-bold"></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Kode RUP</th>
                                <td id="summary_proyek_rup" class="text-dark"></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Nomor Kontrak</th>
                                <td id="summary_proyek_nomor" class="text-dark fw-bold"></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Nilai Kontrak</th>
                                <td id="summary_proyek_nilai" class="text-dark fw-bold text-primary"></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Tanggal Mulai</th>
                                <td id="summary_proyek_tgl_mulai" class="text-dark"></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Tanggal Selesai</th>
                                <td id="summary_proyek_tgl_selesai" class="text-dark"></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Nama Penyedia</th>
                                <td id="summary_proyek_penyedia" class="text-dark"></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Konsultan Pengawas</th>
                                <td id="summary_proyek_konsultan" class="text-dark"></td>
                            </tr>
                            <tr>
                                <th class="bg-light">PPK</th>
                                <td id="summary_proyek_ppk" class="text-dark"></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Lokasi</th>
                                <td id="summary_proyek_lokasi" class="text-dark"></td>
                            </tr>
                            <tr class="summary-tender-row" style="display: none;">
                                <th class="bg-light text-primary">No. SPMK / SPP</th>
                                <td id="summary_proyek_no_spmk_spp" class="text-dark fw-bold"></td>
                            </tr>
                            <tr class="summary-tender-row" style="display: none;">
                                <th class="bg-light text-primary">Waktu Penyelesaian</th>
                                <td id="summary_proyek_waktu_penyelesaian" class="text-dark fw-bold"></td>
                            </tr>
                            <tr class="summary-tender-row" style="display: none;">
                                <th class="bg-light text-primary">Tgl Mulai Pekerjaan</th>
                                <td id="summary_proyek_tgl_mulai_pekerjaan" class="text-dark fw-bold"></td>
                            </tr>
                            <tr class="summary-tender-row" style="display: none;">
                                <th class="bg-light text-primary">Tgl Selesai Pekerjaan</th>
                                <td id="summary_proyek_tgl_selesai_pekerjaan" class="text-dark fw-bold"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">Kembali</button>
                <button type="button" id="btnProyekFinalSubmit" class="btn btn-primary btn-sm"><i class="bx bx-check me-1"></i> Ya, Daftarkan Kontrak</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: BERITA ACARA KEMAJUAN PEKERJAAN (FORMAT BA.docx) -->
<div class="modal fade" id="modalBeritaAcara" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title text-white fw-bold"><i class="bx bx-file-find me-1"></i> Berita Acara Kemajuan Pekerjaan - Laporan Mingguan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4" style="background: #f8f9fa;">
                <!-- Print Preview Area -->
                <div id="ba-print-area" style="background: #fff; padding: 40px 50px; max-width: 800px; margin: 0 auto; box-shadow: 0 2px 12px rgba(0,0,0,0.1); font-family: Arial, sans-serif; font-size: 11pt; color: #000;">
                    
                    <!-- Header -->
                    <div style="text-align: center; margin-bottom: 20px;">
                        <h3 style="font-size: 14pt; font-weight: bold; margin: 0 0 4px 0; text-transform: uppercase; letter-spacing: 1px;">BERITA ACARA KEMAJUAN PEKERJAAN</h3>
                        <h4 style="font-size: 12pt; font-weight: bold; margin: 0 0 16px 0;">LAPORAN MINGGUAN</h4>
                        <hr style="border-top: 2px solid #000; margin: 8px 0;">
                    </div>

                    <!-- Nomor & Tanggal -->
                    <table style="width:100%; border-collapse:collapse; margin-bottom:14px;">
                        <tr>
                            <td style="width:30%; padding:3px 0;">Nomor</td>
                            <td style="width:2%; padding:3px 0;">:</td>
                            <td style="padding:3px 0; border-bottom:1px solid #555;">................</td>
                        </tr>
                        <tr>
                            <td style="padding:3px 0;">Tanggal</td>
                            <td style="padding:3px 0;">:</td>
                            <td style="padding:3px 0; border-bottom:1px solid #555;" id="ba-tanggal-cetak"><?= date('d F Y') ?></td>
                        </tr>
                    </table>

                    <p style="margin-bottom: 12px;">Telah melaksanakan pekerjaan untuk:</p>

                    <!-- Detail Proyek -->
                    <table style="width:100%; border-collapse:collapse; margin-bottom:14px;">
                        <tr>
                            <td style="padding:3px 0; width:5%;">a.</td>
                            <td style="width:30%; padding:3px 0;">Pekerjaan</td>
                            <td style="width:2%; padding:3px 0;">:</td>
                            <td style="padding:3px 0; border-bottom:1px solid #555; font-weight:bold;"><?= isset($activeProyek) ? esc($activeProyek['nama_proyek']) : '........................' ?></td>
                        </tr>
                        <tr>
                            <td style="padding:3px 0;">b.</td>
                            <td style="padding:3px 0;">Lokasi</td>
                            <td style="padding:3px 0;">:</td>
                            <td style="padding:3px 0; border-bottom:1px solid #555;">........................</td>
                        </tr>
                        <tr>
                            <td style="padding:3px 0;">c.</td>
                            <td style="padding:3px 0;">No. Kontrak</td>
                            <td style="padding:3px 0;">:</td>
                            <td style="padding:3px 0; border-bottom:1px solid #555; font-weight:bold;"><?= isset($activeProyek) ? esc($activeProyek['no_kontrak']) : '........................' ?></td>
                        </tr>
                        <tr>
                            <td style="padding:3px 0;">d.</td>
                            <td style="padding:3px 0;">Kontraktor Pelaksana</td>
                            <td style="padding:3px 0;">:</td>
                            <td style="padding:3px 0; border-bottom:1px solid #555;"><?= isset($activeProyek) ? esc($activeProyek['nama_penyedia']) : '........................' ?></td>
                        </tr>
                        <tr>
                            <td style="padding:3px 0;">e.</td>
                            <td style="padding:3px 0;">Konsultan Pengawas</td>
                            <td style="padding:3px 0;">:</td>
                            <td style="padding:3px 0; border-bottom:1px solid #555;"><?= isset($activeProyek) ? esc($activeProyek['konsultan_pengawas'] ?: '-') : '........................' ?></td>
                        </tr>
                        <tr>
                            <td style="padding:3px 0;">f.</td>
                            <td style="padding:3px 0;">Nilai Kontrak</td>
                            <td style="padding:3px 0;">:</td>
                            <td style="padding:3px 0; border-bottom:1px solid #555; font-weight:bold;">Rp <?= isset($activeProyek) ? number_format($activeProyek['nilai_kontrak'], 0, ',', '.') : '................' ?></td>
                        </tr>
                        <tr>
                            <td style="padding:3px 0;">g.</td>
                            <td style="padding:3px 0;">Masa Pelaksanaan</td>
                            <td style="padding:3px 0;">:</td>
                            <td style="padding:3px 0; border-bottom:1px solid #555;"><?= isset($activeProyek) ? date('d M Y', strtotime($activeProyek['tgl_mulai'])) . ' s/d ' . date('d M Y', strtotime($activeProyek['tgl_selesai'])) : '................' ?></td>
                        </tr>
                    </table>

                    <p style="margin-bottom:10px;">Berdasarkan pemeriksaan dan penelitian tersebut terbukti bahwa pelaksana pekerjaan telah melaksanakan pekerjaan sebagai berikut:</p>

                    <!-- Tabel Kemajuan Pekerjaan per Item -->
                    <table style="width:100%; border-collapse:collapse; margin-bottom:14px; font-size:10pt;">
                        <thead>
                            <tr style="background:#e8e8e8; text-align:center;">
                                <th style="border:1px solid #333; padding:5px; width:5%;">No</th>
                                <th style="border:1px solid #333; padding:5px;">Uraian Pekerjaan</th>
                                <th style="border:1px solid #333; padding:5px; width:12%;">Bobot (%)</th>
                                <th style="border:1px solid #333; padding:5px; width:15%;">Rencana Kumulatif (%)</th>
                                <th style="border:1px solid #333; padding:5px; width:15%;">Realisasi Kumulatif (%)</th>
                                <th style="border:1px solid #333; padding:5px; width:12%;">Deviasi (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (!empty($jadwalItems)):
                                $totalBobot = 0;
                                foreach($jadwalItems as $idx2 => $ji2):
                                    $totalBobot += $ji2['bobot'];
                                    // Get rencana kumulatif (last plan value)
                                    $weeklyArrBA = json_decode($ji2['target_mingguan'] ?? '[]', true);
                                    $rencanaKum = (!empty($weeklyArrBA) && is_array($weeklyArrBA)) ? end($weeklyArrBA) : 0;
                                    // Realisasi: get actual progress for this item
                                    $realisasiKum = 0;
                                    if (!empty($harianItems)) {
                                        foreach($harianItems as $hi) {
                                            if ($hi['item_id'] == $ji2['id']) {
                                                $realisasiKum = max($realisasiKum, $hi['progres_persen']);
                                            }
                                        }
                                    }
                                    $deviasiItem = $realisasiKum - $rencanaKum;
                            ?>
                            <tr>
                                <td style="border:1px solid #333; padding:4px; text-align:center;"><?= $idx2 + 1 ?></td>
                                <td style="border:1px solid #333; padding:4px;"><?= esc($ji2['item_pekerjaan']) ?></td>
                                <td style="border:1px solid #333; padding:4px; text-align:center;"><?= number_format($ji2['bobot'], 2) ?>%</td>
                                <td style="border:1px solid #333; padding:4px; text-align:center;"><?= number_format($rencanaKum, 2) ?>%</td>
                                <td style="border:1px solid #333; padding:4px; text-align:center; font-weight:bold;"><?= number_format($realisasiKum, 2) ?>%</td>
                                <td style="border:1px solid #333; padding:4px; text-align:center; color:<?= $deviasiItem < 0 ? '#cc0000' : '#006600' ?>; font-weight:bold;">
                                    <?= ($deviasiItem >= 0 ? '+' : '') . number_format($deviasiItem, 2) ?>%
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <!-- Total Row -->
                            <tr style="background:#f0f0f0; font-weight:bold;">
                                <td colspan="2" style="border:1px solid #333; padding:5px; text-align:center;">JUMLAH / TOTAL</td>
                                <td style="border:1px solid #333; padding:5px; text-align:center;"><?= number_format($totalBobot, 2) ?>%</td>
                                <?php
                                    $planFinal = !empty($scurve['plan']) ? end($scurve['plan']) : 0;
                                    $actFinal = 0;
                                    if (!empty($scurve['actual'])) {
                                        foreach(array_reverse($scurve['actual']) as $v) {
                                            if ($v !== null) { $actFinal = $v; break; }
                                        }
                                    }
                                    $devTotal = $actFinal - $planFinal;
                                ?>
                                <td style="border:1px solid #333; padding:5px; text-align:center;"><?= number_format($planFinal, 2) ?>%</td>
                                <td style="border:1px solid #333; padding:5px; text-align:center; font-weight:bold;"><?= number_format($actFinal, 2) ?>%</td>
                                <td style="border:1px solid #333; padding:5px; text-align:center; color:<?= $devTotal < 0 ? '#cc0000' : '#006600' ?>; font-weight:bold;">
                                    <?= ($devTotal >= 0 ? '+' : '') . number_format($devTotal, 2) ?>%
                                </td>
                            </tr>
                            <?php else: ?>
                            <tr>
                                <td colspan="6" style="border:1px solid #333; padding:8px; text-align:center; color:#888;">Belum ada data jadwal item pekerjaan.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Rekapitulasi Mingguan -->
                    <?php if (!empty($scurve['labels'])): ?>
                    <p style="margin-bottom:8px; font-weight:bold;">Rekapitulasi Deviasi Mingguan:</p>
                    <table style="width:100%; border-collapse:collapse; margin-bottom:14px; font-size:9.5pt;">
                        <thead>
                            <tr style="background:#e8e8e8; text-align:center;">
                                <th style="border:1px solid #333; padding:4px;">Minggu</th>
                                <th style="border:1px solid #333; padding:4px;">Rencana Kum. (%)</th>
                                <th style="border:1px solid #333; padding:4px;">Realisasi Kum. (%)</th>
                                <th style="border:1px solid #333; padding:4px;">Deviasi (%)</th>
                                <th style="border:1px solid #333; padding:4px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for($i = 0; $i < count($scurve['labels']); $i++):
                                $planV = $scurve['plan'][$i] ?? 0;
                                $actV = $scurve['actual'][$i] ?? null;
                                $devV = $scurve['deviasi'][$i] ?? null;
                                if ($actV === null) continue;
                                $statusBA = $devV >= 0 ? 'Normal' : ($devV >= -2 ? 'Lambat' : 'Kritis');
                                $statusColor = $devV >= 0 ? '#006600' : ($devV >= -2 ? '#cc7700' : '#cc0000');
                            ?>
                            <tr style="text-align:center;">
                                <td style="border:1px solid #333; padding:3px;">Minggu ke-<?= $i + 1 ?></td>
                                <td style="border:1px solid #333; padding:3px;"><?= number_format($planV, 2) ?>%</td>
                                <td style="border:1px solid #333; padding:3px; font-weight:bold;"><?= number_format($actV, 2) ?>%</td>
                                <td style="border:1px solid #333; padding:3px; font-weight:bold; color:<?= $devV < 0 ? '#cc0000' : '#006600' ?>;">
                                    <?= ($devV >= 0 ? '+' : '') . number_format($devV, 2) ?>%
                                </td>
                                <td style="border:1px solid #333; padding:3px; font-weight:bold; color:<?= $statusColor ?>;"><?= $statusBA ?></td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>

                    <!-- Kesimpulan -->
                    <?php
                    $actWeightBA = 0;
                    if (!empty($scurve['actual'])) {
                        foreach(array_reverse($scurve['actual']) as $v) {
                            if ($v !== null) { $actWeightBA = $v; break; }
                        }
                    }
                    function angkaTerbilang($angka) {
                        $satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas', 'dua belas', 'tiga belas', 'empat belas', 'lima belas', 'enam belas', 'tujuh belas', 'delapan belas', 'sembilan belas'];
                        if ($angka < 20) return $satuan[$angka];
                        if ($angka < 100) return $satuan[floor($angka/10)].' puluh '.($angka%10 > 0 ? $satuan[$angka%10] : '');
                        return $satuan[floor($angka/100)].' ratus '.($angka%100 > 0 ? angkaTerbilang($angka%100) : '');
                    }
                    $pctInt = (int)floor($actWeightBA);
                    $terbilang = ucwords(angkaTerbilang($pctInt));
                    ?>
                    <p style="margin: 12px 0; text-align:justify;">
                        Kemajuan pekerjaan telah mencapai prestasi sebesar : 
                        <strong><?= number_format($actWeightBA, 2) ?>% (<?= $terbilang ?> Persen)</strong>
                    </p>

                    <p style="margin-bottom:16px; text-align:justify;">
                        Demikian Berita Acara Kemajuan Pekerjaan ini dibuat dengan sebenarnya untuk digunakan sebagaimana mestinya.
                    </p>

                    <!-- Tanda Tangan -->
                    <table style="width:100%; border-collapse:collapse; margin-top:30px;">
                        <tr>
                            <td style="width:50%; text-align:center; vertical-align:top; padding: 0 20px;">
                                <p style="margin:0 0 4px 0;">Diperiksa oleh :</p>
                                <p style="margin:0 0 4px 0; font-weight:bold;">PEJABAT PEMBUAT KOMITMEN</p>
                                <p style="margin:0;">(.................&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</p>
                                <div style="border-top:1px solid #333; margin-top:70px; padding-top:4px;">
                                    <p style="margin:0; font-weight:bold;">..........................................</p>
                                    <p style="margin:0; font-size:9pt;">NIP. .................................</p>
                                </div>
                            </td>
                            <td style="width:50%; text-align:center; vertical-align:top; padding: 0 20px;">
                                <p style="margin:0 0 4px 0;">Dibuat oleh :</p>
                                <p style="margin:0 0 4px 0; font-weight:bold;"><?= isset($activeProyek) ? esc($activeProyek['nama_penyedia']) : 'CV. ...................' ?></p>
                                <p style="margin:0;">(Kontraktor Pelaksana)</p>
                                <div style="border-top:1px solid #333; margin-top:70px; padding-top:4px;">
                                    <p style="margin:0; font-weight:bold;">..........................................</p>
                                    <p style="margin:0; font-size:9pt;">Direktur</p>
                                </div>
                            </td>
                        </tr>
                        <?php if(isset($activeProyek) && $activeProyek['konsultan_pengawas']): ?>
                        <tr>
                            <td colspan="2" style="text-align:center; vertical-align:top; padding: 20px 20px 0;">
                                <p style="margin:0 0 4px 0;">Diketahui oleh :</p>
                                <p style="margin:0 0 4px 0; font-weight:bold;"><?= esc($activeProyek['konsultan_pengawas']) ?></p>
                                <p style="margin:0;">(Konsultan Pengawas)</p>
                                <div style="border-top:1px solid #333; margin-top:70px; padding-top:4px; max-width:250px; margin-left:auto; margin-right:auto;">
                                    <p style="margin:0; font-weight:bold;">..........................................</p>
                                    <p style="margin:0; font-size:9pt;">Direktur / Pengawas</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>

                </div><!-- /ba-print-area -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal"><i class="bx bx-x me-1"></i> Tutup</button>
                <button type="button" class="btn btn-outline-secondary" onclick="exportBaAsDoc()"><i class="bx bxs-file-doc me-1"></i> Simpan .DOC</button>
                <button type="button" class="btn btn-primary" onclick="doPrintBA()"><i class="bx bx-printer me-1"></i> Cetak / Print PDF</button>
            </div>
        </div>
    </div>
</div>

<!-- CSS Styles for animations and scales -->

<style>
    @media print {
        /* Sembunyikan elemen layout admin */
        .layout-menu, .layout-navbar, .footer, .content-footer, .nav-tabs, .buy-now, .d-flex.align-items-center.justify-content-between.mb-4, #welcome-action-banner {
            display: none !important;
        }
        
        .layout-page, .content-wrapper, .container-xxl {
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            background: #fff !important;
        }

        /* Hanya tampilkan Panel Dashboard (Berisi Detail, Grafik, dan Tabel) */
        .tab-content > .tab-pane {
            display: none !important;
        }
        #panel-dashboard {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
        }

        /* Tampilkan Grafik dan Tabel Kurva S sekaligus */
        #scurveChartView, #scurveTableView {
            display: block !important;
            page-break-inside: avoid;
            margin-bottom: 20px;
            width: 100% !important;
        }
        
        /* Hilangkan scroll horizontal pada tabel saat diprint */
        .table-responsive {
            overflow: visible !important;
        }

        /* Sembunyikan tombol-tombol aksi dan form input yang tidak relevan di kertas */
        button, .btn, .form-control::-webkit-file-upload-button, input[type="file"], .position-relative > input {
            display: none !important;
        }
        
        /* Hilangkan bayangan dan border card agar bersih di kertas */
        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
            margin-bottom: 15px !important;
            page-break-inside: avoid;
        }
    }

    .hover-scale-card {
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    .hover-scale-card:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 8px 15px rgba(0,0,0,0.15) !important;
    }
    .hover-zoom-img {
        transition: transform 0.2s ease;
    }
    .hover-zoom-img:hover {
        transform: scale(1.4);
        z-index: 10;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    .nav-tab-btn {
        transition: background-color 0.2s ease, color 0.2s ease;
    }
    .nav-tab-btn.active {
        background-color: #696cff !important;
        color: #ffffff !important;
        box-shadow: 0 4px 8px rgba(105, 108, 255, 0.3) !important;
    }
    .btn-xs {
        padding: 0.15rem 0.4rem;
        font-size: 0.72rem;
        border-radius: 0.2rem;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Chart.js inclusion if not preloaded -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<!-- Leaflet Geocoder JS -->
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
<!-- Leaflet Draw JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>

<script>
    $(document).ready(function() {
        // Setup global AJAX CSRF configuration
        $.ajaxSetup({
            headers: {
                '<?= config('Security')->headerName ?>': '<?= csrf_hash() ?>'
            }
        });

        console.log('Monev Proyek PPK System initialized.');
        
        // Auto-fill Progres Fisik Kumulatif berdasarkan item_id
        const latestProgressMap = {};
        <?php if (!empty($harianItems)): ?>
            <?php 
                $latestProg = [];
                foreach($harianItems as $hi) {
                    if(!isset($latestProg[$hi['item_id']])) {
                        $latestProg[$hi['item_id']] = 0;
                    }
                    $latestProg[$hi['item_id']] += (float) $hi['progres_persen'];
                }
            ?>
            <?php foreach($latestProg as $itemId => $prog): ?>
                latestProgressMap["<?= $itemId ?>"] = <?= $prog ?>;
            <?php endforeach; ?>
        <?php endif; ?>

        $('#harian_item_id').on('change', function(e) {
            // Only auto-fill if this is a genuine user interaction or not in edit mode
            if (e.originalEvent || !$('#harian_edit_id').val()) {
                let itemId = $(this).val();
                if (itemId && latestProgressMap[itemId] !== undefined) {
                    $('#harian_progres').val(latestProgressMap[itemId]);
                } else if (!$('#harian_edit_id').val()) {
                    $('#harian_progres').val('');
                }
            }
        });

        // Initial call to update bobot baseline indicator + week footers
        updateBobotIndicator();
        updateWeekFooters();
        
        // Dynamic event binding to recalculate bobot
        $(document).on('input', '.jadwal-bobot-input', function() {
            updateBobotIndicator();
        });
        
        // Initialize Chart.js S-curve
        <?php if (isset($activeProyek) && !empty($jadwalItems)): ?>
            initScurveChart();
        <?php endif; ?>

        // Initialize active project dashboard map visualizer
        <?php if (isset($activeProyek)): ?>
            initDashboardMap();
        <?php endif; ?>

        // Setup Proyek Form Submit (AJAX with Confirmation Modal)
        $('#formSetupProyek').submit(function(e) {
            e.preventDefault();
            
            // Client-side Validation: Date bounds
            let tglMulai = $('#setup_tgl_mulai').val();
            let tglSelesai = $('#setup_tgl_selesai').val();
            if (tglSelesai && tglMulai && tglSelesai < tglMulai) {
                alert("Peringatan: Tanggal selesai kontrak tidak boleh mendahului tanggal mulai kontrak.");
                return false;
            }

            let tglMulaiPekerjaan = $('#setup_tgl_mulai_pekerjaan').val();
            let tglSelesaiPekerjaan = $('#setup_tgl_selesai_pekerjaan').val();
            if (tglMulaiPekerjaan && tglSelesaiPekerjaan && tglSelesaiPekerjaan < tglMulaiPekerjaan) {
                alert("Peringatan: Tanggal selesai pekerjaan tidak boleh mendahului tanggal mulai pekerjaan.");
                return false;
            }
            
            // Populate modal summary fields
            $('#summary_proyek_nama').text($('#setup_nama_proyek').val() || '-');
            $('#summary_proyek_rup').text($('#setup_kd_rup').val() || 'Tidak ada (Manual)');
            $('#summary_proyek_nomor').text($('#setup_no_kontrak').val() || '-');
            
            let nilai = parseFloat($('#setup_nilai_kontrak').val()) || 0;
            let formattedNilai = 'Rp ' + nilai.toLocaleString('id-ID');
            $('#summary_proyek_nilai').text(formattedNilai);
            
            $('#summary_proyek_tgl_mulai').text($('#setup_tgl_mulai').val() || '-');
            $('#summary_proyek_tgl_selesai').text($('#setup_tgl_selesai').val() || '-');
            $('#summary_proyek_penyedia').text($('#setup_nama_penyedia').val() || '-');
            $('#summary_proyek_konsultan').text($('#setup_konsultan').val() || '-');
            $('#summary_proyek_ppk').text($('#setup_ppk').val() || '-');
            $('#summary_proyek_lokasi').text($('#setup_lokasi').val() || '-');

            let noSpmk = $('#setup_no_spmk_spp').val();
            if (noSpmk) {
                $('.summary-tender-row').show();
                $('#summary_proyek_no_spmk_spp').text(noSpmk);
                $('#summary_proyek_waktu_penyelesaian').text(($('#setup_waktu_penyelesaian').val() || '-') + ' Hari');
                $('#summary_proyek_tgl_mulai_pekerjaan').text(tglMulaiPekerjaan || '-');
                $('#summary_proyek_tgl_selesai_pekerjaan').text(tglSelesaiPekerjaan || '-');
            } else {
                $('.summary-tender-row').hide();
            }
            
            if (document.activeElement) {
                document.activeElement.blur();
            }
            $('#setupProyekModal').modal('hide');
            $('#confirmProyekModal').modal('show');
        });
        
        // Final Setup Proyek Submit from confirmation modal
        $('#btnProyekFinalSubmit').click(function() {
            let form = $('#formSetupProyek')[0];
            let formData = new FormData(form);
            if (!formData.has('<?= csrf_token() ?>')) {
                formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
            }
            $('#btnProyekFinalSubmit').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...');
            
            $.ajax({
                url: '<?= base_url('dashboard/monev/setup-proyek') ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    $('#confirmProyekModal').modal('hide');
                    $('#btnProyekFinalSubmit').prop('disabled', false).html('<i class="bx bx-check me-1"></i> Ya, Daftarkan Kontrak');
                    
                    if (res.status === 'success') {
                        alert(res.message);
                        window.location.href = '<?= base_url('dashboard/monev-proyek?proyek_id=') ?>' + res.proyek_id;
                    } else {
                        alert('Error: ' + res.message);
                        $('#setupProyekModal').modal('show');
                    }
                },
                error: function(xhr) {
                    $('#confirmProyekModal').modal('hide');
                    $('#btnProyekFinalSubmit').prop('disabled', false).html('<i class="bx bx-check me-1"></i> Ya, Daftarkan Kontrak');
                    let errMsg = 'Gagal menyimpan proyek. Hubungi administrator.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg = xhr.responseJSON.message;
                    }
                    alert(errMsg);
                    $('#setupProyekModal').modal('show');
                }
            });
        });

        // Edit Proyek Contract Info Form Submit (AJAX with confirm)
        $('#formEditProyekMonev').submit(function(e) {
            e.preventDefault();
            if (!confirm('Apakah Anda yakin ingin memperbarui informasi kontrak proyek ini?')) {
                return false;
            }
            
            let tglMulai = $(this).find('input[name="tgl_mulai"]').val();
            let tglSelesai = $(this).find('input[name="tgl_selesai"]').val();
            if (tglSelesai && tglMulai && tglSelesai < tglMulai) {
                alert("Peringatan: Tanggal selesai kontrak tidak boleh mendahului tanggal mulai kontrak.");
                return false;
            }

            let tglMulaiPekerjaan = $(this).find('input[name="tgl_mulai_pekerjaan"]').val();
            let tglSelesaiPekerjaan = $(this).find('input[name="tgl_selesai_pekerjaan"]').val();
            if (tglMulaiPekerjaan && tglSelesaiPekerjaan && tglSelesaiPekerjaan < tglMulaiPekerjaan) {
                alert("Peringatan: Tanggal selesai pekerjaan tidak boleh mendahului tanggal mulai pekerjaan.");
                return false;
            }

            let formData = $(this).serialize();
            $.ajax({
                url: '<?= base_url('dashboard/monev/setup-proyek') ?>',
                type: 'POST',
                data: formData,
                success: function(res) {
                    if (res.status === 'success') {
                        alert(res.message);
                        window.location.reload();
                    } else {
                        alert('Error: ' + res.message);
                    }
                },
                error: function() {
                    alert('Gagal memperbarui kontrak.');
                }
            });
        });

        // Save Baseline Schedule Form Submit (AJAX with Confirmation Modal)
        $('#formJadwalMonev').submit(function(e) {
            e.preventDefault();
            
            let totalBobot = 0;
            let tbodyHtml = '';
            let totalMinggu = parseInt($('input[name="total_minggu"]').val()) || 0;
            
            let mismatchedRows = [];
            
            // For each row, gather per-week values and convert to cumulative target_weekly[]
            $('#tableJadwalMonev tbody tr.jadwal-row').each(function(rowIdx) {
                let item = $(this).find('input[name="item_pekerjaan[]"]').val() || '';
                let bobotStr = $(this).find('input[name="bobot[]"]').val() || '';
                let bobotVal = parseFloat(bobotStr.toString().replace(/,/g, '.')) || 0;
                totalBobot += bobotVal;
                
                // Build cumulative array from per-week delta inputs
                let cumArr = [];
                let cumSum = 0;
                let hasAnyValue = false;
                
                // Track total per row to check against bobot
                let rowTotalDelta = 0;
                let weekInputs = $(this).find('.week-input');

                for (let w = 0; w < totalMinggu; w++) {
                    let wInput = weekInputs.eq(w);
                    let wStr = wInput.val() || '';
                    let wVal = parseFloat(wStr.toString().replace(/,/g, '.')) || 0;
                    cumSum += wVal;
                    rowTotalDelta += wVal;
                    if (wVal > 0) hasAnyValue = true;
                    
                    // Convert weighted delta sum to item percentage
                    let itemPercent = 0;
                    if (bobotVal > 0) {
                        itemPercent = (cumSum / bobotVal) * 100;
                    }
                    cumArr.push(parseFloat(itemPercent.toFixed(2)));
                }

                // Validasi bobot vs total input minggu
                if (hasAnyValue) {
                    if (Math.abs(cumSum - bobotVal) > 0.01) {
                        mismatchedRows.push({
                            rowNum: rowIdx + 1,
                            item: item,
                            bobot: bobotVal,
                            sum: cumSum
                        });
                    }
                }

                // If no per-week input found (e.g. new row), fallback to empty
                let weeklyStr = hasAnyValue ? cumArr.join(', ') : '';
                
                // Inject hidden input so serialize() picks it up
                $(this).find('.hidden-target-weekly').remove();
                $(this).append(`<input type="hidden" class="hidden-target-weekly" name="target_weekly[]" value="${weeklyStr}">`);
                
                tbodyHtml += `
                    <tr>
                        <td><strong>${item}</strong></td>
                        <td class="text-center fw-bold text-primary">${bobotVal.toFixed(2)}%</td>
                        <td class="text-muted small">${weeklyStr}</td>
                    </tr>
                `;
            });
            
            if (mismatchedRows.length > 0) {
                let msg = 'Terdapat ketidaksesuaian jumlah progress dengan Bobot pada baris berikut:\n\n';
                mismatchedRows.forEach(m => {
                    msg += `- Baris ${m.rowNum} (${m.item}): Bobot = ${m.bobot.toFixed(2)}%, Total Input = ${m.sum.toFixed(2)}%\n`;
                });
                msg += '\nHarap periksa dan revisi kembali nilai progress mingguannya agar totalnya sama persis dengan Bobot.';
                alert(msg);
                return false;
            }
            
            if (Math.abs(totalBobot - 100.0) > 0.01) {
                alert('Total bobot seluruh item pekerjaan harus tepat 100.00% sebelum dapat disimpan!\nTotal saat ini: ' + totalBobot.toFixed(2) + '%');
                return false;
            }
            
            $('#summary_jadwal_table tbody').html(tbodyHtml);
            $('#summary_jadwal_total_bobot').text(totalBobot.toFixed(2) + '%');
            $('#confirmJadwalModal').modal('show');
        });
        
        // Final Baseline Schedule Submit
        $('#btnJadwalFinalSubmit').click(function() {
            // Disable week inputs temporarily to prevent PHP max_input_vars limit overflow
            // Since we only need item_pekerjaan, bobot, and target_weekly (hidden)
            $('.week-input').prop('disabled', true);
            let formData = $('#formJadwalMonev').serialize();
            $('.week-input').prop('disabled', false);
            
            $('#btnJadwalFinalSubmit').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...');
            
            $.ajax({
                url: '<?= base_url('dashboard/monev/save-jadwal') ?>',
                type: 'POST',
                data: formData,
                success: function(res) {
                    $('#confirmJadwalModal').modal('hide');
                    $('#btnJadwalFinalSubmit').prop('disabled', false).html('<i class="bx bx-save me-1"></i> Ya, Simpan Baseline');
                    
                    if (res.status === 'success') {
                        alert(res.message);
                        window.location.reload();
                    } else {
                        alert('Error: ' + res.message);
                    }
                },
                error: function() {
                    $('#confirmJadwalModal').modal('hide');
                    $('#btnJadwalFinalSubmit').prop('disabled', false).html('<i class="bx bx-save me-1"></i> Ya, Simpan Baseline');
                    alert('Gagal menyimpan baseline Kurva S.');
                }
            });
        });

        // Save Daily Progress Log Form Submit (AJAX with file uploads & Confirmation Modal)
        $('#formHarianMonev').submit(function(e) {
            e.preventDefault();
            
            // Client-side Validation: Date bounds matching contract
            let tanggalStr = $('#harian_tanggal').val();
            let tglMulaiKontrak = '<?= isset($activeProyek) ? $activeProyek['tgl_mulai'] : '' ?>';
            let tglSelesaiKontrak = '<?= isset($activeProyek) ? $activeProyek['tgl_selesai'] : '' ?>';
            if (tanggalStr < tglMulaiKontrak || tanggalStr > tglSelesaiKontrak) {
                alert(`Peringatan: Tanggal pekerjaan (${tanggalStr}) di luar batas masa kontrak proyek (${tglMulaiKontrak} s/d ${tglSelesaiKontrak}).`);
                return false;
            }
            
            // Client-side Validation: Required photos for new log entry
            let editId = $('#harian_edit_id').val();
            if (!editId) {
                let fotoAwalInput = $('input[name="foto_awal"]')[0];
                let fotoProsesInput = $('input[name="foto_proses"]')[0];
                let fotoHasilInput = $('input[name="foto_hasil"]')[0];
                
                let fotoAwal = fotoAwalInput && fotoAwalInput.files.length > 0;
                let fotoProses = fotoProsesInput && fotoProsesInput.files.length > 0;
                let fotoHasil = fotoHasilInput && fotoHasilInput.files.length > 0;
                
                if (!fotoAwal || !fotoProses || !fotoHasil) {
                    alert("Peringatan: Ketiga dokumentasi lapangan (kondisi awal, proses, hasil) wajib diunggah untuk laporan progres baru!");
                    return false;
                }
            }
            
            // Populate confirmation modal summary fields
            let selectedItemText = $('#harian_item_id option:selected').text().trim();
            $('#summary_harian_item').text(selectedItemText || '-');
            $('#summary_harian_tanggal').text(tanggalStr || '-');
            $('#summary_harian_progres').text(($('#harian_progres').val() || '0') + '%');
            
            let volume = $('#harian_volume').val() || '0';
            let satuan = $('#harian_satuan').val() || '';
            $('#summary_harian_volume').text(`${volume} ${satuan}`);
            
            $('#summary_harian_lokasi').text($('#harian_lokasi').val() || '-');
            $('#summary_harian_tenaga').text($('#harian_tenaga').val() || 'Tidak ada');
            $('#summary_harian_peralatan').text($('#harian_peralatan').val() || 'Tidak ada');
            $('#summary_harian_kendala').text($('#harian_kendala').val() || 'Tidak ada hambatan');
            $('#summary_harian_justifikasi').text($('#harian_keterangan').val() || 'Tidak ada');
            
            $('#confirmHarianModal').modal('show');
        });
        
        // Final Daily Progress Submit from confirmation modal
        $('#btnHarianFinalSubmit').click(function() {
            let form = $('#formHarianMonev')[0];
            let formData = new FormData(form);
            
            $('#btnHarianFinalSubmit').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mengirim...');
            
            $.ajax({
                url: '<?= base_url('dashboard/monev/add-harian') ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    $('#confirmHarianModal').modal('hide');
                    $('#btnHarianFinalSubmit').prop('disabled', false).html('<i class="bx bx-send me-1"></i> Ya, Kirim Laporan');
                    
                    if (res.status === 'success') {
                        if (res.warning) {
                            alert(res.message + "\n\n" + res.warning);
                        } else {
                            alert(res.message);
                        }
                        window.location.reload();
                    } else {
                        alert('Error: ' + res.message);
                    }
                },
                error: function() {
                    $('#confirmHarianModal').modal('hide');
                    $('#btnHarianFinalSubmit').prop('disabled', false).html('<i class="bx bx-send me-1"></i> Ya, Kirim Laporan');
                    alert('Gagal menyimpan laporan harian.');
                }
            });
        });
    });

    // Switch Tab in SPA dynamically with smooth transitions
    function switchSPATab(tabId) {
        let tabEl = document.getElementById(tabId);
        if (tabEl) {
            let tab = new bootstrap.Tab(tabEl);
            tab.show();
            // Smoothly scroll to the tab header
            $('html, body').animate({
                scrollTop: $(tabEl).offset().top - 120
            }, 300);
        }
    }

    // Modal triggers
    function showSetupModal() {
        $('#setup_proyek_id').val('');
        $('#formSetupProyek')[0].reset();
        $('#select_rup_prefill').val('');
        $('.tender-only-fields').hide();
        $('#setupProyekModal').modal('show');
    }

    // RUP Details Auto Prefill
    function prefillPaketDetails(kdPaket) {
        if (!kdPaket) return;
        $.ajax({
            url: '<?= base_url('dashboard/get-rup-details/') ?>' + kdPaket,
            type: 'GET',
            success: function(res) {
                $('#setup_nama_proyek').val(res.nama_paket);
                $('#setup_kd_rup').val(res.kd_rup);
                $('#setup_nilai_kontrak').val(res.nilai_kontrak);
                $('#setup_nama_penyedia').val(res.nama_penyedia);
                $('#setup_no_kontrak').val(res.no_kontrak);

                if (res.type === 'Tender') {
                    $('.tender-only-fields').slideDown();
                    $('#setup_no_spmk_spp').val(res.no_spmk_spp || '');
                    $('#setup_waktu_penyelesaian').val(res.waktu_penyelesaian || '');
                    $('#setup_tgl_mulai_pekerjaan').val(res.tgl_mulai_pekerjaan || '');
                    $('#setup_tgl_selesai_pekerjaan').val(res.tgl_selesai_pekerjaan || '');
                } else {
                    $('.tender-only-fields').slideUp();
                    $('#setup_no_spmk_spp').val('');
                    $('#setup_waktu_penyelesaian').val('');
                    $('#setup_tgl_mulai_pekerjaan').val('');
                    $('#setup_tgl_selesai_pekerjaan').val('');
                }

                $('#setup_no_kontrak').focus();
            },
            error: function() {
                alert('Gagal mengambil data paket SPSE.');
            }
        });
    }

    // AJAX Claim RUP Baru
    function claimPaketNew() {
        let kdPaket = $('#input_claim_paket').val().trim();
        if (!kdPaket) {
            alert('Silakan masukkan Kode Paket SPSE terlebih dahulu.');
            return;
        }
        
        let btn = $('#btn_claim_paket');
        let originalText = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Memproses...');
        
        $.ajax({
            url: '<?= base_url('dashboard/add-monev-paket') ?>',
            type: 'POST',
            data: {
                "<?= csrf_token() ?>": "<?= csrf_hash() ?>",
                kd_paket: kdPaket
            },
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false).html(originalText);
                if (res.status === 'success') {
                    alert(res.message);
                    $('#input_claim_paket').val('');
                    
                    // Add new option to dropdown if it doesn't exist
                    let optionExists = $('#select_rup_prefill option[value="' + res.data.kd_paket + '"]').length > 0;
                    if (!optionExists) {
                        let formattedKontrak = 'Rp ' + Number(res.data.nilai_kontrak).toLocaleString('id-ID');
                        let optionText = res.data.kd_paket + ' - ' + res.data.nama_paket + ' (' + formattedKontrak + ') [' + res.data.type + ']';
                        let newOption = new Option(optionText, res.data.kd_paket, false, true);
                        $('#select_rup_prefill').append(newOption).trigger('change');
                    } else {
                        $('#select_rup_prefill').val(res.data.kd_paket).trigger('change');
                    }
                } else {
                    alert('Gagal: ' + res.message);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(originalText);
                let errorMsg = 'Gagal melakukan klaim paket SPSE. Pastikan Kode Paket valid.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                alert(errorMsg);
            }
        });
    }

    // =====================================================
    // TEMPLATE DOWNLOAD & UPLOAD - Jadwal Baseline Kurva S
    // =====================================================

    function downloadJadwalTemplate() {
        let totalMinggu = parseInt($('input[name="total_minggu"]').val()) || 0;
        if (totalMinggu === 0) {
            alert('Durasi kontrak belum terhitung. Pastikan tanggal mulai dan selesai proyek sudah diisi.');
            return;
        }

        // Build header row
        let headers = ['No', 'Uraian Pekerjaan', 'Bobot (%)'];
        for (let w = 1; w <= totalMinggu; w++) {
            headers.push('W' + w);
        }

        // Build example rows (3 rows kosong + 1 contoh)
        let contohRows = [
            ['1', 'Pekerjaan Persiapan', '2.00'],
            ['2', 'Pekerjaan Tanah & Pondasi', '15.00'],
            ['3', 'Pekerjaan Struktur', '25.00'],
            ['4', 'Pekerjaan Arsitektur', '30.00'],
            ['5', 'Pekerjaan MEP', '18.00'],
            ['6', 'Pekerjaan Finishing', '10.00'],
        ];

        // Add empty week cells to each row
        let rows = [headers.join(',')];
        
        // Add instruction row (as comment using semicolons)
        rows.push('# PETUNJUK: Isi nilai progress PER MINGGU (bukan kumulatif) untuk setiap item pekerjaan.');
        rows.push('# Kolom kosong = tidak ada pekerjaan di minggu tersebut. Total Bobot harus = 100%.');
        rows.push('# Hapus baris petunjuk ini (#) sebelum upload.');
        rows.push('');

        contohRows.forEach(function(row) {
            // Pad with empty week cells
            while (row.length < headers.length) {
                row.push('');
            }
            rows.push(row.join(','));
        });

        // Add empty rows for user to fill
        for (let i = contohRows.length + 1; i <= 20; i++) {
            let emptyRow = [i, '', '0.00'];
            while (emptyRow.length < headers.length) emptyRow.push('');
            rows.push(emptyRow.join(','));
        }

        // Add totals footer
        let footerBobot = ['', 'TOTAL', '100.00'];
        while (footerBobot.length < headers.length) footerBobot.push('');
        rows.push(footerBobot.join(','));

        let csvContent = '\uFEFF' + rows.join('\n'); // UTF-8 BOM for Excel
        let blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        let link = document.createElement('a');
        link.href = URL.createObjectURL(blob);

        let namaProyek = '<?= isset($activeProyek) ? preg_replace('/[^a-zA-Z0-9]/', '_', $activeProyek['nama_proyek']) : 'Proyek' ?>';
        link.download = 'Template_Jadwal_Kurva_S_' + namaProyek + '_' + totalMinggu + 'Minggu.csv';
        link.click();

        // Show instructions toast
        let toastHtml = `
            <div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999;">
                <div id="toastDownload" class="toast show align-items-center text-bg-success border-0 shadow-lg" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="bx bx-check-circle me-1"></i>
                            <strong>Template berhasil didownload!</strong><br>
                            <small>Buka dengan Excel/Google Sheets, isi data, lalu upload kembali.</small>
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="$(this).closest('.position-fixed').remove()"></button>
                    </div>
                </div>
            </div>`;
        $('body').append(toastHtml);
        setTimeout(() => $('#toastDownload').closest('.position-fixed').remove(), 5000);
    }

    function uploadJadwalTemplate(input) {
        let file = input.files[0];
        if (!file) return;

        let fileName = file.name.toLowerCase();
        
        if (!fileName.endsWith('.csv')) {
            alert('Format file harus .CSV\n\nCara mudah: Buka file .xlsx di Excel → Simpan Sebagai → CSV (Comma delimited)');
            input.value = '';
            return;
        }

        let reader = new FileReader();
        reader.onload = function(e) {
            let text = e.target.result;
            // Remove BOM (UTF-8 dan UTF-16)
            text = text.replace(/^\uFEFF/, '').replace(/^\uFFFE/, '');
            
            let lines = text.split(/\r?\n/);
            let totalMinggu = parseInt($('input[name="total_minggu"]').val()) || 0;

            // ─── 1. Filter out comment/empty lines, get header + data lines ───
            let headerLine = '';
            let dataLines = [];
            
            for (let line of lines) {
                let trimmed = line.trim();
                if (!trimmed || trimmed.startsWith('#')) continue;
                if (!headerLine) {
                    headerLine = trimmed;
                    continue;
                }
                dataLines.push(trimmed);
            }

            if (!headerLine) {
                alert('Format CSV tidak valid. Pastikan baris header ada di baris pertama.');
                input.value = '';
                return;
            }

            // ─── 2. Auto-detect delimiter: comma or semicolon ───
            let delim = ',';
            let semiCount = (headerLine.match(/;/g) || []).length;
            let commaCount = (headerLine.match(/,/g) || []).length;
            if (semiCount > commaCount) delim = ';';

            // ─── 3. Parse CSV with detected delimiter ───
            function parseLine(line) {
                let result = [];
                let cur = '';
                let inQ = false;
                for (let i = 0; i < line.length; i++) {
                    let c = line[i];
                    if (c === '"') { inQ = !inQ; }
                    else if (c === delim && !inQ) { result.push(cur.trim()); cur = ''; }
                    else { cur += c; }
                }
                result.push(cur.trim());
                return result;
            }

            let headers = parseLine(headerLine).map(h => h.toLowerCase().replace(/\s+/g, ' ').trim());

            // ─── 4. Auto-detect column indices by header name ───
            // Supports: "no", "uraian pekerjaan", "bobot (%)", "bobot", W1..Wn
            let uraianIdx = -1;
            let bobotIdx  = -1;
            let weekCols  = []; // array of {colIdx, weekNum}

            headers.forEach(function(h, i) {
                let clean = h.replace(/[^a-z0-9\s]/gi, '').trim();
                if (uraianIdx === -1 && (clean.includes('uraian') || clean.includes('pekerjaan') || clean.includes('item'))) {
                    uraianIdx = i;
                } else if (bobotIdx === -1 && (clean.includes('bobot') || clean.includes('berat') || clean.includes('weight'))) {
                    bobotIdx = i;
                } else if (/^w(\d+)$/.test(clean)) {
                    let wNum = parseInt(clean.replace('w', ''));
                    weekCols.push({ colIdx: i, weekNum: wNum });
                }
            });

            // Fallback: if no named header found, assume positional
            // Format tanpa "No": Uraian, Bobot, W1..Wn
            // Format dengan "No":  No, Uraian, Bobot, W1..Wn
            if (uraianIdx === -1 || bobotIdx === -1) {
                // Check if first column looks like a number header (No)
                let h0 = headers[0];
                if (h0 === 'no' || h0 === '#' || h0 === 'nomor' || /^\d+$/.test(h0)) {
                    uraianIdx = 1;
                    bobotIdx  = 2;
                    for (let i = 3; i < headers.length; i++) {
                        if (/^w\d+$/i.test(headers[i])) weekCols.push({ colIdx: i, weekNum: weekCols.length + 1 });
                    }
                } else {
                    uraianIdx = 0;
                    bobotIdx  = 1;
                    for (let i = 2; i < headers.length; i++) {
                        if (/^w\d+$/i.test(headers[i])) weekCols.push({ colIdx: i, weekNum: weekCols.length + 1 });
                    }
                }
            }

            // Sort weekCols by week number
            weekCols.sort((a, b) => a.weekNum - b.weekNum);

            if (weekCols.length === 0) {
                alert('Kolom minggu (W1, W2, ...) tidak ditemukan di header CSV.\nHeader yang terdeteksi: ' + headers.join(' | '));
                input.value = '';
                return;
            }

            // ─── 5. Parse data rows ───
            let parsedItems = [];
            for (let line of dataLines) {
                if (!line.trim()) continue;
                let cols = parseLine(line);

                let uraian = (cols[uraianIdx] || '').trim().replace(/^["']|["']$/g, '');
                let bobotStr = (cols[bobotIdx] || '').trim().replace(/,/g, '.'); // handle decimal comma
                let bobot = parseFloat(bobotStr) || 0;

                // Skip: empty uraian, TOTAL row, bobot = 0
                if (!uraian || /^total/i.test(uraian) || bobot <= 0) continue;

                let weekVals = [];
                for (let wc of weekCols) {
                    let raw = (cols[wc.colIdx] || '').trim().replace(/,/g, '.');
                    weekVals.push(parseFloat(raw) || 0);
                }

                parsedItems.push({ uraian, bobot, weekVals });
            }

            if (parsedItems.length === 0) {
                alert('Tidak ada data item pekerjaan yang valid ditemukan.\n\n' +
                      'Header terdeteksi: ' + headers.join(' | ') + '\n' +
                      'Uraian di kolom: ' + uraianIdx + ', Bobot di kolom: ' + bobotIdx + '\n\n' +
                      'Pastikan kolom Uraian Pekerjaan dan Bobot (%) terisi dengan benar.');
                input.value = '';
                return;
            }

            // Confirm before replacing
            let msg = `File "${file.name}" berhasil dibaca.\n\n` +
                      `Ditemukan ${parsedItems.length} item pekerjaan.\n` +
                      `Kolom minggu: ${weekCols.length} (template) → ${totalMinggu} (kontrak).\n\n` +
                      `Data tabel yang ada saat ini akan DIGANTI. Lanjutkan?`;
            
            if (!confirm(msg)) {
                input.value = '';
                return;
            }

            // Clear existing tbody
            $('#tableJadwalMonev tbody').empty();

            // Populate table with parsed data
            parsedItems.forEach(function(item, rowIdx) {
                let weekCells = '';
                for (let w = 0; w < totalMinggu; w++) {
                    let wVal = (w < item.weekVals.length) ? item.weekVals[w] : 0;
                    let hasVal = wVal > 0;
                    weekCells += `<td class="p-1 text-center week-cell" style="${hasVal ? 'background:rgba(99,102,241,0.08);' : ''}">
                        <input type="number" step="0.01" name="week_val[${rowIdx}][${w}]"
                            class="form-control form-control-sm text-center week-input p-0 border-0 shadow-none"
                            value="${hasVal ? wVal : ''}"
                            placeholder="" min="0" max="100"
                            style="width:48px; font-size:0.68rem; background:transparent; ${hasVal ? 'color:#4338ca; font-weight:700;' : ''}"
                            oninput="onWeekInputChange(this)">
                    </td>`;
                }

                let newRow = `
                <tr class="jadwal-row" data-row="${rowIdx}">
                    <td style="position:sticky; left:0; background:#fff; z-index:1;">
                        <div class="d-flex align-items-center gap-1">
                            <span class="text-muted row-num small fw-bold">${rowIdx + 1}.</span>
                            <input type="text" name="item_pekerjaan[]" class="form-control form-control-sm border-0 shadow-none" value="${escHtml(item.uraian)}" required>
                        </div>
                    </td>
                    <td style="position:sticky; left:180px; background:#fff; z-index:1;">
                        <input type="number" step="0.01" name="bobot[]" class="form-control form-control-sm jadwal-bobot-input text-center border-primary" value="${item.bobot.toFixed(2)}" required min="0.01" max="100">
                    </td>
                    ${weekCells}
                    <td class="text-center" style="position:sticky; right:0; background:#fff; z-index:1;">
                        <button type="button" class="btn btn-xs btn-outline-danger" onclick="deleteJadwalRow(this)"><i class="bx bx-trash"></i></button>
                    </td>
                </tr>`;
                $('#tableJadwalMonev tbody').append(newRow);
            });

            updateBobotIndicator();
            updateWeekFooters();
            input.value = '';

            // Success notification
            let totalBobot = parsedItems.reduce((s, i) => s + i.bobot, 0);
            let toastClass = Math.abs(totalBobot - 100) <= 0.01 ? 'text-bg-success' : 'text-bg-warning';
            let toastMsg = Math.abs(totalBobot - 100) <= 0.01 
                ? `✅ ${parsedItems.length} item berhasil dimuat! Total bobot: ${totalBobot.toFixed(2)}%`
                : `⚠️ ${parsedItems.length} item dimuat, total bobot = ${totalBobot.toFixed(2)}% (belum 100%). Silakan sesuaikan.`;

            let toastHtml = `
                <div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999;">
                    <div id="toastUpload" class="toast show align-items-center ${toastClass} border-0 shadow-lg">
                        <div class="d-flex">
                            <div class="toast-body fw-semibold">${toastMsg}</div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="$(this).closest('.position-fixed').remove()"></button>
                        </div>
                    </div>
                </div>`;
            $('body').append(toastHtml);
            setTimeout(() => $('#toastUpload').closest('.position-fixed').remove(), 7000);
        };

        reader.readAsText(file, 'UTF-8');
    }

    // CSV line parser (handles quoted fields)
    function parseCsvLine(line) {
        let result = [];
        let current = '';
        let inQuotes = false;
        for (let i = 0; i < line.length; i++) {
            let c = line[i];
            if (c === '"') {
                inQuotes = !inQuotes;
            } else if (c === ',' && !inQuotes) {
                result.push(current.trim());
                current = '';
            } else {
                current += c;
            }
        }
        result.push(current.trim());
        return result;
    }

    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function toggleFullscreenTable() {
        const container = document.getElementById('jadwalTableContainer');
        if (!document.fullscreenElement && !document.webkitFullscreenElement) {
            // Unhide table for fullscreen
            container.classList.remove('d-none', 'd-md-block');
            
            // Short delay to allow display block to apply before requesting fullscreen
            setTimeout(() => {
                if (container.requestFullscreen) {
                    container.requestFullscreen().then(() => {
                        if (screen.orientation && screen.orientation.lock) {
                            screen.orientation.lock('landscape').catch(e => console.log('Orientation lock not supported', e));
                        }
                    }).catch(err => {
                        console.error("Error attempting to enable fullscreen:", err);
                    });
                } else if (container.webkitRequestFullscreen) { /* Safari */
                    container.webkitRequestFullscreen();
                } else if (container.msRequestFullscreen) { /* IE11 */
                    container.msRequestFullscreen();
                }
                container.classList.add('bg-white', 'p-3', 'overflow-auto');
            }, 50);
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) { /* Safari */
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) { /* IE11 */
                document.msExitFullscreen();
            }
        }
    }

    // Handle native fullscreen exit (ESC key or native Back button)
    document.addEventListener('fullscreenchange', () => {
        const container = document.getElementById('jadwalTableContainer');
        if (!document.fullscreenElement) {
            container.classList.remove('bg-white', 'p-3', 'overflow-auto');
            container.classList.add('d-none', 'd-md-block');
        }
    });
    
    document.addEventListener('webkitfullscreenchange', () => {
        const container = document.getElementById('jadwalTableContainer');
        if (!document.webkitFullscreenElement) {
            container.classList.remove('bg-white', 'p-3', 'overflow-auto');
            container.classList.add('d-none', 'd-md-block');
        }
    });

    // Schedule Dynamic Input Rows — Per-Week Column Grid

    function addJadwalRow() {
        let totalMinggu = parseInt($('input[name="total_minggu"]').val()) || 0;
        let rowIdx = $('#tableJadwalMonev tbody tr.jadwal-row').length;
        
        let weekCells = '';
        for (let w = 0; w < totalMinggu; w++) {
            weekCells += `<td class="p-1 text-center week-cell">
                <input type="number" step="0.01" name="week_val[${rowIdx}][${w}]"
                    class="form-control form-control-sm text-center week-input p-0 border-0 shadow-none"
                    placeholder="" min="0" max="100"
                    style="width:48px; font-size:0.68rem; background:transparent;"
                    oninput="onWeekInputChange(this)">
            </td>`;
        }
        
        let newRow = `
        <tr class="jadwal-row" data-row="${rowIdx}">
            <td style="position:sticky; left:0; background:#fff; z-index:1;">
                <div class="d-flex align-items-center gap-1">
                    <span class="text-muted row-num small fw-bold">${rowIdx + 1}.</span>
                    <input type="text" name="item_pekerjaan[]" class="form-control form-control-sm border-0 shadow-none" placeholder="e.g. Pekerjaan Sipil..." required>
                </div>
            </td>
            <td style="position:sticky; left:180px; background:#fff; z-index:1;">
                <input type="number" step="0.01" name="bobot[]" class="form-control form-control-sm jadwal-bobot-input text-center border-primary" placeholder="0.00" required min="0.01" max="100">
            </td>
            ${weekCells}
            <td class="text-center" style="position:sticky; right:0; background:#fff; z-index:1;">
                <button type="button" class="btn btn-xs btn-outline-danger" onclick="deleteJadwalRow(this)"><i class="bx bx-trash"></i></button>
            </td>
        </tr>`;
        
        $('#tableJadwalMonev tbody').append(newRow);
        updateBobotIndicator();
        updateWeekFooters();
    }

    function deleteJadwalRow(btn) {
        let rowCount = $('#tableJadwalMonev tbody tr.jadwal-row').length;
        if (rowCount > 1) {
            $(btn).closest('tr').remove();
            // Renumber rows
            $('#tableJadwalMonev tbody tr.jadwal-row').each(function(i) {
                $(this).attr('data-row', i);
                $(this).find('.row-num').text((i + 1) + '.');
            });
            updateBobotIndicator();
            updateWeekFooters();
        } else {
            alert('Proyek minimal harus memiliki 1 item pekerjaan.');
        }
    }

    // Called on every week-cell input change — updates footer totals live
    function onWeekInputChange(input) {
        let td = $(input).closest('td');
        let wStr = $(input).val() || '';
        let val = parseFloat(wStr.toString().replace(/,/g, '.')) || 0;
        if (val > 0) {
            td.css('background', 'rgba(99,102,241,0.08)');
            $(input).css({'color': '#4338ca', 'font-weight': '700'});
        } else {
            td.css('background', '');
            $(input).css({'color': '', 'font-weight': ''});
        }
        updateWeekFooters();
    }

    // Recalculate footer Progress Rencana & Total Kumulatif per column
    function updateWeekFooters() {
        let totalMinggu = parseInt($('input[name="total_minggu"]').val()) || 0;
        
        for (let w = 0; w < totalMinggu; w++) {
            let colSum = 0;
            $('#tableJadwalMonev tbody tr.jadwal-row').each(function(rowIdx) {
                // Check both possible name formats
                let wInput = $(this).find(`input[name="week_val[${rowIdx}][${w}]"]`);
                if (wInput.length === 0) {
                    // Try to find by position (for newly added rows)
                    wInput = $(this).find('.week-input').eq(w);
                }
                let wStr = wInput.val() || '';
                let v = parseFloat(wStr.toString().replace(/,/g, '.')) || 0;
                colSum += v;
            });
            
            // Update weekly progress row
            let weekCell = $(`#foot-week-${w}`);
            if (weekCell.length) {
                weekCell.text(colSum > 0 ? colSum.toFixed(2) : '—');
            }
        }
        
        // Update cumulative totals
        let cumTotal = 0;
        for (let w = 0; w < totalMinggu; w++) {
            let weekText = $(`#foot-week-${w}`).text();
            let weekVal = parseFloat(weekText.toString().replace(/,/g, '.')) || 0;
            cumTotal += weekVal;
            let cumCell = $(`#foot-cum-${w}`);
            if (cumCell.length) {
                cumCell.text(cumTotal > 0 ? cumTotal.toFixed(2) : '—');
            }
        }
    }

    // Bobot Counter Guard validation
    function updateBobotIndicator() {
        let total = 0;
        $('.jadwal-bobot-input').each(function() {
            let bStr = $(this).val() || '';
            let val = parseFloat(bStr.toString().replace(/,/g, '.'));
            if (!isNaN(val)) {
                total += val;
            }
        });
        
        let indicator = $('#jadwal-bobot-indicator');
        indicator.text(total.toFixed(2) + '%');
        
        if (Math.abs(total - 100.0) <= 0.01) {
            indicator.removeClass('bg-danger').addClass('bg-success');
            $('#btnJadwalSave').prop('disabled', false);
        } else {
            indicator.removeClass('bg-success').addClass('bg-danger');
            $('#btnJadwalSave').prop('disabled', true);
        }
    }

    // Daily progress logs actions
    function editHarian(item) {
        $('#harian_edit_id').val(item.id);
        $('#harian_item_id').val(item.item_id);
        $('#harian_tanggal').val(item.tanggal);
        $('#harian_volume').val(item.volume_selesai);
        $('#harian_satuan').val(item.satuan);
        $('#harian_lokasi').val(item.lokasi);
        $('#harian_tenaga').val(item.tenaga_kerja);
        $('#harian_peralatan').val(item.peralatan);
        $('#harian_kendala').val(item.kendala);
        $('#harian_keterangan').val(item.keterangan);
        $('#harian_progres').val(item.progres_persen);
        
        // For editing, photos are not mandatory if old ones exist
        $('#edit_photo_reminder').show();
        
        $('#form-harian-title').html('<i class="bx bx-edit me-1"></i> Edit Laporan Harian (ID: ' + item.id + ')');
        $('#btnHarianCancel').show();
        
        // Scroll to form harian
        switchSPATab('harian-tab');
    }

    function resetHarianForm() {
        $('#harian_edit_id').val('');
        $('#formHarianMonev')[0].reset();
        $('#edit_photo_reminder').hide();
        $('#form-harian-title').html('<i class="bx bx-edit me-1"></i> Form Laporan Progres Fisik Harian');
        $('#btnHarianCancel').hide();
    }

    function verifyHarian(id) {
        if (confirm('Apakah Anda yakin ingin memvalidasi/verifikasi laporan progres harian ini?')) {
            $.ajax({
                url: '<?= base_url('dashboard/monev/verify-harian/') ?>' + id,
                type: 'POST',
                success: function(res) {
                    if (res.status === 'success') {
                        alert(res.message);
                        window.location.reload();
                    } else {
                        alert('Error: ' + res.message);
                    }
                },
                error: function() {
                    alert('Gagal memvalidasi laporan harian.');
                }
            });
        }
    }

    // Photo thumbnails popup trigger
    function viewFieldPhotos(awal, proses, hasil) {
        $('#photo_view_awal').attr('src', awal);
        $('#photo_view_proses').attr('src', proses);
        $('#photo_view_hasil').attr('src', hasil);
        $('#photosViewModal').modal('show');
    }

    // Cetak Laporan Keseluruhan Proyek
    function printLaporanProyek() {
        window.print();
    }

    // Laporan Rekapitulasi Prints
    function printBeritaAcara() {
        $('#modalBeritaAcara').modal('show');
    }

    function doPrintBA() {
        let printContent = document.getElementById('ba-print-area').innerHTML;
        let originalBody = document.body.innerHTML;
        document.body.innerHTML = `<html><head><title>Berita Acara Kemajuan Pekerjaan</title>
        <style>
            @page { size: A4 portrait; margin: 2cm 2.5cm; }
            body { font-family: Arial, sans-serif; font-size: 11pt; color: #000; }
            h3, h4 { text-align: center; margin: 4px 0; }
            table { width: 100%; border-collapse: collapse; margin: 8px 0; }
            table th, table td { border: 1px solid #333; padding: 4px 7px; font-size: 10pt; }
            table th { background: #f0f0f0; font-weight: bold; }
            .text-center { text-align: center; }
            .fw-bold { font-weight: bold; }
            .no-border td, .no-border th { border: none; }
            .sign-area { margin-top: 30px; }
            .sign-col { display: inline-block; width: 45%; vertical-align: top; text-align: center; }
            hr { border: 1px solid #333; margin: 10px 0; }
        </style></head><body>${printContent}</body></html>`;
        window.print();
        document.body.innerHTML = originalBody;
        window.location.reload();
    }

    function toggleScurveView() {
        let chartView = document.getElementById('scurveChartView');
        let tableView = document.getElementById('scurveTableView');
        let btn = document.getElementById('btnToggleScurve');
        if (tableView.style.display === 'none') {
            tableView.style.display = 'block';
            chartView.style.display = 'none';
            btn.innerHTML = '<i class="bx bx-line-chart me-1"></i> Tampilkan Grafik';
        } else {
            tableView.style.display = 'none';
            chartView.style.display = 'block';
            btn.innerHTML = '<i class="bx bx-table me-1"></i> Tampilkan Tabel';
        }
    }

    function openMonthlyMc() {
        alert("Rekap MC (Monthly Certificate) Bulan ini:\n\nRealisasi Kumulatif: <?= isset($actWeight) ? number_format($actWeight, 2) : '0' ?>%\nStatus Pembayaran: Terverifikasi oleh Konsultan Pengawas.");
    }

    function exportBaAsDoc() {
        let content = document.getElementById('ba-print-area').innerHTML;
        let html = `<!DOCTYPE html><html><head>
            <meta charset="UTF-8">
            <title>Berita Acara Kemajuan Pekerjaan</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 11pt; color: #000; margin: 2cm 2.5cm; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #333; padding: 4px 7px; }
                th { background: #f0f0f0; }
            </style>
        </head><body>${content}</body></html>`;
        let blob = new Blob([html], { type: 'application/msword' });
        let a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        let namaProyek = '<?= isset($activeProyek) ? preg_replace('/[^a-zA-Z0-9]/', '_', $activeProyek['nama_proyek']) : 'Proyek' ?>';
        a.download = 'BA_Kemajuan_' + namaProyek + '_' + new Date().toISOString().slice(0,10) + '.doc';
        a.click();
    }

    // Chart.js S-curve line graph renderer
    function initScurveChart() {
        let ctx = document.getElementById('scurveChartCanvas').getContext('2d');
        
        let labels = <?= isset($scurve['labels']) ? json_encode($scurve['labels']) : '[]' ?>;
        let planData = <?= isset($scurve['plan']) ? json_encode($scurve['plan']) : '[]' ?>;
        let actualData = <?= isset($scurve['actual']) ? json_encode($scurve['actual']) : '[]' ?>;
        
        let scurveChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Rencana Target Kumulatif (%)',
                        data: planData,
                        borderColor: '#71dd37',
                        backgroundColor: 'rgba(113, 221, 55, 0.1)',
                        borderWidth: 3,
                        pointBackgroundColor: '#71dd37',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: '#71dd37',
                        tension: 0.25,
                        fill: false
                    },
                    {
                        label: 'Realisasi Fisik Kumulatif (%)',
                        data: actualData,
                        borderColor: '#ff3e1d',
                        backgroundColor: 'rgba(255, 62, 29, 0.15)',
                        borderWidth: 4,
                        pointBackgroundColor: '#ff3e1d',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: '#ff3e1d',
                        tension: 0.25,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                size: 12,
                                weight: 'bold'
                            },
                            color: '#566a7f'
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        padding: 12,
                        cornerRadius: 6
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: '#f2f2f2'
                        },
                        ticks: {
                            color: '#8e8e8e',
                            font: {
                                size: 11
                            }
                        }
                    },
                    y: {
                        min: 0,
                        max: 100,
                        grid: {
                            color: '#f2f2f2'
                        },
                        ticks: {
                            color: '#8e8e8e',
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    }

    // Filter Laporan Berdasarkan Waktu
    function filterLaporan() {
        let filter = document.getElementById('filterBulan').value;
        let rows = document.querySelectorAll('#tabelLogbook tbody tr.log-row');
        let today = new Date();
        
        rows.forEach(row => {
            let rowDate = new Date(row.getAttribute('data-tanggal'));
            let show = true;
            
            if (filter === 'this_month') {
                show = (rowDate.getMonth() === today.getMonth() && rowDate.getFullYear() === today.getFullYear());
            } else if (filter === 'last_month') {
                let lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                show = (rowDate.getMonth() === lastMonth.getMonth() && rowDate.getFullYear() === lastMonth.getFullYear());
            }
            
            row.style.display = show ? '' : 'none';
        });
    }

    // Fungsi Cetak Laporan Eksekutif (Menggunakan Popup Print agar 100% bebas dari blank page layout)
    function cetakLaporanEksekutif() {
        let printContents = document.getElementById('areaLaporanCetak').innerHTML;
        let projectName = "<?= esc($activeProyek['nama_proyek'] ?? '') ?>";
        let today = "<?= date('d/m/Y') ?>";
        
        let printWindow = window.open('', '', 'height=800,width=1000');
        printWindow.document.write('<html><head><title>Cetak Laporan Lapangan</title>');
        printWindow.document.write('<style>');
        printWindow.document.write('body { font-family: Arial, sans-serif; padding: 20px; }');
        printWindow.document.write('h2 { text-align: center; margin-bottom: 5px; }');
        printWindow.document.write('h4 { text-align: center; margin-top: 0; color: #555; margin-bottom: 30px; }');
        printWindow.document.write('table { width: 100%; border-collapse: collapse; font-size: 10pt; margin-bottom: 40px; }');
        printWindow.document.write('th, td { border: 1px solid #000; padding: 8px; text-align: left; vertical-align: top; }');
        printWindow.document.write('th { background-color: #f2f2f2 !important; -webkit-print-color-adjust: exact; }');
        printWindow.document.write('.no-print { display: none !important; }');
        printWindow.document.write('.print-photo-gallery { display: flex; flex-direction: row; flex-wrap: wrap; gap: 15px; }');
        printWindow.document.write('.print-img { width: 140px; height: 105px; object-fit: cover; border: 2px solid #ccc; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }');
        printWindow.document.write('.text-muted { color: #666; font-size: 0.8em; }');
        printWindow.document.write('.text-warning { color: #d97706; }');
        printWindow.document.write('.text-success { color: #16a34a; }');
        printWindow.document.write('.badge { padding: 3px 6px; border-radius: 4px; font-weight: bold; background: #eee; }');
        printWindow.document.write('.signature-block { display: flex; justify-content: space-between; padding: 0 50px; font-size: 11pt; margin-top: 50px; page-break-inside: avoid; }');
        printWindow.document.write('</style></head><body>');
        printWindow.document.write('<h2>LAPORAN EKSEKUTIF PROGRES PEKERJAAN LAPANGAN</h2>');
        printWindow.document.write('<h4>Proyek: ' + projectName + ' | Tanggal Cetak: ' + today + '</h4>');
        printWindow.document.write(printContents);
        printWindow.document.write('<div class="signature-block"><div>Disetujui Oleh,<br><br><br><br><br>_______________________<br>Pejabat Pembuat Komitmen (PPK)</div><div>Diperiksa Oleh,<br><br><br><br><br>_______________________<br>Konsultan Pengawas</div></div>');
        printWindow.document.write('</body></html>');
        
        printWindow.document.close();
        printWindow.focus();
        
        // Timeout to allow images to load before printing
        setTimeout(function() {
            printWindow.print();
            printWindow.close();
        }, 500);
    }
    
    // Geodesic Area Calculation (Cartesian coordinate approximation over Earth surface)
    function getPolygonArea(latlngs) {
        var area = 0;
        var numPoints = latlngs.length;
        if (numPoints > 2) {
            var d2r = Math.PI / 180;
            var r = 6378137; // Earth radius in meters
            for (var i = 0; i < numPoints; i++) {
                var p1 = latlngs[i];
                var p2 = latlngs[(i + 1) % numPoints];
                area += (p2.lng - p1.lng) * d2r * (2 + Math.sin(p1.lat * d2r) + Math.sin(p2.lat * d2r));
            }
            area = Math.abs(area * r * r / 2);
        }
        return area;
    }

    // Geodesic Polyline Distance Calculation
    function getPolylineLength(latlngs) {
        var length = 0;
        for (var i = 0; i < latlngs.length - 1; i++) {
            length += latlngs[i].distanceTo(latlngs[i+1]);
        }
        return length;
    }

    // Centroid calculation helper
    function getLayerCentroid(layer, type) {
        if (typeof layer.getBounds === 'function') {
            return layer.getBounds().getCenter();
        }
        if (typeof layer.getLatLng === 'function') {
            return layer.getLatLng();
        }
        return null;
    }

    // Format & Update Dynamic Measurement Badge
    function updateMeasurementBadge(layer, type, badgeId) {
        var badge = $('#' + badgeId);
        if (!badge.length) return;
        
        var text = '';
        if (type === 'polygon' || type === 'rectangle') {
            var latlngs = layer.getLatLngs();
            if (Array.isArray(latlngs) && Array.isArray(latlngs[0])) {
                latlngs = latlngs[0];
            }
            var area = getPolygonArea(latlngs);
            if (area >= 10000) {
                text = '<i class="bx bx-area me-1"></i> Batas Wilayah: ' + (area / 10000).toFixed(2).replace('.', ',') + ' Hektar';
            } else {
                text = '<i class="bx bx-area me-1"></i> Batas Wilayah: ' + Math.round(area).toLocaleString('id-ID') + ' m²';
            }
        } else if (type === 'polyline') {
            var latlngs = layer.getLatLngs();
            var length = getPolylineLength(latlngs);
            if (length >= 1000) {
                text = '<i class="bx bx-ruler me-1"></i> Panjang Jalan: ' + (length / 1000).toFixed(2).replace('.', ',') + ' Km';
            } else {
                text = '<i class="bx bx-ruler me-1"></i> Panjang Jalan: ' + Math.round(length).toLocaleString('id-ID') + ' m';
            }
        }
        
        if (text) {
            badge.html(text).fadeIn();
        } else {
            badge.hide();
        }
    }

    // ----------------------------------------------------
    // MAP 1: SETUP PROYEK BARU (NEW CONTRACT PICKER)
    // ----------------------------------------------------
    let mapPicker = null;
    let markerPicker = null;
    let drawnItems = null;

    function toggleMapPicker() {
        let container = document.getElementById('map_picker_container');
        if (container.style.display === 'none') {
            container.style.display = 'block';
            initMapPicker();
        } else {
            container.style.display = 'none';
        }
    }

    function initMapPicker() {
        if (mapPicker) {
            mapPicker.invalidateSize();
            return;
        }

        let defaultLat = -8.6823;
        let defaultLng = 116.1158; 
        
        let currentGps = $('#setup_gps').val();
        if (currentGps) {
            let parts = currentGps.split(',');
            if (parts.length === 2) {
                defaultLat = parseFloat(parts[0]);
                defaultLng = parseFloat(parts[1]);
            }
        }

        mapPicker = L.map('map_picker').setView([defaultLat, defaultLng], 12);
        
        L.tileLayer('https://mt0.google.com/vt/lyrs=y&hl=en&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            attribution: '© Google Satellite'
        }).addTo(mapPicker);

        // Geocoder Search Control
        L.Control.geocoder({
            defaultMarkGeocode: false
        })
        .on('markgeocode', function(e) {
            var latlng = e.geocode.center;
            mapPicker.setView(latlng, 16);
            updateMarkerAndInput(latlng);
        })
        .addTo(mapPicker);

        // Marker for pin location
        if (currentGps) {
            markerPicker = L.marker([defaultLat, defaultLng]).addTo(mapPicker);
        }

        // Click on map to update coordinate center pin
        mapPicker.on('click', function(e) {
            updateMarkerAndInput(e.latlng);
        });

        // Initialize Leaflet Draw
        drawnItems = new L.FeatureGroup();
        mapPicker.addLayer(drawnItems);

        var drawControl = new L.Control.Draw({
            draw: {
                polygon: {
                    allowIntersection: false,
                    showArea: true,
                    drawError: {
                        color: '#ff3e1d',
                        message: '<strong>Error:</strong> Batas koordinat tidak boleh saling bersilangan.'
                    },
                    shapeOptions: {
                        color: '#3b82f6',
                        fillColor: '#3b82f6',
                        fillOpacity: 0.3,
                        weight: 3
                    }
                },
                polyline: {
                    shapeOptions: {
                        color: '#eab308',
                        weight: 4
                    }
                },
                rectangle: {
                    shapeOptions: {
                        color: '#3b82f6',
                        fillColor: '#3b82f6',
                        fillOpacity: 0.3,
                        weight: 3
                    }
                },
                circle: false,
                circlemarker: false,
                marker: false
            },
            edit: {
                featureGroup: drawnItems,
                remove: true
            }
        });
        mapPicker.addControl(drawControl);

        // Event: Created Shape
        mapPicker.on(L.Draw.Event.CREATED, function (e) {
            var type = e.layerType;
            var layer = e.layer;
            
            drawnItems.clearLayers();
            drawnItems.addLayer(layer);
            
            var geojson = layer.toGeoJSON();
            $('#setup_polygon').val(JSON.stringify(geojson));
            
            updateMeasurementBadge(layer, type, 'polygon_measure_badge');
            
            var centroid = getLayerCentroid(layer, type);
            if (centroid) {
                updateMarkerAndInput(centroid);
            }
        });

        // Event: Edited Shapes
        mapPicker.on(L.Draw.Event.EDITED, function (e) {
            var layers = e.layers;
            layers.eachLayer(function (layer) {
                var geojson = layer.toGeoJSON();
                $('#setup_polygon').val(JSON.stringify(geojson));
                
                var type = layer instanceof L.Polygon ? (layer instanceof L.Rectangle ? 'rectangle' : 'polygon') : 'polyline';
                updateMeasurementBadge(layer, type, 'polygon_measure_badge');
                
                var centroid = getLayerCentroid(layer, type);
                if (centroid) {
                    updateMarkerAndInput(centroid);
                }
            });
        });

        // Event: Deleted Shapes
        mapPicker.on(L.Draw.Event.DELETED, function (e) {
            $('#setup_polygon').val('');
            $('#polygon_measure_badge').hide().html('');
        });

        // Load existing polygon if any
        try {
            var existingGeo = $('#setup_polygon').val().trim();
            if (existingGeo) {
                var parsed = JSON.parse(existingGeo);
                var geoLayer = L.geoJSON(parsed, {
                    style: {
                        color: '#3b82f6',
                        fillColor: '#3b82f6',
                        fillOpacity: 0.3,
                        weight: 3
                    }
                });
                geoLayer.eachLayer(function(l) {
                    drawnItems.addLayer(l);
                    var type = l instanceof L.Polygon ? (l instanceof L.Rectangle ? 'rectangle' : 'polygon') : 'polyline';
                    updateMeasurementBadge(l, type, 'polygon_measure_badge');
                });
                setTimeout(() => { mapPicker.fitBounds(geoLayer.getBounds()); }, 400);
            }
        } catch(err) {
            console.error("Gagal memuat existing geojson:", err);
        }
        
        setTimeout(() => { mapPicker.invalidateSize(); }, 300);
    }

    function updateMarkerAndInput(latlng) {
        if (markerPicker) {
            markerPicker.setLatLng(latlng);
        } else {
            markerPicker = L.marker(latlng).addTo(mapPicker);
        }
        $('#setup_gps').val(latlng.lat.toFixed(6) + ', ' + latlng.lng.toFixed(6));
        if (mapPicker) {
            mapPicker.panTo(latlng);
        }
    }

    // Sync input from manual textarea typings
    function syncPolygonFromText() {
        try {
            var txt = $('#setup_polygon').val().trim();
            if (!txt) {
                if(drawnItems) drawnItems.clearLayers();
                $('#polygon_measure_badge').hide();
                return;
            }
            var parsed = JSON.parse(txt);
            if (mapPicker && parsed) {
                drawnItems.clearLayers();
                var geoLayer = L.geoJSON(parsed).addTo(mapPicker);
                geoLayer.eachLayer(function(l) {
                    drawnItems.addLayer(l);
                    var type = l instanceof L.Polygon ? (l instanceof L.Rectangle ? 'rectangle' : 'polygon') : 'polyline';
                    updateMeasurementBadge(l, type, 'polygon_measure_badge');
                });
                mapPicker.fitBounds(geoLayer.getBounds());
                
                var center = geoLayer.getBounds().getCenter();
                updateMarkerAndInput(center);
            }
        } catch(e) {
            // Typing...
        }
    }

    // Client-side GeoJSON file uploader parsing
    function handleGeoJsonImport(event) {
        var file = event.target.files[0];
        if (!file) return;
        
        var reader = new FileReader();
        reader.onload = function(e) {
            try {
                var content = e.target.result;
                var geojson = JSON.parse(content);
                
                if (!geojson || (!geojson.type && !geojson.geometry)) {
                    alert("Format file GeoJSON tidak valid.");
                    return;
                }
                
                $('#setup_polygon').val(JSON.stringify(geojson, null, 2));
                
                if (!mapPicker) {
                    toggleMapPicker();
                }
                
                setTimeout(() => {
                    drawnItems.clearLayers();
                    var geoLayer = L.geoJSON(geojson, {
                        style: {
                            color: '#3b82f6',
                            fillColor: '#3b82f6',
                            fillOpacity: 0.3,
                            weight: 3
                        }
                    }).addTo(mapPicker);
                    
                    geoLayer.eachLayer(function(l) {
                        drawnItems.addLayer(l);
                        var type = l instanceof L.Polygon ? (l instanceof L.Rectangle ? 'rectangle' : 'polygon') : 'polyline';
                        updateMeasurementBadge(l, type, 'polygon_measure_badge');
                    });
                    
                    var bounds = geoLayer.getBounds();
                    mapPicker.fitBounds(bounds);
                    
                    var center = bounds.getCenter();
                    updateMarkerAndInput(center);
                    
                    alert("GeoJSON berhasil diimpor!");
                }, 500);
                
            } catch (err) {
                alert("Gagal membaca file: " + err.message);
            }
        };
        reader.readAsText(file);
    }

    // ----------------------------------------------------
    // MAP 2: EDIT KONTRAK PROYEK (EDIT MAP PICKER)
    // ----------------------------------------------------
    let editMapPicker = null;
    let editMarkerPicker = null;
    let editDrawnItems = null;

    function toggleEditMapPicker() {
        let container = document.getElementById('edit_map_picker_container');
        if (container.style.display === 'none') {
            container.style.display = 'block';
            initEditMapPicker();
        } else {
            container.style.display = 'none';
        }
    }

    function initEditMapPicker() {
        if (editMapPicker) {
            editMapPicker.invalidateSize();
            return;
        }

        let defaultLat = -8.6823;
        let defaultLng = 116.1158; 
        
        let currentGps = $('#edit_gps').val();
        if (currentGps) {
            let parts = currentGps.split(',');
            if (parts.length === 2) {
                defaultLat = parseFloat(parts[0]);
                defaultLng = parseFloat(parts[1]);
            }
        }

        editMapPicker = L.map('edit_map_picker').setView([defaultLat, defaultLng], 12);
        
        L.tileLayer('https://mt0.google.com/vt/lyrs=y&hl=en&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            attribution: '© Google Satellite'
        }).addTo(editMapPicker);

        // Geocoder Search Control
        L.Control.geocoder({
            defaultMarkGeocode: false
        })
        .on('markgeocode', function(e) {
            var latlng = e.geocode.center;
            editMapPicker.setView(latlng, 16);
            updateEditMarkerAndInput(latlng);
        })
        .addTo(editMapPicker);

        if (currentGps) {
            editMarkerPicker = L.marker([defaultLat, defaultLng]).addTo(editMapPicker);
        }

        editMapPicker.on('click', function(e) {
            updateEditMarkerAndInput(e.latlng);
        });

        // Initialize Leaflet Draw for Edit Form
        editDrawnItems = new L.FeatureGroup();
        editMapPicker.addLayer(editDrawnItems);

        var drawControl = new L.Control.Draw({
            draw: {
                polygon: {
                    allowIntersection: false,
                    showArea: true,
                    drawError: {
                        color: '#ff3e1d',
                        message: '<strong>Error:</strong> Jalur/bidang koordinat tidak boleh bersilangan.'
                    },
                    shapeOptions: {
                        color: '#3b82f6',
                        fillColor: '#3b82f6',
                        fillOpacity: 0.3,
                        weight: 3
                    }
                },
                polyline: {
                    shapeOptions: {
                        color: '#eab308',
                        weight: 4
                    }
                },
                rectangle: {
                    shapeOptions: {
                        color: '#3b82f6',
                        fillColor: '#3b82f6',
                        fillOpacity: 0.3,
                        weight: 3
                    }
                },
                circle: false,
                circlemarker: false,
                marker: false
            },
            edit: {
                featureGroup: editDrawnItems,
                remove: true
            }
        });
        editMapPicker.addControl(drawControl);

        // Event: Created Shape
        editMapPicker.on(L.Draw.Event.CREATED, function (e) {
            var type = e.layerType;
            var layer = e.layer;
            
            editDrawnItems.clearLayers();
            editDrawnItems.addLayer(layer);
            
            var geojson = layer.toGeoJSON();
            $('#edit_polygon').val(JSON.stringify(geojson));
            
            updateMeasurementBadge(layer, type, 'edit_polygon_measure_badge');
            
            var centroid = getLayerCentroid(layer, type);
            if (centroid) {
                updateEditMarkerAndInput(centroid);
            }
        });

        // Event: Edited Shapes
        editMapPicker.on(L.Draw.Event.EDITED, function (e) {
            var layers = e.layers;
            layers.eachLayer(function (layer) {
                var geojson = layer.toGeoJSON();
                $('#edit_polygon').val(JSON.stringify(geojson));
                
                var type = layer instanceof L.Polygon ? (layer instanceof L.Rectangle ? 'rectangle' : 'polygon') : 'polyline';
                updateMeasurementBadge(layer, type, 'edit_polygon_measure_badge');
                
                var centroid = getLayerCentroid(layer, type);
                if (centroid) {
                    updateEditMarkerAndInput(centroid);
                }
            });
        });

        // Event: Deleted Shapes
        editMapPicker.on(L.Draw.Event.DELETED, function (e) {
            $('#edit_polygon').val('');
            $('#edit_polygon_measure_badge').hide().html('');
        });

        // Load existing polygon in edit textarea
        try {
            var existingGeo = $('#edit_polygon').val().trim();
            if (existingGeo) {
                var parsed = JSON.parse(existingGeo);
                var geoLayer = L.geoJSON(parsed, {
                    style: {
                        color: '#3b82f6',
                        fillColor: '#3b82f6',
                        fillOpacity: 0.3,
                        weight: 3
                    }
                });
                geoLayer.eachLayer(function(l) {
                    editDrawnItems.addLayer(l);
                    var type = l instanceof L.Polygon ? (l instanceof L.Rectangle ? 'rectangle' : 'polygon') : 'polyline';
                    updateMeasurementBadge(l, type, 'edit_polygon_measure_badge');
                });
                setTimeout(() => { editMapPicker.fitBounds(geoLayer.getBounds()); }, 400);
            }
        } catch(err) {
            console.error("Gagal memuat existing edit geojson:", err);
        }
        
        setTimeout(() => { editMapPicker.invalidateSize(); }, 300);
    }

    function updateEditMarkerAndInput(latlng) {
        if (editMarkerPicker) {
            editMarkerPicker.setLatLng(latlng);
        } else {
            editMarkerPicker = L.marker(latlng).addTo(editMapPicker);
        }
        $('#edit_gps').val(latlng.lat.toFixed(6) + ', ' + latlng.lng.toFixed(6));
        if (editMapPicker) {
            editMapPicker.panTo(latlng);
        }
    }

    function syncEditPolygonFromText() {
        try {
            var txt = $('#edit_polygon').val().trim();
            if (!txt) {
                if(editDrawnItems) editDrawnItems.clearLayers();
                $('#edit_polygon_measure_badge').hide();
                return;
            }
            var parsed = JSON.parse(txt);
            if (editMapPicker && parsed) {
                editDrawnItems.clearLayers();
                var geoLayer = L.geoJSON(parsed).addTo(editMapPicker);
                geoLayer.eachLayer(function(l) {
                    editDrawnItems.addLayer(l);
                    var type = l instanceof L.Polygon ? (l instanceof L.Rectangle ? 'rectangle' : 'polygon') : 'polyline';
                    updateMeasurementBadge(l, type, 'edit_polygon_measure_badge');
                });
                editMapPicker.fitBounds(geoLayer.getBounds());
                
                var center = geoLayer.getBounds().getCenter();
                updateEditMarkerAndInput(center);
            }
        } catch(e) {
            // typing...
        }
    }

    function handleEditGeoJsonImport(event) {
        var file = event.target.files[0];
        if (!file) return;
        
        var reader = new FileReader();
        reader.onload = function(e) {
            try {
                var content = e.target.result;
                var geojson = JSON.parse(content);
                
                if (!geojson || (!geojson.type && !geojson.geometry)) {
                    alert("Format file GeoJSON tidak valid.");
                    return;
                }
                
                $('#edit_polygon').val(JSON.stringify(geojson, null, 2));
                
                if (!editMapPicker) {
                    toggleEditMapPicker();
                }
                
                setTimeout(() => {
                    editDrawnItems.clearLayers();
                    var geoLayer = L.geoJSON(geojson, {
                        style: {
                            color: '#3b82f6',
                            fillColor: '#3b82f6',
                            fillOpacity: 0.3,
                            weight: 3
                        }
                    }).addTo(editMapPicker);
                    
                    geoLayer.eachLayer(function(l) {
                        editDrawnItems.addLayer(l);
                        var type = l instanceof L.Polygon ? (l instanceof L.Rectangle ? 'rectangle' : 'polygon') : 'polyline';
                        updateMeasurementBadge(l, type, 'edit_polygon_measure_badge');
                    });
                    
                    var bounds = geoLayer.getBounds();
                    editMapPicker.fitBounds(bounds);
                    
                    var center = bounds.getCenter();
                    updateEditMarkerAndInput(center);
                    
                    alert("GeoJSON berhasil diimpor!");
                }, 500);
                
            } catch (err) {
                alert("Gagal membaca file: " + err.message);
            }
        };
        reader.readAsText(file);
    }

    // Reset map size on modal shown
    $('#setupProyekModal').on('shown.bs.modal', function () {
        if(mapPicker && document.getElementById('map_picker_container').style.display !== 'none') {
            mapPicker.invalidateSize();
        }
    });

    // ----------------------------------------------------
    // MAP 3: DASHBOARD MAP VISUALIZATION (MAIN SCREEN)
    // ----------------------------------------------------
    let dashboardMap = null;

    function initDashboardMap() {
        if (!document.getElementById('dashboard_map')) return;

        let gpsStr = "<?= esc($activeProyek['gps_kordinat'] ?? '') ?>";
        let polyStr = `<?= $activeProyek['polygon_lokasi'] ?? '' ?>`;

        let lat = -8.6823;
        let lng = 116.1158;
        let hasGps = false;
        let hasPoly = false;

        if (gpsStr) {
            let parts = gpsStr.split(',');
            if (parts.length === 2) {
                lat = parseFloat(parts[0]);
                lng = parseFloat(parts[1]);
                hasGps = true;
            }
        }

        dashboardMap = L.map('dashboard_map').setView([lat, lng], 13);

        L.tileLayer('https://mt0.google.com/vt/lyrs=y&hl=en&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            attribution: '© Google Satellite'
        }).addTo(dashboardMap);

        if (hasGps) {
            L.marker([lat, lng])
                .addTo(dashboardMap)
                .bindPopup('<div class="fw-bold small text-primary"><i class="bx bxs-briefcase me-1"></i> <?= esc($activeProyek['nama_proyek'] ?? "") ?></div><div class="text-muted text-xs">No: <?= esc($activeProyek['no_kontrak'] ?? "") ?></div>')
                .openPopup();
        }

        if (polyStr) {
            try {
                let geojson = JSON.parse(polyStr);
                hasPoly = true;
                
                let isLine = false;
                if (geojson.geometry && geojson.geometry.type === 'LineString') isLine = true;
                if (geojson.type === 'Feature' && geojson.geometry && geojson.geometry.type === 'LineString') isLine = true;
                if (geojson.type === 'LineString') isLine = true;

                let geoLayer = L.geoJSON(geojson, {
                    style: {
                        color: isLine ? '#eab308' : '#3b82f6',
                        fillColor: '#3b82f6',
                        fillOpacity: 0.35,
                        weight: isLine ? 5 : 3,
                        opacity: 0.8
                    }
                }).addTo(dashboardMap);
                
                setTimeout(() => {
                    dashboardMap.fitBounds(geoLayer.getBounds(), { padding: [20, 20] });
                }, 500);

            } catch(e) {
                console.error("Gagal mem-parsing polygon lokasi dashboard:", e);
            }
        }

        setTimeout(() => {
            dashboardMap.invalidateSize();
        }, 800);
    }

    // Tab size correction triggers
    $('#dashboard-tab').on('shown.bs.tab', function() {
        if (dashboardMap) {
            setTimeout(() => { dashboardMap.invalidateSize(); }, 200);
        }
    });

    // Weekly AI Analysis Generator
    function generateWeeklyAiAnalysis(proyekId, week, deviasi) {
        let btn = $('#btn_ai_' + week);
        let originalText = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> AJIP Berpikir...');

        $.ajax({
            url: '<?= base_url('dashboard/monev/weekly-ai-analysis') ?>',
            type: 'POST',
            data: {
                "<?= csrf_token() ?>": "<?= csrf_hash() ?>",
                proyek_id: proyekId,
                week: week,
                deviasi: deviasi
            },
            success: function(res) {
                btn.prop('disabled', false).html(originalText);
                if (res.status === 'success') {
                    $('#ai_hambatan_' + week).fadeOut(200, function() {
                        $(this).html(res.hambatan).fadeIn(300);
                    });
                    $('#ai_tindak_lanjut_' + week).fadeOut(200, function() {
                        $(this).html(res.tindak_lanjut).fadeIn(300);
                    });
                    btn.removeClass('btn-label-primary').addClass('btn-label-success').html('<i class="bx bx-check-circle me-1"></i> Teroptimalkan AI AJIP (Picu Ulang)');
                } else {
                    alert('Gagal: ' + res.message);
                }
            },
            error: function() {
                btn.prop('disabled', false).html(originalText);
                alert('Gagal terhubung ke AI. Silakan periksa pengaturan API Key Anda.');
            }
        });
    }

    $(window).on('resize', function() {
        if (dashboardMap) dashboardMap.invalidateSize();
        if (mapPicker) mapPicker.invalidateSize();
        if (editMapPicker) editMapPicker.invalidateSize();
    });
</script>



<style>
/* Sembunyikan tombol "Buy Now" atau Chat Icon bawaan tema Sneat */
.buy-now, .btn-buy-now {
    display: none !important;
}
</style>
<?= $this->endSection() ?>
