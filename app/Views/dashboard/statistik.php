<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?>
<?= $this->section('content') ?>

<div class="container-xxl flex-grow-1 container-p-y">


    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
        <h4 class="fw-bold py-3 mb-0">
            <span class="text-muted fw-light">Dashboard /</span> <span id="dashboard-title">RUP Penyedia</span>
        </h4>
        
        <!-- Tab Pills for Switching -->
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button class="btn btn-success btn-sm" onclick="exportData()">
                <i class="bx bx-file me-1"></i> Ekspor Laporan
            </button>
            <a href="<?= base_url('dashboard/clear-cache') ?>" class="btn btn-outline-danger btn-sm" data-confirm="Apakah Anda yakin ingin menghapus seluruh cache data? Hal ini akan memaksa sistem menarik ulang data terbaru dari API.">
                <i class="bx bx-refresh me-1"></i> Clear Cache
            </a>
            <ul class="nav nav-pills" id="dashboard-tabs">
                <li class="nav-item">
                    <button class="nav-link active" onclick="switchType('penyedia', this)">
                        <i class="bx bx-package me-1"></i> Penyedia
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" onclick="switchType('swakelola', this)">
                        <i class="bx bx-sync me-1"></i> Swakelola
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-2 mb-3 mb-md-0">
                    <label class="form-label text-uppercase fw-bold small text-muted">Tahun Anggaran</label>
                    <select id="filter-year" class="form-select" onchange="loadData()">
                        <?php 
                        $curYear = date('Y');
                        if (isset($availableYears) && !empty($availableYears)) {
                            foreach($availableYears as $yr): 
                        ?>
                        <option value="<?= $yr ?>" <?= $yr == $curYear ? 'selected' : '' ?>><?= $yr ?></option>
                        <?php 
                            endforeach; 
                        } else {
                            for($i = $curYear; $i >= 2023; $i--): 
                        ?>
                        <option value="<?= $i ?>" <?= $i == $curYear ? 'selected' : '' ?>><?= $i ?></option>
                        <?php 
                            endfor;
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <label class="form-label text-uppercase fw-bold small text-muted">Organisasi Perangkat Daerah</label>
                    <select id="filter-opd" class="form-select" onchange="applyFilters()">
                        <option value="all">Semua OPD</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3 mb-md-0">
                    <label class="form-label text-uppercase fw-bold small text-muted">Jenis Pengadaan</label>
                    <select id="filter-jenis" class="form-select" onchange="applyFilters()">
                        <option value="all">Semua Jenis</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <label class="form-label text-uppercase fw-bold small text-muted" id="label-metode">Metode Pengadaan</label>
                    <select id="filter-metode" class="form-select" onchange="applyFilters()">
                        <option value="all">Semua Metode</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button onclick="resetFilters()" class="btn btn-outline-secondary d-block w-100 fw-bold">Reset Filters</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-4 mb-4 mb-md-0">
            <div class="card h-100" id="stat-total-pagu">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-dollar"></i></span>
                        </div>
                    </div>
                    <span class="fw-semibold d-block mb-1">Total Pagu</span>
                    <h3 class="card-title mb-2 stat-value text-primary">Rp 0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4 mb-md-0">
            <div class="card h-100" id="stat-total-paket">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-warning"><i class="bx bx-package"></i></span>
                        </div>
                    </div>
                    <span class="fw-semibold d-block mb-1">Jumlah Paket</span>
                    <h3 class="card-title text-nowrap mb-1 stat-value">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100" id="stat-top-opd">
                <div class="card-body">
                    <span class="fw-semibold d-block mb-3 text-muted text-uppercase small" id="top-opd-title">Top 5 OPD (Penyedia)</span>
                    <div id="top-opd-list" class="small">
                        <!-- Top 5 will be injected here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row">
        <!-- Pagu per Jenis Chart -->
        <div class="col-md-7 mb-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header d-flex align-items-center justify-content-between pb-2">
                    <div class="card-title mb-0">
                        <h5 class="m-0 me-2 text-primary fw-bold">
                            <i class="bx bx-bar-chart-alt-2 me-2"></i>Total Pagu per Jenis Pengadaan
                        </h5>
                        <small class="text-muted">Distribusi anggaran berdasarkan jenis kontrak</small>
                    </div>
                </div>
                <div class="card-body pt-3">
                    <div style="height: 350px;">
                        <canvas id="paguJenisChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Paket per Metode Chart -->
        <div class="col-md-5 mb-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header d-flex align-items-center justify-content-between pb-2">
                    <div class="card-title mb-0">
                        <h5 class="m-0 me-2 text-primary fw-bold" id="metode-paket-title">
                            <i class="bx bx-pie-chart-alt-2 me-2"></i>Jumlah Paket per Metode
                        </h5>
                        <small class="text-muted">Sebaran paket berdasarkan pemilihan</small>
                    </div>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div style="height: 350px; width: 100%;">
                        <canvas id="metodePaketChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if(in_array(session()->get('role'), ['admin', 'operator']) && !empty($charts)): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="d-flex align-items-center mb-3">
            <h4 class="fw-bold mb-0 text-primary">
                <i class="bx bx-bar-chart-alt-2 me-2"></i>Custom Analytics
            </h4>
            <div class="ms-auto">
                <span class="badge bg-label-primary">Dynamic Config</span>
            </div>
        </div>
    </div>
    
    <?php foreach($charts as $idx => $chart): ?>
    <div class="col-md-6 mb-4">
        <div class="card h-100 shadow-none border-0">
            <div class="card-header d-flex justify-content-between align-items-center pb-0">
                <div class="card-title mb-0">
                    <h5 class="m-0 me-2 text-primary fw-bold">
                        <i class="bx bx-stats me-2"></i><?= $chart['title'] ?>
                    </h5>
                    <small class="text-muted">Source: <?= $chart['menu_name'] ?></small>
                </div>
            </div>
            <div class="card-body">
                <div style="height: 300px; width: 100%;" class="position-relative">
                    <div id="loader_<?= $chart['id'] ?>" class="position-absolute w-100 h-100 d-flex align-items-center justify-content-center bg-white" style="z-index: 10;">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    </div>
                    <canvas id="dynamicChart_<?= $chart['id'] ?>"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const userSatker = <?= json_encode(session()->get('kd_satker')) ?>;
    const userRoleRaw = <?= json_encode(session()->get('role')) ?>;
    const userRole = userRoleRaw ? userRoleRaw.toString().toLowerCase().trim() : '';
    
    let charts = { paguJenis: null, metodePaket: null };
    let dashboardSummary = null;
    let currentType = 'penyedia';

    function exportData() {
        const year = document.getElementById('filter-year').value;
        window.location.href = `<?= base_url('dashboard/export') ?>/${currentType}/${year}`;
    }

    function switchType(type, btn) {
        currentType = type;
        
        // Update Title & Labels
        document.getElementById('dashboard-title').textContent = (type === 'penyedia') ? 'RUP Penyedia' : 'RUP Swakelola';
        document.getElementById('top-opd-title').textContent = (type === 'penyedia') ? 'Top 5 OPD (Penyedia)' : 'Top 5 OPD (Swakelola)';
        document.getElementById('label-metode').textContent = (type === 'penyedia') ? 'Metode Pengadaan' : 'Tipe Swakelola';
        document.getElementById('metode-paket-title').textContent = (type === 'penyedia') ? 'Jumlah Paket per Metode' : 'Jumlah Paket per Tipe';
        
        // Update UI Tabs Active State
        document.querySelectorAll('#dashboard-tabs .nav-link').forEach(el => el.classList.remove('active'));
        btn.classList.add('active');

        // Hide/Show Jenis Pengadaan Filter
        const jenisCol = document.getElementById('filter-jenis').parentElement;
        if (type === 'swakelola') {
            jenisCol.style.display = 'none';
            document.getElementById('filter-jenis').value = 'all';
        } else {
            jenisCol.style.display = 'block';
        }

        loadData();
    }

    async function loadData() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Menganalisis Transaksi...',
                html: 'Memuat data RUP ' + (currentType === 'penyedia' ? 'Penyedia' : 'Swakelola') + '.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
        }
        try {
            const selectedYear = document.getElementById('filter-year').value;
            const res = await fetch(`<?= base_url('dashboard/getDashboardSummary') ?>/${currentType}?year=${selectedYear}`);
            dashboardSummary = await res.json();
            
            populateFilters();
            applyFilters();
        } catch (e) {
            console.error("Load failed", e);
        } finally {
            if (typeof Swal !== 'undefined') Swal.close();
        }
    }

    function initDashboard() {
        loadData();
    }

    const swakelolaTipeMapping = {
        'Tipe I': 'Swakelola Tipe I - Direncanakan, dilaksanakan, dan diawasi oleh K/L/PD sendiri',
        'Tipe II': 'Swakelola Tipe II - Direncanakan dan diawasi oleh K/L/PD penanggung jawab, dilaksanakan oleh K/L/PD lain',
        'Tipe III': 'Swakelola Tipe III - Direncanakan dan diawasi oleh K/L/PD penanggung jawab, dilaksanakan oleh Ormas',
        'Tipe IV': 'Swakelola Tipe IV - Direncanakan dan diawasi oleh K/L/PD penanggung jawab/Pokmas, dilaksanakan oleh Pokmas',
        'Tipe 1': 'Swakelola Tipe I - Direncanakan, dilaksanakan, dan diawasi oleh K/L/PD sendiri',
        'Tipe 2': 'Swakelola Tipe II - Direncanakan dan diawasi oleh K/L/PD penanggung jawab, dilaksanakan oleh K/L/PD lain',
        'Tipe 3': 'Swakelola Tipe III - Direncanakan dan diawasi oleh K/L/PD penanggung jawab, dilaksanakan oleh Ormas',
        'Tipe 4': 'Swakelola Tipe IV - Direncanakan dan diawasi oleh K/L/PD penanggung jawab/Pokmas, dilaksanakan oleh Pokmas',
        '1': 'Swakelola Tipe I - Direncanakan, dilaksanakan, dan diawasi oleh K/L/PD sendiri',
        '2': 'Swakelola Tipe II - Direncanakan dan diawasi oleh K/L/PD penanggung jawab, dilaksanakan oleh K/L/PD lain',
        '3': 'Swakelola Tipe III - Direncanakan dan diawasi oleh K/L/PD penanggung jawab, dilaksanakan oleh Ormas',
        '4': 'Swakelola Tipe IV - Direncanakan dan diawasi oleh K/L/PD penanggung jawab/Pokmas, dilaksanakan oleh Pokmas'
    };

    function populateFilters() {
        if (!dashboardSummary) return;

        const opdSelect = document.getElementById('filter-opd');
        const jenisSelect = document.getElementById('filter-jenis');
        const metodeSelect = document.getElementById('filter-metode');

        const opds = (dashboardSummary.opds || []).sort();
        const jenis = (dashboardSummary.jenis || []).sort();
        const metodes = (dashboardSummary.metodes || []).sort();

        // Save previous values
        const prevOpd = opdSelect.value;
        const prevJenis = jenisSelect.value;
        const prevMetode = metodeSelect.value;

        opdSelect.innerHTML = '<option value="all">Semua OPD</option>';
        opds.forEach(o => { opdSelect.appendChild(new Option(o, o)); });

        jenisSelect.innerHTML = '<option value="all">Semua Jenis</option>';
        jenis.forEach(j => { jenisSelect.appendChild(new Option(j, j)); });

        metodeSelect.innerHTML = '<option value="all">Semua ' + (currentType === 'penyedia' ? 'Metode' : 'Tipe') + '</option>';
        metodes.forEach(m => { 
            const label = (currentType === 'swakelola' && swakelolaTipeMapping[m]) ? swakelolaTipeMapping[m] : m;
            metodeSelect.appendChild(new Option(label, m)); 
        });

        // Restore previous values if they exist in the new options
        if (Array.from(opdSelect.options).some(opt => opt.value === prevOpd)) {
            opdSelect.value = prevOpd;
        }
        if (Array.from(jenisSelect.options).some(opt => opt.value === prevJenis)) {
            jenisSelect.value = prevJenis;
        }
        if (Array.from(metodeSelect.options).some(opt => opt.value === prevMetode)) {
            metodeSelect.value = prevMetode;
        }

        if ((userRole === 'satker' || userRole === 'opd') && userSatker) {
            // Karena data sudah difilter di backend, array opds hanya akan berisi OPD milik satker tersebut.
            const userOpd = opds.length > 0 ? opds[0] : 'all';
            opdSelect.value = userOpd;
            opdSelect.disabled = true;
        }
    }

    function applyFilters() {
        if (!dashboardSummary || !dashboardSummary.matrix) return;
        
        const opdVal = document.getElementById('filter-opd').value;
        const jenisVal = document.getElementById('filter-jenis').value;
        const metodeVal = document.getElementById('filter-metode').value;

        let totalPagu = 0;
        let totalPaket = 0;
        let opdMap = {};
        let jenisMap = {};
        let metodeMap = {};

        for (const [opd, jenisObj] of Object.entries(dashboardSummary.matrix)) {
            if (opdVal !== 'all' && opd !== opdVal) continue;

            for (const [jenis, metodeObj] of Object.entries(jenisObj)) {
                if (jenisVal !== 'all' && jenis !== jenisVal) continue;

                for (const [metode, stats] of Object.entries(metodeObj)) {
                    if (metodeVal !== 'all' && metode !== metodeVal) continue;

                    totalPagu += stats.pagu;
                    totalPaket += stats.paket;

                    opdMap[opd] = (opdMap[opd] || 0) + stats.pagu;
                    jenisMap[jenis] = (jenisMap[jenis] || 0) + stats.pagu;
                    metodeMap[metode] = (metodeMap[metode] || 0) + stats.paket;
                }
            }
        }

        const top5Opd = Object.entries(opdMap).sort((a, b) => b[1] - a[1]).slice(0, 5);
        const top5Obj = Object.fromEntries(top5Opd);

        updateUI(totalPagu, totalPaket, top5Obj);
        renderCharts(jenisMap, metodeMap);
    }

    function updateUI(pagu, paket, top5) {
        document.getElementById('stat-total-pagu').querySelector('.stat-value').textContent = formatIDR(pagu);
        document.getElementById('stat-total-paket').querySelector('.stat-value').textContent = paket.toLocaleString('id-ID');

        const listContainer = document.getElementById('top-opd-list');
        listContainer.innerHTML = Object.entries(top5).map(([name, val], idx) => `
            <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                <div class="text-truncate mr-2" style="max-width: 65%;">
                    <span class="font-weight-bold text-primary mr-1">${idx + 1}.</span> 
                    <span title="${name}">${name}</span>
                </div>
                <div class="font-weight-bold ml-auto">${formatIDRShort(val)}</div>
            </div>
        `).join('') || '<div class="text-muted text-center py-3">Tidak ada data</div>';
    }

    function renderCharts(jenisData, metodeData) {
        const palette = ['#696cff', '#8592a3', '#03c3ec', '#71dd37', '#ffab00', '#ff3e1d', '#00efd1'];
        
        if (charts.paguJenis) charts.paguJenis.destroy();
        charts.paguJenis = new Chart(document.getElementById('paguJenisChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(jenisData),
                datasets: [{
                    label: 'Total Pagu',
                    data: Object.values(jenisData),
                    backgroundColor: 'rgba(105, 108, 255, 0.8)',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { 
                        beginAtZero: true,
                        ticks: { callback: v => formatIDRShort(v) }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => 'Pagu: ' + formatIDR(ctx.raw)
                        }
                    }
                }
            }
        });

        if (charts.metodePaket) charts.metodePaket.destroy();
        charts.metodePaket = new Chart(document.getElementById('metodePaketChart'), {
            type: 'pie',
            data: {
                labels: Object.keys(metodeData),
                datasets: [{ data: Object.values(metodeData), backgroundColor: palette }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    function formatIDR(v) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v);
    }

    function formatIDRShort(v) {
        if (v >= 1e12) return 'Rp ' + (v / 1e12).toFixed(1) + ' T';
        if (v >= 1e9) return 'Rp ' + (v / 1e9).toFixed(1) + ' M';
        if (v >= 1e6) return 'Rp ' + (v / 1e6).toFixed(1) + ' Jt';
        return formatIDR(v);
    }

    function resetFilters() {
        if (userRole !== 'satker' && userRole !== 'opd') document.getElementById('filter-opd').value = 'all';
        document.getElementById('filter-jenis').value = 'all';
        document.getElementById('filter-metode').value = 'all';
        applyFilters();
    }

    function renderDynamicCharts() {
        const dynamicChartsData = <?= json_encode($charts ?? []) ?>;
        const palette = ['#696cff', '#8592a3', '#03c3ec', '#71dd37', '#ffab00', '#ff3e1d', '#00efd1'];

        dynamicChartsData.forEach(chartConfig => {
            const ctx = document.getElementById(`dynamicChart_${chartConfig.id}`);
            if (!ctx) return;

            fetch(`<?= base_url('dashboard/getChartData/') ?>${chartConfig.id}`)
                .then(res => res.json())
                .then(data => {
                    const loader = document.getElementById(`loader_${chartConfig.id}`);
                    if (loader) loader.remove();
                    
                    new Chart(ctx, {
                        type: chartConfig.chart_type === 'pie' ? 'pie' : 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: chartConfig.title,
                                data: data.values,
                                backgroundColor: chartConfig.chart_type === 'pie' ? palette : 'rgba(105, 108, 255, 0.8)',
                                borderRadius: chartConfig.chart_type === 'pie' ? 0 : 5
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { 
                                    display: chartConfig.chart_type === 'pie',
                                    position: 'bottom'
                                }
                            },
                            scales: chartConfig.chart_type === 'pie' ? {} : {
                                y: { 
                                    beginAtZero: true,
                                    ticks: { 
                                        callback: function(value) {
                                            return chartConfig.aggregation === 'sum' ? formatIDRShort(value) : value;
                                        } 
                                    }
                                }
                            }
                        }
                    });
                })
                .catch(err => console.error(`Error loading chart ${chartConfig.id}:`, err));
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initDashboard();
        renderDynamicCharts();
    });
</script>
<?= $this->endSection() ?>
