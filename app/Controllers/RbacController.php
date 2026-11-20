<?php

namespace App\Controllers;

use App\Models\RolePermissionModel;
use App\Models\UserModel;

class RbacController extends BaseController
{
    public function index()
    {
        $roleModel = new RolePermissionModel();
        $userModel = new UserModel();
        
        // Get unique roles from users table
        $roles = $userModel->select('role')->distinct()->findAll();
        $roles = array_column($roles, 'role');
        
        // Ensure standard roles exist in list
        $standardRoles = ['admin', 'operator', 'opd', 'satker', 'ppk', 'user'];
        $roles = array_unique(array_merge($roles, $standardRoles));

        $menus = [
            ['key' => 'report', 'label' => 'Summary - Laporan Realisasi'],
            ['key' => 'dashboard', 'label' => 'Data RUP - Dashboard'],
            ['key' => 'comparisons', 'label' => 'Data RUP - Perbandingan Tahunan'],
            ['key' => 'penyedia', 'label' => 'Data RUP - Penyedia'],
            ['key' => 'swakelola', 'label' => 'Data RUP - Swakelola'],
            ['key' => 'rup-opd', 'label' => 'Data RUP - RUP OPD'],
            ['key' => 'rup_itkp', 'label' => 'Data RUP - Data ITKP'],
            ['key' => 'dashboard-lpse', 'label' => 'Data Pengadaan - Dashboard LPSE'],
            ['key' => 'lpse_tender', 'label' => 'Data Pengadaan - Data LPSE - Tender'],
            ['key' => 'lpse_nontender', 'label' => 'Data Pengadaan - Data LPSE - Non Tender'],
            ['key' => 'lpse_pencatatan_nontender', 'label' => 'Data Pengadaan - Data LPSE - Pencatatan Non Tender'],
            ['key' => 'lpse_pencatatan_swakelola', 'label' => 'Data Pengadaan - Data LPSE - Pencatatan Swakelola'],
            ['key' => 'epurchasing', 'label' => 'Data Pengadaan - E-Purchasing'],
            ['key' => 'itkp', 'label' => 'Data Pengadaan - Data ITKP'],
            ['key' => 'dashboard_penyedia', 'label' => 'Database - Dashboard Penyedia'],
            ['key' => 'penyedia-katalog', 'label' => 'Data Pengadaan - Penyedia Katalog'],
            ['key' => 'detail_penyedia', 'label' => 'Data Pengadaan - Detail Penyedia'],
            ['key' => 'monev', 'label' => 'MONEV - Monev Proyek'],
            ['key' => 'monev_summary', 'label' => 'MONEV - Summary Progres'],
            ['key' => 'monev_opd', 'label' => 'Data Pengadaan - Monev OPD'],
            ['key' => 'master_anggaran', 'label' => 'Aplikasi - Master Anggaran'],
            ['key' => 'user_management', 'label' => 'Aplikasi - Manage User'],
            ['key' => 'chatbot_settings', 'label' => 'Aplikasi - Chatbot Settings'],
            ['key' => 'chatbot_knowledge', 'label' => 'Aplikasi - Library Kepintaran AI'],
            ['key' => 'api_settings', 'label' => 'Aplikasi - Setting AGI Integrator'],
            ['key' => 'sync_data', 'label' => 'Aplikasi - Sinkronisasi Data'],
            ['key' => 'master_menu', 'label' => 'Aplikasi - Master Menu'],
            ['key' => 'patch_app', 'label' => 'Aplikasi - System Patcher'],
            ['key' => 'user_logs', 'label' => 'Aplikasi - Activity Logs'],
            ['key' => 'require_2fa', 'label' => 'Keamanan - Wajibkan 2FA (Google Authenticator)'],
        ];

        // Fetch current permissions using the checkPermission method (so defaults are resolved properly in UI checkboxes)
        $permissions = [];
        foreach ($roles as $role) {
            foreach ($menus as $menu) {
                $permissions[$role][$menu['key']] = $roleModel->checkPermission($role, $menu['key']) ? 1 : 0;
            }
        }

        return view('user/rbac', [
            'title' => 'Manage RBAC',
            'user'  => session()->get(),
            'roles' => $roles,
            'menus' => $menus,
            'permissions' => $permissions
        ]);
    }

    public function save()
    {
        $roleModel = new RolePermissionModel();
        $userModel = new UserModel();
        $perms = $this->request->getPost('perms'); // [role][menu_key] = 1

        // Get standard roles and menus to save explicit states for all
        $roles = $userModel->select('role')->distinct()->findAll();
        $roles = array_column($roles, 'role');
        $standardRoles = ['admin', 'operator', 'opd', 'satker', 'ppk', 'user'];
        $roles = array_unique(array_merge($roles, $standardRoles));

        $menus = [
            'report', 'dashboard', 'comparisons', 'penyedia', 'swakelola', 'rup-opd',
            'dashboard-lpse', 'lpse_tender', 'lpse_nontender', 'lpse_pencatatan_nontender',
            'lpse_pencatatan_swakelola', 'epurchasing', 'itkp', 'dashboard_penyedia', 'penyedia-katalog', 'detail_penyedia', 'monev', 'monev_summary', 'monev_opd',
            'master_anggaran', 'user_management', 'chatbot_settings', 'chatbot_knowledge', 'api_settings', 'sync_data', 'master_menu',
            'patch_app', 'user_logs', 'require_2fa'
        ];

        // Clear existing permissions to sync
        $roleModel->truncate();

        foreach ($roles as $role) {
            if ($role === 'admin') continue; // admin always allowed, no need to store
            foreach ($menus as $menuKey) {
                $isAllowed = isset($perms[$role][$menuKey]) ? 1 : 0;
                $roleModel->insert([
                    'role'       => $role,
                    'menu_key'   => $menuKey,
                    'is_allowed' => $isAllowed
                ]);
            }
        }

        return redirect()->to(base_url('user/rbac'))->with('success', 'Hak akses berhasil diperbarui.');
    }
}
