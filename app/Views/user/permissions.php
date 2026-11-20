<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><?= $title ?></h4>
        <p class="text-muted mb-0">Atur hak akses spesifik untuk user ini. Pengaturan ini akan menimpa (override) hak akses standar dari rolenya (<strong><?= strtoupper($user['role']) ?></strong>).</p>
    </div>
    <a href="<?= base_url('user') ?>" class="btn btn-secondary">
        <i class="bx bx-arrow-back me-1"></i> Kembali
    </a>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Hak Akses Menu</h5>
                <form action="<?= base_url('user/permissions/reset/'.$user['username']) ?>" method="post" onsubmit="return confirm('Apakah Anda yakin ingin menghapus semua hak akses kustom dan mengembalikannya ke standar role?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-warning btn-sm">
                        <i class="bx bx-refresh me-1"></i> Reset ke Standar Role
                    </button>
                </form>
            </div>
            <div class="card-body">
                <form action="<?= base_url('user/permissions/save/'.$user['username']) ?>" method="post">
                    <?= csrf_field() ?>
                    
                    <div class="table-responsive text-nowrap mb-4">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Menu / Modul</th>
                                    <th width="15%" class="text-center">Standar Role</th>
                                    <th width="15%" class="text-center">Akses User Ini</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no=1; foreach($permissions as $p): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <strong><?= $p['label'] ?></strong>
                                        <?php if($p['has_custom']): ?>
                                            <span class="badge bg-label-info ms-2" style="font-size: 0.7em;">Dimodifikasi</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if($p['role_default']): ?>
                                            <i class="bx bx-check-circle text-success" title="Role ini diizinkan"></i>
                                        <?php else: ?>
                                            <i class="bx bx-x-circle text-danger" title="Role ini dilarang"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center m-0">
                                            <input class="form-check-input" type="checkbox" name="perms[<?= $p['key'] ?>]" value="1" <?= $p['is_allowed'] ? 'checked' : '' ?> <?= $user['role'] == 'admin' ? 'disabled' : '' ?>>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary" <?= $user['role'] == 'admin' ? 'disabled' : '' ?>>
                            <i class="bx bx-save me-1"></i> Simpan Hak Akses
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
