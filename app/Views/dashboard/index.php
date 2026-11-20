<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<style>
    .desktop-profile-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 30px;
        display: flex;
        align-items: center;
        gap: 20px;
        position: relative;
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
    }
    .profile-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background-color: #e0f2fe;
        color: #0284c7;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 40px;
        flex-shrink: 0;
    }
    .desktop-profile-info h3 {
        font-size: 14px;
        color: #64748b;
        margin: 0 0 5px 0;
        font-weight: 500;
    }
    .desktop-profile-info h2 {
        font-size: 24px;
        color: #0284c7;
        margin: 0 0 5px 0;
        font-weight: 700;
        text-transform: uppercase;
    }
    .desktop-profile-info p {
        font-size: 14px;
        color: #94a3b8;
        margin: 0;
    }
    .badge-role {
        position: absolute;
        top: 30px;
        right: 30px;
        background-color: #0284c7;
        color: white;
        font-size: 12px;
        padding: 5px 12px;
        border-radius: 15px;
        font-weight: 600;
    }

    .section-title {
        font-size: 18px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .section-title i {
        color: #0ea5e9;
        font-size: 24px;
    }

    .desktop-quick-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
    }
    .quick-btn {
        background: #ffffff;
        border-radius: 16px;
        padding: 25px 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        border: 1px solid #f1f5f9;
        transition: all 0.3s ease;
    }
    .quick-btn:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px rgba(0,0,0,0.05);
    }
    .quick-btn i {
        font-size: 36px;
        margin-bottom: 12px;
    }
    .quick-btn span {
        font-size: 14px;
        font-weight: 600;
        text-align: center;
    }
    /* Quick Action Colors */
    .btn-blue { color: #0284c7; background: #f0f9ff; border-color: #e0f2fe; }
    .btn-green { color: #16a34a; background: #f0fdf4; border-color: #dcfce3; }
    .btn-purple { color: #9333ea; background: #faf5ff; border-color: #f3e8ff; }
    .btn-orange { color: #ea580c; background: #fff7ed; border-color: #ffedd5; }

    .info-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #f1f5f9;
        padding: 0;
        margin-bottom: 20px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }
    .info-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 20px 25px;
        border-bottom: 1px solid #f1f5f9;
        background: #f8fafc;
    }
    .info-header i {
        color: #0284c7;
        font-size: 22px;
    }
    .info-header h4 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #0284c7;
    }
    .info-body {
        padding: 25px;
    }
    .info-item {
        margin-bottom: 18px;
    }
    .info-item:last-child {
        margin-bottom: 0;
    }
    .info-item label {
        display: block;
        font-size: 13px;
        color: #64748b;
        margin-bottom: 5px;
    }
    .info-item .val {
        font-size: 15px;
        color: #1e293b;
        font-weight: 600;
    }
</style>

<div class="row">
    <!-- Left Column: Profile & Actions -->
    <div class="col-lg-8 mb-4">
        <!-- Profile Card -->
        <div class="desktop-profile-card mb-4">
            <div class="profile-icon">
                <i class='bx bx-user'></i>
            </div>
            <div class="desktop-profile-info">
                <h3>Selamat Datang,</h3>
                <h2><?= esc(session()->get('nama_lengkap') ?? 'Pengguna') ?></h2>
                <p>ID: <?= esc(session()->get('username') ?? '-') ?></p>
            </div>
            <div class="badge-role"><?= strtoupper(esc(session()->get('role') ?? 'GUEST')) ?></div>
        </div>

        <!-- Quick Actions -->
        <div class="section-title mt-5">
            <i class='bx bx-transfer-alt'></i> Aksi Cepat
        </div>
        <div class="desktop-quick-grid">
            <a href="<?= base_url('dashboard/statistik') ?>" class="quick-btn btn-blue">
                <i class='bx bx-bar-chart-alt-2'></i>
                <span>Statistik<br>Dasbor</span>
            </a>
            <a href="<?= base_url('dashboard/monev-proyek') ?>" class="quick-btn btn-green">
                <i class='bx bx-trending-up'></i>
                <span>Monev<br>Proyek</span>
            </a>
            <a href="<?= base_url('report') ?>" class="quick-btn btn-purple">
                <i class='bx bx-spreadsheet'></i>
                <span>Laporan<br>Realisasi</span>
            </a>
            <a href="<?= base_url('dashboard/layanan') ?>" class="quick-btn btn-orange">
                <i class='bx bx-grid-alt'></i>
                <span>Semua<br>Layanan</span>
            </a>
        </div>
    </div>

    <!-- Right Column: Info Cards -->
    <div class="col-lg-4">
        <div class="info-card">
            <div class="info-header">
                <i class='bx bx-buildings'></i>
                <h4>Informasi Instansi</h4>
            </div>
            <div class="info-body">
                <div class="info-item">
                    <label>Nama OPD / Satuan Kerja Saat Ini</label>
                    <div class="val"><?= esc(session()->get('nama_satker') ?? 'Tidak ada data instansi (Seluruh OPD)') ?></div>
                </div>
                <?php if(session()->get('kd_satker')): ?>
                <div class="info-item">
                    <label>Kode Satker</label>
                    <div class="val"><?= esc(session()->get('kd_satker')) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

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
    </div>
</div>
<?= $this->endSection() ?>
