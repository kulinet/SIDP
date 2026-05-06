<?php

namespace App\Models;

use CodeIgniter\Model;

class DashboardChartModel extends Model
{
    protected $table            = 'dashboard_charts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['title', 'menu_id', 'chart_type', 'label_field', 'value_field', 'aggregation'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getWithMenu($id = null)
    {
        if ($id === null) {
            return $this->select('dashboard_charts.*, dynamic_menus.name as menu_name, dynamic_menus.api_url')
                        ->join('dynamic_menus', 'dynamic_menus.id = dashboard_charts.menu_id')
                        ->findAll();
        }

        return $this->select('dashboard_charts.*, dynamic_menus.name as menu_name, dynamic_menus.api_url')
                    ->join('dynamic_menus', 'dynamic_menus.id = dashboard_charts.menu_id')
                    ->find($id);
    }
}
