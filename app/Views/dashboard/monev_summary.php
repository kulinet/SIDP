<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?>

<?= $this->section('content') ?>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
        <div>
            <h4 class="fw-bold py-1 mb-0">
                <span class="text-muted fw-light">MONEV /</span> Summary Progres Proyek
            </h4>
            <p class="text-muted small mb-0">Ringkasan status proyek, deviasi, dan realisasi untuk pimpinan.</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <h5 class="card-title mb-0 fw-bold">Daftar Proyek & Deviasi</h5>
            <a href="<?= base_url('dashboard/export-monev-summary') ?>" class="btn btn-sm btn-success shadow-sm">
                <i class="bx bx-file me-1"></i> Export Excel
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive text-nowrap">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Proyek</th>
                            <th>No Kontrak</th>
                            <th>Penyedia</th>
                            <th>Tgl Selesai</th>
                            <th>Realisasi Kumulatif</th>
                            <th>Deviasi Kumulatif</th>
                            <th>Deviasi Mingguan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($summaryData)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">Belum ada data proyek.</td>
                        </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($summaryData as $row): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><span class="fw-semibold text-primary"><?= esc($row['nama_proyek']) ?></span></td>
                                <td><?= esc($row['no_kontrak']) ?></td>
                                <td><?= esc($row['nama_penyedia']) ?></td>
                                <td><?= date('d M Y', strtotime($row['tgl_selesai'])) ?></td>
                                <td><span class="fw-bold text-primary"><?= number_format($row['realisasi'], 2) ?>%</span></td>
                                <td>
                                    <span class="badge <?= $row['deviasi_kumulatif'] < 0 ? 'bg-label-danger' : 'bg-label-success' ?>">
                                        <?= number_format($row['deviasi_kumulatif'], 2) ?>%
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $row['deviasi_mingguan'] < 0 ? 'bg-label-danger' : 'bg-label-success' ?>">
                                        <?= number_format($row['deviasi_mingguan'], 2) ?>%
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['status_indikator'] === 'Merah (Kritis)'): ?>
                                        <span class="badge bg-danger"><i class="bx bx-error-circle me-1"></i>Kritis/Terlambat</span>
                                    <?php elseif ($row['status_indikator'] === 'Kuning (Perhatian)'): ?>
                                        <span class="badge bg-warning text-dark"><i class="bx bx-time-five me-1"></i>Perhatian</span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><i class="bx bx-check-double me-1"></i>Sesuai Rencana</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="showChartModal(<?= $row['id'] ?>, '<?= esc($row['nama_proyek']) ?>')">
                                        <i class="bx bx-line-chart me-1"></i> Grafik & Detail
                                    </button>
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

