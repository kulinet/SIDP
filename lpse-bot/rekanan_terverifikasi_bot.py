"""
Bot Scraper Penyedia / Rekanan Terverifikasi SPSE Inaproc
Target URL: https://spse.inaproc.id/lombokbaratkab/verifikasi/rekananterverifikasi

Fitur Utama:
  1. Otomatis membuka Chrome dengan profil persisten (tidak perlu login berulang jika sesi masih aktif).
  2. Mengambil seluruh halaman tabel DataTables di /verifikasi/rekananterverifikasi dengan deteksi kolom dinamis
     (Nama Penyedia, Alamat, Telepon, NPWP, Email, Tanggal Daftar/Verifikasi, Bentuk Usaha, Sertifikat Elektronik).
  3. Mengambil halaman detail tiap penyedia (/verifikasi/view?id=...) secara paralel (asynchronous fetch di browser)
     untuk mendapatkan Kabupaten/Kota, Provinsi, Kode Pos, User ID, Status, dan Terdaftar Di secara presisi.
  4. Menyimpan hasil ke:
     - Excel (.xlsx) : lpse-bot/Rekap_Penyedia_Terverifikasi_lombokbaratkab.xlsx (lengkap dengan sheet Ringkasan)
     - CSV (.csv)    : lpse-bot/Rekap_Penyedia_Terverifikasi_lombokbaratkab.csv
     - Database MySQL: tabel `pengadaanpro_contractors` & `inaproc_vendor_sync` (otomatis jika DB aktif)

Contoh Penggunaan:
  python rekanan_terverifikasi_bot.py                        # Scrape daftar + detail + simpan Excel, CSV & DB
  python rekanan_terverifikasi_bot.py --mode list            # Hanya scrape tabel daftar (cepat)
  python rekanan_terverifikasi_bot.py --mode enrich          # Hanya lengkapi detail dari data yang sudah ada
  python rekanan_terverifikasi_bot.py --limit 100            # Uji coba 100 penyedia pertama
  python rekanan_terverifikasi_bot.py --no-db                # Simpan ke Excel & CSV saja tanpa MySQL
"""
import argparse
import csv
import json
import os
import re
import sys
import time

if hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8")
if hasattr(sys.stderr, "reconfigure"):
    sys.stderr.reconfigure(encoding="utf-8")

import pandas as pd
from bs4 import BeautifulSoup
from selenium import webdriver
from selenium.common.exceptions import TimeoutException
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select, WebDriverWait

try:
    import pymysql
    HAS_PYMYSQL = True
except ImportError:
    HAS_PYMYSQL = False

try:
    import config
except ImportError:
    class config:
        TAHUN = "2026"
        INSTANSI = "lombokbaratkab"
        DELAY = 0.5
        LIMIT = None
        HEADLESS = False

# ==============================================================================
# KONFIGURASI DIREKTORI & DATABASE
# ==============================================================================
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.dirname(SCRIPT_DIR)

# Gunakan profil Chrome yang sudah ada di writable/browser_profile_spse agar sesi login tersimpan
SHARED_PROFILE_DIR = os.path.join(PROJECT_ROOT, "writable", "browser_profile_spse")
LOCAL_PROFILE_DIR = os.path.join(SCRIPT_DIR, "browser_profile_spse")
PROFILE_DIR = SHARED_PROFILE_DIR if os.path.isdir(os.path.join(PROJECT_ROOT, "writable")) else LOCAL_PROFILE_DIR
os.makedirs(PROFILE_DIR, exist_ok=True)

LOG_DIR = os.path.join(PROJECT_ROOT, "writable", "logs") if os.path.isdir(os.path.join(PROJECT_ROOT, "writable")) else SCRIPT_DIR
os.makedirs(LOG_DIR, exist_ok=True)

STATUS_FILE = os.path.join(LOG_DIR, "bot_spse_terverifikasi_status.json")
LOG_FILE = os.path.join(LOG_DIR, "bot_spse_terverifikasi.log")

DB_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "sidp_new",
    "charset": "utf8mb4",
}

BENTUK_USAHA_KEYWORDS = [
    "perseroan terbatas",
    "persekutuan komanditer",
    "commanditer",
    "orang perseorangan",
    "usaha dagang",
    "koperasi",
    "firma",
    "yayasan",
    "bumd",
    "bumn",
    "badan usaha",
]


def log(msg):
    timestamp = time.strftime("%H:%M:%S")
    print(f"[{timestamp}] {msg}", flush=True)
    try:
        with open(LOG_FILE, "a", encoding="utf-8") as f:
            f.write(f"[{timestamp}] {msg}\n")
    except Exception:
        pass


def update_status(status, message, count=0, total=0, mode="all"):
    try:
        data = {
            "status": status,
            "message": message,
            "count": count,
            "total": total,
            "mode": mode,
            "updated_at": time.strftime("%Y-%m-%d %H:%M:%S"),
        }
        with open(STATUS_FILE, "w", encoding="utf-8") as f:
            json.dump(data, f, ensure_ascii=False, indent=2)
    except Exception:
        pass


# ==============================================================================
# HELPER NORMALISASI & DETEKSI LOKASI
# ==============================================================================
def normalize_name(name):
    clean = re.sub(
        r"^(pt|cv|ud|fa|koperasi|kop|toko|yayasan)\.?\s+",
        "",
        name or "",
        flags=re.IGNORECASE,
    )
    return re.sub(r"[^a-zA-Z0-9]", "", clean).lower()


def make_slug(name):
    slug = re.sub(r"[^a-zA-Z0-9\s-]", "", name or "").strip().lower()
    return re.sub(r"[\s_]+", "-", slug)


def parse_kpp(npwp):
    digits = re.sub(r"\D", "", npwp or "")
    if len(digits) >= 12:
        return digits[9:12]
    return ""


def clean_kabupaten_kota(kab_raw):
    """
    Normalisasi teks Kabupaten/Kota dari SPSE.
    Contoh: 'Lombok Barat (Kab.)' -> 'Kab. Lombok Barat'
            'Mataram (Kota)'      -> 'Kota Mataram'
    """
    if not kab_raw:
        return ""
    kab = kab_raw.strip()
    m_kab = re.search(r"^(.*?)\s*\((kab\.?|kabupaten)\)$", kab, re.IGNORECASE)
    if m_kab:
        return f"Kab. {m_kab.group(1).strip()}"
    m_kota = re.search(r"^(.*?)\s*\((kota(madya)?\.?)\)$", kab, re.IGNORECASE)
    if m_kota:
        return f"Kota {m_kota.group(1).strip()}"
    return kab


