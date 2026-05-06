<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    public function index()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }
        return view('auth/login');
    }

    public function login()
    {
        $rules = [
            'username' => 'required|alpha_numeric',
            'password' => 'required|min_length[5]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $model = new UserModel();
        $user = $model->where('username', $this->request->getVar('username'))->first();

        if ($user && password_verify($this->request->getVar('password'), $user['password'])) {
            $this->setUserSession($user);
            return redirect()->to('/dashboard');
        }

        return redirect()->back()->withInput()->with('error', 'Username atau Password salah');
    }

    private function setUserSession($user)
    {
        $data = [
            'id'           => $user['id'],
            'username'     => $user['username'],
            'nama_lengkap' => $user['nama_lengkap'],
            'role'         => $user['role'],
            'kd_satker'    => $user['kd_satker'],
            'isLoggedIn'   => true,
        ];

        session()->set($data);
        return true;
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/auth');
    }
}
