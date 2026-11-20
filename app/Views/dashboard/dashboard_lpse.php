<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?>

<?= $this->section('content') ?>
<div class="container-xxl flex-grow-1 container-p-y" id="dashboard-content">

  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
      <h4 class="fw-bold mb-1">
        <span class="text-muted fw-light">Data Pengadaan /</span> Dashboard LPSE
      </h4>
      <p class="text-muted mb-0">Ringkasan total pagu & realisasi seluruh metode pengadaan</p>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
      <select id="year-filter" class="form-select form-select-sm" style="width:130px;">
        <option value="all">Semua Tahun</option>
      </select>
      <select id="quarter-filter" class="form-select form-select-sm" style="width:150px;">
        <option value="all">Semua Triwulan</option>
        <option value="1">Triwulan I (Q1)</option>
        <option value="2">Triwulan II (Q2)</option>
        <option value="3">Triwulan III (Q3)</option>
        <option value="4">Triwulan IV (Q4)</option>
      </select>
      <a href="<?= base_url('dashboard/clear-cache') ?>" class="btn btn-outline-danger btn-sm">
        <i class="bx bx-refresh me-1"></i> Clear Cache
      </a>
      <button onclick="exportToPDF()" class="btn btn-primary btn-sm">
        <i class="bx bxs-file-pdf me-1"></i> Download PDF
      </button>
    </div>
  </div>

  <!-- Total Summary & Efficiency -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 12px;">
        <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
          <h6 class="fw-bold mb-0 text-uppercase small text-muted tracking-widest" style="letter-spacing: 1px;">Ikhtisar Efisiensi Pengadaan</h6>
        </div>
        <div class="card-body py-4">
          <div class="row g-4 align-items-center text-center text-md-start">
            <div class="col-md-3 border-end-md">
              <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-1">
                <div class="avatar avatar-sm bg-label-secondary rounded me-2"><i class="bx bx-archive fs-5"></i></div>
                <p class="text-muted small mb-0">Total Pagu</p>
              </div>
              <h4 class="fw-extrabold mb-0" id="total-all-pagu" style="color: #1e293b;">Rp 0</h4>
            </div>
            <div class="col-md-3 border-end-md">
              <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-1">
                <div class="avatar avatar-sm bg-label-primary rounded me-2"><i class="bx bx-check-double fs-5"></i></div>
                <p class="text-muted small mb-0">Total Realisasi</p>
              </div>
              <h4 class="fw-extrabold mb-0" id="total-all-realisasi" style="color: #2563eb;">Rp 0</h4>
            </div>
            <div class="col-md-3 border-end-md">
              <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-1">
                <div class="avatar avatar-sm bg-label-success rounded me-2"><i class="bx bx-trending-up fs-5"></i></div>
                <p class="text-muted small mb-0">Total Efisiensi</p>
              </div>
              <h4 class="fw-extrabold mb-0 text-success" id="total-all-efisiensi">Rp 0</h4>
            </div>
            <div class="col-md-3">
              <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-1">
                <div class="avatar avatar-sm bg-label-info rounded me-2"><i class="bx bx-pie-chart-alt-2 fs-5"></i></div>
                <p class="text-muted small mb-0">Rasio Efisiensi</p>
              </div>
              <h4 class="fw-extrabold mb-0 text-info" id="total-all-pct">0%</h4>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <style>
    @media (min-width: 768px) {
      .border-end-md { border-right: 1px solid #e2e8f0 !important; }
    }
    .fw-extrabold { font-weight: 800; }
    .text-teal { color: #0d9488 !important; }
    .text-purple { color: #7c3aed !important; }
    .btn-xs { padding: 0.2rem 0.5rem; font-size: 0.75rem; border-radius: 4px; }
    #kanalTabs .nav-link { border-radius: 8px; color: #475569; transition: all 0.2s; }
    #kanalTabs .nav-link.active { background-color: #f1f5f9; color: #0f172a !important; font-weight: 700; border-bottom: 2px solid #2563eb; }

    /* Print Styles */
    @media print {
      body * { visibility: hidden; }
      #dashboard-content, #dashboard-content * { visibility: visible; }
      #dashboard-content {
        position: absolute;
        left: 0;
        top: 0;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
      }
      .btn, #year-filter, #quarter-filter, .card-header select, .layout-navbar, .layout-menu, .footer {
        display: none !important;
      }
      .card {
        box-shadow: none !important;
        border: 1px solid #eee !important;
        break-inside: avoid;
      }
      canvas {
        max-width: 100% !important;
        height: auto !important;
      }
      @page {
        size: landscape;
        margin: 10mm;
      }
    }
  </style>

  <!-- Summary Cards -->
  <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-5 g-3 mb-4" id="summary-cards">
    <div class="col">
      <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #2563eb !important;">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <p class="text-muted small mb-0">Tender</p>
              <h5 class="fw-bold mb-0" style="color: #2563eb;" id="card-tender-realisasi">-</h5>
              <div class="small text-muted">Pagu: <span id="card-tender-pagu">-</span></div>
              <div class="small text-success mt-1">Efisiensi: <span id="card-tender-efisiensi">-</span></div>
            </div>
            <div class="avatar avatar-md rounded-circle" style="background-color: rgba(37,99,235,0.1); color: #2563eb;">
              <i class="bx bx-clipboard fs-4"></i>
            </div>
          </div>
          <div class="progress mb-1" style="height: 6px;">
            <div id="card-tender-progress" class="progress-bar" role="progressbar" style="width: 0%; background-color: #2563eb;"></div>
          </div>
          <div class="d-flex justify-content-between small">
            <span class="text-muted" id="card-tender-count">0 paket</span>
            <span class="fw-bold" style="color: #2563eb;" id="card-tender-pct">0%</span>
          </div>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #0d9488 !important;">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <p class="text-muted small mb-0">Non Tender</p>
              <h5 class="fw-bold mb-0" style="color: #0d9488;" id="card-nontender-realisasi">-</h5>
              <div class="small text-muted">Pagu: <span id="card-nontender-pagu">-</span></div>
              <div class="small text-success mt-1">Efisiensi: <span id="card-nontender-efisiensi">-</span></div>
            </div>
            <div class="avatar avatar-md rounded-circle" style="background-color: rgba(13,148,136,0.1); color: #0d9488;">
              <i class="bx bx-file fs-4"></i>
            </div>
          </div>
          <div class="progress mb-1" style="height: 6px;">
            <div id="card-nontender-progress" class="progress-bar" role="progressbar" style="width: 0%; background-color: #0d9488;"></div>
          </div>
          <div class="d-flex justify-content-between small">
            <span class="text-muted" id="card-nontender-count">0 paket</span>
            <span class="fw-bold" style="color: #0d9488;" id="card-nontender-pct">0%</span>
          </div>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #10b981 !important;">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <p class="text-muted small mb-0">E-Purchasing</p>
              <h5 class="fw-bold mb-0" style="color: #10b981;" id="card-epurchasing-realisasi">-</h5>
              <div class="small text-muted">Pagu: <span id="card-epurchasing-pagu">-</span></div>
              <div class="small text-success mt-1">Efisiensi: <span id="card-epurchasing-efisiensi">-</span></div>
            </div>
            <div class="avatar avatar-md rounded-circle" style="background-color: rgba(16,185,129,0.1); color: #10b981;">
              <i class="bx bx-cart fs-4"></i>
            </div>
          </div>
          <div class="progress mb-1" style="height: 6px;">
            <div id="card-epurchasing-progress" class="progress-bar" role="progressbar" style="width: 0%; background-color: #10b981;"></div>
          </div>
          <div class="d-flex justify-content-between small">
            <span class="text-muted" id="card-epurchasing-count">0 paket</span>
            <span class="fw-bold" style="color: #10b981;" id="card-epurchasing-pct">0%</span>
          </div>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #7c3aed !important;">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <p class="text-muted small mb-0">Pencatatan Non Tender</p>
              <h5 class="fw-bold mb-0" style="color: #7c3aed;" id="card-pct-nontender-realisasi">-</h5>
              <div class="small text-muted">Pagu: <span id="card-pct-nontender-pagu">-</span></div>
              <div class="small text-success mt-1">Efisiensi: <span id="card-pct-nontender-efisiensi">-</span></div>
            </div>
            <div class="avatar avatar-md rounded-circle" style="background-color: rgba(124,58,237,0.1); color: #7c3aed;">
              <i class="bx bx-receipt fs-4"></i>
            </div>
          </div>
          <div class="progress mb-1" style="height: 6px;">
            <div id="card-pct-nontender-progress" class="progress-bar" role="progressbar" style="width: 0%; background-color: #7c3aed;"></div>
          </div>
          <div class="d-flex justify-content-between small">
            <span class="text-muted" id="card-pct-nontender-count">0 paket</span>
            <span class="fw-bold" style="color: #7c3aed;" id="card-pct-nontender-pct">0%</span>
          </div>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #f59e0b !important;">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <p class="text-muted small mb-0">Pencatatan Swakelola</p>
              <h5 class="fw-bold mb-0" style="color: #f59e0b;" id="card-pct-swakelola-realisasi">-</h5>
              <div class="small text-muted">Pagu: <span id="card-pct-swakelola-pagu">-</span></div>
              <div class="small text-success mt-1">Efisiensi: <span id="card-pct-swakelola-efisiensi">-</span></div>
            </div>
            <div class="avatar avatar-md rounded-circle" style="background-color: rgba(245,158,11,0.1); color: #f59e0b;">
              <i class="bx bx-building-house fs-4"></i>
            </div>
          </div>
          <div class="progress mb-1" style="height: 6px;">
            <div id="card-pct-swakelola-progress" class="progress-bar" role="progressbar" style="width: 0%; background-color: #f59e0b;"></div>
          </div>
          <div class="d-flex justify-content-between small">
            <span class="text-muted" id="card-pct-swakelola-count">0 paket</span>
            <span class="fw-bold" style="color: #f59e0b;" id="card-pct-swakelola-pct">0%</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Detail Tiap Kanal: Metode & Jenis Pengadaan -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-header bg-white border-bottom pb-3 pt-3">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
              <h5 class="fw-bold mb-1 text-primary">
                <i class="bx bx-layer me-2"></i>Rincian Pengadaan per Kanal (Metode & Jenis Pengadaan)
              </h5>
              <p class="text-muted small mb-0">Rincian komparasi pagu, realisasi riil, efisiensi anggaran, dan penyerapan untuk setiap metode pemilihan dan jenis pengadaan</p>
            </div>
            <div>
              <span class="badge bg-label-info px-3 py-2 fw-semibold" id="kanal-filter-badge">Semua Tahun</span>
            </div>
          </div>
          
          <!-- Nav Tabs Kanal -->
          <ul class="nav nav-pills mt-3 flex-wrap gap-1" id="kanalTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active py-2 px-3 fw-semibold" id="tab-btn-all" data-bs-toggle="pill" data-bs-target="#tab-pane-all" type="button" role="tab">
                <i class="bx bx-grid-alt me-1"></i> Semua Kanal
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link py-2 px-3 fw-semibold" id="tab-btn-tender" data-bs-toggle="pill" data-bs-target="#tab-pane-tender" type="button" role="tab" style="color: #2563eb;">
                <i class="bx bx-clipboard me-1"></i> Tender
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link py-2 px-3 fw-semibold text-teal" id="tab-btn-nontender" data-bs-toggle="pill" data-bs-target="#tab-pane-nontender" type="button" role="tab">
                <i class="bx bx-file me-1"></i> Non Tender
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link py-2 px-3 fw-semibold text-success" id="tab-btn-epurchasing" data-bs-toggle="pill" data-bs-target="#tab-pane-epurchasing" type="button" role="tab">
                <i class="bx bx-cart me-1"></i> E-Purchasing
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link py-2 px-3 fw-semibold text-purple" id="tab-btn-pct-nontender" data-bs-toggle="pill" data-bs-target="#tab-pane-pct-nontender" type="button" role="tab">
                <i class="bx bx-receipt me-1"></i> Pencatatan Non Tender
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link py-2 px-3 fw-semibold text-warning" id="tab-btn-pct-swakelola" data-bs-toggle="pill" data-bs-target="#tab-pane-pct-swakelola" type="button" role="tab">
                <i class="bx bx-building-house me-1"></i> Pencatatan Swakelola
              </button>
            </li>
          </ul>
        </div>

        <div class="card-body pt-3 pb-3">
          <div class="tab-content p-0" id="kanalTabContent">
            <!-- Tab Pane: Semua Kanal (Overview Komparasi) -->
            <div class="tab-pane fade show active" id="tab-pane-all" role="tabpanel">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="table-overview-kanal">
                  <thead class="table-light">
                    <tr>
                      <th class="fw-bold">Kanal Pengadaan</th>
                      <th class="fw-bold text-center">Jumlah Paket</th>
                      <th class="fw-bold text-end">Total Pagu (Rp)</th>
                      <th class="fw-bold text-end">Realisasi (Rp)</th>
                      <th class="fw-bold text-end">Efisiensi (Rp)</th>
                      <th class="fw-bold text-center" style="width: 180px;">Tingkat Penyerapan</th>
                      <th class="fw-bold text-center" style="width: 130px;">Aksi</th>
                    </tr>
                  </thead>
                  <tbody id="tbody-overview-kanal">
                    <!-- Populated via JS -->
                  </tbody>
                  <tfoot class="table-light fw-bold" id="tfoot-overview-kanal">
                    <!-- Populated via JS -->
                  </tfoot>
                </table>
              </div>
            </div>

            <!-- Tab Panes for Specific Kanals -->
            <!-- Tender -->
            <div class="tab-pane fade" id="tab-pane-tender" role="tabpanel">
              <div id="kanal-content-tender"></div>
            </div>
            <!-- Non Tender -->
            <div class="tab-pane fade" id="tab-pane-nontender" role="tabpanel">
              <div id="kanal-content-nontender"></div>
            </div>
            <!-- E-Purchasing -->
            <div class="tab-pane fade" id="tab-pane-epurchasing" role="tabpanel">
              <div id="kanal-content-epurchasing"></div>
            </div>
            <!-- Pencatatan Non Tender -->
            <div class="tab-pane fade" id="tab-pane-pct-nontender" role="tabpanel">
              <div id="kanal-content-pct-nontender"></div>
            </div>
            <!-- Pencatatan Swakelola -->
            <div class="tab-pane fade" id="tab-pane-pct-swakelola" role="tabpanel">
              <div id="kanal-content-pct-swakelola"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Charts Row -->
  <div class="row g-4 mb-4">
    <!-- Bar Chart: Realisasi per Jenis Pengadaan -->
    <div class="col-12 col-lg-8">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-transparent border-bottom-0 pb-0">
          <h6 class="fw-bold mb-0">Total Realisasi per Jenis Pengadaan</h6>
          <small class="text-muted">Nilai kontrak/pembayaran yang sudah terealisasi</small>
        </div>
        <div class="card-body pt-2">
          <canvas id="chart-bar" style="max-height: 320px;"></canvas>
        </div>
      </div>
    </div>

    <!-- Doughnut Chart: Proporsi Pagu -->
    <div class="col-12 col-lg-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-transparent border-bottom-0 pb-0">
          <h6 class="fw-bold mb-0">Proporsi Anggaran (Pagu)</h6>
          <small class="text-muted">Distribusi total pagu berdasarkan metode</small>
        </div>
        <div class="card-body pt-2 d-flex flex-column align-items-center justify-content-center">
          <canvas id="chart-doughnut" style="max-height: 260px; max-width: 260px;"></canvas>
          <div id="doughnut-legend" class="mt-3 w-100"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Quarterly Trend Chart: Realisasi per Triwulan -->
  <div class="row g-4 mb-4">
    <div class="col-12">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-bottom-0 pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div>
            <h6 class="fw-bold mb-0">Tren Penyerapan Realisasi per Triwulan (Q1 - Q4)</h6>
            <small class="text-muted">Distribusi realisasi pengadaan per kuartal (Triwulan I - IV) berdasarkan metode pemilihan</small>
          </div>
          <span class="badge bg-label-primary px-3 py-2 fw-semibold" id="quarter-chart-badge">Tahun: Semua Tahun</span>
        </div>
        <div class="card-body pt-2">
          <canvas id="chart-quarter" style="max-height: 320px;"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Comparison Chart: Pagu vs Realisasi -->
  <div class="row g-4">
    <div class="col-12">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-bottom-0 pb-0">
          <h6 class="fw-bold mb-0">Perbandingan Pagu vs Realisasi (Progres Penyerapan)</h6>
          <small class="text-muted">Bar: Pagu (Anggaran), Line: Realisasi (Penyerapan)</small>
        </div>
        <div class="card-body pt-2">
          <canvas id="chart-comparison" style="max-height: 350px;"></canvas>
        </div>
      </div>
    </div>
  </div>

</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
const API_URL = '<?= base_url('dashboard/getDashboardLpseData') ?>';
const COLORS = {
  'Tender':               { border: '#2563eb', bg: '#2563eb' },
  'Non Tender':           { border: '#0d9488', bg: '#0d9488' },
  'E-Purchasing':         { border: '#10b981', bg: '#10b981' },
  'Pencatatan Non Tender':{ border: '#7c3aed', bg: '#7c3aed' },
  'Pencatatan Swakelola': { border: '#f59e0b', bg: '#f59e0b' },
};

const JENIS_PALETTE = [
  { border: '#2563eb', bg: '#2563eb' },
  { border: '#0d9488', bg: '#0d9488' },
  { border: '#7c3aed', bg: '#7c3aed' },
  { border: '#f59e0b', bg: '#f59e0b' },
  { border: '#f43f5e', bg: '#f43f5e' },
  { border: '#6366f1', bg: '#6366f1' },
  { border: '#ec4899', bg: '#ec4899' },
  { border: '#10b981', bg: '#10b981' },
];
function getJenisColor(idx) {
  return JENIS_PALETTE[idx % JENIS_PALETTE.length];
}

const fmt = (v) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v || 0);
const fmtShort = (v) => {
  if (v >= 1e12) return 'Rp ' + (v/1e12).toFixed(1) + 'T';
  if (v >= 1e9)  return 'Rp ' + (v/1e9).toFixed(1) + 'M';
  if (v >= 1e6)  return 'Rp ' + (v/1e6).toFixed(1) + 'Jt';
  return fmt(v);
};

