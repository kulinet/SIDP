"""
Versi SELENIUM dari bot rekap tender LPSE -> Excel.

Browser Chrome sungguhan dikendalikan otomatis: membuka halaman daftar, mengatur
jumlah baris per halaman, klik 'Next' sampai habis, lalu membuka tiap tab detail
(Pengumuman, Jadwal, Peserta, Hasil Evaluasi, Pemenang, Pemenang Berkontrak).
Parsing tabel & penulisan Excel memakai logika yang sama dengan lpse_bot.py.

Contoh:
  python lpse_bot_selenium.py --limit 10
  python lpse_bot_selenium.py --tahun 2025 --headless
"""
import sys
import time

import pandas as pd
from bs4 import BeautifulSoup
from selenium import webdriver
from selenium.common.exceptions import TimeoutException
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select, WebDriverWait

import lpse_bot
from lpse_bot import LpseBot, clean, config

LIST_COLS = ["Kode Tender", "Nama Paket", "Instansi", "Tahap", "HPS", "Jadwal Pengumuman"]


class SeleniumLpseBot(LpseBot):
    def __init__(self, instansi, tahun, delay=0.5, retries=3):
        super().__init__(instansi, tahun, delay, retries)
        opts = webdriver.ChromeOptions()
        if "--headless" in sys.argv or getattr(config, "HEADLESS", False):
            opts.add_argument("--headless=new")
            opts.add_argument("user-agent=" + lpse_bot.HEADERS["User-Agent"])
        opts.add_argument("--disable-blink-features=AutomationControlled")
        opts.add_argument("--window-size=1400,1000")
        opts.add_argument("--disable-gpu")
        opts.add_experimental_option("excludeSwitches", ["enable-logging"])
        opts.page_load_strategy = "eager"  # tak perlu menunggu iklan/analytics
        self.driver = webdriver.Chrome(options=opts)  # Selenium Manager mengunduh driver otomatis
        self.driver.set_page_load_timeout(90)
        self.driver.execute_cdp_cmd("Network.enable", {})
        self.driver.execute_cdp_cmd("Network.setExtraHTTPHeaders",
                                    {"headers": {"Referer": self.list_url}})
        self.wait = WebDriverWait(self.driver, 40)

    def __del__(self):
        try:
            self.driver.quit()
        except Exception:
            pass

    # ---------- daftar tender: klik tabel DataTables ----------
    def _rows(self):
        soup = BeautifulSoup(self.driver.find_element(By.ID, "tbllelang").get_attribute("outerHTML"),
                             "html.parser")
        rows = []
        for tr in soup.select("tbody tr"):
            tds = tr.find_all("td")
            if len(tds) < 2 or "dataTables_empty" in (tds[0].get("class") or []):
                continue
            rows.append([clean(td) for td in tds])
        return rows

    def _wait_rows(self):
        def ready(d):
            proc = d.find_element(By.ID, "tbllelang_processing")
            return proc.value_of_css_property("display") == "none"
        self.wait.until(ready)
        time.sleep(0.5)

    def list_tenders(self, limit=None, **_):
        d = self.driver
        d.get(self.list_url)
        self.wait.until(EC.presence_of_element_located((By.ID, "tbllelang")))
        self._wait_rows()
        # naikkan jumlah baris per halaman ke nilai terbesar
        try:
            sel = Select(d.find_element(By.NAME, "tbllelang_length"))
            sel.select_by_value(max((o.get_attribute("value") for o in sel.options), key=int))
            self._wait_rows()
        except Exception:
            pass

        all_rows, seen_first = [], None
        while True:
            rows = self._rows()
            if not rows or rows[0][0] == seen_first:
                break
            seen_first = rows[0][0]
            all_rows.extend(rows)
            print(f"  daftar tender: {len(all_rows)} terambil", end="\r")
            if limit and len(all_rows) >= limit:
                all_rows = all_rows[:limit]
                break
            nxt = d.find_element(By.ID, "tbllelang_next")
            if "disabled" in (nxt.get_attribute("class") or ""):
                break
            d.execute_script("arguments[0].click();", nxt.find_element(By.TAG_NAME, "a"))
            try:
                self.wait.until(lambda _d: self._rows() and self._rows()[0][0] != seen_first)
            except TimeoutException:
                break
        print()
        cols = [self.ID_LABEL] + LIST_COLS[1:]
        recs = [{c: (r[i] if i < len(r) else None) for i, c in enumerate(cols)}
                for r in all_rows]
        return pd.DataFrame(recs, columns=cols)

    # ---------- halaman detail ----------
    def _get(self, url):
        for n in range(1, self.retries + 1):
            try:
                self.driver.get(url)
                title = self.driver.title or ""
                if "Terjadi Kesalahan" in title:
                    return None
                try:
                    WebDriverWait(self.driver, 5).until(
                        EC.presence_of_element_located((By.CSS_SELECTOR, "table, .alert")))
                except TimeoutException:
                    pass
                return self.driver.page_source
            except Exception:
                time.sleep(self.delay * n * 2)
        return None


if __name__ == "__main__":
    lpse_bot.main(SeleniumLpseBot,
                  lambda ap: ap.add_argument("--headless", action="store_true",
                                             help="jalankan Chrome tanpa jendela"))
