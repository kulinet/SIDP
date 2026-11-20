import sys
import os
import re
import time
import json
import csv
import pymysql
import requests

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')
if hasattr(sys.stderr, 'reconfigure'):
    sys.stderr.reconfigure(encoding='utf-8')

from bs4 import BeautifulSoup
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select

# Database Configuration
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'sidp_new',
    'charset': 'utf8mb4'
}

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
LOG_DIR = os.path.join(BASE_DIR, 'writable', 'logs')
os.makedirs(LOG_DIR, exist_ok=True)

STATUS_FILE = os.path.join(LOG_DIR, 'bot_spse_status.json')
LOG_FILE = os.path.join(LOG_DIR, 'bot_spse.log')
PROFILE_DIR = os.path.join(BASE_DIR, 'writable', 'browser_profile_spse')
os.makedirs(PROFILE_DIR, exist_ok=True)

TARGET_URL = "https://spse.inaproc.id/lombokbaratkab/verifikasi/rekanan"

def log(msg):
    timestamp = time.strftime('%H:%M:%S')
    print(f"[{timestamp}] {msg}", flush=True)
    try:
        with open(LOG_FILE, 'a', encoding='utf-8') as f:
            f.write(f"[{timestamp}] {msg}\n")
    except Exception:
        pass

def update_status(status, message, count=0, total=0, mode='enrich'):
    try:
        data = {
            'status': status,
            'message': message,
            'count': count,
            'total': total,
            'mode': mode,
            'updated_at': time.strftime('%Y-%m-%d %H:%M:%S')
        }
        with open(STATUS_FILE, 'w', encoding='utf-8') as f:
            json.dump(data, f, ensure_ascii=False, indent=2)
    except Exception:
        pass

def normalize_name(name):
    clean = re.sub(r'^(pt|cv|ud|fa|koperasi|kop|toko|yayasan)\.?\s+', '', name or '', flags=re.IGNORECASE)
    return re.sub(r'[^a-zA-Z0-9]', '', clean).lower()

def make_slug(name):
    slug = re.sub(r'[^a-zA-Z0-9\s-]', '', name or '').strip().lower()
    return re.sub(r'[\s_]+', '-', slug)

def parse_kpp(npwp):
    digits = re.sub(r'\D', '', npwp or '')
    if len(digits) >= 12:
        return digits[9:12]
    return ''

def clean_kabupaten_kota(kab_raw):
    """
    Normalisasi teks Kabupaten/Kota dari SPSE.
    Contoh: 'Sumbawa (Kab.)' -> 'Kab. Sumbawa'
            'Mataram (Kota)' -> 'Kota Mataram'
    """
    if not kab_raw:
        return ''
    kab = kab_raw.strip()
    m_kab = re.search(r'^(.*?)\s*\((kab\.?|kabupaten)\)$', kab, re.IGNORECASE)
    if m_kab:
        return f"Kab. {m_kab.group(1).strip()}"
    m_kota = re.search(r'^(.*?)\s*\((kota(madya)?\.?)\)$', kab, re.IGNORECASE)
    if m_kota:
        return f"Kota {m_kota.group(1).strip()}"
    return kab

def determine_flag_lokasi(domisili_kota, province):
    """
    Klasifikasi flag_lokasi untuk SIDP / Inaproc:
    - 'lobar'      : Kab. Lombok Barat & Gerung
    - 'mataram'    : Kota Mataram
    - 'ntb_lain'   : Lombok Tengah, Timur, Utara, Sumbawa, Dompu, Bima, NTB
    - 'luar'       : Luar NTB
    """
    u_dom = (domisili_kota or '').upper()
    u_prov = (province or '').upper()
    
    if 'LOMBOK BARAT' in u_dom or 'GERUNG' in u_dom:
        return 'lobar'
    if 'MATARAM' in u_dom:
        return 'mataram'
    if any(k in u_dom for k in ['LOMBOK TENGAH', 'LOMBOK TIMUR', 'LOMBOK UTARA', 'SUMBAWA', 'DOMPU', 'BIMA']) or 'NUSA TENGGARA BARAT' in u_prov:
        return 'ntb_lain'
    if u_dom or u_prov:
        return 'luar'
    return 'unknown'

