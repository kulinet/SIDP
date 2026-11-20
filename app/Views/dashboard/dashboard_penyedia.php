<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<!-- Leaflet & Custom CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
  .pulse-marker-lobar {
    background-color: #10b981;
    border-radius: 50%;
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
    animation: pulse-lobar 2s infinite;
  }
  @keyframes pulse-lobar {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 14px rgba(16, 185, 129, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
  }

  .pulse-marker-ntb {
    background-color: #3b82f6;
    border-radius: 50%;
    box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
    animation: pulse-ntb 2s infinite;
  }
  @keyframes pulse-ntb {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 12px rgba(59, 130, 246, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
  }

  .pulse-marker-luar {
    background-color: #8b5cf6;
    border-radius: 50%;
    box-shadow: 0 0 0 0 rgba(139, 92, 246, 0.7);
    animation: pulse-luar 2s infinite;
  }
  @keyframes pulse-luar {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(139, 92, 246, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 12px rgba(139, 92, 246, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(139, 92, 246, 0); }
  }

  #map-domisili {
    height: 480px;
    width: 100%;
    border-radius: 12px;
    z-index: 1;
  }

  .map-dark-tiles .leaflet-tile-pane {
    filter: brightness(0.65) invert(1) contrast(3) hue-rotate(200deg) saturate(0.35);
  }

  .map-overlay-card {
    position: absolute;
    bottom: 20px;
    left: 20px;
    z-index: 1000;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(0,0,0,0.08);
    border-radius: 10px;
    padding: 10px 14px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
  }

  .map-controls-top {
    position: absolute;
    top: 20px;
    right: 20px;
    z-index: 1000;
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
  }

  .stat-card-gradient {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .stat-card-gradient:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.08) !important;
  }
</style>

<div class="container-xxl flex-grow-1 container-p-y" id="dashboard-penyedia-app">
  <!-- Header & Filters -->
  <div class="card border-0 shadow-sm mb-4" style="border-radius: 14px;">
    <div class="card-body p-4">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
          <h4 class="fw-bold mb-1 d-flex align-items-center">
            <i class="bx bx-map-pin text-primary me-2 fs-3"></i>
            <span>Dashboard Domisili Rekanan LPSE</span>
          </h4>
          <p class="text-muted small mb-0">
            Analisis sebaran domisili, metode pengadaan, dan jenis pekerjaan rekanan pemenang paket di Kabupaten Lombok Barat
          </p>
        </div>

        <!-- Filter Controls -->
        <div class="d-flex gap-2 flex-wrap align-items-center">
          <!-- Tahun -->
          <div>
            <select id="filter-tahun" class="form-select form-select-sm" style="min-width: 130px;">
              <option value="all">Semua Tahun</option>
              <?php foreach ($available_years as $y): ?>
                <option value="<?= esc($y) ?>"><?= esc($y) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Sumber Transaksi -->
          <div>
            <select id="filter-sumber" class="form-select form-select-sm" style="min-width: 140px;">
              <option value="all">Semua Sumber</option>
              <option value="tender">Tender</option>
              <option value="nontender">Non-Tender</option>
              <option value="pencatatan">Pencatatan</option>
              <option value="epurchasing">E-Katalog</option>
            </select>
          </div>

          <!-- Jenis Pengadaan -->
          <div>
            <select id="filter-jenis" class="form-select form-select-sm" style="min-width: 150px;">
              <option value="all">Semua Pekerjaan</option>
              <option value="konstruksi">Konstruksi</option>
              <option value="barang">Pengadaan Barang</option>
              <option value="konsultansi">Jasa Konsultansi</option>
              <option value="jasa_lainnya">Jasa Lainnya</option>
            </select>
          </div>

          <button id="btn-refresh-data" class="btn btn-primary btn-sm d-flex align-items-center">
            <i class="bx bx-refresh me-1"></i> Refresh
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Loading State Indicator -->
  <div id="loading-spinner" class="text-center py-5 d-none">
    <div class="spinner-border text-primary" role="status">
      <span class="visually-hidden">Memuat data...</span>
    </div>
    <p class="text-muted small mt-2">Menganalisis sebaran domisili rekanan...</p>
  </div>

  <div id="dashboard-data-container">
    <!-- Row 1: KPI Summary Cards -->
    <div class="row g-3 mb-4">
      <!-- Total Rekanan Unik -->
      <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card h-100 border-0 shadow-sm stat-card-gradient" style="border-radius: 12px; border-left: 4px solid #4f46e5 !important;">
          <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <span class="text-muted small fw-semibold">TOTAL REKANAN</span>
              <div class="avatar avatar-xs bg-label-primary rounded"><i class="bx bx-buildings fs-6"></i></div>
            </div>
            <h3 class="mb-0 fw-bold text-dark" id="kpi-total-vendors">0</h3>
            <small class="text-muted" id="kpi-total-packages">0 paket terlaksana</small>
          </div>
        </div>
      </div>

      <!-- Rekanan Lokal Lombok Barat -->
      <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card h-100 border-0 shadow-sm stat-card-gradient" style="border-radius: 12px; border-left: 4px solid #10b981 !important;">
          <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <span class="text-success small fw-semibold">LOKAL LOBAR</span>
              <span class="badge bg-label-success small" id="kpi-lobar-pct">0%</span>
            </div>
            <h3 class="mb-0 fw-bold text-success" id="kpi-lobar-vendors">0</h3>
            <small class="text-muted d-block text-truncate" id="kpi-lobar-val">Rp 0</small>
          </div>
        </div>
      </div>

      <!-- Rekanan Regional NTB Lain -->
      <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card h-100 border-0 shadow-sm stat-card-gradient" style="border-radius: 12px; border-left: 4px solid #3b82f6 !important;">
          <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <span class="text-primary small fw-semibold">REGIONAL NTB</span>
              <span class="badge bg-label-primary small" id="kpi-ntb-pct">0%</span>
            </div>
            <h3 class="mb-0 fw-bold text-primary" id="kpi-ntb-vendors">0</h3>
            <small class="text-muted d-block text-truncate" id="kpi-ntb-val">Rp 0</small>
          </div>
        </div>
      </div>

      <!-- Rekanan Luar NTB -->
      <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card h-100 border-0 shadow-sm stat-card-gradient" style="border-radius: 12px; border-left: 4px solid #8b5cf6 !important;">
          <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <span class="text-info small fw-semibold">LUAR NTB</span>
              <span class="badge bg-label-info small" id="kpi-luar-pct">0%</span>
            </div>
            <h3 class="mb-0 fw-bold text-info" id="kpi-luar-vendors">0</h3>
            <small class="text-muted d-block text-truncate" id="kpi-luar-val">Rp 0</small>
          </div>
        </div>
      </div>

      <!-- Total Nilai Transaksi -->
      <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card h-100 border-0 shadow-sm stat-card-gradient" style="border-radius: 12px; border-left: 4px solid #f59e0b !important;">
          <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <span class="text-warning small fw-semibold">NILAI KONTRAK</span>
              <div class="avatar avatar-xs bg-label-warning rounded"><i class="bx bx-dollar-circle fs-6"></i></div>
            </div>
            <h3 class="mb-0 fw-bold text-warning" id="kpi-total-val-short">Rp 0</h3>
            <small class="text-muted d-block text-truncate" id="kpi-total-val">Rp 0</small>
          </div>
        </div>
      </div>

      <!-- Rasio Nilai Lokal vs Luar -->
      <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card h-100 border-0 shadow-sm stat-card-gradient" style="border-radius: 12px; border-left: 4px solid #06b6d4 !important;">
          <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <span class="text-dark small fw-semibold">ANGGARAN LOKAL</span>
              <i class="bx bx-pie-chart-alt text-info fs-6"></i>
            </div>
            <h3 class="mb-0 fw-bold text-dark" id="kpi-lobar-val-pct">0%</h3>
            <small class="text-muted">terserap di Lombok Barat</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Row 2: Interactive Geolocation Heat Map -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 14px;">
          <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
              <h5 class="card-title mb-0 fw-bold d-flex align-items-center">
                <i class="bx bx-globe text-primary me-2"></i> Peta Heat Map &amp; Sebaran Geografis Rekanan
              </h5>
              <small class="text-muted">Intensitas sebaran domisili rekanan pemenang paket pekerjaan di Lombok Barat</small>
            </div>
            <div class="d-flex gap-2 align-items-center flex-wrap">
              <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-focus-lobar">
                <i class="bx bx-target-lock me-1"></i> Fokus Lombok / NTB
              </button>
              <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-focus-indo">
                <i class="bx bx-fullscreen me-1"></i> Seluruh Indonesia
              </button>
              <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-primary active" id="btn-map-light">Light</button>
                <button type="button" class="btn btn-outline-primary" id="btn-map-dark">Cyber Dark</button>
              </div>
            </div>
          </div>

          <div class="card-body p-0 position-relative">
            <!-- Leaflet Map Container -->
            <div id="map-domisili"></div>

            <!-- Floating Map Legend -->
            <div class="map-overlay-card">
              <div class="fw-bold small mb-1 text-dark"><i class="bx bx-layer me-1"></i> Legenda Domisili:</div>
              <div class="d-flex flex-column gap-1 small">
                <div class="d-flex align-items-center">
                  <span class="badge rounded-pill me-2" style="background:#10b981; width:12px; height:12px; padding:0;"></span>
                  <span><strong>Lokal:</strong> Kab. Lombok Barat</span>
                </div>
                <div class="d-flex align-items-center">
                  <span class="badge rounded-pill me-2" style="background:#3b82f6; width:12px; height:12px; padding:0;"></span>
                  <span><strong>Regional:</strong> NTB (Mataram, Loteng, dll.)</span>
                </div>
                <div class="d-flex align-items-center">
                  <span class="badge rounded-pill me-2" style="background:#8b5cf6; width:12px; height:12px; padding:0;"></span>
                  <span><strong>Luar Daerah:</strong> Jawa, Bali, Jakarta, dll.</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Row 3: Charts (Metode & Jenis Pengadaan) -->
    <div class="row g-4 mb-4">
      <!-- Chart 1: Metode Pengadaan vs Domisili -->
      <div class="col-lg-6 col-md-12">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
          <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <div>
              <h6 class="card-title mb-0 fw-bold">Perbandingan Metode Pekerjaan</h6>
              <small class="text-muted">Komposisi domisili pada setiap metode pengadaan</small>
            </div>
            <span class="badge bg-label-primary">Metode</span>
          </div>
          <div class="card-body p-3">
            <div style="height: 320px;">
              <canvas id="chart-metode"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Chart 2: Jenis Pekerjaan vs Domisili -->
      <div class="col-lg-6 col-md-12">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
          <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <div>
              <h6 class="card-title mb-0 fw-bold">Perbandingan Jenis Pekerjaan</h6>
              <small class="text-muted">Distribusi rekanan pada konstruksi, barang, dan konsultansi</small>
            </div>
            <span class="badge bg-label-info">Jenis Pengadaan</span>
          </div>
          <div class="card-body p-3">
            <div style="height: 320px;">
              <canvas id="chart-jenis"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Row 4: Charts (Donut Proporsi & Nilai Kontrak per Domisili) -->
    <div class="row g-4 mb-4">
      <!-- Chart 3: Donut Proporsi Domisili -->
      <div class="col-lg-5 col-md-12">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
          <div class="card-header bg-white py-3 border-bottom">
            <h6 class="card-title mb-0 fw-bold">Proporsi Domisili Rekanan</h6>
            <small class="text-muted">Persentase jumlah penyedia berdasarkan kota/kabupaten</small>
          </div>
          <div class="card-body p-4 d-flex flex-column align-items-center justify-content-center">
            <div style="height: 250px; width: 250px;" class="position-relative">
              <canvas id="chart-donut-domisili"></canvas>
            </div>
            <div id="donut-legend-custom" class="w-100 mt-3 small"></div>
          </div>
        </div>
      </div>

      <!-- Chart 4: Top Domisili by Nilai Kontrak (Rp Miliar) -->
      <div class="col-lg-7 col-md-12">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
          <div class="card-header bg-white py-3 border-bottom">
            <h6 class="card-title mb-0 fw-bold">Perputaran Nilai Kontrak Terbesar per Domisili</h6>
            <small class="text-muted">Total nilai kontrak/realisasi pekerjaan dalam Miliar Rupiah (Rp M)</small>
          </div>
          <div class="card-body p-3">
            <div style="height: 310px;">
              <canvas id="chart-nilai-domisili"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Row 5: Detailed Data Tables -->
    <div class="row g-4 mb-4">
      <!-- Table 1: Peringkat Domisili -->
      <div class="col-lg-6 col-md-12">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
          <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <div>
              <h6 class="card-title mb-0 fw-bold">Peringkat Domisili Rekanan</h6>
              <small class="text-muted">Daftar kota/kabupaten dengan jumlah rekanan terbanyak</small>
            </div>
            <span class="badge bg-label-primary" id="badge-top-domisili-count">0 Wilayah</span>
          </div>
          <div class="table-responsive" style="max-height: 420px;">
            <table class="table table-hover table-striped mb-0 small">
              <thead class="table-light sticky-top">
                <tr>
                  <th style="width: 30px;">#</th>
                  <th>Wilayah Domisili</th>
                  <th class="text-center">Kategori</th>
                  <th class="text-end">Rekanan</th>
                  <th class="text-end">Paket</th>
                  <th class="text-end">Total Nilai</th>
                </tr>
              </thead>
              <tbody id="table-domisili-body">
                <tr><td colspan="6" class="text-center text-muted py-4">Memuat data domisili...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Table 2: Rekanan Pemenang Paket Terbesar -->
      <div class="col-lg-6 col-md-12">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
          <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
              <h6 class="card-title mb-0 fw-bold">Top Rekanan Pemenang Paket Terbesar</h6>
              <small class="text-muted">Penyedia dengan perolehan nilai kontrak tertinggi di LPSE</small>
            </div>
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-label-primary" id="badge-top-rekanan-count">0 Rekanan</span>
              <a href="<?= base_url('dashboard/exportDashboardRekanan') ?>" id="btn-export-rekanan" class="btn btn-sm btn-outline-success d-flex align-items-center shadow-none" title="Unduh data rekanan ke Excel">
                <i class="bx bx-download me-1"></i> Export Excel
              </a>
            </div>
          </div>
          <div class="px-3 py-2 border-bottom bg-light">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-white border-end-0"><i class="bx bx-search text-muted"></i></span>
              <input type="text" id="search-rekanan-table" class="form-control border-start-0" placeholder="Cari nama rekanan atau domisili...">
            </div>
          </div>
          <div class="table-responsive" style="max-height: 420px;">
            <table class="table table-hover table-striped mb-0 small">
              <thead class="table-light sticky-top">
                <tr>
                  <th style="width: 30px;">#</th>
                  <th>Nama Rekanan</th>
                  <th>Domisili</th>
                  <th class="text-center">Paket</th>
                  <th class="text-end">Total Nilai Kontrak</th>
                </tr>
              </thead>
              <tbody id="table-rekanan-body">
                <tr><td colspan="5" class="text-center text-muted py-4">Memuat data rekanan...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Leaflet JS & Leaflet.heat -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Optimize Canvas2D context for leaflet-heat to eliminate willReadFrequently warning
(function() {
  const originalGetContext = HTMLCanvasElement.prototype.getContext;
  HTMLCanvasElement.prototype.getContext = function(type, attributes) {
    if (type === '2d') {
      attributes = attributes || {};
      attributes.willReadFrequently = true;
    }
    return originalGetContext.call(this, type, attributes);
  };
})();

document.addEventListener('DOMContentLoaded', function() {
  const API_URL = '<?= base_url('dashboard/getDashboardPenyediaData') ?>';

  // State Map & Charts
  let map = null;
  let heatLayer = null;
  let markerLayerGroup = null;
  let currentTileLayer = null;
  let activeTheme = 'light';

  let chartMetode = null;
  let chartJenis = null;
  let chartDonut = null;
  let chartNilaiDomisili = null;

  // Standard Leaflet Tile Provider (OpenStreetMap)
  const TILE_OSM = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
  const TILE_ATTR = '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors';

  // Format Currency
  const fmt = (v) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v || 0);
  const fmtShort = (v) => {
    if (v >= 1e12) return 'Rp ' + (v/1e12).toFixed(1) + ' T';
    if (v >= 1e9)  return 'Rp ' + (v/1e9).toFixed(1) + ' M';
    if (v >= 1e6)  return 'Rp ' + (v/1e6).toFixed(1) + ' Jt';
    return fmt(v);
  };

  // 1. Initialize Leaflet Map
  function initMap() {
    map = L.map('map-domisili', {
      center: [-8.6500, 116.3000],
      zoom: 9,
      zoomControl: true,
      attributionControl: true
    });

    currentTileLayer = L.tileLayer(TILE_OSM, {
      maxZoom: 19,
      subdomains: ['a', 'b', 'c'],
      attribution: TILE_ATTR
    }).addTo(map);

    markerLayerGroup = L.layerGroup().addTo(map);
  }

  // 2. Fetch Data & Refresh UI
  function loadDashboardData() {
    const tahun = document.getElementById('filter-tahun').value;
    const sumber = document.getElementById('filter-sumber').value;
    const jenis = document.getElementById('filter-jenis').value;

    const btnExportRek = document.getElementById('btn-export-rekanan');
    if (btnExportRek) {
      btnExportRek.href = `<?= base_url('dashboard/exportDashboardRekanan') ?>?tahun=${encodeURIComponent(tahun)}&sumber=${encodeURIComponent(sumber)}&jenis=${encodeURIComponent(jenis)}`;
    }

    const spinner = document.getElementById('loading-spinner');
    const container = document.getElementById('dashboard-data-container');

    spinner.classList.remove('d-none');
    container.style.opacity = '0.4';

    const url = `${API_URL}?tahun=${encodeURIComponent(tahun)}&sumber=${encodeURIComponent(sumber)}&jenis=${encodeURIComponent(jenis)}`;

    fetch(url)
      .then(res => res.json())
      .then(res => {
        spinner.classList.add('d-none');
        container.style.opacity = '1';

        if (res.status === 'success') {
          renderKPI(res.kpi);
          renderMap(res.map);
          renderCharts(res.charts);
          renderTables(res.tables);
        } else {
          console.error(res.message);
        }
      })
      .catch(err => {
        spinner.classList.add('d-none');
        container.style.opacity = '1';
        console.error('Error fetching dashboard data:', err);
      });
  }

  // 3. Render KPI Summary Cards
  function renderKPI(kpi) {
    document.getElementById('kpi-total-vendors').textContent = Number(kpi.total_vendors || 0).toLocaleString('id-ID');
    document.getElementById('kpi-total-packages').textContent = `${Number(kpi.total_packages || 0).toLocaleString('id-ID')} paket terdata`;

    document.getElementById('kpi-lobar-vendors').textContent = Number(kpi.lobar_vendors || 0).toLocaleString('id-ID');
    document.getElementById('kpi-lobar-pct').textContent = `${kpi.lobar_vendors_pct || 0}%`;
    document.getElementById('kpi-lobar-val').textContent = fmtShort(kpi.lobar_value || 0);

    document.getElementById('kpi-ntb-vendors').textContent = Number(kpi.ntb_vendors || 0).toLocaleString('id-ID');
    document.getElementById('kpi-ntb-pct').textContent = `${kpi.ntb_vendors_pct || 0}%`;
    document.getElementById('kpi-ntb-val').textContent = fmtShort(kpi.ntb_value || 0);

    document.getElementById('kpi-luar-vendors').textContent = Number(kpi.luar_vendors || 0).toLocaleString('id-ID');
    document.getElementById('kpi-luar-pct').textContent = `${kpi.luar_vendors_pct || 0}%`;
    document.getElementById('kpi-luar-val').textContent = fmtShort(kpi.luar_value || 0);

    document.getElementById('kpi-total-val-short').textContent = kpi.total_value_short || 'Rp 0';
    document.getElementById('kpi-total-val').textContent = kpi.total_value_formatted || 'Rp 0';

    document.getElementById('kpi-lobar-val-pct').textContent = `${kpi.lobar_value_pct || 0}%`;
  }

  // 4. Render Map & Heatmap Layer
  function renderMap(mapData) {
    if (!map) return;

    // Reset Marker Group
    markerLayerGroup.clearLayers();

    // Reset Heat Layer
    if (heatLayer) {
      map.removeLayer(heatLayer);
      heatLayer = null;
    }

    const nodes = mapData.nodes || [];
    const heatPoints = mapData.heat_points || [];

    // Add Heatmap Layer
    if (heatPoints.length > 0 && typeof L.heatLayer === 'function') {
      heatLayer = L.heatLayer(heatPoints, {
        radius: 35,
        blur: 25,
        maxZoom: 14,
        gradient: { 0.2: '#3b82f6', 0.5: '#10b981', 0.8: '#f59e0b', 1.0: '#ef4444' }
      }).addTo(map);
    }

    // Add Interactive Pulsing Circle Markers
    nodes.forEach(node => {
      let pulseClass = 'pulse-marker-lobar';
      let colorBadge = 'bg-success';
      let catName = 'Penyedia Lokal';

      if (node.cat === 'ntb_lain') {
        pulseClass = 'pulse-marker-ntb';
        colorBadge = 'bg-primary';
        catName = 'Regional NTB';
      } else if (node.cat === 'luar_ntb') {
        pulseClass = 'pulse-marker-luar';
        colorBadge = 'bg-label-info';
        catName = 'Luar Daerah';
      }

      // Radius based on vendor count
      const radius = Math.min(28, Math.max(12, Math.sqrt(node.vendors_count) * 4.5));

      const markerHtml = `
        <div style="width:${radius*2}px; height:${radius*2}px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:bold; font-size:11px; border:2px solid #fff; box-shadow:0 2px 10px rgba(0,0,0,0.3);" class="${pulseClass}">
          ${node.vendors_count}
        </div>
      `;

      const customIcon = L.divIcon({
        className: 'custom-hub-marker',
        html: markerHtml,
        iconSize: [radius*2, radius*2],
        iconAnchor: [radius, radius]
      });

      const marker = L.marker([node.lat, node.lng], { icon: customIcon });

      // Popup Content
      let topVendorsHtml = '';
      if (node.top_vendors && node.top_vendors.length > 0) {
        topVendorsHtml = `<div class="mt-2 pt-2 border-top small">
          <strong class="text-muted d-block mb-1">Top Rekanan:</strong>
          ${node.top_vendors.map(v => `<div class="d-flex justify-content-between text-truncate"><span>• ${v.name}</span> <span class="fw-bold text-dark ms-1">${v.val}</span></div>`).join('')}
        </div>`;
      }

      const popupHtml = `
        <div style="min-width: 220px; font-family: inherit;">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <h6 class="fw-bold mb-0 text-dark">${node.label}</h6>
            <span class="badge ${colorBadge} small">${catName}</span>
          </div>
          <div class="small text-muted mb-2">Koordinat: ${node.lat.toFixed(4)}, ${node.lng.toFixed(4)}</div>
          
          <div class="row g-2 text-center small mb-2">
            <div class="col-6">
              <div class="p-1 bg-light rounded border">
                <span class="text-muted d-block" style="font-size:10px;">REKANAN</span>
                <strong class="text-dark fs-6">${node.vendors_count}</strong>
              </div>
            </div>
            <div class="col-6">
              <div class="p-1 bg-light rounded border">
                <span class="text-muted d-block" style="font-size:10px;">PAKET</span>
                <strong class="text-primary fs-6">${node.packages_count}</strong>
              </div>
            </div>
          </div>
          
          <div class="p-2 bg-light rounded border text-center mb-1">
            <span class="text-muted d-block small" style="font-size:10px;">TOTAL NILAI KONTRAK</span>
            <strong class="text-success">${node.total_val_formatted}</strong>
          </div>

          ${topVendorsHtml}
        </div>
      `;

      marker.bindPopup(popupHtml);
      markerLayerGroup.addLayer(marker);
    });
  }

  // 5. Render All Chart.js Visualizations
  function renderCharts(cData) {
    // 5A. Chart Metode (Stacked Bar)
    if (chartMetode) chartMetode.destroy();
    const ctxMetode = document.getElementById('chart-metode').getContext('2d');
    chartMetode = new Chart(ctxMetode, {
      type: 'bar',
      data: {
        labels: cData.metode.categories,
        datasets: [
          { label: 'Lokal Lobar', data: cData.metode.lobar, backgroundColor: '#10b981', borderRadius: 4 },
          { label: 'Regional NTB', data: cData.metode.ntb_lain, backgroundColor: '#3b82f6', borderRadius: 4 },
          { label: 'Luar NTB', data: cData.metode.luar_ntb, backgroundColor: '#8b5cf6', borderRadius: 4 },
          { label: 'Belum Terdata', data: cData.metode.unclassified, backgroundColor: '#cbd5e1', borderRadius: 4 }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: { stacked: true, grid: { display: false } },
          y: { stacked: true, grid: { color: 'rgba(0,0,0,0.05)' } }
        },
        plugins: {
          legend: { position: 'top' },
          tooltip: {
            callbacks: {
              label: ctx => ` ${ctx.dataset.label}: ${ctx.raw} paket`
            }
          }
        }
      }
    });

    // 5B. Chart Jenis Pengadaan (Grouped Bar)
    if (chartJenis) chartJenis.destroy();
    const ctxJenis = document.getElementById('chart-jenis').getContext('2d');
    chartJenis = new Chart(ctxJenis, {
      type: 'bar',
      data: {
        labels: cData.jenis.categories,
        datasets: [
          { label: 'Lokal Lobar', data: cData.jenis.lobar, backgroundColor: '#10b981', borderRadius: 5 },
          { label: 'Regional NTB', data: cData.jenis.ntb_lain, backgroundColor: '#3b82f6', borderRadius: 5 },
          { label: 'Luar NTB', data: cData.jenis.luar_ntb, backgroundColor: '#8b5cf6', borderRadius: 5 }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: { grid: { display: false } },
          y: { grid: { color: 'rgba(0,0,0,0.05)' } }
        },
        plugins: {
          legend: { position: 'top' },
          tooltip: {
            callbacks: {
              label: ctx => ` ${ctx.dataset.label}: ${ctx.raw} paket`
            }
          }
        }
      }
    });

    // 5C. Chart Donut Domisili
    if (chartDonut) chartDonut.destroy();
    const ctxDonut = document.getElementById('chart-donut-domisili').getContext('2d');
    const donutColors = ['#10b981', '#3b82f6', '#06b6d4', '#f59e0b', '#8b5cf6', '#cbd5e1'];
    chartDonut = new Chart(ctxDonut, {
      type: 'doughnut',
      data: {
        labels: cData.domisili_donut.labels,
        datasets: [{
          data: cData.domisili_donut.values,
          backgroundColor: donutColors,
          borderWidth: 2,
          hoverOffset: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: ctx => ` ${ctx.label}: ${ctx.raw} rekanan`
            }
          }
        }
      }
    });

    // Custom legend for Donut
    let donutLegendHtml = '<div class="row g-2">';
    cData.domisili_donut.labels.forEach((lbl, idx) => {
      const val = cData.domisili_donut.values[idx] || 0;
      donutLegendHtml += `
        <div class="col-6 d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center text-truncate">
            <span style="width:10px; height:10px; border-radius:50%; background:${donutColors[idx]}; display:inline-block; margin-right:6px; flex-shrink:0;"></span>
            <span class="text-truncate">${lbl}</span>
          </div>
          <strong class="ms-1">${val}</strong>
        </div>
      `;
    });
    donutLegendHtml += '</div>';
    document.getElementById('donut-legend-custom').innerHTML = donutLegendHtml;

    // 5D. Chart Top Domisili by Nilai Kontrak
    if (chartNilaiDomisili) chartNilaiDomisili.destroy();
    const ctxNilai = document.getElementById('chart-nilai-domisili').getContext('2d');
    chartNilaiDomisili = new Chart(ctxNilai, {
      type: 'bar',
      data: {
        labels: cData.nilai_by_domisili.labels,
        datasets: [{
          label: 'Total Nilai Kontrak (Rp Miliar)',
          data: cData.nilai_by_domisili.values,
          backgroundColor: '#3b82f6',
          borderRadius: 6
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: {
            grid: { color: 'rgba(0,0,0,0.05)' },
            ticks: { callback: v => `Rp ${v} M` }
          },
          y: { grid: { display: false } }
        },
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: ctx => ` Total: Rp ${ctx.raw} Miliar`
            }
          }
        }
      }
    });
  }

  // 6. Render Data Tables
  function renderTables(tData) {
    // 6A. Table Domisili
    const domBody = document.getElementById('table-domisili-body');
    const domList = tData.top_domisili || [];
    document.getElementById('badge-top-domisili-count').textContent = `${domList.length} Wilayah Terdata`;

    if (domList.length === 0) {
      domBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data domisili yang cocok dengan filter</td></tr>';
    } else {
      domBody.innerHTML = domList.map((d, i) => {
        let badge = '<span class="badge bg-label-secondary">Lainnya</span>';
        if (d.cat === 'lobar') badge = '<span class="badge bg-label-success">Lokal Lobar</span>';
        else if (d.cat === 'ntb_lain') badge = '<span class="badge bg-label-primary">Regional NTB</span>';
        else if (d.cat === 'luar_ntb') badge = '<span class="badge bg-label-info">Luar Daerah</span>';

        return `
          <tr>
            <td>${i + 1}</td>
            <td><strong class="text-dark">${d.domisili}</strong></td>
            <td class="text-center">${badge}</td>
            <td class="text-end fw-semibold">${d.vendors.toLocaleString('id-ID')}</td>
            <td class="text-end">${d.packages.toLocaleString('id-ID')}</td>
            <td class="text-end fw-bold text-success">${d.total_val_formatted}</td>
          </tr>
        `;
      }).join('');
    }

    // 6B. Table Rekanan Terbesar (Semua Rekanan)
    currentRekananList = tData.top_rekanan || [];
    renderRekananRows(currentRekananList);
  }

  function renderRekananRows(list) {
    const rekBody = document.getElementById('table-rekanan-body');
    const badgeCount = document.getElementById('badge-top-rekanan-count');
    if (badgeCount) {
      badgeCount.textContent = `${list.length.toLocaleString('id-ID')} Rekanan`;
    }

    if (!list || list.length === 0) {
      rekBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data rekanan yang cocok dengan filter / pencarian</td></tr>';
      return;
    }

    rekBody.innerHTML = list.map((r, i) => {
      let domBadge = '<span class="badge bg-label-secondary small">-</span>';
      if (r.cat === 'lobar') domBadge = '<span class="badge bg-label-success small"><i class="bx bx-map-pin me-1"></i>Lombok Barat</span>';
      else if (r.cat === 'ntb_lain') domBadge = `<span class="badge bg-label-primary small">${r.domisili}</span>`;
      else if (r.cat === 'luar_ntb') domBadge = `<span class="badge bg-label-info small">${r.domisili}</span>`;

      return `
        <tr>
          <td>${i + 1}</td>
          <td><span class="fw-semibold text-dark">${r.nama_penyedia}</span></td>
          <td>${domBadge}</td>
          <td class="text-center"><span class="badge bg-label-primary rounded-pill">${r.total_paket}</span></td>
          <td class="text-end fw-bold text-success">${r.total_nilai_formatted}</td>
        </tr>
      `;
    }).join('');
  }

  // Live filter pencarian rekanan
  document.getElementById('search-rekanan-table')?.addEventListener('input', function(e) {
    const q = (e.target.value || '').toLowerCase().trim();
    if (!q) {
      renderRekananRows(currentRekananList);
    } else {
      const filtered = currentRekananList.filter(r => 
        (r.nama_penyedia && r.nama_penyedia.toLowerCase().includes(q)) ||
        (r.domisili && r.domisili.toLowerCase().includes(q))
      );
      renderRekananRows(filtered);
    }
  });

  // Event Listeners for Map Controls
  document.getElementById('btn-focus-lobar')?.addEventListener('click', function() {
    if (map) map.flyTo([-8.6500, 116.2000], 10, { animate: true, duration: 1.2 });
  });

  document.getElementById('btn-focus-indo')?.addEventListener('click', function() {
    if (map) map.flyTo([-2.5489, 118.0149], 5, { animate: true, duration: 1.5 });
  });

  document.getElementById('btn-map-light')?.addEventListener('click', function() {
    const mapEl = document.getElementById('map-domisili');
    if (mapEl) mapEl.classList.remove('map-dark-tiles');
    activeTheme = 'light';
    document.getElementById('btn-map-light').classList.add('active');
    document.getElementById('btn-map-dark').classList.remove('active');
  });

  document.getElementById('btn-map-dark')?.addEventListener('click', function() {
    const mapEl = document.getElementById('map-domisili');
    if (mapEl) mapEl.classList.add('map-dark-tiles');
    activeTheme = 'dark';
    document.getElementById('btn-map-dark').classList.add('active');
    document.getElementById('btn-map-light').classList.remove('active');
  });

  // Filter Trigger
  ['filter-tahun', 'filter-sumber', 'filter-jenis'].forEach(id => {
    document.getElementById(id)?.addEventListener('change', loadDashboardData);
  });
  document.getElementById('btn-refresh-data')?.addEventListener('click', loadDashboardData);

  // Initialize
  initMap();
  loadDashboardData();
});
</script>
<?= $this->endSection() ?>
