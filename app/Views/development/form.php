<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><?= $title ?></h4>
    <a href="<?= base_url('development') ?>" class="btn btn-label-secondary">
        <i class="bx bx-arrow-back me-1"></i> Back to List
    </a>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card mb-4">
            <h5 class="card-header fw-bold">Menu Configuration</h5>
            <div class="card-body">
                <form action="<?= isset($menu) ? base_url('development/update/'.$menu['id']) : base_url('development/store') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label text-uppercase fw-bold small text-muted">Menu Name</label>
                        <input type="text" name="name" class="form-control" required value="<?= isset($menu) ? $menu['name'] : '' ?>" placeholder="e.g. Data RUP Baru">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-uppercase fw-bold small text-muted">Slug (optional)</label>
                        <input type="text" name="slug" class="form-control" value="<?= isset($menu) ? $menu['slug'] : '' ?>" placeholder="e.g. data-rup-baru">
                        <div class="form-text">Used for the URL: /dynamic/your-slug</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-uppercase fw-bold small text-muted">API URL</label>
                        <div class="input-group">
                            <input type="text" id="api_url" name="api_url" class="form-control" required value="<?= isset($menu) ? $menu['api_url'] : '' ?>" placeholder="http://192.168.13.8:3000/api/data/X">
                            <button type="button" class="btn btn-outline-secondary" id="fetch-keys-btn">
                                <i class="bx bx-sync me-1"></i> Fetch Keys
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-uppercase fw-bold small text-muted">Icon (Boxicons)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i id="icon-preview" class="<?= isset($menu) ? $menu['icon'] : 'bx bx-link' ?>"></i></span>
                            <input type="text" id="icon-input" name="icon" class="form-control" value="<?= isset($menu) ? $menu['icon'] : 'bx bx-link' ?>" readonly>
                            <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#iconPickerModal">
                                <i class="bx bx-pointer me-1"></i> Pick Icon
                            </button>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-uppercase fw-bold small text-muted">Description (optional)</label>
                        <textarea name="description" class="form-control" rows="3"><?= isset($menu) ? $menu['description'] : '' ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary d-block w-100">
                        <i class="bx bx-save me-1"></i> <?= isset($menu) ? 'Update' : 'Create' ?> Menu
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php if(isset($menu)): ?>
    <div class="col-lg-7">
        <!-- Column Management -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Column Mappings</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addColumnModal">
                    <i class="bx bx-plus me-1"></i> Add Column
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive text-nowrap">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Field Key</th>
                                <th>Label</th>
                                <th>Formatter</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($menu['columns'])): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No columns configured yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($menu['columns'] as $col): ?>
                                    <tr>
                                        <td><?= $col['order_index'] ?></td>
                                        <td><code><?= $col['field_key'] ?></code></td>
                                        <td><?= $col['label'] ?></td>
                                        <td><span class="badge bg-label-secondary"><?= $col['formatter'] ?></span></td>
                                        <td>
                                            <a href="<?= base_url('development/deleteColumn/'.$col['id']) ?>" class="btn btn-sm btn-icon btn-label-danger" data-confirm="Hapus kolom <?= $col['label'] ?>?">
                                                <i class="bx bx-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Add Column Modal -->
<?php if(isset($menu)): ?>
<div class="modal fade" id="addColumnModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?= base_url('development/addColumn/'.$menu['id']) ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Column Mapping</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-uppercase fw-bold small text-muted">JSON Field Key</label>
                        <select name="field_key" id="field_key_select" class="form-select" required>
                            <?php if(!empty($api_keys)): ?>
                                <option value="">Select a key...</option>
                                <?php foreach($api_keys as $key): ?>
                                    <option value="<?= $key ?>"><?= $key ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">Fetch API keys first...</option>
                            <?php endif; ?>
                        </select>
                        <div class="form-text">The exact key from your API JSON response.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-uppercase fw-bold small text-muted">Display Label</label>
                        <input type="text" name="label" class="form-control" required placeholder="e.g. Nama Paket">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-uppercase fw-bold small text-muted">Order Index</label>
                        <input type="number" name="order_index" class="form-control" value="<?= count($menu['columns'] ?? []) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-uppercase fw-bold small text-muted">Formatter</label>
                        <select name="formatter" class="form-select">
                            <option value="none">None (Raw Text)</option>
                            <option value="currency">Currency (IDR)</option>
                            <option value="date">Date</option>
                            <option value="badge">Badge</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Column</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Icon Picker Modal -->