const hexToRgba = (hex, alpha) => {
  const r = parseInt(hex.slice(1, 3), 16);
  const g = parseInt(hex.slice(3, 5), 16);
  const b = parseInt(hex.slice(5, 7), 16);
  return `rgba(${r}, ${g}, ${b}, ${alpha})`;
};

let chartBar, chartDoughnut, chartComparison, chartQuarter;

function destroyCharts() {
  [chartBar, chartDoughnut, chartComparison, chartQuarter].forEach(c => c && c.destroy());
}

async function exportToPDF() {
  const element = document.getElementById('dashboard-content');
  const year = document.getElementById('year-filter').value;
  const quarter = document.getElementById('quarter-filter').value;
  const qSuffix = quarter !== 'all' ? `_Q${quarter}` : '';
  
  // Custom styling for export
  const uiElements = element.querySelectorAll('.btn, #year-filter, #quarter-filter, .card-header select');
  uiElements.forEach(el => el.style.display = 'none');
  
  const opt = {
    margin:       [10, 5, 10, 5], // [top, left, bottom, right] - shift left slightly
    filename:     `Dashboard_LPSE_${year}${qSuffix}.pdf`,
    image:        { type: 'jpeg', quality: 1 },
    html2canvas:  { 
      scale: 2, 
      useCORS: true, 
      logging: false,
      letterRendering: true,
      windowWidth: 1500, // Balanced width
      x: 0,
      y: 0,
      scrollX: 0,
      scrollY: 0
    },
    jsPDF:        { unit: 'mm', format: 'a3', orientation: 'landscape' },
    pagebreak:    { mode: ['avoid-all'] }
  };

  try {
    window.scrollTo(0, 0);
    // Add temporary styling
    const originalStyle = element.style.cssText;
    element.style.padding = '15px';
    element.style.background = '#ffffff';
    element.style.width = '1400px'; 
    
    await html2pdf().set(opt).from(element).save();
    
    element.style.cssText = originalStyle;
  } catch (e) {
    console.error('PDF Export Error:', e);
  } finally {
    uiElements.forEach(el => el.style.display = '');
  }
}

