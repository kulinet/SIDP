<!-- Modal Tambah Tindakan Perbaikan (Follow-up / Action Item) -->
<div class="modal fade" id="modalAddFollowup" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark py-3">
                <h5 class="modal-title d-flex align-items-center gap-2 fw-bold text-dark" id="modalAddFollowupTitle">
                    <i class="bx bx-wrench fs-4"></i> Catat Tindakan Perbaikan (Follow-up)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAddFollowup" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="finding_id" id="followup_finding_id" value="">
                    
                    <div class="alert alert-info py-2 px-3 mb-3 border-0 small">
                        <strong>Temuan Terkait:</strong> <span id="followup_finding_label" class="fw-semibold">-</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Rencana / Tindakan Perbaikan (Action) <span class="text-danger">*</span></label>
                        <textarea name="action" id="followup_action" class="form-control" rows="3" placeholder="Uraikan aksi konkret yang dilaksanakan untuk menyelesaikan temuan..." required></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Penanggung Jawab (PIC)</label>
                            <input type="text" name="pic" id="followup_pic" class="form-control" placeholder="Contoh: Mandor Utama / Site Manager">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Target Tanggal Selesai (Deadline) <span class="text-danger">*</span></label>
                            <input type="date" name="deadline" id="followup_deadline" class="form-control" value="<?= date('Y-m-d', strtotime('+2 days')) ?>" required>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded border mb-3">
                        <label class="form-label fw-semibold text-dark mb-1 d-flex align-items-center gap-1">
                            <i class="bx bx-image text-warning"></i> Unggah Foto Bukti Perbaikan
                        </label>
                        <input type="file" name="file_evidence" class="form-control" accept="image/*,.pdf">
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSubmitFollowup" class="btn btn-warning text-dark fw-bold d-flex align-items-center gap-1">
                        <i class="bx bx-check-circle"></i> Simpan Tindak Lanjut
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Verifikasi Follow-up (Reviewer / Admin) -->
<div class="modal fade" id="modalVerifyFollowup" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white py-3">
                <h5 class="modal-title text-white d-flex align-items-center gap-2" id="modalVerifyFollowupTitle">
                    <i class="bx bx-badge-check fs-4"></i> Verifikasi Tindakan Perbaikan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formVerifyFollowup" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="followup_id" id="verify_followup_id" value="">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Keputusan Verifikasi <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="decision" id="decisionApprove" value="APPROVE" checked>
                                <label class="form-check-label fw-bold text-success" for="decisionApprove">
                                    <i class="bx bx-check"></i> DITERIMA (Telah Memenuhi Syarat)
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="decision" id="decisionReject" value="REJECT">
                                <label class="form-check-label fw-bold text-danger" for="decisionReject">
                                    <i class="bx bx-x"></i> DITOLAK (Perlu Perbaikan Ulang)
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Catatan Verifikasi Penilai / Reviewer</label>
                        <textarea name="notes" id="verify_notes" class="form-control" rows="3" placeholder="Tuliskan catatan hasil pemeriksaan bukti perbaikan di lapangan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSubmitVerify" class="btn btn-success d-flex align-items-center gap-1">
                        <i class="bx bx-save"></i> Simpan Hasil Verifikasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
