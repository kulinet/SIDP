<?php

namespace App\Controllers;

use App\Models\SettingsModel;

class SettingsController extends BaseController
{
    protected $settingsModel;

    public function __construct()
    {
        $this->settingsModel = new SettingsModel();
        
        // Extra check to ensure only admin can access
        if (session()->get('role') !== 'admin') {
            header('Location: ' . base_url('dashboard'));
            exit;
        }
    }

    public function chatbot()
    {
        $data = [
            'title' => 'Chatbot Settings',
            'settings' => [
                'model' => $this->settingsModel->getSetting('chatbot_model', 'gpt-5.4-mini'),
                'api_key' => $this->settingsModel->getSetting('chatbot_api_key', ''),
                'api_url' => $this->settingsModel->getSetting('chatbot_api_url', 'https://api.ai.cc/v1/chat/completions'),
            ]
        ];

        return view('user/chatbot_settings', $data);
    }

    public function saveChatbot()
    {
        $rules = [
            'chatbot_model' => 'required',
            'chatbot_api_key' => 'required',
            'chatbot_api_url' => 'required|valid_url',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->settingsModel->updateSetting('chatbot_model', $this->request->getPost('chatbot_model'));
        $this->settingsModel->updateSetting('chatbot_api_key', $this->request->getPost('chatbot_api_key'));
        $this->settingsModel->updateSetting('chatbot_api_url', $this->request->getPost('chatbot_api_url'));

        return redirect()->to(base_url('user/chatbot-settings'))->with('success', 'Chatbot settings updated successfully.');
    }

    public function api()
    {
        $userModel = new \App\Models\UserModel();
        $user = $userModel->find(session()->get('id'));

        $data = [
            'title' => 'API Security Settings',
            'user'  => $user
        ];

        return view('user/api_settings', $data);
    }

    public function generateToken()
    {
        $userModel = new \App\Models\UserModel();
        $userId = session()->get('id');
        
        $token = bin2hex(random_bytes(32)); // Secure 64-char token
        
        $userModel->update($userId, ['api_token' => $token]);

        return redirect()->to(base_url('user/api-settings'))->with('success', 'New API Token generated successfully.');
    }
}
