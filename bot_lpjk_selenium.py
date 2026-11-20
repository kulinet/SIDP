import sys
import os
import re
import time
import json
import csv
import pymysql

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')
if hasattr(sys.stderr, 'reconfigure'):
    sys.stderr.reconfigure(encoding='utf-8')

import argparse
from bs4 import BeautifulSoup
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'sidp_new',
    'charset': 'utf8mb4'
}

STATUS_FILE = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'writable', 'logs', 'bot_lpjk_status.json')
LOG_FILE = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'writable', 'logs', 'bot_lpjk.log')

def log(msg):
    print(msg, flush=True)
    try:
        os.makedirs(os.path.dirname(LOG_FILE), exist_ok=True)
        with open(LOG_FILE, 'a', encoding='utf-8') as f:
            f.write(f"[{time.strftime('%H:%M:%S')}] {msg}\n")
    except Exception:
        pass

def update_status(status, message, count=0, total=0):
    try:
        os.makedirs(os.path.dirname(STATUS_FILE), exist_ok=True)
        data = {
            'status': status,
            'message': message,
            'count': count,
            'total': total,
            'updated_at': time.strftime('%Y-%m-%d %H:%M:%S')
        }
        with open(STATUS_FILE, 'w', encoding='utf-8') as f:
            json.dump(data, f, ensure_ascii=False)
    except Exception:
        pass

def parse_html_table(html_content):
    """
    Ekstraksi super cepat seluruh baris tabel HTML menggunakan regex iterator.
    """
    print("[*] Memulai pemindaian data tabel kontraktor...")
    start_time = time.time()
    
    records = []
    tr_pattern = re.compile(r'<tr[^>]*>(.*?)</tr>', re.DOTALL | re.IGNORECASE)
    td_pattern = re.compile(r'<td[^>]*>(.*?)</td>', re.DOTALL | re.IGNORECASE)
    tag_cleaner = re.compile(r'<[^>]+>')
    attr_pattern = re.compile(r'data-attr=["\']([^"\']+)["\']', re.IGNORECASE)

    count = 0
    for tr_match in tr_pattern.finditer(html_content):
        tr_html = tr_match.group(1)
        tds = td_pattern.findall(tr_html)
        
        if len(tds) >= 5:
            col_no = tag_cleaner.sub('', tds[0]).strip()
            if col_no.lower().startswith('no') or col_no.lower().startswith('th'):
                continue
                
            nama = tag_cleaner.sub('', tds[1]).strip()
            if not nama or nama.lower() == 'nama badan usaha':
                continue
                
            provinsi = tag_cleaner.sub('', tds[2]).strip()
            kabupaten = tag_cleaner.sub('', tds[3]).strip()
            npwp_raw = tag_cleaner.sub('', tds[4]).strip()
            npwp = re.sub(r'\D', '', npwp_raw)
            
            detail_attr = ''
            if len(tds) >= 6:
                m_attr = attr_pattern.search(tds[5])
                if m_attr:
                    detail_attr = m_attr.group(1)
            
            norm_name = re.sub(r'^(pt|cv|ud|fa|koperasi|kop|toko)\.?\s+', '', nama, flags=re.IGNORECASE)
            norm_name = re.sub(r'[^a-zA-Z0-9]', '', norm_name).lower()
            
            slug = re.sub(r'[^a-zA-Z0-9\s-]', '', nama).strip().lower()
            slug = re.sub(r'[\s_]+', '-', slug)
            
            records.append({
                'name': nama,
                'norm_name': norm_name,
                'slug': slug,
                'province': provinsi,
                'kabupaten': kabupaten,
                'npwp': npwp,
                'npwp_formatted': npwp_raw,
                'detail_url': detail_attr
            })
            count += 1
            if count % 10000 == 0:
                print(f"  -> {count:,} kontraktor diekstrak...")

    elapsed = time.time() - start_time
    print(f"[+] SELESAI! Berhasil mengekstrak {len(records):,} kontraktor dalam {elapsed:.2f} detik.")
    return records

