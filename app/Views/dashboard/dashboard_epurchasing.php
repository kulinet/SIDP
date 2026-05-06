<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<style>
    :root {
        --glass-bg: rgba(255, 255, 255, 0.7);
        --glass-border: rgba(255, 255, 255, 0.3);
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --accent-gradient: linear-gradient(135deg, #00c6fb 0%, #005bea 100%);
    }

    .glass-card {
        background: var(--glass-bg);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border: 1px solid var(--glass-border);
        border-radius: 20px;
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
        transition: all 0.3s ease;
    }

    .glass-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.15);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        margin-bottom: 15px;
    }

    .bg-gradient-primary { background: var(--primary-gradient); color: white; }
    .bg-gradient-accent { background: var(--accent-gradient); color: white; }
    .bg-gradient-info { background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%); color: white; }

    .chart-container {
        position: relative;
        height: 350px;
        width: 100%;
    }

    .top-satker-item {
        padding: 15px;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .top-satker-item:last-child { border-bottom: none; }

    .rank-badge {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.8rem;
        margin-right: 15px;
    }

    .rank-1 { background: #FFD700; color: #000; }
    .rank-2 { background: #C0C0C0; color: #000; }
    .rank-3 { background: #CD7F32; color: #fff; }
    .rank-other { background: #f0f2f4; color: #566a7f; }

    .shimmer {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
    }

    @keyframes shimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    .table-custom-header {
        background: #f1f3f9 !important;
        color: #566a7f !important;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        font-weight: 700;
    }
    
    .table-modern td {
        vertical-align: middle;
        padding: 1rem 0.75rem !important;
    }

    .pkg-title {
        font-weight: 600;
        color: #32325d;
        font-size: 0.9rem;
        line-height: 1.4;
    }

    .pkg-subtitle {
        font-size: 0.8rem;
        color: #6b7280;
    }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold py-3 mb-0">
                <span class="text-muted fw-light">Analytics /</span> Dashboard E-Purchasing
            </h4>
            <p class="text-muted small mb-0">Ringkasan transaksi pengadaan melalui katalog elektronik</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('dashboard/table-epurchasing') ?>" class="btn btn-primary">
                <i class="bx bx-table me-1"></i> Lihat Detail Tabel
            </a>
            <button onclick="loadDashboardData()" class="btn btn-outline-secondary">
                <i class="bx bx-refresh me-1"></i> Refresh
            </button>
            <?php if(session()->get('role')): ?>
            <a href="<?= base_url('dashboard/clear-cache') ?>" class="btn btn-outline-danger" data-confirm="Apakah Anda yakin ingin menghapus seluruh cache data? Hal ini akan memaksa sistem menarik ulang data terbaru dari API.">
                <i class="bx bx-trash me-1"></i> Clear Cache
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="glass-card p-4">
                <div class="stat-icon bg-gradient-primary">
                    <i class="bx bx-money fs-4"></i>
                </div>
                <h6 class="text-muted small text-uppercase fw-bold mb-1">Total Realisasi</h6>
                <h3 class="fw-bold mb-0" id="stat-total-realisasi">-</h3>
                <p class="text-success small mb-0 mt-2">
                    <i class="bx bx-trending-up me-1"></i> Tahun Berjalan (<span class="active-year-label">2026</span>)
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card p-4">
                <div class="stat-icon bg-gradient-accent">
                    <i class="bx bx-package fs-4"></i>
                </div>
                <h6 class="text-muted small text-uppercase fw-bold mb-1">Total Paket</h6>
                <h3 class="fw-bold mb-0" id="stat-total-packages">-</h3>
                <p class="text-info small mb-0 mt-2">
                    <i class="bx bx-info-circle me-1"></i> Tahun Berjalan (<span class="active-year-label">2026</span>)
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card p-4">
                <div class="stat-icon bg-gradient-info">
                    <i class="bx bx-calendar fs-4"></i>
                </div>
                <h6 class="text-muted small text-uppercase fw-bold mb-1">Histori Data</h6>
                <h3 class="fw-bold mb-0" id="stat-total-years">-</h3>
                <p class="text-muted small mb-0 mt-2">
                    <i class="bx bx-history me-1"></i> Tahun Terekam
                </p>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Monthly Trend Chart -->
        <div class="col-lg-12 mb-4">
            <div class="glass-card p-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h5 class="fw-bold mb-0">Tren Performa Bulanan (<span class="active-year-label">2026</span>)</h5>
                        <p class="text-muted small mb-0">Distribusi paket dan realisasi per bulan</p>
                    </div>
                </div>
                <div class="chart-container" style="height: 300px;">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- History Chart -->
        <div class="col-lg-8 mb-4">
            <div class="glass-card p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h5 class="fw-bold mb-0">Histori Realisasi Tahunan</h5>
                    <div class="dropdown">
                        <button class="btn p-0" type="button" data-bs-toggle="dropdown">
                            <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="toggleChartType('bar')">Bar Chart</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="toggleChartType('line')">Line Chart</a></li>
                        </ul>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="historyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Satker List -->
        <div class="col-lg-4 mb-4">
            <div class="glass-card p-4 h-100">
                <h5 class="fw-bold mb-4">Top 10 Satker (Realisasi)</h5>
                <div id="top-satker-list">
                    <!-- Loaded via JS -->
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted small">Memuat peringkat...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if(in_array($user['role'] ?? '', ['admin', 'operator'])): ?>
    <div class="row mb-4">
        <!-- Leadership Report Section -->
        <div class="col-lg-12">
            <div class="glass-card p-4" style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);">
                <div class="d-flex align-items-center mb-3">
                    <div class="stat-icon bg-gradient-primary me-3 mb-0" style="width: 40px; height: 40px;">
                        <i class="bx bx-file-find fs-5"></i>
                    </div>
                    <h5 class="fw-bold mb-0">Laporan Analisis Pimpinan</h5>
                </div>
                <div class="p-3 bg-white rounded-3 border border-dashed">
                    <p class="mb-0 text-dark" id="leadership-narrative" style="line-height: 1.8; font-size: 1rem; font-style: italic;">
                        Menganalisis data...
                    </p>
                </div>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="small text-muted">Pertumbuhan (vs Tahun Lalu)</div>
                        <div class="fw-bold h5 text-primary" id="report-growth">0%</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Performa Tertinggi</div>
                        <div class="fw-bold h5 text-dark" id="report-top-performer">-</div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <button class="btn btn-sm btn-label-primary">
                            <i class="bx bx-printer me-1"></i> Cetak Laporan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Top Vendor Section -->
        <div class="col-lg-6 mb-4">
            <div class="glass-card p-4">
                <h5 class="fw-bold mb-4">Rekanan Terbanyak (Paket)</h5>
                <div id="top-vendor-list">
                    <!-- Loaded via JS -->
                </div>
            </div>
        </div>
        <!-- Vendor Chart -->
        <div class="col-lg-6 mb-4">
            <div class="glass-card p-4">
                <h5 class="fw-bold mb-4">Distribusi Paket per Penyedia</h5>
                <div class="chart-container" style="height: 300px;">
                    <canvas id="vendorChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Detail Paket Vendor -->
<div class="modal fade" id="vendorPackagesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-label-primary">
                <h5 class="modal-title fw-bold" id="vendorModalTitle">Detail Paket Pekerjaan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-modern mb-0">
                        <thead>
                            <tr>
                                <th class="table-custom-header text-center" style="width: 50px;">#</th>
                                <th class="table-custom-header">Informasi Paket</th>
                                <th class="table-custom-header">Program & Produk</th>
                                <th class="table-custom-header">Satker Pelaksana</th>
                                <th class="table-custom-header text-center">Tgl Order</th>
                                <th class="table-custom-header text-end">Realisasi</th>
                            </tr>
                        </thead>
                        <tbody id="vendorPackagesTableBody">
                            <!-- Loaded via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    let historyChart;
    let vendorChart;
    let chartType = 'bar';
    let rawHistoryData = null;
    let globalTopVendorData = {};

    function formatIDR(v) {
        if (v >= 1e12) return 'Rp ' + (v / 1e12).toFixed(2) + ' T';
        if (v >= 1e9) return 'Rp ' + (v / 1e9).toFixed(2) + ' M';
        if (v >= 1e6) return 'Rp ' + (v / 1e6).toFixed(2) + ' Jt';
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(v);
    }

    function formatIDRFull(v) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(v);
    }

    function escapeHTML(str) {
        if (!str || typeof str !== 'string') return str;
        return str.replace(/&/g, "&amp;")
                  .replace(/</g, "&lt;")
                  .replace(/>/g, "&gt;")
                  .replace(/"/g, "&quot;")
                  .replace(/'/g, "&#039;");
    }

    async function loadDashboardData() {
        console.log('Fetching dashboard data...');
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Menganalisis Transaksi...',
                html: 'Memuat data E-Purchasing dan menghitung metrik.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }

        try {
            const res = await fetch('<?= base_url('dashboard/getEpurchasingDashboardData') ?>');
            const data = await res.json();
            console.log('Data received:', data);
            
            if (!data || !data.summary) {
                console.warn('Dashboard data is incomplete:', data);
                return;
            }

            // Update Stats
            document.getElementById('stat-total-realisasi').innerText = formatIDRFull(data.summary.total_realisasi || 0);
            document.getElementById('stat-total-packages').innerText = (data.summary.total_packages || 0).toLocaleString('id-ID') + ' Paket';
            document.getElementById('stat-total-years').innerText = (data.summary.years_count || 0) + ' Tahun';

            // Update year labels
            const yearLabels = document.querySelectorAll('.active-year-label');
            yearLabels.forEach(el => el.innerText = data.summary.year_active || '2026');

            // Build History Chart
            if (data.history && Object.keys(data.history).length > 0) {
                rawHistoryData = data.history;
                renderHistoryChart(data.history);
            }

            // Build Monthly Chart
            if (data.monthly) {
                renderMonthlyChart(data.monthly);
            }

            // Build Top Satker List
            if (data.top_satker) {
                renderTopSatker(data.top_satker);
            }

            // Analysis for Admin/Operator
            const narrativeEl = document.getElementById('leadership-narrative');
            if (narrativeEl && data.report) {
                narrativeEl.innerText = data.report.narrative || 'Analisis tidak tersedia.';
                document.getElementById('report-growth').innerText = (data.report.growth > 0 ? '+' : '') + data.report.growth + '%';
                document.getElementById('report-top-performer').innerText = data.report.top_performer || '-';
                
                if (data.top_vendor) {
                    globalTopVendorData = data.top_vendor;
                    renderTopVendor(data.top_vendor);
                    renderVendorChart(data.top_vendor);
                }
            }

        } catch (error) {
            console.error('Error loading dashboard data:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire('Terjadi Kesalahan', 'Gagal memuat data dashboard. Silakan coba segarkan halaman.', 'error');
            } else {
                alert('Gagal memuat data dashboard. Silakan coba segarkan halaman.');
            }
        } finally {
            if (typeof Swal !== 'undefined') {
                Swal.close();
            }
        }
    }

    let monthlyChart;
    function renderMonthlyChart(monthly) {
        const monthNames = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Ags", "Sep", "Okt", "Nov", "Des"];
        const labels = Object.keys(monthly).map(m => monthNames[m-1]);
        const realisasiData = Object.keys(monthly).map(m => monthly[m].realisasi);
        const countData = Object.keys(monthly).map(m => monthly[m].count);

        if (monthlyChart) monthlyChart.destroy();

        const ctx = document.getElementById('monthlyChart').getContext('2d');
        monthlyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Realisasi (Rp)',
                        data: realisasiData,
                        borderColor: '#696cff',
                        backgroundColor: 'rgba(105, 108, 255, 0.1)',
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Paket',
                        data: countData,
                        borderColor: '#03c3ec',
                        backgroundColor: '#03c3ec',
                        type: 'bar',
                        borderRadius: 5,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label.includes('Rp')) return label + ': ' + formatIDR(context.parsed.y);
                                return label + ': ' + context.parsed.y + ' Paket';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        ticks: { callback: v => formatIDR(v) }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }

    function renderHistoryChart(history) {
        const labels = Object.keys(history);
        const realisasiData = labels.map(y => history[y].realisasi);
        const countData = labels.map(y => history[y].count);

        if (historyChart) historyChart.destroy();

        const ctx = document.getElementById('historyChart').getContext('2d');
        historyChart = new Chart(ctx, {
            type: chartType,
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Nilai Realisasi (Rp)',
                        data: realisasiData,
                        backgroundColor: 'rgba(102, 126, 234, 0.6)',
                        borderColor: 'rgba(102, 126, 234, 1)',
                        borderWidth: 2,
                        borderRadius: 8,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Jumlah Paket',
                        data: countData,
                        type: 'line',
                        backgroundColor: 'rgba(0, 198, 251, 0.2)',
                        borderColor: 'rgba(0, 198, 251, 1)',
                        borderWidth: 3,
                        pointRadius: 5,
                        yAxisID: 'y1',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label.includes('Nilai')) {
                                    return label + ': ' + formatIDR(context.parsed.y);
                                }
                                return label + ': ' + context.parsed.y + ' Paket';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        ticks: { callback: v => formatIDR(v) }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }

    function toggleChartType(type) {
        chartType = type;
        if (rawHistoryData) renderHistoryChart(rawHistoryData);
    }

    function renderTopSatker(topSatker) {
        const container = document.getElementById('top-satker-list');
        container.innerHTML = '';

        let rank = 1;
        for (const kd in topSatker) {
            const s = topSatker[kd];
            const rankCls = rank === 1 ? 'rank-1' : (rank === 2 ? 'rank-2' : (rank === 3 ? 'rank-3' : 'rank-other'));
            
            const html = `
                <div class="top-satker-item">
                    <div class="d-flex align-items-center">
                        <div class="rank-badge ${rankCls}">${rank}</div>
                        <div style="max-width: 180px;">
                            <div class="fw-bold small text-truncate" title="${escapeHTML(s.nama)}">${escapeHTML(s.nama)}</div>
                            <div class="text-muted extra-small">${s.count} Paket</div>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold small text-primary">${formatIDR(s.realisasi)}</div>
                    </div>
                </div>
            `;
            container.innerHTML += html;
            rank++;
        }
    }

    function renderTopVendor(topVendor) {
        const container = document.getElementById('top-vendor-list');
        if (!container) return;
        container.innerHTML = '';

        let rank = 1;
        for (const name in topVendor) {
            const v = topVendor[name];
            const html = `
                <div class="top-satker-item">
                    <div class="d-flex align-items-center">
                        <div class="rank-badge rank-other">${rank}</div>
                        <div style="max-width: 250px;">
                            <div class="fw-bold small text-truncate" title="${escapeHTML(name)}">${escapeHTML(name)}</div>
                            <div class="text-muted extra-small">${formatIDR(v.realisasi)}</div>
                        </div>
                    </div>
                    <div class="text-end">
                        <button class="btn btn-sm badge bg-label-primary rounded-pill border-0 vendor-pkg-btn" 
                                data-vendor-name="${escapeHTML(name)}"
                                style="cursor: pointer; box-shadow: 0 2px 5px rgba(105, 108, 255, 0.3);">
                            <i class="bx bx-list-ul me-1"></i>${v.count} Paket
                        </button>
                    </div>
                </div>
            `;
            container.innerHTML += html;
            rank++;
        }

        // Attach event listeners securely to avoid inline onclick XSS issues
        document.querySelectorAll('.vendor-pkg-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Decode HTML entities back for the JS object lookup
                const rawName = this.getAttribute('data-vendor-name')
                                    .replace(/&amp;/g, '&').replace(/&lt;/g, '<')
                                    .replace(/&gt;/g, '>').replace(/&quot;/g, '"')
                                    .replace(/&#039;/g, "'");
                showVendorPackages(rawName);
            });
        });
    }

    function showVendorPackages(vendorName) {
        const vendorData = globalTopVendorData[vendorName];
        if (!vendorData || !vendorData.packages) return;

        document.getElementById('vendorModalTitle').innerText = 'Detail Paket: ' + vendorName;
        const tbody = document.getElementById('vendorPackagesTableBody');
        tbody.innerHTML = '';

        // Sort packages by order date (newest first) or realization (descending)
        const sortedPackages = vendorData.packages.sort((a, b) => b.realisasi - a.realisasi);

        sortedPackages.forEach((pkg, idx) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="text-center text-muted small">${idx + 1}</td>
                <td>
                    <div class="pkg-title">${escapeHTML(pkg.nama_paket) || '-'}</div>
                </td>
                <td>
                    <div class="small fw-semibold text-dark mb-1">${escapeHTML(pkg.nama_program) || '-'}</div>
                    <div class="pkg-subtitle"><i class="bx bx-purchase-tag-alt me-1"></i>${escapeHTML(pkg.nama_produk) || '-'}</div>
                </td>
                <td>
                    <div class="small text-muted"><i class="bx bx-building me-1"></i>${escapeHTML(pkg.satker) || '-'}</div>
                </td>
                <td class="text-center">
                    <span class="badge bg-label-info rounded-pill px-3">${escapeHTML(pkg.tgl_order) || '-'}</span>
                </td>
                <td class="text-end">
                    <div class="fw-bold text-primary h6 mb-0">${formatIDR(pkg.realisasi || 0)}</div>
                </td>
            `;
            tbody.appendChild(tr);
        });

        // Use jQuery to show the modal to avoid any native Bootstrap initialization issues
        $('#vendorPackagesModal').modal('show');
    }

    function renderVendorChart(topVendor) {
        const ctx = document.getElementById('vendorChart');
        if (!ctx) return;
        
        if (vendorChart) vendorChart.destroy();

        const labels = Object.keys(topVendor);
        const counts = labels.map(n => topVendor[n].count);

        vendorChart = new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels.map(l => l.length > 20 ? l.substring(0, 20) + '...' : l),
                datasets: [{
                    data: counts,
                    backgroundColor: [
                        '#696cff', '#03c3ec', '#71dd37', '#ff3e1d', '#ffab00',
                        '#233446', '#8592a3', '#667eea', '#764ba2', '#00c6fb'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', display: false }
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', loadDashboardData);
</script>

<?= $this->endSection() ?>