def detect_location(address):
    u_addr = (address or '').upper()
    
    lobar_keywords = ['LOMBOK BARAT', 'GERUNG', 'NARMADA', 'KEDIRI', 'LABUAPI', 'GUNUNGSARI', 'BATULAYAR', 'LEMBAR', 'SEKOTONG', 'LINGSAR', 'KURIPAN']
    for kw in lobar_keywords:
        if kw in u_addr:
            return 'Kab. Lombok Barat', 'Nusa Tenggara Barat'
            
    mataram_keywords = ['MATARAM', 'AMPENAN', 'CAKRANEGARA', 'SEKARBELA', 'SANDUBAYA', 'SELAPARANG', 'PAGUTAN', 'PEJANGGIK']
    for kw in mataram_keywords:
        if kw in u_addr:
            return 'Kota Mataram', 'Nusa Tenggara Barat'

    loteng_keywords = ['LOMBOK TENGAH', 'PRAYA', 'PUJUT', 'KOPANG', 'JONGGAT', 'PRINGGARATA', 'BATUKLIANG', 'JANAPRIA']
    for kw in loteng_keywords:
        if kw in u_addr:
            return 'Kab. Lombok Tengah', 'Nusa Tenggara Barat'

    lotim_keywords = ['LOMBOK TIMUR', 'SELONG', 'MASBAGIK', 'SAKRA', 'AIKMEL', 'LABUHAN HAJI', 'SUKAMULIA', 'TERARA', 'KERUAK']
    for kw in lotim_keywords:
        if kw in u_addr:
            return 'Kab. Lombok Timur', 'Nusa Tenggara Barat'

    klu_keywords = ['LOMBOK UTARA', 'TANJUNG', 'GANGGA', 'BAYAN', 'KAYANGAN', 'PEMENANG']
    for kw in klu_keywords:
        if kw in u_addr:
            return 'Kab. Lombok Utara', 'Nusa Tenggara Barat'

    if 'SUMBAWA BARAT' in u_addr or 'TALIWANG' in u_addr:
        return 'Kab. Sumbawa Barat', 'Nusa Tenggara Barat'
    if 'SUMBAWA' in u_addr:
        return 'Kab. Sumbawa', 'Nusa Tenggara Barat'

    if 'DOMPU' in u_addr:
        return 'Kab. Dompu', 'Nusa Tenggara Barat'
    if 'BIMA' in u_addr:
        return 'Kab./Kota Bima', 'Nusa Tenggara Barat'

    if 'DENPASAR' in u_addr or 'BALI' in u_addr or 'BADUNG' in u_addr:
        return 'Bali', 'Bali'
    if 'JAKARTA' in u_addr:
        return 'DKI Jakarta', 'DKI Jakarta'
    if 'SURABAYA' in u_addr:
        return 'Kota Surabaya', 'Jawa Timur'
    if 'BANDUNG' in u_addr:
        return 'Kota Bandung', 'Jawa Barat'
    if 'SEMARANG' in u_addr:
        return 'Kota Semarang', 'Jawa Tengah'

    return '', ''

def parse_spse_detail_html(html):
    """
    Ekstraksi data lengkap dari halaman detail verifikasi:
    https://spse.inaproc.id/lombokbaratkab/verifikasi/view?id=...
    """
    if not html:
        return {}
    soup = BeautifulSoup(html, 'html.parser')
    data = {}
    
    # 1. Scan semua baris tabel (th/td pairs atau td/td pairs)
    for tr in soup.find_all('tr'):
        cells = tr.find_all(['th', 'td'])
        i = 0
        while i < len(cells) - 1:
            lbl = cells[i].get_text(strip=True).rstrip(':*').strip().lower()
            val = cells[i+1].get_text(' ', strip=True)
            if lbl:
                data[lbl] = val
            i += 2

    # 2. Scan elemen dl/dt/dd jika ada
    for dt in soup.find_all('dt'):
        dd = dt.find_next_sibling('dd')
        if dd:
            lbl = dt.get_text(strip=True).rstrip(':*').strip().lower()
            val = dd.get_text(' ', strip=True)
            if lbl and lbl not in data:
                data[lbl] = val

    # Helper cari nilai berdasarkan kata kunci label
    def get_val(keys):
        for k in keys:
            for d_k, d_v in data.items():
                if k == d_k or k in d_k:
                    return d_v
        return ''

    full_text = soup.get_text()

    kab_raw = get_val(['kabupaten/kota', 'kabupaten / kota', 'kabupaten', 'kota'])
    if not kab_raw:
        m = re.search(r'Kabupaten\s*/\s*Kota\s*[:\t]?\s*([^\n\r<]+)', full_text, re.IGNORECASE)
        if m:
            kab_raw = m.group(1).strip()

    prov_raw = get_val(['provinsi'])
    if not prov_raw:
        m = re.search(r'Provinsi\s*[:\t]?\s*([^\n\r<]+)', full_text, re.IGNORECASE)
        if m:
            prov_raw = m.group(1).strip()

    terdaftar_raw = get_val(['terdaftar di', 'terdaftar'])
    alamat_raw = get_val(['alamat'])
    pos_raw = get_val(['kode pos'])
    bentuk_raw = get_val(['bentuk usaha'])
    user_id_raw = get_val(['user id'])
    nama_raw = get_val(['nama badan usaha', 'nama pelaku usaha'])
    telp_raw = get_val(['no. telepon', 'telepon'])
    hp_raw = get_val(['no. handphone', 'handphone', 'no. hp', 'hp'])
    status_raw = get_val(['status'])
    tgl_daftar_raw = get_val(['tanggal pendaftaran', 'tanggal daftar'])

    clean_kab = clean_kabupaten_kota(kab_raw)
    flag_lok = determine_flag_lokasi(clean_kab, prov_raw)

    phone_clean = hp_raw if hp_raw and hp_raw != '-' else (telp_raw if telp_raw and telp_raw != '-' else '')

    return {
        'kabupaten_kota_raw': kab_raw,
        'domisili_kota': clean_kab,
        'province': prov_raw,
        'flag_lokasi': flag_lok,
        'address': alamat_raw,
        'kode_pos': pos_raw,
        'bentuk_usaha': bentuk_raw,
        'user_id': user_id_raw,
        'nama_badan_usaha': nama_raw,
        'terdaftar_di': terdaftar_raw,
        'phone': phone_clean,
        'status': status_raw,
        'tanggal_daftar': tgl_daftar_raw
    }

