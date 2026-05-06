<?php

namespace App\Controllers;

use App\Models\DynamicMenuModel;
use App\Models\DynamicColumnModel;
use CodeIgniter\API\ResponseTrait;

class DynamicData extends BaseController
{
    use ResponseTrait;

    protected $menuModel;
    protected $columnModel;

    public function __construct()
    {
        $this->menuModel = new DynamicMenuModel();
        $this->columnModel = new DynamicColumnModel();
    }

    public function view($slug)
    {
        $menu = $this->menuModel->where('slug', $slug)->first();
        if (!$menu) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Dynamic menu not found.');
        }

        $columns = $this->columnModel->where('menu_id', $menu['id'])->orderBy('order_index', 'ASC')->findAll();

        $data = [
            'title'   => $menu['name'],
            'user'    => session()->get(),
            'menu'    => $menu,
            'columns' => $columns
        ];
        
        return view('dashboard/dynamic_table', $data);
    }

    public function getDatatables($slug)
    {
        $menu = $this->menuModel->where('slug', $slug)->first();
        if (!$menu) return $this->failNotFound('Menu not found');

        $cacheKey = "dynamic_data_" . md5($slug);
        $allData = cache($cacheKey);
        
        if ($allData === null) {
            $client = \Config\Services::curlrequest();
            try {
                $response = $client->get($menu['api_url'], [
                    'headers' => ['Accept' => 'application/json'],
                    'timeout' => 10
                ]);
                $allData = json_decode($response->getBody(), true);
                if (!is_array($allData)) {
                    $allData = [];
                } else {
                    cache()->save($cacheKey, $allData, 300);
                }
            } catch (\Exception $e) {
                log_message('error', '[DynamicData] API Fetch error: ' . $e->getMessage());
                $allData = [];
            }
        }

        $draw = $this->request->getGet('draw') ?? 1;
        $start = $this->request->getGet('start') ?? 0;
        $length = $this->request->getGet('length') ?? 10;
        $searchData = $this->request->getGet('search');
        $search = $searchData['value'] ?? '';

        // Search
        if (!empty($search)) {
            $allData = array_filter($allData, function($item) use ($search) {
                foreach ($item as $val) {
                    if (is_scalar($val) && stripos((string)$val, $search) !== false) return true;
                }
                return false;
            });
        }

        $totalRecords = count($allData);

        // Sorting
        $order = $this->request->getGet('order');
        if ($order && isset($order[0]['column'])) {
            $colIdx = $order[0]['column'];
            $dir = $order[0]['dir'] ?? 'asc';
            $columns = $this->columnModel->where('menu_id', $menu['id'])->orderBy('order_index', 'ASC')->findAll();
            $colName = $columns[$colIdx]['field_key'] ?? null;
            
            if ($colName) {
                usort($allData, function($a, $b) use ($colName, $dir) {
                    $valA = $a[$colName] ?? '';
                    $valB = $b[$colName] ?? '';
                    if (is_numeric($valA) && is_numeric($valB)) {
                        return ($dir === 'asc') ? $valA <=> $valB : $valB <=> $valA;
                    }
                    return ($dir === 'asc') ? strnatcasecmp((string)$valA, (string)$valB) : strnatcasecmp((string)$valB, (string)$valA);
                });
            }
        }

        // Pagination
        $dataPage = ($length == -1) ? $allData : array_slice($allData, intval($start), intval($length));

        return $this->response->setJSON([
            'draw'            => intval($draw),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data'            => array_values($dataPage)
        ]);

    }

}
