<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card mb-4">
            <h5 class="card-header fw-bold">Edit User: <?= $user['username'] ?></h5>
            <div class="card-body">
                <?php if(session()->getFlashdata('errors')): ?>
                    <div class="alert alert-danger mb-4">
                        <ul class="mb-0">
                        <?php foreach(session()->getFlashdata('errors') as $error): ?>
                            <li><?= $error ?></li>
                        <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?= base_url('user/update/'.$user['id']) ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-uppercase fw-bold small text-muted">Username</label>
                            <input type="text" name="username" class="form-control" required value="<?= old('username', $user['username']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-uppercase fw-bold small text-muted">Password (Kosongkan jika tetap)</label>
                            <input type="password" name="password" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-uppercase fw-bold small text-muted">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control" required value="<?= old('nama_lengkap', $user['nama_lengkap']) ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-uppercase fw-bold small text-muted">Role</label>
                            <select name="role" id="role-select" class="form-select" required>
                                <option value="admin" <?= old('role', $user['role']) == 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="operator" <?= old('role', $user['role']) == 'operator' ? 'selected' : '' ?>>Operator</option>
                                <option value="opd" <?= old('role', $user['role']) == 'opd' ? 'selected' : '' ?>>OPD</option>
                            </select>
                        </div>
                        <div class="col-md-5 mb-3 <?= $user['role'] !== 'opd' ? 'd-none' : '' ?>" id="opd-selection-container">
                            <label class="form-label text-uppercase fw-bold small text-muted">Pilih OPD</label>
                            <select id="opd-dropdown" class="form-select">
                                <option value="">-- Pilih OPD --</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3 <?= $user['role'] !== 'opd' ? 'd-none' : '' ?>" id="satker-code-container">
                            <label class="form-label text-uppercase fw-bold small text-muted">Kode Satker</label>
                            <input type="text" name="kd_satker" id="kd_satker" class="form-control bg-light" value="<?= old('kd_satker', $user['kd_satker']) ?>" readonly>
                        </div>
                    </div>

                    <div class="pt-4 border-top mt-4 d-flex justify-content-between">
                        <a href="<?= base_url('user') ?>" class="btn btn-label-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary px-4">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    const roleSelect = document.getElementById('role-select');
    const opdContainer = document.getElementById('opd-selection-container');
    const satkerContainer = document.getElementById('satker-code-container');
    const opdDropdown = document.getElementById('opd-dropdown');
    const kdSatkerInput = document.getElementById('kd_satker');
    const existingKdSatker = '<?= $user['kd_satker'] ?>';

    let opdData = [];

    async function fetchOpds() {
        try {
            const res = await fetch('<?= base_url('dashboard/getData/penyedia') ?>');
            const data = await res.json();
            
            const uniqueOpds = {};
            data.forEach(item => {
                if (item.nama_satker && !uniqueOpds[item.nama_satker]) {
                    uniqueOpds[item.nama_satker] = item.kd_satker;
                }
            });

            opdData = Object.entries(uniqueOpds).sort((a, b) => a[0].localeCompare(b[0]));
            
            opdData.forEach(([name, code]) => {
                const opt = document.createElement('option');
                opt.value = code;
                opt.textContent = name;
                if (code == existingKdSatker) opt.selected = true;
                opdDropdown.appendChild(opt);
            });
        } catch (err) {
            console.error('Failed to fetch OPD list:', err);
        }
    }

    roleSelect.addEventListener('change', function() {
        if (this.value === 'opd') {
            opdContainer.classList.remove('d-none');
            satkerContainer.classList.remove('d-none');
            if (opdData.length === 0) fetchOpds();
        } else {
            opdContainer.classList.add('d-none');
            satkerContainer.classList.add('d-none');
            kdSatkerInput.value = '';
        }
    });

    opdDropdown.addEventListener('change', function() {
        kdSatkerInput.value = this.value;
    });

    if (roleSelect.value === 'opd') {
        fetchOpds();
    }
</script>
<?= $this->endSection() ?>