def extract_row_data(tds, current_url=""):
    """
    Parse satu baris <tr> pada tabel daftar rekanan:
    0: Nama Penyedia (+ Alamat, No. Telepon, Link)
    1: NPWP
    2: Email
    3: Tanggal Daftar
    4: Sertifikat Elektronik
    5: Bentuk Usaha
    """
    if len(tds) < 2:
        return None

    td_nama = tds[0]
    
    # Ambil link detail dan nama penyedia
    link_elem = td_nama.find('a')
    detail_url = ''
    nama = ''
    if link_elem:
        nama = link_elem.get_text(strip=True)
        href = link_elem.get('href', '')
        if href:
            if href.startswith('http'):
                detail_url = href
            else:
                base_domain = "https://spse.inaproc.id"
                detail_url = base_domain + (href if href.startswith('/') else '/' + href)
    
    full_nama_text = td_nama.get_text('\n', strip=True)
    lines = [l.strip() for l in full_nama_text.split('\n') if l.strip()]
    
    if not nama and len(lines) > 0:
        nama = lines[0]

    # Ekstraksi Telepon
    phone = ''
    phone_match = re.search(r'(?:No\.?\s*Telepon|Telp|Telepon)\s*[:\-]?\s*([0-9\-\+\/\s]+)', full_nama_text, re.IGNORECASE)
    if phone_match:
        phone = re.sub(r'[^\d\-\+\/]', '', phone_match.group(1)).strip()

    # Ekstraksi Alamat (bersihkan nama dan baris no telepon)
    address_lines = []
    for line in lines:
        if line.lower() == nama.lower():
            continue
        if re.search(r'(?:No\.?\s*Telepon|Telp|Telepon)\s*:', line, re.IGNORECASE):
            continue
        address_lines.append(line)
    address = ' '.join(address_lines).strip()

    # NPWP
    npwp_raw = tds[1].get_text(strip=True) if len(tds) > 1 else ''
    npwp = re.sub(r'\D', '', npwp_raw)

    # Email
    email = tds[2].get_text(strip=True) if len(tds) > 2 else ''

    # Tanggal Daftar
    tgl_daftar = tds[3].get_text(strip=True) if len(tds) > 3 else ''

    # Sertifikat Elektronik
    sertifikat = tds[4].get_text(strip=True) if len(tds) > 4 else ''

    # Bentuk Usaha
    bentuk_usaha = tds[5].get_text(strip=True) if len(tds) > 5 else ''

    if not nama and not npwp:
        return None

    norm_name = normalize_name(nama)
    slug = make_slug(nama)
    kpp = parse_kpp(npwp)
    domisili_kota, province = detect_location(address)

    remote_id = 'spse_' + (npwp if npwp else norm_name[:40])

    return {
        'remote_id': remote_id,
        'contractor_name': nama,
        'contractor_name_normalized': norm_name,
        'slug': slug,
        'npwp': npwp,
        'kpp_code': kpp,
        'address': address,
        'phone': phone,
        'email': email,
        'tanggal_daftar': tgl_daftar,
        'sertifikat_elektronik': sertifikat,
        'bentuk_usaha': bentuk_usaha,
        'domisili_kota': domisili_kota,
        'province': province,
        'detail_url': detail_url
    }

