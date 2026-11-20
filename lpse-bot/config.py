# ==============================================================================
# PENGATURAN UMUM BOT LPSE (TENDER & NON TENDER)
# ==============================================================================
# Anda cukup mengubah nilai di bawah ini untuk mengatur semua bot:

TAHUN = "2025"                  # Tahun anggaran yang ingin direkap (contoh: "2024", "2025", "2026")
INSTANSI = "lombokbaratkab"     # Slug instansi LPSE (contoh: lombokbaratkab, pemprovntb, dll)
DELAY = 0.5                     # Jeda waktu antar request dalam detik (agar aman dari blokir)
LIMIT = None                    # Batas jumlah paket yang diambil (None = semua paket, atau angka misal 10)
HEADLESS = False                # Khusus Selenium: True jika ingin berjalan tanpa membuka jendela browser
