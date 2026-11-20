"""
Bot rekap data tender LPSE (SPSE v4 - inaproc) ke Excel.

Alur:
  1. Ambil daftar tender lewat endpoint DataTables (/dt/lelang) -- halaman HTML-nya
     kosong karena datanya dimuat via AJAX, sehingga requests+BeautifulSoup biasa gagal.
  2. Untuk tiap tender, buka semua tab:
       Pengumuman, Jadwal, Peserta, Hasil Evaluasi, Pemenang, Pemenang Berkontrak
  3. Simpan semuanya ke satu file Excel (satu sheet per tab).

Contoh:
  python lpse_bot.py                         # semua tender 2025 Lombok Barat
  python lpse_bot.py --limit 10              # uji coba 10 tender
  python lpse_bot.py --tahun 2024 --delay 1  # tahun lain, jeda 1 detik
"""
import argparse
import re
import sys
import time

import pandas as pd
import requests
from bs4 import BeautifulSoup

try:
    import config
except ImportError:
    class config:
        TAHUN = "2025"
        INSTANSI = "lombokbaratkab"
        DELAY = 0.5
        LIMIT = None
        HEADLESS = False

HEADERS = {
    "User-Agent": ("Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
                   "(KHTML, like Gecko) Chrome/124.0 Safari/537.36"),
    "Accept-Language": "id-ID,id;q=0.9",
}

LIST_COLUMNS = ["Kode Tender", "Nama Paket", "Instansi", "Tahap", "HPS (ringkas)",
                "Metode Kualifikasi", "Jenis", "Metode Evaluasi", "Kategori - TA",
                "Versi SPSE", "Nilai HPS"]

# nama sheet -> path tab (relatif terhadap {base}); {id} = kode tender
TABS = {
    "Pengumuman": "/lelang/{id}/pengumumanlelang",
    "Jadwal": "/lelang/{id}/jadwal",
    "Peserta": "/lelang/{id}/peserta",
    "Hasil Evaluasi": "/evaluasi/{id}/hasil",
    "Pemenang": "/evaluasi/{id}/pemenang",
    "Pemenang Berkontrak": "/evaluasi/{id}/pemenangberkontrak",
}


def clean(el):
    return re.sub(r"\s+", " ", el.get_text(" ", strip=True)).strip()


def unique_names(names):
    """Beri nama unik pada header kosong/duplikat (mis. 'K','K' -> 'K','K (2)')."""
    seen, out = {}, []
    for i, n in enumerate(names):
        n = n or f"Kolom {i+1}"
        seen[n] = seen.get(n, 0) + 1
        out.append(n if seen[n] == 1 else f"{n} ({seen[n]})")
    return out


