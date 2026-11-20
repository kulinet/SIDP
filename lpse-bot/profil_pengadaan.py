from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from bs4 import BeautifulSoup
from io import StringIO
import pandas as pd
import time
import re
import os

# ==============================================================================
# DAFTAR SATUAN KERJA TARGET (68 Satker Pilihan)
# ==============================================================================
TARGET_SATKER = [
    "BADAN KEPEGAWAIAN DAN PENGEMBANGAN SUMBER DAYA MANUSIA - 5.03.5.04.0.00.31.0000",
    "BADAN KESATUAN BANGSA DAN POLITIK - 8.01.0.00.0.00.43.0000",
    "BADAN KEUANGAN DAN ASET DAERAH - 5.02.0.00.0.00.30.0000",
    "BADAN PENANGGULANGAN BENCANA DAERAH - 1.05.0.00.0.00.07.0000",
    "BADAN PENDAPATAN DAERAH - 5.02.0.00.0.00.29.0000",
    "BADAN PERENCANAAN PEMBANGUNAN, RISET, DAN INOVASI DAERAH - 5.01.5.05.0.00.28.0000",
    "DINAS KEARSIPAN DAN PERPUSTAKAAN - 2.24.2.23.0.00.21.0000",
    "DINAS KELAUTAN DAN PERIKANAN - 3.25.0.00.0.00.22.0000",
    "DINAS KELAUTAN, PERIKANAN, DAN KETAHANAN PANGAN - 3.25.2.09.0.00.11.0000",
    "DINAS KEPENDUDUKAN DAN PENCATATAN SIPIL - 2.12.0.00.0.00.13.0000",
    "DINAS KESEHATAN - 1.02.2.14.0.00.01.0000",
    "DINAS KETAHANAN PANGAN - 2.09.0.00.0.00.11.0000",
    "DINAS KOMUNIKASI, INFORMATIKA, DAN STATISTIK - 2.16.2.20.2.21.17.0000",
    "DINAS LINGKUNGAN HIDUP - 2.11.0.00.0.00.12.0000",
    "DINAS PARIWISATA, EKONOMI KREATIF, PEMUDA, DAN OLAHRAGA - 3.26.2.19.0.00.23.0000",
    "DINAS PEKERJAAN UMUM, PENATAAN RUANG, PERUMAHAN DAN KAWASAN PERMUKIMAN - 1.03.1.04.2.10.03.0000",
    "DINAS PEMADAM KEBAKARAN DAN PENYELAMATAN - 1.05.0.00.0.00.08.0000",
    "DINAS PEMBERDAYAAN MASYARAKAT DAN DESA - 2.13.0.00.0.00.14.0000",
    "DINAS PEMUDA DAN OLAHRAGA - 2.19.0.00.0.00.19.0000",
    "DINAS PEMUDA DAN OLAH RAGA - 2.19.0.00.0.00.20.0000",
    "DINAS PENANAMAN MODAL DAN PELAYANAN TERPADU SATU PINTU - 2.18.0.00.0.00.19.0000",
    "DINAS PENDIDIKAN DAN KEBUDAYAAN - 1.01.2.22.0.00.01.0000",
    "DINAS PENGENDALIAN PENDUDUK, KELUARGA BERENCANA, PEMBERDAYAAN PEREMPUAN DAN PERLINDUNGAN ANAK - 2.08.2.14.0.00.15.0000",
    "DINAS PERDAGANGAN - 3.30.0.00.0.00.25.0000",
    "DINAS PERHUBUNGAN - 2.15.0.00.0.00.16.0000",
    "DINAS PERINDUSTRIAN, KOPERASI, USAHA KECIL DAN MENENGAH, DAN TENAGA KERJA - 3.31.2.17.2.07.18.0000",
    "DINAS PERTANIAN - 3.27.0.00.0.00.24.0000",
    "DINAS PERUMAHAN DAN PERMUKIMAN - 1.04.0.00.0.00.05.0000",
    "DINAS SOSIAL, PEMBERDAYAAN PEREMPUAN, DAN PERLINDUNGAN ANAK - 1.06.2.08.0.00.09.0000",
    "DINAS TENAGA KERJA - 2.07.3.32.0.00.10.0000",
    "INSPEKTORAT KABUPATEN - 6.01.0.00.0.00.32.0000",
    "KANTOR CAMAT BATU LAYAR - 7.01.0.00.0.00.42.0000",
    "KANTOR CAMAT GERUNG - 7.01.0.00.0.00.35.0000",
    "KANTOR CAMAT GUNUNGSARI - 7.01.0.00.0.00.41.0000",
    "KANTOR CAMAT KEDIRI - 7.01.0.00.0.00.37.0000",
    "KANTOR CAMAT KURIPAN - 7.01.0.00.0.00.38.0000",
    "KANTOR CAMAT LABUAPI - 7.01.0.00.0.00.36.0000",
    "KANTOR CAMAT LEMBAR - 7.01.0.00.0.00.34.0000",
    "KANTOR CAMAT LINGSAR - 7.01.0.00.0.00.40.0000",
    "KANTOR CAMAT NARMADA - 7.01.0.00.0.00.39.0000",
    "KANTOR CAMAT SEKOTONG - 7.01.0.00.0.00.33.0000",
    "RUMAH SAKIT UMUM DAERAH PATUT PATUH PATJU - 1.02.0.00.0.00.02.0000",
    "SATUAN POLISI PAMONG PRAJA - 1.05.0.00.0.00.06.0000",
    "SEKRETARIAT DAERAH - 4.01.0.00.0.00.26.0000",
    "SEKRETARIAT DPRD - 4.02.0.00.0.00.27.0000",
    "UPT LABORATORIUM KESEHATAN KABUPATEN LOMBOK BARAT - 1.02.2.14.0.00.01.0003",
    "UPT PUSKESMAS BANYUMULEK - 1.02.2.14.0.00.01.0019",
    "UPT PUSKESMAS DASAN TAPEN - 1.02.2.14.0.00.01.0020",
    "UPT PUSKESMAS EYAT MAYANG - 1.02.2.14.0.00.01.0022",
    "UPT PUSKESMAS GERUNG - 1.02.2.14.0.00.01.0015",
    "UPT PUSKESMAS GUNUNGSARI - 1.02.2.14.0.00.01.0005",
    "UPT PUSKESMAS JEMBATAN KEMBAR - 1.02.2.14.0.00.01.0016",
    "UPT PUSKESMAS KEDIRI - 1.02.2.14.0.00.01.0013",
    "UPT PUSKESMAS KURIPAN - 1.02.2.14.0.00.01.0014",
    "UPT PUSKESMAS LABUAPI - 1.02.2.14.0.00.01.0011",
    "UPT PUSKESMAS LINGSAR - 1.02.2.14.0.00.01.0007",
    "UPT PUSKESMAS MENINTING - 1.02.2.14.0.00.01.0004",
    "UPT PUSKESMAS NARMADA - 1.02.2.14.0.00.01.0009",
    "UPT PUSKESMAS PELANGAN - 1.02.2.14.0.00.01.0018",
    "UPT PUSKESMAS PENIMBUNG - 1.02.2.14.0.00.01.0006",
    "UPT PUSKESMAS PERAMPUAN - 1.02.2.14.0.00.01.0012",
    "UPT PUSKESMAS SEDAU - 1.02.2.14.0.00.01.0010",
    "UPT PUSKESMAS SEKOTONG - 1.02.2.14.0.00.01.0017",
    "UPT PUSKESMAS SESELA - 1.02.2.14.0.00.01.0023",
    "UPT PUSKESMAS SIGERONGAN - 1.02.2.14.0.00.01.0008",
    "UPT PUSKESMAS SURANADI - 1.02.2.14.0.00.01.0021",
    "UPT RSUD AWET MUDA NARMADA - 1.02.2.14.0.00.01.0002",
    "UPT RSUD PATUT PATUH PATJU - 1.02.2.14.0.00.01.0001",
]

