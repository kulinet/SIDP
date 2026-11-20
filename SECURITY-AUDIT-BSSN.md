# LAPORAN AUDIT KEAMANAN APLIKASI
## InfoAJIP (SIDP — Sistem Informasi Daftar Pengadaan)

**Tanggal Audit:** 2026-10-19
**Framework:** CodeIgniter 4.7 (^4.7, composer)
**Versi App:** V1.08.15
**Lokasi:** C:\Laragon\www\infoajip
**Standar Referensi:** BSSN RI, OWASP Top 10 2021, CIS Benchmarks
**Auditor:** Hermes Agent

---

## RINGKASAN EKSEKUTIF

Status: **PERLU PERBAIKAN KRITIS**

| Severity | Jumlah |
|----------|--------|
| KRITIS   | 5      |
| TINGGI   | 4      |
| SEDANG   | 4      |
| RENDAH   | 3      |

Aplikasi memiliki fondasi security yang baik (RBAC, 2FA, throttle, CSRF, security headers, CSP), tetapi dijalankan dalam mode **development** dengan database **root tanpa password**, dan menggunakan versi CodeIgniter 4.7 yang memiliki **4 CVE aktif** (2 critical, 1 high, 1 medium). Wajib upgrade sebelum production.

---

## TEMUAN KEAMANAN

### 🔴 KRITIS

#### K-01: CI_ENVIRONMENT = development (Production Exposure)
**Lokasi:** `.env` line `CI_ENVIRONMENT = development`
**OWASP:** A05:2021 – Security Misconfiguration
**BSSN:** Pengamanan Konfigurasi Sistem
**Impact:** Mode development menampilkan stack trace detail, error lengkap, dan toolbar debug ke end-user. Informasi struktur folder, query DB, dan variabel internal terekspos.
**Rekomendasi:**
```env
CI_ENVIRONMENT = production
```

#### K-02: Database Root Tanpa Password
**Lokasi:** `.env` — `database.default.username = root`, `database.default.password =` (kosong)
**OWASP:** A07:2021 – Identification and Authentication Failures
**BSSN:** Pengamanan Database
**Impact:** Jika MySQL/MariaDB terekspos (misal via port forwarding atau misconfig), attacker dapat full DB access tanpa kredensial.
**Rekomendasi:** Buat DB user khusus dengan password kuat, privilege terbatas (hanya DML+DDL pada database sidp_new, tidak ada GRANT/DROP untuk user lain).

#### K-03: CVE-2026-63221 — SQL Injection di Query Builder deleteBatch()
**Lokasi:** codeigniter4/framework <4.7.4 (composer.lock)
**CVE:** CVE-2026-63221 (Critical)
**OWASP:** A03:2021 – Injection
**Impact:** SQL injection pada `deleteBatch()` saat digunakan dengan `where()`. Jika aplikasi menggunakan pattern ini, attacker dapat inject SQL.
**Rekomendasi:** Upgrade `codeigniter4/framework` ke ^4.7.4:
```bash
php composer.phar update codeigniter4/framework
```

#### K-04: CVE-2026-63223 — File Upload Extension Validation Bypass
**Lokasi:** codeigniter4/framework <4.7.4 (composer.lock)
**CVE:** CVE-2026-63223 (Critical)
**OWASP:** A03:2021 – Injection
**Impact:** Aturan validasi `is_image` dan `mime_in` dapat di-bypass. Attacker dapat upload file PHP berkedok JPG/PNG → RCE.
**Rekomendasi:** Upgrade ke ^4.7.4. Sementara itu, tambahkan validasi magic bytes manual:
```php
$bytes = file_get_contents($file->getTempName(), false, null, 0, 12);
// Verify magic bytes match expected MIME
```

#### K-05: encryption.key Tidak Diatur
**Lokasi:** `.env` — `# encryption.key =` (dikomentari/kosong)
**OWASP:** A02:2021 – Cryptographic Failures
**BSSN:** Pengamanan Data / Kriptografi
**Impact:** Data sensitif yang dienkripsi via `Encryption` class akan menggunakan key default/lemah. Session encryption (jika diaktifkan) juga terpengaruh.
**Rekomendasi:**
```bash
php spark key:generate
```
Atau set manual di `.env`:
```env
encryption.key = <32-byte random hex>
```

---

### 🟠 TINGGI

#### T-01: CVE-2026-63222 — Path Traversal di UploadedFile::move()
**Lokasi:** codeigniter4/framework <4.7.4 (composer.lock)
**CVE:** CVE-2026-63222 (High)
**Impact:** `UploadedFile::move()` dengan nama file dari client dapat menyebabkan path traversal (`../../`).
**Rekomendasi:** Upgrade ke ^4.7.4. App sudah menggunakan `getRandomName()` di sebagian besar upload handler — VERIFIKASI semua handler.

