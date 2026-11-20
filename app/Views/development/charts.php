<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?>

<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><?= $title ?></h4>
    <a href="<?= base_url('development') ?>" class="btn btn-label-secondary">
        <i class="bx bx-arrow-back me-1"></i> Back to Menus
    </a>
</div>


<div class="row">
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-body">
                <form id="chart-form" action="<?= base_url('development/storeChart') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="chart_id">
                    <div class="mb-3">
                        <label class="form-label text-uppercase fw-bold small text-muted">Chart Title</label>
                        <input type="text" name="title" class="form-control" required placeholder="e.g. Distribusi Pagu per OPD">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-uppercase fw-bold small text-muted">Data Source (Menu)</label>
                        <select name="menu_id" id="menu_id_select" class="form-select" required>
                            <option value="">Select source...</option>
                            <?php foreach($menus as $m): ?>
                                <option value="<?= $m['id'] ?>" data-url="<?= $m['api_url'] ?>"><?= $m['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-uppercase fw-bold small text-muted">Chart Type</label>
                        <select name="chart_type" class="form-select">
                            <option value="bar">Bar Chart</option>
                            <option value="pie">Pie Chart</option>
                            <option value="doughnut">Doughnut Chart</option>
                            <option value="line">Line Chart</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-uppercase fw-bold small text-muted">Label Field (X-Axis)</label>
                        <select name="label_field" id="label_field_select" class="form-select" required>
                            <option value="">Select source first...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-uppercase fw-bold small text-muted">Value Field (Y-Axis / Data)</label>
                        <select name="value_field" id="value_field_select" class="form-select" required>
                            <option value="">Select source first...</option>
                        </select>
                        <div class="form-text">For 'count' aggregation, this can be any field.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-uppercase fw-bold small text-muted">Aggregation</label>
                        <select name="aggregation" class="form-select">
                            <option value="count">Count (Frequency)</option>
                            <option value="sum">Sum (Total Value)</option>
                        </select>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" id="submit-btn" class="btn btn-primary">
                            <i class="bx bx-plus me-1"></i> Add Chart to Dashboard
                        </button>
                        <button type="button" id="cancel-btn" class="btn btn-label-secondary d-none" onclick="cancelEdit()">
                            Cancel Edit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card mb-4">
            <h5 class="card-header fw-bold">Active Dashboard Charts</h5>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Source</th>
                                <th>Type</th>
                                <th>Config</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($charts)): ?>
                                <tr><td colspan="5" class="text-center">No charts configured.</td></tr>
                            <?php else: ?>
                                <?php foreach($charts as $c): ?>
                                    <tr>
                                        <td><strong><?= $c['title'] ?></strong></td>
                                        <td><?= $c['menu_name'] ?></td>
                                        <td><span class="badge bg-label-info"><?= $c['chart_type'] ?></span></td>
                                        <td>
                                            <small class="text-muted">
                                                Labels: <?= $c['label_field'] ?><br>
                                                Values: <?= $c['value_field'] ?> (<?= $c['aggregation'] ?>)
                                            </small>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-icon btn-label-primary me-1" onclick='editChart(<?= json_encode($c) ?>)'>
                                                <i class="bx bx-edit"></i>
                                            </button>
                                            <a href="<?= base_url('development/deleteChart/'.$c['id']) ?>" class="btn btn-sm btn-icon btn-label-danger" data-confirm="Hapus chart <?= $c['title'] ?>?">
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
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    function fetchSelectedMenuKeys() {
        const url = $('#menu_id_select').find(':selected').data('url');
        if (!url) return;
        
        const csrfName = '<?= csrf_token() ?>';
        const csrfHash = '<?= csrf_hash() ?>';

        $.post('<?= base_url('development/fetchApiKeys') ?>', { 
            [csrfName]: csrfHash,
            url: url 
        }, function(res) {
            const labelSelect = $('#label_field_select');
            const valueSelect = $('#value_field_select');
            labelSelect.empty();
            valueSelect.empty();
            
            if (res && res.length) {
                res.forEach(key => {
                    labelSelect.append(new Option(key, key));
                    valueSelect.append(new Option(key, key));
                });
            }
            updateCSRF();
        }).fail(function() {
            updateCSRF();
        });
    }

    $('#menu_id_select').change(fetchSelectedMenuKeys);

    $(document).ready(function() {
        if ($('#menu_id_select').val()) {
            fetchSelectedMenuKeys();
        }
    });

    function editChart(data) {
        // Switch form to Edit Mode
        $('#chart_id').val(data.id);
        $('#chart-form').attr('action', '<?= base_url('development/updateChart/') ?>' + data.id);
        $('.card-header.fw-bold').first().text('Edit Chart: ' + data.title);
        $('#submit-btn').html('<i class="bx bx-save me-1"></i> Update Chart Configuration');
        $('#cancel-btn').removeClass('d-none');

        // Populate fields
        $('input[name="title"]').val(data.title);
        $('#menu_id_select').val(data.menu_id);
        $('select[name="chart_type"]').val(data.chart_type);
        $('select[name="aggregation"]').val(data.aggregation);

        // Fetch keys and set labels/values AFTER keys are loaded
        const url = $('#menu_id_select').find(':selected').data('url');
        if (url) {
            const csrfName = '<?= csrf_token() ?>';
            const csrfHash = '<?= csrf_hash() ?>';

            $.post('<?= base_url('development/fetchApiKeys') ?>', { 
                [csrfName]: csrfHash,
                url: url 
            }, function(res) {
                const labelSelect = $('#label_field_select');
                const valueSelect = $('#value_field_select');
                labelSelect.empty();
                valueSelect.empty();
                
                if (res && res.length) {
                    res.forEach(key => {
                        labelSelect.append(new Option(key, key));
                        valueSelect.append(new Option(key, key));
                    });
                    
                    // Set the specific values
                    labelSelect.val(data.label_field);
                    valueSelect.val(data.value_field);
                }
                updateCSRF();
            });
        }
        
        // Scroll to form
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function cancelEdit() {
        // Reset form
        $('#chart_id').val('');
        $('#chart-form').attr('action', '<?= base_url('development/storeChart') ?>');
        $('#chart-form')[0].reset();
        $('.card-header.fw-bold').first().text('Add New Chart');
        $('#submit-btn').html('<i class="bx bx-plus me-1"></i> Add Chart to Dashboard');
        $('#cancel-btn').addClass('d-none');
        
        $('#label_field_select').empty().append('<option value="">Select source first...</option>');
        $('#value_field_select').empty().append('<option value="">Select source first...</option>');
    }


    function updateCSRF() {
        const cookieValue = document.cookie
            .split('; ')
            .find(row => row.startsWith('csrf_cookie_name='))
            ?.split('=')[1];
        if (cookieValue) {
            $('.csrf-token-meta').attr('content', cookieValue);
            $('input[name="csrf_test_name"]').val(cookieValue);
        }
    }

</script>
<?= $this->endSection() ?>
