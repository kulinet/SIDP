<?php

namespace App\Models;

use CodeIgniter\Model;

class ChatbotLogModel extends Model
{
    protected $table            = 'chatbot_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['user_id', 'username', 'ip_address', 'question', 'response', 'model', 'status'];

    protected $useTimestamps = false;
}