#### T-02: CSRF Exception Scope Terlalu Lebar
**Lokasi:** `app/Config/Filters.php` line 86-99
```php
'csrf' => ['except' => [
    'api/*', 'chatbot/*', 'report/*',
    'dashboard/monev/*', 'dashboard/monev-v2/*',
    'dashboard/do-launch-bot-spse', ...
]]
```
**OWASP:** A01:2021 – Broken Access Control
**Impact:** Endpoint POST di `dashboard/monev/*` dan `dashboard/monev-v2/*` tidak dilindungi CSRF. Endpoint ini melakukan operasi state-changing (start, submit, review, finalize, upload evidence, delete evidence).
**Rekomendasi:** Hapus `dashboard/monev/*` dan `dashboard/monev-v2/*` dari CSRF exception. Gunakan CSRF token via header (`X-CSRF-TOKEN`) untuk AJAX request.

#### T-03: forceGlobalSecureRequests = false
**Lokasi:** `app/Config/App.php` line 160
**OWASP:** A02:2021 – Cryptographic Failures
**Impact:** HTTPS tidak diwajibkan. Request HTTP tidak di-redirect ke HTTPS. Session cookie dapat di-sniff via MITM.
**Rekomendasi:**
```php
public bool $forceGlobalSecureRequests = true;
```
Pastikan SSL/TLS certificate valid di production.

#### T-04: Cookie $secure = false
**Lokasi:** `app/Config/Cookie.php` line 57
**OWASP:** A05:2021 – Security Misconfiguration
**Impact:** Session cookie dapat dikirim via HTTP → cookie interception.
**Rekomendasi:**
```php
public bool $secure = true;
```
(Sudah `$httponly = true`, `$samesite = 'Strict'` — bagus.)

---

### 🟡 SEDANG

#### S-01: CVE-2026-63220 — Spoofable Forwarded HTTPS Headers
**Lokasi:** codeigniter4/framework <4.7.4
**CVE:** CVE-2026-63220 (Medium)
**Impact:** `IncomingRequest::isSecure()` dapat di-spoof via X-Forwarded-Proto header.
**Rekomendasi:** Upgrade ke ^4.7.4. Pastikan reverse proxy (Apache/Nginx) mem-strip header `X-Forwarded-Proto` dari client.

#### S-02: Session Driver FileHandler (Production)
**Lokasi:** `app/Config/Session.php` — `$driver = FileHandler::class`
**Impact:** Session tersimpan di filesystem. Tidak scalable, sulit di-monitor, rentan jika writable/ terekspos.
**Rekomendasi:** Gunakan `DatabaseHandler` atau `RedisHandler` untuk production.

#### S-03: CSRF Token Randomize & Regenerate Disabled
**Lokasi:** `app/Config/Security.php`
```php
public bool $tokenRandomize = false;
public bool $regenerate = false;
```
**Impact:** Token CSRF statik sepanjang session → lebih mudah di-predict/reuse.
**Rekomendasi:**
```php
public bool $tokenRandomize = true;
public bool $regenerate = true;
```

#### S-04: debug_chat.log Terekspos di Public Folder
**Lokasi:** `public/debug_chat.log`
**Impact:** File log debug chatbot dapat diakses langsung via URL `https://infoajip.io/debug_chat.log` → leak informasi internal (payload, response, error).
**Rekomendasi:** Hapus file. Pindahkan logging ke `writable/logs/`. Tambahkan rule di `.htaccess`:
```apache
<FilesMatch "\.log$">
    Require all denied
</FilesMatch>
```

---

### 🟢 RENDAH

#### R-01: Session Expiration 7200s (2 jam)
**Lokasi:** `app/Config/Session.php` — `$expiration = 7200`
**Impact:** Untuk aplikasi government yang menangani data sensitif (pengadaan, PII), 2 jam cukup lama.
**Rekomendasi:** Turunkan ke 1800 (30 menit) untuk session inactivity timeout.

#### R-02: CSP Mengizinkan 'unsafe-inline' dan 'unsafe-eval'
**Lokasi:** `app/Filters/SecureHeaders.php` line 60
```php
"script-src 'self' 'unsafe-inline' 'unsafe-eval' ..."
```
**Impact:** XSS protection berkurang. Attacker dapat inject inline script.
**Rekomendasi:** Gunakan nonce-based CSP atau hash-based CSP. Hapus `'unsafe-eval'` jika tidak ada eval() di JS.

