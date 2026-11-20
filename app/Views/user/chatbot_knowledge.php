<?= $this->extend(service('request')->getUserAgent()->isMobile() ? 'layout/mobile_main' : 'layout/main') ?>

<?= $this->section('content') ?>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Chatbot AI /</span> Library Kepintaran AI</h4>

    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success alert-dismissible" role="alert">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Form Upload -->
        <div class="col-md-5">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Unggah Dokumen Aturan (PDF/TXT)</h5>
                </div>
                <div class="card-body">
                    <form action="<?= base_url('user/chatbot-knowledge/upload') ?>" method="POST" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label" for="pdf_document">File PDF / TXT Peraturan</label>
                            <input type="file" class="form-control" id="pdf_document" name="pdf_document" accept="application/pdf, text/plain" required />
                            <div class="form-text">Upload file PDF (Digital asli) atau file Text (.txt) seperti salinan teks Perpres 16/2018. Dokumen akan dipecah dan diproses menjadi Vektor agar AI dapat memahaminya.</div>
                        </div>
                        <button type="submit" class="btn btn-primary" onclick="this.innerHTML='<i class=\'bx bx-loader bx-spin\'></i> Sedang Memproses...'; this.classList.add('disabled'); this.form.submit();">
                            <i class='bx bx-upload'></i> Unggah & Proses Vektor
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="alert alert-info">
                <h6 class="alert-heading fw-bold mb-1"><i class='bx bx-info-circle'></i> Info Cara Kerja (RAG)</h6>
                <p class="mb-0" style="font-size: 13px;">File PDF yang diunggah akan diekstrak teksnya dan diubah menjadi nilai Vektor Matematis menggunakan model Embedding AI. Saat user bertanya, AI akan mencari potongan teks paling relevan dari database vektor ini sebelum menjawab.</p>
            </div>
        </div>

        <!-- Daftar Dokumen -->
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Daftar Dokumen di Library</h5>
                </div>
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Dokumen</th>
                                <th>Jumlah Potongan (Chunks)</th>
                                <th>Diunggah Pada</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            <?php if (empty($documents)): ?>
                            <tr>
                                <td colspan="4" class="text-center">Belum ada dokumen yang diunggah.</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($documents as $doc): ?>
                                <tr>
                                    <td><strong><i class='bx bxs-file-pdf text-danger'></i> <?= esc($doc['document_title']) ?></strong></td>
                                    <td><span class="badge bg-label-primary"><?= $doc['chunks'] ?> Chunks</span></td>
                                    <td><?= date('d M Y, H:i', strtotime($doc['uploaded_at'])) ?></td>
                                    <td>
                                        <form action="<?= base_url('user/chatbot-knowledge/delete') ?>" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus dokumen ini dari pengetahuan AI?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="document_title" value="<?= esc($doc['document_title']) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bx bx-trash"></i> Hapus</button>
                                        </form>
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
