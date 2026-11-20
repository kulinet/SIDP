<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\RolePermissionModel;

class RbacFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        if (!$session->get('isLoggedIn')) {
            return redirect()->to('/auth');
        }

        $role = $session->get('role');
        if ($role === 'admin') {
            return; // Admin always has all permissions
        }

        $path = $request->getUri()->getPath(); // e.g. "dashboard/comparisons"
        $normalizedPath = trim($path, '/');

        // Khusus upload SIKaP: Hanya admin yang boleh akses
        if (str_contains($normalizedPath, 'upload-sikap') || str_contains($normalizedPath, 'do-upload-sikap')) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Hanya administrator yang memiliki akses ke menu Upload SIKaP.');
        }

        $menuKey = $this->mapPathToMenuKey($path);

        if ($menuKey !== null) {
            $userPermModel = new \App\Models\UserPermissionModel();
            $permModel = new RolePermissionModel();
            
            $userId = $session->get('id');
            
            // Check custom permission first
            $hasCustomAccess = $userPermModel->getCustomPermissionStatus($userId, $menuKey);
            
            $isAllowed = false;
            if ($hasCustomAccess !== null) {
                $isAllowed = $hasCustomAccess;
            } else {
                $isAllowed = $permModel->checkPermission($role, $menuKey);
            }

            if (!$isAllowed) {
                // If it is an AJAX request, return a JSON error
                if ($request->isAJAX()) {
                    $response = service('response');
                    return $response->setJSON([
                        'status' => 'error',
                        'message' => 'Anda tidak memiliki hak akses untuk tindakan ini.'
                    ])->setStatusCode(403);
                }

                $firstUrl = $permModel->getFirstAllowedUrl($role);
                if ($firstUrl) {
                    return redirect()->to(base_url($firstUrl))->with('error', 'Anda tidak memiliki hak akses ke halaman tersebut.');
                } else {
                    $session->destroy();
                    return redirect()->to(base_url('auth'))->with('error', 'Akun Anda tidak memiliki hak akses menu apapun. Silakan hubungi admin.');
                }
            }
        }
    }

    private function mapPathToMenuKey($path)
    {
        $path = trim($path, '/');

        // Exact mappings for main pages
        if ($path === 'dashboard' || $path === 'dashboard/index') {
            return 'dashboard';
        }
        if ($path === 'dashboard/comparisons') {
            return 'comparisons';
        }
        if ($path === 'dashboard/penyedia') {
            return 'penyedia';
        }
        if ($path === 'dashboard/swakelola') {
            return 'swakelola';
        }
        if ($path === 'dashboard/rup-opd' || $path === 'dashboard/kajiulang-rup-data') {
            return 'rup-opd';
        }
        if ($path === 'dashboard/rup-itkp' ||
            $path === 'dashboard/getRupItkpDatatables' ||
            $path === 'dashboard/getRupItkpDashboardData' ||
            $path === 'dashboard/export-rup-itkp') {
            return 'rup_itkp';
        }
        if ($path === 'dashboard/dashboard-lpse') {
            return 'dashboard-lpse';
        }
        if ($path === 'dashboard/epurchasing' ||
            $path === 'dashboard/table-epurchasing' ||
            $path === 'dashboard/getEpurchasingDatatables' ||
            $path === 'dashboard/getEpurchasingDashboardData') {
            return 'epurchasing';
        }
        if ($path === 'dashboard/itkp' ||
            $path === 'dashboard/getItkpDatatables' ||
            $path === 'dashboard/export-itkp') {
            return 'itkp';
        }
        if ($path === 'dashboard/dashboard-penyedia' ||
            $path === 'dashboard/getDashboardPenyediaData' ||
            $path === 'dashboard/exportDashboardRekanan') {
            return 'dashboard_penyedia';
        }
        if ($path === 'dashboard/detail-penyedia' ||
            $path === 'dashboard/getDetailPenyedia' ||
            $path === 'dashboard/exportDetailPenyedia' ||
            $path === 'dashboard/getTransaksiPenyedia' ||
            $path === 'dashboard/getProfilPenyedia' ||
            $path === 'dashboard/detectDomisiliAi' ||
            $path === 'dashboard/getScrapeContractorsDatatables' ||
            $path === 'dashboard/exportContractors' ||
            $path === 'dashboard/get-bot-lpjk-status' ||
            $path === 'dashboard/do-launch-bot-lpjk' ||
            $path === 'dashboard/stop-bot-lpjk' ||
            $path === 'dashboard/get-bot-spse-status' ||
            $path === 'dashboard/do-launch-bot-spse' ||
            $path === 'dashboard/stop-bot-spse' ||
            strpos($path, 'dashboard/get-contractor-lpjk-detail') === 0 ||
            strpos($path, 'dashboard/fetchApiPenyedia') === 0) {
            return 'detail_penyedia';
        }
        if ($path === 'dashboard/penyedia-katalog' ||
            $path === 'dashboard/getPenyediaKatalog' ||
            $path === 'dashboard/exportPenyediaKatalog') {
            return 'penyedia-katalog';
        }
        if ($path === 'report' || $path === 'report/getSummaryData') {
            return 'report';
        }

        // Master Anggaran endpoints
        if (strpos($path, 'master-anggaran') === 0) {
            return 'master_anggaran';
        }

        // Monev Summary and OPD endpoints
        if ($path === 'dashboard/monev-summary' || $path === 'dashboard/export-monev-summary') {
            return 'monev_summary';
        }

        if ($path === 'dashboard/monev-opd' || 
            $path === 'dashboard/getMonevOpdData' ||
            $path === 'dashboard/getMonevProgresData' ||
            $path === 'dashboard/exportMonevProgresSpse') {
            return 'monev_opd';
        }

        // Dashboard data / support endpoints — require base dashboard permission
        if ($path === 'dashboard/layanan' ||
            $path === 'dashboard/statistik' ||
            $path === 'dashboard/clear-cache' ||
            $path === 'dashboard/getDashboardLpseData' ||
            strpos($path, 'dashboard/getData/') === 0 ||
            strpos($path, 'dashboard/getDashboardSummary/') === 0 ||
            strpos($path, 'dashboard/getDatatables/') === 0 ||
            strpos($path, 'dashboard/getFilterOptions/') === 0 ||
            strpos($path, 'dashboard/getChartData/') === 0 ||
            strpos($path, 'dashboard/getLpseDatatables/') === 0 ||
            strpos($path, 'dashboard/export/') === 0 ||
            $path === 'dashboard/export-rup-opd' ||
            $path === 'dashboard/export-rup-detail' ||
            $path === 'dashboard/export-non-pengadaan') {
            return 'dashboard';
        }

        // LPSE Subpages
        if (strpos($path, 'dashboard/lpse/tender') === 0) {
            return 'lpse_tender';
        }
        if (strpos($path, 'dashboard/lpse/nontender') === 0) {
            return 'lpse_nontender';
        }
        if (strpos($path, 'dashboard/lpse/pencatatan_nontender') === 0) {
            return 'lpse_pencatatan_nontender';
        }
        if (strpos($path, 'dashboard/lpse/pencatatan_swakelola') === 0) {
            return 'lpse_pencatatan_swakelola';
        }

        // Monev Proyek Subpages & Supporting API endpoints
        if ($path === 'dashboard/monev' ||
            $path === 'dashboard/monev-proyek' ||
            strpos($path, 'dashboard/monev/') === 0 ||
            strpos($path, 'dashboard/monev-v2/') === 0 ||
            strpos($path, 'dashboard/add-monev-paket') === 0 ||
            strpos($path, 'dashboard/remove-monev-paket') === 0 ||
            strpos($path, 'dashboard/get-rup-details') === 0 ||
            strpos($path, 'dashboard/getMonevChartData') === 0 ||
            strpos($path, 'dashboard/analyze-anomaly') === 0 ||
            strpos($path, 'dashboard/analyze-epurchasing') === 0 ||
            strpos($path, 'dashboard/verify-non-pengadaan-ai') === 0) {
            return 'monev';
        }

        // Jika path tidak dikenali, kembalikan null agar tidak diblokir
        // (route di luar dashboard sudah dilindungi filter masing-masing)
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
