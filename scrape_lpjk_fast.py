import sys
import os
import re
import json
import csv
import time
import argparse
import urllib3
import requests
import pymysql

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'sidp_new',
    'charset': 'utf8mb4'
}

def parse_html_table(html_content):
    """
    Ekstraksi super cepat 200.000+ baris tabel HTML menggunakan regex iterator.
    Mampu memproses 200k baris dalam 3-5 detik dengan konsumsi RAM minimal.
    """
    print("[*] Memulai pemindaian tabel HTML...")
    start_time = time.time()
    
    # Cari tbody atau seluruh tr
    records = []
    
    # Pattern untuk menangkap setiap <tr> di dalam tabel
    # Format LPJK:
    # <td>No</td>
    # <td>Nama Badan Usaha</td>
    # <td>Provinsi</td>
    # <td>Kabupaten</td>
    # <td>NPWP</td>
    # <td><a id="smallButton" data-attr="...">Detail</a></td>
    
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
            # Lewati baris header jika ada <td> yang bertuliskan No
            if col_no.lower().startswith('no'):
                continue
                
            nama = tag_cleaner.sub('', tds[1]).strip()
            provinsi = tag_cleaner.sub('', tds[2]).strip()
            kabupaten = tag_cleaner.sub('', tds[3]).strip()
            npwp_raw = tag_cleaner.sub('', tds[4]).strip()
            npwp = re.sub(r'\D', '', npwp_raw)
            
            detail_attr = ''
            if len(tds) >= 6:
                m_attr = attr_pattern.search(tds[5])
                if m_attr:
                    detail_attr = m_attr.group(1)
            
            # Normalisasi nama untuk pencarian
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
            if count % 25000 == 0:
                print(f"  -> {count:,} baris kontraktor berhasil diekstrak ({time.time() - start_time:.1f} detik)...")

    elapsed = time.time() - start_time
    print(f"[+] SELESAI! Berhasil mengekstrak {len(records):,} kontraktor dalam {elapsed:.2f} detik.")
    return records

def save_to_files(records, base_name="lpjk_contractors_master"):
    """
    Simpan hasil ekstraksi ke format JSON dan CSV siap pakai
    """
    if not records:
        print("[!] Tidak ada data untuk disimpan.")
        return

    json_path = f"c:/laragon/www/infoajip/{base_name}.json"
    csv_path = f"c:/laragon/www/infoajip/{base_name}.csv"
    
    print(f"[*] Menyimpan ke {json_path}...")
    with open(json_path, 'w', encoding='utf-8') as f:
        json.dump(records, f, ensure_ascii=False, indent=2)
        
    print(f"[*] Menyimpan ke {csv_path} (Format CSV UTF-8)...")
    with open(csv_path, 'w', encoding='utf-8-sig', newline='') as f:
        writer = csv.writer(f, delimiter=';')
        writer.writerow(['No', 'Nama Perusahaan / Kontraktor', 'NPWP', 'Kabupaten / Kota', 'Provinsi', 'Detail SBU URL'])
        for idx, r in enumerate(records, start=1):
            writer.writerow([idx, r['name'], r['npwp_formatted'] or r['npwp'], r['kabupaten'], r['province'], r['detail_url']])
            
    print(f"[+] File JSON ({os.path.getsize(json_path)/(1024*1024):.1f} MB) & CSV ({os.path.getsize(csv_path)/(1024*1024):.1f} MB) berhasil dibuat!")