def determine_flag_lokasi(domisili_kota, province):
    """
    Klasifikasi flag_lokasi untuk SIDP / Inaproc:
    - 'lobar'    : Kab. Lombok Barat & Gerung
    - 'mataram'  : Kota Mataram
    - 'ntb_lain' : Lombok Tengah, Timur, Utara, Sumbawa, Dompu, Bima, NTB
    - 'luar'     : Luar NTB
    """
    u_dom = (domisili_kota or "").upper()
    u_prov = (province or "").upper()

    if "LOMBOK BARAT" in u_dom or "GERUNG" in u_dom:
        return "lobar"
    if "MATARAM" in u_dom:
        return "mataram"
    if (
        any(
            k in u_dom
            for k in [
                "LOMBOK TENGAH",
                "LOMBOK TIMUR",
                "LOMBOK UTARA",
                "SUMBAWA",
                "DOMPU",
                "BIMA",
            ]
        )
        or "NUSA TENGGARA BARAT" in u_prov
    ):
        return "ntb_lain"
    if u_dom or u_prov:
        return "luar"
    return "unknown"


def detect_location(address):
    u_addr = (address or "").upper()

    lobar_keywords = [
        "LOMBOK BARAT",
        "GERUNG",
        "NARMADA",
        "KEDIRI",
        "LABUAPI",
        "GUNUNGSARI",
        "BATULAYAR",
        "BATU LAYAR",
        "LEMBAR",
        "SEKOTONG",
        "LINGSAR",
        "KURIPAN",
    ]
    for kw in lobar_keywords:
        if kw in u_addr:
            return "Kab. Lombok Barat", "Nusa Tenggara Barat"

    mataram_keywords = [
        "MATARAM",
        "AMPENAN",
        "CAKRANEGARA",
        "SEKARBELA",
        "SANDUBAYA",
        "SELAPARANG",
        "PAGUTAN",
        "PEJANGGIK",
    ]
    for kw in mataram_keywords:
        if kw in u_addr:
            return "Kota Mataram", "Nusa Tenggara Barat"

    loteng_keywords = [
        "LOMBOK TENGAH",
        "PRAYA",
        "PUJUT",
        "KOPANG",
        "JONGGAT",
        "PRINGGARATA",
        "BATUKLIANG",
        "JANAPRIA",
    ]
    for kw in loteng_keywords:
        if kw in u_addr:
            return "Kab. Lombok Tengah", "Nusa Tenggara Barat"

    lotim_keywords = [
        "LOMBOK TIMUR",
        "SELONG",
        "MASBAGIK",
        "SAKRA",
        "AIKMEL",
        "LABUHAN HAJI",
        "SUKAMULIA",
        "TERARA",
        "KERUAK",
    ]
    for kw in lotim_keywords:
        if kw in u_addr:
            return "Kab. Lombok Timur", "Nusa Tenggara Barat"

    klu_keywords = ["LOMBOK UTARA", "TANJUNG", "GANGGA", "BAYAN", "KAYANGAN", "PEMENANG"]
    for kw in klu_keywords:
        if kw in u_addr:
            return "Kab. Lombok Utara", "Nusa Tenggara Barat"

    if "SUMBAWA BARAT" in u_addr or "TALIWANG" in u_addr:
        return "Kab. Sumbawa Barat", "Nusa Tenggara Barat"
    if "SUMBAWA" in u_addr:
        return "Kab. Sumbawa", "Nusa Tenggara Barat"
    if "DOMPU" in u_addr:
        return "Kab. Dompu", "Nusa Tenggara Barat"
    if "BIMA" in u_addr:
        return "Kab./Kota Bima", "Nusa Tenggara Barat"
    if "DENPASAR" in u_addr or "BALI" in u_addr or "BADUNG" in u_addr:
        return "Bali", "Bali"
    if "JAKARTA" in u_addr:
        return "DKI Jakarta", "DKI Jakarta"
    if "SURABAYA" in u_addr:
        return "Kota Surabaya", "Jawa Timur"
    if "BANDUNG" in u_addr:
        return "Kota Bandung", "Jawa Barat"
    if "SEMARANG" in u_addr:
        return "Kota Semarang", "Jawa Tengah"

    return "", ""


def is_bentuk_usaha_text(text):
    t = (text or "").lower()
    return any(kw in t for kw in BENTUK_USAHA_KEYWORDS) or bool(
        re.match(r"^(pt|cv|ud|koperasi|firma)\b", t)
    )


