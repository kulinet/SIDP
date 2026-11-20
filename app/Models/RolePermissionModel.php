<?php

namespace App\Models;

use CodeIgniter\Model;

class RolePermissionModel extends Model
{
    protected $table            = 'role_permissions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['role', 'menu_key', 'is_allowed'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function checkPermission($role, $menuKey)
    {
        // Admin always has permission
        if ($role === 'admin') return true;

        $permission = $this->where('role', $role)
                           ->where('menu_key', $menuKey)
                           ->first();

        if ($permission) {
            return (bool)$permission['is_allowed'];
        }

        // Default permissions if not set in DB
        $defaults = [
            'operator' => [
                'report', 'dashboard', 'comparisons', 'penyedia', 'swakelola', 'rup-opd', 'rup_itkp',
                'dashboard-lpse', 'lpse', 'epurchasing', 'itkp', 'dashboard_penyedia', 'penyedia-katalog', 'detail_penyedia', 'development',
                'monev', 'monev_summary', 'monev_opd', 'master_anggaran'
            ],
            'opd' => [
                'report', 'dashboard', 'comparisons', 'penyedia', 'swakelola', 'rup-opd', 'rup_itkp',
                'itkp', 'dashboard_penyedia', 'penyedia-katalog', 'monev_summary', 'monev_opd'
            ],
            'satker' => [
                'report', 'dashboard', 'comparisons', 'penyedia', 'swakelola', 'rup-opd', 'rup_itkp',
                'itkp', 'dashboard_penyedia', 'penyedia-katalog', 'monev_summary', 'monev_opd'
            ],
            'ppk' => [
                'monev', 'monev_opd', 'itkp', 'rup_itkp'
            ]
        ];

        return in_array($menuKey, $defaults[$role] ?? []);
    }

    public function getFirstAllowedUrl($role)
    {
        if ($role === 'admin') {
            return 'dashboard';
        }

        // Urutan menu prioritas beserta URL-nya
        $menuMapping = [
            'dashboard'                 => 'dashboard',
            'monev_summary'             => 'dashboard/monev-summary',
            'monev_opd'                 => 'dashboard/monev-opd',
            'monev'                     => 'dashboard/monev-proyek',
            'report'                    => 'report',
            'comparisons'               => 'dashboard/comparisons',
            'penyedia'                  => 'dashboard/penyedia',
            'swakelola'                 => 'dashboard/swakelola',
            'rup-opd'                   => 'dashboard/rup-opd',
            'rup_itkp'                  => 'dashboard/rup-itkp',
            'dashboard-lpse'            => 'dashboard/dashboard-lpse',
            'lpse_tender'               => 'dashboard/lpse/tender',
            'lpse_nontender'            => 'dashboard/lpse/nontender',
            'lpse_pencatatan_nontender' => 'dashboard/lpse/pencatatan_nontender',
            'lpse_pencatatan_swakelola' => 'dashboard/lpse/pencatatan_swakelola',
            'epurchasing'               => 'dashboard/epurchasing',
            'itkp'                      => 'dashboard/itkp',
            'dashboard_penyedia'        => 'dashboard/dashboard-penyedia',
            'master_anggaran'           => 'master-anggaran',
        ];

        foreach ($menuMapping as $key => $url) {
            if ($this->checkPermission($role, $key)) {
                return $url;
            }
        }

        return null;
    }
}

