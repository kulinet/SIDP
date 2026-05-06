<?php

namespace App\Models;

use CodeIgniter\Model;

class DynamicColumnModel extends Model
{
    protected $table            = 'dynamic_columns';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['menu_id', 'field_key', 'label', 'order_index', 'is_searchable', 'is_sortable', 'formatter'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
