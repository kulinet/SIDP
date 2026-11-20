<?= $this->extend('layout/mobile_main') ?>

<?= $this->section('styles') ?>
<style>
    .profile-header {
        background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
        padding: 30px 20px;
        text-align: center;
        color: white;
        border-bottom-left-radius: 30px;
        border-bottom-right-radius: 30px;
        margin-bottom: -40px;
        position: relative;
        z-index: 1;
    }
    .profile-header .back-btn {
        position: absolute;
        top: 20px;
        left: 20px;
        color: white;
        font-size: 24px;
        text-decoration: none;
    }
    .profile-avatar-container {
        position: relative;
        width: 100px;
        height: 100px;
        margin: 0 auto 10px;
        z-index: 2;
    }
    .profile-avatar {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        border: 4px solid #ffffff;
        object-fit: cover;
        background: #f1f5f9;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .profile-title {
        font-size: 18px;
        font-weight: 700;
        margin: 0;
    }
    .profile-subtitle {
        font-size: 13px;
        opacity: 0.9;
        margin-top: 5px;
    }

    .profile-content {
        padding: 60px 20px 30px;
        background: #f8fafc;
        min-height: calc(100vh - 200px);
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 8px;
    }
    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        font-size: 14px;
        color: #1e293b;
        background: #ffffff;
        outline: none;
        transition: border-color 0.2s;
    }
    .form-control:focus {
        border-color: #0ea5e9;
    }
    .form-control:read-only {
        background: #f1f5f9;
        color: #64748b;
        cursor: not-allowed;
    }
    .btn-save {
        width: 100%;
        padding: 14px;
        background: #0ea5e9;
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
        box-shadow: 0 4px 6px rgba(14, 165, 233, 0.2);
    }
    .btn-save:hover {
        background: #0284c7;
    }
    
    .card-section {
        background: white;
        padding: 20px;
        border-radius: 16px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        margin-bottom: 20px;
    }
    .section-title {
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-title i {
        color: #0ea5e9;
        font-size: 18px;
    }
    
    .alert {
        padding: 15px;
        border-radius: 12px;
        margin-bottom: 20px;
        font-size: 13px;
    }
    .alert-success { background: #dcfce3; color: #166534; border: 1px solid #bbf7d0; }
    .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
    $avatarUrl = !empty($user['avatar']) 
        ? base_url('uploads/avatars/' . $user['avatar']) 
        : 'https://ui-avatars.com/api/?name=' . urlencode($user['nama_lengkap'] ?? 'U') . '&background=random&size=200';
?>
<div class="profile-header">
    <a href="<?= base_url('dashboard') ?>" class="back-btn"><i class='bx bx-arrow-back'></i></a>
    <div class="profile-avatar-container">
        <img src="<?= $avatarUrl ?>" alt="Profile" class="profile-avatar" />
    </div>
    <h1 class="profile-title"><?= esc($user['nama_lengkap']) ?></h1>
    <div class="profile-subtitle"><?= strtoupper(esc($user['role'])) ?></div>
</div>

<div class="profile-content">

    <?php if(session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    
    <?php if(session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>
    
    <?php if(session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger">
            <ul style="margin: 0; padding-left: 20px;">
            <?php foreach(session()->getFlashdata('errors') as $error): ?>
                <li><?= esc($error) ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= base_url('profile/update') ?>" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        
        <div class="card-section">
            <div class="section-title"><i class='bx bx-id-card'></i> Identitas Pengguna</div>
            
            <div class="form-group">
                <label>Ganti Foto Profil</label>
                <input type="file" name="avatar" class="form-control" accept="image/png, image/jpeg, image/webp" style="padding: 9px 15px;">
                <small style="color: #64748b; margin-top: 5px; display: block;">Opsional. Format: JPG, PNG, WEBP. Maks 2MB.</small>
            </div>
            
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" class="form-control" value="<?= esc($user['nama_lengkap']) ?>" readonly>
            </div>
            
            <div class="form-group">
                <label>Instansi / Satuan Kerja</label>
                <?php 
                    $instansi = ($user['role'] === 'opd') ? $user['nama_lengkap'] : session()->get('nama_satker');
                    $instansi = $instansi ?? 'Tidak ada data instansi';
                ?>
                <input type="text" class="form-control" value="<?= esc($instansi) ?>" readonly>
            </div>
        </div>

        <div class="card-section">
            <div class="section-title"><i class='bx bx-phone'></i> Kontak Pribadi</div>
            
            <div class="form-group">
                <label>Nomor HP (WhatsApp)</label>
                <input type="text" name="nomor_hp" class="form-control" placeholder="Contoh: 08123456789" value="<?= esc(old('nomor_hp', $user['nomor_hp'])) ?>">
            </div>
            
            <div class="form-group">
                <label>Alamat Email</label>
                <input type="email" name="email" class="form-control" placeholder="email@instansi.go.id" value="<?= esc(old('email', $user['email'])) ?>">
            </div>
        </div>
        
        <div class="card-section">
            <div class="section-title"><i class='bx bx-lock-alt'></i> Keamanan</div>
            
            <div class="form-group">
                <label>Ganti Password Baru</label>
                <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah">
                <small style="color: #64748b; margin-top: 5px; display: block;">Minimal 5 karakter.</small>
            </div>
        </div>

        <button type="submit" class="btn-save">Simpan Perubahan</button>
    </form>
</div>
<?= $this->endSection() ?>