<!-- Modal Detail Proyek -->
<div class="modal fade" id="chartModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title fw-bold" id="chartModalTitle">Grafik Kurva S & Deviasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center" id="chartLoading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Memuat data grafik...</p>
                </div>
                
                <div id="chartContent" style="display: none;">
                    <!-- Grafik -->
                    <div class="mb-4" style="height: 350px; position: relative;">
                        <canvas id="scurveCanvas"></canvas>
                    </div>
                    
                    <!-- Tabel Detail Deviasi -->
                    <h6 class="fw-bold mb-3"><i class="bx bx-table me-1"></i>Tabel Detail Deviasi Mingguan</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm text-center" style="font-size: 0.8rem;">
                            <thead class="table-light">
                                <tr id="tableHead"></tr>
                            </thead>
                            <tbody>
                                <tr id="rowProgRencana" class="bg-warning bg-opacity-25 fw-semibold text-dark"></tr>
                                <tr id="rowKumRencana" class="fw-bold" style="background:#fff3cd; color:#d97706;"></tr>
                                <tr id="rowProgAktual" class="fw-bold text-primary"></tr>
                                <tr id="rowKumAktual" class="fw-bold" style="background:#dbeafe; color:#1d4ed8;"></tr>
                                <tr id="rowDeviasi" class="fw-bold"></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let scurveChartInstance = null;
    const chartModal = new bootstrap.Modal(document.getElementById('chartModal'));

    function showChartModal(proyekId, projectName) {
        document.getElementById('chartModalTitle').innerText = 'Grafik Kurva S & Deviasi: ' + projectName;
        document.getElementById('chartLoading').style.display = 'block';
        document.getElementById('chartContent').style.display = 'none';
        chartModal.show();

        fetch('<?= base_url("dashboard/monev/get-chart-data") ?>/' + proyekId)
            .then(response => response.json())
            .then(data => {
                document.getElementById('chartLoading').style.display = 'none';
                document.getElementById('chartContent').style.display = 'block';
                renderChartAndTable(data);
            })
            .catch(error => {
                console.error("Error fetching chart data:", error);
                document.getElementById('chartLoading').innerHTML = '<div class="alert alert-danger">Gagal memuat data grafik.</div>';
            });
    }

    function renderChartAndTable(data) {
        // Render Chart
        if (scurveChartInstance) {
            scurveChartInstance.destroy();
        }

        const ctx = document.getElementById('scurveCanvas').getContext('2d');
        scurveChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Rencana Kumulatif (%)',
                        data: data.plan,
                        borderColor: '#ffab00',
                        backgroundColor: 'rgba(255, 171, 0, 0.1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.3,
                        pointRadius: 4,
                        pointBackgroundColor: '#ffab00'
                    },
                    {
                        label: 'Realisasi Kumulatif (%)',
                        data: data.actual,
                        borderColor: '#696cff',
                        backgroundColor: 'rgba(105, 108, 255, 0.2)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.3,
                        pointRadius: 5,
                        pointBackgroundColor: '#696cff'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y + '%';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        min: 0,
                        max: 100,
                        ticks: { callback: function(value) { return value + '%'; } }
                    }
                }
            }
        });

        // Render Table
        let headHtml = '<th>Uraian</th>';
        let progRencanaHtml = '<td>Progress Rencana (%)</td>';
        let kumRencanaHtml = '<td>Total Kumulatif Rencana (%)</td>';
        let progAktualHtml = '<td>Progress Aktual (%)</td>';
        let kumAktualHtml = '<td>Total Kumulatif Aktual (%)</td>';
        let devHtml = '<td>Deviasi (+/-)</td>';

        let prevPlan = 0;
        let prevAct = 0;

        for (let i = 0; i < data.labels.length; i++) {
            headHtml += `<th>${data.labels[i]}</th>`;
            
            // Rencana
            let currentPlan = data.plan[i] !== null && data.plan[i] !== undefined ? data.plan[i] : null;
            let wPlan = currentPlan !== null ? (currentPlan - prevPlan) : null;
            if (currentPlan !== null) prevPlan = currentPlan;
            
            // Aktual
            let currentAct = data.actual[i] !== null && data.actual[i] !== undefined ? data.actual[i] : null;
            let wAct = currentAct !== null ? (currentAct - prevAct) : null;
            if (currentAct !== null) prevAct = currentAct;

            progRencanaHtml += `<td>${wPlan !== null ? wPlan.toFixed(2) : '-'}</td>`;
            kumRencanaHtml += `<td>${currentPlan !== null ? currentPlan.toFixed(2) : '-'}</td>`;
            
            progAktualHtml += `<td>${wAct !== null && wAct !== 0 ? wAct.toFixed(2) : '-'}</td>`;
            kumAktualHtml += `<td>${currentAct !== null && currentAct !== 0 ? currentAct.toFixed(2) : '-'}</td>`;
            
            let dev = data.deviasi[i];
            let devColor = 'inherit';
            let devText = '-';
            if (dev !== null && dev !== undefined) {
                devColor = dev < 0 ? '#dc3545' : '#198754';
                devText = dev.toFixed(2);
            }
            devHtml += `<td style="color: ${devColor};">${devText}</td>`;
        }

        document.getElementById('tableHead').innerHTML = headHtml;
        document.getElementById('rowProgRencana').innerHTML = progRencanaHtml;
        document.getElementById('rowKumRencana').innerHTML = kumRencanaHtml;
        document.getElementById('rowProgAktual').innerHTML = progAktualHtml;
        document.getElementById('rowKumAktual').innerHTML = kumAktualHtml;
        document.getElementById('rowDeviasi').innerHTML = devHtml;
    }
</script>
<?= $this->endSection() ?>
