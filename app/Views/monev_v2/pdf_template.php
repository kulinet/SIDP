<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laporan Hasil Monitoring & Evaluasi Proyek</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0;
            color: #2c3e50;
            font-size: 16pt;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 10pt;
            color: #7f8c8d;
        }
        .section-title {
            background-color: #ecf0f1;
            padding: 5px 10px;
            font-weight: bold;
            font-size: 11pt;
            margin-top: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #3498db;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.border th, table.border td {
            border: 1px solid #bdc3c7;
            padding: 6px;
        }
        table.border th {
            background-color: #f7f9fa;
            text-align: center;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .badge {
            padding: 3px 6px;
            font-size: 8pt;
            border-radius: 3px;
            color: white;
            font-weight: bold;
        }
        .bg-success { background-color: #2ecc71; }
        .bg-warning { background-color: #f1c40f; color: #333; }
        .bg-danger { background-color: #e74c3c; }
        .bg-primary { background-color: #3498db; }
        .qr-container {
            float: right;
            width: 100px;
            text-align: center;
            font-size: 8pt;
        }
        .footer {
            margin-top: 30px;
            font-size: 9pt;
        }
        .signature-box {
            width: 200px;
            text-align: center;
            float: left;
            margin-top: 20px;
            margin-right: 50px;
        }
        .signature-line {
            border-bottom: 1px solid #333;
            margin-top: 50px;
            margin-bottom: 5px;
        }
        .clear { clear: both; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>

    <div class="header">
        <h2>LAPORAN HASIL MONITORING & EVALUASI</h2>
        <p>Sistem Informasi Data Proyek (Infoajip) V2</p>
    </div>

    <div style="float: right; text-align: right;">
        <span class="badge bg-primary">Dokumen Final: <?= esc($doc_number) ?></span><br>
        <span style="font-size: 8pt; color: #7f8c8d;">Tanggal Finalisasi: <?= date('d M Y H:i', strtotime($finalized_at)) ?></span><br>
        <span style="font-size: 8pt; color: #7f8c8d;">Status: IMMUTABLE (LOCKED)</span>
    </div>
    <div style="clear: both;"></div>

    <div class="section-title">A. INFORMASI PROYEK & KONTRAK</div>
    <table>
        <tr>
            <td width="20%" style="color: #7f8c8d;">Nama Proyek</td>
            <td width="2%">:</td>
            <td width="78%"><strong><?= esc($contract['nama_proyek']) ?></strong></td>
        </tr>
        <tr>
            <td style="color: #7f8c8d;">Nomor Kontrak</td>
            <td>:</td>
            <td><?= esc($contract['no_kontrak']) ?></td>
        </tr>
        <tr>
            <td style="color: #7f8c8d;">Nilai Kontrak</td>
            <td>:</td>
            <td>Rp <?= number_format((float) $contract['nilai_kontrak'], 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td style="color: #7f8c8d;">Penyedia Jasa</td>
            <td>:</td>
            <td><strong><?= esc($contract['penyedia']) ?></strong></td>
        </tr>
        <tr>
            <td style="color: #7f8c8d;">Waktu Pelaksanaan</td>
            <td>:</td>
            <td><?= date('d/m/Y', strtotime($contract['tgl_mulai'])) ?> s.d <?= date('d/m/Y', strtotime($contract['tgl_selesai'])) ?></td>
        </tr>
    </table>

    <div class="section-title">B. INFORMASI PERIODE MONEV</div>
    <table>
        <tr>
            <td width="20%" style="color: #7f8c8d;">Nomor Sesi Monev</td>
            <td width="2%">:</td>
            <td width="38%"><?= esc($period['nomor_monev']) ?> (Periode #<?= $period['periode_ke'] ?>)</td>
            <td width="20%" style="color: #7f8c8d;">Tanggal Evaluasi</td>
            <td width="2%">:</td>
            <td width="18%"><?= date('d M Y', strtotime($period['tgl_rencana_monev'])) ?></td>
        </tr>
        <tr>
            <td style="color: #7f8c8d;">Petugas Assessor</td>
            <td>:</td>
            <td><?= esc($period['nama_assessor'] ?? '-') ?></td>
            <td style="color: #7f8c8d;">Skor Kinerja Akhir</td>
            <td>:</td>
            <td><strong><?= number_format((float) $period['skor_kinerja_total'], 1) ?> / 36</strong></td>
        </tr>
    </table>

    <div class="section-title">C. REKAP PROGRESS FISIK (Berdasarkan Log Terverifikasi)</div>
    <table class="border">
        <tr>
            <th>Progress Rencana</th>
            <th>Progress Aktual</th>
            <th>Deviasi (Kurva S)</th>
            <th>Status Keterlambatan</th>
        </tr>
        <tr>
            <td class="text-center"><?= number_format((float) $progress['progress_rencana'], 2) ?>%</td>
            <td class="text-center"><strong><?= number_format((float) $progress['progress_aktual_verified'], 2) ?>%</strong></td>
            <?php $dev = (float) $progress['deviasi']; ?>
            <td class="text-center" style="color: <?= $dev < 0 ? '#e74c3c' : '#2ecc71' ?>;">
                <?= ($dev > 0 ? '+' : '') . number_format($dev, 2) ?>%
            </td>
            <td class="text-center"><?= esc($progress['status_proyek']) ?></td>
        </tr>
    </table>

    <div class="section-title">D. HASIL PENILAIAN ASPEK (6 Aspek, 12 Indikator)</div>
    <table class="border">
        <tr>
            <th width="30%">Aspek & Indikator</th>
            <th width="15%">Rating</th>
            <th width="55%">Catatan Pemeriksaan (Evidence Reference)</th>
        </tr>
        <?php foreach ($assessment['aspects'] as $asp): ?>
            <tr>
                <td colspan="3" style="background-color: #f1f2f6; font-weight: bold;"><?= esc($asp['nama_aspek']) ?></td>
            </tr>
            <?php foreach ($asp['indicators'] as $ind): 
                // Find matching rated item
                $ratedItem = null;
                foreach ($assessment['items'] as $it) {
                    if ($it['indicator_id'] == $ind['id']) {
                        $ratedItem = $it;
                        break;
                    }
                }
                
                $rating = $ratedItem['rating'] ?? 'N/A';
                $ratingText = str_replace('_', ' ', $rating);
                $color = '#333';
                if ($rating == 'SANGAT_BAIK') $color = '#2ecc71';
                if ($rating == 'BAIK') $color = '#f39c12';
                if ($rating == 'TIDAK_BAIK') $color = '#e74c3c';
            ?>
            <tr>
                <td style="font-size: 9pt;">[<?= esc($ind['kode_indikator']) ?>] <?= esc($ind['nama_indikator']) ?></td>
                <td class="text-center" style="color: <?= $color ?>; font-weight: bold; font-size: 9pt;"><?= esc($ratingText) ?></td>
                <td style="font-size: 9pt;"><?= nl2br(esc($ratedItem['catatan_penilai'] ?? '-')) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </table>

    <!-- Page Break before findings -->
    <div class="page-break"></div>

    <div class="section-title">E. DAFTAR TEMUAN MASALAH (FINDINGS) & TINDAK LANJUT</div>
    <?php if (empty($findings)): ?>
        <p style="text-align: center; color: #7f8c8d; padding: 20px;"><em>Tidak ada temuan lapangan yang dicatat pada sesi Monev ini.</em></p>
    <?php else: ?>
        <?php foreach ($findings as $f): ?>
            <div style="border: 1px solid #bdc3c7; padding: 10px; margin-bottom: 10px;">
                <div style="border-bottom: 1px solid #ecf0f1; padding-bottom: 5px; margin-bottom: 5px;">
                    <strong>[<?= esc($f['kode_temuan']) ?>] <?= esc($f['title'] ?? $f['deskripsi_temuan']) ?></strong>
                    <div style="float: right;">
                        <span class="badge bg-danger"><?= esc($f['severity']) ?></span>
                        <span class="badge bg-warning"><?= esc($f['status']) ?></span>
                    </div>
                </div>
                <div style="clear: both;"></div>
                <p style="margin: 5px 0; font-size: 9pt;"><strong>Deskripsi:</strong> <?= nl2br(esc($f['deskripsi_temuan'])) ?></p>
                <p style="margin: 5px 0; font-size: 9pt;"><strong>Rekomendasi:</strong> <?= nl2br(esc($f['rekomendasi_tindakan'])) ?></p>
                <p style="margin: 5px 0; font-size: 9pt;"><strong>Batas Waktu:</strong> <?= date('d/m/Y', strtotime($f['batas_waktu_penyelesaian'] ?? $f['due_date'])) ?></p>
                
                <?php if (!empty($f['followups'])): ?>
                    <table class="border" style="margin-top: 10px; font-size: 8pt;">
                        <tr>
                            <th>Aksi Tindak Lanjut</th>
                            <th>Status</th>
                            <th>Catatan Verifikasi</th>
                        </tr>
                        <?php foreach ($f['followups'] as $fup): ?>
                        <tr>
                            <td><?= esc($fup['deskripsi_perbaikan'] ?? $fup['action']) ?></td>
                            <td class="text-center"><?= esc($fup['status']) ?></td>
                            <td><?= esc($fup['verification_notes'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="section-title">F. KESIMPULAN & REKOMENDASI ASSESSOR</div>
    <div style="border: 1px solid #bdc3c7; padding: 10px; margin-bottom: 15px; background-color: #f9f9f9;">
        <p style="margin: 0; font-size: 10pt;"><?= nl2br(esc($period['kesimpulan_rekomendasi'])) ?></p>
    </div>

    <!-- Signatures and QR -->
    <div class="footer">
        <div class="qr-container">
            <!-- Render SVG directly -->
            <?= $qr_svg ?>
            <div style="margin-top: 5px; word-break: break-all;">Hash: <?= substr($hash, 0, 16) ?>...</div>
        </div>

        <div class="signature-box">
            Mengetahui,<br>
            <strong>Pejabat Pembuat Komitmen (PPK)</strong>
            <div class="signature-line"></div>
            <?= esc($contract['ppk']['nama_lengkap'] ?? '..................................') ?>
        </div>

        <div class="signature-box">
            Dilaksanakan Oleh,<br>
            <strong>Petugas Assessor Monev</strong>
            <div class="signature-line"></div>
            <?= esc($period['nama_assessor'] ?? '..................................') ?>
        </div>
        <div class="clear"></div>
        <div style="margin-top: 30px; text-align: center; color: #7f8c8d; font-size: 8pt;">
            Dokumen ini dihasilkan secara otomatis oleh Sistem Infoajip pada <?= date('d/m/Y H:i:s') ?> dan tidak dapat diubah secara retroaktif.<br>
            Untuk memverifikasi keaslian dokumen, pindai kode QR atau periksa hash dokumen di sistem utama.
        </div>
    </div>
</body>
</html>