# ==============================================================================
# PARSING BARIS TABEL & HALAMAN DETAIL SPSE
# ==============================================================================
def extract_row_data(tds, headers=None):
    """
    Parse satu baris <tr> pada tabel /verifikasi/rekananterverifikasi.
    Mendukung pemetaan kolom dinamis berdasarkan header <th>.
    """
    if len(tds) < 2:
        return None

    first_cls = tds[0].get("class") or []
    if "dataTables_empty" in first_cls:
        return None

    # Cek apakah kolom 0 adalah nomor urut (angka murni)
    col_offset = 0
    first_txt = tds[0].get_text(strip=True)
    if first_txt.isdigit() and len(tds) >= 3 and tds[1].find("a"):
        col_offset = 1

    td_nama = tds[col_offset]

    # Ambil link detail (/verifikasi/view?id=...) dan nama penyedia
    link_elem = td_nama.find("a")
    detail_url = ""
    verifikasi_id = ""
    nama = ""
    if link_elem:
        nama = link_elem.get_text(strip=True)
        href = link_elem.get("href", "")
        if href:
            if href.startswith("http"):
                detail_url = href
            else:
                detail_url = "https://spse.inaproc.id" + (
                    href if href.startswith("/") else "/" + href
                )
            m_id = re.search(r"[?&]id=(\d+)", detail_url)
            if m_id:
                verifikasi_id = m_id.group(1)

    full_nama_text = td_nama.get_text("\n", strip=True)
    lines = [l.strip() for l in full_nama_text.split("\n") if l.strip()]

    if not nama and lines:
        nama = lines[0]

    # Ekstraksi Telepon dari kolom pertama
    phone = ""
    phone_match = re.search(
        r"(?:No\.?\s*Telepon|Telp|Telepon|HP)\s*[:\-]?\s*([0-9\-\+\/\s]+)",
        full_nama_text,
        re.IGNORECASE,
    )
    if phone_match:
        phone = re.sub(r"[^\d\-\+\/]", "", phone_match.group(1)).strip()

    # Ekstraksi Alamat (bersihkan baris nama dan baris no telepon)
    address_lines = []
    for line in lines:
        if line.lower() == nama.lower():
            continue
        if re.search(r"(?:No\.?\s*Telepon|Telp|Telepon|HP)\s*[:\-]", line, re.IGNORECASE):
            continue
        address_lines.append(line)
    address = " ".join(address_lines).strip()

    # Pemetaan kolom berikutnya berdasarkan header jika tersedia
    npwp_raw = ""
    email = ""
    tgl_daftar = ""
    sertifikat = ""
    bentuk_usaha = ""

    if headers and len(headers) == len(tds):
        for idx in range(col_offset + 1, len(tds)):
            h = headers[idx].lower()
            val = tds[idx].get_text(" ", strip=True)
            if "npwp" in h:
                npwp_raw = val
            elif "email" in h or "surel" in h:
                email = val
            elif "tanggal" in h or "tgl" in h or "verifikasi" in h or "daftar" in h:
                if not tgl_daftar:
                    tgl_daftar = val
            elif "bentuk" in h or "badan usaha" in h:
                bentuk_usaha = val
            elif "sertifikat" in h:
                sertifikat = val
    else:
        # Fallback berdasarkan posisi kolom
        rem = [td.get_text(" ", strip=True) for td in tds[col_offset + 1 :]]
        if len(rem) >= 1:
            npwp_raw = rem[0]
        if len(rem) >= 2:
            email = rem[1]
        if len(rem) >= 3:
            tgl_daftar = rem[2]
        if len(rem) == 4:
            # Jika hanya ada 4 kolom sisa (total 5 kolom), kolom terakhir biasanya Bentuk Usaha
            if is_bentuk_usaha_text(rem[3]):
                bentuk_usaha = rem[3]
            else:
                sertifikat = rem[3]
        elif len(rem) >= 5:
            sertifikat = rem[3]
            bentuk_usaha = rem[4]

    # Koreksi otomatis apabila bentuk_usaha masuk ke sertifikat_elektronik
    if not bentuk_usaha and sertifikat and is_bentuk_usaha_text(sertifikat):
        bentuk_usaha = sertifikat
        sertifikat = ""

    npwp = re.sub(r"\D", "", npwp_raw)
    if not nama and not npwp:
        return None

    # Deteksi bentuk usaha dari prefix nama jika kolom bentuk_usaha masih kosong
    if not bentuk_usaha:
        m_bu = re.match(r"^(PT|CV|UD|KOPERASI|YAYASAN|FIRMA)\.?\s+", nama, re.IGNORECASE)
        if m_bu:
            prefix = m_bu.group(1).upper()
            bu_map = {
                "PT": "Perseroan Terbatas (PT)",
                "CV": "Persekutuan Komanditer (CV)",
                "UD": "Usaha Dagang (UD)",
                "KOPERASI": "Koperasi",
                "YAYASAN": "Yayasan",
                "FIRMA": "Firma",
            }
            bentuk_usaha = bu_map.get(prefix, prefix)

    norm_name = normalize_name(nama)
    slug = make_slug(nama)
    kpp = parse_kpp(npwp)
    domisili_kota, province = detect_location(address)
    flag_lokasi = determine_flag_lokasi(domisili_kota, province)

    remote_id = "spse_" + (npwp if npwp else (verifikasi_id if verifikasi_id else norm_name[:40]))

    return {
        "remote_id": remote_id,
        "verifikasi_id": verifikasi_id,
        "user_id": "",
        "contractor_name": nama,
        "contractor_name_normalized": norm_name,
        "slug": slug,
        "bentuk_usaha": bentuk_usaha,
        "npwp": npwp,
        "npwp_formatted": npwp_raw,
        "kpp_code": kpp,
        "address": address,
        "kode_pos": "",
        "domisili_kota": domisili_kota,
        "province": province,
        "flag_lokasi": flag_lokasi,
        "phone": phone,
        "email": email,
        "tanggal_daftar": tgl_daftar,
        "sertifikat_elektronik": sertifikat,
        "terdaftar_di": "",
        "status_verifikasi": "Terverifikasi",
        "detail_url": detail_url,
    }


def parse_spse_detail_html(html):
    """
    Ekstraksi data lengkap dari halaman detail verifikasi:
    https://spse.inaproc.id/lombokbaratkab/verifikasi/view?id=...
    """
    if not html:
        return {}
    soup = BeautifulSoup(html, "html.parser")
    data = {}

    # 1. Scan semua baris tabel (th/td pairs atau td/td pairs)
    for tr in soup.find_all("tr"):
        cells = tr.find_all(["th", "td"])
        i = 0
        while i < len(cells) - 1:
            lbl = cells[i].get_text(strip=True).rstrip(":*").strip().lower()
            val = cells[i + 1].get_text(" ", strip=True)
            if lbl:
                data[lbl] = val
            i += 2

    # 2. Scan elemen dl/dt/dd jika ada
    for dt in soup.find_all("dt"):
        dd = dt.find_next_sibling("dd")
        if dd:
            lbl = dt.get_text(strip=True).rstrip(":*").strip().lower()
            val = dd.get_text(" ", strip=True)
            if lbl and lbl not in data:
                data[lbl] = val

    def get_val(keys):
        for k in keys:
            for d_k, d_v in data.items():
                if k == d_k or k in d_k:
                    return d_v
        return ""

    full_text = soup.get_text()

    kab_raw = get_val(["kabupaten/kota", "kabupaten / kota", "kabupaten", "kota"])
    if not kab_raw:
        m = re.search(r"Kabupaten\s*/\s*Kota\s*[:\t]?\s*([^\n\r<]+)", full_text, re.IGNORECASE)
        if m:
            kab_raw = m.group(1).strip()

    prov_raw = get_val(["provinsi"])
    if not prov_raw:
        m = re.search(r"Provinsi\s*[:\t]?\s*([^\n\r<]+)", full_text, re.IGNORECASE)
        if m:
            prov_raw = m.group(1).strip()

    terdaftar_raw = get_val(["terdaftar di", "terdaftar"])
    alamat_raw = get_val(["alamat"])
    pos_raw = get_val(["kode pos"])
    bentuk_raw = get_val(["bentuk usaha"])
    user_id_raw = get_val(["user id"])
    nama_raw = get_val(["nama badan usaha", "nama pelaku usaha"])
    telp_raw = get_val(["no. telepon", "telepon"])
    hp_raw = get_val(["no. handphone", "handphone", "no. hp", "hp"])
    status_raw = get_val(["status"])
    tgl_daftar_raw = get_val(["tanggal pendaftaran", "tanggal daftar", "tanggal verifikasi"])

    clean_kab = clean_kabupaten_kota(kab_raw)
    flag_lok = determine_flag_lokasi(clean_kab, prov_raw)
    phone_clean = hp_raw if hp_raw and hp_raw != "-" else (telp_raw if telp_raw and telp_raw != "-" else "")

    return {
        "kabupaten_kota_raw": kab_raw,
        "domisili_kota": clean_kab,
        "province": prov_raw,
        "flag_lokasi": flag_lok,
        "address": alamat_raw,
        "kode_pos": pos_raw,
        "bentuk_usaha": bentuk_raw,
        "user_id": user_id_raw,
        "nama_badan_usaha": nama_raw,
        "terdaftar_di": terdaftar_raw,
        "phone": phone_clean,
        "status_verifikasi": status_raw or "Terverifikasi",
        "tanggal_daftar": tgl_daftar_raw,
    }


