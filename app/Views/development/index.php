<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><?= $title ?></h4>
    <div>
        <a href="<?= base_url('development/charts') ?>" class="btn btn-outline-primary me-2">
            <i class="bx bx-bar-chart-alt-2 me-1"></i> Manage Charts
        </a>
        <a href="<?= base_url('development/create') ?>" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i> Create New Menu
        </a>
    </div>
</div>


<div class="card">
    <h5 class="card-header fw-bold">Active Dynamic Menus</h5>
    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>API URL</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($menus)): ?>
                        <tr>
                            <td colspan="4" class="text-center">No dynamic menus found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($menus as $menu): ?>
                            <tr>
                                <td>
                                    <i class="<?= $menu['icon'] ?> mr-2"></i>
                                    <strong><?= $menu['name'] ?></strong>
                                </td>
                                <td><code>/dynamic/<?= $menu['slug'] ?></code></td>
                                <td><small class="text-muted"><?= $menu['api_url'] ?></small></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="<?= base_url('dynamic/'.$menu['slug']) ?>" class="btn btn-sm btn-icon btn-label-info me-1" target="_blank" title="View">
                                            <i class="bx bx-link-external"></i>
                                        </a>
                                        <a href="<?= base_url('development/edit/'.$menu['id']) ?>" class="btn btn-sm btn-icon btn-label-primary me-1" title="Edit">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                        <a href="<?= base_url('development/delete/'.$menu['id']) ?>" class="btn btn-sm btn-icon btn-label-danger" data-confirm="Hapus menu <?= $menu['name'] ?>?" title="Delete">
                                            <i class="bx bx-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