def save_to_database(records):
    if not records:
        return 0

    try:
        conn = pymysql.connect(**DB_CONFIG)
        cursor = conn.cursor()
    except Exception as e:
        log(f"[!] Gagal koneksi database: {e}")
        return 0

    sql_contractor = """
        INSERT INTO `pengadaanpro_contractors` (
            remote_id, slug, contractor_name, contractor_name_normalized,
            bentuk_usaha, npwp, kpp_code, domisili_kota, province,
            address, phone, email, tanggal_daftar, sertifikat_elektronik, detail_url
        ) VALUES (
            %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s
        ) ON DUPLICATE KEY UPDATE
            contractor_name = VALUES(contractor_name),
            contractor_name_normalized = VALUES(contractor_name_normalized),
            bentuk_usaha = IF(VALUES(bentuk_usaha) != '', VALUES(bentuk_usaha), bentuk_usaha),
            npwp = IF(VALUES(npwp) != '', VALUES(npwp), npwp),
            kpp_code = IF(VALUES(kpp_code) != '', VALUES(kpp_code), kpp_code),
            domisili_kota = IF(VALUES(domisili_kota) != '', VALUES(domisili_kota), domisili_kota),
            province = IF(VALUES(province) != '', VALUES(province), province),
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
            %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, 'spse_scrap', NOW(), NOW(), NOW()
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
            last_sync_status = 'spse_scrap',
            last_sync_at = NOW(),
            updated_at = NOW()
    """

    batch_size = 500
    saved_count = 0

    for i in range(0, len(records), batch_size):
        batch = records[i:i + batch_size]
        vals_contractors = []
        vals_inaproc = []

        for r in batch:
            vals_contractors.append((
                r['remote_id'],
                r['slug'],
                r['contractor_name'],
                r['contractor_name_normalized'],
                r['bentuk_usaha'],
                r['npwp'],
                r['kpp_code'],
                r['domisili_kota'],
                r['province'],
                r['address'],
                r['phone'],
                r['email'],
                r['tanggal_daftar'],
                r['sertifikat_elektronik'],
                r['detail_url']
            ))

            kode_vendor = 'SPSE_' + (r['npwp'] if r['npwp'] else r['remote_id'])
            flag_loc = determine_flag_lokasi(r['domisili_kota'], r['province'])
            
            vals_inaproc.append((
                kode_vendor,
                r['contractor_name'],
                r['bentuk_usaha'],
                r['npwp'],
                r['kpp_code'],
                r['domisili_kota'],
                flag_loc,
                r['address'],
                r['email'],
                r['phone']
            ))

        try:
            cursor.executemany(sql_contractor, vals_contractors)
            cursor.executemany(sql_inaproc, vals_inaproc)
            conn.commit()
            saved_count += len(batch)
        except Exception as e:
            conn.rollback()
            log(f"[!] Error saat simpan batch: {e}")

    conn.close()
    return saved_count

def export_database_to_csv():
    """
    Ekspor seluruh data SPSE dari database ke Hasil_Scrape_SPSE_Rekanan.csv
    """
    csv_file = os.path.join(BASE_DIR, 'Hasil_Scrape_SPSE_Rekanan.csv')
    log(f"[*] Mengekspor seluruh database SPSE ke: {csv_file}...")
    try:
        conn = pymysql.connect(**DB_CONFIG)
        cur = conn.cursor(pymysql.cursors.DictCursor)
        cur.execute("""
            SELECT 
                remote_id, contractor_name, bentuk_usaha, npwp, kpp_code,
                address, phone, email, tanggal_daftar, sertifikat_elektronik,
                domisili_kota, province, terdaftar_di, detail_url
            FROM pengadaanpro_contractors
            WHERE remote_id LIKE 'spse_%'
            ORDER BY id ASC
        """)
        rows = cur.fetchall()
        conn.close()

        fieldnames = [
            'remote_id', 'contractor_name', 'bentuk_usaha', 'npwp', 'kpp_code',
            'address', 'phone', 'email', 'tanggal_daftar', 'sertifikat_elektronik',
            'domisili_kota', 'province', 'terdaftar_di', 'detail_url'
        ]
        with open(csv_file, 'w', newline='', encoding='utf-8-sig') as f:
            writer = csv.DictWriter(f, fieldnames=fieldnames, delimiter=';', extrasaction='ignore')
            writer.writeheader()
            for r in rows:
                writer.writerow(r)
        log(f"[OK] File CSV berhasil diperbarui ({len(rows):,} baris): {csv_file}")
    except Exception as e:
        log(f"[!] Gagal ekspor database ke CSV: {e}")