def save_to_files(records, base_name="lpjk_contractors_master"):
    if not records:
        return

    json_path = f"c:/laragon/www/infoajip/{base_name}.json"
    csv_path = f"c:/laragon/www/infoajip/{base_name}.csv"
    sikap_csv_path = "c:/laragon/www/infoajip/Hasil_Bot_SIKaP.csv"
    
    log(f"[*] Menyimpan {len(records):,} data ke {json_path}...")
    with open(json_path, 'w', encoding='utf-8') as f:
        json.dump(records, f, ensure_ascii=False, indent=2)
        
    log(f"[*] Menyimpan ke {csv_path} (Format CSV UTF-8 Excel)...")
    with open(csv_path, 'w', encoding='utf-8-sig', newline='') as f:
        writer = csv.writer(f, delimiter=';')
        writer.writerow(['No', 'Nama Perusahaan / Kontraktor', 'NPWP', 'Kabupaten / Kota', 'Provinsi', 'Detail SBU URL'])
        for idx, r in enumerate(records, start=1):
            writer.writerow([idx, r['name'], r['npwp_formatted'] or r['npwp'], r['kabupaten'], r['province'], r['detail_url']])
            
    try:
        import shutil
        shutil.copyfile(csv_path, sikap_csv_path)
        log(f"[+] File referensi Hasil_Bot_SIKaP.csv otomatis disinkronkan & diperbarui!")
    except Exception as e:
        log(f"[!] Gagal memperbarui Hasil_Bot_SIKaP.csv: {e}")
        
    log(f"[+] File cadangan JSON & CSV berhasil disimpan!")

def save_to_database(records):
    if not records:
        return
        
    log(f"[*] Menghubungkan ke database MySQL sidp_new...")
    try:
        conn = pymysql.connect(**DB_CONFIG)
        cursor = conn.cursor()
    except Exception as e:
        log(f"[!] Gagal koneksi ke database: {e}")
        update_status('error', f'Gagal koneksi database MySQL: {e}')
        return

    log(f"[*] Melakukan bulk upsert {len(records):,} kontraktor ke tabel `pengadaanpro_contractors`...")
    update_status('saving', f'Memulai simpan {len(records):,} kontraktor ke database...', 0, len(records))
    batch_size = 2000
    total_saved = 0
    start_time = time.time()

    sql = """
        INSERT INTO `pengadaanpro_contractors` (
            remote_id, slug, contractor_name, contractor_name_normalized,
            npwp, domisili_kota, province, address, detail_url
        ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)
        ON DUPLICATE KEY UPDATE
            contractor_name = VALUES(contractor_name),
            domisili_kota = IF(VALUES(domisili_kota) != '' AND VALUES(domisili_kota) IS NOT NULL, VALUES(domisili_kota), domisili_kota),
            province = IF(VALUES(province) != '' AND VALUES(province) IS NOT NULL, VALUES(province), province),
            npwp = IF(VALUES(npwp) != '' AND VALUES(npwp) IS NOT NULL, VALUES(npwp), npwp),
            detail_url = IF(VALUES(detail_url) != '' AND VALUES(detail_url) IS NOT NULL, VALUES(detail_url), detail_url),
            updated_at = CURRENT_TIMESTAMP
    """

    for i in range(0, len(records), batch_size):
        batch = records[i:i + batch_size]
        values = []
        for r in batch:
            remote_id = 'lpjk_' + (r['npwp'] if r['npwp'] else r['slug'][:50])
            slug = r['slug'][:150]
            name = r['name'][:255]
            norm_name = r['norm_name'][:255]
            npwp = r['npwp'][:35]
            kabupaten = r['kabupaten'][:100]
            province = r['province'][:100]
            address = f"{kabupaten}, {province}".strip(', ')
            detail_url = r.get('detail_url', '')[:500]
            values.append((remote_id, slug, name, norm_name, npwp, kabupaten, province, address, detail_url))

        try:
            cursor.executemany(sql, values)
            conn.commit()
            total_saved += len(values)
            log(f"  -> {total_saved:,} / {len(records):,} data tersimpan ke MySQL...")
            update_status('saving', f'Menyimpan {total_saved:,} / {len(records):,} data ke MySQL...', total_saved, len(records))
        except Exception as e:
            log(f"[!] Error batch {i}: {e}")
            conn.rollback()

    cursor.close()
    conn.close()
    elapsed = time.time() - start_time
    log(f"[+] Bulk upsert SELESAI! {total_saved:,} kontraktor berhasil diperbarui dalam {elapsed:.2f} detik.")

