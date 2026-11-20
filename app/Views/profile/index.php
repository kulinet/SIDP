<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Akun /</span> Profil Pengguna</h4>

    <div class="row">
        <div class="col-md-12">
            
            <?php if(session()->getFlashdata('success')): ?>
                <div class="alert alert-success alert-dismissible" role="alert">
                    <?= session()->getFlashdata('success') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if(session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible" role="alert">
                    <?= session()->getFlashdata('error') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if(session()->getFlashdata('errors')): ?>
                <div class="alert alert-danger alert-dismissible" role="alert">
                    <ul class="mb-0">
                    <?php foreach(session()->getFlashdata('errors') as $error): ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <h5 class="card-header">Detail Profil</h5>
                <!-- Account -->
                <div class="card-body">
                    <div class="d-flex align-items-start align-items-sm-center gap-4">
                        <?php
                            $avatarUrl = !empty($user['avatar']) 
                                ? base_url('uploads/avatars/' . $user['avatar']) 
                                : 'https://ui-avatars.com/api/?name=' . urlencode($user['nama_lengkap'] ?? 'U') . '&background=random&size=200';
                        ?>
                        <img src="<?= $avatarUrl ?>" alt="user-avatar" class="d-block rounded-circle" height="100" width="100" id="uploadedAvatar" style="object-fit: cover;" />
                        <div class="button-wrapper">
                            <h4 class="mb-1"><?= esc($user['nama_lengkap']) ?></h4>
                            <p class="text-muted mb-0">Role: <?= strtoupper(esc($user['role'])) ?></p>
                            <p class="text-muted mb-0">ID: <?= esc($user['username']) ?></p>
                        </div>
                    </div>
                </div>
                <hr class="my-0" />
                <div class="card-body">
                    <form action="<?= base_url('profile/update') ?>" method="POST" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div class="row">
                            <div class="mb-3 col-md-12">
                                <label class="form-label">Ganti Foto Profil</label>
                                <input type="file" name="avatar" class="form-control" accept="image/png, image/jpeg, image/webp" />
                                <div class="form-text">Opsional. Format yang diizinkan: JPG, PNG, WEBP. Maks 2MB.</div>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label">Nama Lengkap</label>
                                <input class="form-control" type="text" value="<?= esc($user['nama_lengkap']) ?>" readonly style="background-color: #f8f9fa;" />
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label">Instansi / Satuan Kerja</label>
                                <?php 
                                    $instansi = ($user['role'] === 'opd') ? $user['nama_lengkap'] : session()->get('nama_satker');
                                    $instansi = $instansi ?? 'Tidak ada data instansi';
                                ?>
                                <input class="form-control" type="text" value="<?= esc($instansi) ?>" readonly style="background-color: #f8f9fa;" />
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label">Nomor HP (WhatsApp)</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text">ID (+62)</span>
                                    <input type="text" name="nomor_hp" class="form-control" value="<?= esc(old('nomor_hp', $user['nomor_hp'])) ?>" placeholder="08123456789" />
                                </div>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label">E-mail</label>
                                <input class="form-control" type="email" name="email" value="<?= esc(old('email', $user['email'])) ?>" placeholder="email@instansi.go.id" />
                            </div>
                            
                            <div class="mb-3 col-md-12 mt-3">
                                <h5 class="mb-3">Keamanan</h5>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label">Ganti Password Baru</label>
                                <input class="form-control" type="password" name="password" placeholder="Kosongkan jika tidak ingin mengubah password" />
                                <div class="form-text">Biarkan kosong jika tidak ingin mengubah password. Minimal 5 karakter jika ingin mengubah.</div>
                            </div>
                        </div>
                        <div class="mt-2">
                            <button type="submit" class="btn btn-primary me-2">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
                <!-- /Account -->
            </div>
            
        </div>
    </div>
</div>
<?= $this->endSection() ?>