def export_to_csv(records):
    export_database_to_csv()

def enrich_details_from_db(driver):
    """
    Mengambil data detail dari https://spse.inaproc.id/lombokbaratkab/verifikasi/view?id=...
    untuk mendapatkan Kabupaten/Kota, Provinsi, Terdaftar di, dan Alamat resmi secara presisi.
    Menggunakan browser fetch asynchronous agar proses sangat cepat (~4-5 menit untuk 6.500+ data).
    """
    log("=" * 75)
    log("       MODE ENRICHMENT: LENGKAPI DETAIL DOMISILI KABUPATEN/KOTA")
    log("=" * 75)

    try:
        conn = pymysql.connect(**DB_CONFIG)
        cur = conn.cursor(pymysql.cursors.DictCursor)
        cur.execute("""
            SELECT id, remote_id, contractor_name, npwp, detail_url, domisili_kota
            FROM pengadaanpro_contractors
            WHERE remote_id LIKE 'spse_%'
              AND detail_url IS NOT NULL 
              AND detail_url != ''
              AND (detail_fetched_at IS NULL OR domisili_kota = '' OR domisili_kota IS NULL)
            ORDER BY id ASC
        """)
        items_to_enrich = cur.fetchall()
        conn.close()
    except Exception as e:
        log(f"[!] Gagal membaca data dari database: {e}")
        update_status('error', f'Gagal membaca database: {e}', mode='enrich')
        return

    total_items = len(items_to_enrich)
    if total_items == 0:
        log("[OK] Semua data rekanan SPSE di database sudah memiliki detail Kabupaten/Kota yang lengkap!")
        update_status('completed', 'Semua data rekanan sudah memiliki detail lengkap!', 0, 0, mode='enrich')
        return

    log(f"[*] Ditemukan {total_items:,} rekanan yang perlu dilengkapi detail Kabupaten/Kota & Domisili.")
    log("[*] Menjalankan pengambilan detail secara paralel melalui sesi aktif Chrome...")
    update_status('enriching', f'Mempersiapkan pengambilan detail untuk {total_items:,} rekanan...', 0, total_items, mode='enrich')

    driver.set_script_timeout(45)

    # JavaScript untuk fetch paralel di browser context (bypasses Cloudflare & reuses session)
    JS_FETCH_BATCH = """
    var items = arguments[0];
    var callback = arguments[arguments.length - 1];

    if (!items || !items.length) {
        if (typeof callback === 'function') callback([]);
        return;
    }

    Promise.all(items.map(function(item) {
        var controller = new AbortController();
        var tid = setTimeout(function() { controller.abort(); }, 10000);
        return fetch(item.url, { credentials: 'include', signal: controller.signal })
            .then(function(res) {
                clearTimeout(tid);
                if (res.ok) {
                    return res.text().then(function(t) { 
                        return { id: item.id, url: item.url, html: t, success: true }; 
                    });
                } else {
                    return { id: item.id, url: item.url, html: '', success: false, status: res.status };
                }
            })
            .catch(function(err) {
                clearTimeout(tid);
                return { id: item.id, url: item.url, html: '', success: false, error: err.toString() };
            });
    })).then(function(results) {
        if (typeof callback === 'function') {
            callback(results);
        }
    }).catch(function(e) {
        if (typeof callback === 'function') {
            callback([]);
        }
    });
    """

    BATCH_SIZE = 5
    processed_count = 0
    updated_db_count = 0
    start_time = time.time()

    # Reopen MySQL connection for streaming updates
    conn = pymysql.connect(**DB_CONFIG)
    cur = conn.cursor()

    sql_update_contractor = """
        UPDATE `pengadaanpro_contractors` SET
            domisili_kota = IF(%s != '', %s, domisili_kota),
            province = IF(%s != '', %s, province),
            terdaftar_di = IF(%s != '', %s, terdaftar_di),
            address = IF(%s != '', %s, address),
            bentuk_usaha = IF(%s != '', %s, bentuk_usaha),
            phone = IF(phone = '' AND %s != '', %s, phone),
            detail_fetched_at = NOW(),
            updated_at = NOW()
        WHERE id = %s
    """

    sql_update_inaproc = """
        UPDATE `inaproc_vendor_sync` SET
            lokasi_perusahaan = IF(%s != '', %s, lokasi_perusahaan),
            flag_lokasi = IF(%s != '', %s, flag_lokasi),
            alamat_penyedia = IF(%s != '', %s, alamat_penyedia),
            bentuk_usaha = IF(%s != '', %s, bentuk_usaha),
            telepon = IF(telepon = '' AND %s != '', %s, telepon),
            last_sync_at = NOW(),
            updated_at = NOW()
        WHERE kode_penyedia = %s OR npwp_penyedia = %s
    """

    last_dom_sample = ''

    for i in range(0, total_items, BATCH_SIZE):
        batch = items_to_enrich[i:i + BATCH_SIZE]
        batch_payload = [{'id': r['id'], 'url': r['detail_url']} for r in batch]

        try:
            results = driver.execute_async_script(JS_FETCH_BATCH, batch_payload)
        except Exception as e:
            log(f"[!] Error execute_async_script batch {i//BATCH_SIZE + 1}: {e}")
            time.sleep(1)
            continue

        if not results:
            continue

        # Cek apakah sesi login SPSE terputus
        first_html = (results[0].get('html') or '').lower()
        if 'login' in first_html and 'password' in first_html and 'verifikasi' not in first_html:
            log("[!] Sesi login SPSE kedaluwarsa. Mohon login ulang di Chrome...")
            update_status('waiting_login', 'Sesi login kedaluwarsa. Silakan login ulang di Chrome...', processed_count, total_items, mode='enrich')
            time.sleep(10)
            continue

        for res in results:
            processed_count += 1
            rec_id = res['id']
            html = res.get('html', '')
            if not html:
                continue

            parsed = parse_spse_detail_html(html)
            dom_kota = parsed.get('domisili_kota', '')
            prov = parsed.get('province', '')
            flag_lok = parsed.get('flag_lokasi', '')
            terdaftar = parsed.get('terdaftar_di', '')
            addr = parsed.get('address', '')
            bentuk = parsed.get('bentuk_usaha', '')
            phone = parsed.get('phone', '')

            if dom_kota:
                last_dom_sample = dom_kota

            # Update contractor
            cur.execute(sql_update_contractor, (
                dom_kota, dom_kota,
                prov, prov,
                terdaftar, terdaftar,
                addr, addr,
                bentuk, bentuk,
                phone, phone,
                rec_id
            ))

            # Update inaproc sync
            # Cari npwp / remote_id
            target_item = next((it for it in batch if it['id'] == rec_id), None)
            if target_item:
                kode_vendor = 'SPSE_' + (target_item['npwp'] if target_item['npwp'] else target_item['remote_id'])
                npwp_clean = target_item['npwp']
                cur.execute(sql_update_inaproc, (
                    dom_kota, dom_kota,
                    flag_lok, flag_lok,
                    addr, addr,
                    bentuk, bentuk,
                    phone, phone,
                    kode_vendor, npwp_clean
                ))

            updated_db_count += 1

        conn.commit()

        # Update log dan status setiap batch
        pct = int((processed_count / total_items) * 100)
        elapsed = time.time() - start_time
        speed = processed_count / elapsed if elapsed > 0 else 0
        rem_sec = int((total_items - processed_count) / speed) if speed > 0 else 0
        rem_str = f"{rem_sec // 60}m {rem_sec % 60}s"

        if (i // BATCH_SIZE) % 5 == 0 or processed_count >= total_items:
            log(f"[*] Detail Rekanan: {processed_count:,}/{total_items:,} ({pct}%) | Domisili Terakhir: {last_dom_sample} | Estimasi: {rem_str}")
            update_status('enriching', f'Memproses detail rekanan: {processed_count:,} / {total_items:,} ({pct}%) - Domisili: {last_dom_sample}', processed_count, total_items, mode='enrich')

        # Jeda mikro agar Chrome dan network tetap stabil
        time.sleep(0.1)

    conn.close()

    log("\n" + "=" * 75)
    log(f"[*] ENRICHMENT SELESAI: {updated_db_count:,} data rekanan berhasil diperkaya dengan Kabupaten/Kota & Domisili!")
    log("=" * 75)

    # Ekspor ulang ke CSV
    export_database_to_csv()

    update_status('completed', f'Selesai! {updated_db_count:,} rekanan berhasil dilengkapi data Kabupaten/Kota.', updated_db_count, total_items, mode='enrich')

def run_scraper(mode='enrich'):
    log("=" * 75)
    log(f"       BOT SPSE INAPROC REKANAN LOMBOK BARAT (MODE: {mode.upper()})")
    log("=" * 75)
    
    update_status('starting', 'Menyiapkan browser Chrome...', mode=mode)
    
    chrome_options = Options()
    chrome_options.add_argument(f"--user-data-dir={PROFILE_DIR}")
    chrome_options.add_argument("--disable-notifications")
    chrome_options.add_argument("--no-sandbox")
    chrome_options.add_argument("--disable-dev-shm-usage")
    chrome_options.add_argument("--window-size=1366,768")
    
    log("[*] Membuka browser Chrome...")
    try:
        driver = webdriver.Chrome(options=chrome_options)
    except Exception as e:
        log(f"[!] Gagal membuka Chrome: {e}")
        update_status('error', f'Gagal membuka Chrome: {e}', mode=mode)
        return

    try:
        log(f"[*] Mengarahkan browser ke: {TARGET_URL}")
        driver.get(TARGET_URL)
        
        print("\n" + "=" * 75)
        print("                      PETUNJUK LOGIN PENGGUNA")
        print("=" * 75)
        print(" 1. Silakan login ke akun SPSE Anda pada jendela Chrome yang terbuka.")
        print(" 2. Masukkan User ID, Password, dan selesaikan CAPTCHA jika diminta.")
        print(f" 3. Pastikan Anda berada di halaman:")
        print(f"    {TARGET_URL}")
        print(" 4. Ketika halaman verifikasi rekanan sudah tampil, bot otomatis bekerja!")
        print("=" * 75 + "\n")
        
        update_status('waiting_login', 'Menunggu pengguna login di Chrome...', mode=mode)

        logged_in = False
        while not logged_in:
            cur_url = driver.current_url.lower()
            if 'verifikasi' in cur_url and 'login' not in cur_url:
                tables = driver.find_elements(By.TAG_NAME, 'table')
                if len(tables) > 0:
                    body_text = driver.find_element(By.TAG_NAME, 'body').text.lower()
                    if 'nama penyedia' in body_text or 'rekanan' in body_text or 'detail' in body_text:
                        log("[+] Sesi Verifikasi Rekanan terdeteksi aktif & siap!")
                        logged_in = True
                        break
            try:
                time.sleep(2)
            except KeyboardInterrupt:
                log("[!] Dibatalkan oleh pengguna.")
                return

        time.sleep(2)

        # EKSEKUSI SESUAI MODE
        if mode == 'enrich':
            enrich_details_from_db(driver)
            
        elif mode == 'scrape':
            # MODE SCRAPING DAFTAR REKANAN
            log("[*] Mulai menganalisis tabel data rekanan...")
            update_status('scraping', 'Memeriksa struktur DataTables pada halaman...', mode='scrape')
            time.sleep(2)

            # Ubah length dropdown ke maksimum jika tersedia
            try:
                length_selects = driver.find_elements(By.CSS_SELECTOR, "select[name$='_length'], select.form-select, select[name='length']")
                for sel in length_selects:
                    s = Select(sel)
                    options = [o.get_attribute('value') for o in s.options if o.get_attribute('value') and o.get_attribute('value').isdigit()]
                    if options:
                        max_opt = max(options, key=lambda x: int(x))
                        s.select_by_value(max_opt)
                        log(f"[*] Mengubah tampilan tabel menjadi {max_opt} data per halaman.")
                        time.sleep(3)
                        break
            except Exception as e:
                log(f"[-] Catatan pengaturan length tabel: {e}")

            all_records = []
            seen_keys = set()

            ajax_info = driver.execute_script("""
                try {
                    if (window.jQuery && $.fn && $.fn.dataTable) {
                        var tables = $.fn.dataTable.tables();
                        if (tables.length > 0) {
                            var dt = $(tables[0]).DataTable();
                            return {
                                ajax_url: dt.ajax.url() || null,
                                total: dt.page.info() ? dt.page.info().recordsTotal : null
                            };
                        }
                    }
                } catch(e) {}
                return null;
            """)

            total_reported = None
            if ajax_info and ajax_info.get('total'):
                total_reported = ajax_info.get('total')
                log(f"[i] Total rekanan terdaftar menurut SPSE: {total_reported:,} rekanan.")

            log("[*] Memulai pembacaan seluruh halaman tabel rekanan...")
            page_num = 1
            consecutive_zero = 0
            total_saved_db = 0

            while True:
                log(f"[*] Memproses Halaman {page_num}...")
                time.sleep(1.5)

                soup = BeautifulSoup(driver.page_source, 'html.parser')
                
                target_table = None
                for tbl in soup.find_all('table'):
                    headers = [th.get_text(strip=True).lower() for th in tbl.find_all('th')]
                    if any('nama penyedia' in h for h in headers):
                        target_table = tbl
                        break

                if not target_table:
                    log("[!] Tabel rekanan tidak ditemukan pada halaman ini.")
                    break

                tbody = target_table.find('tbody')
                rows = tbody.find_all('tr') if tbody else target_table.find_all('tr')

                page_records = []
                for tr in rows:
                    tds = tr.find_all('td')
                    if len(tds) < 2:
                        continue

                    rec = extract_row_data(tds, current_url=driver.current_url)
                    if rec:
                        uniq_key = rec['npwp'] if rec['npwp'] else rec['contractor_name_normalized']
                        if uniq_key not in seen_keys:
                            seen_keys.add(uniq_key)
                            all_records.append(rec)
                            page_records.append(rec)

                if len(page_records) > 0:
                    saved_page = save_to_database(page_records)
                    total_saved_db += saved_page
                    consecutive_zero = 0
                    log(f"  -> Halaman {page_num}: {len(page_records)} rekanan baru langsung tersimpan ke Database. (Total di DB: {total_saved_db:,})")
                    update_status('saving', f'Menyimpan Halaman {page_num}: {total_saved_db:,} rekanan tersimpan di DB...', total_saved_db, total_reported or total_saved_db, mode='scrape')
                else:
                    consecutive_zero += 1
                    log(f"  -> Halaman {page_num}: 0 rekanan baru terdeteksi. (Pengecekan akhir: {consecutive_zero}/2)")
                    if consecutive_zero >= 2:
                        log("[*] Sudah tidak ada data baru lagi yang ditemukan. Menghentikan scraping daftar...")
                        break

                # Cek apakah DataTables sudah mencapai halaman terakhir
                is_last_page = False
                try:
                    is_last_page = driver.execute_script("""
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
                    """)
                except Exception:
                    pass

                if is_last_page:
                    log(f"[*] Halaman terakhir DataTables tercapai pada Halaman {page_num}. Ekstraksi daftar tuntas!")
                    break

                # Cari tombol Next
                next_btn = None
                try:
                    possible_selectors = [
                        "li.paginate_button.next:not(.disabled) a",
                        ".paginate_button.next:not(.disabled)",
                        "a[id$='_next']:not(.disabled)",
                        "#table-rekanan_next:not(.disabled) a",
                        "button.next:not(:disabled)"
                    ]
                    for sel in possible_selectors:
                        elements = driver.find_elements(By.CSS_SELECTOR, sel)
                        if elements and elements[0].is_displayed() and elements[0].is_enabled():
                            classes = elements[0].get_attribute('class') or ''
                            parent_classes = elements[0].find_element(By.XPATH, '..').get_attribute('class') or ''
                            if 'disabled' not in classes.lower() and 'disabled' not in parent_classes.lower():
                                next_btn = elements[0]
                                break
                except Exception:
                    pass

                if next_btn:
                    try:
                        driver.execute_script("arguments[0].scrollIntoView(true);", next_btn)
                        time.sleep(0.5)
                        driver.execute_script("arguments[0].click();", next_btn)
                        page_num += 1
                        time.sleep(2)
                    except Exception as e:
                        log(f"[!] Gagal klik tombol halaman berikutnya: {e}")
                        break
                else:
                    log("[*] Tidak ada tombol halaman selanjutnya (sudah mencapai halaman terakhir).")
                    break

            log("\n" + "=" * 75)
            log(f"[*] SELESAI: Berhasil mengumpulkan & menyimpan {total_saved_db:,} rekanan SPSE ke database!")
            log("=" * 75)

            export_database_to_csv()
            update_status('completed', f'Selesai! {total_saved_db:,} rekanan berhasil tersimpan di database.', total_saved_db, total_saved_db, mode='scrape')

        # Tutup browser
        try:
            time.sleep(3)
            driver.quit()
            log("[*] Browser Chrome ditutup.")
        except Exception:
            pass

    except Exception as e:
        log(f"[!] Terjadi kesalahan pada bot: {e}")
        update_status('error', f'Error: {e}', mode=mode)

if __name__ == '__main__':
    selected_mode = 'enrich'

    # Cek argumen CLI: --mode=enrich atau --mode=scrape
    for arg in sys.argv[1:]:
        if arg.startswith('--mode='):
            m = arg.split('=', 1)[1].lower().strip().strip('"').strip("'")
            if m in ['enrich', 'scrape']:
                selected_mode = m

    run_scraper(mode=selected_mode)
