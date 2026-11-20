<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="card-title mb-0 fw-bold text-primary">
                        <i class="bx bx-bar-chart-square me-2"></i>Summary Laporan Realisasi Anggaran RUP
                    </h5>
                    <small class="text-muted d-block mb-1">Konsolidasi data RUP dengan realisasi pengadaan per OPD</small>
                    
                    <?php 
                    $isRestrictedRole = in_array(session()->get('role'), ['opd', 'satker']);
                    $userNamaSatker = session()->get('nama_satker') ?: session()->get('nama_lengkap');
                    ?>
                    <?php if($isRestrictedRole): ?>
                    <div class="d-flex align-items-center gap-2 mt-2">
                        <i class="bx bx-shield-quarter text-warning"></i>
                        <small class="text-muted">Data difilter untuk:</small>
                        <span class="badge bg-label-warning fw-bold"><?= esc($userNamaSatker ?: 'Satker Anda') ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-2 w-100 w-md-auto">
                    <select id="yearFilter" class="form-select form-select-sm border-primary text-primary fw-bold flex-fill flex-md-grow-0" style="min-width: 120px;">
                        <?php 
                        $currentYear = date('Y');
                        for($i = $currentYear; $i >= 2022; $i--): 
                        ?>
                            <option value="<?= $i ?>" <?= $i == $currentYear ? 'selected' : '' ?>>Tahun <?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                    <button id="refreshBtn" class="btn btn-primary btn-sm rounded-pill px-3 flex-fill flex-md-grow-0">
                        <i class="bx bx-refresh me-1"></i> Refresh
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="reportTable" class="table table-hover table-bordered align-middle" style="width:100%">
                        <thead>
                            <tr class="header-main">
                                <th rowspan="2" class="text-center align-middle bg-secondary text-white border-0">No</th>
                                <th rowspan="2" class="align-middle bg-secondary text-white border-0">Nama OPD / Satker</th>
                                <th colspan="5" class="text-center bg-penyedia text-white py-3 border-0">
                                    <i class="bx bx-package me-1"></i> PAKET PENYEDIA
                                </th>
                                <th colspan="5" class="text-center bg-swakelola text-white py-3 border-0">
                                    <i class="bx bx-refresh me-1"></i> PAKET SWAKELOLA
                                </th>
                                <th colspan="5" class="text-center bg-total text-white py-3 border-0">
                                    <i class="bx bx-check-double me-1"></i> TOTAL REALISASI
                                </th>
                            </tr>
                            <tr class="header-sub">
                                <th class="text-center">Tot. Paket RUP</th>
                                <th class="text-center">Pagu RUP</th>
                                <th class="text-center">Pkt. Realisasi</th>
                                <th class="text-center">Realisasi (Rp)</th>
                                <th class="text-center">%</th>
                                <th class="text-center">Tot. Paket RUP</th>
                                <th class="text-center">Pagu RUP</th>
                                <th class="text-center">Pkt. Realisasi</th>
                                <th class="text-center">Realisasi (Rp)</th>
                                <th class="text-center">%</th>
                                <th class="text-center">Tot. Paket RUP</th>
                                <th class="text-center">Tot. Pagu RUP</th>
                                <th class="text-center">Tot. Pkt. Realisasi</th>
                                <th class="text-center">Tot. Realisasi (Rp)</th>
                                <th class="text-center">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                        <tfoot class="bg-light fw-bold">
                            <tr>
                                <td colspan="2" class="text-center">TOTAL KESELURUHAN</td>
                                <td class="text-center" id="total_paket_rup_p">0</td>
                                <td class="text-end" id="total_pagu_p">0</td>
                                <td class="text-center" id="total_paket_real_p">0</td>
                                <td class="text-end" id="total_real_p">0</td>
                                <td class="text-center" id="total_pct_p">0%</td>

                                <td class="text-center" id="total_paket_rup_s">0</td>
                                <td class="text-end" id="total_pagu_s">0</td>
                                <td class="text-center" id="total_paket_real_s">0</td>
                                <td class="text-end" id="total_real_s">0</td>
                                <td class="text-center" id="total_pct_s">0%</td>

                                <td class="text-center" id="total_paket_rup_all">0</td>
                                <td class="text-end" id="total_pagu_all">0</td>
                                <td class="text-center" id="total_paket_real_all">0</td>
                                <td class="text-end" id="total_real_all">0</td>
                                <td class="text-center" id="total_pct_all">0%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    const formatIDR = (val) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(val);
    };

    let table = $('#reportTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: '<?= base_url('report/getSummaryData') ?>',
            type: 'GET',
            data: function(d) {
                d.year = $('#yearFilter').val();
            },
            dataSrc: function(json) {
                if (!Array.isArray(json)) {
                    if (json && Array.isArray(json.data)) {
                        json = json.data;
                    } else {
                        console.error('Invalid summary data format:', json);
                        return [];
                    }
                }

                // Calculate totals for footer
                let totals = {
                    pagu_p: 0, paket_rup_p: 0, paket_real_p: 0, real_p: 0,
                    pagu_s: 0, paket_rup_s: 0, paket_real_s: 0, real_s: 0,
                    pagu_all: 0, paket_rup_all: 0, paket_real_all: 0, real_all: 0
                };

                json.forEach(item => {
                    totals.pagu_p += (item.pagu_penyedia || 0);
                    totals.paket_rup_p += (item.total_paket_p || 0);
                    totals.paket_real_p += (item.paket_realisasi_p || 0);
                    totals.real_p += (item.realisasi_penyedia || 0);

                    totals.pagu_s += (item.pagu_swakelola || 0);
                    totals.paket_rup_s += (item.total_paket_s || 0);
                    totals.paket_real_s += (item.paket_realisasi_s || 0);
                    totals.real_s += (item.realisasi_swakelola || 0);
                });

                totals.pagu_all = totals.pagu_p + totals.pagu_s;
                totals.paket_rup_all = totals.paket_rup_p + totals.paket_rup_s;
                totals.paket_real_all = totals.paket_real_p + totals.paket_real_s;
                totals.real_all = totals.real_p + totals.real_s;

                $('#total_paket_rup_p').text((totals.paket_rup_p || 0).toLocaleString('id-ID'));
                $('#total_pagu_p').text(formatIDR(totals.pagu_p));
                $('#total_paket_real_p').text((totals.paket_real_p || 0).toLocaleString('id-ID'));
                $('#total_real_p').text(formatIDR(totals.real_p));
                $('#total_pct_p').text((totals.pagu_p > 0 ? (totals.real_p / totals.pagu_p * 100).toFixed(2) : 0) + '%');
                
                $('#total_paket_rup_s').text((totals.paket_rup_s || 0).toLocaleString('id-ID'));
                $('#total_pagu_s').text(formatIDR(totals.pagu_s));
                $('#total_paket_real_s').text((totals.paket_real_s || 0).toLocaleString('id-ID'));
                $('#total_real_s').text(formatIDR(totals.real_s));
                $('#total_pct_s').text((totals.pagu_s > 0 ? (totals.real_s / totals.pagu_s * 100).toFixed(2) : 0) + '%');

                $('#total_paket_rup_all').text((totals.paket_rup_all || 0).toLocaleString('id-ID'));
                $('#total_pagu_all').text(formatIDR(totals.pagu_all));
                $('#total_paket_real_all').text((totals.paket_real_all || 0).toLocaleString('id-ID'));
                $('#total_real_all').text(formatIDR(totals.real_all));
                $('#total_pct_all').text((totals.pagu_all > 0 ? (totals.real_all / totals.pagu_all * 100).toFixed(2) : 0) + '%');

                return json;
            }
        },
        columns: [
            { 
                data: null, 
                render: (data, type, row, meta) => meta.row + 1,
                className: 'text-center'
            },
            { data: 'nama_satker' },
            {
                data: 'total_paket_p',
                render: (data) => (data || 0).toLocaleString('id-ID'),
                className: 'text-center'
            },
            { 
                data: 'pagu_penyedia',
                render: (data) => formatIDR(data || 0),
                className: 'text-end'
            },
            {
                data: 'paket_realisasi_p',
                render: (data) => (data || 0).toLocaleString('id-ID'),
                className: 'text-center'
            },
            { 
                data: 'realisasi_penyedia',
                render: (data) => formatIDR(data || 0),
                className: 'text-end'
            },
            { 
                data: null,
                render: (data) => {
                    let pct = data.pagu_penyedia > 0 ? (data.realisasi_penyedia / data.pagu_penyedia * 100).toFixed(2) : 0;
                    return `<span class="badge ${pct >= 100 ? 'bg-success' : (pct >= 50 ? 'bg-warning' : 'bg-danger')}">${pct}%</span>`;
                },
                className: 'text-center'
            },
            {
                data: 'total_paket_s',
                render: (data) => (data || 0).toLocaleString('id-ID'),
                className: 'text-center'
            },
            { 
                data: 'pagu_swakelola',
                render: (data) => formatIDR(data || 0),
                className: 'text-end'
            },
            {
                data: 'paket_realisasi_s',
                render: (data) => (data || 0).toLocaleString('id-ID'),
                className: 'text-center'
            },
            { 
                data: 'realisasi_swakelola',
                render: (data) => formatIDR(data || 0),
                className: 'text-end'
            },
            { 
                data: null,
                render: (data) => {
                    let pct = data.pagu_swakelola > 0 ? (data.realisasi_swakelola / data.pagu_swakelola * 100).toFixed(2) : 0;
                    return `<span class="badge ${pct >= 100 ? 'bg-success' : (pct >= 50 ? 'bg-info' : 'bg-secondary')}">${pct}%</span>`;
                },
                className: 'text-center'
            },
            {
                data: null,
                render: (data) => (data.total_paket_p + data.total_paket_s).toLocaleString('id-ID'),
                className: 'text-center'
            },
            { 
                data: null,
                render: (data) => formatIDR((data.pagu_penyedia || 0) + (data.pagu_swakelola || 0)),
                className: 'text-end fw-bold'
            },
            {
                data: null,
                render: (data) => (data.paket_realisasi_p + data.paket_realisasi_s).toLocaleString('id-ID'),
                className: 'text-center'
            },
            { 
                data: null,
                render: (data) => formatIDR((data.realisasi_penyedia || 0) + (data.realisasi_swakelola || 0)),
                className: 'text-end fw-bold'
            },
            { 
                data: null,
                render: (data) => {
                    let totalPagu = (data.pagu_penyedia || 0) + (data.pagu_swakelola || 0);
                    let totalReal = (data.realisasi_penyedia || 0) + (data.realisasi_swakelola || 0);
                    let pct = totalPagu > 0 ? (totalReal / totalPagu * 100).toFixed(2) : 0;
                    
                    let colorClass = 'bg-danger';
                    if (pct >= 80) colorClass = 'bg-success';
                    else if (pct >= 40) colorClass = 'bg-primary';
                    else if (pct > 0) colorClass = 'bg-warning';

                    return `
                        <div class="d-flex flex-column align-items-center">
                            <span class="badge ${colorClass} mb-1" style="font-size: 0.7rem;">${pct}%</span>
                            <div class="progress w-100" style="height: 6px; border-radius: 10px;">
                                <div class="progress-bar ${colorClass}" 
                                     role="progressbar" style="width: ${pct}%" 
                                     aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>`;
                },
                className: 'text-center',
                width: '120px'
            }
        ],
        dom: '<"d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2"Bf>rtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="bx bxs-file-export me-1"></i> Export Excel',
                className: 'btn btn-outline-success btn-sm rounded-pill px-3',
                title: () => 'Laporan Summary Realisasi RUP - Tahun ' + $('#yearFilter').val(),
                messageTop: () => `Tanggal Export: ${new Date().toLocaleString('id-ID')}`
            },
            {
                extend: 'print',
                text: '<i class="bx bx-printer me-1"></i> Print',
                className: 'btn btn-outline-secondary btn-sm rounded-pill px-3',
                messageTop: () => `Tanggal Cetak: ${new Date().toLocaleString('id-ID')}`
            }
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Cari OPD..."
        }
    });

    $('#yearFilter, #refreshBtn').on('change click', function() {
        table.ajax.reload();
    });
});
</script>

