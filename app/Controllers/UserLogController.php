<?php

namespace App\Controllers;

use App\Models\UserLogModel;

class UserLogController extends BaseController
{
    public function index()
    {
        return view('user/logs', [
            'title' => 'Activity Logs',
            'user'  => session()->get()
        ]);
    }

    public function getDatatables()
    {
        $logModel = new UserLogModel();
        
        $limit = $this->request->getGet('length') ?: 10;
        $offset = $this->request->getGet('start') ?: 0;
        $searchParam = $this->request->getGet('search');
        $search = (is_array($searchParam) && isset($searchParam['value'])) ? $searchParam['value'] : '';

        $builder = $logModel->builder();
        
        if (!empty($search)) {
            $builder->groupStart()
                ->like('username', $search)
                ->orLike('action', $search)
                ->orLike('ip_address', $search)
                ->orLike('user_agent', $search)
                ->groupEnd();
        }

        // Count total filtered
        $totalFiltered = $builder->countAllResults(false);
        
        // Order by latest
        $builder->orderBy('created_at', 'DESC');
        $builder->limit($limit, $offset);
        
        $data = $builder->get()->getResultArray();
        
        foreach ($data as &$row) {
            $ua = $row['user_agent'];
            $os = 'Unknown OS';
            $browser = 'Unknown Browser';
            
            if (preg_match('/windows nt 10/i', $ua)) $os = 'Windows 10/11';
            elseif (preg_match('/windows nt 6\.3/i', $ua)) $os = 'Windows 8.1';
            elseif (preg_match('/windows nt 6\.2/i', $ua)) $os = 'Windows 8';
            elseif (preg_match('/windows nt 6\.1/i', $ua)) $os = 'Windows 7';
            elseif (preg_match('/macintosh|mac os x/i', $ua)) $os = 'Mac OS';
            elseif (preg_match('/linux/i', $ua)) $os = 'Linux';
            elseif (preg_match('/android/i', $ua)) $os = 'Android';
            elseif (preg_match('/iphone|ipad|ipod/i', $ua)) $os = 'iOS';
            
            if (preg_match('/edg/i', $ua)) $browser = 'Edge';
            elseif (preg_match('/chrome|crios/i', $ua)) $browser = 'Chrome';
            elseif (preg_match('/firefox|fxios/i', $ua)) $browser = 'Firefox';
            elseif (preg_match('/safari/i', $ua)) $browser = 'Safari';
            elseif (preg_match('/opr|opera/i', $ua)) $browser = 'Opera';
            
            $device = preg_match('/mobile|android|iphone|ipad|ipod/i', $ua) ? 'Mobile' : 'Desktop';
            $icon = $device === 'Mobile' ? 'bx-mobile' : 'bx-desktop';
            
            $row['parsed_agent'] = "<i class='bx {$icon} me-1'></i>{$os} &middot; {$browser}";
        }

        $totalData = $logModel->countAllResults();

        return $this->response->setJSON([
            'draw' => intval($this->request->getGet('draw')),
            'recordsTotal' => $totalData,
            'recordsFiltered' => $totalFiltered,
            'data' => $data
        ]);
    }

    public function clear()
    {
        // Must be admin
        if (session()->get('role') !== 'admin') {
            return redirect()->back()->with('error', 'Unauthorized access');
        }

        $logModel = new UserLogModel();
        
        // Truncate or delete all
        $logModel->emptyTable(); // Empties the table and resets auto-increment
        
        // Log this action itself
        $this->logActivity('Cleared all user activity logs');

        return redirect()->to(base_url('user/logs'))->with('success', 'Semua riwayat log aktivitas berhasil dihapus.');
    }
}
