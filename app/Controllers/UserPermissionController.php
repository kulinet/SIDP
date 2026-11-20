<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\RolePermissionModel;
use App\Models\UserPermissionModel;

class UserPermissionController extends BaseController
{
    private $menus = [
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
    ];

    public function index($slug)
    {
        $userModel = new UserModel();
        $user = $userModel->where('username', $slug)->first();

        if (!$user) {
            return redirect()->to('/user')->with('error', 'User tidak ditemukan');
        }
        
        $userId = $user['id'];

        $rolePermModel = new RolePermissionModel();
        $userPermModel = new UserPermissionModel();

        $permissions = [];
        foreach ($this->menus as $menu) {
            $key = $menu['key'];
            
            // Dapatkan custom permission
            $custom = $userPermModel->getCustomPermissionStatus($userId, $key);
            
            // Dapatkan role default
            $roleDefault = $rolePermModel->checkPermission($user['role'], $key) ? 1 : 0;
            
            $permissions[] = [
                'key' => $key,
                'label' => $menu['label'],
                'role_default' => $roleDefault,
                'has_custom' => ($custom !== null),
                'is_allowed' => ($custom !== null) ? ($custom ? 1 : 0) : $roleDefault
            ];
        }

        $data = [
            'title' => 'Hak Akses Khusus: ' . $user['nama_lengkap'],
            'user' => $user,
            'permissions' => $permissions
        ];

        return view('user/permissions', $data);
    }

    public function save($slug)
    {
        $userModel = new UserModel();
        $user = $userModel->where('username', $slug)->first();

        if (!$user) {
            return redirect()->back()->with('error', 'User tidak ditemukan');
        }
        
        $userId = $user['id'];

        $perms = $this->request->getPost('perms') ?? [];
        $userPermModel = new UserPermissionModel();

        // Bersihkan semua custom permission lama untuk user ini
        $userPermModel->where('user_id', $userId)->delete();

        // Masukkan yang baru
        foreach ($this->menus as $menu) {
            $key = $menu['key'];
            $isAllowed = isset($perms[$key]) ? 1 : 0;
            
            $userPermModel->insert([
                'user_id' => $userId,
                'menu_key' => $key,
                'is_allowed' => $isAllowed
            ]);
        }

        return redirect()->to(base_url("user/permissions/{$slug}"))->with('success', 'Hak akses kustom berhasil disimpan.');
    }

    public function reset($slug)
    {
        $userModel = new UserModel();
        $user = $userModel->where('username', $slug)->first();

        if ($user) {
            $userPermModel = new UserPermissionModel();
            $userPermModel->where('user_id', $user['id'])->delete();
        }

        return redirect()->to(base_url("user/permissions/{$slug}"))->with('success', 'Hak akses telah dikembalikan ke standar role.');
    }
}
