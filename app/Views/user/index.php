<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
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
                            <a href="<?= base_url('user/edit/'.$user['id']) ?>" class="btn btn-sm btn-icon btn-label-primary me-1">
                                <i class="bx bx-edit"></i>
                            </a>
                            <a href="<?= base_url('user/delete/'.$user['id']) ?>" class="btn btn-sm btn-icon btn-label-danger" data-confirm="Hapus user <?= $user['username'] ?>?">
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