def parse_lpjk_modal(html_content):
    """
    Parser HTML modal detail LPJK PUPR (Administrasi, Legalitas, Pengurus, SBU)
    """
    soup = BeautifulSoup(html_content, 'html.parser')
    result = {
        'admin': {},
        'legalitas': [],
        'pengurus': [],
        'sbu': []
    }
    
    # 1. Parse Data Administrasi
    for tr in soup.find_all('tr'):
        tds = [td.get_text(strip=True) for td in tr.find_all(['td', 'th'])]
        if len(tds) >= 2:
            label = tds[0].lower().rstrip(' :')
            val = tds[2] if len(tds) >= 3 and tds[1] == ':' else tds[1].lstrip(': ')
            
            if 'nama badan usaha' in label:
                result['admin']['nama'] = val
            elif 'alamat' in label:
                result['admin']['alamat'] = val
            elif 'telepon' in label:
                result['admin']['telepon'] = val
            elif 'email' in label:
                result['admin']['email'] = val
            elif 'nib' in label or 'induk berusaha' in label:
                result['admin']['nib'] = val
            elif 'npwp' in label or 'pokok wajib pajak' in label:
                result['admin']['npwp'] = val
            elif 'penanggungjawab badan usaha' in label or 'pjbu' in label:
                result['admin']['pjbu'] = val
            elif 'penanggungjawab teknik' in label or 'pjtbu' in label:
                result['admin']['pjtbu'] = val

    # 2. Parse Tables
    for tbl in soup.find_all('table'):
        headers = [th.get_text(strip=True).lower() for th in tbl.find_all('th')]
        tbody = tbl.find('tbody')
        rows = tbody.find_all('tr') if tbody else tbl.find_all('tr')[1:]
        
        # Legalitas: Akte, Notaris, Pengesahan AHU
        if any('akte' in h for h in headers) or any('notaris' in h for h in headers):
            for tr in rows:
                cols = [td.get_text(strip=True) for td in tr.find_all('td')]
                if len(cols) >= 4:
                    result['legalitas'].append({
                        'no': cols[0],
                        'nomor_akte': cols[1],
                        'tanggal': cols[2],
                        'nama_notaris': cols[3],
                        'nomor_pengesahan': cols[4] if len(cols) > 4 else ''
                    })
                    
        # Pengurus: Nama Pengurus, Jabatan
        elif any('nama pengurus' in h or 'pengurus' in h for h in headers) and any('jabatan' in h for h in headers):
            for tr in rows:
                cols = [td.get_text(strip=True) for td in tr.find_all('td')]
                if len(cols) >= 2:
                    nama = cols[1] if len(cols) >= 3 else cols[0]
                    jabatan = cols[2] if len(cols) >= 3 else cols[1]
                    result['pengurus'].append({
                        'nama': nama,
                        'jabatan': jabatan
                    })

        # SBU: Klasifikasi / Kualifikasi KBLI
        elif any('subklasifikasi' in h or 'kode' in h for h in headers) and any('kualifikasi' in h for h in headers):
            for tr in rows:
                cols = [td.get_text(strip=True) for td in tr.find_all('td')]
                if len(cols) >= 5:
                    if len(cols) >= 9:
                        result['sbu'].append({
                            'id_izin': cols[1],
                            'subklasifikasi': cols[2],
                            'kode_sbu': cols[3],
                            'kualifikasi': cols[4],
                            'penerbit': cols[5],
                            'tgl_terbit': cols[6],
                            'tgl_habis': cols[7],
                            'status': cols[8]
                        })
                    else:
                        result['sbu'].append({
                            'subklasifikasi': cols[1] if len(cols) > 1 else '',
                            'kode_sbu': cols[2] if len(cols) > 2 else '',
                            'kualifikasi': cols[3] if len(cols) > 3 else '',
                            'penerbit': cols[4] if len(cols) > 4 else ''
                        })

    return result

