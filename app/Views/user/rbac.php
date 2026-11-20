<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="bx bx-shield-quarter me-2"></i>Role Based Access Control (RBAC)
                </h5>
                <small class="text-muted">Atur hak akses menu berdasarkan role pengguna</small>
            </div>
            <div class="card-body">
                <form action="<?= base_url('user/rbac/save') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th class="py-3 px-4" style="width: 300px;">Menu Dashboard</th>
                                    <?php foreach($roles as $role): ?>
                                        <th class="text-center py-3">
                                            <div class="text-uppercase fw-bold text-dark small mb-1"><?= $role ?></div>
                                            <div class="form-check d-inline-block">
                                                <input class="form-check-input check-all" type="checkbox" data-role="<?= $role ?>">
                                            </div>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($menus as $menu): ?>
                                <tr>
                                    <td class="px-4 fw-semibold text-dark">
                                        <?= $menu['label'] ?>
                                    </td>
                                    <?php foreach($roles as $role): ?>
                                        <td class="text-center">
                                            <?php if($role === 'admin'): ?>
                                                <div class="form-check form-switch d-inline-block">
                                                    <input class="form-check-input" type="checkbox" checked disabled>
                                                </div>
                                            <?php else: ?>
                                                <div class="form-check form-switch d-inline-block">
                                                    <input class="form-check-input role-checkbox-<?= $role ?>" 
                                                           type="checkbox" 
                                                           name="perms[<?= $role ?>][<?= $menu['key'] ?>]" 
                                                           value="1"
                                                           <?= (isset($permissions[$role][$menu['key']]) && $permissions[$role][$menu['key']] == 1) ? 'checked' : '' ?>>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary px-5 rounded-pill shadow-sm">
                            <i class="bx bx-save me-1"></i> Simpan Konfigurasi RBAC
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    $('.check-all').on('change', function() {
        const role = $(this).data('role');
        const checked = $(this).is(':checked');
        $(`.role-checkbox-${role}`).prop('checked', checked);
    });
});
</script>
<style>
    .table thead th {
        vertical-align: middle;
        border-top: none;
    }
    .form-check-input:checked {
        background-color: #696cff;
        border-color: #696cff;
    }
    .form-switch .form-check-input {
        width: 3em;
        height: 1.5em;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(105, 108, 255, 0.04);
    }
</style>
<?= $this->endSection() ?>
