<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?>
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

    .table-modern thead th {
        background-color: #f8f9fa !important;
        text-transform: uppercase;
        font-size: 0.7rem;
        letter-spacing: 0.05em;
        font-weight: 700;
        color: #4b5563;
        border-top: none;
        padding: 12px 15px !important;
    }
    
    .table-modern tbody tr {
        transition: all 0.2s ease;
    }
    
    .table-modern tbody tr:hover {
        background-color: rgba(105, 108, 255, 0.05) !important;
        transform: scale(1.002);
    }

    .pkg-title {
        font-weight: 700;
        color: #2d3748;
        font-size: 0.85rem;
        line-height: 1.5;
        margin-bottom: 2px;
    }

    .pkg-subtitle {
        font-size: 0.75rem;
        color: #718096;
        display: flex;
        align-items: center;
    }

    .price-tag {
        font-family: 'Public Sans', sans-serif;
        font-weight: 800;
        color: #696cff;
        font-size: 0.95rem;
    }

    .modal-premium-header {
        background: linear-gradient(135deg, #696cff 0%, #3f4191 100%);
        color: white;
        border-bottom: none;
    }

    .modal-premium-header .btn-close {
        filter: brightness(0) invert(1);
    }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
        <div>
            <h4 class="fw-bold py-3 mb-0">
                <span class="text-muted fw-light">Analytics /</span> Dashboard E-Purchasing
            </h4>
            <p class="text-muted small mb-0">Ringkasan transaksi pengadaan melalui katalog elektronik</p>
        </div>
        <div class="d-flex flex-wrap gap-2 w-100 w-md-auto">
            <a href="<?= base_url('dashboard/table-epurchasing') ?>" class="btn btn-primary flex-fill">
                <i class="bx bx-table me-1"></i> Lihat Detail Tabel
            </a>
            <button onclick="loadDashboardData()" class="btn btn-outline-secondary flex-fill">
                <i class="bx bx-refresh me-1"></i> Refresh
            </button>
            <?php if(session()->get('role')): ?>
            <a href="<?= base_url('dashboard/clear-cache') ?>" class="btn btn-outline-danger flex-fill" data-confirm="Apakah Anda yakin ingin menghapus seluruh cache data? Hal ini akan memaksa sistem menarik ulang data terbaru dari API.">
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
                    <i class="bx bx-info-circle me-1"></i> Tahun Berjalan (<span class="active-year-label">2026</span>) &bull; <span id="stat-total-items">0 Item Produk</span>
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card p-4">
                <div class="stat-icon bg-gradient-info">
                    <i class="bx bx-info-circle fs-4"></i>
                </div>
                <h6 class="text-muted small text-uppercase fw-bold mb-1">Paket Status Lainnya</h6>
                <h3 class="fw-bold mb-0" id="stat-other-status-packages">-</h3>
                <p class="text-muted small mb-0 mt-2">
                    <i class="bx bx-time me-1"></i> Negosiasi &amp; Menunggu Konfirmasi
                </p>
            </div>
        </div>
    </div>

    <!-- Analitik Komoditas Produk, Metode Minikom & Asal Dana -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="glass-card p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-3 gap-2">
                    <div>
                        <h5 class="fw-bold mb-1">
                            <i class="bx bx-purchase-tag-alt text-primary me-1"></i> Ringkasan Komoditas Produk, Metode Minikom &amp; Asal Dana (<span class="active-year-label">2026</span>)
                        </h5>
                        <p class="text-muted small mb-0">Informasi komoditas produk e-katalog (<code>products_json</code>), metode pengadaan (<code>flag_minikom</code>), dan asal dana (<code>funding_source</code>)</p>
                    </div>
                    <ul class="nav nav-pills nav-sm gap-2" id="productIntelTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold btn-sm" id="tab-top-products-btn" data-bs-toggle="pill" data-bs-target="#tab-top-products" type="button" role="tab">
                                <i class="bx bx-purchase-tag-alt me-1"></i> Top 10 Komoditas / Item Produk
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold btn-sm" id="tab-minikom-funding-btn" data-bs-toggle="pill" data-bs-target="#tab-minikom-funding" type="button" role="tab">
                                <i class="bx bx-pie-chart-alt-2 me-1"></i> Metode Minikom &amp; Asal Dana
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="tab-content pt-2">
                    <!-- Tab 1: Top 10 Komoditas / Item Produk -->
                    <div class="tab-pane fade show active" id="tab-top-products" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover table-modern align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 45px;">#</th>
                                        <th>Nama Produk / Komoditas</th>
                                        <th class="text-center">Frekuensi</th>
                                        <th class="text-center">Total Kuantitas</th>
                                        <th class="text-end">Rata-rata Harga Awal (Origin)</th>
                                        <th class="text-end">Rata-rata Harga Akhir (Final)</th>
                                        <th class="text-end">Total Nilai Produk</th>
                                    </tr>
                                </thead>
                                <tbody id="top-products-table-body">
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">Memuat data komoditas produk...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 2: Rincian Flag Minikom & Asal Dana (Funding Source) -->
                    <div class="tab-pane fade" id="tab-minikom-funding" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-2"><i class="bx bx-transfer-alt text-primary me-1"></i> Distribusi Metode (Flag Minikom)</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover table-modern mb-0">
                                        <thead>
                                            <tr>
                                                <th>Flag Minikom</th>
                                                <th class="text-center">Jumlah Paket</th>
                                                <th class="text-end">Total Realisasi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="minikom-table-body">
                                            <tr><td colspan="3" class="text-center py-3">Memuat...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-2"><i class="bx bx-wallet text-success me-1"></i> Distribusi Asal Dana (Funding Source)</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover table-modern mb-0">
                                        <thead>
                                            <tr>
                                                <th>Asal Dana</th>
                                                <th class="text-center">Jumlah Paket</th>
                                                <th class="text-end">Total Realisasi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="funding-table-body">
                                            <tr><td colspan="3" class="text-center py-3">Memuat...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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

    <!-- E-Purchasing Status Distribution Section -->
    <div class="row mb-4">
        <!-- Status Chart -->
        <div class="col-lg-5 mb-4 mb-lg-0">
            <div class="glass-card p-4 h-100">
                <h5 class="fw-bold mb-0">Distribusi Status E-Purchasing</h5>
                <p class="text-muted small mb-4">Persentase jumlah paket berdasarkan status</p>
                <div class="chart-container" style="height: 250px;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
        <!-- Status Table -->
        <div class="col-lg-7">
            <div class="glass-card p-4 h-100">
                <h5 class="fw-bold mb-0">Rincian Status E-Purchasing</h5>
                <p class="text-muted small mb-4">Statistik detail per status pengadaan</p>
                <div class="table-responsive">
                    <table class="table table-hover table-modern mb-0">
                        <thead>
                            <tr>
                                <th style="width: 250px;">Status</th>
                                <th class="text-center">Jumlah Paket</th>
                                <th class="text-end">Total Realisasi (Rp)</th>
                            </tr>
                        </thead>
                        <tbody id="status-table-body">
                            <tr>
                                <td colspan="3" class="text-center py-4">Memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
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
            <div class="modal-header modal-premium-header">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-white text-primary me-3 mb-0" style="width: 35px; height: 35px; border-radius: 8px;">
                        <i class="bx bx-briefcase-alt-2 fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="vendorModalTitle">Detail Paket Pekerjaan</h5>
                        <p class="text-white-50 extra-small mb-0">Informasi lengkap transaksi e-purchasing, rincian produk, metode &amp; asal dana</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-modern align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="table-custom-header text-center" style="width: 45px;">#</th>
                                <th class="table-custom-header">Informasi Paket &amp; Satker</th>
                                <th class="table-custom-header">Program &amp; Produk</th>
                                <th class="table-custom-header text-center">Metode &amp; Asal Dana</th>
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

<!-- Modal Rincian Produk (products_json) & Ongkos Kirim -->
<div class="modal fade" id="productBreakdownModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header modal-premium-header">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-white text-primary me-3 mb-0" style="width: 38px; height: 38px; border-radius: 10px;">
                        <i class="bx bx-purchase-tag-alt fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="prodModalTitle">Rincian Item Produk &amp; Harga</h5>
                        <p class="text-white-50 extra-small mb-0" id="prodModalSubtitle">Detail item produk E-Katalog, harga awal (origin price), harga akhir (final price) &amp; ongkos kirim</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Metadata Paket -->
                <div class="row g-2 mb-3 p-3 rounded-3" style="background: #f8f9fa; border: 1px solid #e9ecef;" id="prodModalMeta">
                </div>

                <!-- Tabel Item Produk -->
                <div class="table-responsive">
                    <table class="table table-hover table-modern align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 45px;">#</th>
                                <th>Nama Produk &amp; Product ID</th>
                                <th class="text-center">Kuantitas</th>
                                <th class="text-end">Harga Awal / Unit (Origin)</th>
                                <th class="text-end">Harga Akhir / Unit (Final)</th>
                                <th class="text-end">Subtotal Harga Awal</th>
                                <th class="text-end">Subtotal Harga Akhir</th>
                            </tr>
                        </thead>
                        <tbody id="prodModalTableBody">
                        </tbody>
                        <tfoot id="prodModalTableFoot" style="background: #f8f9fa; font-weight: 700;">
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <div class="extra-small text-muted">
                    <i class="bx bx-info-circle me-1"></i> Rincian produk berasal dari data item E-Katalog (<code>products_json</code>) beserta Ongkos Kirim (<code>shipping_fee</code>).
                </div>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    let historyChart;
    let vendorChart;
    let statusChart;
    let chartType = 'bar';
    let rawHistoryData = null;
    let globalTopVendorData = {};
    let globalPackageLookup = {};

    function formatIDR(v) {
        v = Number(v || 0);
        if (v >= 1e12) return 'Rp ' + (v / 1e12).toFixed(2) + ' T';
        if (v >= 1e9) return 'Rp ' + (v / 1e9).toFixed(2) + ' M';
        if (v >= 1e6) return 'Rp ' + (v / 1e6).toFixed(2) + ' Jt';
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(v));
    }

    function formatIDRFull(v) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(Number(v || 0)));
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

            const s = data.summary;
            // Update Summary Cards
            document.getElementById('stat-total-realisasi').innerText = formatIDRFull(s.total_realisasi || 0);
            document.getElementById('stat-total-packages').innerText = (s.total_packages || 0).toLocaleString('id-ID') + ' Paket';
            document.getElementById('stat-total-items').innerText = (s.total_product_items || 0).toLocaleString('id-ID') + ' Item Produk';
            document.getElementById('stat-other-status-packages').innerText = (s.other_status_packages || 0).toLocaleString('id-ID') + ' Paket';

            // Update year labels
            const yearLabels = document.querySelectorAll('.active-year-label');
            yearLabels.forEach(el => el.innerText = s.year_active || '2026');

            // Render Minikom & Funding Summary tables
            renderMinikomAndFunding(data.minikom_summary || {}, data.funding_summary || {});

            // Render Top 10 Products
            renderTopProducts(data.top_products || []);

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

            // Build Status Summary Chart and Table
            if (data.status_summary) {
                renderStatusData(data.status_summary);
            }

            // Top Vendor Rendering
            if (data.top_vendor) {
                globalTopVendorData = data.top_vendor;
                Object.values(globalTopVendorData).forEach(v => {
                    (v.packages || []).forEach(pkg => {
                        if (pkg.kd_paket) globalPackageLookup[pkg.kd_paket] = pkg;
                    });
                });
                renderTopVendor(data.top_vendor);
                renderVendorChart(data.top_vendor);
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

    function renderMinikomAndFunding(minikomSummary, fundingSummary) {
        const minikomBody = document.getElementById('minikom-table-body');
        if (minikomBody) {
            const keys = Object.keys(minikomSummary).sort((a, b) => minikomSummary[b].count - minikomSummary[a].count);
            if (keys.length === 0) {
                minikomBody.innerHTML = '<tr><td colspan="3" class="text-center py-3 text-muted">Tidak ada data</td></tr>';
            } else {
                minikomBody.innerHTML = keys.map(k => {
                    const item = minikomSummary[k];
                    const isMini = k.toLowerCase().indexOf('non') === -1 && k.toLowerCase().indexOf('mini') !== -1;
                    const badgeCls = isMini ? 'bg-label-warning' : 'bg-label-secondary';
                    return `
                        <tr>
                            <td><span class="badge ${badgeCls} fw-bold">${escapeHTML(k)}</span></td>
                            <td class="text-center fw-bold">${item.count.toLocaleString('id-ID')} Paket</td>
                            <td class="text-end fw-bold text-primary">${formatIDRFull(item.realisasi)}</td>
                        </tr>
                    `;
                }).join('');
            }
        }

        const fundingBody = document.getElementById('funding-table-body');
        if (fundingBody) {
            const keys = Object.keys(fundingSummary).sort((a, b) => fundingSummary[b].count - fundingSummary[a].count);
            if (keys.length === 0) {
                fundingBody.innerHTML = '<tr><td colspan="3" class="text-center py-3 text-muted">Tidak ada data</td></tr>';
            } else {
                fundingBody.innerHTML = keys.map(k => {
                    const item = fundingSummary[k];
                    const badgeCls = k === 'APBD' ? 'bg-label-primary' : (k === 'BLUD' ? 'bg-label-info' : 'bg-label-success');
                    return `
                        <tr>
                            <td><span class="badge ${badgeCls} fw-bold">${escapeHTML(k)}</span></td>
                            <td class="text-center fw-bold">${item.count.toLocaleString('id-ID')} Paket</td>
                            <td class="text-end fw-bold text-primary">${formatIDRFull(item.realisasi)}</td>
                        </tr>
                    `;
                }).join('');
            }
        }
    }

    function renderTopProducts(products) {
        const tbody = document.getElementById('top-products-table-body');
        if (!tbody) return;

        if (!products || products.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">Data item produk belum tersedia.</td></tr>';
            return;
        }

        tbody.innerHTML = products.map((p, idx) => {
            const rankCls = idx === 0 ? 'rank-1' : (idx === 1 ? 'rank-2' : (idx === 2 ? 'rank-3' : 'rank-other'));
            return `
                <tr>
                    <td class="text-center">
                        <div class="rank-badge ${rankCls} mx-auto" style="margin-right:0;">${idx + 1}</div>
                    </td>
                    <td style="max-width: 320px;">
                        <div class="pkg-title text-wrap">${escapeHTML(p.product_name)}</div>
                        <div class="extra-small text-muted">ID: <code>${escapeHTML(p.product_id || '-')}</code></div>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-label-primary fw-bold">${(p.paket_count || 0).toLocaleString('id-ID')} Paket</span>
                    </td>
                    <td class="text-center fw-bold text-dark">
                        ${(p.total_qty || 0).toLocaleString('id-ID')} Unit
                    </td>
                    <td class="text-end text-muted small">
                        ${formatIDRFull(p.avg_origin_price || 0)}
                    </td>
                    <td class="text-end fw-semibold text-dark small">
                        ${formatIDRFull(p.avg_final_price || 0)}
                    </td>
                    <td class="text-end fw-bold text-primary">
                        ${formatIDRFull(p.total_final || 0)}
                    </td>
                </tr>
            `;
        }).join('');
    }

    function showProductBreakdownModal(pkg) {
        if (!pkg) return;

        document.getElementById('prodModalTitle').innerText = pkg.nama_paket || 'Rincian Produk Paket';
        document.getElementById('prodModalSubtitle').innerText = 'No. Referensi: ' + (pkg.kd_paket || '-') + ' | Kode RUP: ' + (pkg.rup_code || '-');

        const flagMini = pkg.flag_minikom || 'Non Minikom';
        const fund = pkg.funding_source || pkg.sumber_dana || 'APBD';
        const shipFee = Number(pkg.shipping_fee || 0);

        document.getElementById('prodModalMeta').innerHTML = `
            <div class="col-md-4">
                <div class="extra-small text-muted text-uppercase fw-bold">Satker Pelaksana</div>
                <div class="small fw-bold text-dark">${escapeHTML(pkg.satker || '-')}</div>
            </div>
            <div class="col-md-4">
                <div class="extra-small text-muted text-uppercase fw-bold">Penyedia E-Katalog</div>
                <div class="small fw-bold text-primary">${escapeHTML(pkg.nama_penyedia || '-')}</div>
            </div>
            <div class="col-md-4">
                <div class="extra-small text-muted text-uppercase fw-bold">Metode, Asal Dana &amp; Ongkos Kirim</div>
                <div class="d-flex flex-wrap gap-1 mt-1">
                    <span class="badge bg-label-warning">${escapeHTML(flagMini)}</span>
                    <span class="badge bg-label-primary">Asal Dana: ${escapeHTML(fund)}</span>
                    <span class="badge bg-label-info">Ongkir: ${formatIDRFull(shipFee)}</span>
                </div>
            </div>
        `;

        const items = pkg.products_list || [];
        const tbody = document.getElementById('prodModalTableBody');
        const tfoot = document.getElementById('prodModalTableFoot');

        if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">Data rincian item produk (products_json) tidak ditemukan.</td></tr>';
            tfoot.innerHTML = '';
        } else {
            tbody.innerHTML = items.map((it, idx) => {
                return `
                    <tr>
                        <td class="text-center">${idx + 1}</td>
                        <td>
                            <div class="fw-bold text-dark small">${escapeHTML(it.product_name || '-')}</div>
                            <div class="extra-small text-muted">ID: <code>${escapeHTML(it.product_id || '-')}</code></div>
                        </td>
                        <td class="text-center fw-bold">${Number(it.qty || 0).toLocaleString('id-ID')}</td>
                        <td class="text-end text-muted small">${formatIDRFull(it.origin_price || 0)}</td>
                        <td class="text-end fw-semibold text-dark small">${formatIDRFull(it.final_price || 0)}</td>
                        <td class="text-end text-muted small">${formatIDRFull(it.subtotal_origin || 0)}</td>
                        <td class="text-end fw-bold text-primary small">${formatIDRFull(it.subtotal_final || 0)}</td>
                    </tr>
                `;
            }).join('');

            tfoot.innerHTML = `
                <tr>
                    <td colspan="5" class="text-end text-uppercase small">Subtotal Harga Produk:</td>
                    <td class="text-end text-muted">${formatIDRFull(pkg.total_origin || 0)}</td>
                    <td class="text-end text-primary">${formatIDRFull(pkg.total_final_produk || 0)}</td>
                </tr>
                <tr>
                    <td colspan="6" class="text-end text-uppercase small">Ongkos Kirim (Shipping Fee):</td>
                    <td class="text-end text-info">${formatIDRFull(shipFee)}</td>
                </tr>
                <tr style="background: #eef0ff;">
                    <td colspan="6" class="text-end text-uppercase small text-dark">Total Nilai Transaksi / Kontrak Paket:</td>
                    <td class="text-end text-primary fs-6">${formatIDRFull(pkg.realisasi || 0)}</td>
                </tr>
            `;
        }

        $('#productBreakdownModal').modal('show');
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
                                if (label.includes('Rp')) return label + ': ' + formatIDRFull(context.parsed.y);
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
                                if (label.includes('(Rp)')) {
                                    return label + ': ' + formatIDRFull(context.parsed.y);
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

    function renderStatusData(statusSummary) {
        const tbody = document.getElementById('status-table-body');
        if (!tbody) return;
        tbody.innerHTML = '';

        const labels = [];
        const counts = [];
        const colors = [];

        // Dynamic statuses list sorted by package count descending
        const statuses = Object.keys(statusSummary).sort((a, b) => statusSummary[b].count - statusSummary[a].count);
        
        const colorMap = {
            'COMPLETED': '#71dd37', // Green
            'ON PROCESS': '#03c3ec', // Cyan
            'PAYMENT OUTSIDE SYSTEM': '#696cff', // Indigo
            'ON NEGOTIATION': '#ffab00', // Orange
            'WAITING PPK REVIEW': '#ff3e1d', // Red
            'WAITING SELLER CONFIRMATION': '#8592a3' // Grey
        };
        
        const badgeMap = {
            'COMPLETED': 'bg-label-success',
            'ON PROCESS': 'bg-label-info',
            'PAYMENT OUTSIDE SYSTEM': 'bg-label-primary',
            'ON NEGOTIATION': 'bg-label-warning',
            'WAITING PPK REVIEW': 'bg-label-danger',
            'WAITING SELLER CONFIRMATION': 'bg-label-secondary'
        };

        statuses.forEach(status => {
            const data = statusSummary[status] || { count: 0, realisasi: 0 };
            if (data.count === 0) return; // Skip if no packages
            
            labels.push(status);
            counts.push(data.count);
            
            let color = colorMap[status] || '#71dd37'; // Green fallback
            let badgeClass = badgeMap[status] || 'bg-label-success';
            colors.push(color);

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <span class="badge ${badgeClass} text-uppercase fw-bold">${status}</span>
                </td>
                <td class="text-center fw-bold">${data.count.toLocaleString('id-ID')} Paket</td>
                <td class="text-end fw-bold text-primary">${formatIDRFull(data.realisasi)}</td>
            `;
            tbody.appendChild(tr);
        });

        // Render Status Doughnut Chart
        const ctx = document.getElementById('statusChart');
        if (!ctx) return;
        
        if (statusChart) statusChart.destroy();
        
        statusChart = new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: counts,
                    backgroundColor: colors,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                return label + ': ' + context.parsed + ' Paket';
                            }
                        }
                    }
                }
            }
        });
    }

    function renderTopSatker(topSatker) {
        const container = document.getElementById('top-satker-list');
        container.innerHTML = '';

        let rank = 1;
        for (const kd in topSatker) {
            const s = topSatker[kd];
            const rankCls = rank === 1 ? 'rank-1' : (rank === 2 ? 'rank-2' : (rank === 3 ? 'rank-3' : 'rank-other'));

            const html = `
                <div class="top-satker-item gap-2">
                    <div class="d-flex align-items-center flex-grow-1" style="min-width: 0;">
                        <div class="rank-badge ${rankCls} flex-shrink-0">${rank}</div>
                        <div style="min-width: 0; flex-grow: 1;">
                            <div class="fw-bold small text-truncate" title="${escapeHTML(s.nama)}">${escapeHTML(s.nama)}</div>
                            <div class="text-muted extra-small">${s.count} Paket</div>
                        </div>
                    </div>
                    <div class="text-end flex-shrink-0">
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
                <div class="top-satker-item gap-2">
                    <div class="d-flex align-items-center flex-grow-1" style="min-width: 0;">
                        <div class="rank-badge rank-other flex-shrink-0">${rank}</div>
                        <div style="min-width: 0; flex-grow: 1;">
                            <div class="fw-bold small text-truncate" title="${escapeHTML(name)}">${escapeHTML(name)}</div>
                            <div class="text-muted extra-small">${formatIDR(v.realisasi)}</div>
                        </div>
                    </div>
                    <div class="text-end flex-shrink-0">
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

        // Sort packages by realization (descending)
        const sortedPackages = vendorData.packages.sort((a, b) => b.realisasi - a.realisasi);

        sortedPackages.forEach((pkg, idx) => {
            if (pkg.kd_paket) globalPackageLookup[pkg.kd_paket] = pkg;
            const flagMini = pkg.flag_minikom || 'Non Minikom';
            const fund = pkg.funding_source || pkg.sumber_dana || 'APBD';
            const itemCount = (pkg.products_list || []).length || pkg.count_product || 0;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="text-center">
                    <span class="badge bg-label-secondary text-muted" style="width: 28px;">${idx + 1}</span>
                </td>
                <td>
                    <div class="pkg-title">${escapeHTML(pkg.nama_paket) || '-'}</div>
                    <div class="extra-small text-muted mb-1"><i class="bx bx-hash me-1"></i>REF: ${escapeHTML(pkg.kd_paket) || '-'}</div>
                    <div class="extra-small text-muted d-flex align-items-center">
                        <i class="bx bx-building-house me-1 text-primary"></i>
                        <span class="text-truncate" style="max-width: 220px;" title="${escapeHTML(pkg.satker)}">${escapeHTML(pkg.satker) || '-'}</span>
                    </div>
                </td>
                <td>
                    <div class="small fw-bold text-dark mb-1" style="max-width: 260px;">
                        <i class="bx bx-folder-open text-warning me-1"></i>${escapeHTML(pkg.nama_program) || '-'}
                    </div>
                    <div class="pkg-subtitle mb-1">
                        <i class="bx bx-purchase-tag-alt text-info me-1"></i>${escapeHTML(pkg.nama_produk) || '-'}
                    </div>
                    <button type="button" class="btn btn-xs btn-outline-primary rounded-pill py-0 px-2 vendor-item-breakdown-btn" data-kd-paket="${escapeHTML(pkg.kd_paket)}" style="font-size: 0.7rem;">
                        <i class="bx bx-search-alt me-1"></i>Rincian Produk (${itemCount} Item)
                    </button>
                </td>
                <td class="text-center">
                    <div class="d-flex flex-column gap-1 align-items-center">
                        <span class="badge bg-label-warning" style="font-size: 0.65rem;">${escapeHTML(flagMini)}</span>
                        <span class="badge bg-label-primary" style="font-size: 0.65rem;">${escapeHTML(fund)}</span>
                    </div>
                </td>
                <td class="text-center">
                    <span class="badge bg-label-info rounded-pill px-2" style="font-size: 0.65rem;">
                        <i class="bx bx-calendar-event me-1"></i>${escapeHTML(pkg.tgl_order) || '-'}
                    </span>
                </td>
                <td class="text-end">
                    <div class="extra-small text-muted mb-1">NILAI TRANSAKSI</div>
                    <div class="price-tag">${formatIDRFull(pkg.realisasi || 0)}</div>
                </td>
            `;
            tbody.appendChild(tr);
        });

        tbody.querySelectorAll('.vendor-item-breakdown-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const kd = this.getAttribute('data-kd-paket');
                if (kd && globalPackageLookup[kd]) {
                    showProductBreakdownModal(globalPackageLookup[kd]);
                }
            });
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
        
        // Append package count to labels for the legend
        const chartLabels = labels.map(l => {
            let name = l.length > 20 ? l.substring(0, 20) + '...' : l;
            return name + ' (' + topVendor[l].count + ' Pkt)';
        });

        vendorChart = new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: chartLabels,
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
                    legend: { 
                        position: 'bottom', 
                        display: true,
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: { size: 10 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                return ' ' + label;
                            }
                        }
                    }
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', loadDashboardData);
</script>

<?= $this->endSection() ?>
