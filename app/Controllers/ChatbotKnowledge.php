<?php

namespace App\Controllers;

use App\Models\ChatbotKnowledgeModel;
use CodeIgniter\RESTful\ResourceController;
use Smalot\PdfParser\Parser;

class ChatbotKnowledge extends ResourceController
{
    public function index()
    {
        $model = new ChatbotKnowledgeModel();
        
        // Group by document_title to show the list of uploaded documents
        $db = \Config\Database::connect();
        $builder = $db->table('chatbot_knowledge');
        $builder->select('document_title, COUNT(*) as chunks, MAX(created_at) as uploaded_at');
        $builder->groupBy('document_title');
        $builder->orderBy('uploaded_at', 'DESC');
        
        $documents = $builder->get()->getResultArray();
        
        return view('user/chatbot_knowledge', [
            'title' => 'Library Kepintaran AI',
            'user'  => session()->get(),
            'documents' => $documents
        ]);
    }

    public function upload()
    {
        // Tingkatkan memori dan waktu eksekusi untuk menangani dokumen besar
        ini_set('memory_limit', '512M');
        set_time_limit(300); // 5 menit

        $file = $this->request->getFile('pdf_document');
        $ext = strtolower($file->getExtension());
        if (!$file->isValid() || !in_array($ext, ['pdf', 'txt'])) {
            return redirect()->back()->with('error', 'File tidak valid. Harap unggah PDF atau TXT.');
        }

        $filename = $file->getName();
        // Cek jika dokumen sudah ada
        $model = new ChatbotKnowledgeModel();
        $existing = $model->where('document_title', $filename)->first();
        if ($existing) {
            return redirect()->back()->with('error', 'Dokumen dengan nama ini sudah ada di library.');
        }

        // Simpan file sementara
        $tempPath = $file->getTempName();

        try {
            $text = '';
            if ($ext === 'pdf') {
                $parser = new Parser();
                $pdf = $parser->parseFile($tempPath);
                $text = $pdf->getText();
            } else {
                // Jika file TXT, langsung baca isinya
                $text = file_get_contents($tempPath);
            }
            
            // Bersihkan text (hapus multiple whitespace, baris kosong)
            $text = preg_replace('/\s+/', ' ', $text);

            // Chunking text (misalnya per 1000 karakter)
            $rawChunks = str_split($text, 1000);
            
            // Bersihkan chunk yang kosong atau hanya spasi
            $chunks = array_values(array_filter(array_map('trim', $rawChunks)));

            if (empty($chunks)) {
                return redirect()->back()->with('error', 'Dokumen tidak memiliki teks yang valid untuk diproses.');
            }
            
            $settingsModel = new \App\Models\SettingsModel();
            $apiKey = $settingsModel->getSetting('embedding_api_key', env('OPENAI_API_KEY'));
            $embeddingUrl = $settingsModel->getSetting('embedding_api_url', 'https://api.openai.com/v1/embeddings');
            $embeddingModel = $settingsModel->getSetting('embedding_model', 'text-embedding-3-small');

            $db = \Config\Database::connect();
            $db->transStart();

            $successCount = 0;
            $lastError = '';

            // Proses embedding dalam batch (misalnya 20 chunks per API call)
            // Ini akan sangat memangkas jumlah request cURL (dari misal 100 request menjadi 5 request)
            $batchSize = 20;
            $chunkBatches = array_chunk($chunks, $batchSize);

            foreach ($chunkBatches as $batch) {
                $embedRes = $this->getBatchEmbeddings($batch, $apiKey, $embeddingUrl, $embeddingModel);
                
                if (isset($embedRes['error'])) {
                    // Jika batch gagal, coba satu per satu sebagai fallback
                    $lastError = $embedRes['error'];
                    foreach ($batch as $chunkText) {
                        $singleRes = $this->getEmbedding($chunkText, $apiKey, $embeddingUrl, $embeddingModel);
                        if (isset($singleRes['error'])) {
                            $lastError = $singleRes['error'];
                            continue;
                        }
                        $model->insert([
                            'document_title' => $filename,
                            'chunk_text' => $chunkText,
                            'embedding_vector' => json_encode($singleRes['embedding']),
                        ]);
                        $successCount++;
                    }
                } else {
                    $embeddings = $embedRes['embeddings'];
                    foreach ($batch as $idx => $chunkText) {
                        if (isset($embeddings[$idx])) {
                            $model->insert([
                                'document_title' => $filename,
                                'chunk_text' => $chunkText,
                                'embedding_vector' => json_encode($embeddings[$idx]),
                            ]);
                            $successCount++;
                        }
                    }
                }
            }

            $db->transComplete();

            if ($successCount === 0 && $lastError !== '') {
                return redirect()->back()->with('error', "Gagal membuat vektor. Pastikan API Chatbot Anda mendukung endpoint /embeddings dan model text-embedding-3-small. Error: " . $lastError);
            }

            return redirect()->back()->with('success', "Berhasil membaca dan menyimpan $successCount potongan teks dari $filename.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses PDF: ' . $e->getMessage());
        }
    }
    
    public function delete($id = null)
    {
        $title = $this->request->getPost('document_title');
        $model = new ChatbotKnowledgeModel();
        $model->where('document_title', $title)->delete();
        return redirect()->back()->with('success', "Dokumen $title berhasil dihapus dari library.");
    }

    private function getBatchEmbeddings($texts, $apiKey, $url, $model)
    {
        $data = [
            'model' => $model,
            'input' => $texts
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
            'api-key: ' . $apiKey
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) return ['error' => 'cURL Error: ' . $error];

        $resObj = json_decode($response, true);
        if (isset($resObj['data']) && is_array($resObj['data'])) {
            // Urutkan data berdasarkan index agar posisinya presisi sesuai input
            usort($resObj['data'], function($a, $b) {
                return $a['index'] <=> $b['index'];
            });

            $embeddings = [];
            foreach ($resObj['data'] as $item) {
                if (isset($item['embedding'])) {
                    $embeddings[] = $item['embedding'];
                }
            }
            return ['embeddings' => $embeddings];
        }

        return ['error' => 'Response API tidak valid: ' . $response];
    }

    private function getEmbedding($text, $apiKey, $url, $model)
    {
        $data = [
            'model' => $model,
            'input' => $text
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
            'api-key: ' . $apiKey
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) return ['error' => 'cURL Error: ' . $error];

        $resObj = json_decode($response, true);
        if (isset($resObj['data'][0]['embedding'])) {
            return ['embedding' => $resObj['data'][0]['embedding']];
        }

        return ['error' => 'Response API tidak valid: ' . $response];
    }
}
