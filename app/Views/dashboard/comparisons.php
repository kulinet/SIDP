<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="container-xxl flex-grow-1 container-p-y">


    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="fw-bold py-3 mb-0">
            <span class="text-muted fw-light">Dashboard /</span> Perbandingan Tahunan
        </h4>
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
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header pb-2">
                    <h5 class="m-0 me-2 text-primary fw-bold"><i class="bx bx-line-chart me-2"></i>Perbandingan Total Pagu (Rp)</h5>
                </div>
                <div class="card-body pt-3">
                    <div style="height: 350px;"><canvas id="paguYearlyChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header pb-2">
                    <h5 class="m-0 me-2 text-primary fw-bold"><i class="bx bx-bar-chart me-2"></i>Perbandingan Jumlah Paket</h5>
                </div>
                <div class="card-body pt-3">
                    <div style="height: 350px;"><canvas id="paketYearlyChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row" id="extra-charts-container">
        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header pb-2">
                    <h5 class="m-0 me-2 text-primary fw-bold" id="dist-chart-1-title"><i class="bx bx-pie-chart-alt me-2"></i>Distribusi Metode Pengadaan</h5>
                </div>
                <div class="card-body pt-3">
                    <div style="height: 350px;"><canvas id="distChart1"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header pb-2">
                    <h5 class="m-0 me-2 text-primary fw-bold" id="dist-chart-2-title"><i class="bx bx-pie-chart-alt-2 me-2"></i>Distribusi Jenis Pengadaan</h5>
                </div>
                <div class="card-body pt-3">
                    <div style="height: 350px;"><canvas id="distChart2"></canvas></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header"><h5 class="fw-bold mb-0">Rincian Data Tahunan <span id="rincian-title" class="text-primary">Penyedia</span></h5></div>
                <div class="table-responsive text-nowrap">
                    <table class="table table-hover">
                        <thead><tr><th>Tahun Anggaran</th><th>Total Pagu</th><th>Jumlah Paket</th><th class="text-center">Aksi</th></tr></thead>
                        <tbody id="rincian-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const statsPenyedia = <?= json_encode($statsPenyedia ?? []) ?>;
    const statsSwakelola = <?= json_encode($statsSwakelola ?? []) ?>;
    let currentType = 'penyedia';
    let currentStats = statsPenyedia;
    let charts = { pagu: null, paket: null, dist1: null, dist2: null };

    function switchType(type, btn) {
        currentType = type;
        currentStats = (type === 'penyedia') ? statsPenyedia : statsSwakelola;
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Menganalisis Data...',
                html: 'Memproses komparasi RUP ' + (type === 'penyedia' ? 'Penyedia' : 'Swakelola') + '.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
        }
        
        setTimeout(() => {
            if (btn) {
                document.querySelectorAll('#dashboard-tabs .nav-link').forEach(el => el.classList.remove('active'));
                btn.classList.add('active');
            }
            document.getElementById('rincian-title').textContent = type === 'penyedia' ? 'Penyedia' : 'Swakelola';
            
            document.getElementById('dist-chart-1-title').innerHTML = type === 'penyedia' ? 
                '<i class="bx bx-pie-chart-alt me-2"></i>Distribusi Metode Pengadaan' : 
                '<i class="bx bx-pie-chart-alt me-2"></i>Distribusi Tipe Swakelola';
            
            document.getElementById('dist-chart-2-title').innerHTML = '<i class="bx bx-pie-chart-alt-2 me-2"></i>Distribusi Jenis Pengadaan';

            document.getElementById('extra-charts-container').style.display = type === 'penyedia' ? 'flex' : 'none';

            renderComparisons();
            if (typeof Swal !== 'undefined') Swal.close();
        }, 150);
    }

    function formatIDR(v) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v); }
    function formatIDRShort(v) {
        if (v >= 1e12) return 'Rp ' + (v / 1e12).toFixed(1) + ' T';
        if (v >= 1e9) return 'Rp ' + (v / 1e9).toFixed(1) + ' M';
        if (v >= 1e6) return 'Rp ' + (v / 1e6).toFixed(1) + ' Jt';
        return formatIDR(v);
    }

    function renderComparisons() {
        const yearly = currentStats.yearly || {};
        const years = Object.keys(yearly).sort();
        const pagu = years.map(y => yearly[y].pagu);
        const paket = years.map(y => yearly[y].paket);

        const palettePaket = 'rgba(3, 195, 236, 0.8)';

        if (charts.pagu) charts.pagu.destroy();
        charts.pagu = new Chart(document.getElementById('paguYearlyChart'), {
            type: 'line',
            data: {
                labels: years,
                datasets: [{
                    label: 'Total Pagu',
                    data: pagu,
                    borderColor: '#696cff',
                    backgroundColor: 'rgba(105, 108, 255, 0.2)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, ticks: { callback: v => formatIDRShort(v) } } },
                plugins: { tooltip: { callbacks: { label: ctx => 'Pagu: ' + formatIDR(ctx.raw) } } }
            }
        });

        if (charts.paket) charts.paket.destroy();
        charts.paket = new Chart(document.getElementById('paketYearlyChart'), {
            type: 'bar',
            data: {
                labels: years,
                datasets: [{
                    label: 'Jumlah Paket',
                    data: paket,
                    backgroundColor: palettePaket,
                    borderRadius: 5
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
        });

        if (currentType === 'penyedia') {
            const colors = ['#696cff', '#03c3ec', '#71dd37', '#ff3e1d', '#ffab00', '#8592a3', '#007bff', '#28a745', '#e83e8c', '#fd7e14'];
            
            const processDist = (distData) => {
                const cats = Object.keys(distData).sort();
                return cats.map((cat, idx) => ({
                    label: cat,
                    data: years.map(yr => distData[cat][yr] || 0),
                    backgroundColor: colors[idx % colors.length],
                    borderRadius: 5
                }));
            };

            if (charts.dist1) charts.dist1.destroy();
            charts.dist1 = new Chart(document.getElementById('distChart1'), {
                type: 'bar',
                data: { labels: years, datasets: processDist(currentStats.dist1 || {}) },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true } },
                    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 15 } } }
                }
            });

            if (charts.dist2) charts.dist2.destroy();
            charts.dist2 = new Chart(document.getElementById('distChart2'), {
                type: 'bar',
                data: { labels: years, datasets: processDist(currentStats.dist2 || {}) },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true } },
                    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 15 } } }
                }
            });
        }

        const tbody = document.getElementById('rincian-tbody');
        tbody.innerHTML = years.map((y, idx) => `
            <tr>
                <td><strong>${y}</strong> ${idx === years.length - 1 ? '<span class="badge bg-label-success ms-2">Terbaru</span>' : ''}</td>
                <td class="text-primary fw-bold">${formatIDR(yearly[y].pagu)}</td>
                <td>${yearly[y].paket.toLocaleString('id-ID')}</td>
                <td class="text-center">
                    <a href="<?= base_url('dashboard/export') ?>/${currentType}/${y}" class="btn btn-sm btn-label-primary">
                        <i class="bx bx-download me-1"></i> Unduh
                    </a>
                </td>
            </tr>
        `).reverse().join('') || '<tr><td colspan="3" class="text-center text-muted py-4">Tidak ada data ditarik dari API</td></tr>';
    }

    document.addEventListener('DOMContentLoaded', () => {
        switchType('penyedia', document.querySelector('.nav-link.active'));
    });
</script>
<?= $this->endSection() ?>
