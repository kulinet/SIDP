<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Chatbot AI Settings</h5>
                <small class="text-muted float-end">Configure OpenAI/Proxy settings</small>
            </div>
            <div class="card-body">
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
<?= $this->endSection() ?>