#### R-03: API Token Tidak Ada Expiry
**Lokasi:** `app/Controllers/Api.php` — `login()` generates token tanpa expiry field
**OWASP:** A07:2021 – Identification and Authentication Failures
**Impact:** Token API valid selamanya sampai user login ulang. Jika token leak, tidak ada auto-revocation.
**Rekomendasi:** Tambah kolom `api_token_expires_at` di tabel users. Validasi expiry di `ApiAuthFilter`.

---

## YANG SUDAH BAIK ✅

1. **RBAC Implementation** — `RbacFilter` dengan custom permission per user, role-based menu mapping, admin bypass
2. **2FA / TOTP** — Google2FA integration untuk admin & role yang require_2fa
3. **Login Throttle** — 5 attempts, 10 menit lockout, IP-based
4. **API Rate Limiting** — 60 req/min general, 15 req/min chatbot
5. **API Token Security** — 64-char hex random, stored as SHA-256 hash (not plaintext)
6. **Legacy Token Migration** — Auto-hash plain tokens on first use
7. **Session Regeneration** — `regenerate(true)` on login & logout (anti-fixation)
8. **Password Hashing** — `password_verify()` (bcrypt/argon2)
9. **Password Policy** — `min_length[8]`, `alpha_numeric` username
10. **CSRF Protection** — Active globally dengan exception list (perlu tightening)
11. **Security Headers** — X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy, HSTS, CSP
12. **CSP** — Comprehensive directives dengan strict whitelist
13. **CORS** — Whitelist origin (infoajip.io, ajip-api.cloud), pattern matching untuk subdomain
14. **File Upload Validation** — ext_in + mime_in + max_size di endpoint dokumen monev
15. **Patch Controller** — MIME type check (finfo), size limit, path traversal block, auto-backup
16. **.env not in git** — `.gitignore` properly excludes .env
17. **Directory listing disabled** — `Options -Indexes` di .htaccess
18. **Server signature off** — `ServerSignature Off`
19. **HTTPS redirect di .htaccess** — www→non-www redirect, HTTPS condition
20. **CSPEnabled = true** — di Config\App
21. **Cookie HttpOnly = true, SameSite = Strict**
22. **InvalidChars filter** — Active globally (before)
23. **User Activity Logging** — `LogFilter` (userlog) active after every request
24. **No hardcoded credentials** — tidak ditemukan di app/
25. **No raw SQL queries** — tidak ditemukan `$db->query()` atau raw queries
26. **No {!! !!} in views** — semua output menggunakan `<?= ?>` (CI4 auto-escapes)

---

## CHECKLIST DEPLOYMENT PRODUCTION

- [ ] CI_ENVIRONMENT = production
- [ ] Database: gunakan user khusus, password kuat, privilege terbatas
- [ ] encryption.key di-generate dan di-set di .env
- [ ] Upgrade codeigniter4/framework ke ^4.7.4
- [ ] forceGlobalSecureRequests = true
- [ ] Cookie $secure = true
- [ ] Hapus dashboard/monev/* dari CSRF exception
- [ ] CSRF $tokenRandomize = true, $regenerate = true
- [ ] Session driver = DatabaseHandler atau RedisHandler
- [ ] Session expiration = 1800 (30 menit)
- [ ] Hapus public/debug_chat.log, block *.log di .htaccess
- [ ] API token expiry diimplementasi
- [ ] HTTPS/TLS certificate valid
- [ ] Firewall: hanya port 80/443 yang publik
- [ ] Backup otomatis database
- [ ] Log rotation (daily, 14 days)

---

## PRIORITAS PERBAIKAN

1. **SEGERA (sebelum deploy):**
   - K-01: Set CI_ENVIRONMENT = production
   - K-02: Buat DB user khusus dengan password
   - K-03/K-04/K-05: Upgrade CI4 ke ^4.7.4 + generate encryption.key
   - T-03: forceGlobalSecureRequests = true

2. **MINGGU 1:**
   - T-01: Upgrade CI4 (sama dengan di atas)
   - T-02: Tighten CSRF exception scope
   - T-04: Cookie $secure = true
   - S-04: Hapus debug_chat.log, block *.log

3. **BULAN 1:**
   - S-01: Upgrade CI4 (sama dengan di atas)
   - S-02: Switch session driver ke Database/Redis
   - S-03: CSRF randomize + regenerate
   - R-01: Session expiration 30 menit
   - R-03: API token expiry
   - R-02: CSP nonce-based (opsional, perlu refactoring JS)

---

## REFERENSI

- BSSN RI — Pedoman Pengamanan Aplikasi Web
- OWASP Top 10 2021
- CodeIgniter 4 Security Advisories (CVE-2026-63220 s/d CVE-2026-63223)
- CIS Benchmark PHP / CodeIgniter

---

**Audit selesai:** 2026-10-19
**Total file diperiksa:** 238 PHP files
**Metode:** Static code review, config analysis, dependency audit (composer audit)
