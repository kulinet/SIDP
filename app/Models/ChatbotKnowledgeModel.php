<?php

namespace App\Models;

use CodeIgniter\Model;

class ChatbotKnowledgeModel extends Model
{
    protected $table = 'chatbot_knowledge';
    protected $primaryKey = 'id';
    protected $allowedFields = ['document_title', 'chunk_text', 'embedding_vector', 'created_at', 'updated_at'];
    protected $useTimestamps = true;
}
