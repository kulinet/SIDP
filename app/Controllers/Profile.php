<?php

namespace App\Controllers;

use App\Models\UserModel;

class Profile extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index()
    {
        $userId = session()->get('id');
        $user = $this->userModel->find($userId);

        if (!$user) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login kembali.');
        }

        $data = [
            'title' => 'Profil Pengguna',
            'user'  => $user,
        ];

        $agent = $this->request->getUserAgent();
        if ($agent->isMobile() && $this->request->getGet('force_desktop') !== '1') {
            return view('profile/mobile_index', $data);
        }

        return view('profile/index', $data);
    }

    public function update()
    {
        $userId = session()->get('id');
        
        $rules = [
            'email'    => 'permit_empty|valid_email',
            'nomor_hp' => 'permit_empty|numeric|min_length[9]|max_length[15]',
        ];

        $messages = [];
        // Validasi opsional password baru
        $password = $this->request->getPost('password');
        if (!empty($password)) {
            $rules['password'] = 'min_length[8]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/]';
            $messages['password'] = [
                'min_length'  => 'Password minimal harus 8 karakter.',
                'regex_match' => 'Password baru harus mengandung kombinasi huruf besar (A-Z), huruf kecil (a-z), angka (0-9), dan simbol/karakter khusus (!@#$%^&* dll).',
            ];
        }

        $avatar = $this->request->getFile('avatar');
        if ($avatar && $avatar->isValid() && !$avatar->hasMoved()) {
            $uploadErr = '';
            if (!is_secure_uploaded_file($avatar, ['image'], 2097152, $uploadErr)) {
                return redirect()->back()->withInput()->with('errors', ['avatar' => $uploadErr]);
            }
            $rules['avatar'] = 'max_size[avatar,2048]';
        }

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $updateData = [
            'email'    => $this->request->getPost('email'),
            'nomor_hp' => $this->request->getPost('nomor_hp'),
        ];

        if (!empty($password)) {
            $updateData['password'] = $password; // UserModel hashPassword hook will hash it automatically
        }

        if ($avatar && $avatar->isValid() && !$avatar->hasMoved()) {
            $newName = $avatar->getRandomName();
            $avatar->move(FCPATH . 'uploads/avatars', $newName);
            $updateData['avatar'] = $newName;
            session()->set('avatar', $newName); // update session to reflect immediately
        }

        $this->userModel->update($userId, $updateData);

        return redirect()->back()->with('success', 'Profil berhasil diperbarui!');
    }
}
