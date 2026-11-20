<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?>

<?= $this->section('content') ?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <h4 class="fw-bold mb-0"><?= $title ?></h4>
    <a href="<?= base_url('user/create') ?>" class="btn btn-primary">
        <i class="bx bx-plus me-1"></i> Tambah User
    </a>
</div>


<div class="card">
    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Nama Lengkap</th>
                    <th>Role</th>
                    <th>Kode Satker</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                    <tr>
                        <td><strong><?= $user['username'] ?></strong></td>
                        <td><?= $user['nama_lengkap'] ?></td>
                        <td>
                            <span class="badge bg-label-<?= $user['role'] == 'admin' ? 'primary' : ($user['role'] == 'operator' ? 'success' : 'secondary') ?>">
                                <?= strtoupper($user['role']) ?>
                            </span>
                        </td>
                        <td><?= $user['kd_satker'] ?: '-' ?></td>
                        <td class="text-center">
                            <a href="<?= base_url('user/permissions/'.$user['username']) ?>" class="btn btn-sm btn-icon btn-label-warning me-1" title="Atur Hak Akses">
                                <i class="bx bx-key"></i>
                            </a>
                            <a href="<?= base_url('user/edit/'.encode_id($user['id'])) ?>" class="btn btn-sm btn-icon btn-label-primary me-1" title="Edit">
                                <i class="bx bx-edit"></i>
                            </a>
                            <a href="<?= base_url('user/reset-2fa/'.encode_id($user['id'])) ?>" class="btn btn-sm btn-icon btn-label-info me-1" data-confirm="Reset 2FA untuk user <?= $user['username'] ?>?" title="Reset 2FA">
                                <i class="bx bx-reset"></i>
                            </a>
                            <a href="<?= base_url('user/delete/'.encode_id($user['id'])) ?>" class="btn btn-sm btn-icon btn-label-danger" data-confirm="Hapus user <?= $user['username'] ?>?" title="Hapus">
                                <i class="bx bx-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
