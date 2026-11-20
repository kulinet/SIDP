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
        border: 1px solid #e2e8f0;
    }

    .main-content {
        padding: 15px;
        background-color: #f8fafc;
        min-height: calc(100vh - 150px);
    }

    /* Profile Card */
    .profile-card {
        background: #ffffff;
        border: 1px solid #e0f2fe;
        border-radius: 16px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
        position: relative;
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
    }
    .profile-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background-color: #e0f2fe;
        color: #0284c7;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 24px;
        flex-shrink: 0;
    }
    .profile-info h3 {
        font-size: 12px;
        color: #64748b;
        margin: 0 0 4px 0;
        font-weight: 500;
    }
    .profile-info h2 {
        font-size: 15px;
        color: #0284c7;
        margin: 0 0 4px 0;
        font-weight: 700;
        text-transform: uppercase;
    }
    .profile-info p {
        font-size: 12px;
        color: #94a3b8;
        margin: 0;
    }
    .badge-role {
        position: absolute;
        top: 20px;
        right: 20px;
        background-color: #0284c7;
        color: white;
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 12px;
        font-weight: 600;
    }

    /* Quick Actions (Aksi Cepat) */
    .section-title {
        font-size: 14px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-title i {
        color: #0ea5e9;
        font-size: 18px;
    }
    
    .quick-actions-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: 25px;
    }
    .quick-btn {
        background: #ffffff;
        border-radius: 12px;
        padding: 15px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        border: 1px solid #f1f5f9;
        transition: transform 0.2s;
    }
    .quick-btn:active {
        transform: scale(0.98);
    }
    .quick-btn i {
        font-size: 26px;
        margin-bottom: 8px;
    }
    .quick-btn span {
        font-size: 12px;
        font-weight: 600;
        text-align: center;
    }
    /* Quick Action Colors */
    .btn-blue { color: #0284c7; background: #f0f9ff; border-color: #e0f2fe; }
    .btn-green { color: #16a34a; background: #f0fdf4; border-color: #dcfce3; }
    .btn-purple { color: #9333ea; background: #faf5ff; border-color: #f3e8ff; }
    .btn-orange { color: #ea580c; background: #fff7ed; border-color: #ffedd5; }

    /* Info Cards (Informasi Jabatan style) */
    .info-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #f1f5f9;
        padding: 0;
        margin-bottom: 15px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .info-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 15px 20px;
        border-bottom: 1px solid #f1f5f9;
    }
    .info-header i {
        color: #0284c7;
        font-size: 18px;
    }
    .info-header h4 {
        margin: 0;
        font-size: 14px;
        font-weight: 600;
        color: #0284c7;
    }
    .info-body {
        padding: 15px 20px;
    }
    .info-item {
        margin-bottom: 12px;
    }
    .info-item:last-child {
        margin-bottom: 0;
    }
    .info-item label {
        display: block;
        font-size: 12px;
        color: #64748b;
        margin-bottom: 3px;
    }
    .info-item .val {
        font-size: 13px;
        color: #1e293b;
        font-weight: 500;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Header -->
<div class="app-header">
    <div class="app-logo">
        AJIP
    </div>
    <div class="header-actions">
        <a href="<?= base_url('dashboard/layanan#search') ?>" class="text-secondary" title="Cari Layanan" style="display: flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 50%; background: #f8fafc;">
            <i class='bx bx-search' style="font-size: 20px; color: #475569;"></i>
        </a>
        <a href="<?= base_url('profile') ?>">
            <?php
                $avatarUrl = session()->get('avatar') 
                    ? base_url('uploads/avatars/' . session()->get('avatar')) 
                    : 'https://ui-avatars.com/api/?name=' . urlencode(session()->get('username') ?? 'U') . '&background=random';
            ?>
            <img src="<?= $avatarUrl ?>" alt="Profile" class="header-avatar" style="object-fit: cover;" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode(session()->get('username') ?? 'U') ?>&background=random'" />
        </a>
    </div>
</div>

<div class="main-content">
    
    <!-- Profile Card -->
    <div class="profile-card">
        <div class="profile-icon">
            <i class='bx bx-user'></i>
        </div>
        <div class="profile-info">
            <h3>Selamat Datang,</h3>
            <h2><?= esc(session()->get('nama_lengkap') ?? 'Pengguna') ?></h2>
            <p>ID: <?= esc(session()->get('username') ?? '-') ?></p>
        </div>
        <div class="badge-role"><?= strtoupper(esc(session()->get('role') ?? 'GUEST')) ?></div>
    </div>

    <!-- Quick Actions -->
    <div class="section-title">
        <i class='bx bx-transfer-alt'></i> Aksi Cepat
    </div>
    <div class="quick-actions-grid">
        <a href="<?= base_url('dashboard?force_desktop=1') ?>" class="quick-btn btn-blue">
            <i class='bx bx-bar-chart-alt-2'></i>
            <span>Statistik Dasbor</span>
        </a>
        <a href="<?= base_url('dashboard/monev-proyek') ?>" class="quick-btn btn-green">
            <i class='bx bx-trending-up'></i>
            <span>Monev Proyek</span>
        </a>
        <a href="<?= base_url('report') ?>" class="quick-btn btn-purple">
            <i class='bx bx-spreadsheet'></i>
            <span>Laporan Realisasi</span>
        </a>
        <a href="<?= base_url('dashboard/layanan') ?>" class="quick-btn btn-orange">
            <i class='bx bx-grid-alt'></i>
            <span>Semua Layanan</span>
        </a>
    </div>

    <!-- Information Cards -->
    <?php if(session()->get('role') === 'opd'): ?>
    <div class="info-card">
        <div class="info-header">
            <i class='bx bx-buildings'></i>
            <h4>Informasi Instansi</h4>
        </div>
        <div class="info-body">
            <div class="info-item">
                <label>Nama OPD / Satuan Kerja Saat Ini</label>
                <div class="val"><?= esc(session()->get('nama_lengkap') ?? 'Tidak ada data instansi (Seluruh OPD)') ?></div>
            </div>
            <?php if(session()->get('kd_satker')): ?>
            <div class="info-item">
                <label>Kode Satker</label>
                <div class="val"><?= esc(session()->get('kd_satker')) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="info-card">
        <div class="info-header">
            <i class='bx bx-id-card'></i>
            <h4>Informasi Akses Sistem</h4>
        </div>
        <div class="info-body">
            <div class="info-item">
                <label>Tingkat Hak Akses</label>
                <div class="val text-uppercase"><?= esc(session()->get('role') ?? 'Tamu') ?></div>
            </div>
            <div class="info-item">
                <label>Status Akun</label>
                <div class="val text-success"><i class='bx bx-check-circle'></i> Aktif</div>
            </div>
        </div>
    </div>

    <br><br>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- No extra scripts needed for the dashboard home -->
<?= $this->endSection() ?>
