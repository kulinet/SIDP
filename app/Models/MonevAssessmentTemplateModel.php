<?php

namespace App\Models;

use CodeIgniter\Model;

class MonevAssessmentTemplateModel extends Model
{
    protected $table            = 'monev_assessment_templates';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'kode_template',
        'nama_template',
        'versi',
        'is_active',
        'deskripsi',
        'threshold_config',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Mengambil template aktif beserta seluruh aspek dan indikatornya.
     */
    public function getActiveTemplateWithStructure($templateId = null)
    {
        $builder = $this->db->table($this->table);
        if ($templateId) {
            $builder->where('id', $templateId);
        } else {
            $builder->where('is_active', 1)->orderBy('id', 'DESC');
        }

        $template = $builder->get()->getRowArray();
        if (!$template) {
            return null;
        }

        if (!empty($template['threshold_config']) && is_string($template['threshold_config'])) {
            $template['threshold_config'] = json_decode($template['threshold_config'], true);
        }

        $aspects = $this->db->table('monev_assessment_aspects')
            ->where('template_id', $template['id'])
            ->orderBy('urutan', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($aspects as &$aspect) {
            $aspect['indicators'] = $this->db->table('monev_assessment_indicators')
                ->where('aspect_id', $aspect['id'])
                ->orderBy('urutan', 'ASC')
                ->get()
                ->getResultArray();
        }

        $template['aspects'] = $aspects;
        return $template;
    }
}
