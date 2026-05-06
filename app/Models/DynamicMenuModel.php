<?php

namespace App\Models;

use CodeIgniter\Model;

class DynamicMenuModel extends Model
{
    protected $table            = 'dynamic_menus';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['name', 'slug', 'api_url', 'description', 'icon'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getWithColumns($id = null)
    {
        if ($id === null) {
            return $this->findAll();
        }

        $menu = $this->find($id);
        if ($menu) {
            $columnModel = new DynamicColumnModel();
            $menu['columns'] = $columnModel->where('menu_id', $id)->orderBy('order_index', 'ASC')->findAll();
        }

        return $menu;
    }
}
