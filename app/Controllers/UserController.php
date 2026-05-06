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
            'password'     => 'required|min_length[5]',
            'nama_lengkap' => 'required',
            'role'         => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $model = new UserModel();
        $model->save($this->request->getPost());

        return redirect()->to('/user')->with('success', 'User berhasil ditambahkan');
    }

    public function edit($id)
    {
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

    public function update($id)
    {
        $model = new UserModel();
        $user = $model->find($id);

        $rules = [
            'username'     => "required|alpha_numeric|is_unique[users.username,id,{$id}]",
            'nama_lengkap' => 'required',
            'role'         => 'required',
        ];

        if ($this->request->getPost('password')) {
            $rules['password'] = 'min_length[5]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = $this->request->getPost();
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $model->update($id, $data);

        return redirect()->to('/user')->with('success', 'User berhasil diperbarui');
    }

    public function delete($id)
    {
        $model = new UserModel();
        $model->delete($id);
        return redirect()->to('/user')->with('success', 'User berhasil dihapus');
    }
}
