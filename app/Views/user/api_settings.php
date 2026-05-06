<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Settings /</span> API Security
    </h4>

    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success alert-dismissible" role="alert">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <h5 class="card-header">API Access Token</h5>
                <div class="card-body">
                    <div class="alert alert-warning mb-4" role="alert">
                        <h6 class="alert-heading fw-bold mb-1">Peringatan Keamanan!</h6>
                        <p class="mb-0">Token ini memberikan akses penuh ke API data pengadaan. Jangan bagikan token ini kepada siapapun. Gunakan token ini pada header <code>X-API-KEY</code> di aplikasi Flutter Anda.</p>
                    </div>

                    <form action="<?= base_url('user/api-settings/generate') ?>" method="POST">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label">Your API Token</label>
                            <div class="input-group input-group-merge">
                                <input type="text" class="form-control" id="apiToken" value="<?= $user['api_token'] ?? 'Belum ada token' ?>" readonly>
                                <span class="input-group-text cursor-pointer" onclick="copyToken()"><i class="bx bx-copy"></i></span>
                            </div>
                            <div class="form-text">Gunakan token ini untuk autentikasi aplikasi mobile.</div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary me-2" onclick="return confirm('Generate token baru akan membatalkan token yang lama. Lanjutkan?')">Generate New Token</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <h5 class="card-header">Contoh Penggunaan API (Flutter)</h5>
                <div class="card-body">
                    <pre class="bg-dark text-white p-3 rounded"><code>
// Contoh Flutter Code
final response = await http.get(
  Uri.parse('https://sidp.io/api/summary'),
  headers: {
    'X-API-KEY': 'YOUR_TOKEN_HERE',
    'Accept': 'application/json',
  },
);
                    </code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToken() {
    var copyText = document.getElementById("apiToken");
    if (copyText.value === 'Belum ada token') return;
    
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);
    
    alert("Token berhasil disalin!");
}
</script>
<?= $this->endSection() ?>
