<!-- Modal Upload Bukti Lapangan Monev V2 -->
<div class="modal fade" id="modalUploadEvidence" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title text-white d-flex align-items-center gap-2" id="modalUploadEvidenceTitle">
                    <i class="bx bx-upload fs-4"></i> Unggah Bukti Lapangan Monev
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formUploadEvidence" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="entity_type" value="MONEV_PERIOD">
                    <input type="hidden" name="entity_id" value="<?= $activePeriod['id'] ?? 0 ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Kategori Bukti <span class="text-danger">*</span></label>
                        <select name="jenis_eviden" class="form-select" required>
                            <option value="FOTO_LAPANGAN">Foto Lapangan / Dokumentasi Fisik</option>
                            <option value="DOKUMEN_ADMINISTRASI">Dokumen Administrasi / Pengujian</option>
                            <option value="BERITA_ACARA">Berita Acara / Surat Resmi</option>
                            <option value="LAINNYA">Lain-lain</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Keterangan / Catatan Bukti <span class="text-danger">*</span></label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Uraikan lokasi dan deskripsi objek bukti..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold text-dark mb-0">
                                <i class="bx bx-map-pin text-danger me-1"></i> Koordinat Geotagging
                            </label>
                            <button type="button" id="btnGetEvidenceGps" class="btn btn-sm btn-outline-primary py-1 px-2 d-flex align-items-center gap-1 shadow-sm">
                                <i class="bx bx-crosshair"></i> Ambil Geo Tag (GPS)
                            </button>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light text-muted" style="font-size: 0.75rem;">Lat</span>
                                    <input type="text" name="gps_lat" id="evidence_lat" class="form-control form-control-sm font-monospace" placeholder="-8.6812345">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light text-muted" style="font-size: 0.75rem;">Lng</span>
                                    <input type="text" name="gps_lng" id="evidence_lng" class="form-control form-control-sm font-monospace" placeholder="116.1245678">
                                </div>
                            </div>
                        </div>
                        <div id="gpsStatusFeedback" class="form-text mt-1 text-muted" style="font-size: 0.75rem;">
                            Klik "Ambil Geo Tag (GPS)" untuk mendeteksi koordinat lokasi saat ini secara otomatis.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Pilih Berkas Bukti <span class="text-danger">*</span></label>
                        <input type="file" name="file_evidence" class="form-control" accept="image/*,.pdf" required>
                        <small class="text-muted">Format: JPG, PNG, WEBP, atau PDF. Maksimal 10MB.</small>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSubmitEvidence" class="btn btn-primary d-flex align-items-center gap-1">
                        <i class="bx bx-upload"></i> Unggah Bukti
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
