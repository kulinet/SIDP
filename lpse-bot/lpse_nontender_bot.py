"""
Bot rekap NON TENDER LPSE (SPSE) -> Excel, versi requests (tanpa browser).

Sama dengan lpse_bot.py, hanya berbeda halaman/endpoint:
  daftar : /nontender?tahun=...        (AJAX: /dt/pl)
  tab    : Pengumuman, Jadwal, Peserta, Hasil Evaluasi, Pemenang, Pemenang Berkontrak

Contoh:
  python lpse_nontender_bot.py --limit 10
  python lpse_nontender_bot.py --tahun 2025 --output nontender.xlsx
"""
import lpse_bot
from lpse_bot import LpseBot


class NonTenderConfig:
    LIST_PATH = "/nontender"
    DT_PATH = "/dt/pl"
    ID_LABEL = "Kode Paket"
    OUT_PREFIX = "Rekap_NonTender"
    COLUMNS = ["Kode Paket", "Nama Paket", "Instansi", "Tahap", "HPS (ringkas)",
               "Metode Pengadaan", "Kategori - TA", "Versi SPSE", "Nilai Kontrak"]
    TABS = {
        "Pengumuman": "/nontender/{id}/pengumumanpl",
        "Jadwal": "/nontender/{id}/jadwal",
        "Peserta": "/nontender/{id}/peserta",
        "Hasil Evaluasi": "/evaluasinontender/{id}/hasil",
        "Pemenang": "/evaluasinontender/{id}/pemenang",
        "Pemenang Berkontrak": "/evaluasinontender/{id}/pemenangberkontrak",
    }


class NonTenderBot(NonTenderConfig, LpseBot):
    pass


if __name__ == "__main__":
    lpse_bot.main(NonTenderBot)
