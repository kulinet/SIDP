<?php

namespace App\Controllers;

class PatchController extends BaseController
{
    private string $backupDir;
    private string $patchLogFile;

    public function __construct()
    {
        $this->backupDir   = WRITEPATH . 'backups' . DIRECTORY_SEPARATOR;
        $this->patchLogFile = $this->backupDir . 'patch_history.json';

        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    public function index()
    {
        $history = $this->getHistory();

        return view('development/patch', [
            'title'   => 'System Patcher',
            'user'    => session()->get(),
            'history' => $history,
        ]);
    }

    public function upload()
    {
        // Validate file
        $file = $this->request->getFile('patch_file');

        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'File tidak valid atau tidak ditemukan.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file->getTempName());
        
        if ($mimeType !== 'application/zip') {
            return redirect()->back()->with('error', 'Hanya file .zip yang diperbolehkan. Tipe file terdeteksi: ' . $mimeType);
        }

        if ($file->getSize() > 100 * 1024 * 1024) { // 100MB limit
            return redirect()->back()->with('error', 'Ukuran file maksimum adalah 100MB.');
        }

        $patchName   = pathinfo($file->getClientName(), PATHINFO_FILENAME);
        $timestamp   = date('Ymd_His');
        $backupName  = "backup_before_{$patchName}_{$timestamp}.zip";
        $backupPath  = $this->backupDir . $backupName;

        // Move uploaded patch to a temp location
        $tempPath = $this->backupDir . "temp_patch_{$timestamp}.zip";
        $file->move($this->backupDir, "temp_patch_{$timestamp}.zip");

        if (!file_exists($tempPath)) {
            return redirect()->back()->with('error', 'Gagal menyimpan file patch sementara.');
        }

        // Step 1: Auto-backup files that will be overwritten
        $zip = new \ZipArchive();
        if ($zip->open($tempPath) !== true) {
            unlink($tempPath);
            return redirect()->back()->with('error', 'File ZIP tidak dapat dibuka atau rusak.');
        }

        $backupZip = new \ZipArchive();
        $backupZip->open($backupPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $filesInPatch = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name   = $zip->getNameIndex($i);
            $target = ROOTPATH . $name;

            // Security: block path traversal and direct PHP in public
            if (str_contains($name, '..') || str_contains($name, "\0")) {
                $zip->close();
                $backupZip->close();
                unlink($tempPath);
                @unlink($backupPath);
                return redirect()->back()->with('error', 'File ZIP mengandung path yang tidak aman.');
            }

            $filesInPatch[] = $name;

            // Backup existing file before overwrite
            if (file_exists($target) && !is_dir($target)) {
                $backupZip->addFile($target, $name);
            }
        }

        $backupZip->close();

        // Step 2: Extract patch to ROOTPATH
        $extractResult = $zip->extractTo(ROOTPATH);
        $zip->close();
        unlink($tempPath);

        if (!$extractResult) {
            @unlink($backupPath);
            return redirect()->back()->with('error', 'Gagal mengekstrak file patch ke server.');
        }

        // Step 3: Clear all caches
        cache()->clean();

        // Step 4: Run pending database migrations automatically if any
        $migrationNote = '';
        try {
            $runner = \Config\Services::migrations();
            $runner->latest();
            $migrationNote = ' Database migrasi telah diperbarui.';
        } catch (\Throwable $mEx) {
            log_message('error', 'Patch auto-migration error: ' . $mEx->getMessage());
            $migrationNote = ' (Peringatan migrasi: ' . $mEx->getMessage() . ')';
        }

        // Step 5: Log this patch internally
        $this->logPatch([
            'patch_name'   => $file->getClientName(),
            'backup_file'  => $backupName,
            'applied_at'   => date('Y-m-d H:i:s'),
            'applied_by'   => session()->get('username') ?? 'admin',
            'file_count'   => count($filesInPatch),
            'files'        => $filesInPatch,
        ]);

        $this->logActivity("Applied patch: {$file->getClientName()}");

        return redirect()->to(base_url('development/patch'))
                         ->with('success', "Patch \"{$file->getClientName()}\" berhasil diterapkan. " . count($filesInPatch) . " file diperbarui.{$migrationNote} Backup tersimpan: {$backupName}");
    }

    public function rollback(string $backupFile)
    {
        // Sanitize
        $backupFile = basename($backupFile); // Strip any directory components
        if (!str_starts_with($backupFile, 'backup_before_')) {
            return redirect()->back()->with('error', 'File rollback tidak valid.');
        }

        $backupPath = $this->backupDir . $backupFile;
        if (!file_exists($backupPath)) {
            return redirect()->back()->with('error', 'File backup tidak ditemukan.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($backupPath) !== true) {
            return redirect()->back()->with('error', 'File backup tidak dapat dibuka.');
        }

        // Security check
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_contains($name, '..') || str_contains($name, "\0")) {
                $zip->close();
                return redirect()->back()->with('error', 'File backup mengandung path yang tidak aman.');
            }
        }

        $result = $zip->extractTo(ROOTPATH);
        $zip->close();

        if (!$result) {
            return redirect()->back()->with('error', 'Gagal melakukan rollback.');
        }

        // Clear cache after rollback
        cache()->clean();

        // Log rollback
        $this->logPatch([
            'patch_name'  => "[ROLLBACK] " . $backupFile,
            'backup_file' => null,
            'applied_at'  => date('Y-m-d H:i:s'),
            'applied_by'  => session()->get('username') ?? 'admin',
            'file_count'  => $zip->numFiles ?? '?',
            'files'       => [],
        ]);

        $this->logActivity("Rolled back patch from backup: {$backupFile}");

        return redirect()->to(base_url('development/patch'))
                         ->with('success', "Rollback dari \"{$backupFile}\" berhasil diterapkan.");
    }