<style>
    .bg-penyedia {
        background: linear-gradient(135deg, #71dd37 0%, #3a9d23 100%) !important; /* Vibrant Green for Penyedia */
    }
    .bg-swakelola {
        background: linear-gradient(135deg, #03c3ec 0%, #0097a7 100%) !important; /* Bright Cyan */
    }
    .bg-total {
        background: linear-gradient(135deg, #696cff 0%, #3f51b5 100%) !important; /* Premium Blue for Total */
    }
    .header-main th {
        font-weight: 800 !important;
        text-transform: uppercase;
        font-size: 0.9rem !important;
        letter-spacing: 1.2px;
        text-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }
    #reportTable {
        background-color: #ffffff;
        border-collapse: collapse !important;
        border: 1px solid #444 !important;
    }
    #reportTable th, #reportTable td {
        border: 1px solid #444 !important;
    }
    .header-main th {
        font-weight: 700 !important;
        text-transform: uppercase;
        font-size: 0.85rem !important;
        letter-spacing: 1px;
    }
    .header-sub th {
        background-color: #f8f9fa !important;
        color: #333 !important;
        font-weight: 700 !important;
        font-size: 0.75rem !important;
        text-transform: uppercase;
        padding: 8px 5px !important;
    }
    #reportTable tbody td {
        font-size: 0.85rem;
        padding: 8px 6px;
        color: #333;
    }
    .table-hover tbody tr:hover {
        background-color: #f2f2f2 !important;
    }
    .badge {
        font-weight: 600;
        padding: 0.3em 0.6em;
        border-radius: 4px;
    }
    tfoot td {
        background-color: #eee !important;
        font-weight: bold;
        color: #000;
    }
    .progress {
        background-color: #f0f2f5;
        border-radius: 10px;
        box-shadow: inset 0 1px 2px rgba(0,0,0,.05);
    }
    .progress-bar {
        border-radius: 10px;
        transition: width 0.6s ease;
    }
    div.dataTables_wrapper div.dataTables_filter input {
        border-radius: 20px;
        padding: 6px 15px;
        border: 1px solid #d9dee3;
        margin-left: 10px;
    }
    .dt-buttons .btn {
        margin-right: 5px;
    }
    /* Fixed column width for percentages */
    .pct-col {
        min-width: 80px;
    }
    /* Mobile datatable adjustments */
    @media (max-width: 768px) {
        div.dataTables_wrapper div.dataTables_filter {
            text-align: left !important;
            margin-top: 5px;
        }
        div.dataTables_wrapper div.dataTables_filter input {
            width: 100%;
            margin-left: 0;
            margin-top: 5px;
            display: block;
        }
        .dt-buttons {
            display: flex;
            width: 100%;
            gap: 8px;
        }
        .dt-buttons .btn {
            flex: 1;
            margin-right: 0 !important;
            padding: 8px 5px !important;
        }
    }
</style>
<?= $this->endSection() ?>
