<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class LogFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // We log after the request so we know if it was successful, but doing it in after() 
        // sometimes misses the exact payload or route if it redirects. 
        // We can just log in after() unless it's a specific action.
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $method = $request->getMethod(true);

        // We auto-log all requests now, but we skip certain frequent AJAX/system routes
        // to prevent spamming the database with noisy data.
        $uri = $request->getUri()->getPath();

        // Skip login/logout routes as they are handled manually in Auth controller
        // Skip getDatatables, getChartData, and other frequent AJAX calls
        $skipRoutes = [
            'auth/login', 'auth/logout', 
            'getDatatables', 'getChartData', 
            'getDashboardSummary', 'getDashboardLpseData', 'getEpurchasingDashboardData',
            'getData', 'getFilterOptions', 'getSummaryData'
        ];

        foreach ($skipRoutes as $route) {
            if (str_contains($uri, $route)) {
                return;
            }
        }

        $logModel = new \App\Models\UserLogModel();
        $session = session();
        
        $user_id = $session->get('id');
        if (!$user_id) return; // Only log authenticated users for these generic routes

        $username = $session->get('username') ?? 'Unknown';
        $role = $session->get('role');
        
        // Generate a readable action description
        $action = "{$method} request to /{$uri}";
        
        $logModel->insert([
            'user_id'    => $user_id,
            'username'   => $username,
            'role'       => $role,
            'action'     => $action,
            'ip_address' => $request->getIPAddress(),
            'user_agent' => $request->getUserAgent()->getAgentString(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