def extract_modal_details_turbo(driver, records, scope='ntb'):
    """
    Turbo Parallel Modal Extractor:
    Mengekstrak data modal detail langsung di dalam session browser Chrome
    secara paralel (batch Promise.all + jQuery.ajax) sehingga super cepat
    dan bebas issue CSRF / Captcha.
    """
    if not records:
        return

    # Filter target berdasarkan scope
    targets = []
    scope_lower = (scope or 'ntb').lower()
    
    for r in records:
        url = r.get('detail_url', '').strip()
        if not url:
            continue
        
        prov = (r.get('province') or '').lower()
        kab = (r.get('kabupaten') or '').lower()
        
        if scope_lower == 'ntb':
            if 'nusa tenggara barat' in prov or 'ntb' in prov or 'lombok' in kab or 'sumbawa' in kab or 'mataram' in kab or 'bima' in kab or 'dompu' in kab:
                targets.append(r)
        else:
            targets.append(r)
            
    if not targets:
        log(f"[*] Tidak ada kontraktor dengan detail_url untuk scope '{scope}'.")
        return

    log(f"\n" + "="*70)
    log(f"⚡ TURBO DETAIL EXTRACTOR: {len(targets):,} KONTRAKTOR TARGET (Scope: {scope.upper()})")
    log("="*70)
    update_status('extracting_details', f'Memulai ekstraksi modal detail ({len(targets):,} kontraktor)...', 0, len(targets))

    try:
        conn = pymysql.connect(**DB_CONFIG)
        cursor = conn.cursor()
    except Exception as e:
        log(f"[!] Gagal koneksi database: {e}")
        return

    js_fetch_item = """
    let callback = arguments[arguments.length - 1];
    let url = arguments[0];
    
    if (window.jQuery && jQuery.ajax) {
        jQuery.ajax({
            url: url,
            type: 'POST',
            timeout: 15000,
            success: function(html) {
                callback({ ok: true, html: html });
            },
            error: function(xhr, status, error) {
                callback({ ok: false, error: error, status: xhr.status, html: xhr.responseText || '' });
            }
        });
    } else {
        fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.text().then(text => ({ status: r.status, text: text })))
        .then(res => {
            if (res.status === 200) {
                callback({ ok: true, html: res.text });
            } else {
                callback({ ok: false, status: res.status, html: res.text, error: 'HTTP ' + res.status });
            }
        })
        .catch(err => {
            callback({ ok: false, error: err.toString() });
        });
    }
    """

    completed = 0
    saved_count = 0
    start_time = time.time()

    for idx, contractor in enumerate(targets):
        url = contractor.get('detail_url', '').strip()
        if not url:
            continue
            
        try:
            res = driver.execute_async_script(js_fetch_item, url)
        except Exception as e:
            log(f"[!] Error fetch detail {contractor['name']}: {e}")
            res = None

        if not res:
            continue
            
        modal_html = res.get('html', '')
        if '429' in modal_html or res.get('status') == 429:
            log(f"[!] Terkena limit LPJK (HTTP 429). Istirahat sejenak 5 detik...")
            time.sleep(5)
            # Coba 1x lagi
            try:
                res = driver.execute_async_script(js_fetch_item, url)
                modal_html = res.get('html', '') if res else ''
            except Exception:
                pass

        if not res or not res.get('ok') or not modal_html or '429' in modal_html:
            continue
            
        parsed = parse_lpjk_modal(modal_html)
        adm = parsed.get('admin', {})
        legal = parsed.get('legalitas', [])
        pengurus = parsed.get('pengurus', [])
        sbu = parsed.get('sbu', [])
        
        # Hanya simpan jika minimal ada data administrasi / legalitas / sbu
        if not adm and not legal and not pengurus and not sbu:
            continue

        alamat = adm.get('alamat', '').strip()
        telepon = adm.get('telepon', '').strip()
        email = adm.get('email', '').strip()
        nib = adm.get('nib', '').strip()
        pjbu = adm.get('pjbu', '').strip()
        pjtbu = adm.get('pjtbu', '').strip()
        
        direktur = pjbu
        if not direktur and pengurus:
            for p in pengurus:
                if 'direktur' in p.get('jabatan', '').lower():
                    direktur = p.get('nama', '')
                    break
            if not direktur:
                direktur = pengurus[0].get('nama', '')
                
        sbu_codes = ', '.join(filter(None, [s.get('kode_sbu') for s in sbu]))
            remote_id = 'lpjk_' + (contractor['npwp'] if contractor['npwp'] else contractor['slug'][:50])
            npwp = contractor['npwp']
            
            url = contractor.get('detail_url', '')
            norm = contractor.get('norm_name', '')
            c_name = contractor.get('name', '').strip()
            
            try:
                cursor.execute("""
                    UPDATE pengadaanpro_contractors SET
                        address = IF(%s != '', %s, address),
                        phone = IF(%s != '', %s, phone),
                        email = IF(%s != '', %s, email),
                        nib = IF(%s != '', %s, nib),
                        pjbu = IF(%s != '', %s, pjbu),
                        pjtbu = IF(%s != '', %s, pjtbu),
                        direktur = IF(%s != '', %s, direktur),
                        legalitas_json = %s,
                        pengurus_json = %s,
                        sbu_json = %s,
                        sbu_codes = IF(%s != '', %s, sbu_codes),
                        detail_fetched_at = NOW()
                    WHERE (detail_url = %s AND %s != '')
                       OR remote_id = %s 
                       OR (contractor_name_normalized = %s AND %s != '')
                       OR (npwp = %s AND %s != '' AND %s NOT LIKE '100000000%%')
                """, (
                    alamat, alamat,
                    telepon, telepon,
                    email, email,
                    nib, nib,
                    pjbu, pjbu,
                    pjtbu, pjtbu,
                    direktur, direktur,
                    json.dumps(legal, ensure_ascii=False),
                    json.dumps(pengurus, ensure_ascii=False),
                    json.dumps(sbu, ensure_ascii=False),
                    sbu_codes, sbu_codes,
                    url, url,
                    remote_id,
                    norm, norm,
                    npwp, npwp, npwp
                ))
                conn.commit()
                
                # Auto-enrich inaproc_vendor_sync
                if c_name or norm:
                    cursor.execute("""
                        UPDATE inaproc_vendor_sync SET
                            alamat_penyedia = IF(%s != '', %s, alamat_penyedia),
                            telepon = IF(%s != '', %s, telepon),
                            email = IF(%s != '', %s, email),
                            nib = IF(%s != '', %s, nib)
                        WHERE UPPER(TRIM(nama_penyedia)) = %s
                           OR REPLACE(REPLACE(REPLACE(UPPER(nama_penyedia), 'PT ', ''), 'CV ', ''), '.', '') = %s
                           OR (npwp_penyedia = %s AND %s != '' AND %s NOT LIKE '100000000%%')
                    """, (alamat, alamat, telepon, telepon, email, email, nib, nib, c_name.upper(), norm.upper(), npwp, npwp, npwp))
                    conn.commit()
                saved_count += 1
            except Exception as ex:
                log(f"[!] Update error on {contractor['name']}: {ex}")

        completed = idx + 1
        name_sample = contractor.get('name', '')
        if completed % 5 == 0 or completed == len(targets):
            log(f"  -> [{completed:,}/{len(targets):,}] ({saved_count:,} detail tersimpan) Terakhir: {name_sample} ({time.time() - start_time:.1f}s)")
            update_status('extracting_details', f'Mengekstrak modal: {completed:,} / {len(targets):,} kontraktor ({saved_count:,} tersimpan)...', completed, len(targets))
        
        # Delay aman agar server LPJK tidak me-return HTTP 429 (Too Many Requests)
        time.sleep(0.7)

    cursor.close()
    conn.close()
    elapsed = time.time() - start_time
    log(f"[+] SELESAI! {completed:,} profil modal detail kontraktor berhasil diekstrak & disimpan dalam {elapsed:.1f} detik.")

