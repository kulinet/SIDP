<?php

namespace App\Models;

use CodeIgniter\Model;

class UserPermissionModel extends Model
{
    protected $table            = 'user_permissions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['user_id', 'menu_key', 'is_allowed'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Cek apakah user memiliki custom permission untuk menu tertentu
     * Return:
     * - true (jika diizinkan)
     * - false (jika dilarang)
     * - null (jika tidak ada setting kustom, sehingga harus fallback ke role)
     */
    public function getCustomPermissionStatus($userId, $menuKey)
    {
        $perm = $this->where('user_id', $userId)
                     ->where('menu_key', $menuKey)
                     ->first();

        if ($perm) {
            return $perm['is_allowed'] == 1;
        }

        return null;
    }
}
