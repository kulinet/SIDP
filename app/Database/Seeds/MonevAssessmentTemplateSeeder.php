<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MonevAssessmentTemplateSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        $templateCode = 'TMPL-KINERJA-PENYEDIA-V1';
        $templateName = 'Kinerja Penyedia Jasa Konstruksi';
        $templateVersion = '1.0';

        // 1. Check or Insert Template (Idempotent)
        $existing = $db->table('monev_assessment_templates')
            ->where('kode_template', $templateCode)
            ->get()
            ->getRowArray();

        $templateData = [
            'kode_template'    => $templateCode,
            'nama_template'    => $templateName,
            'versi'            => $templateVersion,
            'is_active'        => 1,
            'deskripsi'        => 'Master template standar penilaian kinerja penyedia jasa konstruksi dengan 6 aspek dan 12 indikator.',
            'threshold_config' => null, // Configurable, tidak di-hardcode sesuai aturan bisnis
            'updated_at'       => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $db->table('monev_assessment_templates')
                ->where('id', $existing['id'])
                ->update($templateData);
            $templateId = (int) $existing['id'];
        } else {
            // Nonaktifkan template lain jika ada
            $db->table('monev_assessment_templates')->where('is_active', 1)->update(['is_active' => 0]);
            
            $templateData['created_at'] = date('Y-m-d H:i:s');
            $db->table('monev_assessment_templates')->insert($templateData);
            $templateId = (int) $db->insertID();
        }

        // Pastikan template ini menjadi satu-satunya yang aktif
        $db->table('monev_assessment_templates')
            ->where('id !=', $templateId)
            ->update(['is_active' => 0]);

        // 2. Data 6 Aspek dan 12 Indikator
        $structure = [
            [
                'kode_aspek'   => 'ASP-01',
                'nama_aspek'   => 'ADMINISTRASI',
                'urutan'       => 1,
                'bobot_persen' => 15.00,
                'deskripsi'    => 'Ketertiban dokumen administrasi, izin, perizinan, fasilitas kantor direksi dan gudang.',
                'indicators'   => [
                    [
                        'kode_indikator'       => 'IND-01',
                        'nama_indikator'       => 'Ketepatan dan kelengkapan dalam memenuhi administrasi pekerjaan sesuai kontrak, antara lain Time Schedule, Shop Drawing, As Built Drawing, laporan-laporan, Buku Tamu, Buku Direksi, Buku Bahan, Buku Tenaga, perizinan, dan dokumen administrasi lainnya.',
                        'urutan'               => 1,
                        'deskripsi_kriteria'   => 'Kelengkapan dan ketepatan penyusunan seluruh dokumen administrasi teknis pekerjaan.',
                        'panduan_nilai_1'      => 'Dokumen administrasi tidak lengkap atau tidak diserahkan sesuai ketentuan kontrak.',
                        'panduan_nilai_2'      => 'Dokumen administrasi diserahkan tetapi terdapat keterlambatan atau catatan perbaikan minor.',
                        'panduan_nilai_3'      => 'Dokumen administrasi lengkap, tepat waktu, dan tertib sesuai seluruh ketentuan kontrak.',
                        'evidence_requirement' => 'Dokumen Time Schedule, Shop Drawing, As Built Drawing, Laporan Berkala, Buku Direksi/Tamu/Bahan/Tenaga, Izin Kerja.',
                    ],
                    [
                        'kode_indikator'       => 'IND-02',
                        'nama_indikator'       => 'Kelengkapan kantor administrasi seperti gudang, kantor direksi, papan nama, dan fasilitas administrasi lainnya.',
                        'urutan'               => 2,
                        'deskripsi_kriteria'   => 'Ketersediaan dan kelayakan fasilitas penunjang operasional kantor dan penyimpanan di lokasi pekerjaan.',
                        'panduan_nilai_1'      => 'Fasilitas administrasi lapangan (gudang, kantor direksi, papan nama) tidak tersedia atau sangat tidak layak.',
                        'panduan_nilai_2'      => 'Fasilitas administrasi tersedia namun kurang lengkap atau terdapat kekurangan minor.',
                        'panduan_nilai_3'      => 'Seluruh fasilitas administrasi lapangan (gudang, kantor direksi, papan nama) lengkap, layak, dan terawat.',
                        'evidence_requirement' => 'Foto papan nama proyek, foto kantor direksi keet, foto gudang material/peralatan lapangan.',
                    ],
                ]
            ],
            [
                'kode_aspek'   => 'ASP-02',
                'nama_aspek'   => 'JADWAL DAN WAKTU',
                'urutan'       => 2,
                'bobot_persen' => 25.00,
                'deskripsi'    => 'Kepatuhan jangka waktu kontrak dan kesesuaian progres fisik terhadap Kurva S.',
                'indicators'   => [
                    [
                        'kode_indikator'       => 'IND-03',
                        'nama_indikator'       => 'Pelaksanaan pekerjaan sesuai jangka waktu pelaksanaan yang ditetapkan dalam kontrak.',
                        'urutan'               => 1,
                        'deskripsi_kriteria'   => 'Kepatuhan milestone waktu penyelesaian pekerjaan terhadap kalender kontrak kerja.',
                        'panduan_nilai_1'      => 'Pelaksanaan pekerjaan melewati batas waktu kontrak tanpa justifikasi/adendum yang sah.',
                        'panduan_nilai_2'      => 'Pelaksanaan berpotensi terlambat tetapi ada upaya percepatan atau perpanjangan waktu yang sah.',
                        'panduan_nilai_3'      => 'Pelaksanaan pekerjaan selesai tepat waktu atau lebih awal sesuai jadwal kontrak.',
                        'evidence_requirement' => 'Jadwal pelaksanaan kontrak, SPMK, Berita Acara Progres / Serah Terima Pekerjaan.',
                    ],
                    [
                        'kode_indikator'       => 'IND-04',
                        'nama_indikator'       => 'Progress/prestasi pekerjaan sesuai jadwal dan tidak ada keterlambatan berdasarkan Time Schedule/Kurva S.',
                        'urutan'               => 2,
                        'deskripsi_kriteria'   => 'Deviasi kumulatif realisasi progres fisik terhadap kurva baseline rencana.',
                        'panduan_nilai_1'      => 'Deviasi realisasi fisik terhadap Kurva S mengalami keterlambatan signifikan (deviasi negatif berat / kritis).',
                        'panduan_nilai_2'      => 'Terjadi keterlambatan minor (deviasi negatif ringan) namun dalam batas toleransi wajar.',
                        'panduan_nilai_3'      => 'Progres fisik selalu sesuai atau melebihi target Kurva S (deviasi nol atau positif).',
                        'evidence_requirement' => 'Kurva S baseline vs aktual, laporan mingguan pengawas/konsultan, justifikasi teknis.',
                    ],
                ]
            ],
            [
                'kode_aspek'   => 'ASP-03',
                'nama_aspek'   => 'MATERIAL',
                'urutan'       => 3,
                'bobot_persen' => 20.00,
                'deskripsi'    => 'Ketersediaan kuantitas bahan dan kesesuaian mutu material terhadap spesifikasi teknis.',
                'indicators'   => [
                    [
                        'kode_indikator'       => 'IND-05',
                        'nama_indikator'       => 'Ketersediaan bahan selama pelaksanaan pekerjaan terpenuhi.',
                        'urutan'               => 1,
                        'deskripsi_kriteria'   => 'Kelancaran rantai pasok material di lokasi proyek untuk mendukung ritme kerja.',
                        'panduan_nilai_1'      => 'Bahan sering terlambat atau kosong di lapangan sehingga menghambat jalannya pekerjaan.',
                        'panduan_nilai_2'      => 'Ketersediaan bahan cukup memadai meskipun sesekali terjadi kendala pasokan minor.',
                        'panduan_nilai_3'      => 'Ketersediaan bahan selalu tercukupi tepat waktu dan tidak pernah mengganggu kontinuitas pekerjaan.',
                        'evidence_requirement' => 'Buku penerimaan bahan/material, catatan stok material di gudang lapangan.',
                    ],
                    [
                        'kode_indikator'       => 'IND-06',
                        'nama_indikator'       => 'Bahan yang digunakan sesuai dengan spesifikasi teknis.',
                        'urutan'               => 2,
                        'deskripsi_kriteria'   => 'Kesesuaian standar mutu, merk, dan spesifikasi material terhadap RKS kontrak.',
                        'panduan_nilai_1'      => 'Bahan tidak sesuai spesifikasi teknis kontrak atau ditolak saat uji mutu/lab.',
                        'panduan_nilai_2'      => 'Bahan secara umum sesuai spesifikasi namun ada sebagian item yang memerlukan penyesuaian/uji ulang.',
                        'panduan_nilai_3'      => 'Seluruh bahan yang digunakan 100% lulus uji spesifikasi teknis, memiliki sertifikat mutu/hasil uji lab.',
                        'evidence_requirement' => 'Mill certificate pabrikan, hasil uji laboratorium terakreditasi, Berita Acara Approval Material.',
                    ],
                ]
            ],
            [
                'kode_aspek'   => 'ASP-04',
                'nama_aspek'   => 'TENAGA KERJA DAN PERALATAN',
                'urutan'       => 4,
                'bobot_persen' => 20.00,
                'deskripsi'    => 'Kecukupan jumlah, kompetensi tenaga kerja, serta kelaikan kapasitas peralatan kerja.',
                'indicators'   => [
                    [
                        'kode_indikator'       => 'IND-07',
                        'nama_indikator'       => 'Jumlah tenaga kerja dan peralatan selama waktu pelaksanaan pekerjaan terpenuhi.',
                        'urutan'               => 1,
                        'deskripsi_kriteria'   => 'Pemenuhan kuantitas personel dan armada alat sesuai rencana mobilisasi.',
                        'panduan_nilai_1'      => 'Jumlah personel dan alat di lapangan sangat kurang dibanding kebutuhan rencana kerja.',
                        'panduan_nilai_2'      => 'Jumlah tenaga kerja dan alat cukup memadai meskipun pada item tertentu terdapat kekurangan minor.',
                        'panduan_nilai_3'      => 'Jumlah tenaga kerja dan peralatan terpenuhi penuh sesuai alokasi rencana kerja.',
                        'evidence_requirement' => 'Daftar hadir harian tenaga kerja, log harian penggunaan alat berat/peralatan kerja.',
                    ],
                    [
                        'kode_indikator'       => 'IND-08',
                        'nama_indikator'       => 'Kemampuan/keahlian tenaga kerja sesuai ketentuan kontrak dan struktur organisasi penawaran.',
                        'urutan'               => 2,
                        'deskripsi_kriteria'   => 'Kesesuaian kualifikasi dan kehadiran personel inti/ahli sesuai dokumen penawaran.',
                        'panduan_nilai_1'      => 'Tenaga ahli/terampil yang diajukan dalam penawaran tidak berada di lapangan atau tidak kompeten.',
                        'panduan_nilai_2'      => 'Tenaga kerja berkompeten namun sebagian personel kunci digantikan tanpa persetujuan formal lengkap.',
                        'panduan_nilai_3'      => 'Seluruh tenaga kerja dan personel manajerial/ahli memiliki sertifikat kompetensi (SKA/SKT) dan bekerja sesuai struktur organisasi kontrak.',
                        'evidence_requirement' => 'Sertifikat SKA/SKT tenaga ahli/terampil, struktur organisasi proyek, SK penugasan personel inti.',
                    ],
                    [
                        'kode_indikator'       => 'IND-09',
                        'nama_indikator'       => 'Kapasitas dan jenis peralatan sesuai ketentuan kontrak.',
                        'urutan'               => 3,
                        'deskripsi_kriteria'   => 'Kelaikan fungsi, kapasitas daya, dan kesesuaian jenis peralatan utama.',
                        'panduan_nilai_1'      => 'Peralatan yang dimobilisasi tidak sesuai jenis dan kapasitasnya, atau dalam kondisi rusak/tidak layak operasi.',
                        'panduan_nilai_2'      => 'Peralatan sesuai ketentuan kontrak namun sesekali mengalami breakdown/kerusakan ringan.',
                        'panduan_nilai_3'      => 'Seluruh peralatan sesuai kapasitas dan spesifikasi kontrak, memiliki bukti laik operasi/SIA, dan beroperasi prima.',
                        'evidence_requirement' => 'Bukti kepemilikan/sewa alat, Surat Izin Alat (SIA), foto alat beroperasi di lokasi.',
                    ],
                ]
            ],
            [
                'kode_aspek'   => 'ASP-05',
                'nama_aspek'   => 'KESELAMATAN DAN KESEHATAN KERJA',
                'urutan'       => 5,
                'bobot_persen' => 10.00,
                'deskripsi'    => 'Penerapan Sistem Manajemen Keselamatan Konstruksi (SMKK) dan perlengkapan APD/APK.',
                'indicators'   => [
                    [
                        'kode_indikator'       => 'IND-10',
                        'nama_indikator'       => 'Kelengkapan K3 selama pelaksanaan pekerjaan terpenuhi, termasuk peralatan/bahan K3, pakaian kerja, sepatu keselamatan, helm, rambu-rambu, alat pengaman, dan catatan kejadian.',
                        'urutan'               => 1,
                        'deskripsi_kriteria'   => 'Kedisiplinan dan kelengkapan fasilitas keselamatan kerja di seluruh area konstruksi.',
                        'panduan_nilai_1'      => 'Rambu dan APD K3 tidak disediakan atau pekerja tidak memakai perlengkapan keselamatan standar.',
                        'panduan_nilai_2'      => 'Peralatan K3 tersedia namun kedisiplinan pemakaian APD di lapangan masih perlu ditingkatkan.',
                        'panduan_nilai_3'      => 'Penerapan K3 lengkap dan tertib (APD, rambu, pagar pengaman, kotak P3K, buku catatan insiden / zero accident).',
                        'evidence_requirement' => 'Foto penerapan APD pekerja, foto rambu K3 di lokasi, buku catatan kejadian/kecelakaan kerja, dokumen RKK.',
                    ],
                ]
            ],
            [
                'kode_aspek'   => 'ASP-06',
                'nama_aspek'   => 'LINGKUNGAN',
                'urutan'       => 6,
                'bobot_persen' => 10.00,
                'deskripsi'    => 'Komunikasi sosial dengan warga sekitar dan mitigasi dampak lingkungan pekerjaan.',
                'indicators'   => [
                    [
                        'kode_indikator'       => 'IND-11',
                        'nama_indikator'       => 'Adanya sosialisasi/pemberitahuan kepada lingkungan sekitar pekerjaan.',
                        'urutan'               => 1,
                        'deskripsi_kriteria'   => 'Pelaksanaan sosialisasi dan koordinasi dengan komunitas/warga sekitar area proyek.',
                        'panduan_nilai_1'      => 'Tidak pernah ada pemberitahuan atau koordinasi dengan warga dan pihak sekitar lokasi proyek.',
                        'panduan_nilai_2'      => 'Ada pemberitahuan lisan namun dokumentasi administratif sosialisasi tidak lengkap.',
                        'panduan_nilai_3'      => 'Sosialisasi resmi telah dilakukan dengan baik dan didukung berita acara/surat pemberitahuan kepada warga dan aparat setempat.',
                        'evidence_requirement' => 'Surat pemberitahuan lingkungan, daftar hadir sosialisasi, berita acara izin lingkungan setempat.',
                    ],
                    [
                        'kode_indikator'       => 'IND-12',
                        'nama_indikator'       => 'Tidak ada komplain/permasalahan dengan lingkungan sekitar.',
                        'urutan'               => 2,
                        'deskripsi_kriteria'   => 'Pengelolaan kebersihan, ketertiban, dan ketiadaan keluhan warga sekitar pekerjaan.',
                        'panduan_nilai_1'      => 'Terjadi komplain keras/konflik dengan lingkungan sekitar yang tidak diselesaikan atau menghentikan pekerjaan.',
                        'panduan_nilai_2'      => 'Terdapat komplain minor (misal debu/kebisingan) namun segera ditangani dan diselesaikan oleh penyedia.',
                        'panduan_nilai_3'      => 'Tidak ada keluhan/komplain dari masyarakat, lokasi bersih dan dampak lingkungan terkelola dengan sangat baik.',
                        'evidence_requirement' => 'Surat keterangan tidak ada sengketa dari kepala desa/kelurahan setempat, foto mitigasi dampak lingkungan.',
                    ],
                ]
            ],
        ];

        // 3. Upsert Aspek & Indikator
        foreach ($structure as $aspData) {
            $existingAspect = $db->table('monev_assessment_aspects')
                ->where('template_id', $templateId)
                ->where('kode_aspek', $aspData['kode_aspek'])
                ->get()
                ->getRowArray();

            $aspectPayload = [
                'template_id'  => $templateId,
                'urutan'       => $aspData['urutan'],
                'kode_aspek'   => $aspData['kode_aspek'],
                'nama_aspek'   => $aspData['nama_aspek'],
                'bobot_persen' => $aspData['bobot_persen'],
                'deskripsi'    => $aspData['deskripsi'],
            ];

            if ($existingAspect) {
                $db->table('monev_assessment_aspects')
                    ->where('id', $existingAspect['id'])
                    ->update($aspectPayload);
                $aspectId = (int) $existingAspect['id'];
            } else {
                $aspectPayload['created_at'] = date('Y-m-d H:i:s');
                $db->table('monev_assessment_aspects')->insert($aspectPayload);
                $aspectId = (int) $db->insertID();
            }

            foreach ($aspData['indicators'] as $indData) {
                $existingIndicator = $db->table('monev_assessment_indicators')
                    ->where('aspect_id', $aspectId)
                    ->where('kode_indikator', $indData['kode_indikator'])
                    ->get()
                    ->getRowArray();

                $indicatorPayload = [
                    'aspect_id'            => $aspectId,
                    'urutan'               => $indData['urutan'],
                    'kode_indikator'       => $indData['kode_indikator'],
                    'nama_indikator'       => $indData['nama_indikator'],
                    'deskripsi_kriteria'   => $indData['deskripsi_kriteria'],
                    'panduan_nilai_1'      => $indData['panduan_nilai_1'],
                    'panduan_nilai_2'      => $indData['panduan_nilai_2'],
                    'panduan_nilai_3'      => $indData['panduan_nilai_3'],
                    'evidence_requirement' => $indData['evidence_requirement'],
                ];

                if ($existingIndicator) {
                    $db->table('monev_assessment_indicators')
                        ->where('id', $existingIndicator['id'])
                        ->update($indicatorPayload);
                } else {
                    $indicatorPayload['created_at'] = date('Y-m-d H:i:s');
                    $db->table('monev_assessment_indicators')->insert($indicatorPayload);
                }
            }
        }
    }
}