<div class="modal fade" id="iconPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">Select Icon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-4">
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" id="icon-search" class="form-control" placeholder="Search icons (e.g. user, chart, home)..." autofocus>
                    </div>
                </div>
                <div class="icon-grid-container" style="max-height: 400px; overflow-y: auto;">
                    <div class="row g-2" id="icon-grid">
                        <?php 
                            $commonIcons = [
                                'bx-home', 'bx-user', 'bx-cog', 'bx-bar-chart-alt-2', 'bx-line-chart', 'bx-pie-chart-alt-2', 
                                'bx-table', 'bx-list-ul', 'bx-data', 'bx-database', 'bx-server', 'bx-cloud', 
                                'bx-file', 'bx-folder', 'bx-book', 'bx-detail', 'bx-edit', 'bx-edit-alt',
                                'bx-check-square', 'bx-checkbox-checked', 'bx-bell', 'bx-calendar', 'bx-time',
                                'bx-search', 'bx-filter', 'bx-link', 'bx-link-external', 'bx-globe', 'bx-map',
                                'bx-envelope', 'bx-message-square-detail', 'bx-chat', 'bx-phone',
                                'bx-cart', 'bx-store', 'bx-credit-card', 'bx-wallet', 'bx-money',
                                'bx-grid-alt', 'bx-category', 'bx-windows', 'bx-layer', 'bx-package',
                                'bx-star', 'bx-heart', 'bx-bookmark', 'bx-flag', 'bx-tag',
                                'bx-shield-quarter', 'bx-lock-alt', 'bx-key', 'bx-error', 'bx-info-circle',
                                'bx-plus-circle', 'bx-minus-circle', 'bx-help-circle', 'bx-bulb'
                            ];
                            foreach($commonIcons as $icon):
                        ?>
                        <div class="col-6 col-md-3 col-lg-2 icon-item-wrapper" data-name="<?= str_replace('bx-', '', $icon) ?>">
                            <div class="icon-card p-3 border rounded text-center cursor-pointer h-100 d-flex flex-column align-items-center justify-content-center" 
                                 onclick="selectIcon('bx <?= $icon ?>')" 
                                 style="transition: all 0.2s; border-color: #f1f5f9 !important;">
                                <i class="bx <?= $icon ?> fs-2 mb-2"></i>
                                <span class="small text-muted text-truncate w-100"><?= str_replace('bx-', '', $icon) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $(document).ready(function() {
        const csrfName = '<?= csrf_token() ?>';
        let csrfHash = '<?= csrf_hash() ?>';

        function doFetch(isAuto = false) {
            const url = $('#api_url').val();
            if (!url) {
                if(!isAuto) showAlert('Input required', 'Enter API URL first', 'warning');
                return;
            }
            
            // Visual feedback
            const btn = $('#fetch-keys-btn');
            const originalText = btn.html();
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Loading...');

            $.post('<?= base_url('development/fetchApiKeys') ?>', { 
                [csrfName]: csrfHash,
                url: url 
            }, function(res) {
                btn.prop('disabled', false).html('<i class="bx bx-sync me-1"></i> Re-fetch');
                const select = $('#field_key_select');
                select.empty();
                select.append('<option value="">Select a key...</option>');
                if (res && res.length) {
                    res.forEach(key => {
                        select.append(new Option(key, key));
                    });
                }
                updateCSRF();
            }).fail(function(e) {
                btn.prop('disabled', false).html('<i class="bx bx-sync me-1"></i> Re-fetch');
                updateCSRF();
            });
        }

        $('#fetch-keys-btn').click(function() {
            doFetch(false);
        });

        // Auto fetch on load only if dropdown is empty
        if ($('#api_url').val() && $('#field_key_select option').length <= 2) {
            doFetch(true);
        }
    });


    function updateCSRF() {
        const cookieValue = document.cookie
            .split('; ')
            .find(row => row.startsWith('csrf_cookie_name='))
            ?.split('=')[1];
        if (cookieValue) {
            $('.csrf-token-meta').attr('content', cookieValue);
            // Also update any hidden inputs if they exist
            $('input[name="csrf_test_name"]').val(cookieValue);
        }
    }

    // Icon Picker Logic
    function selectIcon(iconClass) {
        $('#icon-input').val(iconClass);
        $('#icon-preview').attr('class', iconClass);
        bootstrap.Modal.getInstance(document.getElementById('iconPickerModal')).hide();
    }

    $('#icon-search').on('keyup', function() {
        const term = $(this).val().toLowerCase();
        $('.icon-item-wrapper').each(function() {
            const name = $(this).data('name').toLowerCase();
            $(this).toggle(name.includes(term));
        });
    });

    // Handle modal focus for search
    $('#iconPickerModal').on('shown.bs.modal', function () {
        $('#icon-search').focus();
    });

</script>
<?= $this->endSection() ?>