class LpseBot:
    # --- konfigurasi jenis paket (ditimpa oleh subclass, mis. Non Tender) ---
    LIST_PATH = "/lelang"            # halaman daftar
    DT_PATH = "/dt/lelang"           # endpoint DataTables (AJAX)
    ID_LABEL = "Kode Tender"         # nama kolom kode paket
    OUT_PREFIX = "Rekap_Tender"
    COLUMNS = LIST_COLUMNS
    TABS = TABS

    def __init__(self, instansi, tahun, delay=0.5, retries=3):
        self.base = f"https://spse.inaproc.id/{instansi}"
        self.tahun = tahun
        self.delay = delay
        self.retries = retries
        self.s = requests.Session()
        self.s.headers.update(HEADERS)
        self.list_url = f"{self.base}{self.LIST_PATH}?tahun={tahun}"

    def _get(self, url):
        for n in range(1, self.retries + 1):
            try:
                r = self.s.get(url, timeout=60, headers={"Referer": self.list_url})
                if r.status_code == 200:
                    return r.text
                if r.status_code == 404:
                    return None
            except requests.RequestException:
                pass
            time.sleep(self.delay * n * 2)
        return None

    # ---------- 1. daftar tender ----------
    def list_tenders(self, limit=None, page_size=100):
        html = self.s.get(self.list_url, timeout=60).text
        token = re.search(r"authenticityToken\s*=\s*'(\w+)'", html)
        if not token:
            sys.exit("Token tidak ditemukan; struktur halaman mungkin berubah.")
        token = token.group(1)
        rows, start = [], 0
        while True:
            r = self.s.post(
                f"{self.base}{self.DT_PATH}?tahun={self.tahun}",
                data={"authenticityToken": token, "draw": 1,
                      "start": start, "length": page_size},
                headers={"Referer": self.list_url, "X-Requested-With": "XMLHttpRequest"},
                timeout=60)
            r.raise_for_status()
            data = r.json().get("data", [])
            if not data:
                break
            rows.extend(data)
            print(f"  daftar tender: {len(rows)} terambil", end="\r")
            start += len(data)
            if limit and len(rows) >= limit:
                rows = rows[:limit]
                break
            time.sleep(self.delay)
        print()
        out = []
        for r in rows:
            r = [re.sub(r"<[^>]+>", "", c).strip() if isinstance(c, str) else c for c in r]
            out.append({c: (r[i] if i < len(r) else None)
                        for i, c in enumerate(self.COLUMNS)})
        return pd.DataFrame(out, columns=self.COLUMNS)

    # ---------- 2. parsing tab ----------
    @staticmethod
    def parse_page(html, sheet_name, kode, id_label="Kode Paket"):
        """Mengurai halaman tab SPSE menjadi DataFrame yang rapi dan terstruktur."""
        if not html:
            return pd.DataFrame([{id_label: kode, "Status": "Halaman tidak dapat diakses / 404"}])

        soup = BeautifulSoup(html, "html.parser")
        main = soup.find("main") or soup

        # 1. Tab Pemenang & Pemenang Berkontrak: gabungkan info paket + tabel pemenang
        if "Pemenang" in sheet_name:
            info = {}
            for tr in main.find_all("tr"):
                cells = tr.find_all(["th", "td"], recursive=False)
                if len(cells) == 2 and cells[0].name == "th" and not cells[1].find("table"):
                    k = clean(cells[0])
                    v = clean(cells[1])
                    if k:
                        info[k] = v

            winner_rows, winner_hdr = [], []
            for tb in main.find_all("table"):
                trs = tb.find_all("tr")
                if not trs:
                    continue
                hdr = [clean(th) for th in trs[0].find_all("th")]
                if any("Pemenang" in h for h in hdr):
                    winner_hdr = hdr
                    for tr in trs[1:]:
                        tds = tr.find_all("td")
                        if tds:
                            winner_rows.append([clean(td) for td in tds])
                    break

            if winner_rows:
                w = max(len(winner_hdr), max(len(r) for r in winner_rows))
                hdr = unique_names((winner_hdr + [f"Kolom {i+1}" for i in range(len(winner_hdr), w)])[:w])
                rows = [r + [""] * (w - len(r)) for r in winner_rows]
                df = pd.DataFrame(rows, columns=hdr)
                df.insert(0, id_label, kode)
                for col in ["HPS", "Pagu", "Satuan Kerja", "Nama Paket", "Nama Non Tender", "Nama Tender"]:
                    if col in info:
                        df.insert(1, col, info[col])
                return df
            else:
                row = {id_label: kode, **info, "Status Pemenang": "Belum ada pemenang / belum dibuat"}
                return pd.DataFrame([row])

        # 2. Tab Pengumuman: tabel key-value (1 baris per paket)
        if "Pengumuman" in sheet_name:
            info = {id_label: kode}
            for tr in main.find_all("tr"):
                cells = tr.find_all(["th", "td"], recursive=False)
                if len(cells) == 2 and cells[0].name == "th" and not cells[1].find("table"):
                    k = clean(cells[0])
                    v = clean(cells[1])
                    if k and k not in info:
                        info[k] = v
            return pd.DataFrame([info])

        # 3. Tab Jadwal, Peserta, Hasil Evaluasi: Tabel data (banyak baris per paket)
        for tb in main.find_all("table"):
            trs = tb.find_all("tr")
            if not trs:
                continue
            hdr = [clean(th) for th in trs[0].find_all("th")]
            if len(hdr) >= 2 and not trs[0].find("td"):
                recs = [[clean(td) for td in tr.find_all("td")] for tr in trs[1:] if tr.find_all("td")]
                if recs:
                    w = max(len(hdr), max(len(r) for r in recs))
                    hdr = unique_names((hdr + [f"Kolom {i+1}" for i in range(len(hdr), w)])[:w])
                    recs = [r + [""] * (w - len(r)) for r in recs]
                    df = pd.DataFrame(recs, columns=hdr)
                    df.insert(0, id_label, kode)
                    return df

        return pd.DataFrame([{id_label: kode, "Status": "Tidak ada data pada tabel"}])

    def scrape_tender(self, kode):
        result = {}
        for sheet, path in self.TABS.items():
            html = self._get(self.base + path.format(id=kode))
            time.sleep(self.delay)
            result[sheet] = self.parse_page(html, sheet, kode, self.ID_LABEL)
        return result


def main(bot_cls=None, extra_args=None):
    ap = argparse.ArgumentParser(description="Bot rekap data LPSE ke Excel")
    ap.add_argument("--instansi", default=config.INSTANSI, help=f"slug LPSE (default: {config.INSTANSI})")
    ap.add_argument("--tahun", default=config.TAHUN, help=f"tahun anggaran (default: {config.TAHUN})")
    ap.add_argument("--limit", type=int, default=config.LIMIT, help="batasi jumlah tender/paket (uji coba)")
    ap.add_argument("--delay", type=float, default=config.DELAY, help=f"jeda antar request detik (default: {config.DELAY})")
    ap.add_argument("--output", default=None)
    if extra_args:
        extra_args(ap)
    a = ap.parse_args()

    bot = (bot_cls or LpseBot)(a.instansi, a.tahun, a.delay)
    print("Mengambil daftar paket...")
    df_list = bot.list_tenders(a.limit)
    print(f"Total {len(df_list)} paket. Mengambil detail tiap tab...")

    sheet_rows = {k: [] for k in bot.TABS}
    idc = bot.ID_LABEL
    for n, kode in enumerate(df_list[idc], 1):
        print(f"[{n}/{len(df_list)}] {kode}", end="\r")
        res = bot.scrape_tender(kode)
        for sheet, df in res.items():
            if df is not None and not df.empty:
                sheet_rows[sheet].append(df)
    print()

    out = a.output or f"{bot.OUT_PREFIX}_{a.instansi}_{a.tahun}.xlsx"
    with pd.ExcelWriter(out, engine="openpyxl") as xw:
        df_list.to_excel(xw, sheet_name="Daftar Paket", index=False)
        for sheet in bot.TABS:
            if sheet_rows[sheet]:
                df_sheet = pd.concat(sheet_rows[sheet], ignore_index=True)
                df_sheet.to_excel(xw, sheet_name=sheet[:31], index=False)
        for ws in xw.book.worksheets:  # perapihan lebar kolom
            for col in ws.columns:
                w = max(len(str(c.value)) if c.value is not None else 0 for c in col)
                ws.column_dimensions[col[0].column_letter].width = min(max(w + 2, 10), 60)
            ws.freeze_panes = "A2"
    print(f"Selesai! Data tersimpan di: {out}")


if __name__ == "__main__":
    main()
