<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\Controller;

class UserController extends BaseController
{
    public function index()
    {
        $model = new UserModel();
        $data = [
            'title' => 'Manajemen User',
            'users' => $model->findAll(),
        ];
        return view('user/index', $data);
    }

    public function create()
    {
        $data = ['title' => 'Tambah User Baru'];
        return view('user/create', $data);
    }

    public function store()
    {
        $rules = [
            'username'     => 'required|alpha_numeric|is_unique[users.username]',
            'password'     => 'required|min_length[8]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/]',
            'nama_lengkap' => 'required',
            'role'         => 'required|in_list[admin,operator,ppk,satker,opd]',
        ];

        $messages = [
            'password' => [
                'min_length'  => 'Password minimal harus 8 karakter.',
                'regex_match' => 'Password harus mengandung kombinasi huruf besar (A-Z), huruf kecil (a-z), angka (0-9), dan simbol/karakter khusus (!@#$%^&* dll).',
            ]
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $model = new UserModel();
        $model->save([
            'username'     => $this->request->getPost('username'),
            'password'     => $this->request->getPost('password'),
            'nama_lengkap' => $this->request->getPost('nama_lengkap'),
            'role'         => $this->request->getPost('role'),
            'kd_satker'    => $this->request->getPost('kd_satker'),
            'email'        => $this->request->getPost('email'),
            'nomor_hp'     => $this->request->getPost('nomor_hp'),
        ]);

        return redirect()->to('/user')->with('success', 'User berhasil ditambahkan');
    }

    public function edit($encoded_id)
    {
        $id = decode_id($encoded_id);
        if (!$id) {
            return redirect()->to('/user')->with('error', 'URL tidak valid');
        }

        $model = new UserModel();
        $user = $model->find($id);
        
        if (!$user) {
            return redirect()->to('/user')->with('error', 'User tidak ditemukan');
        }

        $data = [
            'title' => 'Edit User',
            'user'  => $user
        ];
        return view('user/edit', $data);
    }

    public function update($encoded_id)
    {
        $id = decode_id($encoded_id);
        if (!$id) {
            return redirect()->to('/user')->with('error', 'URL tidak valid');
        }

        $model = new UserModel();
        $user = $model->find($id);

        $rules = [
            'username'     => "required|alpha_numeric|is_unique[users.username,id,{$id}]",
            'nama_lengkap' => 'required',
            'role'         => 'required',
        ];

        $messages = [];
        if ($this->request->getPost('password')) {
            $rules['password'] = 'min_length[8]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/]';
            $messages['password'] = [
                'min_length'  => 'Password minimal harus 8 karakter.',
                'regex_match' => 'Password baru harus mengandung kombinasi huruf besar (A-Z), huruf kecil (a-z), angka (0-9), dan simbol/karakter khusus (!@#$%^&* dll).',
            ];
        }

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Gunakan explicit fields untuk mencegah mass assignment
        $data = [
            'username'     => $this->request->getPost('username'),
            'nama_lengkap' => $this->request->getPost('nama_lengkap'),
            'role'         => $this->request->getPost('role'),
            'kd_satker'    => $this->request->getPost('kd_satker'),
            'email'        => $this->request->getPost('email'),
            'nomor_hp'     => $this->request->getPost('nomor_hp'),
        ];
        if (!empty($this->request->getPost('password'))) {
            $data['password'] = $this->request->getPost('password');
        }

        $model->update($id, $data);

        // Jika user yang diubah adalah user yang sedang login, update sesi dan regenerate session ID
        if (session()->get('id') == $id) {
            session()->regenerate(true);
            session()->set([
                'username'     => $data['username'],
                'nama_lengkap' => $data['nama_lengkap'],
                'role'         => $data['role'],
                'kd_satker'    => $data['kd_satker'],
            ]);
        }

        return redirect()->to('/user')->with('success', 'User berhasil diperbarui');
    }

    public function delete($encoded_id)
    {
        $id = decode_id($encoded_id);
        if (!$id) {
            return redirect()->to('/user')->with('error', 'URL tidak valid');
        }

        $model = new UserModel();
        $model->delete($id);
        return redirect()->to('/user')->with('success', 'User berhasil dihapus');
    }

    public function reset2fa($encoded_id)
    {
        $id = decode_id($encoded_id);
        if (!$id) {
            return redirect()->to('/user')->with('error', 'URL tidak valid');
        }

        $model = new UserModel();
        $model->update($id, ['google2fa_secret' => null]);
        
        return redirect()->to('/user')->with('success', '2FA berhasil direset untuk user tersebut.');
    }
}
