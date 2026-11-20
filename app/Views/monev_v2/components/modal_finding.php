<!-- Modal Catat Temuan (Finding) Baru -->
<div class="modal fade" id="modalAddFinding" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title text-white d-flex align-items-center gap-2" id="modalAddFindingTitle">
                    <i class="bx bx-error-alt fs-4"></i> Catat Temuan Pengawasan (Finding)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAddFinding" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="period_id" id="finding_period_id" value="<?= $activePeriod['id'] ?? '' ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold text-dark">Judul Temuan <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="finding_title" class="form-control" placeholder="Contoh: Pekerja Struktur Tidak Memakai Helm Standar SNI" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark">Tingkat Keparahan (Severity) <span class="text-danger">*</span></label>
                            <select name="severity" id="finding_severity" class="form-select fw-semibold" required>
                                <option value="LOW" class="text-success">🟢 LOW (Minor / Ringan)</option>
                                <option value="MEDIUM" selected class="text-warning">🟡 MEDIUM (Perlu Perhatian)</option>
                                <option value="HIGH" class="text-danger">🟠 HIGH (Tinggi / Menghambat)</option>
                                <option value="CRITICAL" class="text-danger fw-bold">🔴 CRITICAL (Kritis / SCM / Stop Kerja)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Kategori Temuan</label>
                            <select name="category" id="finding_category" class="form-select">
                                <option value="TEKNIS_KONSTRUKSI">Teknis & Spesifikasi Konstruksi</option>
                                <option value="KETERLAMBATAN_WAKTU">Jadwal & Keterlambatan Fisik</option>
                                <option value="K3_LINGKUNGAN">Keselamatan Kerja (K3) & Lingkungan</option>
                                <option value="ADMINISTRATIF">Administrasi, Time Schedule & Gambar</option>
                                <option value="LAINNYA">Lainnya / Umum</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Lokasi Spesifik / Zona Proyek</label>
                            <input type="text" name="lokasi_spesifik" class="form-control" placeholder="Contoh: Abutment 1 STA 0+250 atau Lantai 2 Zona B">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold text-dark">Deskripsi Detail Masalah / Temuan <span class="text-danger">*</span></label>
                            <textarea name="description" id="finding_description" class="form-control" rows="3" placeholder="Uraikan fakta ketidaksesuaian yang ditemukan di lapangan..." required></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold text-dark">Rekomendasi Tindakan Korektif</label>
                            <textarea name="recommendation" class="form-control" rows="2" placeholder="Langkah perbaikan wajib yang harus dilakukan oleh pihak penanggung jawab..."></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Pihak Penanggung Jawab (Responsible Party)</label>
                            <input type="text" name="responsible_party" class="form-control" value="Penyedia Jasa Konstruksi" placeholder="Contoh: PT. Kontraktor / Konsultan Supervisi">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Batas Waktu Penyelesaian (Due Date) <span class="text-danger">*</span></label>
                            <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+3 days')) ?>" required>
                        </div>

                        <div class="col-12">
                            <div class="p-3 bg-light rounded border">
                                <label class="form-label fw-semibold text-dark mb-1 d-flex align-items-center gap-1">
                                    <i class="bx bx-camera text-primary"></i> Lampiran Foto Bukti Temuan (Evidence)
                                </label>
                                <input type="file" name="file_evidence" class="form-control" accept="image/*,.pdf">
                                <div class="form-text small text-muted">Format gambar JPG, PNG, atau dokumen PDF (Maks. 5 MB).</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSubmitFinding" class="btn btn-primary d-flex align-items-center gap-1">
                        <i class="bx bx-save"></i> Simpan Temuan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
