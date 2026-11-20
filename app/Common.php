<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

if (!function_exists('is_secure_uploaded_file')) {
    /**
     * Memvalidasi file upload secara ketat (Anti-WebShell / Anti-RCE / Magic Bytes).
     *
     * @param \CodeIgniter\HTTP\Files\UploadedFile|null $file
     * @param array $allowedTypes Kategori ['image', 'document', 'csv'] atau ekstensi spesifik
     * @param int $maxSizeBytes Default 10MB
     * @param string &$errorMessage Output pesan error jika gagal
     * @return bool
     */
    function is_secure_uploaded_file($file, array $allowedTypes = ['image', 'document'], int $maxSizeBytes = 10485760, &$errorMessage = ''): bool
    {
        if (!$file || !$file instanceof \CodeIgniter\HTTP\Files\UploadedFile) {
            $errorMessage = 'File upload tidak ditemukan.';
            return false;
        }

        if (!$file->isValid()) {
            $errorMessage = $file->getErrorString() ?: 'File yang diunggah tidak valid atau corrupt.';
            return false;
        }

        if ($file->hasMoved()) {
            $errorMessage = 'File sudah dipindahkan sebelumnya.';
            return false;
        }

        if ($file->getSize() > $maxSizeBytes) {
            $maxMb = round($maxSizeBytes / (1024 * 1024), 1);
            $errorMessage = "Ukuran file melebihi batas maksimum ({$maxMb} MB).";
            return false;
        }

        // Resolusi whitelist ekstensi
        $imageExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $docExts   = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
        $csvExts   = ['csv', 'txt'];

        $resolvedExts = [];
        foreach ($allowedTypes as $type) {
            $t = strtolower($type);
            if ($t === 'image' || $t === 'images') {
                $resolvedExts = array_merge($resolvedExts, $imageExts);
            } elseif ($t === 'document' || $t === 'documents') {
                $resolvedExts = array_merge($resolvedExts, $docExts);
            } elseif ($t === 'csv') {
                $resolvedExts = array_merge($resolvedExts, $csvExts);
            } else {
                $resolvedExts[] = $t;
            }
        }
        $resolvedExts = array_unique(array_filter($resolvedExts));

        // 1. Ekstensi client
        $clientExt = strtolower($file->getClientExtension());
        if (!in_array($clientExt, $resolvedExts, true)) {
            $errorMessage = "Ekstensi file .{$clientExt} tidak diizinkan. Hanya format: " . implode(', ', $resolvedExts);
            return false;
        }

        // 2. Blacklist ekstensi berbahaya absolut
        $dangerousExts = [
            'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phps', 'phar',
            'inc', 'pl', 'py', 'cgi', 'sh', 'bash', 'exe', 'bat', 'cmd', 'js', 'html', 'htm', 'shtml', 'svg'
        ];
        $originalName = strtolower($file->getClientName());
        foreach ($dangerousExts as $bad) {
            if (preg_match('/\\.' . preg_quote($bad, '/') . '(\\.[a-z0-9]+)?$/i', $originalName)) {
                $errorMessage = "File dengan ekstensi atau format berisiko ditolak oleh sistem keamanan.";
                return false;
            }
        }

        // 3. Verifikasi MIME asli via finfo
        $tempPath = $file->getTempName();
        if (!file_exists($tempPath) || !is_readable($tempPath)) {
            $errorMessage = "Berkas sementara tidak dapat diakses.";
            return false;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = strtolower($finfo->file($tempPath) ?: '');

        $allowedMimes = [
            'jpg'  => ['image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'png'  => ['image/png'],
            'webp' => ['image/webp'],
            'gif'  => ['image/gif'],
            'pdf'  => ['application/pdf'],
            'doc'  => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
            'xls'  => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
            'csv'  => ['text/csv', 'text/plain', 'application/csv', 'text/comma-separated-values'],
            'txt'  => ['text/plain'],
        ];

        $validMimesForExt = $allowedMimes[$clientExt] ?? [];
        if (!empty($validMimesForExt) && !in_array($realMime, $validMimesForExt, true)) {
            $errorMessage = "Tipe MIME berkas ({$realMime}) tidak cocok dengan format .{$clientExt}.";
            return false;
        }

        // 4. Magic Bytes Check
        $handle = @fopen($tempPath, 'rb');
        if ($handle) {
            $header = fread($handle, 16);
            fclose($handle);

            if (in_array($clientExt, ['jpg', 'jpeg']) && !str_starts_with($header, "\xFF\xD8\xFF")) {
                $errorMessage = "Signature berkas JPEG/JPG tidak valid.";
                return false;
            }
            if ($clientExt === 'png' && !str_starts_with($header, "\x89PNG\r\n\x1a\n")) {
                $errorMessage = "Signature berkas PNG tidak valid.";
                return false;
            }
            if ($clientExt === 'webp' && (!str_starts_with($header, 'RIFF') || substr($header, 8, 4) !== 'WEBP')) {
                $errorMessage = "Signature berkas WEBP tidak valid.";
                return false;
            }
            if ($clientExt === 'pdf' && !str_starts_with($header, '%PDF-')) {
                $errorMessage = "Signature berkas PDF tidak valid.";
                return false;
            }
            if (in_array($clientExt, ['docx', 'xlsx']) && !str_starts_with($header, "PK\x03\x04")) {
                $errorMessage = "Signature dokumen Microsoft Office tidak valid.";
                return false;
            }
        }

        // 5. Anti-Polyglot: Cek apakah file menyembunyikan tag script PHP
        $fileSize = filesize($tempPath);
        $readBytes = min($fileSize, 65536);
        $contentSample = @file_get_contents($tempPath, false, null, 0, $readBytes);
        if ($fileSize > 65536) {
            $tailBytes = min(8192, $fileSize - $readBytes);
            $contentSample .= @file_get_contents($tempPath, false, null, $fileSize - $tailBytes, $tailBytes);
        }

        if (preg_match('/<\?(?:php|=)/i', $contentSample) || preg_match('/<script\s+language\s*=\s*["\']?php/i', $contentSample)) {
            $errorMessage = "Berkas ditolak: Terdeteksi pola executable mencurigakan di dalam berkas.";
            return false;
        }

        return true;
    }
}

