<?php

namespace App\Controllers;

use App\Models\DynamicMenuModel;
use App\Models\DynamicColumnModel;
use CodeIgniter\API\ResponseTrait;

class Development extends BaseController
{
    use ResponseTrait;

    protected $menuModel;
    protected $columnModel;

    public function __construct()
    {
        $this->menuModel = new DynamicMenuModel();
        $this->columnModel = new DynamicColumnModel();
    }

    public function index()
    {
        $data = [
            'title' => 'Development - Dynamic Menus',
            'user'  => session()->get(),
            'menus' => $this->menuModel->findAll()
        ];
        return view('development/index', $data);
    }

    public function create()
    {
        $data = [
            'title' => 'Create Dynamic Menu',
            'user'  => session()->get(),
            'api_keys' => []
        ];
        return view('development/form', $data);
    }

    public function store()
    {
        $data = $this->request->getPost();
        
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = url_title($data['name'], '-', true);
        }

        if ($id = $this->menuModel->insert($data)) {
            return redirect()->to(base_url("development/edit/{$id}"))->with('success', 'Menu created. Now configure the columns.');
        }

        return redirect()->back()->withInput()->with('error', 'Failed to create menu.');
    }

    public function edit($id)
    {
        $menu = $this->menuModel->getWithColumns($id);
        if (!$menu) {
            return redirect()->to(base_url('development'))->with('error', 'Menu not found.');
        }

        // Pre-fetch API keys for the mapping modal
        $api_keys = [];
        if (!empty($menu['api_url'])) {
            $api_keys = $this->_getApiKeys($menu['api_url']);
        }

        $data = [
            'title' => 'Edit Dynamic Menu: ' . $menu['name'],
            'user'  => session()->get(),
            'menu'  => $menu,
            'api_keys' => $api_keys
        ];
        return view('development/form', $data);
    }

    public function update($id)
    {
        $data = $this->request->getPost();
        if ($this->menuModel->update($id, $data)) {
            return redirect()->to(base_url('development'))->with('success', 'Menu updated.');
        }

        return redirect()->back()->withInput()->with('error', 'Failed to update menu.');
    }

    public function delete($id)
    {
        if ($this->menuModel->delete($id)) {
            return redirect()->to(base_url('development'))->with('success', 'Menu deleted.');
        }
        return redirect()->back()->with('error', 'Failed to delete menu.');
    }

    // Column Management
    public function addColumn($menuId)
    {
        $data = $this->request->getPost();
        $data['menu_id'] = $menuId;
        
        if ($this->columnModel->insert($data)) {
            return redirect()->back()->with('success', 'Column added.');
        }
        return redirect()->back()->with('error', 'Failed to add column.');
    }

    public function deleteColumn($id)
    {
        if ($this->columnModel->delete($id)) {
            return redirect()->back()->with('success', 'Column deleted.');
        }
        return redirect()->back()->with('error', 'Failed to delete column.');
    }

    // API Tester
    public function fetchApiKeys()
    {
        $url = $this->request->getPost('url');
        if (!$url) return $this->fail('URL is required');

        $keys = $this->_getApiKeys($url);
        return $this->respond($keys);
    }

    private function _getApiKeys($url)
    {
        $client = \Config\Services::curlrequest();
        try {
            // Add timeout for better UX
            $response = $client->get($url, ['timeout' => 5]);
            $data = json_decode($response->getBody(), true);
            
            if (empty($data)) return [];

            // Handle potential nested data structure (e.g. { data: [...] } or direct [...])
            $items = $data;
            if (isset($data['data']) && is_array($data['data'])) {
                $items = $data['data'];
            }

            // If it's a list, take the first item to get keys
            $item = is_array($items) ? ($items[0] ?? $items) : $items;
            if (!is_array($item)) return [];

            return array_keys($item);
        } catch (\Exception $e) {
            return [];
        }
    }

    // Chart Management
    public function charts()
    {
        $chartModel = new \App\Models\DashboardChartModel();
        $data = [
            'title' => 'Dashboard Chart Configuration',
            'user'  => session()->get(),
            'charts' => $chartModel->getWithMenu(),
            'menus'  => $this->menuModel->findAll()
        ];
        return view('development/charts', $data);
    }

    public function storeChart()
    {
        $chartModel = new \App\Models\DashboardChartModel();
        $data = $this->request->getPost();
        
        if ($chartModel->insert($data)) {
            return redirect()->to(base_url('development/charts'))->with('success', 'Chart configuration saved.');
        }

        return redirect()->back()->withInput()->with('error', 'Failed to save chart.');
    }

    public function updateChart($id)
    {
        $chartModel = new \App\Models\DashboardChartModel();
        $data = $this->request->getPost();
        
        if ($chartModel->update($id, $data)) {
            return redirect()->to(base_url('development/charts'))->with('success', 'Chart configuration updated.');
        }

        return redirect()->back()->withInput()->with('error', 'Failed to update chart.');
    }

    public function deleteChart($id)
    {
        $chartModel = new \App\Models\DashboardChartModel();
        if ($chartModel->delete($id)) {
            return redirect()->to(base_url('development/charts'))->with('success', 'Chart deleted.');
        }
        return redirect()->back()->with('error', 'Failed to delete chart.');
    }
}