    public function deleteBackup(string $backupFile)
    {
        $backupFile = basename($backupFile);
        if (!str_starts_with($backupFile, 'backup_before_')) {
            return redirect()->back()->with('error', 'File tidak valid.');
        }

        $backupPath = $this->backupDir . $backupFile;
        if (file_exists($backupPath)) {
            unlink($backupPath);
            $this->logActivity("Deleted patch backup: {$backupFile}");
        }

        return redirect()->back()->with('success', "Backup \"{$backupFile}\" berhasil dihapus.");
    }

    // ─── Generate Patch ────────────────────────────────────

    public function generate()
    {
        $since = $this->request->getGet('since');
        $sinceTimestamp = $since ? strtotime($since) : strtotime('-7 days');

        // Scan key application directories for modified files
        $scanDirs = [
            'app',
            'public',
        ];

        $modifiedFiles = [];
        foreach ($scanDirs as $dir) {
            $fullDir = ROOTPATH . $dir;
            if (is_dir($fullDir)) {
                $this->scanModifiedFiles($fullDir, ROOTPATH, $sinceTimestamp, $modifiedFiles);
            }
        }

        // Sort by modification time (newest first)
        usort($modifiedFiles, fn($a, $b) => $b['mtime'] - $a['mtime']);

        return view('development/patch_generate', [
            'title'         => 'Generate Patch File',
            'user'          => session()->get(),
            'modifiedFiles' => $modifiedFiles,
            'since'         => date('Y-m-d', $sinceTimestamp),
        ]);
    }

