<?= $this->extend('layout/mobile_main') ?>

<?= $this->section('styles') ?>
<style>
    /* Top Header */
    .app-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
        background: #ffffff;
        border-bottom: 1px solid #f1f5f9;
        padding-top: max(15px, env(safe-area-inset-top));
    }
    .app-logo {
        font-size: 22px;
        font-weight: 800;
        color: #0ea5e9;
        display: flex;
        align-items: center;
    }
    .app-logo span {
        color: #ef4444; /* Red part */
    }
    .header-actions {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .header-actions i {
        font-size: 22px;
        color: #64748b;
    }
    .header-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
    }

    /* Main Content Wrapper */
    .main-content {
        padding: 20px;
        background-color: #ffffff;
        min-height: calc(100vh - 150px);
    }

    /* Breadcrumbs */
    .breadcrumbs {
        font-size: 12px;
        color: #94a3b8;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
    }
    .breadcrumbs strong {
        color: #334155;
        font-weight: 500;
        margin-right: 10px;
    }
    .breadcrumbs .separator {
        margin: 0 10px;
        color: #cbd5e1;
    }

    /* Search Bar & View Toggle */
    .toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        gap: 10px;
    }
    .search-box {
        flex: 1;
        display: flex;
        align-items: center;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 20px;
        padding: 8px 15px;
    }
    .search-box input {
        border: none;
        background: transparent;
        outline: none;
        font-size: 13px;
        width: 100%;
        color: #334155;
    }
    .search-box i {
        color: #94a3b8;
        font-size: 16px;
    }
    
    .view-toggles {
        display: flex;
        gap: 5px;
    }
    .toggle-btn {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 18px;
        cursor: pointer;
    }
    .toggle-btn.active {
        background-color: #0ea5e9;
        color: white;
    }
    .toggle-btn.inactive {
        background-color: #f1f5f9;
        color: #0ea5e9;
    }

    /* Card Grid System */
    .service-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        padding-bottom: 30px;
    }
    
    .service-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 20px 10px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all 0.2s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .service-card:active {
        transform: scale(0.98);
        background-color: #f8fafc;
    }

    .icon-container {
        position: relative;
        width: 60px;
        height: 60px;
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 12px;
    }
    
    /* Background blob for icons */
    .icon-bg-blob {
        position: absolute;
        width: 45px;
        height: 45px;
        background-color: #ffe4e6; /* Soft pink default */
        border-radius: 50%;
        top: 0;
        right: 0;
        z-index: 1;
    }
    .icon-bg-blue { background-color: #e0f2fe; }
    .icon-bg-green { background-color: #dcfce3; }
    
    .icon-element {
        position: relative;
        z-index: 2;
        font-size: 32px;
        color: #0284c7; /* Deep blue */
    }
    .icon-element.red { color: #e11d48; }

    .service-label {
        font-size: 12px;
        font-weight: 600;
        color: #334155;
        text-align: center;
        line-height: 1.35;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="main-content">
    <!-- Breadcrumbs -->
    <div class="breadcrumbs">
        <strong>Layanan SIDP</strong> <span class="separator">|</span> Home > Layanan
    </div>

    <!-- Toolbar (Search & View Toggle) -->
    <div class="toolbar">
        <div class="search-box">
            <input type="text" placeholder="Cari Layanan SIDP .." id="searchInput">
            <i class='bx bx-search'></i>
        </div>
        <div class="view-toggles">
            <div class="toggle-btn inactive"><i class='bx bx-list-ul'></i></div>
            <div class="toggle-btn active"><i class='bx bx-grid-alt'></i></div>
        </div>
    </div>

    <!-- Service Grid -->
    <div class="service-grid">
        
        <?php if(has_permission('dashboard')): ?>
        <a href="<?= base_url('dashboard?force_desktop=1') ?>" class="service-card menu-searchable">
            <div class="icon-container">
                <div class="icon-bg-blob"></div>
                <i class='bx bxs-bar-chart-alt-2 icon-element'></i>
            </div>
            <span class="service-label">Statistik<br>Dasbor</span>
        </a>
        <?php endif; ?>

        <?php if(has_permission('penyedia')): ?>
        <a href="<?= base_url('dashboard/penyedia') ?>" class="service-card menu-searchable">
            <div class="icon-container">
                <div class="icon-bg-blob icon-bg-blue"></div>
                <i class='bx bxs-store-alt icon-element red'></i>
            </div>
            <span class="service-label">Data<br>Penyedia</span>
        </a>
        <?php endif; ?>

        <?php if(has_permission('swakelola')): ?>
        <a href="<?= base_url('dashboard/swakelola') ?>" class="service-card menu-searchable">
            <div class="icon-container">
                <div class="icon-bg-blob"></div>
                <i class='bx bxs-group icon-element'></i>
            </div>
            <span class="service-label">Data<br>Swakelola</span>
        </a>
        <?php endif; ?>

        <?php if(has_permission('rup-opd')): ?>
        <a href="<?= base_url('dashboard/rup-opd') ?>" class="service-card menu-searchable">
            <div class="icon-container">
                <div class="icon-bg-blob icon-bg-blue"></div>
                <i class='bx bxs-buildings icon-element'></i>
            </div>
            <span class="service-label">Cek RUP<br>OPD</span>
        </a>
        <?php endif; ?>

        <?php if(has_permission('rup_itkp') || has_permission('itkp') || has_permission('dashboard')): ?>
        <a href="<?= base_url('dashboard/rup-itkp') ?>" class="service-card menu-searchable">
            <div class="icon-container">
                <div class="icon-bg-blob icon-bg-blue"></div>
                <i class='bx bxs-spreadsheet icon-element'></i>
            </div>
            <span class="service-label">Data ITKP<br>RUP</span>
        </a>
        <?php endif; ?>

        <?php if(has_permission('master_anggaran')): ?>
        <a href="<?= base_url('master-anggaran') ?>" class="service-card menu-searchable">
            <div class="icon-container">
                <div class="icon-bg-blob icon-bg-green"></div>
                <i class='bx bxs-calculator icon-element'></i>
            </div>
            <span class="service-label">Master<br>Anggaran</span>
        </a>
        <?php endif; ?>

        <?php if(has_permission('dashboard-lpse') || has_permission('lpse_tender')): ?>
        <a href="<?= base_url('dashboard/dashboard-lpse') ?>" class="service-card menu-searchable">
            <div class="icon-container">
                <div class="icon-bg-blob"></div>
                <i class='bx bxs-data icon-element red'></i>
            </div>
            <span class="service-label">Dashboard<br>LPSE</span>
        </a>
        <?php endif; ?>

        <?php if(has_permission('itkp')): ?>
        <a href="<?= base_url('dashboard/itkp') ?>" class="service-card menu-searchable">
            <div class="icon-container">
                <div class="icon-bg-blob icon-bg-blue"></div>
                <i class='bx bxs-layer icon-element'></i>
            </div>
            <span class="service-label">Data<br>ITKP</span>
        </a>
        <?php endif; ?>

        <?php if(has_permission('epurchasing')): ?>
        <a href="<?= base_url('dashboard/epurchasing') ?>" class="service-card menu-searchable">
            <div class="icon-container">
                <div class="icon-bg-blob icon-bg-green"></div>
                <i class='bx bxs-cart icon-element'></i>
            </div>
            <span class="service-label">E-Purchasing<br>Data</span>
        </a>
        <?php endif; ?>

        <?php if(has_permission('monev')): ?>
        <a href="<?= base_url('dashboard/monev-proyek') ?>" class="service-card menu-searchable">
            <div class="icon-container">
                <div class="icon-bg-blob icon-bg-blue"></div>
                <i class='bx bxs-briefcase icon-element red'></i>
            </div>
            <span class="service-label">Monev<br>Proyek</span>
        </a>
        <?php endif; ?>

        <?php if(has_permission('report')): ?>
        <a href="<?= base_url('report') ?>" class="service-card menu-searchable">
            <div class="icon-container">
                <div class="icon-bg-blob icon-bg-green"></div>
                <i class='bx bxs-spreadsheet icon-element'></i>
            </div>
            <span class="service-label">Laporan<br>Realisasi</span>
        </a>
        <?php endif; ?>

        <?php if(has_permission('lpse_tender')): ?>
        <a href="<?= base_url('dashboard/lpse/tender') ?>" class="service-card menu-searchable">
            <div class="icon-container">
                <div class="icon-bg-blob"></div>
                <i class='bx bx-file icon-element'></i>
            </div>
            <span class="service-label">Tender</span>
        </a>
        <?php endif; ?>

        <?php if(has_permission('lpse_nontender')): ?>
        <a href="<?= base_url('dashboard/lpse/nontender') ?>" class="service-card menu-searchable">
            <div class="icon-container">
                <div class="icon-bg-blob icon-bg-blue"></div>
                <i class='bx bx-file-blank icon-element red'></i>
            </div>
            <span class="service-label">Non<br>Tender</span>
        </a>
        <?php endif; ?>

        <?php if(has_permission('lpse_pencatatan_nontender')): ?>
        <a href="<?= base_url('dashboard/lpse/pencatatan-nontender') ?>" class="service-card menu-searchable">
            <div class="icon-container">
                <div class="icon-bg-blob icon-bg-green"></div>
                <i class='bx bx-edit icon-element'></i>
            </div>
            <span class="service-label">Catat Non<br>Tender</span>
        </a>
        <?php endif; ?>

        <?php if(has_permission('lpse_pencatatan_swakelola')): ?>
        <a href="<?= base_url('dashboard/lpse/pencatatan-swakelola') ?>" class="service-card menu-searchable">
            <div class="icon-container">
                <div class="icon-bg-blob"></div>
                <i class='bx bx-notepad icon-element red'></i>
            </div>
            <span class="service-label">Catat<br>Swakelola</span>
        </a>
        <?php endif; ?>

    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Simple filter search for menu
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let items = document.querySelectorAll('.menu-searchable');
        
        items.forEach(function(item) {
            let label = item.querySelector('.service-label').innerText.toLowerCase();
            if (label.includes(filter)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    });

    // Auto-focus search input if requested from URL or hash
    if (window.location.hash === '#search' || new URLSearchParams(window.location.search).has('search')) {
        setTimeout(() => {
            if (searchInput) {
                searchInput.focus();
                searchInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, 300);
    }
</script>
<?= $this->endSection() ?>