# ==============================================================================
# PENYIMPANAN DATABASE, EXCEL, DAN CSV
# ==============================================================================
def save_to_database(records):
    if not records or not HAS_PYMYSQL:
        return 0

    try:
        conn = pymysql.connect(**DB_CONFIG)
        cursor = conn.cursor()
    except Exception as e:
        log(f"[!] Catatan koneksi DB MySQL (lewatkan jika tidak memakai DB): {e}")
        return 0

    sql_contractor = """
        INSERT INTO `pengadaanpro_contractors` (
            remote_id, slug, contractor_name, contractor_name_normalized,
            bentuk_usaha, npwp, kpp_code, domisili_kota, province,
            terdaftar_di, address, phone, email, tanggal_daftar,
            sertifikat_elektronik, detail_url
        ) VALUES (
            %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s
        ) ON DUPLICATE KEY UPDATE
            contractor_name = VALUES(contractor_name),
            contractor_name_normalized = VALUES(contractor_name_normalized),
            bentuk_usaha = IF(VALUES(bentuk_usaha) != '', VALUES(bentuk_usaha), bentuk_usaha),
            npwp = IF(VALUES(npwp) != '', VALUES(npwp), npwp),
            kpp_code = IF(VALUES(kpp_code) != '', VALUES(kpp_code), kpp_code),
            domisili_kota = IF(VALUES(domisili_kota) != '', VALUES(domisili_kota), domisili_kota),
            province = IF(VALUES(province) != '', VALUES(province), province),
            terdaftar_di = IF(VALUES(terdaftar_di) != '', VALUES(terdaftar_di), terdaftar_di),
            address = IF(VALUES(address) != '', VALUES(address), address),
            phone = IF(VALUES(phone) != '', VALUES(phone), phone),
            email = IF(VALUES(email) != '', VALUES(email), email),
            tanggal_daftar = IF(VALUES(tanggal_daftar) != '', VALUES(tanggal_daftar), tanggal_daftar),
            sertifikat_elektronik = IF(VALUES(sertifikat_elektronik) != '', VALUES(sertifikat_elektronik), sertifikat_elektronik),
            detail_url = IF(VALUES(detail_url) != '', VALUES(detail_url), detail_url),
            updated_at = CURRENT_TIMESTAMP
    """

    sql_inaproc = """
        INSERT INTO `inaproc_vendor_sync` (
            kode_penyedia, nama_penyedia, bentuk_usaha, npwp_penyedia,
            kpp_code, lokasi_perusahaan, flag_lokasi, alamat_penyedia,
            email, telepon, last_sync_status, last_sync_at, created_at, updated_at
        ) VALUES (
            %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, 'spse_terverifikasi', NOW(), NOW(), NOW()
        ) ON DUPLICATE KEY UPDATE
            nama_penyedia = VALUES(nama_penyedia),
            bentuk_usaha = IF(VALUES(bentuk_usaha) != '', VALUES(bentuk_usaha), bentuk_usaha),
            npwp_penyedia = IF(VALUES(npwp_penyedia) != '', VALUES(npwp_penyedia), npwp_penyedia),
            kpp_code = IF(VALUES(kpp_code) != '', VALUES(kpp_code), kpp_code),
            lokasi_perusahaan = IF(VALUES(lokasi_perusahaan) != '', VALUES(lokasi_perusahaan), lokasi_perusahaan),
            flag_lokasi = IF(VALUES(flag_lokasi) != '', VALUES(flag_lokasi), flag_lokasi),
            alamat_penyedia = IF(VALUES(alamat_penyedia) != '', VALUES(alamat_penyedia), alamat_penyedia),
            email = IF(VALUES(email) != '', VALUES(email), email),
            telepon = IF(VALUES(telepon) != '', VALUES(telepon), telepon),
            last_sync_status = 'spse_terverifikasi',
            last_sync_at = NOW(),
            updated_at = NOW()
    """

    batch_size = 400
    saved_count = 0

    for i in range(0, len(records), batch_size):
        batch = records[i : i + batch_size]
        vals_contractors = []
        vals_inaproc = []

        for r in batch:
            vals_contractors.append(
                (
                    r.get("remote_id", ""),
                    r.get("slug", ""),
                    r.get("contractor_name", ""),
                    r.get("contractor_name_normalized", ""),
                    r.get("bentuk_usaha", ""),
                    r.get("npwp", ""),
                    r.get("kpp_code", ""),
                    r.get("domisili_kota", ""),
                    r.get("province", ""),
                    r.get("terdaftar_di", ""),
                    r.get("address", ""),
                    r.get("phone", ""),
                    r.get("email", ""),
                    r.get("tanggal_daftar", ""),
                    r.get("sertifikat_elektronik", ""),
                    r.get("detail_url", ""),
                )
            )

            kode_vendor = "SPSE_" + (r.get("npwp") or r.get("remote_id", ""))
            flag_loc = r.get("flag_lokasi") or determine_flag_lokasi(
                r.get("domisili_kota", ""), r.get("province", "")
            )

            vals_inaproc.append(
                (
                    kode_vendor,
                    r.get("contractor_name", ""),
                    r.get("bentuk_usaha", ""),
                    r.get("npwp", ""),
                    r.get("kpp_code", ""),
                    r.get("domisili_kota", ""),
                    flag_loc,
                    r.get("address", ""),
                    r.get("email", ""),
                    r.get("phone", ""),
                )
            )

        try:
            cursor.executemany(sql_contractor, vals_contractors)
            cursor.executemany(sql_inaproc, vals_inaproc)
            conn.commit()
            saved_count += len(batch)
        except Exception as e:
            conn.rollback()
            log(f"[!] Error saat simpan batch ke DB: {e}")

    conn.close()
    return saved_count


