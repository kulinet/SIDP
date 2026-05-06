<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<?php
    $totalMaster = array_sum(array_column($summary, 'master_pagu'));
    $totalRup    = array_sum(array_column($summary, 'rup_pagu'));
    
    // Hitung total paket
    $totalPaket = 0;
    foreach ($summary as $s) {
        foreach (($s['details'] ?? []) as $d) {
            $totalPaket += count($d['packages'] ?? []);
        }
    }
    
    $avgProgress = $totalMaster > 0 ? round(($totalRup / $totalMaster) * 100, 2) : 0;
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">
            <span class="text-muted fw-light">Data RUP /</span> RUP Per OPD
        </h4>
        <a href="<?= base_url('dashboard/clear-cache') ?>" class="btn btn-outline-danger btn-sm" data-confirm="Apakah Anda yakin ingin menghapus seluruh cache data? Hal ini akan memaksa sistem menarik ulang data terbaru dari API.">
            <i class="bx bx-refresh me-1"></i> Clear Cache
        </a>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-sm-6 col-lg-3 mb-4 mb-lg-0">
            <div class="card card-border-shadow-primary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-money"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0"><?= number_format($totalMaster, 0, ',', '.') ?></h4>
                    </div>
                    <p class="mb-1 fw-semibold">Total Pagu Master</p>
                    <p class="mb-0 text-muted" style="font-size: 0.8rem;">Seluruh OPD/Satker</p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 mb-4 mb-lg-0">
            <div class="card card-border-shadow-success h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-success"><i class="bx bx-check-shield"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0 text-success"><?= number_format($totalRup, 0, ',', '.') ?></h4>
                    </div>
                    <p class="mb-1 fw-semibold">Pagu Terumumkan</p>
                    <p class="mb-0 text-muted" style="font-size: 0.8rem;">Di aplikasi SIRUP</p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 mb-4 mb-lg-0">
            <div class="card card-border-shadow-info h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-info"><i class="bx bx-package"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0 text-info"><?= number_format($totalPaket, 0, ',', '.') ?></h4>
                    </div>
                    <p class="mb-1 fw-semibold">Total Paket RUP</p>
                    <p class="mb-0 text-muted" style="font-size: 0.8rem;">Penyedia & Swakelola</p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 mb-4 mb-lg-0">
            <div class="card card-border-shadow-warning h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-warning"><i class="bx bx-bar-chart-alt-2"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0 text-warning"><?= $avgProgress ?>%</h4>
                    </div>
                    <p class="mb-1 fw-semibold">Rata-rata Progress</p>
                    <div class="progress mt-2" style="height: 5px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?= min(100, $avgProgress) ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card shadow-sm border-0">
        <div class="card-header d-flex justify-content-between align-items-center border-bottom flex-wrap gap-2">
            <h5 class="card-title mb-0 fw-bold"><i class="bx bx-list-ul me-2"></i>Daftar Pengumuman RUP Per OPD</h5>
            
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Status Filter -->
                <select id="filter-status" class="form-select form-select-sm" style="width: auto;">
                    <option value="">Semua Status</option>
                    <option value="kritis">Kritis (< 50%)</option>
                    <option value="sedang">Sedang (50% - 99%)</option>
                    <option value="baik">Baik (≥ 100%)</option>
                </select>

                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-expand-all">
                        <i class="bx bx-expand-vertical me-1"></i> Expand All
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-collapse-all">
                        <i class="bx bx-collapse-vertical me-1"></i> Collapse All
                    </button>
                </div>

                <a href="<?= base_url('dashboard/export-rup-opd') ?>" class="btn btn-sm btn-success">
                    <i class="bx bx-file me-1"></i> Ekspor Laporan
                </a>
            </div>
        </div>
        <div class="card-body pt-4">
            <div class="table-responsive text-nowrap">
                <table class="table table-hover w-100" id="table-rup-opd">
                    <thead class="table-light">
                        <tr>
                            <th width="30"></th>
                            <th>Nama OPD / Satker</th>
                            <th>Pagu Master (Kegiatan)</th>
                            <th>Pagu Terumumkan (SIRUP)</th>
                            <th width="150">Persentase (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($summary as $s): 
                            $hasAnomaly = false;
                            foreach ($s['details'] as $det) {
                                if ($det['pagu_rup'] > $det['pagu_master']) {
                                    $hasAnomaly = true;
                                    break;
                                }
                            }
                        ?>
                        <tr data-details='<?= htmlspecialchars(json_encode($s['details']), ENT_QUOTES, 'UTF-8') ?>' 
                            data-kd-satker='<?= htmlspecialchars($s['kd_satker'], ENT_QUOTES) ?>'
                            data-percent='<?= $s['percent'] ?>'>
                            <td class="details-control text-center">
                                <button class="btn btn-sm btn-icon btn-outline-primary rounded-circle">
                                    <i class="bx bx-plus"></i>
                                </button>
                            </td>
                            <td>
                                <div class="fw-bold text-dark d-flex align-items-center gap-2">
                                    <?= $s['nama_satker'] ?>
                                    <?php if($hasAnomaly): ?>
                                        <span class="badge bg-label-danger animate__animated animate__pulse animate__infinite" style="font-size: 0.65rem;" title="Terdapat anomali distribusi pagu pada rincian program">
                                            <i class="bx bx-error-alt me-1"></i> Anomali
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted"><?= $s['kd_satker_str'] ?></small>
                            </td>
                            <td>
                                <div class="fw-bold"><?= number_format($s['master_pagu'], 0, ',', '.') ?></div>
                            </td>
                            <td>
                                <div class="fw-bold text-primary"><?= number_format($s['rup_pagu'], 0, ',', '.') ?></div>
                                <small class="text-muted"><?= $s['rup_paket'] ?> Paket Terumumkan</small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress w-100 me-2" style="height: 8px;">
                                        <div class="progress-bar <?= $s['percent'] >= 100 ? 'bg-success' : ($s['percent'] >= 50 ? 'bg-primary' : 'bg-danger') ?>" 
                                             role="progressbar" style="width: <?= min(100, $s['percent']) ?>%"></div>
                                    </div>
                                    <small class="fw-bold"><?= $s['percent'] ?>%</small>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
    function format(d, rowId) {
        let details = [];
        try {
            details = JSON.parse(d);
        } catch(e) {
            console.error("Failed to parse details:", e);
            return '<div class="alert alert-danger m-3">Gagal memuat rincian data.</div>';
        }
        
        let html = `
            <div class="p-3 bg-light rounded-3 ms-4 me-4 mb-3 border">
                <h6 class="fw-bold mb-3"><i class="bx bx-subdirectory-right me-1"></i> Rincian Program Kerja</h6>
                <table class="table table-sm table-bordered bg-white w-100">
                    <thead class="table-dark">
                        <tr>
                            <th width="40" class="text-center">Aksi</th>
                            <th>Nama Program</th>
                            <th class="text-end">Pagu Master</th>
                            <th class="text-end">Pagu RUP</th>
                            <th class="text-center">Keterumuman</th>
                        </tr>
                    </thead>
                    <tbody>`;
        
        details.forEach((p, idx) => {
            let statusClass = p.percent >= 100 ? 'text-success' : (p.percent > 0 ? 'text-primary' : 'text-danger');
            let icon = p.percent >= 100 ? 'bx-check-double' : (p.percent > 0 ? 'bx-check' : 'bx-x');
            let makTooltip = p.mak && p.mak !== '-' ? ` <i class="bx bx-info-circle text-muted" style="cursor:help" title="MAK: ${p.mak}"></i>` : '';
            
            let hasPackages = p.packages && p.packages.length > 0;
            let expandBtn = hasPackages ? `<button type="button" class="btn btn-sm btn-icon btn-outline-secondary rounded-circle btn-expand-prog" data-target="pkg-${rowId}-${idx}"><i class="bx bx-plus"></i></button>` : '';
            let anomalyBadge = p.pagu_rup > p.pagu_master ? ' <span class="badge bg-label-danger ms-1" style="font-size: 0.6rem;"><i class="bx bx-error-alt me-1"></i>Anomali</span>' : '';

            html += `
                <tr>
                    <td class="text-center">${expandBtn}</td>
                    <td class="fw-bold text-dark">${p.name}${makTooltip}${anomalyBadge}</td>
                    <td class="text-end">${new Intl.NumberFormat('id-ID').format(p.pagu_master)}</td>
                    <td class="text-end fw-bold">${new Intl.NumberFormat('id-ID').format(p.pagu_rup)}</td>
                    <td class="text-center">
                        <span class="badge bg-label-${p.percent >= 100 ? 'success' : (p.percent > 0 ? 'primary' : 'danger')}">
                            <i class="bx ${icon} me-1"></i> ${p.percent}%
                        </span>
                    </td>
                </tr>`;
                
            if (hasPackages) {
                html += `<tr id="pkg-${rowId}-${idx}" style="display: none;" class="bg-label-secondary pkg-row">
                    <td class="border-0"></td>
                    <td colspan="4" class="p-0 border-0">
                        <div class="p-3 border-start border-4 border-info ms-4 mb-3 mt-2 bg-white rounded shadow-sm">
                            <h6 class="small fw-bold text-info mb-3 text-uppercase"><i class="bx bx-layer me-2"></i>Rincian Paket RUP</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover table-bordered mb-0 w-100" style="font-size: 0.85rem;">
                                    <thead class="table-info">
                                        <tr>
                                            <th width="15%" class="text-dark fw-bold">Kode RUP</th>
                                            <th class="text-dark fw-bold">Nama Paket</th>
                                            <th width="12%" class="text-center text-dark fw-bold">Tipe</th>
                                            <th width="20%" class="text-end text-dark fw-bold">Pagu Paket</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;
                p.packages.forEach(pkg => {
                    let tipeBadge = pkg.tipe === 'Penyedia' ? 'bg-label-primary' : (pkg.tipe === 'Swakelola' ? 'bg-label-warning' : 'bg-label-secondary');
                    html += `<tr>
                        <td class="align-middle"><span class="text-muted font-monospace"><i class="bx bx-hash"></i>${pkg.kd_rup}</span></td>
                        <td class="align-middle" style="min-width: 250px; white-space: normal !important; word-wrap: break-word;">
                            <div class="fw-semibold text-dark mb-1">${pkg.nama_paket}</div>
                            <div class="text-muted extra-small" style="font-size: 0.7rem;"><i class="bx bx-barcode-reader me-1"></i>MAK: ${pkg.mak || '-'}</div>
                        </td>
                        <td class="align-middle text-center"><span class="badge ${tipeBadge} px-2 py-1" style="font-size: 0.7rem;">${pkg.tipe}</span></td>
                        <td class="align-middle text-end"><span class="fw-bold text-primary">Rp ${new Intl.NumberFormat('id-ID').format(pkg.pagu)}</span></td>
                    </tr>`;
                });
                html += `           </tbody>
                                </table>
                            </div>
                        </div>
                    </td>
                </tr>`;
            }
        });

        html += `</tbody></table></div>`;
        return html;
    }

    $(document).ready(function() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Merender Tabel...',
                html: 'Menyusun rincian progres RUP seluruh OPD.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
        }

        setTimeout(() => {
            var table = $('#table-rup-opd').DataTable({
                responsive: true,
                pageLength: 25,
                order: [[4, 'asc']], // Show lowest percentage first
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Cari OPD...",
                    lengthMenu: "_MENU_",
                },
                dom: '<"row"<"col-md-6"l><"col-md-6"f>>t<"row"<"col-md-6"i><"col-md-6"p>>'
            });
            
            // Custom Filtering
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                var filter = $('#filter-status').val();
                if (!filter) return true;
                
                var node = table.row(dataIndex).node();
                var percent = parseFloat($(node).attr('data-percent')) || 0;
                
                if (filter === 'kritis') return percent < 50;
                if (filter === 'sedang') return percent >= 50 && percent < 100;
                if (filter === 'baik') return percent >= 100;
                return true;
            });
            
            $('#filter-status').on('change', function() {
                table.draw();
            });

            // Add event listener for opening and closing details
            $('#table-rup-opd tbody').on('click', 'td.details-control', function () {
                var tr = $(this).closest('tr');
                var row = table.row(tr);
                var icon = $(this).find('i');
                var rowId = tr.attr('data-kd-satker') || row.index();
                rowId = String(rowId).replace(/[^a-zA-Z0-9_-]/g, '_'); // sanitize

                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('shown');
                    icon.removeClass('bx-minus').addClass('bx-plus');
                } else {
                    row.child(format(tr.attr('data-details'), rowId)).show();
                    tr.addClass('shown');
                    icon.removeClass('bx-plus').addClass('bx-minus');
                }
            });

            // Expand all
            $('#btn-expand-all').on('click', function() {
                $('#table-rup-opd tbody tr:not(.shown)').each(function() {
                    $(this).find('td.details-control').trigger('click');
                });
            });

            // Collapse all
            $('#btn-collapse-all').on('click', function() {
                $('#table-rup-opd tbody tr.shown').each(function() {
                    $(this).find('td.details-control').trigger('click');
                });
            });

            // Expand Program Details (Event Delegation)
            // Use native getElementById and style.display to avoid class toggle issues
            $(document).on('click', '.btn-expand-prog', function(e) {
                e.preventDefault();
                var targetId = $(this).data('target');
                var targetRow = document.getElementById(targetId);
                var icon = $(this).find('i');
                
                if (targetRow) {
                    if (targetRow.style.display === 'none') {
                        targetRow.style.display = 'table-row';
                        icon.removeClass('bx-plus').addClass('bx-minus');
                    } else {
                        targetRow.style.display = 'none';
                        icon.removeClass('bx-minus').addClass('bx-plus');
                    }
                } else {
                    console.error("Target row not found: " + targetId);
                }
            });

            if (typeof Swal !== 'undefined') Swal.close();
        }, 100);
    });
</script>
<style>
    .details-control { cursor: pointer; }
    .dataTables_filter input { border-radius: 5px; padding: 5px 10px; border: 1px solid #d9dee3; margin-left: 10px; }
</style>
<?= $this->endSection() ?>