def clean_number(text):
    """Membersihkan format Rp, spasi, dan titik ribuan menjadi float"""
    if text is None or pd.isna(text):
        return 0.0
    cleaned = re.sub(r'[^0-9]', '', str(text).strip())
    return float(cleaned) if cleaned else 0.0

def save_data(semua_data, script_dir):
    """Fungsi pembantu untuk menyimpan data ke Excel dan CSV"""
    if not semua_data:
        return
    df = pd.DataFrame(semua_data)
    nama_file_excel = os.path.join(script_dir, "Rekap_Inaproc_1_Baris.xlsx")
    nama_file_csv = os.path.join(script_dir, "Rekap_Inaproc_1_Baris.csv")
    df.to_excel(nama_file_excel, index=False)
    df.to_csv(nama_file_csv, index=False)

def scrape_and_flatten(use_target_list=True, max_satker=None):
    """
    Scrape data profil pengadaan INAPROC.
    - use_target_list: True untuk hanya mengambil 68 Satker target dari TARGET_SATKER.
                       False untuk mengambil seluruh 507 Satker di dropdown.
    - max_satker: Batas jumlah satker yang diambil (None = semua sesuai target).
    """
    script_dir = os.path.dirname(os.path.abspath(__file__))
    
    options = webdriver.ChromeOptions()
    # options.add_argument('--headless=new') # Aktifkan jika ingin berjalan tanpa jendela browser
    options.add_argument('--disable-gpu')
    options.add_argument('--no-sandbox')
    options.add_argument('--window-size=1920,1080')
    options.add_argument('user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')

    driver = webdriver.Chrome(options=options)
    wait = WebDriverWait(driver, 20)
    
    url = "https://data.inaproc.id/profil-pengadaan?tahun=2026&jenis=4&instansi=D307&view=Data"
    print(f"Membuka URL: {url}")
    driver.get(url)
    time.sleep(6) # Tunggu rendering awal React
    
    semua_data = []
    
    try:
        # 1. Buka Combobox Satuan Kerja (React-Select)
        satker_label = wait.until(EC.presence_of_element_located((By.XPATH, "//label[contains(., 'Satuan Kerja')]")))
        inp = satker_label.find_element(By.TAG_NAME, "input")
        
        # Buka dropdown pertama kali untuk membaca semua opsi Satuan Kerja
        driver.execute_script("arguments[0].click();", inp)
        inp.send_keys(Keys.DOWN)
        time.sleep(1.5)
        
        options_elements = driver.find_elements(By.XPATH, "//div[contains(@id, '-option-')]")
        total_options = len(options_elements)
        print(f"Total opsi tersedia di dropdown web: {total_options}")
        
        # Simpan pemetaan: nama_satker -> indeks di dropdown web
        web_satker_map = {}
        for idx, opt in enumerate(options_elements):
            text_clean = opt.text.strip()
            if text_clean:
                web_satker_map[text_clean] = idx
        
        # Tutup dropdown sementara
        inp.send_keys(Keys.ESCAPE)
        time.sleep(1)
        
        # Tentukan daftar satker yang akan diproses
        if use_target_list:
            items_to_process = []
            for target in TARGET_SATKER:
                target_clean = target.strip()
                if target_clean in web_satker_map:
                    items_to_process.append((target_clean, web_satker_map[target_clean]))
                else:
                    # Pencocokan fleksibel jika ada perbedaan spasi kecil
                    matched_idx = None
                    for web_name, idx in web_satker_map.items():
                        if target_clean.lower() in web_name.lower() or web_name.lower() in target_clean.lower():
                            matched_idx = idx
                            items_to_process.append((web_name, idx))
                            break
                    if matched_idx is None:
                        print(f"Peringatan: Satker target '{target}' tidak ditemukan di opsi web!")
        else:
            # Mengambil seluruh opsi web (lewati indeks 0 'Semua')
            items_to_process = [(text, idx) for text, idx in web_satker_map.items() if idx > 0]
            
        if max_satker:
            items_to_process = items_to_process[:max_satker]
            
        total_count = len(items_to_process)
        print(f"\n=======================================================")
        print(f" Memulai scraping untuk {total_count} Satuan Kerja")
        print(f"=======================================================\n")

        for seq, (satker_nama, satker_idx) in enumerate(items_to_process, 1):
            print(f"[{seq}/{total_count}] Memproses: {satker_nama}")
            
            try:
                # Buka combobox
                driver.execute_script("arguments[0].click();", inp)
                inp.send_keys(Keys.DOWN)
                time.sleep(0.8)
                
                # Temukan elemen opsi di web
                current_options = driver.find_elements(By.XPATH, "//div[contains(@id, '-option-')]")
                if satker_idx < len(current_options):
                    opsi = current_options[satker_idx]
                    driver.execute_script("arguments[0].scrollIntoView(true);", opsi)
                    driver.execute_script("arguments[0].click();", opsi)
                else:
                    print(f"  Peringatan: Opsi ke-{satker_idx} tidak ditemukan di DOM!")
                    continue
                
                # Tunggu data tabel selesai me-load di DOM
                tables = []
                for _ in range(15): # Tunggu dinamis hingga maksimal 7.5 detik
                    time.sleep(0.5)
                    try:
                        if len(driver.find_elements(By.TAG_NAME, "table")) >= 3:
                            try:
                                tables = pd.read_html(StringIO(driver.page_source), flavor='lxml')
                            except Exception:
                                tables = pd.read_html(StringIO(driver.page_source), flavor='html5lib')
                            if len(tables) >= 3:
                                break
                    except Exception:
                        pass
                
                if not tables:
                    raise Exception("Tabel data tidak ditemukan atau timeout saat memuat.")
                
                # --- EKSTRAKSI DATA TABEL ---
                row_data = {'Satuan Kerja': satker_nama}
                
                belanja_pengadaan_val = 0.0
                total_rup_val = 0.0
                penyedia_paket = 0
                swakelola_paket = 0
                
                # Table 0: Belanja Pengadaan (Nominal)
                if len(tables) > 0 and 'Nilai' in tables[0].columns:
                    belanja_str = tables[0].iloc[0]['Nilai']
                    belanja_pengadaan_val = clean_number(belanja_str)
                row_data['Belanja Pengadaan (Rp)'] = belanja_pengadaan_val
                
                # Table 1: Total RUP (Nominal & Jumlah)
                if len(tables) > 1 and 'Kategori' in tables[1].columns and 'Nilai' in tables[1].columns:
                    rup_nom = tables[1][tables[1]['Kategori'] == 'Nominal']
                    if not rup_nom.empty:
                        total_rup_val = clean_number(rup_nom.iloc[0]['Nilai'])
                row_data['Total RUP (Rp)'] = total_rup_val
                
                # Hitung Persentase Keterisian RUP & Sisa Belanja
                if belanja_pengadaan_val > 0:
                    persen_keterisian = (total_rup_val / belanja_pengadaan_val) * 100
                    persen_sisa = 100 - persen_keterisian
                else:
                    persen_keterisian = 0.0
                    persen_sisa = 0.0
                row_data['Keterisian Total RUP (%)'] = f"{persen_keterisian:.2f}%"
                row_data['Sisa Belanja (%)'] = f"{persen_sisa:.2f}%"
                
                # Table 3: Penyedia & Swakelola
                if len(tables) > 3 and 'Kategori' in tables[3].columns and 'Jumlah' in tables[3].columns:
                    peny_row = tables[3][tables[3]['Kategori'] == 'Penyedia']
                    swak_row = tables[3][tables[3]['Kategori'] == 'Swakelola']
                    
                    if not peny_row.empty:
                        penyedia_paket = int(clean_number(peny_row.iloc[0]['Jumlah']))
                    if not swak_row.empty:
                        swakelola_paket = int(clean_number(swak_row.iloc[0]['Jumlah']))
                        
                total_paket = penyedia_paket + swakelola_paket
                if total_paket > 0:
                    persen_penyedia = (penyedia_paket / total_paket) * 100
                    persen_swakelola = (swakelola_paket / total_paket) * 100
                else:
                    persen_penyedia = 0.0
                    persen_swakelola = 0.0
                    
                row_data['Paket Penyedia'] = f"{penyedia_paket} ({persen_penyedia:.2f}%)"
                row_data['Paket Swakelola'] = f"{swakelola_paket} ({persen_swakelola:.2f}%)"
                
                # Ekstraksi Semua Kategori Lainnya (PDN, Impor, UMKK, Metode Pemilihan, dll)
                for df in tables:
                    if 'Kategori' in df.columns and 'Jumlah' in df.columns:
                        for _, r in df.iterrows():
                            kat = str(r['Kategori']).strip()
                            if kat not in ['Penyedia', 'Swakelola', 'Nominal']:
                                if 'Jumlah' in df.columns and not pd.isna(r['Jumlah']):
                                    row_data[f"Jumlah {kat}"] = int(clean_number(r['Jumlah']))
                                if 'Nominal' in df.columns and not pd.isna(r['Nominal']):
                                    row_data[f"Nominal {kat}"] = clean_number(r['Nominal'])
                                    
                semua_data.append(row_data)
                
                # Simpan cadangan berkala setiap 5 baris agar data tidak hilang
                if len(semua_data) % 5 == 0:
                    save_data(semua_data, script_dir)
                
            except Exception as e:
                print(f"  Peringatan: Gagal mengekstrak data '{satker_nama}': {e}")
                semua_data.append({'Satuan Kerja': satker_nama, 'Error': str(e)})

    except Exception as e:
        print(f"Error Utama: {e}")
    finally:
        driver.quit()
        
    # Simpan hasil akhir ke Excel & CSV
    if semua_data:
        save_data(semua_data, script_dir)
        nama_file_excel = os.path.join(script_dir, "Rekap_Inaproc_1_Baris.xlsx")
        nama_file_csv = os.path.join(script_dir, "Rekap_Inaproc_1_Baris.csv")
        print(f"\n=======================================================")
        print(f" Selesai! {len(semua_data)} data berhasil disimpan ke:")
        print(f"  - {nama_file_excel}")
        print(f"  - {nama_file_csv}")
        print(f"=======================================================\n")
        
        df_final = pd.DataFrame(semua_data)
        print("Preview 5 data pertama:")
        print(df_final.head())
    else:
        print("\nTidak ada data yang berhasil diambil.")

if __name__ == "__main__":
    # use_target_list=True akan mengambil 68 Satker target di atas saja.
    # Ubah max_satker=None untuk mengambil semua 68 satker, atau angka (misal: max_satker=3) untuk tes cepat.
    scrape_and_flatten(use_target_list=True, max_satker=None)