function buildCharts(data) {
  destroyCharts();

  const labels   = data.labels;
  const years    = data.years;
  const byYear   = data.by_year;
  const totals   = data.total_pagu;
  const totalReal = data.total_realisasi;
  const jenisLabels = data.jenis_labels || [];
  const byJenis    = data.by_jenis || {};

  // --- Update Cards ---
  const cardMap = {
    'Tender':               ['card-tender-pagu', 'card-tender-realisasi', 'card-tender-count', 'card-tender-progress', 'card-tender-pct', 'card-tender-efisiensi'],
    'Non Tender':           ['card-nontender-pagu', 'card-nontender-realisasi', 'card-nontender-count', 'card-nontender-progress', 'card-nontender-pct', 'card-nontender-efisiensi'],
    'E-Purchasing':         ['card-epurchasing-pagu', 'card-epurchasing-realisasi', 'card-epurchasing-count', 'card-epurchasing-progress', 'card-epurchasing-pct', 'card-epurchasing-efisiensi'],
    'Pencatatan Non Tender':['card-pct-nontender-pagu', 'card-pct-nontender-realisasi', 'card-pct-nontender-count', 'card-pct-nontender-progress', 'card-pct-nontender-pct', 'card-pct-nontender-efisiensi'],
    'Pencatatan Swakelola': ['card-pct-swakelola-pagu', 'card-pct-swakelola-realisasi', 'card-pct-swakelola-count', 'card-pct-swakelola-progress', 'card-pct-swakelola-pct', 'card-pct-swakelola-efisiensi'],
  };

  // --- Global Totals ---
  let allPagu = 0, allReal = 0;
  labels.forEach(lbl => {
    allPagu += totals[lbl] || 0;
    allReal += totalReal[lbl] || 0;
  });
  const allEf = Math.max(0, allPagu - allReal);
  const allEfPct = allPagu > 0 ? ((allEf / allPagu) * 100).toFixed(1) : 0;

  document.getElementById('total-all-pagu').textContent = fmt(allPagu);
  document.getElementById('total-all-realisasi').textContent = fmt(allReal);
  document.getElementById('total-all-efisiensi').textContent = fmt(allEf);
  document.getElementById('total-all-pct').textContent = allEfPct + '%';

  labels.forEach(lbl => {
    const ids = cardMap[lbl];
    if (!ids) return;

    const [paguId, realId, countId, progId, pctId, efId] = ids;
    const pagu = totals[lbl] || 0;
    const real = totalReal[lbl] || 0;
    const ef   = Math.max(0, pagu - real);
    const pct  = pagu > 0 ? ((real / pagu) * 100).toFixed(1) : 0;
    const efPct = pagu > 0 ? ((ef / pagu) * 100).toFixed(1) : 0;

    document.getElementById(paguId).textContent = fmt(pagu);
    document.getElementById(realId).textContent = fmt(real);
    document.getElementById(efId).textContent   = fmt(ef) + ' (' + efPct + '%)';
    document.getElementById(pctId).textContent = pct + '%';
    document.getElementById(progId).style.width = Math.min(pct, 100) + '%';
    
    let totalCount = 0;
    if (byYear[lbl]) Object.values(byYear[lbl]).forEach(v => totalCount += v.count);
    document.getElementById(countId).textContent = totalCount.toLocaleString('id-ID') + ' paket';
  });

  // --- Render Detail Tiap Kanal (Metode & Jenis Pengadaan) ---
  renderKanalDetails(data.by_kanal);

  // --- Bar Chart: Realisasi per Jenis Pengadaan ---
  chartBar = new Chart(document.getElementById('chart-bar'), {
    type: 'bar',
    data: {
      labels: years,
      datasets: jenisLabels.map((jenis, idx) => ({
        label: jenis,
        data: years.map(yr => byJenis[jenis]?.[yr]?.realisasi || 0),
        backgroundColor: getJenisColor(idx).bg,
        borderColor: getJenisColor(idx).border,
        borderWidth: 2,
        borderRadius: 6,
      }))
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'top' },
        tooltip: { callbacks: { label: ctx => ` ${ctx.dataset.label}: ${fmt(ctx.raw)}` } }
      },
      scales: {
        y: { ticks: { callback: v => fmtShort(v) }, grid: { color: 'rgba(0,0,0,0.05)' } },
        x: { grid: { display: false } }
      }
    }
  });

  // --- Doughnut Chart (Pagu Proporsi) ---
  const doughnutValues = labels.map(lbl => totals[lbl] || 0);
  chartDoughnut = new Chart(document.getElementById('chart-doughnut'), {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{
        data: doughnutValues,
        backgroundColor: labels.map(l => COLORS[l]?.border),
        hoverOffset: 8,
        borderWidth: 2,
      }]
    },
    options: {
      responsive: true,
      cutout: '65%',
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${fmt(ctx.raw)}` } }
      }
    }
  });

  // Custom legend
  const totalAllPagu = doughnutValues.reduce((a, b) => a + b, 0);
  document.getElementById('doughnut-legend').innerHTML = labels.map((lbl, i) => {
    const pct = totalAllPagu > 0 ? ((doughnutValues[i] / totalAllPagu) * 100).toFixed(1) : 0;
    return `<div class="d-flex justify-content-between align-items-center mb-1 small">
      <span><span class="badge me-1" style="background:${COLORS[lbl]?.border}">&nbsp;</span>${lbl}</span>
      <span class="fw-bold">${fmt(doughnutValues[i])} (${pct}%)</span>
    </div>`;
  }).join('');

  // --- Comparison Chart: Pagu (Bar) vs Realisasi (Line/Point) ---
  const comparisonDatasets = [];
  labels.forEach(lbl => {
    // Dataset 1: Realisasi (Solid)
    comparisonDatasets.push({
      type: 'bar',
      label: `Realisasi ${lbl}`,
      data: years.map(yr => byYear[lbl]?.[yr]?.realisasi || 0),
      backgroundColor: COLORS[lbl]?.border,
      borderColor: COLORS[lbl]?.border,
      borderWidth: 1,
      stack: lbl, // Group by category
      order: 1
    });

    // Dataset 2: Sisa Pagu (Transparent)
    comparisonDatasets.push({
      type: 'bar',
      label: `Sisa Pagu ${lbl}`,
      data: years.map(yr => {
        const pagu = byYear[lbl]?.[yr]?.pagu || 0;
        const real = byYear[lbl]?.[yr]?.realisasi || 0;
        return Math.max(0, pagu - real); // Only show positive remainder
      }),
      backgroundColor: hexToRgba(COLORS[lbl]?.border, 0.15),
      borderColor: COLORS[lbl]?.border,
      borderWidth: 1,
      stack: lbl, // Group by category
      order: 2
    });
  });

  chartComparison = new Chart(document.getElementById('chart-comparison'), {
    data: {
      labels: years,
      datasets: comparisonDatasets
    },
    plugins: [{
      id: 'efficiencyLabels',
      afterDatasetsDraw(chart) {
        const { ctx, scales: { x, y } } = chart;
        chart.data.datasets.forEach((dataset, i) => {
          // Only process the "Sisa Pagu" datasets (even indices in our array: 1, 3, 5, 7)
          if (!dataset.label.includes('Sisa')) return;
          
          const meta = chart.getDatasetMeta(i);
          meta.data.forEach((bar, index) => {
            const yr = chart.data.labels[index];
            const lbl = dataset.stack;
            const dataObj = byYear[lbl]?.[yr];
            if (!dataObj || dataObj.pagu <= 0) return;

            const efPct = (((dataObj.pagu - dataObj.realisasi) / dataObj.pagu) * 100).toFixed(1);
            if (efPct <= 0) return;

            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'bottom';
            ctx.font = 'bold 10px sans-serif';
            ctx.fillStyle = COLORS[lbl]?.border;
            // Position: top of the bar
            ctx.fillText(efPct + '% Efisiensi', bar.x, bar.y - 5);
            ctx.restore();
          });
        });
      }
    }],
    options: {
      responsive: true,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15 } },
        tooltip: { 
          callbacks: { 
            label: ctx => {
              if (ctx.dataset.label.includes('Sisa')) {
                const yr = ctx.label;
                const lbl = ctx.dataset.stack;
                const pagu = byYear[lbl]?.[yr]?.pagu || 0;
                const real = byYear[lbl]?.[yr]?.realisasi || 0;
                const ef = Math.max(0, pagu - real);
                const efPct = pagu > 0 ? ((ef / pagu) * 100).toFixed(1) : 0;
                return [
                  ` Total Pagu ${lbl}: ${fmt(pagu)}`,
                  ` Efisiensi: ${fmt(ef)} (${efPct}%)`
                ];
              }
              return ` ${ctx.dataset.label}: ${fmt(ctx.raw)}`;
            }
          } 
        }
      },
      scales: {
        y: { stacked: true, ticks: { callback: v => fmtShort(v) }, grid: { color: 'rgba(0,0,0,0.05)' } },
        x: { stacked: true, grid: { display: false } }
      }
    }
  });

  // --- Quarterly Trend Chart: Realisasi per Triwulan ---
  const byQuarter = data.by_quarter || {};
  const quarterKeys = [1, 2, 3, 4];
  const quarterNames = ['Triwulan I (Jan-Mar)', 'Triwulan II (Apr-Jun)', 'Triwulan III (Jul-Sep)', 'Triwulan IV (Okt-Des)'];
  
  if (byQuarter['undated'] && byQuarter['undated'].total > 0) {
    quarterKeys.push('undated');
    quarterNames.push('Tanpa Tanggal');
  }

  const quarterDatasets = labels.map(lbl => ({
    label: lbl,
    data: quarterKeys.map(k => byQuarter[k]?.by_source?.[lbl] || 0),
    borderColor: COLORS[lbl]?.border,
    backgroundColor: COLORS[lbl]?.border,
    borderWidth: 2.5,
    tension: 0.35,
    pointRadius: 4,
    pointHoverRadius: 6,
    fill: false
  }));

  quarterDatasets.unshift({
    label: 'Total Semua Metode',
    data: quarterKeys.map(k => byQuarter[k]?.total || 0),
    borderColor: '#0f172a',
    backgroundColor: '#0f172a',
    borderWidth: 3,
    borderDash: [5, 5],
    tension: 0.35,
    pointRadius: 5,
    pointHoverRadius: 7,
    fill: false
  });

  chartQuarter = new Chart(document.getElementById('chart-quarter'), {
    type: 'line',
    data: {
      labels: quarterNames,
      datasets: quarterDatasets
    },
    options: {
      responsive: true,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'top', labels: { boxWidth: 14, padding: 15, usePointStyle: true } },
        tooltip: {
          callbacks: {
            label: ctx => ` ${ctx.dataset.label}: ${fmt(ctx.raw)}`
          }
        }
      },
      scales: {
        x: { grid: { color: 'rgba(0,0,0,0.03)' } },
        y: { ticks: { callback: v => fmtShort(v) }, grid: { color: 'rgba(0,0,0,0.05)' } }
      }
    }
  });

  const curYear = document.getElementById('year-filter').value;
  const curQ = document.getElementById('quarter-filter').value;
  const qDesc = curQ === 'all' ? 'Semua Triwulan' : `Triwulan ${curQ}`;
  document.getElementById('quarter-chart-badge').textContent = `Tahun: ${curYear === 'all' ? 'Semua Tahun' : curYear} | Filter: ${qDesc}`;
}

function renderKanalDetails(byKanal) {
  if (!byKanal) return;

  const kanalMeta = {
    'Tender': {
      icon: 'bx bx-clipboard',
      color: '#2563eb',
      bgClass: 'bg-label-primary',
      url: '<?= base_url('dashboard/lpse/tender') ?>',
      containerId: 'kanal-content-tender'
    },
    'Non Tender': {
      icon: 'bx bx-file',
      color: '#0d9488',
      bgClass: 'bg-label-info',
      url: '<?= base_url('dashboard/lpse/nontender') ?>',
      containerId: 'kanal-content-nontender'
    },
    'E-Purchasing': {
      icon: 'bx bx-cart',
      color: '#10b981',
      bgClass: 'bg-label-success',
      url: '<?= base_url('dashboard/table-epurchasing') ?>',
      containerId: 'kanal-content-epurchasing'
    },
    'Pencatatan Non Tender': {
      icon: 'bx bx-receipt',
      color: '#7c3aed',
      bgClass: 'bg-label-secondary',
      url: '<?= base_url('dashboard/lpse/pencatatan-nontender') ?>',
      containerId: 'kanal-content-pct-nontender'
    },
    'Pencatatan Swakelola': {
      icon: 'bx bx-building-house',
      color: '#f59e0b',
      bgClass: 'bg-label-warning',
      url: '<?= base_url('dashboard/lpse/pencatatan-swakelola') ?>',
      containerId: 'kanal-content-pct-swakelola'
    }
  };

  // 1. Render Tab Overview (Semua Kanal)
  let tbodyHtml = '';
  let totPkg = 0, totPagu = 0, totReal = 0, totEf = 0;

  Object.keys(kanalMeta).forEach(lbl => {
    const kData = byKanal[lbl] || { total: { pagu: 0, realisasi: 0, efisiensi: 0, count: 0, pct: 0, ef_pct: 0 }, metode: {}, jenis: {} };
    const tot = kData.total || { pagu: 0, realisasi: 0, efisiensi: 0, count: 0, pct: 0, ef_pct: 0 };
    totPkg += tot.count || 0;
    totPagu += tot.pagu || 0;
    totReal += tot.realisasi || 0;
    totEf += tot.efisiensi || 0;

    const meta = kanalMeta[lbl];
    const pct = tot.pct || 0;
    const efPct = tot.ef_pct || 0;
    const metodeCount = Object.keys(kData.metode || {}).length;
    const jenisCount = Object.keys(kData.jenis || {}).length;

    tbodyHtml += `
      <tr>
        <td>
          <div class="d-flex align-items-center">
            <div class="avatar avatar-sm rounded me-2 ${meta.bgClass}">
              <i class="${meta.icon} fs-5"></i>
            </div>
            <div>
              <span class="fw-bold">${lbl}</span>
              <small class="text-muted d-block">${metodeCount} metode pemilihan, ${jenisCount} jenis pengadaan</small>
            </div>
          </div>
        </td>
        <td class="text-center fw-semibold">${(tot.count || 0).toLocaleString('id-ID')}</td>
        <td class="text-end fw-semibold">${fmt(tot.pagu)}</td>
        <td class="text-end fw-bold" style="color: ${meta.color};">${fmt(tot.realisasi)}</td>
        <td class="text-end fw-semibold text-success">${fmt(tot.efisiensi)} <small class="text-muted">(${efPct}%)</small></td>
        <td>
          <div class="d-flex align-items-center justify-content-between small mb-1">
            <span class="fw-semibold">${pct}%</span>
          </div>
          <div class="progress" style="height: 6px;">
            <div class="progress-bar" role="progressbar" style="width: ${Math.min(pct, 100)}%; background-color: ${meta.color};"></div>
          </div>
        </td>
        <td class="text-center">
          <a href="${meta.url}" target="_blank" class="btn btn-xs btn-outline-primary" title="Buka Data Tabel">
            <i class="bx bx-table me-1"></i> Buka Tabel
          </a>
        </td>
      </tr>
    `;
  });

  const totPct = totPagu > 0 ? ((totReal / totPagu) * 100).toFixed(1) : 0;
  const totEfPct = totPagu > 0 ? ((totEf / totPagu) * 100).toFixed(1) : 0;

  const tbodyEl = document.getElementById('tbody-overview-kanal');
  if (tbodyEl) tbodyEl.innerHTML = tbodyHtml;

  const tfootEl = document.getElementById('tfoot-overview-kanal');
  if (tfootEl) {
    tfootEl.innerHTML = `
      <tr>
        <td>TOTAL KESELURUHAN</td>
        <td class="text-center">${totPkg.toLocaleString('id-ID')}</td>
        <td class="text-end">${fmt(totPagu)}</td>
        <td class="text-end text-primary">${fmt(totReal)}</td>
        <td class="text-end text-success">${fmt(totEf)} <small class="text-muted">(${totEfPct}%)</small></td>
        <td>
          <div class="d-flex align-items-center justify-content-between small mb-1">
            <span class="fw-semibold">${totPct}%</span>
          </div>
          <div class="progress" style="height: 6px;">
            <div class="progress-bar bg-primary" role="progressbar" style="width: ${Math.min(totPct, 100)}%;"></div>
          </div>
        </td>
        <td></td>
      </tr>
    `;
  }

  // 2. Render Each Individual Kanal Tab Pane
  Object.keys(kanalMeta).forEach(lbl => {
    const meta = kanalMeta[lbl];
    const kData = byKanal[lbl] || { total: {}, metode: {}, jenis: {} };
    const tot = kData.total || { pagu: 0, realisasi: 0, efisiensi: 0, count: 0, pct: 0, ef_pct: 0 };
    const metodes = kData.metode || {};
    const jenisMap = kData.jenis || {};

    let html = `
      <!-- Kanal Header Banner -->
      <div class="d-flex justify-content-between align-items-center p-3 mb-3 rounded flex-wrap gap-2" style="background-color: rgba(30, 41, 59, 0.03); border-left: 4px solid ${meta.color};">
        <div>
          <h6 class="fw-bold mb-1" style="color: ${meta.color};">
            <i class="${meta.icon} me-1"></i> Rincian Kanal ${lbl}
          </h6>
          <div class="d-flex flex-wrap gap-3 mt-1 small">
            <span>Pagu: <strong>${fmt(tot.pagu)}</strong></span>
            <span>Realisasi: <strong style="color: ${meta.color};">${fmt(tot.realisasi)}</strong></span>
            <span class="text-success">Efisiensi: <strong>${fmt(tot.efisiensi)} (${tot.ef_pct || 0}%)</strong></span>
            <span>Penyerapan: <strong>${tot.pct || 0}%</strong> (${(tot.count || 0).toLocaleString('id-ID')} paket)</span>
          </div>
        </div>
        <div>
          <a href="${meta.url}" target="_blank" class="btn btn-sm btn-outline-primary">
            <i class="bx bx-table me-1"></i> Buka Data Tabel ${lbl}
          </a>
        </div>
      </div>

      <div class="row g-3">
        <!-- Kolom Metode Pemilihan -->
        <div class="col-12 col-xl-6">
          <div class="card border shadow-none h-100">
            <div class="card-header py-2 bg-light border-bottom d-flex justify-content-between align-items-center">
              <span class="fw-bold small text-uppercase"><i class="bx bx-check-shield me-1"></i> Metode Pemilihan</span>
              <span class="badge bg-white text-dark border">${Object.keys(metodes).length} Metode</span>
            </div>
            <div class="table-responsive">
              <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.825rem;">
                <thead class="table-light">
                  <tr>
                    <th>Metode Pemilihan</th>
                    <th class="text-center">Paket</th>
                    <th class="text-end">Pagu (Rp)</th>
                    <th class="text-end">Realisasi (Rp)</th>
                    <th class="text-center" style="width: 100px;">Penyerapan</th>
                  </tr>
                </thead>
                <tbody>
    `;

    const mKeys = Object.keys(metodes);
    if (mKeys.length === 0) {
      html += `<tr><td colspan="5" class="text-center text-muted py-3">Tidak ada data metode</td></tr>`;
    } else {
      mKeys.forEach(mk => {
        const mv = metodes[mk];
        html += `
          <tr>
            <td class="fw-semibold">${mk}</td>
            <td class="text-center">${mv.count}</td>
            <td class="text-end">${fmt(mv.pagu)}</td>
            <td class="text-end fw-bold" style="color: ${meta.color};">${fmt(mv.realisasi)}</td>
            <td>
              <div class="d-flex justify-content-between small" style="font-size: 0.75rem;">
                <span>${mv.pct}%</span>
              </div>
              <div class="progress" style="height: 4px;">
                <div class="progress-bar" style="width: ${Math.min(mv.pct, 100)}%; background-color: ${meta.color};"></div>
              </div>
            </td>
          </tr>
        `;
      });
    }

    html += `
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Kolom Jenis Pengadaan -->
        <div class="col-12 col-xl-6">
          <div class="card border shadow-none h-100">
            <div class="card-header py-2 bg-light border-bottom d-flex justify-content-between align-items-center">
              <span class="fw-bold small text-uppercase"><i class="bx bx-category me-1"></i> Jenis Pengadaan</span>
              <span class="badge bg-white text-dark border">${Object.keys(jenisMap).length} Jenis</span>
            </div>
            <div class="table-responsive">
              <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.825rem;">
                <thead class="table-light">
                  <tr>
                    <th>Jenis Pengadaan</th>
                    <th class="text-center">Paket</th>
                    <th class="text-end">Pagu (Rp)</th>
                    <th class="text-end">Realisasi (Rp)</th>
                    <th class="text-center" style="width: 100px;">Penyerapan</th>
                  </tr>
                </thead>
                <tbody>
    `;

    const jKeys = Object.keys(jenisMap);
    if (jKeys.length === 0) {
      html += `<tr><td colspan="5" class="text-center text-muted py-3">Tidak ada data jenis pengadaan</td></tr>`;
    } else {
      jKeys.forEach(jk => {
        const jv = jenisMap[jk];
        html += `
          <tr>
            <td class="fw-semibold">${jk}</td>
            <td class="text-center">${jv.count}</td>
            <td class="text-end">${fmt(jv.pagu)}</td>
            <td class="text-end fw-bold" style="color: ${meta.color};">${fmt(jv.realisasi)}</td>
            <td>
              <div class="d-flex justify-content-between small" style="font-size: 0.75rem;">
                <span>${jv.pct}%</span>
              </div>
              <div class="progress" style="height: 4px;">
                <div class="progress-bar" style="width: ${Math.min(jv.pct, 100)}%; background-color: ${meta.color};"></div>
              </div>
            </td>
          </tr>
        `;
      });
    }

    html += `
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    `;

    const container = document.getElementById(meta.containerId);
    if (container) {
      container.innerHTML = html;
    }
  });

  // Update badge filter year/quarter
  const curYr = document.getElementById('year-filter').value;
  const curQ = document.getElementById('quarter-filter').value;
  const qStr = curQ === 'all' ? 'Semua Triwulan' : `Triwulan ${curQ}`;
  const yrStr = curYr === 'all' ? 'Semua Tahun' : curYr;
  const kanalBadge = document.getElementById('kanal-filter-badge');
  if (kanalBadge) {
    kanalBadge.textContent = `Filter: ${yrStr} | ${qStr}`;
  }
}

async function loadData(year = null, quarter = null) {
  if (year === null) year = document.getElementById('year-filter').value || 'all';
  if (quarter === null) quarter = document.getElementById('quarter-filter').value || 'all';

  ['chart-bar','chart-doughnut','chart-comparison','chart-quarter'].forEach(id => {
    const canvas = document.getElementById(id);
    if (canvas) canvas.style.opacity = '0.4';
  });

  // Show stylish SweetAlert loading
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: 'Memproses Data...',
      html: 'Menarik dan menyaring data pengadaan terbaru.',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });
  }

  try {
    const res = await fetch(`${API_URL}?year=${year}&quarter=${quarter}`);
    const data = await res.json();

    const sel = document.getElementById('year-filter');
    const existingYears = [...sel.options].map(o => o.value).filter(v => v !== 'all');
    (data.years || []).forEach(yr => {
      if (!existingYears.includes(yr)) {
        const opt = document.createElement('option');
        opt.value = yr; opt.text = yr;
        sel.appendChild(opt);
      }
    });
    if (year !== 'all') sel.value = year;

    buildCharts(data);
  } catch(e) {
    console.error(e);
  } finally {
    ['chart-bar','chart-doughnut','chart-comparison','chart-quarter'].forEach(id => {
      const canvas = document.getElementById(id);
      if (canvas) canvas.style.opacity = '1';
    });
    
    // Close loading
    if (typeof Swal !== 'undefined') {
      Swal.close();
    }
  }
}

document.addEventListener('DOMContentLoaded', () => {
  loadData('all', 'all');
  document.getElementById('year-filter').addEventListener('change', function() {
    loadData(this.value, document.getElementById('quarter-filter').value);
  });
  document.getElementById('quarter-filter').addEventListener('change', function() {
    loadData(document.getElementById('year-filter').value, this.value);
  });
});
</script>
<?= $this->endSection() ?>