def export_records_to_files(records, instansi="lombokbaratkab", output_prefix=None):
    """
    Simpan hasil scrape ke Excel (.xlsx) dan CSV (.csv) di dalam folder lpse-bot.
    """
    if not records:
        log("[!] Tidak ada data untuk diekspor ke Excel/CSV.")
        return

    base_name = output_prefix or f"Rekap_Penyedia_Terverifikasi_{instansi}"
    excel_path = os.path.join(SCRIPT_DIR, f"{base_name}.xlsx")
    csv_path = os.path.join(SCRIPT_DIR, f"{base_name}.csv")

    export_rows = []
    for idx, r in enumerate(records, 1):
        export_rows.append(
            {
                "No": idx,
                "ID Verifikasi": r.get("verifikasi_id", ""),
                "User ID": r.get("user_id", ""),
                "Nama Penyedia": r.get("contractor_name", ""),
                "Bentuk Usaha": r.get("bentuk_usaha", ""),
                "NPWP": r.get("npwp", ""),
                "Kode KPP": r.get("kpp_code", ""),
                "Alamat": r.get("address", ""),
                "Kode Pos": r.get("kode_pos", ""),
                "Kabupaten/Kota": r.get("domisili_kota", ""),
                "Provinsi": r.get("province", ""),
                "Kategori Wilayah": r.get("flag_lokasi", ""),
                "No. Telepon / HP": r.get("phone", ""),
                "Email": r.get("email", ""),
                "Tanggal Daftar / Verifikasi": r.get("tanggal_daftar", ""),
                "Sertifikat Elektronik": r.get("sertifikat_elektronik", ""),
                "Terdaftar Di": r.get("terdaftar_di", ""),
                "Status": r.get("status_verifikasi", "Terverifikasi"),
                "URL Detail": r.get("detail_url", ""),
            }
        )

    df_main = pd.DataFrame(export_rows)

    # Simpan CSV (UTF-8 with BOM, titik koma agar langsung rapi saat dibuka di Excel Indonesia)
    df_main.to_csv(csv_path, index=False, sep=";", encoding="utf-8-sig")
    log(f"[OK] File CSV tersimpan ({len(df_main):,} baris): {csv_path}")

    # Buat ringkasan per Kabupaten/Kota & Bentuk Usaha untuk Sheet 2
    df_dom = (
        df_main.groupby(["Provinsi", "Kabupaten/Kota", "Kategori Wilayah"], dropna=False)
        .size()
        .reset_index(name="Jumlah Penyedia")
        .sort_values(by="Jumlah Penyedia", ascending=False)
    )
    df_bu = (
        df_main.groupby(["Bentuk Usaha"], dropna=False)
        .size()
        .reset_index(name="Jumlah Penyedia")
        .sort_values(by="Jumlah Penyedia", ascending=False)
    )

    try:
        with pd.ExcelWriter(excel_path, engine="openpyxl") as xw:
            df_main.to_excel(xw, sheet_name="Penyedia Terverifikasi", index=False)
            df_dom.to_excel(xw, sheet_name="Rekap Domisili", index=False)
            df_bu.to_excel(xw, sheet_name="Rekap Bentuk Usaha", index=False)

            for ws in xw.book.worksheets:
                for col in ws.columns:
                    max_len = max(
                        len(str(c.value)) if c.value is not None else 0 for c in col
                    )
                    ws.column_dimensions[col[0].column_letter].width = min(
                        max(max_len + 2, 10), 55
                    )
                ws.freeze_panes = "A2"
        log(f"[OK] File Excel tersimpan ({len(df_main):,} baris): {excel_path}")
    except Exception as e:
        log(f"[!] Gagal menyimpan Excel: {e}")


# ==============================================================================
# PENGAMBILAN DETAIL PARALEL (/verifikasi/view?id=...)
# ==============================================================================
JS_FETCH_BATCH = """
var items = arguments[0];
var callback = arguments[arguments.length - 1];

if (!items || !items.length) {
    if (typeof callback === 'function') callback([]);
    return;
}

Promise.all(items.map(function(item) {
    var controller = new AbortController();
    var tid = setTimeout(function() { controller.abort(); }, 12000);
    return fetch(item.url, { credentials: 'include', signal: controller.signal })
        .then(function(res) {
            clearTimeout(tid);
            if (res.ok) {
                return res.text().then(function(t) {
                    return { idx: item.idx, url: item.url, html: t, success: true };
                });
            } else {
                return { idx: item.idx, url: item.url, html: '', success: false, status: res.status };
            }
        })
        .catch(function(err) {
            clearTimeout(tid);
            return { idx: item.idx, url: item.url, html: '', success: false, error: err.toString() };
        });
})).then(function(results) {
    if (typeof callback === 'function') callback(results);
}).catch(function(e) {
    if (typeof callback === 'function') callback([]);
});
"""