def save_to_database(records):
    """
    Simpan / Upsert data ke MySQL database `pengadaanpro_contractors` dalam batch 2.000 data
    """
    if not records:
        return
        
    print("[*] Menghubungkan ke database MySQL sidp_new...")
    try:
        conn = pymysql.connect(**DB_CONFIG)
        cursor = conn.cursor()
    except Exception as e:
        print(f"[!] Gagal koneksi ke database: {e}")
        return

    print(f"[*] Melakukan bulk upsert {len(records):,} data ke tabel `pengadaanpro_contractors`...")
    batch_size = 2000
    total_saved = 0
    start_time = time.time()

    sql = """
        INSERT INTO `pengadaanpro_contractors` (
            remote_id, slug, contractor_name, contractor_name_normalized,
            npwp, domisili_kota, province, address
        ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
        ON DUPLICATE KEY UPDATE
            contractor_name = VALUES(contractor_name),
            domisili_kota = IF(VALUES(domisili_kota) != '' AND VALUES(domisili_kota) IS NOT NULL, VALUES(domisili_kota), domisili_kota),
            province = IF(VALUES(province) != '' AND VALUES(province) IS NOT NULL, VALUES(province), province),
            npwp = IF(VALUES(npwp) != '' AND VALUES(npwp) IS NOT NULL, VALUES(npwp), npwp),
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
            values.append((remote_id, slug, name, norm_name, npwp, kabupaten, province, address))

        try:
            cursor.executemany(sql, values)
            conn.commit()
            total_saved += len(values)
            print(f"  -> Berhasil menyimpan {total_saved:,} / {len(records):,} data...")
        except Exception as e:
            print(f"[!] Error batch {i}: {e}")
            conn.rollback()

    cursor.close()
    conn.close()
    elapsed = time.time() - start_time
    print(f"[+] Bulk upsert selesai! {total_saved:,} data kontraktor berhasil diperbarui dalam {elapsed:.2f} detik.")

def fetch_from_curl(curl_cmd):
    """
    Ekstrak URL, Cookie, dan form payload dari command cURL yang disalin user
    lalu jalankan request streaming langsung ke server LPJK
    """
    print("[*] Menganalisis string cURL dari user...")
    
    # Ambil URL
    url_match = re.search(r"curl\s+['\"]?([^'\"\s]+)['\"]?", curl_cmd)
    url = url_match.group(1) if url_match else "https://lpjk.pu.go.id/laporan-lpjk/searching"
    
    # Ambil Cookie
    cookie_match = re.search(r"-H\s+['\"]Cookie:\s*([^'\"]+)['\"]", curl_cmd, re.IGNORECASE)
    cookies_str = cookie_match.group(1) if cookie_match else ""
    
    # Ambil Data/Payload
    data_match = re.search(r"--data-raw\s+['\"]([^'\"]+)['\"]", curl_cmd)
    if not data_match:
        data_match = re.search(r"--data\s+['\"]([^'\"]+)['\"]", curl_cmd)
    payload_str = data_match.group(1) if data_match else ""

    headers = {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Origin': 'https://lpjk.pu.go.id',
        'Referer': 'https://lpjk.pu.go.id/laporan-lpjk/sebaran/cari',
        'Content-Type': 'application/x-www-form-urlencoded'
    }
    
    if cookies_str:
        headers['Cookie'] = cookies_str

    print(f"[*] Mengirim POST request ke: {url}")
    print(f"[*] Menggunakan session cookie (panjang: {len(cookies_str)} karakter)...")
    
    start_req = time.time()
    resp = requests.post(url, headers=headers, data=payload_str, verify=False, timeout=120)
    print(f"[+] Response diterima! Status: {resp.status_code} | Ukuran: {len(resp.text)/(1024*1024):.2f} MB ({time.time() - start_req:.2f} detik)")
    
    if resp.status_code == 200:
        return resp.text
    else:
        print(f"[!] Request gagal dengan status {resp.status_code}: {resp.text[:300]}")
        return None

def main():
    parser = argparse.ArgumentParser(description="Bot Pengambil Data LPJK Cepat (200.000+ Data)")
    parser.add_argument("--file", help="Path ke file HTML hasil simpan browser (misal: searching.html)", default=None)
    parser.add_argument("--curl", help="String cURL lengkap yang disalin dari DevTools", default=None)
    parser.add_argument("--curl-file", help="File teks berisi string cURL", default=None)
    args = parser.parse_args()

    html_content = None

    # 1. Cek argumen --file atau file default searching.html
    target_html = args.file or "c:/laragon/www/infoajip/searching.html"
    if os.path.isfile(target_html):
        print(f"[+] Ditemukan file HTML: {target_html} ({os.path.getsize(target_html)/(1024*1024):.2f} MB)")
        with open(target_html, 'r', encoding='utf-8', errors='ignore') as f:
            html_content = f.read()

    # 2. Cek argumen --curl atau file lpjk_curl.txt
    elif args.curl:
        html_content = fetch_from_curl(args.curl)
    elif args.curl_file and os.path.isfile(args.curl_file):
        with open(args.curl_file, 'r', encoding='utf-8', errors='ignore') as f:
            curl_cmd = f.read().strip()
        html_content = fetch_from_curl(curl_cmd)
    elif os.path.isfile("c:/laragon/www/infoajip/lpjk_curl.txt"):
        print("[+] Ditemukan file cURL: c:/laragon/www/infoajip/lpjk_curl.txt")
        with open("c:/laragon/www/infoajip/lpjk_curl.txt", 'r', encoding='utf-8', errors='ignore') as f:
            curl_cmd = f.read().strip()
        html_content = fetch_from_curl(curl_cmd)
    else:
        print("\n" + "="*70)
        print("BOT LPJK SIAP MENERIMA DATA:")
        print("1. Anda bisa simpan halaman tabel di browser Anda (Ctrl+S) sebagai:")
        print("   c:/laragon/www/infoajip/searching.html")
        print("2. ATAU salin cURL dari DevTools (F12 > Network) dan simpan ke file:")
        print("   c:/laragon/www/infoajip/lpjk_curl.txt")
        print("="*70 + "\n")
        return

    if html_content:
        # Ekstraksi baris tabel
        records = parse_html_table(html_content)
        
        if records:
            # Simpan file cadangan JSON & CSV
            save_to_files(records)
            # Simpan langsung ke database
            save_to_database(records)
            print("\n[V] SELURUH PROSES BERHASIL DISELESAIKAN!")
        else:
            print("[!] Tabel tidak ditemukan atau data kosong di dalam file HTML tersebut.")

if __name__ == '__main__':
    main()