    public function downloadPatch()
    {
        $selectedFiles = $this->request->getPost('files');
        $patchLabel    = $this->request->getPost('patch_label') ?: 'patch';

        if (empty($selectedFiles)) {
            return redirect()->back()->with('error', 'Tidak ada file yang dipilih.');
        }

        // Sanitize label
        $patchLabel = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $patchLabel);
        $timestamp  = date('Ymd_His');
        $zipName    = "{$patchLabel}_{$timestamp}.zip";
        $zipPath    = $this->backupDir . $zipName;

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()->with('error', 'Gagal membuat file ZIP.');
        }

        $addedCount = 0;
        foreach ($selectedFiles as $relativePath) {
            // Security: block traversal
            if (str_contains($relativePath, '..') || str_contains($relativePath, "\0")) {
                continue;
            }

            $fullPath = ROOTPATH . $relativePath;
            if (file_exists($fullPath) && !is_dir($fullPath)) {
                // Use forward slashes in ZIP for cross-platform compatibility
                $zipEntryName = str_replace('\\', '/', $relativePath);
                $zip->addFile($fullPath, $zipEntryName);
                $addedCount++;
            }
        }

        $zip->close();

        if ($addedCount === 0) {
            @unlink($zipPath);
            return redirect()->back()->with('error', 'Tidak ada file valid yang ditambahkan ke patch.');
        }

        // Stream the file as download then delete
        $zipContent = file_get_contents($zipPath);
        @unlink($zipPath); // cleanup temp file from server

        return $this->response
            ->setHeader('Content-Type', 'application/zip')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $zipName . '"')
            ->setHeader('Content-Length', (string)strlen($zipContent))
            ->setHeader('Cache-Control', 'no-cache, must-revalidate')
            ->setBody($zipContent)
            ->send();
    }

    private function scanModifiedFiles(string $dir, string $rootPath, int $sinceTimestamp, array &$results): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        // Directories to skip
        $skipDirs = ['vendor', 'node_modules', '.git', 'writable', 'ThirdParty'];

        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;

            $fullPath = $file->getRealPath();
            $relativePath = str_replace($rootPath, '', $fullPath);
            $relativePath = str_replace('\\', '/', $relativePath);

            // Skip excluded directories
            $skip = false;
            foreach ($skipDirs as $skipDir) {
                if (str_contains($relativePath, $skipDir . '/')) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) continue;

            $mtime = $file->getMTime();
            if ($mtime >= $sinceTimestamp) {
                $results[] = [
                    'path'     => $relativePath,
                    'mtime'    => $mtime,
                    'size'     => $file->getSize(),
                    'modified' => date('Y-m-d H:i:s', $mtime),
                ];
            }
        }
    }

    // ─── Helpers ───────────────────────────────────────────

    private function getHistory(): array
    {
        if (!file_exists($this->patchLogFile)) {
            return [];
        }
        $json = file_get_contents($this->patchLogFile);
        $data = json_decode($json, true);
        return is_array($data) ? array_reverse($data) : [];
    }

    private function logPatch(array $entry): void
    {
        $history = array_reverse($this->getHistory()); // oldest first
        
        if (!str_starts_with($entry['patch_name'], '[ROLLBACK]')) {
            $time = isset($entry['applied_at']) ? strtotime($entry['applied_at']) : time();
            $month = date('m', $time);
            
            $countThisMonth = 0;
            foreach ($history as $h) {
                if (isset($h['applied_at']) && date('m', strtotime($h['applied_at'])) === $month) {
                    if (!str_starts_with($h['patch_name'], '[ROLLBACK]')) {
                        $countThisMonth++;
                    }
                }
            }
            
            $patchSeq = str_pad($countThisMonth + 1, 2, '0', STR_PAD_LEFT);
            $entry['app_version'] = "V1.{$month}.{$patchSeq}";
            
            // Update global .env version
            $envPath = ROOTPATH . '.env';
            if (file_exists($envPath)) {
                $env = file_get_contents($envPath);
                if (str_contains($env, 'APP_VERSION=')) {
                    $env = preg_replace('/^APP_VERSION=.*$/m', 'APP_VERSION="' . $entry['app_version'] . '"', $env);
                } else {
                    $env .= "\nAPP_VERSION=\"" . $entry['app_version'] . "\"\n";
                }
                file_put_contents($envPath, $env);
            }
        } else {
            // For rollback, keep it empty or "-" 
            $entry['app_version'] = "-";
        }
        
        $history[] = $entry;
        file_put_contents($this->patchLogFile, json_encode($history, JSON_PRETTY_PRINT));
    }
}