def enrich_records_via_browser(driver, records, use_db=True):
    """
    Lengkapi data Kabupaten/Kota, Provinsi, Alamat, Bentuk Usaha, dan Terdaftar Di
    dari halaman /verifikasi/view?id=... menggunakan sesi aktif browser Chrome.
    """
    targets = [
        (idx, r)
        for idx, r in enumerate(records)
        if r.get("detail_url") and not r.get("domisili_kota")
    ]
    if not targets:
        # Jika semua sudah punya domisili_kota dari deteksi alamat, tetap enrich yang belum punya user_id/terdaftar_di
        targets = [
            (idx, r)
            for idx, r in enumerate(records)
            if r.get("detail_url") and not r.get("terdaftar_di")
        ]

    total_targets = len(targets)
    if total_targets == 0:
        log("[OK] Seluruh data penyedia sudah memiliki informasi detail lengkap.")
        return records

    log("=" * 75)
    log(f"  MENGAMBIL DETAIL {total_targets:,} PENYEDIA TERVERIFIKASI (PARALEL)")
    log("=" * 75)

    driver.set_script_timeout(45)
    batch_size = 8
    processed = 0
    start_time = time.time()
    last_dom = "-"

    for i in range(0, total_targets, batch_size):
        batch = targets[i : i + batch_size]
        payload = [{"idx": idx, "url": r["detail_url"]} for idx, r in batch]

        try:
            results = driver.execute_async_script(JS_FETCH_BATCH, payload)
        except Exception as e:
            log(f"[!] Peringatan fetch batch {i // batch_size + 1}: {e}")
            time.sleep(1)
            continue

        if not results:
            continue

        updated_in_batch = []
        for res in results:
            processed += 1
            rec_idx = res.get("idx")
            html = res.get("html", "")
            if rec_idx is None or not html:
                continue

            parsed = parse_spse_detail_html(html)
            rec = records[rec_idx]

            if parsed.get("domisili_kota"):
                rec["domisili_kota"] = parsed["domisili_kota"]
                last_dom = parsed["domisili_kota"]
            if parsed.get("province"):
                rec["province"] = parsed["province"]
            if parsed.get("flag_lokasi") and parsed["flag_lokasi"] != "unknown":
                rec["flag_lokasi"] = parsed["flag_lokasi"]
            if parsed.get("address"):
                rec["address"] = parsed["address"]
            if parsed.get("kode_pos"):
                rec["kode_pos"] = parsed["kode_pos"]
            if parsed.get("bentuk_usaha"):
                rec["bentuk_usaha"] = parsed["bentuk_usaha"]
            if parsed.get("user_id"):
                rec["user_id"] = parsed["user_id"]
            if parsed.get("terdaftar_di"):
                rec["terdaftar_di"] = parsed["terdaftar_di"]
            if parsed.get("phone") and not rec.get("phone"):
                rec["phone"] = parsed["phone"]
            if parsed.get("status_verifikasi"):
                rec["status_verifikasi"] = parsed["status_verifikasi"]

            updated_in_batch.append(rec)

        if use_db and updated_in_batch:
            save_to_database(updated_in_batch)

        pct = int((processed / total_targets) * 100)
        elapsed = time.time() - start_time
        speed = processed / elapsed if elapsed > 0 else 0
        rem_sec = int((total_targets - processed) / speed) if speed > 0 else 0
        rem_str = f"{rem_sec // 60}m {rem_sec % 60}s"

        if (i // batch_size) % 5 == 0 or processed >= total_targets:
            log(
                f"[*] Detail Penyedia: {processed:,}/{total_targets:,} ({pct}%) | Domisili: {last_dom} | Estimasi: {rem_str}"
            )
            update_status(
                "enriching",
                f"Melengkapi detail penyedia: {processed:,}/{total_targets:,} ({pct}%) - Domisili: {last_dom}",
                processed,
                total_targets,
                mode="enrich",
            )

        time.sleep(0.1)

    return records


# ==============================================================================
# PROSES UTAMA SCRAPER REKANAN TERVERIFIKASI
# ==============================================================================
def wait_datatable_ready(driver, timeout=25):
    """Tunggu indikator loading/processing DataTables selesai."""
    try:
        WebDriverWait(driver, timeout).until(
            lambda d: all(
                el.value_of_css_property("display") == "none"
                for el in d.find_elements(By.CSS_SELECTOR, "div.dataTables_processing")
            )
        )
    except Exception:
        pass
    time.sleep(0.4)


def run_bot(instansi="lombokbaratkab", mode="all", limit=None, use_db=True, headless=False):
    target_url = f"https://spse.inaproc.id/{instansi}/verifikasi/rekananterverifikasi"

    log("=" * 75)
    log("  BOT SCRAPER PENYEDIA TERVERIFIKASI SPSE INAPROC")
    log(f"  Target URL : {target_url}")
    log(f"  Mode       : {mode.upper()} | Limit: {limit or 'Semua Data'}")
    log("=" * 75)

    update_status("starting", "Menyiapkan browser Chrome...", mode=mode)

    chrome_options = Options()
    chrome_options.add_argument(f"--user-data-dir={PROFILE_DIR}")
    chrome_options.add_argument("--disable-notifications")
    chrome_options.add_argument("--no-sandbox")
    chrome_options.add_argument("--disable-dev-shm-usage")
    chrome_options.add_argument("--disable-blink-features=AutomationControlled")
    chrome_options.add_argument("--window-size=1400,900")
    if headless:
        chrome_options.add_argument("--headless=new")

    log("[*] Membuka browser Google Chrome...")
    try:
        driver = webdriver.Chrome(options=chrome_options)
    except Exception as e:
        log(f"[!] Gagal membuka Chrome: {e}")
        update_status("error", f"Gagal membuka Chrome: {e}", mode=mode)
        return

    try:
        log(f"[*] Mengarahkan browser ke: {target_url}")
        driver.get(target_url)

        print("\n" + "=" * 75)
        print("                      PETUNJUK LOGIN PENGGUNA")
        print("=" * 75)
        print(" 1. Jika belum login, silakan login ke akun SPSE Anda di jendela Chrome.")
        print(" 2. Setelah login berhasil, bot otomatis mengarahkan ke halaman:")
        print(f"    {target_url}")
        print(" 3. Bot akan otomatis bekerja saat tabel Rekanan Terverifikasi tampil!")
        print("=" * 75 + "\n")

        update_status("waiting_login", "Menunggu login & halaman Rekanan Terverifikasi siap...", mode=mode)

        # Tunggu hingga pengguna selesai login dan tabel rekanan terverifikasi muncul
        logged_in = False
        redirected_to_target = False
        while not logged_in:
            cur_url = driver.current_url.lower()
            # Jika sudah login tapi diarahkan ke halaman lain (misal /verifikasi/rekanan atau beranda),
            # arahkan otomatis ke /verifikasi/rekananterverifikasi
            if (
                "spse.inaproc.id" in cur_url
                and "login" not in cur_url
                and "pakta" not in cur_url
                and "rekananterverifikasi" not in cur_url
                and ("verifikasi" in cur_url or "home" in cur_url or "dashboard" in cur_url)
                and not redirected_to_target
            ):
                log(f"[*] Sesi login terdeteksi, mengarahkan ke: {target_url}")
                redirected_to_target = True
                driver.get(target_url)
                time.sleep(2)
                continue

            if "verifikasi" in cur_url and "login" not in cur_url:
                tables = driver.find_elements(By.TAG_NAME, "table")
                if tables:
                    body_text = driver.find_element(By.TAG_NAME, "body").text.lower()
                    if "nama penyedia" in body_text or "npwp" in body_text:
                        if "rekananterverifikasi" not in cur_url and not redirected_to_target:
                            redirected_to_target = True
                            driver.get(target_url)
                            time.sleep(2)
                            continue
                        log("[+] Halaman Rekanan Terverifikasi terdeteksi aktif & siap!")
                        logged_in = True
                        break
            time.sleep(2)

        wait_datatable_ready(driver)

        all_records = []
        seen_keys = set()

        if mode in ("all", "list", "scrape"):
            # Ubah jumlah baris per halaman ke nilai terbesar (100)
            try:
                length_selects = driver.find_elements(
                    By.CSS_SELECTOR,
                    "select[name$='_length'], select.form-select, select[name='length']",
                )
                for sel in length_selects:
                    s = Select(sel)
                    options = [
                        o.get_attribute("value")
                        for o in s.options
                        if o.get_attribute("value") and o.get_attribute("value").lstrip("-").isdigit()
                    ]
                    if options:
                        max_opt = max(options, key=lambda x: int(x))
                        s.select_by_value(max_opt)
                        log(f"[*] Mengatur tampilan tabel menjadi {max_opt} baris per halaman.")
                        wait_datatable_ready(driver)
                        time.sleep(1.5)
                        break
            except Exception as e:
                log(f"[-] Catatan pengaturan jumlah baris per halaman: {e}")

            # Cek info total data dari DataTables API
            dt_info = driver.execute_script(
                """
                try {
                    if (window.jQuery && $.fn && $.fn.dataTable) {
                        var tables = $.fn.dataTable.tables();
                        if (tables.length > 0) {
                            var dt = $(tables[0]).DataTable();
                            var info = dt.page.info();
                            return {
                                recordsTotal: info ? info.recordsTotal : null,
                                pages: info ? info.pages : null
                            };
                        }
                    }
                } catch(e) {}
                return null;
                """
            )
            total_reported = dt_info.get("recordsTotal") if dt_info else None
            total_pages = dt_info.get("pages") if dt_info else None
            if total_reported:
                log(
                    f"[i] Total Penyedia Terverifikasi di SPSE: {total_reported:,} penyedia ({total_pages or '?'} halaman)."
                )

            page_num = 1
            consecutive_zero = 0

            while True:
                wait_datatable_ready(driver)
                soup = BeautifulSoup(driver.page_source, "html.parser")

                target_table = None
                headers = []
                for tbl in soup.find_all("table"):
                    ths = [th.get_text(" ", strip=True) for th in tbl.find_all("th")]
                    if any("nama penyedia" in h.lower() or "npwp" in h.lower() for h in ths):
                        target_table = tbl
                        headers = ths
                        break

                if not target_table:
                    log("[!] Tabel Rekanan Terverifikasi tidak ditemukan pada halaman ini.")
                    break

                tbody = target_table.find("tbody")
                rows = tbody.find_all("tr") if tbody else target_table.find_all("tr")

                page_records = []
                for tr in rows:
                    tds = tr.find_all("td")
                    rec = extract_row_data(tds, headers=headers)
                    if not rec:
                        continue

                    uniq_key = (
                        rec["verifikasi_id"]
                        or rec["npwp"]
                        or rec["contractor_name_normalized"]
                    )
                    if uniq_key not in seen_keys:
                        seen_keys.add(uniq_key)
                        all_records.append(rec)
                        page_records.append(rec)
                        if limit and len(all_records) >= limit:
                            break

                if page_records:
                    if use_db:
                        save_to_database(page_records)
                    consecutive_zero = 0
                    log(
                        f"  -> Halaman {page_num}/{total_pages or '?'}: +{len(page_records)} penyedia (Total terkumpul: {len(all_records):,})"
                    )
                    update_status(
                        "scraping",
                        f"Halaman {page_num}: {len(all_records):,} penyedia terverifikasi terkumpul...",
                        len(all_records),
                        total_reported or len(all_records),
                        mode=mode,
                    )
                else:
                    consecutive_zero += 1
                    log(
                        f"  -> Halaman {page_num}: 0 penyedia baru terdeteksi ({consecutive_zero}/2)"
                    )
                    if consecutive_zero >= 2:
                        break

                # Simpan checkpoint berkala setiap 10 halaman
                if page_num % 10 == 0:
                    export_records_to_files(all_records, instansi=instansi)

                if limit and len(all_records) >= limit:
                    log(f"[*] Batas --limit {limit} tercapai.")
                    break

                # Cek apakah sudah di halaman terakhir DataTables
                is_last_page = driver.execute_script(
                    """
                    try {
                        var tables = $.fn.dataTable.tables();
                        if (tables.length > 0) {
                            var info = $(tables[0]).DataTable().page.info();
                            if (info && info.pages > 0 && info.page >= (info.pages - 1)) {
                                return true;
                            }
                        }
                    } catch(e) {}
                    return false;
                    """
                )
                if is_last_page:
                    log(f"[*] Halaman terakhir ({page_num}) telah selesai diproses.")
                    break

                # Klik halaman berikutnya via DataTables API atau tombol Next
                moved = driver.execute_script(
                    """
                    try {
                        var tables = $.fn.dataTable.tables();
                        if (tables.length > 0) {
                            var dt = $(tables[0]).DataTable();
                            var info = dt.page.info();
                            if (info && info.page < info.pages - 1) {
                                dt.page('next').draw('page');
                                return true;
                            }
                        }
                    } catch(e) {}
                    return false;
                    """
                )

                if not moved:
                    next_btn = None
                    for sel in [
                        "li.paginate_button.next:not(.disabled) a",
                        ".paginate_button.next:not(.disabled)",
                        "a[id$='_next']:not(.disabled)",
                    ]:
                        elems = driver.find_elements(By.CSS_SELECTOR, sel)
                        if elems and elems[0].is_displayed() and elems[0].is_enabled():
                            next_btn = elems[0]
                            break
                    if next_btn:
                        driver.execute_script("arguments[0].click();", next_btn)
                    else:
                        log("[*] Tidak ada tombol halaman berikutnya. Selesai membaca tabel.")
                        break

                page_num += 1
                time.sleep(0.8)

            log(
                f"[*] Selesai membaca daftar tabel: {len(all_records):,} penyedia terverifikasi terkumpul."
            )
            export_records_to_files(all_records, instansi=instansi)

        # Jika mode 'all' atau 'enrich', ambil detail Kabupaten/Kota dari /verifikasi/view?id=...
        if mode in ("all", "enrich"):
            if not all_records:
                csv_fallback = os.path.join(
                    SCRIPT_DIR, f"Rekap_Penyedia_Terverifikasi_{instansi}.csv"
                )
                if os.path.isfile(csv_fallback):
                    try:
                        df_prev = pd.read_csv(csv_fallback, sep=";", dtype=str).fillna("")
                        for _, row in df_prev.iterrows():
                            npwp_val = row.get("NPWP", "")
                            nama_val = row.get("Nama Penyedia", "")
                            ver_id = row.get("ID Verifikasi", "")
                            norm_n = normalize_name(nama_val)
                            all_records.append(
                                {
                                    "remote_id": "spse_" + (npwp_val or ver_id or norm_n[:40]),
                                    "verifikasi_id": ver_id,
                                    "user_id": row.get("User ID", ""),
                                    "contractor_name": nama_val,
                                    "contractor_name_normalized": norm_n,
                                    "slug": make_slug(nama_val),
                                    "bentuk_usaha": row.get("Bentuk Usaha", ""),
                                    "npwp": npwp_val,
                                    "kpp_code": row.get("Kode KPP", "") or parse_kpp(npwp_val),
                                    "address": row.get("Alamat", ""),
                                    "kode_pos": row.get("Kode Pos", ""),
                                    "domisili_kota": row.get("Kabupaten/Kota", ""),
                                    "province": row.get("Provinsi", ""),
                                    "flag_lokasi": row.get("Kategori Wilayah", ""),
                                    "phone": row.get("No. Telepon / HP", ""),
                                    "email": row.get("Email", ""),
                                    "tanggal_daftar": row.get("Tanggal Daftar / Verifikasi", ""),
                                    "sertifikat_elektronik": row.get("Sertifikat Elektronik", ""),
                                    "terdaftar_di": row.get("Terdaftar Di", ""),
                                    "status_verifikasi": row.get("Status", "Terverifikasi"),
                                    "detail_url": row.get("URL Detail", ""),
                                }
                            )
                        log(f"[*] Memuat {len(all_records):,} baris dari CSV: {csv_fallback}")
                    except Exception as e:
                        log(f"[!] Gagal memuat CSV untuk mode enrich: {e}")

            if not all_records and use_db and HAS_PYMYSQL:
                # Muat dari database jika dijalankan langsung dengan --mode enrich
                try:
                    conn = pymysql.connect(**DB_CONFIG)
                    cur = conn.cursor(pymysql.cursors.DictCursor)
                    cur.execute(
                        """
                        SELECT remote_id, slug, contractor_name, contractor_name_normalized,
                               bentuk_usaha, npwp, kpp_code, address, domisili_kota, province,
                               phone, email, tanggal_daftar, sertifikat_elektronik,
                               terdaftar_di, detail_url
                        FROM pengadaanpro_contractors
                        WHERE remote_id LIKE 'spse_%'
                        ORDER BY id ASC
                        """
                    )
                    all_records = cur.fetchall()
                    conn.close()
                except Exception as e:
                    log(f"[!] Gagal memuat data dari DB untuk mode enrich: {e}")

            if all_records:
                all_records = enrich_records_via_browser(
                    driver, all_records, use_db=use_db
                )
                export_records_to_files(all_records, instansi=instansi)

        update_status(
            "completed",
            f"Selesai! {len(all_records):,} penyedia terverifikasi berhasil disimpan ke Excel, CSV & Database.",
            len(all_records),
            len(all_records),
            mode=mode,
        )
        log("=" * 75)
        log(
            f"[SELESAI] Total {len(all_records):,} Penyedia Terverifikasi berhasil diekspor!"
        )
        log("=" * 75)

    except Exception as e:
        log(f"[!] Terjadi kesalahan pada bot: {e}")
        update_status("error", f"Error: {e}", mode=mode)
    finally:
        try:
            time.sleep(2)
            driver.quit()
            log("[*] Browser Chrome ditutup.")
        except Exception:
            pass


if __name__ == "__main__":
    ap = argparse.ArgumentParser(
        description="Bot Scraper Penyedia Terverifikasi SPSE Inaproc"
    )
    ap.add_argument(
        "--instansi",
        default=getattr(config, "INSTANSI", "lombokbaratkab"),
        help="Slug LPSE instansi (default: lombokbaratkab)",
    )
    ap.add_argument(
        "--mode",
        choices=["all", "list", "enrich", "scrape"],
        default="all",
        help="Mode eksekusi: 'all' (daftar + detail), 'list' (hanya tabel daftar), 'enrich' (hanya detail)",
    )
    ap.add_argument(
        "--limit",
        type=int,
        default=getattr(config, "LIMIT", None),
        help="Batasi jumlah penyedia yang diambil (untuk uji coba)",
    )
    ap.add_argument(
        "--no-db",
        action="store_true",
        help="Hanya simpan ke Excel & CSV tanpa menyimpan ke MySQL",
    )
    ap.add_argument(
        "--headless",
        action="store_true",
        help="Jalankan Chrome secara headless (tanpa jendela)",
    )
    args = ap.parse_args()

    run_bot(
        instansi=args.instansi,
        mode=args.mode,
        limit=args.limit,
        use_db=not args.no_db,
        headless=args.headless,
    )
