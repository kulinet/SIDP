<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Chatbot AI Settings</h5>
                <small class="text-muted float-end">Configure OpenAI/Proxy settings</small>
            </div>
            <div class="card-body">
                <?php if(session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <?= session()->getFlashdata('success') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <form action="<?= base_url('user/chatbot-settings/save') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="chatbot_model">AI Model</label>
                        <div class="input-group input-group-merge">
                            <span id="basic-icon-default-company2" class="input-group-text"><i class="bx bx-chip"></i></span>
                            <input
                                type="text"
                                id="chatbot_model"
                                name="chatbot_model"
                                class="form-control"
                                placeholder="gpt-4o-mini"
                                value="<?= old('chatbot_model', $settings['model']) ?>"
                                required
                            />
                        </div>
                        <div class="form-text text-warning small">
                            <i class="bx bx-info-circle"></i> Jika model sedang error (502), coba ganti ke model alternatif yang tersedia (misal: <code>gpt-5.4-mini</code>).
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="chatbot_api_url">API Base URL</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text"><i class="bx bx-link"></i></span>
                            <input
                                type="url"
                                id="chatbot_api_url"
                                name="chatbot_api_url"
                                class="form-control"
                                placeholder="https://api.ai.cc/v1/chat/completions"
                                value="<?= old('chatbot_api_url', $settings['api_url']) ?>"
                                required
                            />
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="chatbot_api_key">API Key</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text"><i class="bx bx-key"></i></span>
                            <input
                                type="password"
                                id="chatbot_api_key"
                                name="chatbot_api_key"
                                class="form-control"
                                placeholder="sk-..."
                                value="<?= old('chatbot_api_key', $settings['api_key']) ?>"
                                required
                            />
                        </div>
                        <div class="form-text">Pastikan API Key memiliki akses ke model yang dipilih.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="chatbot_system_prompt">Kustomisasi System Prompt / Kemampuan AI</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text"><i class="bx bx-brain"></i></span>
                            <textarea
                                id="chatbot_system_prompt"
                                name="chatbot_system_prompt"
                                class="form-control"
                                rows="4"
                                placeholder="Contoh: Selalu gunakan bahasa yang sopan. Jawablah dengan format poin-poin. Anda juga merupakan pakar keamanan siber..."
                            ><?= old('chatbot_system_prompt', $settings['system_prompt'] ?? '') ?></textarea>
                        </div>
                        <div class="form-text text-muted small">
                            <i class="bx bx-info-circle"></i> Tambahkan instruksi khusus untuk memperluas atau mengubah kemampuan (<em>behavior</em>) chatbot AI ini. Akan digabungkan dengan konteks internal.
                        </div>
                    </div>
                    <div class="divider my-4">
                        <div class="divider-text text-uppercase fw-bold"><i class="bx bx-layer"></i> Embedding Settings (Khusus RAG/Knowledge)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label" for="embedding_model">Embedding Model</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text"><i class="bx bx-chip"></i></span>
                            <input
                                type="text"
                                id="embedding_model"
                                name="embedding_model"
                                class="form-control"
                                placeholder="text-embedding-3-small"
                                value="<?= old('embedding_model', $settings['embedding_model']) ?>"
                                required
                            />
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="embedding_api_url">Embedding API URL</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text"><i class="bx bx-link"></i></span>
                            <input
                                type="url"
                                id="embedding_api_url"
                                name="embedding_api_url"
                                class="form-control"
                                placeholder="https://api.openai.com/v1/embeddings"
                                value="<?= old('embedding_api_url', $settings['embedding_api_url']) ?>"
                                required
                            />
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="embedding_api_key">Embedding API Key</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text"><i class="bx bx-key"></i></span>
                            <input
                                type="password"
                                id="embedding_api_key"
                                name="embedding_api_key"
                                class="form-control"
                                placeholder="sk-..."
                                value="<?= old('embedding_api_key', $settings['embedding_api_key']) ?>"
                                required
                            />
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2">Save Changes</button>
                        <a href="<?= base_url('dashboard') ?>" class="btn btn-outline-secondary">Back to Dashboard</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-label-info">
            <div class="card-body">
                <h5 class="card-title text-info"><i class="bx bx-bulb me-1"></i> Tips</h5>
                <p class="card-text small">
                    Pengaturan ini disimpan di database. Jika terjadi error berantai, Anda masih bisa mengubahnya melalui database di tabel <code>settings</code>.
                </p>
                <hr>
                <p class="card-text small mb-0">
                    <strong>Model Saat Ini:</strong><br>
                    <code><?= esc($settings['model']) ?></code>
                </p>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-body">
                <h5 class="card-title"><i class="bx bx-list-ul me-1"></i> Daftar Model Umum</h5>
                <p class="text-muted small">Beberapa model yang umum digunakan pada endpoint OpenAI-Compatible:</p>
                <div class="table-responsive">
                    <table class="table table-sm table-borderless">
                        <thead>
                            <tr class="small border-bottom">
                                <th>Model Name</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <tr>
                                <td><code>gpt-5.4-mini</code></td>
                                <td class="text-success fw-bold">Active / OK</td>
                            </tr>
                            <tr>
                                <td><code>gpt-4o-mini</code></td>
                                <td>Standard Mini</td>
                            </tr>
                            <tr>
                                <td><code>gpt-4o</code></td>
                                <td>Flagship Model</td>
                            </tr>
                            <tr>
                                <td><code>gpt-4-turbo</code></td>
                                <td>Legacy Smart</td>
                            </tr>
                            <tr>
                                <td><code>claude-3-5-sonnet</code></td>
                                <td>Anthropic (Proxy)</td>
                            </tr>
                            <tr>
                                <td><code>gemini-1.5-flash</code></td>
                                <td>Google (Proxy)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-warning p-2 mt-2 small">
                    <i class="bx bx-error"></i> <strong>Penting:</strong> Nama model harus sesuai dengan yang didukung oleh <strong>API Key</strong> dan <strong>Base URL</strong> Anda.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== CHATBOT LOG INVESTIGATION TABLE ===== -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="bx bx-search-alt-2 me-2 text-primary"></i>Investigasi Log Percakapan Chatbot
                    </h5>
                    <small class="text-muted">Rekaman pertanyaan dan respons AI untuk pemantauan kualitas & kecerdasan chatbot</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-label-primary fs-6">
                        <?= count($chatbot_logs ?? []) ?> log terakhir
                    </span>
                    <form action="<?= base_url('user/chatbot-settings/clear-logs') ?>" method="POST" onsubmit="return confirm('Hapus semua log chatbot? Tindakan ini tidak dapat dibatalkan.')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bx bx-trash me-1"></i> Hapus Semua Log
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body pt-3">
                <!-- Stats Bar -->
                <?php
                    $logs = $chatbot_logs ?? [];
                    $totalSuccess = count(array_filter($logs, fn($l) => ($l['status'] ?? '') === 'success'));
                    $totalError   = count(array_filter($logs, fn($l) => ($l['status'] ?? '') === 'error'));
                    $uniqueUsers  = count(array_unique(array_column($logs, 'username')));
                    $avgRespLen   = count($logs) > 0 ? round(array_sum(array_map(fn($l) => strlen($l['response'] ?? ''), $logs)) / count($logs)) : 0;
                ?>
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-3 text-center bg-light">
                            <div class="fs-4 fw-bold text-success"><?= $totalSuccess ?></div>
                            <div class="small text-muted"><i class="bx bx-check-circle me-1"></i>Respons Sukses</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-3 text-center bg-light">
                            <div class="fs-4 fw-bold text-danger"><?= $totalError ?></div>
                            <div class="small text-muted"><i class="bx bx-error-circle me-1"></i>Respons Error</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-3 text-center bg-light">
                            <div class="fs-4 fw-bold text-info"><?= $uniqueUsers ?></div>
                            <div class="small text-muted"><i class="bx bx-user me-1"></i>Pengguna Unik</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-3 text-center bg-light">
                            <div class="fs-4 fw-bold text-warning"><?= number_format($avgRespLen) ?></div>
                            <div class="small text-muted"><i class="bx bx-text me-1"></i>Rata-rata Karakter Respons</div>
                        </div>
                    </div>
                </div>

                <!-- Log Table -->
                <div class="table-responsive">
                    <table class="table table-hover w-100" id="table-chatbot-logs">
                        <thead class="table-dark">
                            <tr>
                                <th width="35">#</th>
                                <th width="140">Waktu</th>
                                <th width="110">Pengguna</th>
                                <th width="90">IP Address</th>
                                <th width="90">Model</th>
                                <th width="70">Status</th>
                                <th>Pertanyaan</th>
                                <th>Respons AI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bx bx-chat fs-1 d-block mb-2"></i>
                                        Belum ada log percakapan chatbot. Log akan muncul setelah user mulai mengajukan pertanyaan.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $i => $log): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td class="small text-nowrap"><?= esc($log['created_at'] ?? '-') ?></td>
                                        <td>
                                            <span class="badge bg-label-secondary">
                                                <i class="bx bx-user me-1"></i><?= esc($log['username'] ?? '-') ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted"><?= esc($log['ip_address'] ?? '-') ?></td>
                                        <td><code class="small"><?= esc($log['model'] ?? '-') ?></code></td>
                                        <td>
                                            <?php if (($log['status'] ?? '') === 'success'): ?>
                                                <span class="badge bg-success"><i class="bx bx-check me-1"></i>OK</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><i class="bx bx-x me-1"></i>Error</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="log-question" style="max-width:260px; white-space:normal; word-break:break-word;">
                                                <?= esc($log['question'] ?? '-') ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-start gap-2">
                                                <div class="log-response-preview" style="max-width:320px; white-space:normal; word-break:break-word; max-height:80px; overflow:hidden; font-size:12px;">
                                                    <?= esc(mb_substr($log['response'] ?? '-', 0, 300)) ?><?= mb_strlen($log['response'] ?? '') > 300 ? '...' : '' ?>
                                                </div>
                                                <?php if (mb_strlen($log['response'] ?? '') > 100): ?>
                                                <button class="btn btn-xs btn-outline-info btn-sm flex-shrink-0"
                                                    style="font-size:11px; padding:2px 7px;"
                                                    onclick="showFullResponse(this)"
                                                    data-response="<?= htmlspecialchars($log['response'] ?? '', ENT_QUOTES) ?>"
                                                    data-question="<?= htmlspecialchars($log['question'] ?? '', ENT_QUOTES) ?>"
                                                    data-user="<?= esc($log['username'] ?? '') ?>"
                                                    data-time="<?= esc($log['created_at'] ?? '') ?>"
                                                    data-model="<?= esc($log['model'] ?? '') ?>">
                                                    <i class="bx bx-fullscreen"></i>
                                                </button>
                                                <?php endif; ?>
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
    </div>
