<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;

class Api extends BaseController
{
    use ResponseTrait;

    protected $user;

    public function __construct()
    {
        // Simple Token-based Auth for Mobile
        $token = $this->request->getHeaderLine('X-API-KEY');
        if (!$token) {
            $token = $this->request->getGet('api_key'); // Fallback to query param
        }

        if (!$token) {
            header('Content-Type: application/json');
            echo json_encode(['status' => false, 'message' => 'API Key Required (X-API-KEY header)']);
            exit;
        }

        $userModel = new \App\Models\UserModel();
        $this->user = $userModel->where('api_token', $token)->first();

        if (!$this->user) {
            header('Content-Type: application/json');
            echo json_encode(['status' => false, 'message' => 'Invalid API Key']);
            exit;
        }
    }

    /**
     * Dashboard Summary for Mobile
     * GET /api/summary
     */
    public function getSummary()
    {
        $dashboard = new Dashboard();
        $year = date('Y');
        
        // Fetch real data from Dashboard methods
        $this->request->setGlobal('get', ['year' => $year]);
        $lpseRes = $dashboard->getDashboardLpseData();
        $lpseData = json_decode($lpseRes->getJSON(), true);

        return $this->respond([
            'status' => true,
            'message' => 'Dashboard Summary Retrieved',
            'data' => [
                'year' => $year,
                'lpse_summary' => $lpseData['totals'] ?? [],
                'recent_status' => $lpseData['by_year'] ?? [],
                'last_updated' => date('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * LPSE Data (Tender/Non-Tender)
     * GET /api/lpse/(:segment)
     */
    public function getLpse($type)
    {
        $dashboard = new Dashboard();
        $year = $this->request->getGet('year') ?? date('Y');
        $search = $this->request->getGet('search') ?? '';
        
        // Mocking DataTables request parameters
        $_GET['year'] = $year;
        $_GET['start'] = 0;
        $_GET['length'] = 100; // Return top 100 for mobile
        if ($search) $_GET['search']['value'] = $search;

        $res = $dashboard->getLpseDatatables($type);
        $json = json_decode($res->getJSON(), true);

        return $this->respond([
            'status' => true,
            'type' => $type,
            'total' => $json['recordsTotal'] ?? 0,
            'data' => $json['data'] ?? []
        ]);
    }

    /**
     * E-Purchasing Data
     * GET /api/epurchasing
     */
    public function getEpurchasing()
    {
        $dashboard = new Dashboard();
        $year = $this->request->getGet('year') ?? date('Y');
        
        $_GET['year'] = $year;
        $res = $dashboard->getEpurchasingDashboardData();
        $json = json_decode($res->getJSON(), true);

        return $this->respond([
            'status' => true,
            'data' => $json
        ]);
    }

    /**
     * RUP OPD Monitoring
     * GET /api/rup-opd
     */
    public function getRupOpd()
    {
        $dashboard = new Dashboard();
        
        // Use the rupOpd logic but ensure we return only the summary data
        // We'll need a way to bypass the view return in Dashboard::rupOpd
        // For now, let's just fetch the cache directly since Dashboard::rupOpd caches it
        
        $year = date('Y');
        $cacheKey = "rup_opd_v10_{$year}"; // Matching version in Dashboard.php
        $summary = cache($cacheKey);
        
        if (!$summary) {
            return $this->fail('Data not cached yet. Please visit web dashboard first or wait for sync.');
        }

        return $this->respond([
            'status' => true,
            'data' => $summary
        ]);
    }

    /**
     * Authentication for Mobile
     * POST /api/login
     */
    public function login()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $userModel = new \App\Models\UserModel();
        $user = $userModel->where('username', $username)->first();

        if ($user && password_verify($password, $user['password'])) {
            // In a real app, generate a JWT token here.
            // For now, return user profile.
            unset($user['password']);
            return $this->respond([
                'status' => true,
                'message' => 'Login Successful',
                'user' => $user,
                'token' => base64_encode($username . ':' . time()) // Dummy token
            ]);
        }

        return $this->failUnauthorized('Invalid username or password');
    }
}
