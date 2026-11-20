<?php

namespace App\Services;

use CodeIgniter\Database\ConnectionInterface;

class MonevDashboardService
{
    protected ConnectionInterface $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Get Aggregated Early Warning KPIs
     */
    public function getAggregatedKpis(): array
    {
        $totalProyek = $this->db->table('monev_proyek')->countAllResults();
        
        // Active projects (having at least one monev_period or ongoing status)
        // For simplicity, we'll consider all projects in monev_proyek as "active" for now unless they have a 'completed' status.
        $proyekAktif = $totalProyek; 
        
        // Count open findings
        $findingsOpen = $this->db->table('monev_finding')
                                 ->whereIn('status', ['OPEN', 'IN_PROGRESS'])
                                 ->countAllResults();
                                 
        // Findings overdue
        $findingsOverdue = $this->db->table('monev_finding')
                                    ->whereIn('status', ['OPEN', 'IN_PROGRESS'])
                                    ->where('due_date <', date('Y-m-d'))
                                    ->countAllResults();
                                    
        // Follow-ups overdue
        $followupsOverdue = $this->db->table('monev_followup')
                                     ->whereIn('status', ['OPEN', 'IN_PROGRESS'])
                                     ->where('deadline <', date('Y-m-d'))
                                     ->countAllResults();

        // Calculate average performance and delayed/critical projects
        $avgPerformance = 0;
        $totalFinalized = 0;
        
        $proyekTerlambat = 0;
        $proyekKritis = 0;
        
        $monevOverdue = 0;
        
        // Get the latest finalized period for each project
        $projects = $this->db->table('monev_proyek')->get()->getResultArray();
        
        foreach ($projects as $proj) {
            $latestMonev = $this->db->table('monev_period')
                                    ->where('proyek_id', $proj['id'])
                                    ->orderBy('id', 'DESC')
                                    ->get()
                                    ->getRowArray();
                                    
            if ($latestMonev) {
                if ($latestMonev['status'] === 'FINALIZED') {
                    $totalFinalized++;
                    $avgPerformance += (float) $latestMonev['skor_kinerja_total'];
                }
                
                $progSnap = !empty($latestMonev['progress_snapshot']) && is_string($latestMonev['progress_snapshot']) 
                            ? json_decode($latestMonev['progress_snapshot'], true) 
                            : ($latestMonev['progress_snapshot'] ?? []);
                            
                $progAktual = (float) ($progSnap['progress_aktual_verified'] ?? ($progSnap['progres_persen'] ?? 0));
                $progRencana = (float) ($progSnap['progress_rencana'] ?? 0);
                $deviasi = round($progAktual - $progRencana, 2);
                
                if ($deviasi < 0) {
                    $proyekTerlambat++;
                    if ($deviasi <= -10) {
                        $proyekKritis++;
                    }
                }
                
                // Monev overdue logic: if latest monev was updated more than 30 days ago
                if (strtotime($latestMonev['updated_at']) < strtotime('-30 days')) {
                    $monevOverdue++;
                }
                
            } else {
                // No monev session means overdue
                $monevOverdue++;
            }
        }
        
        if ($totalFinalized > 0) {
            $avgPerformance = round($avgPerformance / $totalFinalized, 1);
        }

        return [
            'total_proyek' => $totalProyek,
            'proyek_aktif' => $proyekAktif,
            'proyek_terlambat' => $proyekTerlambat,
            'proyek_kritis' => $proyekKritis,
            'avg_performance' => $avgPerformance,
            'findings_open' => $findingsOpen,
            'findings_overdue' => $findingsOverdue,
            'followups_overdue' => $followupsOverdue,
            'monev_overdue' => $monevOverdue,
        ];
    }

    /**
     * Get Project Health Matrix
     */
    public function getProjectHealthList(): array
    {
        $projects = $this->db->table('monev_proyek')->orderBy('id', 'DESC')->get()->getResultArray();
        $healthList = [];
        
        foreach ($projects as $proj) {
            $latestMonev = $this->db->table('monev_period')
                                    ->where('proyek_id', $proj['id'])
                                    ->orderBy('id', 'DESC')
                                    ->get()
                                    ->getRowArray();
                                    
            $health = [
                'id' => $proj['id'],
                'nama_proyek' => $proj['nama_proyek'],
                'penyedia' => $proj['nama_penyedia'],
                'latest_monev_date' => $latestMonev ? $latestMonev['tgl_rencana_monev'] : null,
                'status_monev' => $latestMonev ? $latestMonev['status'] : 'BELUM_ADA',
                
                // Health Badges (GREEN, YELLOW, RED)
                'health_schedule' => 'GREEN',
                'health_performance' => 'GREEN',
                'health_findings' => 'GREEN',
                
                // Data
                'deviasi' => 0,
                'skor' => 0,
                'open_findings' => 0
            ];
            
            if ($latestMonev) {
                // Schedule Health
                $progSnap = !empty($latestMonev['progress_snapshot']) && is_string($latestMonev['progress_snapshot']) 
                            ? json_decode($latestMonev['progress_snapshot'], true) 
                            : ($latestMonev['progress_snapshot'] ?? []);
                            
                $progAktual = (float) ($progSnap['progress_aktual_verified'] ?? ($progSnap['progres_persen'] ?? 0));
                $progRencana = (float) ($progSnap['progress_rencana'] ?? 0);
                $deviasi = round($progAktual - $progRencana, 2);
                
                $health['deviasi'] = $deviasi;
                if ($deviasi <= -10) {
                    $health['health_schedule'] = 'RED';
                } elseif ($deviasi < -5) {
                    $health['health_schedule'] = 'YELLOW';
                }
                
                // Performance Health
                $skor = (float) $latestMonev['skor_kinerja_total'];
                $health['skor'] = $skor;
                if ($latestMonev['status'] === 'FINALIZED') {
                    if ($skor < 20) {
                        $health['health_performance'] = 'RED';
                    } elseif ($skor < 28) {
                        $health['health_performance'] = 'YELLOW';
                    }
                } else {
                    $health['health_performance'] = 'GREY'; // Not finalized yet
                }
                
                // Findings Health
                $findings = $this->db->table('monev_finding')
                                     ->where('monev_period_id', $latestMonev['id'])
                                     ->whereIn('status', ['OPEN', 'IN_PROGRESS'])
                                     ->get()
                                     ->getResultArray();
                                     
                $health['open_findings'] = count($findings);
                foreach ($findings as $f) {
                    if ($f['severity'] === 'CRITICAL') {
                        $health['health_findings'] = 'RED';
                        break;
                    } elseif ($f['severity'] === 'HIGH' && $health['health_findings'] !== 'RED') {
                        $health['health_findings'] = 'YELLOW';
                    }
                }
            } else {
                $health['health_schedule'] = 'GREY';
                $health['health_performance'] = 'GREY';
                $health['health_findings'] = 'GREY';
            }
            
            $healthList[] = $health;
        }
        
        return $healthList;
    }
}