</div>

<!-- Modal Detail Log -->
<div class="modal fade" id="logDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <div>
                    <h5 class="modal-title mb-0"><i class="bx bx-search-alt-2 me-2"></i>Detail Log Percakapan</h5>
                    <small class="text-light opacity-75" id="log-meta"></small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold text-primary"><i class="bx bx-question-mark me-1"></i>Pertanyaan User</label>
                    <div class="alert alert-light border p-3" id="log-question" style="white-space:pre-wrap; word-break:break-word;"></div>
                </div>
                <div>
                    <label class="form-label fw-bold text-success"><i class="bx bx-bot me-1"></i>Respons AI</label>
                    <div class="border rounded p-3 bg-light" id="log-response" style="white-space:pre-wrap; word-break:break-word; font-size:13px; max-height:450px; overflow-y:auto;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function() {
    if ($('#table-chatbot-logs tbody tr td').length > 1) {
        $('#table-chatbot-logs').DataTable({
            responsive: true,
            pageLength: 25,
            order: [[1, 'desc']],
            columnDefs: [
                { orderable: false, targets: [6, 7] }
            ],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/id.json' }
        });
    }
});

function showFullResponse(btn) {
    document.getElementById('log-question').textContent = btn.dataset.question;
    document.getElementById('log-response').textContent  = btn.dataset.response;
    document.getElementById('log-meta').textContent =
        '👤 ' + btn.dataset.user + '  |  🕐 ' + btn.dataset.time + '  |  🤖 ' + btn.dataset.model;
    new bootstrap.Modal(document.getElementById('logDetailModal')).show();
}
</script>
<?= $this->endSection() ?>

