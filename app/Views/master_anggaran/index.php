<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?>
<?= $this->section('content') ?>

<!-- DataTables CSS CDN -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<style>
    .table-master-opd th {
        vertical-align: middle;
        font-weight: 700;
        font-size: 0.85rem;
    }
    .table-master-opd td {
        vertical-align: middle;
        font-size: 0.88rem;
    }
    .details-control { cursor: pointer; }
    .sub-table-prog {
        font-size: 0.82rem;
        background: #ffffff;
    }
    .sub-table-prog th {
        background-color: #1e293b !important;
        color: #ffffff !important;
        font-weight: 600;
        padding: 6px 8px;
    }
    .sub-table-prog td {
        padding: 6px 8px;
        vertical-align: middle;
    }
    .timeline-item {
        position: relative;
        padding-left: 25px;
        margin-bottom: 20px;
        border-left: 2px solid #e2e8f0;
    }
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -7px;
        top: 0px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: #4f46e5;
        border: 2px solid #ffffff;
    }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Header Title & Action Toolbar -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1">
                <span class="text-muted fw-light">Aplikasi /</span> Master Anggaran OPD
            </h4>
            <div class="text-muted small">
                Tabel Master Anggaran Program Kegiatan OPD & Penyesuaian Anggaran (Pergeseran / APBD-P) Tahun <?= esc($currentYear) ?>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="d-flex align-items-center bg-white rounded px-2 border shadow-sm">
                <span class="small fw-bold text-muted me-2"><i class="bx bx-calendar me-1"></i>Tahun:</span>
                <select id="select-global-year" class="form-select form-select-sm border-0 shadow-none font-monospace fw-bold text-primary" style="width: auto;">
                    <?php foreach($availableYears as $yr): ?>
                    <option value="<?= $yr ?>" <?= $yr == $currentYear ? 'selected' : '' ?>><?= $yr ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <a href="<?= base_url('master-anggaran/export?year=' . $currentYear) ?>" class="btn btn-sm btn-success shadow-sm">
                <i class="bx bx-download me-1"></i> Unduh CSV
            </a>
        </div>
    </div>

    <!-- Executive KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card card-border-shadow-primary h-100 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar avatar-sm me-2">
                            <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-wallet"></i></span>
                        </div>
                        <span class="fw-semibold text-muted small text-uppercase">Pagu Terkini</span>
                    </div>
                    <h5 class="mb-1 fw-bold text-primary">Rp <?= number_format($kpi['total_pagu_terkini'] ?? 0, 0, ',', '.') ?></h5>
                    <small class="text-muted">Total Anggaran Terkini (<?= $kpi['total_opd'] ?? 0 ?> OPD)</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card card-border-shadow-secondary h-100 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar avatar-sm me-2">
                            <span class="avatar-initial rounded bg-label-secondary"><i class="bx bx-money"></i></span>
                        </div>
                        <span class="fw-semibold text-muted small text-uppercase">Pagu Awal DPA</span>
                    </div>
                    <h5 class="mb-1 fw-bold text-dark">Rp <?= number_format($kpi['total_pagu_awal'] ?? 0, 0, ',', '.') ?></h5>
                    <small class="text-muted">Sebelum Pergeseran / APBD-P</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card card-border-shadow-<?= ($kpi['total_selisih'] ?? 0) != 0 ? 'warning' : 'success' ?> h-100 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar avatar-sm me-2">
                            <span class="avatar-initial rounded bg-label-<?= ($kpi['total_selisih'] ?? 0) != 0 ? 'warning' : 'success' ?>">
                                <i class="bx bx-transfer-alt"></i>
                            </span>
                        </div>
                        <span class="fw-semibold text-muted small text-uppercase">Net Selisih</span>
                    </div>
                    <h5 class="mb-1 fw-bold text-<?= ($kpi['total_selisih'] ?? 0) > 0 ? 'success' : (($kpi['total_selisih'] ?? 0) < 0 ? 'danger' : 'dark') ?>">
                        <?= ($kpi['total_selisih'] ?? 0) > 0 ? '+' : '' ?>Rp <?= number_format($kpi['total_selisih'] ?? 0, 0, ',', '.') ?>
                    </h5>
                    <small class="text-muted"><?= ($kpi['total_selisih'] ?? 0) == 0 ? 'Anggaran Balance' : 'Penyesuaian Kumulatif' ?></small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card card-border-shadow-info h-100 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-sm me-2">
                                <span class="avatar-initial rounded bg-label-info"><i class="bx bx-list-check"></i></span>
                            </div>
                            <span class="fw-semibold text-muted small text-uppercase">Program Kerja</span>
                        </div>
                        <?php if(!empty($kpi['total_edited'])): ?>
                        <span class="badge bg-label-warning" style="font-size: 0.7rem;">
                            <?= $kpi['total_edited'] ?> Diubah
                        </span>
                        <?php endif; ?>
                    </div>
                    <h5 class="mb-1 fw-bold text-info"><?= number_format($kpi['total_program'] ?? 0, 0, ',', '.') ?> Program</h5>
                    <small class="text-muted"><?= $kpi['total_edited'] ?? 0 ?> Program Mengalami Pergeseran</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-body">
            <div class="row align-items-end g-3">
                <div class="col-md-4">
                    <label class="form-label text-uppercase fw-bold small text-muted">Pencarian Cepat</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bx bx-search"></i></span>
                        <input type="text" id="custom-search" placeholder="Cari OPD atau Kode Program..." class="form-control">
                    </div>
                </div>
                <div class="col-md-5">
                    <label class="form-label text-uppercase fw-bold small text-muted">Filter Satuan Kerja / OPD</label>
                    <?php if(!$isRestrictedRole): ?>
                    <select id="filter-opd" class="form-select">
                        <option value="all">Semua OPD (<?= count($availableOpds) ?> Satker)</option>
                        <?php foreach($availableOpds as $opd): ?>
                        <option value="<?= esc($opd) ?>"><?= esc($opd) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php else: ?>
                    <input type="text" class="form-control" value="<?= esc($opdGrouped[0]['nama_satker'] ?? 'Satker Anda') ?>" readonly>
                    <input type="hidden" id="filter-opd" value="all">
                    <?php endif; ?>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary w-50" id="btn-expand-all">
                        <i class="bx bx-expand-vertical me-1"></i> Expand
                    </button>
                    <button type="button" class="btn btn-outline-secondary w-50" id="btn-collapse-all">
                        <i class="bx bx-collapse-vertical me-1"></i> Collapse
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold text-dark">
                <i class="bx bx-table me-2 text-primary"></i>Daftar Master Anggaran Per OPD & Program Kegiatan
            </h5>
            <span class="badge bg-label-primary font-monospace">Tahun <?= esc($currentYear) ?></span>
        </div>
        <div class="card-body pt-3">
            <div class="table-responsive">
                <table id="table-master-anggaran" class="table table-hover table-master-opd w-100">
                    <thead class="table-light">
                        <tr>
                            <th width="35" class="text-center"></th>
                            <th width="40" class="text-center">No.</th>
                            <th>Nama OPD / Satuan Kerja</th>
                            <th class="text-center" width="130">Jml Program</th>
                            <th class="text-end" width="180">Pagu Awal DPA</th>
                            <th class="text-end" width="180">Pagu Terkini</th>
                            <th class="text-end" width="150">Selisih (+/-)</th>
                            <th class="text-center" width="150">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1; 
                        foreach($opdGrouped as $opd): 
                        ?>
                        <tr data-programs='<?= htmlspecialchars(json_encode($opd['programs']), ENT_QUOTES, 'UTF-8') ?>'
                            data-satker='<?= htmlspecialchars($opd['nama_satker'], ENT_QUOTES) ?>'
                            data-kd-satker='<?= htmlspecialchars($opd['kd_satker'], ENT_QUOTES) ?>'>
                            <td class="details-control text-center align-middle">
                                <button class="btn btn-sm btn-icon btn-outline-primary rounded-circle">
                                    <i class="bx bx-plus"></i>
                                </button>
                            </td>
                            <td class="text-center font-monospace text-muted align-middle"><?= $no++ ?></td>
                            <td class="align-middle">
                                <div class="fw-bold text-dark"><?= esc($opd['nama_satker']) ?></div>
                                <span class="badge bg-label-secondary font-monospace" style="font-size: 0.7rem;"><?= esc($opd['kd_satker']) ?></span>
                            </td>
                            <td class="text-center align-middle font-monospace fw-semibold">
                                <?= $opd['jml_program'] ?> Program
                            </td>
                            <td class="text-end align-middle font-monospace text-muted">
                                Rp <?= number_format($opd['total_pagu_awal'], 2, ',', '.') ?>
                            </td>
                            <td class="text-end align-middle font-monospace fw-bold text-primary">
                                Rp <?= number_format($opd['total_pagu_terkini'], 2, ',', '.') ?>
                            </td>
                            <td class="text-end align-middle font-monospace <?= $opd['total_selisih'] > 0 ? 'text-success fw-bold' : ($opd['total_selisih'] < 0 ? 'text-danger fw-bold' : 'text-muted') ?>">
                                <?= $opd['total_selisih'] > 0 ? '+' : '' ?><?= number_format($opd['total_selisih'], 2, ',', '.') ?>
                            </td>
                            <td class="text-center align-middle">
                                <?php if($opd['total_edited'] > 0): ?>
                                <span class="badge bg-label-warning" title="<?= $opd['total_edited'] ?> program disesuaikan">
                                    <i class="bx bx-edit me-1"></i><?= $opd['total_edited'] ?> Pergeseran
                                </span>
                                <?php else: ?>
                                <span class="badge bg-label-success">
                                    <i class="bx bx-check me-1"></i>Original DPA
                                </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3" class="text-center fw-bold">TOTAL KESELURUHAN KABUPATEN</th>
                            <th class="text-center font-monospace fw-bold"><?= number_format($kpi['total_program'] ?? 0, 0, ',', '.') ?> Program</th>
                            <th class="text-end font-monospace fw-bold text-muted">Rp <?= number_format($kpi['total_pagu_awal'] ?? 0, 2, ',', '.') ?></th>
                            <th class="text-end font-monospace fw-bold text-primary">Rp <?= number_format($kpi['total_pagu_terkini'] ?? 0, 2, ',', '.') ?></th>
                            <th class="text-end font-monospace fw-bold <?= ($kpi['total_selisih'] ?? 0) != 0 ? 'text-warning' : 'text-success' ?>">
                                <?= ($kpi['total_selisih'] ?? 0) > 0 ? '+' : '' ?>Rp <?= number_format($kpi['total_selisih'] ?? 0, 2, ',', '.') ?>
                            </th>
                            <th class="text-center font-monospace fw-bold"><?= $kpi['total_edited'] ?? 0 ?> Program Diubah</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ==================== MODAL EDIT ANGGARAN ==================== -->