def run_bot(fetch_details=True, scope='ntb', details_only=False):
    update_status('running', 'Membuka browser Chrome LPJK...', 0, 0)
    log("\n" + "="*70)
    log(f"🚀 MEMULAI BOT OTOMATIS LPJK (SELENIUM) [Mode Detail: {'AKTIF' if fetch_details else 'NONAKTIF'}, Scope: {scope.upper()}, Details Only: {'YA' if details_only else 'TIDAK'}]")
    log("="*70)
    
    options = Options()
    options.add_argument("--start-maximized")
    options.add_argument("--disable-blink-features=AutomationControlled")
    options.add_argument("--remote-allow-origins=*")
    options.add_experimental_option("excludeSwitches", ["enable-automation"])
    options.add_experimental_option("useAutomationExtension", False)
    # Cegah browser Chrome tertutup otomatis saat script selesai
    options.add_experimental_option("detach", True)

    log("[*] Membuka browser Chrome...")
    try:
        driver = webdriver.Chrome(options=options)
    except Exception as e:
        log(f"[!] Gagal membuka Chrome: {e}")
        update_status('error', f'Gagal membuka Chrome: {e}')
        return
    
    target_url = "https://lpjk.pu.go.id/laporan-lpjk/sebaran/cari"
    log(f"[*] Mengarahkan ke: {target_url}")
    try:
        driver.get(target_url)
    except Exception as e:
        log(f"[!] Gagal memuat halaman: {e}")
        update_status('error', f'Gagal memuat URL LPJK: {e}')
        return

    # JIKA MODE KHUSUS EKSTRAKSI MODAL DARI DATABASE YANG SUDAH ADA
    if details_only:
        log("\n[*] Mengambil daftar kontraktor yang sudah memiliki detail_url dari database...")
        try:
            conn = pymysql.connect(**DB_CONFIG, cursorclass=pymysql.cursors.DictCursor)
            cur = conn.cursor()
            cur.execute("""
                SELECT id, contractor_name as name, contractor_name_normalized as norm_name,
                       slug, province, domisili_kota as kabupaten, npwp, detail_url
                FROM pengadaanpro_contractors
                WHERE detail_url IS NOT NULL AND detail_url != '' AND legalitas_json IS NULL
                ORDER BY id ASC
            """)
            records = cur.fetchall()
            cur.close()
            conn.close()
        except Exception as e:
            log(f"[!] Gagal membaca database: {e}")
            return

        log(f"[+] Ditemukan {len(records):,} kontraktor yang siap diekstrak modal detailnya!")
        update_status('waiting_captcha', 'Silakan selesaikan reCAPTCHA di browser Chrome agar sesi aktif...', 0, 0)
        log("\n" + "="*70)
        log("👉 INSTRUKSI PENGGUNA:")
        log("1. Browser Chrome sudah terbuka di layar.")
        log("2. Selesaikan reCAPTCHA sekali saja lalu klik Search (agar cookie sesi aktif).")
        log("3. Setelah halaman selesai dimuat, proses ekstraksi modal detail akan langsung berjalan.")
        log("="*70)
        
        # Tunggu sampai sesi siap (jQuery / reCAPTCHA selesai)
        time.sleep(10)
        extract_modal_details_turbo(driver, records, scope=scope)
        log("\n" + "="*70)
        log(f"🎉 SUKSES! Ekstraksi profil modal detail LPJK selesai!")
        log("="*70 + "\n")
        update_status('finished', f'Selesai! Ekstraksi modal detail kontraktor berhasil disimpan.', len(records), len(records))
        return

    update_status('waiting_captcha', 'Browser LPJK terbuka di layar. Silakan selesaikan reCAPTCHA & klik Search di Chrome.', 0, 0)
    log("\n" + "="*70)
    log("👉 INSTRUKSI PENGGUNA:")
    log("1. Jendela browser Chrome LPJK sudah terbuka di layar Anda.")
    log("2. Pilih filter yang Anda inginkan (atau biarkan kosong untuk semua data).")
    log("3. Centang reCAPTCHA di halaman web.")
    log("4. Klik tombol 'Search'.")
    log("="*70)
    log("⏳ Bot sedang standby memantau... Begitu data muncul, bot akan MENARIK OTOMATIS!\n")

    # Loop pemantauan sampai tabel muncul dengan data
    extracted = False
    try:
        while True:
            time.sleep(1.5)
            try:
                curr_url = driver.current_url
            except Exception:
                log("[!] Jendela browser Chrome ditutup oleh pengguna.")
                update_status('stopped', 'Browser Chrome ditutup oleh pengguna.')
                break
            
            # Cek jika tabel #TABLE_1 sudah ada di halaman
            try:
                table_exists = driver.execute_script("return document.getElementById('TABLE_1') !== null;")
                if table_exists:
                    # 1. Cek jumlah baris melalui DataTables API (jika aktif)
                    dt_count = driver.execute_script("""
                        if (window.jQuery && jQuery.fn.dataTable && jQuery('#TABLE_1').DataTable) {
                            try { return jQuery('#TABLE_1').DataTable().rows().count(); } catch(e) {}
                        }
                        return 0;
                    """)
                    
                    # 2. Cek jumlah baris melalui DOM biasa
                    dom_count = driver.execute_script("""
                        return document.querySelectorAll('#TABLE_1 tbody tr').length;
                    """)
                    
                    total_detected = max(dt_count or 0, dom_count or 0)
                    
                    if total_detected > 0:
                        # Cek apakah bukan teks loading
                        is_loading = driver.execute_script("""
                            let firstTr = document.querySelector('#TABLE_1 tbody tr');
                            if (!firstTr) return false;
                            let txt = firstTr.innerText.toLowerCase();
                            return txt.includes('loading') || txt.includes('no data') || txt.includes('no matching');
                        """)
                        
                        if not is_loading:
                            log(f"\n[!] DATA TERDETEKSI DI BROWSER! (Total terdeteksi: {total_detected:,} baris)")
                            log("[*] Mengekstrak seluruh data dari browser...")
                            update_status('extracting', f'Data terdeteksi ({total_detected:,} baris)! Mengekstrak tabel utama...', 0, total_detected)
                            
                            records = []
                            
                            # Jika DataTables aktif dan punya data
                            if dt_count and dt_count > 0:
                                log(f"[*] Mengambil {dt_count:,} data via DataTables engine...")
                                raw_dt_data = driver.execute_script("""
                                    let dt = jQuery('#TABLE_1').DataTable();
                                    let data = dt.rows().data().toArray();
                                    let clean = function(s) { return typeof s === 'string' ? s.replace(/<[^>]+>/g, '').trim() : (s || ''); };
                                    let results = [];
                                    for (let i = 0; i < data.length; i++) {
                                        let row = data[i];
                                        let nama = clean(row[1]);
                                        let prov = clean(row[2]);
                                        let kab = clean(row[3]);
                                        let npwp_raw = clean(row[4]);
                                        let detail = '';
                                        if (row[5] && typeof row[5] === 'string') {
                                            let m = row[5].match(/data-attr=["']([^"']+)["']/i);
                                            if (m) detail = m[1];
                                        }
                                        results.push([nama, prov, kab, npwp_raw, detail]);
                                    }
                                    return results;
                                """)
                                
                                if raw_dt_data:
                                    tag_cleaner = re.compile(r'<[^>]+>')
                                    for item in raw_dt_data:
                                        nama = str(item[0]).strip()
                                        provinsi = str(item[1]).strip()
                                        kabupaten = str(item[2]).strip()
                                        npwp_raw = str(item[3]).strip()
                                        detail_attr = str(item[4]).strip()
                                        npwp = re.sub(r'\D', '', npwp_raw)
                                        
                                        norm_name = re.sub(r'^(pt|cv|ud|fa|koperasi|kop|toko)\.?\s+', '', nama, flags=re.IGNORECASE)
                                        norm_name = re.sub(r'[^a-zA-Z0-9]', '', norm_name).lower()
                                        slug = re.sub(r'[^a-zA-Z0-9\s-]', '', nama).strip().lower()
                                        slug = re.sub(r'[\s_]+', '-', slug)
                                        
                                        records.append({
                                            'name': nama,
                                            'norm_name': norm_name,
                                            'slug': slug,
                                            'province': provinsi,
                                            'kabupaten': kabupaten,
                                            'npwp': npwp,
                                            'npwp_formatted': npwp_raw,
                                            'detail_url': detail_attr
                                        })
                            
                            # Fallback jika DataTables tidak dipakai / gagal
                            if not records:
                                log("[*] Mengambil data dari source HTML DOM...")
                                page_html = driver.page_source
                                records = parse_html_table(page_html)
                                
                            if records:
                                log(f"[+] Total {len(records):,} kontraktor berhasil diekstrak!")
                                save_to_files(records)
                                save_to_database(records)
                                extracted = True
                                
                                # EKSTRAKSI MODAL DETAIL TURBO
                                if fetch_details:
                                    extract_modal_details_turbo(driver, records, scope=scope)
                                    
                                log("\n" + "="*70)
                                log(f"🎉 SUKSES BESAR! {len(records):,} DATA KONTRAKTOR & PROFIL MODAL LPJK BERHASIL DISIMPAN!")
                                log("="*70 + "\n")
                                update_status('finished', f'Selesai! {len(records):,} data kontraktor & profil modal detail berhasil disimpan ke database.', len(records), len(records))
                                break
            except Exception as e:
                pass
    except KeyboardInterrupt:
        log("\n[!] Bot dihentikan oleh pengguna.")
        update_status('stopped', 'Bot dihentikan oleh pengguna.')
    except Exception as e:
        log(f"\n[!] Error: {e}")
        update_status('error', f'Error: {e}')

    if extracted:
        log("[*] Browser Chrome tetap dibiarkan terbuka agar Anda dapat meninjau halaman LPJK.")
        log("[*] Anda dapat menutup browser Chrome secara manual atau melalui tombol 'Hentikan Bot' di web.")
    else:
        log("[*] Selesai. Browser Chrome tetap terbuka.")

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Bot Otomatis LPJK PUPR Super Cepat")
    parser.add_argument("--fetch-details", action="store_true", default=True, help="Ekstrak detail modal (NIB, Pengurus, Legalitas, SBU)")
    parser.add_argument("--no-fetch-details", action="store_false", dest="fetch_details", help="Hanya ambil tabel tanpa modal")
    parser.add_argument("--scope", default="ntb", choices=["ntb", "all"], help="Filter wilayah target ekstraksi detail (ntb/all)")
    parser.add_argument("--details-only", action="store_true", help="Langsung ekstrak modal detail dari database tanpa scrape tabel utama")
    args = parser.parse_args()
    
    run_bot(fetch_details=args.fetch_details, scope=args.scope, details_only=args.details_only)