<div class="modal fade" id="modalEditAnggaran" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white fw-bold">
                    <i class="bx bx-edit me-2"></i>Penyesuaian Anggaran Program Kerja
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditAnggaran">
                <input type="hidden" id="edit_id" name="id">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <!-- Satker Info -->
                    <div class="mb-3 p-2 bg-light rounded border">
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Satuan Kerja / OPD</small>
                        <div class="fw-bold text-dark" id="edit_nama_satker">-</div>
                        <small class="text-muted" id="edit_prog_title">-</small>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small text-muted text-uppercase fw-bold">Pagu Awal DPA</label>
                            <input type="text" class="form-control font-monospace bg-light" id="edit_pagu_awal_display" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-muted text-uppercase fw-bold">Pagu Saat Ini</label>
                            <input type="text" class="form-control font-monospace bg-light fw-bold text-primary" id="edit_pagu_saat_ini" readonly>
                        </div>
                    </div>

                    <!-- Input Pagu Baru -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Pagu Anggaran Baru (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text fw-bold">Rp</span>
                            <input type="text" class="form-control form-control-lg font-monospace fw-bold text-dark" id="edit_pagu_baru" name="pagu_baru" required placeholder="0">
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <small class="text-muted" id="edit_selisih_preview">Selisih: Rp 0</small>
                        </div>
                    </div>

                    <!-- Jenis Perubahan -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Jenis Perubahan Anggaran <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit_jenis_perubahan" name="jenis_perubahan" required>
                            <option value="Pergeseran Anggaran">Pergeseran Anggaran (Antar Kegiatan/Sub Kegiatan)</option>
                            <option value="APBD Perubahan (APBD-P)">APBD Perubahan (APBD-P)</option>
                            <option value="Koreksi Data">Koreksi Data / Sinkronisasi DPA</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>

                    <!-- Komentar / Alasan Perubahan -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Komentar / Alasan Perubahan <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="edit_komentar" name="komentar" rows="3" required placeholder="Tuliskan nomor nota dinas / SK / alasan pergeseran anggaran..."></textarea>
                        <small class="text-muted" style="font-size: 0.75rem;">Komentar ini akan dicatat dalam audit trail histori perubahan anggaran.</small>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-edit">
                        <i class="bx bx-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== MODAL RIWAYAT / HISTORI ==================== -->
<div class="modal fade" id="modalHistoryAnggaran" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title text-white fw-bold">
                    <i class="bx bx-history me-2"></i>Riwayat Perubahan Anggaran Program
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3 p-3 bg-light rounded border">
                    <div class="fw-bold text-dark" id="hist_satker">-</div>
                    <div class="text-primary font-monospace fw-semibold" id="hist_program">-</div>
                </div>

                <h6 class="fw-bold text-dark mb-3"><i class="bx bx-time-five me-1"></i> Kronologi Perubahan:</h6>
                <div id="hist_timeline_container">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="mt-2 text-muted">Memuat riwayat...</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- DataTables JS CDN -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const CAN_EDIT = <?= $canEdit ? 'true' : 'false' ?>;

// Format child row showing program details for a Satker
function formatProgramsRow(programsJson, satkerName, kdSatker) {
    let progs = [];
    try {
        progs = JSON.parse(programsJson);
    } catch(e) {
        return '<div class="alert alert-danger m-3">Gagal memuat rincian program.</div>';
    }

    let html = `
        <div class="p-3 bg-light rounded-3 ms-4 me-4 mb-3 border shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bx bx-subdirectory-right text-primary me-1"></i> Rincian Program Kegiatan (${satkerName})
                </h6>
                <span class="badge bg-label-primary font-monospace">${progs.length} Program Kerja</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered sub-table-prog w-100">
                    <thead>
                        <tr>
                            <th width="35" class="text-center">No</th>
                            <th width="110">Kode Program</th>
                            <th>Nama Program Kerja</th>
                            <th width="140" class="text-end">Pagu Awal</th>
                            <th width="150" class="text-end">Pagu Terkini</th>
                            <th width="130" class="text-end">Selisih (+/-)</th>
                            <th width="170">Status / Alasan</th>
                            <th width="110" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>`;

    progs.forEach((p, idx) => {
        let selisih = parseFloat(p.selisih) || 0;
        let selisihClass = selisih > 0 ? 'text-success fw-bold' : (selisih < 0 ? 'text-danger fw-bold' : 'text-muted');
        let selisihFmt = (selisih > 0 ? '+' : '') + new Intl.NumberFormat('id-ID').format(selisih);
        
        let statusBadge = p.is_edited ? 
            `<span class="badge bg-label-warning text-wrap" style="font-size: 0.7rem;">${p.jenis_perubahan || 'Pergeseran'}</span>
             <div class="text-muted extra-small mt-1" style="font-size: 0.68rem;" title="${p.keterangan_perubahan || ''}">${p.keterangan_perubahan ? (p.keterangan_perubahan.length > 30 ? p.keterangan_perubahan.substring(0, 30) + '...' : p.keterangan_perubahan) : '-'}</div>` : 
            `<span class="badge bg-label-success" style="font-size: 0.7rem;">Original DPA</span>`;

        let actionBtns = `
            <div class="btn-group btn-group-sm">
                ${CAN_EDIT ? `
                <button type="button" class="btn btn-primary btn-xs btn-edit-prog" 
                    data-id="${p.id}"
                    data-satker="${encodeURIComponent(satkerName)}"
                    data-kd-prog="${p.kd_program_str}"
                    data-nama-prog="${encodeURIComponent(p.nama_program)}"
                    data-pagu-awal="${p.pagu_awal}"
                    data-pagu="${p.pagu_program}"
                    data-jenis="${p.jenis_perubahan || 'Pergeseran Anggaran'}"
                    data-komentar="${encodeURIComponent(p.keterangan_perubahan || '')}"
                    title="Edit / Sesuaikan Pagu Anggaran">
                    <i class="bx bx-edit"></i> Edit
                </button>` : ''}
                <button type="button" class="btn btn-outline-dark btn-xs btn-history-prog" 
                    data-id="${p.id}"
                    data-satker="${encodeURIComponent(satkerName)}"
                    data-prog="${encodeURIComponent(p.kd_program_str + ' - ' + p.nama_program)}"
                    title="Lihat Riwayat Perubahan">
                    <i class="bx bx-history"></i> Log
                </button>
            </div>`;

        html += `
            <tr id="prog-row-${p.id}">
                <td class="text-center font-monospace text-muted">${idx + 1}</td>
                <td class="font-monospace fw-semibold text-primary">${p.kd_program_str || '-'}</td>
                <td class="fw-semibold text-dark">${p.nama_program}</td>
                <td class="text-end font-monospace text-muted">Rp ${new Intl.NumberFormat('id-ID').format(p.pagu_awal)}</td>
                <td class="text-end font-monospace fw-bold text-dark">Rp ${new Intl.NumberFormat('id-ID').format(p.pagu_program)}</td>
                <td class="text-end font-monospace ${selisihClass}">${selisihFmt}</td>
                <td>${statusBadge}</td>
                <td class="text-center">${actionBtns}</td>
            </tr>`;
    });

    html += `</tbody></table></div></div>`;
    return html;
}

$(document).ready(function() {
    // Year Change
    $('#select-global-year').on('change', function() {
        window.location.href = '<?= base_url('master-anggaran') ?>?year=' + $(this).val();
    });

    // DataTable Init
    const table = $('#table-master-anggaran').DataTable({
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
        dom: '<"d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2"l>rtip',
        language: {
            lengthMenu: "Tampilkan _MENU_ OPD",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ OPD",
            zeroRecords: "Tidak ada data Satker / OPD yang sesuai",
            paginate: { next: "Next", previous: "Prev" }
        },
        order: [[2, 'asc']], // Order by OPD name
        columnDefs: [
            { orderable: false, targets: [0, 1] }
        ]
    });

    // Custom Search
    $('#custom-search').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Filter OPD
    $('#filter-opd').on('change', function() {
        const val = $(this).val();
        if (val === 'all' || !val) {
            table.column(2).search('').draw();
        } else {
            table.column(2).search('^' + $.fn.dataTable.util.escapeRegex(val) + '$', true, false).draw();
        }
    });

    // Expand / Collapse Child Rows
    $('#table-master-anggaran tbody').on('click', 'td.details-control', function () {
        var tr = $(this).closest('tr');
        var row = table.row(tr);
        var icon = $(this).find('i');
        var satkerName = tr.attr('data-satker') || 'Unknown';
        var kdSatker = tr.attr('data-kd-satker') || '-';

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('shown');
            icon.removeClass('bx-minus').addClass('bx-plus');
        } else {
            row.child(formatProgramsRow(tr.attr('data-programs'), satkerName, kdSatker)).show();
            tr.addClass('shown');
            icon.removeClass('bx-plus').addClass('bx-minus');
        }
    });

    // Expand All
    $('#btn-expand-all').on('click', function() {
        $('#table-master-anggaran tbody tr:not(.shown)').each(function() {
            $(this).find('td.details-control').trigger('click');
        });
    });

    // Collapse All
    $('#btn-collapse-all').on('click', function() {
        $('#table-master-anggaran tbody tr.shown').each(function() {
            $(this).find('td.details-control').trigger('click');
        });
    });

    // Helper: format currency input
    function formatCurrencyInput(val) {
        let num = String(val).replace(/[^0-9]/g, '');
        if (!num) return '0';
        return new Intl.NumberFormat('id-ID').format(num);
    }

    // Modal Edit Open
    $(document).on('click', '.btn-edit-prog', function() {
        let btn = $(this);
        let id = btn.data('id');
        let satker = decodeURIComponent(btn.data('satker'));
        let kdProg = btn.data('kd-prog');
        let namaProg = decodeURIComponent(btn.data('nama-prog'));
        let paguAwal = parseFloat(btn.data('pagu-awal')) || 0;
        let paguSaatIni = parseFloat(btn.data('pagu')) || 0;
        let jenis = btn.data('jenis') || 'Pergeseran Anggaran';
        let komentar = decodeURIComponent(btn.data('komentar') || '');

        $('#edit_id').val(id);
        $('#edit_nama_satker').text(satker);
        $('#edit_prog_title').text(kdProg + ' - ' + namaProg);
        $('#edit_pagu_awal_display').val('Rp ' + new Intl.NumberFormat('id-ID').format(paguAwal));
        $('#edit_pagu_saat_ini').val('Rp ' + new Intl.NumberFormat('id-ID').format(paguSaatIni));
        $('#edit_pagu_baru').val(new Intl.NumberFormat('id-ID').format(paguSaatIni));
        $('#edit_jenis_perubahan').val(jenis);
        $('#edit_komentar').val(komentar);

        updateSelisihPreview(paguSaatIni, paguSaatIni);

        $('#modalEditAnggaran').modal('show');
    });

    function updateSelisihPreview(currentPagu, newPagu) {
        let diff = newPagu - currentPagu;
        let diffFmt = (diff > 0 ? '+' : '') + new Intl.NumberFormat('id-ID').format(diff);
        let color = diff > 0 ? 'text-success fw-bold' : (diff < 0 ? 'text-danger fw-bold' : 'text-muted');
        $('#edit_selisih_preview').html(`Selisih terhadap pagu saat ini: <span class="${color}">Rp ${diffFmt}</span>`);
    }

    $('#edit_pagu_baru').on('input', function() {
        let val = $(this).val().replace(/[^0-9]/g, '');
        $(this).val(val ? new Intl.NumberFormat('id-ID').format(val) : '0');
        
        let currentPaguStr = $('#edit_pagu_saat_ini').val().replace(/[^0-9]/g, '');
        let currentPagu = parseFloat(currentPaguStr) || 0;
        let newPagu = parseFloat(val) || 0;
        updateSelisihPreview(currentPagu, newPagu);
    });

    // Form Edit Submit (AJAX)
    $('#formEditAnggaran').on('submit', function(e) {
        e.preventDefault();
        let submitBtn = $('#btn-save-edit');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...');

        $.ajax({
            url: '<?= base_url('master-anggaran/update') ?>',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                submitBtn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Simpan Perubahan');
                if (res.status === 'success') {
                    $('#modalEditAnggaran').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Gagal!', res.message || 'Terjadi kesalahan.', 'error');
                }
            },
            error: function() {
                submitBtn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Simpan Perubahan');
                Swal.fire('Error!', 'Gagal menghubungi server.', 'error');
            }
        });
    });

    // History Modal Open
    $(document).on('click', '.btn-history-prog', function() {
        let btn = $(this);
        let id = btn.data('id');
        let satker = decodeURIComponent(btn.data('satker'));
        let prog = decodeURIComponent(btn.data('prog'));

        $('#hist_satker').text(satker);
        $('#hist_program').text(prog);
        $('#hist_timeline_container').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="mt-2 text-muted">Memuat riwayat...</div>
            </div>
        `);

        $('#modalHistoryAnggaran').modal('show');

        $.ajax({
            url: '<?= base_url('master-anggaran/history') ?>/' + id,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    let logs = res.history || [];
                    if (logs.length === 0) {
                        $('#hist_timeline_container').html(`
                            <div class="alert alert-info py-3 mb-0">
                                <i class="bx bx-info-circle me-1"></i> Belum ada riwayat perubahan manual pada program kerja ini. Anggaran masih sesuai dengan DPA original.
                            </div>
                        `);
                        return;
                    }

                    let html = '<div class="timeline p-2">';
                    logs.forEach(log => {
                        let diff = parseFloat(log.selisih) || 0;
                        let diffBadge = diff > 0 ? 
                            `<span class="badge bg-label-success font-monospace">+Rp ${new Intl.NumberFormat('id-ID').format(diff)}</span>` : 
                            (diff < 0 ? `<span class="badge bg-label-danger font-monospace">-Rp ${new Intl.NumberFormat('id-ID').format(Math.abs(diff))}</span>` : '<span class="badge bg-label-secondary">Tidak Berubah</span>');

                        html += `
                            <div class="timeline-item">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge bg-primary">${log.jenis_perubahan}</span>
                                    <small class="text-muted font-monospace"><i class="bx bx-calendar me-1"></i>${log.created_at}</small>
                                </div>
                                <div class="fw-bold text-dark mt-1">
                                    Pagu: Rp ${new Intl.NumberFormat('id-ID').format(log.pagu_sebelum)} <i class="bx bx-right-arrow-alt"></i> Rp ${new Intl.NumberFormat('id-ID').format(log.pagu_sesudah)} ${diffBadge}
                                </div>
                                <div class="bg-white p-2 rounded border mt-2">
                                    <small class="text-muted d-block fw-bold" style="font-size:0.7rem;">ALASAN / KOMENTAR:</small>
                                    <div class="text-dark small">${log.komentar || '-'}</div>
                                </div>
                                <div class="text-muted extra-small mt-1" style="font-size:0.75rem;">
                                    <i class="bx bx-user me-1"></i>Diubah oleh: <b>${log.user_nama || 'User'}</b>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    $('#hist_timeline_container').html(html);
                } else {
                    $('#hist_timeline_container').html('<div class="alert alert-danger">Gagal memuat riwayat.</div>');
                }
            },
            error: function() {
                $('#hist_timeline_container').html('<div class="alert alert-danger">Gagal menghubungi server.</div>');
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
